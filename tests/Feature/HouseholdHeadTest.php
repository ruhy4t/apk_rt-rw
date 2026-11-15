<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class HouseholdHeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_replace_head_without_deleting_members_or_changing_accounts(): void
    {
        [$household, $head, $member, $operator] = $this->family();

        $this->actingAs($operator)->patch(route('households.head', $household), [
            'new_head_id' => $member->id,
            'current_head_id' => $head->id,
            'previous_relationship' => 'Kepala Keluarga Sebelumnya',
        ])->assertRedirect(route('residents.index'))->assertSessionHasNoErrors();

        $this->assertSame('Kepala Keluarga', $member->fresh()->relationship);
        $this->assertSame('Kepala Keluarga Sebelumnya', $head->fresh()->relationship);
        $this->assertSame(1, $household->residents()->where('relationship', 'Kepala Keluarga')->count());
        $this->assertDatabaseCount('residents', 2);
        $this->assertSame('rt_operator', $operator->fresh()->role);
        $this->assertDatabaseHas('audit_events', ['action' => 'household.head_updated', 'subject_id' => $household->id]);
        $this->get(route('residents.index'))->assertSee('Ganti kepala keluarga')->assertSee('Anggota Uji');
    }

    public function test_head_must_belong_to_same_household_and_requests_are_scoped_to_operator_area(): void
    {
        [$household, $head, $member, $operator] = $this->family();
        $other = Household::query()->create(['rt_area_id' => $household->rt_area_id, 'household_code' => 'OTHER']);
        $outsider = $other->residents()->create(['full_name' => 'Anggota Lain', 'relationship' => 'Anak']);

        $this->actingAs($operator)->patch(route('households.head', $household), [
            'new_head_id' => $outsider->id, 'current_head_id' => $head->id, 'previous_relationship' => 'Famili Lain',
        ])->assertSessionHasErrors('new_head_id');

        $rw = RwArea::query()->create(['code' => 'OTHER-RW', 'name' => 'Wilayah lain']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => 'OTHER-RT', 'name' => 'Lingkungan lain']);
        $outsideOperator = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id]);
        $this->actingAs($outsideOperator)->patch(route('households.head', $household), [
            'new_head_id' => $member->id, 'current_head_id' => $head->id, 'previous_relationship' => 'Famili Lain',
        ])->assertForbidden();
        $this->assertSame('Kepala Keluarga', $head->fresh()->relationship);
        $this->assertSame('Istri', $member->fresh()->relationship);
    }

    public function test_resident_and_guest_cannot_replace_head(): void
    {
        [$household, $head, $member] = $this->family();
        $values = ['new_head_id' => $member->id, 'current_head_id' => $head->id, 'previous_relationship' => 'Famili Lain'];
        $this->patch(route('households.head', $household), $values)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'resident']))
            ->patch(route('households.head', $household), $values)->assertForbidden();
        $this->assertSame('Kepala Keluarga', $head->fresh()->relationship);
    }

    public function test_stale_form_cannot_overwrite_a_recent_head_change(): void
    {
        [$household, $head, $member, $operator] = $this->family();
        $this->actingAs($operator)->patch(route('households.head', $household), [
            'new_head_id' => $member->id, 'current_head_id' => $head->id, 'previous_relationship' => 'Famili Lain',
        ])->assertSessionHasNoErrors();
        $this->patch(route('households.head', $household), [
            'new_head_id' => $head->id, 'current_head_id' => $head->id, 'previous_relationship' => 'Famili Lain',
        ])->assertSessionHasErrors('head');
        $this->assertSame('Kepala Keluarga', $member->fresh()->relationship);
        $this->assertSame('Famili Lain', $head->fresh()->relationship);
    }

    public function test_missing_previous_relationship_and_invalid_selection_leave_data_unchanged(): void
    {
        [$household, $head, $member, $operator] = $this->family();
        $this->actingAs($operator)->patch(route('households.head', $household), [
            'new_head_id' => $member->id, 'current_head_id' => $head->id,
        ])->assertSessionHasErrors('previous_relationship');
        $this->patch(route('households.head', $household), [
            'new_head_id' => $head->id, 'current_head_id' => $head->id, 'previous_relationship' => 'Famili Lain',
        ])->assertSessionHasErrors('new_head_id');
        $this->assertSame('Kepala Keluarga', $head->fresh()->relationship);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_existing_member_can_be_assigned_when_household_has_no_head(): void
    {
        [$household, $head, $member, $operator] = $this->family();
        $head->update(['relationship' => 'Famili Lain']);
        $this->actingAs($operator)->patch(route('households.head', $household), [
            'new_head_id' => $member->id, 'current_head_id' => '',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Kepala Keluarga', $member->fresh()->relationship);
    }

    public function test_inconsistent_existing_heads_are_rejected_without_modifying_members(): void
    {
        [$household, $head, $member, $operator] = $this->family();
        $member->update(['relationship' => 'Kepala Keluarga']);
        $this->actingAs($operator)->patch(route('households.head', $household), [
            'new_head_id' => $member->id, 'current_head_id' => $head->id, 'previous_relationship' => 'Famili Lain',
        ])->assertSessionHasErrors('head');
        $this->assertSame('Kepala Keluarga', $head->fresh()->relationship);
        $this->assertSame('Kepala Keluarga', $member->fresh()->relationship);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_adding_head_is_allowed_only_when_household_has_no_head(): void
    {
        [$household, $head, $member, $operator] = $this->family();
        $values = [
            'full_name' => 'Kepala Baru Uji', 'national_id' => '0000000000000008',
            'birthplace' => 'Kota Uji', 'birth_date' => '1990-01-01', 'gender' => 'Perempuan',
            'religion' => 'Keyakinan Uji', 'marital_status' => 'Kawin', 'education' => 'Sarjana',
            'occupation' => 'Karyawan', 'residency_status' => 'Tetap', 'relationship' => 'Kepala Keluarga',
        ];
        $this->actingAs($operator)->get(route('residents.people.create', $household))
            ->assertDontSee('<option value="Kepala Keluarga"', false);
        $this->post(route('residents.people.store', $household), $values)->assertSessionHasErrors('relationship');
        $this->assertDatabaseCount('residents', 2);

        $head->update(['relationship' => 'Famili Lain']);
        $this->flushSession()->get(route('residents.people.create', $household))
            ->assertSee('<option value="Kepala Keluarga"', false);
        $this->post(route('residents.people.store', $household), $values)->assertSessionHasNoErrors();
        $this->assertSame(1, $household->residents()->where('relationship', 'Kepala Keluarga')->count());
    }

    public function test_failure_during_promotion_rolls_back_previous_head_and_audit(): void
    {
        [$household, $head, $member, $operator] = $this->family();
        Resident::updating(function (Resident $resident) use ($member) {
            if ($resident->id === $member->id) {
                throw new RuntimeException('Synthetic write failure');
            }
        });
        try {
            $this->withoutExceptionHandling()->actingAs($operator)->patch(route('households.head', $household), [
                'new_head_id' => $member->id, 'current_head_id' => $head->id, 'previous_relationship' => 'Famili Lain',
            ]);
            $this->fail('Expected the simulated write failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic write failure', $exception->getMessage());
        } finally {
            Resident::flushEventListeners();
        }
        $this->assertSame('Kepala Keluarga', $head->fresh()->relationship);
        $this->assertSame('Istri', $member->fresh()->relationship);
        $this->assertDatabaseCount('audit_events', 0);
    }

    private function family(): array
    {
        $rw = RwArea::query()->create(['code' => 'RW-UJI', 'name' => 'Wilayah uji']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => 'RT-UJI', 'name' => 'Lingkungan uji']);
        $household = Household::query()->create(['rt_area_id' => $rt->id, 'household_code' => 'KEL-UJI']);
        $head = $household->residents()->create(['full_name' => 'Kepala Uji', 'relationship' => 'Kepala Keluarga']);
        $member = $household->residents()->create(['full_name' => 'Anggota Uji', 'relationship' => 'Istri']);
        $operator = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id]);

        return [$household, $head, $member, $operator];
    }
}
