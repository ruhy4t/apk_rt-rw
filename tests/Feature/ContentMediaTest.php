<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\GalleryItem;
use App\Models\Household;
use App\Models\Resident;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        config(['filesystems.private_files_enabled' => true]);
        Storage::fake('b2');
    }

    private function operator(): User
    {
        $rw = RwArea::query()->create(['code' => '01', 'name' => 'RW Uji']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => '05', 'name' => 'RT Uji', 'chairman_name' => 'Ketua RT Uji']);

        return User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id]);
    }

    private function announcement(User $actor, array $changes = []): Announcement
    {
        return Announcement::query()->create(array_replace([
            'rt_area_id' => $actor->rt_area_id, 'created_by' => $actor->id, 'title' => 'Pengumuman Uji',
            'body' => 'Kegiatan lingkungan', 'audience' => 'public', 'is_published' => true,
            'signatory_data' => ['name' => 'Ketua RT Uji', 'title' => 'Ketua RT 05', 'area_type' => 'rt', 'area_id' => $actor->rt_area_id, 'area_code' => '05', 'issued_at' => now()->toIso8601String()],
        ], $changes));
    }

    private function attach($content, User $actor): StoredFile
    {
        Storage::disk('b2')->put('content-photos/test.jpg', 'synthetic-image');

        return $content->photo()->create(['uploaded_by' => $actor->id, 'disk' => 'b2', 'object_key' => 'content-photos/test.jpg', 'original_name' => 'foto.jpg', 'mime_type' => 'image/jpeg', 'size_bytes' => 15]);
    }

    public function test_staff_can_attach_photos_to_announcement_and_agenda_and_home_renders_them(): void
    {
        $actor = $this->operator();
        $this->actingAs($actor)->post(route('announcements.store'), ['rt_area_id' => $actor->rt_area_id, 'title' => 'Foto pengumuman', 'body' => 'Kabar lingkungan', 'audience' => 'public', 'is_published' => 1, 'photo' => UploadedFile::fake()->image('photo.jpg', 400, 300)])
            ->assertRedirect()->assertSessionHasNoErrors();
        $announcement = Announcement::query()->firstOrFail();
        $this->assertNotNull($announcement->photo);
        Storage::disk('b2')->assertExists($announcement->photo->object_key);
        $this->actingAs($actor)->post(route('agenda.store'), ['rt_area_id' => $actor->rt_area_id, 'title' => 'Foto agenda', 'starts_at' => now()->addDay()->format('Y-m-d H:i:s'), 'audience' => 'public', 'is_published' => 1, 'photo' => UploadedFile::fake()->image('agenda.png', 400, 300)])
            ->assertRedirect()->assertSessionHasNoErrors();
        $event = CommunityEvent::query()->firstOrFail();
        $this->assertNotNull($event->photo);
        $this->get('/')->assertOk()->assertSee(route('content-photos.show', $announcement->photo), false)->assertSee(route('content-photos.show', $event->photo), false);
    }

    public function test_photo_content_is_stored_on_b2_and_not_on_application_disks(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $actor = $this->operator();
        $upload = UploadedFile::fake()->image('photo.jpg', 400, 300);
        $bytes = file_get_contents($upload->getPathname());
        $this->actingAs($actor)->post(route('announcements.store'), [
            'rt_area_id' => $actor->rt_area_id, 'title' => 'Foto B2 Uji', 'body' => 'Dokumentasi uji',
            'audience' => 'residents', 'photo' => $upload,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $photo = Announcement::query()->firstOrFail()->photo;
        $this->assertSame('b2', $photo->disk);
        $this->assertSame($bytes, Storage::disk('b2')->get($photo->object_key));
        $this->assertSame('foto.jpg', $photo->original_name);
        $this->assertSame($upload->getSize(), $photo->size_bytes);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('stored_files', 1);
    }

    public function test_public_photo_rechecks_parent_publication_and_private_photo_requires_matching_rt(): void
    {
        $actor = $this->operator();
        $announcement = $this->announcement($actor);
        $photo = $this->attach($announcement, $actor);
        $this->get(route('content-photos.show', $photo))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $announcement->update(['is_published' => false]);
        $this->get(route('content-photos.show', $photo))->assertNotFound();
        $this->actingAs($actor)->get(route('content-photos.show', $photo))->assertOk();
        $announcement->update(['is_published' => true, 'audience' => 'residents']);
        $outsider = User::factory()->create(['role' => 'resident']);
        $this->actingAs($outsider)->get(route('content-photos.show', $photo))->assertNotFound();
        $household = Household::query()->create(['rt_area_id' => $actor->rt_area_id, 'household_code' => 'SYNTHETIC']);
        Resident::query()->create(['household_id' => $household->id, 'user_id' => $outsider->id, 'full_name' => 'Warga Uji', 'relationship' => 'Anak']);
        $this->actingAs($outsider)->get(route('content-photos.show', $photo))->assertOk();
        $outsider->update(['is_active' => false]);
        $this->actingAs($outsider)->get(route('content-photos.show', $photo))->assertNotFound();
    }

    public function test_photo_rejects_svg_oversized_dimensions_and_cross_rt_writes(): void
    {
        $actor = $this->operator();
        $data = ['rt_area_id' => $actor->rt_area_id, 'title' => 'Foto Uji', 'body' => 'Isi', 'audience' => 'public'];
        $this->actingAs($actor)->post(route('announcements.store'), [...$data, 'photo' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')])->assertSessionHasErrors('photo');
        $this->post(route('announcements.store'), [...$data, 'photo' => UploadedFile::fake()->image('big.jpg', 2500, 2)])->assertSessionHasErrors('photo');
        $otherRt = RtArea::query()->create(['rw_area_id' => $actor->rtArea->rw_area_id, 'code' => '06', 'name' => 'RT Lain']);
        $this->post(route('announcements.store'), [...$data, 'rt_area_id' => $otherRt->id, 'photo' => UploadedFile::fake()->image('small.jpg')])->assertForbidden();
        $this->assertDatabaseCount('announcements', 0);
        $this->assertDatabaseCount('stored_files', 0);
        $this->assertSame([], Storage::disk('b2')->allFiles());
    }

    public function test_photo_can_be_replaced_or_removed_without_overwriting_content_on_upload_failure(): void
    {
        $actor = $this->operator();
        $announcement = $this->announcement($actor);
        $old = $this->attach($announcement, $actor);
        $data = ['title' => 'Judul Baru', 'body' => 'Isi', 'audience' => 'public', 'is_published' => 1];
        $this->actingAs($actor)->patch(route('announcements.update', $announcement), [...$data, 'photo' => UploadedFile::fake()->image('new.jpg')])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('stored_files', 1);
        $new = $announcement->fresh()->photo;
        $this->assertNotSame($old->id, $new->id);
        Storage::disk('b2')->assertExists($new->object_key);
        config(['filesystems.private_files_enabled' => false]);
        $this->patch(route('announcements.update', $announcement), [...$data, 'title' => 'Tidak Disimpan', 'photo' => UploadedFile::fake()->image('blocked.jpg')])->assertSessionHasErrors('photo');
        $this->assertSame('Judul Baru', $announcement->fresh()->title);
        $this->patch(route('announcements.update', $announcement), [...$data, 'remove_photo' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('stored_files', 0);
    }

    public function test_gallery_embeds_supported_video_and_limits_private_listing(): void
    {
        $actor = $this->operator();
        $base = ['rt_area_id' => $actor->rt_area_id, 'title' => 'Video Kegiatan', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=Test_vid001', 'audience' => 'public', 'is_published' => 1];
        $this->actingAs($actor)->post(route('gallery.store'), $base)->assertRedirect(route('gallery.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('gallery_items', 1);
        $this->get(route('gallery.index'))->assertOk()->assertSee('Buka video di www.youtube.com')
            ->assertSee('<iframe', false)->assertSee('https://www.youtube-nocookie.com/embed/Test_vid001', false)
            ->assertSee('referrerpolicy="strict-origin-when-cross-origin"', false)->assertSee('loading="lazy"', false)
            ->assertDontSee('<script src="https://', false)->assertDontSee('autoplay=1', false);
        $this->post(route('gallery.store'), [...$base, 'title' => 'Video Privat', 'video_url' => 'https://youtu.be/Hidden_0001', 'audience' => 'residents'])->assertSessionHasNoErrors();
        $this->post(route('gallery.store'), [...$base, 'title' => 'Video Draf', 'video_url' => 'https://youtu.be/Draft__0001', 'is_published' => 0])->assertSessionHasNoErrors();
        auth()->forgetGuards();
        $this->get(route('gallery.index'))->assertOk()->assertSee('Video Kegiatan')->assertDontSee('Video Privat')->assertDontSee('Video Draf')
            ->assertDontSee('Hidden_0001')->assertDontSee('Draft__0001');
    }

    public function test_new_unsupported_video_urls_are_rejected_and_legacy_links_remain_readable(): void
    {
        $actor = $this->operator();
        $base = ['rt_area_id' => $actor->rt_area_id, 'created_by' => $actor->id, 'title' => 'Video Lama', 'type' => 'video', 'video_url' => 'https://example.org/video/legacy', 'audience' => 'public', 'is_published' => 1];
        $item = GalleryItem::query()->create($base);
        $this->get(route('gallery.index'))->assertOk()->assertSee('Video Lama')->assertDontSee('<iframe', false)->assertSee('belum dapat diputar di galeri');
        $this->actingAs($actor)->patch(route('gallery.update', $item), $base)->assertSessionHasNoErrors();
        $this->post(route('gallery.store'), $base)->assertSessionHasErrors('video_url');
        $this->patch(route('gallery.update', $item), [...$base, 'video_url' => 'https://example.org/video/new'])->assertSessionHasErrors('video_url');
        $this->assertSame($base['video_url'], $item->fresh()->video_url);
        $this->assertDatabaseCount('gallery_items', 1);
    }

    public function test_gallery_rejects_unsafe_video_urls_and_resident_writes(): void
    {
        $actor = $this->operator();
        $base = ['rt_area_id' => $actor->rt_area_id, 'title' => 'Video Uji', 'type' => 'video', 'audience' => 'residents'];
        $this->actingAs($actor);
        foreach (['javascript:alert(1)', 'http://youtube.com/watch?v=x', 'https://name:password@youtube.com/watch?v=x', 'https://127.0.0.1/video', 'https://server.local/video'] as $url) {
            $this->post(route('gallery.store'), [...$base, 'video_url' => $url])->assertSessionHasErrors('video_url');
        }
        $this->actingAs(User::factory()->create(['role' => 'resident']))->post(route('gallery.store'), [...$base, 'video_url' => 'https://youtu.be/example'])->assertForbidden();
        $this->assertDatabaseCount('gallery_items', 0);
    }

    public function test_photo_gallery_requires_image_and_uses_same_access_checks(): void
    {
        $actor = $this->operator();
        $base = ['rt_area_id' => $actor->rt_area_id, 'title' => 'Foto Kegiatan', 'type' => 'photo', 'audience' => 'residents', 'is_published' => 1];
        $this->actingAs($actor)->post(route('gallery.store'), $base)->assertSessionHasErrors('photo');
        $this->post(route('gallery.store'), [...$base, 'photo' => UploadedFile::fake()->image('gallery.jpg')])->assertSessionHasNoErrors();
        $item = GalleryItem::query()->firstOrFail();
        $this->assertNotNull($item->photo);
        $this->actingAs(User::factory()->create(['role' => 'resident']))->get(route('gallery.index'))->assertDontSee('Foto Kegiatan');
        $this->get(route('content-photos.show', $item->photo))->assertNotFound();
    }

    public function test_missing_gallery_schema_keeps_home_and_gallery_readable_and_blocks_writes_clearly(): void
    {
        $actor = $this->operator();
        Schema::rename('gallery_items', 'gallery_items_unavailable');
        try {
            $this->get('/')->assertOk();
            $this->get(route('gallery.index'))->assertOk()->assertSee('Galeri belum tersedia');
            $this->actingAs($actor)->post(route('gallery.store'), [])->assertStatus(503);
        } finally {
            Schema::rename('gallery_items_unavailable', 'gallery_items');
        }
    }
}
