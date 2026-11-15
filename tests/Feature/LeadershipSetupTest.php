<?php

namespace Tests\Feature;

use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeadershipSetupTest extends TestCase
{
    use RefreshDatabase;

    private function areas(): array
    {
        $rw = RwArea::query()->create(['code' => '01', 'name' => 'RW Uji']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => '05', 'name' => 'RT Uji']);

        return [$rw, $rt];
    }

    public function test_regions_can_be_created_without_chairmen_or_resident_accounts(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->post(route('admin.rw.store'), ['code' => '01', 'name' => 'RW Uji'])->assertSessionHasNoErrors();
        $rw = RwArea::query()->firstOrFail();
        $this->post(route('admin.rt.store'), ['rw_area_id' => $rw->id, 'code' => '05', 'name' => 'RT Uji'])->assertSessionHasNoErrors();
        $this->assertNull($rw->chairman_name);
        $this->assertNull(RtArea::query()->firstOrFail()->chairman_name);
        $this->get(route('admin.setup'))->assertOk()->assertSee('Belum ditetapkan')->assertSee('Tetapkan / ganti Ketua RT')->assertSee('Tetapkan / ganti Ketua RW');
        $this->assertDatabaseCount('residents', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_chairman_names_on_creation_are_always_encrypted_and_not_serialized(): void
    {
        config(['personal_data.encryption_enabled' => false]);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->post(route('admin.rw.store'), ['code' => '01', 'name' => 'RW Uji', 'chairman_name' => 'Ketua RW Sintetis'])->assertSessionHasNoErrors();
        $rw = RwArea::query()->firstOrFail();
        $this->post(route('admin.rt.store'), ['rw_area_id' => $rw->id, 'code' => '05', 'name' => 'RT Uji', 'chairman_name' => 'Ketua RT Sintetis'])->assertSessionHasNoErrors();
        $rt = RtArea::query()->firstOrFail();
        foreach ([[$rw, 'Ketua RW Sintetis'], [$rt, 'Ketua RT Sintetis']] as [$area, $expected]) {
            $this->assertSame($expected, $area->chairman_name);
            $this->assertNotSame($expected, DB::table($area->getTable())->where('id', $area->id)->value('chairman_name'));
            $this->assertArrayNotHasKey('chairman_name', $area->toArray());
        }
        $this->get(route('admin.setup'))->assertOk()->assertSee('Ketua RW Sintetis')->assertSee('Ketua RT Sintetis');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('residents', 0);
    }

    public function test_administrator_can_set_and_replace_both_chairmen_without_changing_accounts(): void
    {
        [$rw, $rt] = $this->areas();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        foreach ([['admin.rt.chairman', $rt, 'Ketua RT'], ['admin.rw.chairman', $rw, 'Ketua RW']] as [$route, $area, $title]) {
            $this->patch(route($route, $area), ['chairman_name' => $title.' Pertama'])->assertSessionHasNoErrors();
            $this->patch(route($route, $area), ['chairman_name' => $title.' Pengganti'])->assertSessionHasNoErrors();
            $this->assertSame($title.' Pengganti', $area->fresh()->chairman_name);
        }
        $this->assertDatabaseHas('audit_events', ['action' => 'rt_area.chairman_updated']);
        $this->assertDatabaseHas('audit_events', ['action' => 'rw_area.chairman_updated']);
        $audit = DB::table('audit_events')->get()->toJson();
        $this->assertStringNotContainsString('Pertama', $audit);
        $this->assertStringNotContainsString('Pengganti', $audit);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_other_roles_cannot_set_chairmen(): void
    {
        [$rw, $rt] = $this->areas();
        $this->patch(route('admin.rt.chairman', $rt), ['chairman_name' => 'Dilarang'])->assertRedirect(route('login'));
        foreach ([
            ['role' => 'resident'],
            ['role' => 'rt_operator', 'rt_area_id' => $rt->id],
            ['role' => 'rw_operator', 'rw_area_id' => $rw->id],
        ] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->patch(route('admin.rt.chairman', $rt), ['chairman_name' => 'Dilarang'])->assertForbidden();
            $this->patch(route('admin.rw.chairman', $rw), ['chairman_name' => 'Dilarang'])->assertForbidden();
        }
        $this->assertNull($rt->fresh()->chairman_name);
        $this->assertNull($rw->fresh()->chairman_name);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_blank_or_overlong_replacement_is_rejected_without_flashing_name(): void
    {
        [$rw, $rt] = $this->areas();
        $rt->update(['chairman_name' => 'Ketua RT Berlaku']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        foreach (['', '   ', str_repeat('x', 161)] as $name) {
            $this->patch(route('admin.rt.chairman', $rt), ['chairman_name' => $name])->assertSessionHasErrors('chairman_name');
            $this->assertFalse(session()->hasOldInput('chairman_name'));
        }
        $this->assertSame('Ketua RT Berlaku', $rt->fresh()->chairman_name);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_missing_columns_do_not_break_setup_or_region_creation(): void
    {
        [$rw, $rt] = $this->areas();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        Schema::table('rt_areas', fn ($table) => $table->renameColumn('chairman_name', 'chairman_name_unavailable'));
        Schema::table('rw_areas', fn ($table) => $table->renameColumn('chairman_name', 'chairman_name_unavailable'));
        try {
            $this->get(route('admin.setup'))->assertOk()->assertSee('Pengaturan Ketua RT/RW belum aktif');
            $this->post(route('admin.rt.store'), ['rw_area_id' => $rw->id, 'code' => '06', 'name' => 'RT Baru'])->assertSessionHasNoErrors();
            $this->post(route('admin.rt.store'), ['rw_area_id' => $rw->id, 'code' => '07', 'name' => 'RT Tertolak', 'chairman_name' => 'Belum Tersedia'])->assertSessionHasErrors('chairman_name');
            $this->patch(route('admin.rt.chairman', $rt), ['chairman_name' => 'Belum Tersedia'])->assertSessionHasErrors('chairman_name');
            $this->patch(route('admin.rw.chairman', $rw), ['chairman_name' => 'Belum Tersedia'])->assertSessionHasErrors('chairman_name');
            $this->assertDatabaseCount('rt_areas', 2);
        } finally {
            Schema::table('rt_areas', fn ($table) => $table->renameColumn('chairman_name_unavailable', 'chairman_name'));
            Schema::table('rw_areas', fn ($table) => $table->renameColumn('chairman_name_unavailable', 'chairman_name'));
        }
    }
}
