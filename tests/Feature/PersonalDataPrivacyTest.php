<?php

namespace Tests\Feature;

use App\Models\AssetLoan;
use App\Models\CommunityAsset;
use App\Models\Contribution;
use App\Models\FinancialEntry;
use App\Models\Household;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\PersonalDataMask;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PersonalDataPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_number_mask_exposes_only_two_leading_and_one_trailing_digits(): void
    {
        $this->assertSame('32*************4', PersonalDataMask::number('3201000000001234'));
        $this->assertSame('08*********8', PersonalDataMask::number('081234567898'));
        $this->assertSame('62********8', PersonalDataMask::number('+62 (812) 345-678'));
        $this->assertSame('***', PersonalDataMask::number('123'));
        $this->assertSame('*', PersonalDataMask::number('x'));
        $this->assertNull(PersonalDataMask::number(null));
    }

    public function test_all_profile_fields_are_encrypted_and_readable_through_authorized_models(): void
    {
        config(['personal_data.encryption_enabled' => true]);
        [$household, $resident] = $this->family();
        $raw = DB::table('residents')->where('id', $resident->id)->first();
        foreach (['full_name', 'relationship', 'phone', 'birthplace', 'birth_date', 'gender', 'marital_status', 'education', 'occupation', 'residency_status', 'national_id', 'religion'] as $field) {
            $this->assertNotSame((string) $resident->$field, (string) $raw->$field, $field);
        }
        $this->assertStringNotContainsString('081234567898', $raw->private_data);
        $this->assertSame('081234567898', $resident->fresh()->phone);
        $this->assertSame('1990-01-02', $resident->fresh()->birth_date->format('Y-m-d'));
        $this->assertSame('Alamat pribadi uji', $household->fresh()->address);
        $this->assertNull(DB::table('households')->where('id', $household->id)->value('address'));
        $this->assertArrayNotHasKey('private_data', $resident->toArray());
        $this->assertArrayNotHasKey('phone', $resident->toArray());
        $this->assertSame(1, $household->residents()->heads()->count());
    }

    public function test_html_and_serialization_do_not_expose_complete_identifiers_or_phone(): void
    {
        config(['personal_data.encryption_enabled' => true]);
        [$household, $resident] = $this->family();
        $operator = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($operator)->get(route('residents.index'))->assertOk()
            ->assertSee('08*********8')->assertSee('32*************4')
            ->assertDontSee('081234567898')->assertDontSee('3201000000001234');
        $this->get(route('residents.edit', $resident))->assertOk()
            ->assertSee('08*********8')->assertSee('32*************5')
            ->assertDontSee('081234567898')->assertDontSee('3201000000001235');
        $this->assertArrayNotHasKey('private_data', $operator->toArray());
        $this->assertArrayNotHasKey('username', $operator->toArray());
    }

    public function test_blank_phone_is_preserved_and_clearing_requires_explicit_action(): void
    {
        config(['personal_data.encryption_enabled' => true]);
        [$household, $resident] = $this->family();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->patch(route('residents.update', $resident), ['full_name' => 'Nama Uji', 'phone' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame('081234567898', $resident->fresh()->phone);
        $this->patch(route('residents.update', $resident), ['full_name' => 'Nama Uji', 'clear_phone' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertNull($resident->fresh()->phone);
    }

    public function test_encrypted_username_can_login_and_duplicate_username_is_rejected(): void
    {
        config(['personal_data.encryption_enabled' => true]);
        $user = User::factory()->create(['username' => 'akun_uji', 'password' => 'Password-Uji-123!', 'role' => 'super_admin']);
        $this->assertNotSame('akun_uji', DB::table('users')->where('id', $user->id)->value('username'));
        $this->post(route('login.store'), $this->loginPayload(['username' => 'akun_uji', 'password' => 'Password-Uji-123!']))
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('users.store'), [
            'username' => 'akun_uji', 'name' => 'Nama Lain', 'role' => 'rt_operator',
            'password' => 'Password-Uji-123!', 'password_confirmation' => 'Password-Uji-123!',
        ])->assertSessionHasErrors('username');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_backfill_is_opt_in_repeatable_and_keeps_legacy_login_working(): void
    {
        [$household, $resident] = $this->family();
        $user = User::factory()->create(['username' => 'akun_lama', 'role' => 'super_admin', 'password' => 'Password-Uji-123!']);
        $this->artisan('app:encrypt-personal-data')->assertSuccessful();
        $this->assertNull(DB::table('residents')->where('id', $resident->id)->value('private_data'));
        $this->artisan('app:encrypt-personal-data', ['--apply' => true])->assertFailed();
        config(['personal_data.encryption_enabled' => true]);
        $this->assertSame('Nama Uji', $resident->fresh()->full_name);
        $this->artisan('app:encrypt-personal-data', ['--apply' => true, '--backup-confirmed' => true])->assertSuccessful();
        $ciphertext = DB::table('residents')->where('id', $resident->id)->value('private_data');
        $this->assertSame('081234567898', $resident->fresh()->phone);
        $this->assertSame('Nama Uji', $resident->fresh()->full_name);
        $this->assertSame('akun_lama', $user->fresh()->username);
        $this->artisan('app:encrypt-personal-data', ['--apply' => true, '--backup-confirmed' => true])->assertSuccessful();
        $this->assertSame($ciphertext, DB::table('residents')->where('id', $resident->id)->value('private_data'));
        $this->post(route('login.store'), $this->loginPayload(['username' => 'akun_lama', 'password' => 'Password-Uji-123!']))->assertRedirect(route('dashboard'));
    }

    public function test_private_service_text_is_encrypted_and_corruption_does_not_fall_back_to_plaintext(): void
    {
        config(['personal_data.encryption_enabled' => true]);
        [$household, $resident] = $this->family();
        $user = User::factory()->create();
        $request = ServiceRequest::query()->create(['household_id' => $household->id, 'requester_id' => $user->id, 'category' => 'complaint', 'subject' => 'Keperluan privat uji', 'description' => 'Keterangan pribadi uji']);
        $this->assertSame('Keterangan pribadi uji', $request->fresh()->description);
        $this->assertSame('[protected]', DB::table('service_requests')->where('id', $request->id)->value('description'));
        DB::table('residents')->where('id', $resident->id)->update(['private_data' => 'invalid-ciphertext']);
        $this->expectException(DecryptException::class);
        $resident->fresh()->phone;
    }

    public function test_first_update_of_legacy_profile_encrypts_unchanged_fields_too(): void
    {
        [$household, $resident] = $this->family();
        config(['personal_data.encryption_enabled' => true]);
        $resident->update(['occupation' => 'Pekerjaan Baru']);
        $this->assertSame('Nama Uji', $resident->fresh()->full_name);
        $this->assertSame('081234567898', $resident->fresh()->phone);
        $this->assertSame('1990-01-02', $resident->fresh()->birth_date->format('Y-m-d'));
        $this->assertSame('Pekerjaan Baru', $resident->fresh()->occupation);
        $this->assertNull(DB::table('residents')->where('id', $resident->id)->value('phone'));
        $this->assertNull(DB::table('residents')->where('id', $resident->id)->value('birth_date'));
    }

    public function test_head_replacement_still_works_with_encrypted_relationships(): void
    {
        config(['personal_data.encryption_enabled' => true]);
        [$household, $head] = $this->family();
        $member = $household->residents()->create(['full_name' => 'Anggota Uji', 'relationship' => 'Anak']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->patch(route('households.head', $household), [
                'new_head_id' => $member->id, 'current_head_id' => $head->id, 'previous_relationship' => 'Kepala Keluarga Sebelumnya',
            ])->assertSessionHasNoErrors();
        $this->assertSame('Kepala Keluarga', $member->fresh()->relationship);
        $this->assertSame('Kepala Keluarga Sebelumnya', $head->fresh()->relationship);
        $this->assertSame(1, $household->residents()->heads()->count());
        $this->get(route('residents.index'))->assertOk()->assertSee('Anggota Uji');
    }

    public function test_private_notes_and_attachment_names_are_encrypted_at_rest(): void
    {
        config(['personal_data.encryption_enabled' => true]);
        [$household] = $this->family();
        $user = User::factory()->create();
        $asset = CommunityAsset::query()->create(['rt_area_id' => $household->rt_area_id, 'created_by' => $user->id, 'name' => 'Barang Uji', 'quantity' => 1]);
        $loan = AssetLoan::query()->create(['asset_id' => $asset->id, 'household_id' => $household->id, 'requested_by' => $user->id, 'quantity' => 1, 'purpose' => 'Keperluan pribadi uji']);
        $contribution = Contribution::query()->create(['household_id' => $household->id, 'recorded_by' => $user->id, 'amount' => 1000, 'period' => '2026-11', 'note' => 'Catatan pribadi uji']);
        $entry = FinancialEntry::query()->create(['rt_area_id' => $household->rt_area_id, 'recorded_by' => $user->id, 'amount' => 1000, 'direction' => 'income', 'category' => 'Uji', 'occurred_on' => '2026-11-15', 'description' => 'Keterangan transaksi privat']);
        $request = ServiceRequest::query()->create(['household_id' => $household->id, 'requester_id' => $user->id, 'category' => 'letter', 'subject' => 'Surat Uji', 'description' => 'Uji privat']);
        $file = $request->files()->create(['uploaded_by' => $user->id, 'disk' => 'b2', 'object_key' => 'synthetic/test.pdf', 'original_name' => 'Dokumen Pribadi Uji.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 100]);
        foreach ([[$loan, 'purpose'], [$contribution, 'note'], [$entry, 'description'], [$file, 'original_name']] as [$model, $field]) {
            $plain = $model->$field;
            $this->assertNotSame($plain, DB::table($model->getTable())->where('id', $model->id)->value($field));
            $this->assertSame($plain, $model->fresh()->$field);
            $this->assertArrayNotHasKey($field, $model->toArray());
        }
    }

    public function test_encrypted_payload_round_trips_long_multibyte_private_text(): void
    {
        config(['personal_data.encryption_enabled' => true]);
        [$household] = $this->family();
        $user = User::factory()->create();
        $text = str_repeat(mb_chr(0x1F642), 5000);
        $request = ServiceRequest::query()->create(['household_id' => $household->id, 'requester_id' => $user->id, 'category' => 'complaint', 'subject' => 'Teks panjang uji', 'description' => $text, 'response' => $text]);
        $this->assertSame($text, $request->fresh()->description);
        $this->assertSame($text, $request->fresh()->response);
    }

    private function family(): array
    {
        $rw = RwArea::query()->create(['code' => 'RW-UJI', 'name' => 'Wilayah uji']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => 'RT-UJI', 'name' => 'Lingkungan uji']);
        $household = Household::query()->create(['rt_area_id' => $rt->id, 'household_code' => 'KEL-UJI', 'address' => 'Alamat pribadi uji', 'family_card_number' => '3201000000001234']);
        $resident = $household->residents()->create([
            'full_name' => 'Nama Uji', 'relationship' => 'Kepala Keluarga', 'phone' => '081234567898',
            'national_id' => '3201000000001235', 'birthplace' => 'Kota Uji', 'birth_date' => '1990-01-02',
            'gender' => 'Perempuan', 'religion' => 'Keyakinan Uji', 'marital_status' => 'Kawin',
            'education' => 'Sarjana', 'occupation' => 'Karyawan', 'residency_status' => 'Tetap',
        ]);

        return [$household, $resident];
    }
}
