<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AreaCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function areas(): array
    {
        $main = RwArea::query()->create(['code' => '05', 'name' => 'RW Uji']);
        $wrong = RwArea::query()->create(['code' => 'RT.02', 'name' => 'RW Salah Uji', 'chairman_name' => 'Ketua RT Sintetis']);
        $rt = RtArea::query()->create(['rw_area_id' => $main->id, 'code' => '05', 'name' => 'RT Uji']);

        return [$main, $wrong, $rt];
    }

    private function input(RwArea $target, array $changes = []): array
    {
        return array_replace(['target_rw_area_id' => $target->id, 'code' => '02', 'name' => 'RT 02', 'confirm_conversion' => 1], $changes);
    }

    public function test_explicit_correction_creates_rt_preserves_chairman_and_keeps_main_data(): void
    {
        [$main, $wrong, $existingRt] = $this->areas();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.rw.convert-to-rt', $wrong), $this->input($main))
            ->assertRedirect(route('admin.setup'))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('rw_areas', ['id' => $wrong->id]);
        $this->assertDatabaseHas('rw_areas', ['id' => $main->id]);
        $converted = RtArea::query()->where('rw_area_id', $main->id)->where('code', '02')->firstOrFail();
        $this->assertSame('Ketua RT Sintetis', $converted->chairman_name);
        $this->assertNotSame('Ketua RT Sintetis', $converted->getRawOriginal('chairman_name'));
        $this->assertSame($main->id, $existingRt->fresh()->rw_area_id);
        $this->assertDatabaseHas('audit_events', ['action' => 'rw_area.corrected_to_rt', 'subject_id' => $wrong->id, 'actor_id' => $admin->id]);
        $this->assertDatabaseHas('audit_events', ['action' => 'rt_area.created_from_rw', 'subject_id' => $converted->id]);
        $this->post(route('admin.rt.store'), ['rw_area_id' => $main->id, 'code' => '03', 'name' => 'RT Berikutnya'])->assertSessionHasNoErrors();
    }

    public function test_correction_requires_confirmation_and_distinct_existing_target(): void
    {
        [$main, $wrong] = $this->areas();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->post(route('admin.rw.convert-to-rt', $wrong), $this->input($main, ['confirm_conversion' => 0]))->assertSessionHasErrors('confirm_conversion');
        $this->post(route('admin.rw.convert-to-rt', $wrong), $this->input($wrong))->assertSessionHasErrors('conversion');
        $this->post(route('admin.rw.convert-to-rt', $wrong), $this->input($main, ['target_rw_area_id' => 999999]))->assertSessionHasErrors('target_rw_area_id');
        $this->assertDatabaseCount('rw_areas', 2);
        $this->assertDatabaseCount('rt_areas', 1);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_guests_and_other_roles_cannot_correct_rw_records(): void
    {
        [$main, $wrong, $rt] = $this->areas();
        $this->post(route('admin.rw.convert-to-rt', $wrong), $this->input($main))->assertRedirect(route('login'));
        foreach ([['role' => 'resident'], ['role' => 'rt_operator', 'rt_area_id' => $rt->id], ['role' => 'rw_operator', 'rw_area_id' => $main->id]] as $attributes) {
            $this->actingAs(User::factory()->create($attributes))->post(route('admin.rw.convert-to-rt', $wrong), $this->input($main))->assertForbidden();
        }
        $this->assertDatabaseCount('rw_areas', 2);
        $this->assertDatabaseCount('rt_areas', 1);
    }

    public function test_linked_rt_accounts_identity_or_duplicate_rt_code_block_correction(): void
    {
        [$main, $wrong] = $this->areas();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->post(route('admin.rw.convert-to-rt', $main), $this->input($wrong))->assertSessionHasErrors('conversion');
        $this->post(route('admin.rw.convert-to-rt', $wrong), $this->input($main, ['code' => '05']))->assertSessionHasErrors('code');
        $wrong->update(['village_name' => 'Kelurahan Uji']);
        $this->post(route('admin.rw.convert-to-rt', $wrong), $this->input($main))->assertSessionHasErrors('conversion');
        $wrong->update(['village_name' => null]);
        User::factory()->create(['role' => 'rw_operator', 'rw_area_id' => $wrong->id]);
        $this->post(route('admin.rw.convert-to-rt', $wrong), $this->input($main))->assertSessionHasErrors('conversion');
        $this->assertDatabaseCount('rw_areas', 2);
        $this->assertDatabaseCount('rt_areas', 1);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_a_historical_signatory_reference_blocks_correction_without_rewriting_documents(): void
    {
        [$main, $wrong] = $this->areas();
        $admin = User::factory()->create(['role' => 'super_admin']);
        foreach ([Announcement::class, CommunityEvent::class] as $modelClass) {
            $data = ['created_by' => $admin->id, 'title' => 'Catatan Uji', 'audience' => 'public', 'is_published' => false, 'signatory_data' => ['name' => 'Ketua Sebelumnya', 'title' => 'Ketua RW Uji', 'area_type' => 'rw', 'area_id' => $wrong->id]];
            $data += $modelClass === Announcement::class ? ['body' => 'Isi Uji'] : ['starts_at' => now()->addDay()];
            $document = $modelClass::query()->create($data);
            $ciphertext = $document->getRawOriginal('signatory_data');
            $this->actingAs($admin)->post(route('admin.rw.convert-to-rt', $wrong), $this->input($main))->assertSessionHasErrors('conversion');
            $this->assertSame($ciphertext, $document->fresh()->getRawOriginal('signatory_data'));
            $document->delete();
        }
        $this->assertDatabaseCount('rw_areas', 2);
        $this->assertDatabaseCount('rt_areas', 1);
    }

    public function test_write_failure_rolls_back_new_rt_source_and_audit_changes(): void
    {
        [$main, $wrong] = $this->areas();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        RtArea::created(fn () => throw new RuntimeException('Synthetic conversion failure'));
        try {
            $this->withoutExceptionHandling()->post(route('admin.rw.convert-to-rt', $wrong), $this->input($main));
            $this->fail('Expected synthetic failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic conversion failure', $exception->getMessage());
        } finally {
            RtArea::flushEventListeners();
        }
        $this->assertDatabaseCount('rw_areas', 2);
        $this->assertDatabaseCount('rt_areas', 1);
        $this->assertDatabaseCount('audit_events', 0);
    }
}
