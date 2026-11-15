<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResidentIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_family_stores_identity_encrypted_and_links_head(): void
    {
        $rt = $this->rt('RW1', 'RT1');
        $operator = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id]);
        $values = $this->person() + [
            'rt_area_id' => $rt->id,
            'household_code' => 'KEL-1',
            'family_card_number' => '0000000000000001',
        ];
        $this->actingAs($operator)->post(route('households.store'), $values)
            ->assertRedirect()->assertSessionHasNoErrors();

        $household = Household::query()->firstOrFail();
        $resident = Resident::query()->firstOrFail();
        $this->assertSame('0000000000000001', $household->family_card_number);
        $this->assertSame('0000000000000002', $resident->national_id);
        $this->assertSame('Kepala Keluarga', $resident->relationship);
        $this->assertNotSame('0000000000000001', DB::table('households')->value('family_card_number'));
        $this->assertNotSame('0000000000000002', DB::table('residents')->value('national_id'));
        $this->assertNotSame('Keyakinan Uji', DB::table('residents')->value('religion'));
        $this->get(route('home'))->assertDontSee('0000000000000001')->assertDontSee('0000000000000002');
    }

    public function test_invalid_or_duplicate_identity_does_not_create_partial_records_or_flash_identifiers(): void
    {
        $rt = $this->rt('RW1', 'RT1');
        $operator = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id]);
        $values = $this->person() + ['rt_area_id' => $rt->id, 'household_code' => 'KEL-1', 'family_card_number' => '0000000000000001'];
        $this->actingAs($operator)->post(route('households.store'), array_merge($values, ['national_id' => '123']))
            ->assertSessionHasErrors('national_id')
            ->assertSessionMissing('_old_input.national_id')
            ->assertSessionMissing('_old_input.family_card_number')
            ->assertSessionMissing('_old_input.religion');
        $this->assertDatabaseCount('households', 0);

        $this->post(route('households.store'), $values)->assertSessionHasNoErrors();
        $this->post(route('households.store'), array_merge($values, ['household_code' => 'KEL-2']))
            ->assertSessionHasErrors('national_id');
        $this->assertDatabaseCount('households', 1);
        $this->assertDatabaseCount('residents', 1);
    }

    public function test_rt_cannot_create_or_open_member_form_outside_own_area(): void
    {
        $own = $this->rt('RW1', 'RT1');
        $other = $this->rt('RW2', 'RT2');
        $household = Household::query()->create(['rt_area_id' => $other->id, 'household_code' => 'OTHER']);
        $operator = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $own->id]);
        $this->actingAs($operator)->get(route('residents.people.create', $household))->assertForbidden();
        $this->post(route('residents.people.store', $household), $this->person() + ['relationship' => 'Anak'])->assertForbidden();
        $this->post(route('households.store'), $this->person() + [
            'rt_area_id' => $other->id, 'household_code' => 'KEL-2', 'family_card_number' => '0000000000000003',
        ])->assertForbidden();
        $this->assertDatabaseCount('residents', 0);
    }

    public function test_edit_keeps_existing_nik_when_left_blank_and_disallows_cross_area_access(): void
    {
        $own = $this->rt('RW1', 'RT1');
        $other = $this->rt('RW2', 'RT2');
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('households.store'), $this->person() + [
            'rt_area_id' => $other->id, 'household_code' => 'OTHER', 'family_card_number' => '0000000000000001',
        ])->assertSessionHasNoErrors();
        $resident = Resident::query()->firstOrFail();
        $this->get(route('residents.edit', $resident))
            ->assertOk()->assertHeader('cache-control', 'no-store, private')
            ->assertDontSee('0000000000000002');
        $this->patch(route('residents.details', $resident), array_merge($this->person(), [
            'full_name' => 'Nama Baru', 'national_id' => '',
        ]))->assertRedirect(route('residents.index'))->assertSessionHasNoErrors();
        $this->assertSame('0000000000000002', $resident->fresh()->national_id);
        $this->assertSame('Nama Baru', $resident->fresh()->full_name);

        $operator = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $own->id]);
        $this->flushSession()->actingAs($operator);
        $this->get(route('residents.edit', $resident))->assertForbidden();
        $this->patch(route('residents.details', $resident), $this->person())->assertForbidden();
    }

    public function test_resident_can_view_only_their_own_household(): void
    {
        $rt = $this->rt('RW1', 'RT1');
        $own = Household::query()->create(['rt_area_id' => $rt->id, 'household_code' => 'OWN']);
        $other = Household::query()->create(['rt_area_id' => $rt->id, 'household_code' => 'OTHER']);
        $user = User::factory()->create(['role' => 'resident']);
        Resident::query()->create(['household_id' => $own->id, 'user_id' => $user->id, 'full_name' => 'Pemilik Uji']);
        Resident::query()->create(['household_id' => $own->id, 'full_name' => 'Keluarga Uji']);
        Resident::query()->create(['household_id' => $other->id, 'full_name' => 'Warga Lain']);

        $this->actingAs($user)->get(route('households.mine'))->assertOk()
            ->assertHeader('cache-control', 'no-store, private')
            ->assertSee('Keluarga Uji')->assertDontSee('Warga Lain');
    }

    public function test_legacy_update_cannot_change_head_of_household(): void
    {
        $rt = $this->rt('RW1', 'RT1');
        $household = Household::query()->create(['rt_area_id' => $rt->id, 'household_code' => 'OWN']);
        $head = $household->residents()->create(['full_name' => 'Kepala Uji', 'relationship' => 'Kepala Keluarga']);
        $member = $household->residents()->create(['full_name' => 'Anggota Uji', 'relationship' => 'Anak']);
        $operator = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id]);

        $this->actingAs($operator)->patch(route('residents.update', $head), [
            'full_name' => 'Kepala Uji', 'relationship' => 'Anak',
        ])->assertSessionHasErrors('relationship');
        $this->patch(route('residents.update', $member), [
            'full_name' => 'Anggota Uji', 'relationship' => 'Kepala Keluarga',
        ])->assertSessionHasErrors('relationship');
        $this->assertSame('Kepala Keluarga', $head->fresh()->relationship);
        $this->assertSame('Anak', $member->fresh()->relationship);
    }

    private function rt(string $rwCode, string $rtCode): RtArea
    {
        $rw = RwArea::query()->create(['code' => $rwCode, 'name' => $rwCode]);

        return RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => $rtCode, 'name' => $rtCode]);
    }

    private function person(): array
    {
        return [
            'full_name' => 'Warga Uji',
            'national_id' => '0000000000000002',
            'birthplace' => 'Kota Uji',
            'birth_date' => '2000-01-01',
            'gender' => 'Perempuan',
            'religion' => 'Keyakinan Uji',
            'marital_status' => 'Belum Kawin',
            'education' => 'SMA',
            'occupation' => 'Pelajar',
            'residency_status' => 'Tetap',
        ];
    }
}
