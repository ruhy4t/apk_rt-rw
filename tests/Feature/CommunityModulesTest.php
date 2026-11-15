<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\ServiceRequest;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rt_operator_cannot_view_residents_outside_assigned_rt(): void
    {
        [$rtOne, $householdOne] = $this->createHousehold('RT-01', 'KEL-01');
        [, $householdTwo] = $this->createHousehold('RT-02', 'KEL-02');
        Resident::query()->create(['household_id' => $householdOne->id, 'full_name' => 'Warga Satu']);
        Resident::query()->create(['household_id' => $householdTwo->id, 'full_name' => 'Warga Dua']);
        $operator = User::factory()->create([
            'role' => 'rt_operator',
            'rt_area_id' => $rtOne->id,
        ]);

        $this->actingAs($operator)
            ->get(route('residents.index'))
            ->assertOk()
            ->assertSee('KEL-01')
            ->assertSee('Warga Satu')
            ->assertDontSee('KEL-02')
            ->assertDontSee('Warga Dua');
    }

    public function test_manager_cannot_record_finances_for_an_unassigned_rt(): void
    {
        [$rtOne] = $this->createHousehold('RT-01', 'KEL-01');
        [$rtTwo] = $this->createHousehold('RT-02', 'KEL-02');
        $operator = User::factory()->create([
            'role' => 'rt_operator',
            'rt_area_id' => $rtOne->id,
        ]);

        $this->actingAs($operator)
            ->post(route('finance.entries.store'), [
                'rt_area_id' => $rtTwo->id,
                'direction' => 'income',
                'category' => 'Iuran',
                'amount' => 10000,
                'occurred_on' => '2026-06-01',
            ])
            ->assertForbidden();
    }

    public function test_resident_cannot_open_resident_management(): void
    {
        $user = User::factory()->create(['role' => 'resident']);

        $this->actingAs($user)->get(route('residents.index'))->assertForbidden();
    }

    public function test_disabled_b2_upload_route_does_not_accept_private_files(): void
    {
        $user = User::factory()->create(['role' => 'resident']);
        [, $household] = $this->createHousehold('RT-01', 'KEL-01');
        Resident::query()->create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'full_name' => 'Warga Contoh',
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'household_id' => $household->id,
            'requester_id' => $user->id,
            'category' => 'letter',
            'subject' => 'Surat keterangan',
            'description' => 'Permohonan contoh',
            'status' => 'submitted',
        ]);

        $this->actingAs($user)
            ->post(route('requests.files.store', $serviceRequest), [])
            ->assertStatus(503);
    }

    public function test_enabled_private_upload_stores_file_and_metadata_without_public_url(): void
    {
        Storage::fake('b2');
        config(['filesystems.private_files_enabled' => true]);
        $user = User::factory()->create(['role' => 'resident']);
        [, $household] = $this->createHousehold('RT-01', 'KEL-01');
        Resident::query()->create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'full_name' => 'Warga Contoh',
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'household_id' => $household->id,
            'requester_id' => $user->id,
            'category' => 'letter',
            'subject' => 'Surat keterangan',
            'description' => 'Permohonan contoh',
            'status' => 'submitted',
        ]);

        $this->actingAs($user)
            ->post(route('requests.files.store', $serviceRequest), [
                'file' => UploadedFile::fake()->create('lampiran.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $storedFile = StoredFile::query()->firstOrFail();
        $this->assertSame('b2', $storedFile->disk);
        $this->assertStringStartsWith('service-requests/'.$serviceRequest->id.'/', $storedFile->object_key);
        Storage::disk('b2')->assertExists($storedFile->object_key);
    }

    public function test_resident_cannot_upload_to_another_residents_request(): void
    {
        Storage::fake('b2');
        config(['filesystems.private_files_enabled' => true]);
        $owner = User::factory()->create(['role' => 'resident']);
        $otherUser = User::factory()->create(['role' => 'resident']);
        [, $household] = $this->createHousehold('RT-01', 'KEL-01');
        Resident::query()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'full_name' => 'Pemilik Permintaan',
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'household_id' => $household->id,
            'requester_id' => $owner->id,
            'category' => 'letter',
            'subject' => 'Surat keterangan',
            'description' => 'Permohonan contoh',
            'status' => 'submitted',
        ]);

        $this->actingAs($otherUser)
            ->post(route('requests.files.store', $serviceRequest), [
                'file' => UploadedFile::fake()->create('lampiran.pdf', 100, 'application/pdf'),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('stored_files', 0);
    }

    public function test_only_authorized_request_participant_can_download_a_private_file(): void
    {
        Storage::fake('b2');
        config(['filesystems.private_files_enabled' => true]);
        $owner = User::factory()->create(['role' => 'resident']);
        $otherUser = User::factory()->create(['role' => 'resident']);
        [, $household] = $this->createHousehold('RT-01', 'KEL-01');
        Resident::query()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'full_name' => 'Pemilik Permintaan',
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'household_id' => $household->id,
            'requester_id' => $owner->id,
            'category' => 'letter',
            'subject' => 'Surat keterangan',
            'description' => 'Permohonan contoh',
            'status' => 'submitted',
        ]);
        Storage::disk('b2')->put('service-requests/'.$serviceRequest->id.'/document.pdf', 'private contents');
        $storedFile = $serviceRequest->files()->create([
            'uploaded_by' => $owner->id,
            'disk' => 'b2',
            'object_key' => 'service-requests/'.$serviceRequest->id.'/document.pdf',
            'original_name' => 'dokumen.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 16,
        ]);

        $this->actingAs($otherUser)
            ->get(route('files.download', $storedFile))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('files.download', $storedFile))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=dokumen.pdf')
            ->assertHeader('cache-control', 'no-store, private');
    }

    public function test_management_pages_render_for_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin);

        foreach ([
            'admin.setup',
            'residents.index',
            'announcements.index',
            'agenda.index',
            'finance.index',
            'assets.index',
            'requests.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_administrator_can_create_a_scoped_operator_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        [$rt] = $this->createHousehold('RT-01', 'KEL-01');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Petugas RT',
                'username' => 'petugas_rt',
                'role' => 'rt_operator',
                'rt_area_id' => $rt->id,
                'password' => 'kata-sandi-kuat-123',
                'password_confirmation' => 'kata-sandi-kuat-123',
            ])
            ->assertRedirect();

        $operator = User::query()->where('username', 'petugas_rt')->firstOrFail();
        $this->assertSame('rt_operator', $operator->role);
        $this->assertSame($rt->id, $operator->rt_area_id);
        $this->assertTrue(Hash::check('kata-sandi-kuat-123', $operator->password));
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $admin->id,
            'action' => 'user.created',
            'subject_type' => 'User',
            'subject_id' => $operator->id,
        ]);
    }

    private function createHousehold(string $rtCode, string $householdCode): array
    {
        $rw = RwArea::query()->create([
            'code' => 'RW-'.$rtCode,
            'name' => 'RW '.$rtCode,
        ]);
        $rt = RtArea::query()->create([
            'rw_area_id' => $rw->id,
            'code' => $rtCode,
            'name' => $rtCode,
        ]);
        $household = Household::query()->create([
            'rt_area_id' => $rt->id,
            'household_code' => $householdCode,
        ]);

        return [$rt, $household];
    }
}
