<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\Household;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentSignatoryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        config(['filesystems.private_files_enabled' => true]);
        Storage::fake('b2');
    }

    private function area(?string $chairman = null): array
    {
        $rw = RwArea::query()->create(['code' => '01', 'name' => 'RW Uji']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => '05', 'name' => 'RT Uji']);
        if ($chairman !== null) {
            $rt->forceFill(['chairman_name' => $chairman])->save();
        }
        $actor = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id]);

        return [$rw, $rt, $actor];
    }

    private function announcementData(RtArea $rt, array $changes = []): array
    {
        return array_replace(['rt_area_id' => $rt->id, 'title' => 'Pengumuman Uji', 'body' => 'Kabar lingkungan uji', 'audience' => 'public', 'is_published' => 0], $changes);
    }

    private function agendaData(RtArea $rt, array $changes = []): array
    {
        return array_replace(['rt_area_id' => $rt->id, 'title' => 'Agenda Uji', 'starts_at' => now()->addDay()->format('Y-m-d H:i:s'), 'audience' => 'public', 'is_published' => 0], $changes);
    }

    private function serviceRequest(RtArea $rt, string $category = 'letter'): ServiceRequest
    {
        $household = Household::query()->create(['rt_area_id' => $rt->id, 'household_code' => 'KEL-'.strtoupper($category)]);

        return ServiceRequest::query()->create([
            'household_id' => $household->id, 'requester_id' => User::factory()->create(['role' => 'resident'])->id,
            'category' => $category, 'subject' => 'Permintaan Uji', 'description' => 'Keperluan sintetis', 'status' => 'submitted',
        ]);
    }

    public function test_drafts_are_allowed_without_chairman_but_publication_has_no_partial_writes(): void
    {
        [, $rt, $actor] = $this->area();
        $this->actingAs($actor)->post(route('announcements.store'), $this->announcementData($rt))->assertSessionHasNoErrors();
        $this->post(route('agenda.store'), $this->agendaData($rt))->assertSessionHasNoErrors();
        $this->assertFalse(Announcement::query()->firstOrFail()->is_published);
        $this->assertFalse(CommunityEvent::query()->firstOrFail()->is_published);
        $this->post(route('announcements.store'), $this->announcementData($rt, ['is_published' => 1, 'photo' => UploadedFile::fake()->image('blocked.jpg')]))->assertSessionHasErrors('signatory');
        $this->post(route('agenda.store'), $this->agendaData($rt, ['is_published' => 1, 'photo' => UploadedFile::fake()->image('blocked.png')]))->assertSessionHasErrors('signatory');
        $this->assertDatabaseCount('announcements', 1);
        $this->assertDatabaseCount('community_events', 1);
        $this->assertDatabaseCount('stored_files', 0);
        $this->assertSame([], Storage::disk('b2')->allFiles());
    }

    public function test_rt_publication_captures_encrypted_server_selected_signatory(): void
    {
        [, $rt, $actor] = $this->area('Ketua RT Sintetis');
        $injected = ['name' => 'Nama Suntikan', 'title' => 'Ketua RW 99'];
        $this->actingAs($actor)->post(route('announcements.store'), $this->announcementData($rt, ['is_published' => 1, 'signatory_data' => $injected]))->assertSessionHasNoErrors();
        $this->post(route('agenda.store'), $this->agendaData($rt, ['is_published' => 1, 'signatory_data' => $injected]))->assertSessionHasNoErrors();
        foreach ([Announcement::query()->firstOrFail(), CommunityEvent::query()->firstOrFail()] as $document) {
            $this->assertSame('Ketua RT Sintetis', $document->signatory_data['name']);
            $this->assertSame('Ketua RT 05', $document->signatory_data['title']);
            $this->assertSame($rt->id, $document->signatory_data['area_id']);
            $this->assertSame('rt', $document->signatory_data['area_type']);
            $raw = $document->getRawOriginal('signatory_data');
            $this->assertStringNotContainsString('Ketua RT Sintetis', $raw);
            $this->assertSame($document->signatory_data, json_decode(Crypt::decryptString($raw), true, flags: JSON_THROW_ON_ERROR));
            $this->assertArrayNotHasKey('signatory_data', $document->toArray());
        }
        $this->get('/')->assertOk()->assertSee('Ketua RT Sintetis')->assertSee('Ketua RT 05')->assertDontSee('Nama Suntikan');
    }

    public function test_chairman_changes_do_not_rewrite_previously_published_signatory(): void
    {
        [, $rt, $actor] = $this->area('Ketua Pertama Uji');
        $this->actingAs($actor)->post(route('announcements.store'), $this->announcementData($rt, ['is_published' => 1]))->assertSessionHasNoErrors();
        $document = Announcement::query()->firstOrFail();
        $snapshot = $document->signatory_data;
        $rt->forceFill(['chairman_name' => 'Ketua Pengganti Uji'])->save();
        $this->patch(route('announcements.update', $document), $this->announcementData($rt, ['title' => 'Judul Diperbarui', 'is_published' => 1]))->assertSessionHasNoErrors();
        $this->assertSame($snapshot, $document->fresh()->signatory_data);
        $this->post(route('announcements.store'), $this->announcementData($rt, ['title' => 'Pengumuman Baru', 'is_published' => 1]))->assertSessionHasNoErrors();
        $newDocument = Announcement::query()->whereKeyNot($document->id)->firstOrFail();
        $this->assertSame('Ketua Pengganti Uji', $newDocument->signatory_data['name']);
        $this->assertSame('Ketua Pertama Uji', $document->fresh()->signatory_data['name']);
    }

    public function test_legacy_published_content_needs_explicit_draft_and_republication_before_signatory_capture(): void
    {
        [, $rt, $actor] = $this->area('Ketua Saat Ini Uji');
        $this->actingAs($actor);
        foreach ([
            [Announcement::class, 'announcements.update', $this->announcementData($rt, ['is_published' => 1])],
            [CommunityEvent::class, 'agenda.update', $this->agendaData($rt, ['is_published' => 1])],
        ] as [$model, $route, $data]) {
            $document = $model::query()->create([...$data, 'created_by' => $actor->id]);
            $this->assertNull($document->signatory_data);
            $this->patch(route($route, $document), [...$data, 'title' => 'Perubahan Ditolak'])->assertSessionHasErrors('is_published');
            $this->assertSame($data['title'], $document->fresh()->title);
            $this->assertNull($document->fresh()->signatory_data);
            $this->patch(route($route, $document), [...$data, 'is_published' => 0])->assertSessionHasNoErrors();
            $this->assertFalse($document->fresh()->is_published);
            $this->assertNull($document->fresh()->signatory_data);
            $this->patch(route($route, $document), $data)->assertSessionHasNoErrors();
            $this->assertSame('Ketua Saat Ini Uji', $document->fresh()->signatory_data['name']);
        }
    }

    public function test_rt_operator_cannot_publish_under_another_rt_or_the_whole_rw(): void
    {
        [$rw, $rt, $actor] = $this->area('Ketua Lingkup Sendiri');
        $otherRt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => '06', 'name' => 'RT Lain', 'chairman_name' => 'Ketua Lingkup Lain']);
        $this->actingAs($actor)->post(route('announcements.store'), $this->announcementData($otherRt, ['is_published' => 1]))->assertForbidden();
        $this->post(route('agenda.store'), $this->agendaData($otherRt, ['is_published' => 1]))->assertForbidden();
        $this->post(route('announcements.store'), $this->announcementData($rt, ['rt_area_id' => null, 'is_published' => 1]))->assertForbidden();
        $this->assertDatabaseCount('announcements', 0);
        $this->assertDatabaseCount('community_events', 0);
    }

    public function test_whole_rw_content_requires_and_records_rw_chairman(): void
    {
        [$rw, $rt] = $this->area('Ketua RT Uji');
        $admin = User::factory()->create(['role' => 'super_admin']);
        $announcement = $this->announcementData($rt, ['rt_area_id' => null, 'is_published' => 1]);
        $agenda = $this->agendaData($rt, ['rt_area_id' => null, 'is_published' => 1]);
        $this->actingAs($admin)->post(route('announcements.store'), $announcement)->assertSessionHasErrors('signatory');
        $this->post(route('agenda.store'), $agenda)->assertSessionHasErrors('signatory');
        $rw->forceFill(['chairman_name' => 'Ketua RW Sintetis'])->save();
        $this->post(route('announcements.store'), $announcement)->assertSessionHasNoErrors();
        $this->post(route('agenda.store'), $agenda)->assertSessionHasNoErrors();
        foreach ([Announcement::query()->firstOrFail(), CommunityEvent::query()->firstOrFail()] as $document) {
            $this->assertSame('Ketua RW Sintetis', $document->signatory_data['name']);
            $this->assertSame('Ketua RW 01', $document->signatory_data['title']);
            $this->assertSame('rw', $document->signatory_data['area_type']);
        }
    }

    public function test_letter_completion_waits_for_rt_chairman_but_complaints_remain_independent(): void
    {
        [, $rt, $actor] = $this->area();
        $letter = $this->serviceRequest($rt);
        $complaint = $this->serviceRequest($rt, 'complaint');
        $this->actingAs($actor)->patch(route('requests.update', $letter), ['status' => 'in_review'])->assertSessionHasNoErrors();
        $this->patch(route('requests.update', $letter), ['status' => 'completed'])->assertSessionHasErrors('signatory');
        $this->assertSame('in_review', $letter->fresh()->status);
        $this->patch(route('requests.update', $complaint), ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $complaint->fresh()->status);
        $rt->forceFill(['chairman_name' => 'Ketua Surat Uji'])->save();
        $this->patch(route('requests.update', $letter), ['status' => 'completed', 'signatory_data' => ['name' => 'Suntikan']])->assertSessionHasNoErrors();
        $this->assertSame('completed', $letter->fresh()->status);
        $this->assertSame('Ketua Surat Uji', $letter->fresh()->signatory_data['name']);
        $this->assertSame('Ketua RT 05', $letter->fresh()->signatory_data['title']);
        $this->assertStringNotContainsString('Ketua Surat Uji', $letter->fresh()->getRawOriginal('signatory_data'));
        $this->assertNull($complaint->fresh()->signatory_data);
    }

    public function test_missing_signatory_schema_blocks_publication_but_allows_drafts(): void
    {
        [, $rt, $actor] = $this->area('Ketua Uji');
        Schema::table('announcements', fn ($table) => $table->renameColumn('signatory_data', 'signatory_data_unavailable'));
        try {
            $this->actingAs($actor)->post(route('announcements.store'), $this->announcementData($rt))->assertSessionHasNoErrors();
            $this->post(route('announcements.store'), $this->announcementData($rt, ['is_published' => 1]))->assertSessionHasErrors('signatory');
            $this->assertDatabaseCount('announcements', 1);
        } finally {
            Schema::table('announcements', fn ($table) => $table->renameColumn('signatory_data_unavailable', 'signatory_data'));
        }
    }
}
