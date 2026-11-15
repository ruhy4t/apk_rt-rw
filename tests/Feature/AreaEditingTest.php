<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Household;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AreaEditingTest extends TestCase
{
    use RefreshDatabase;

    private function areas(): array
    {
        $rw = RwArea::query()->create(['code' => '01', 'name' => 'RW Uji', 'chairman_name' => 'Ketua RW Uji']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => '05', 'name' => 'RT Uji', 'chairman_name' => 'Ketua RT Uji']);

        return [$rw, $rt];
    }

    public function test_super_admin_can_correct_names_and_codes_without_moving_data_or_rewriting_signatories(): void
    {
        [$rw, $rt] = $this->areas();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $operator = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id]);
        $household = Household::query()->create(['rt_area_id' => $rt->id, 'household_code' => 'KEL-UJI']);
        $document = Announcement::query()->create([
            'rt_area_id' => $rt->id, 'created_by' => $admin->id, 'title' => 'Dokumen Uji', 'body' => 'Isi Uji',
            'audience' => 'residents', 'is_published' => true,
            'signatory_data' => ['name' => 'Ketua Lama Uji', 'title' => 'Ketua RT 05'],
        ]);
        $snapshot = $document->getRawOriginal('signatory_data');
        $this->actingAs($admin)->get(route('admin.setup'))->assertOk()->assertSee('Ubah wilayah RT')->assertSee('Ubah wilayah RW');
        $this->patch(route('admin.rt.update', $rt), [
            'code' => '06', 'name' => 'RT Nama Benar', 'rw_area_id' => 99999, 'chairman_name' => 'Suntikan',
        ])->assertSessionHasNoErrors();
        $this->patch(route('admin.rw.update', $rw), ['code' => '02', 'name' => 'RW Nama Benar'])->assertSessionHasNoErrors();
        $this->assertSame('06', $rt->fresh()->code);
        $this->assertSame('RT Nama Benar', $rt->fresh()->name);
        $this->assertSame('Ketua RT Uji', $rt->fresh()->chairman_name);
        $this->assertSame('RW Nama Benar', $rw->fresh()->name);
        $this->assertSame($rw->id, $rt->fresh()->rw_area_id);
        $this->assertSame($rt->id, $household->fresh()->rt_area_id);
        $this->assertSame($rt->id, $operator->fresh()->rt_area_id);
        $this->assertSame($rt->id, $document->fresh()->rt_area_id);
        $this->assertSame($snapshot, $document->fresh()->getRawOriginal('signatory_data'));
        $this->assertDatabaseHas('audit_events', ['action' => 'rt_area.updated', 'actor_id' => $admin->id]);
        $this->assertDatabaseHas('audit_events', ['action' => 'rw_area.updated', 'actor_id' => $admin->id]);
    }

    public function test_other_roles_and_guests_cannot_edit_territories(): void
    {
        [$rw, $rt] = $this->areas();
        $data = ['code' => '99', 'name' => 'Dilarang'];
        $this->patch(route('admin.rt.update', $rt), $data)->assertRedirect(route('login'));
        foreach ([['role' => 'resident'], ['role' => 'rt_operator', 'rt_area_id' => $rt->id], ['role' => 'rw_operator', 'rw_area_id' => $rw->id]] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->patch(route('admin.rt.update', $rt), $data)->assertForbidden();
            $this->patch(route('admin.rw.update', $rw), $data)->assertForbidden();
        }
        $this->assertSame('05', $rt->fresh()->code);
        $this->assertSame('01', $rw->fresh()->code);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_duplicate_codes_and_invalid_names_are_rejected_but_current_codes_can_be_kept(): void
    {
        [$rw, $rt] = $this->areas();
        RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => '06', 'name' => 'RT Lain']);
        RwArea::query()->create(['code' => '02', 'name' => 'RW Data Lama']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->patch(route('admin.rt.update', $rt), ['code' => '06', 'name' => 'Nama Baru'])->assertSessionHasErrors('code');
        $this->patch(route('admin.rw.update', $rw), ['code' => '02', 'name' => 'Nama Baru'])->assertSessionHasErrors('code');
        $this->patch(route('admin.rt.update', $rt), ['code' => str_repeat('x', 33), 'name' => '   '])->assertSessionHasErrors(['code', 'name']);
        $this->patch(route('admin.rw.update', $rw), ['code' => '01', 'name' => str_repeat('x', 121)])->assertSessionHasErrors('name');
        $this->assertSame('RT Uji', $rt->fresh()->name);
        $this->patch(route('admin.rt.update', $rt), ['code' => '05', 'name' => 'Nama Diperbaiki'])->assertSessionHasNoErrors();
        $this->assertSame('Nama Diperbaiki', $rt->fresh()->name);
    }

    public function test_area_corrections_work_before_optional_chairman_columns_are_installed(): void
    {
        [$rw, $rt] = $this->areas();
        Schema::table('rt_areas', fn ($table) => $table->renameColumn('chairman_name', 'chairman_name_unavailable'));
        Schema::table('rw_areas', fn ($table) => $table->renameColumn('chairman_name', 'chairman_name_unavailable'));
        try {
            $this->actingAs(User::factory()->create(['role' => 'super_admin']));
            $this->get(route('admin.setup'))->assertOk()->assertSee('Ubah wilayah RT');
            $this->patch(route('admin.rt.update', $rt), ['code' => '05', 'name' => 'RT Diperbaiki'])->assertSessionHasNoErrors();
            $this->patch(route('admin.rw.update', $rw), ['code' => '01', 'name' => 'RW Diperbaiki'])->assertSessionHasNoErrors();
            $this->assertSame('RT Diperbaiki', $rt->fresh()->name);
            $this->assertSame('RW Diperbaiki', $rw->fresh()->name);
        } finally {
            Schema::table('rt_areas', fn ($table) => $table->renameColumn('chairman_name_unavailable', 'chairman_name'));
            Schema::table('rw_areas', fn ($table) => $table->renameColumn('chairman_name_unavailable', 'chairman_name'));
        }
    }
}
