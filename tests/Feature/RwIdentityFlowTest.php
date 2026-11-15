<?php

namespace Tests\Feature;

use App\Models\RwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RwIdentityFlowTest extends TestCase
{
    use RefreshDatabase;

    private function identity(): array
    {
        return ['village_name' => 'Kelurahan Uji', 'district_name' => 'Kecamatan Uji', 'city_name' => 'Kota Uji'];
    }

    public function test_rw_creation_requires_complete_administrative_identity_when_schema_is_ready(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->post(route('admin.rw.store'), ['code' => '05', 'name' => 'RW 05'])->assertSessionHasErrors(['village_name', 'district_name', 'city_name']);
        $this->assertDatabaseCount('rw_areas', 0);
        $this->post(route('admin.rw.store'), ['code' => '05', 'name' => 'RW 05', ...$this->identity()])->assertSessionHasNoErrors();
        $rw = RwArea::query()->firstOrFail();
        foreach ($this->identity() as $field => $value) {
            $this->assertSame($value, $rw->$field);
        }
        $this->get(route('admin.setup'))->assertOk()->assertSee('Kelurahan Uji')->assertSee('Kecamatan Uji')->assertSee('Kota Uji')->assertSee('Tambah RT');
    }

    public function test_rw_identity_can_be_corrected_as_a_complete_set_while_legacy_code_edits_preserve_it(): void
    {
        $rw = RwArea::query()->create(['code' => '05', 'name' => 'RW Uji', ...$this->identity()]);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->patch(route('admin.rw.update', $rw), ['code' => '05', 'name' => 'Nama Diperbaiki'])->assertSessionHasNoErrors();
        $this->assertSame('Kelurahan Uji', $rw->fresh()->village_name);
        $this->patch(route('admin.rw.update', $rw), ['code' => '05', 'name' => 'Gagal', 'village_name' => 'Kelurahan Baru'])->assertSessionHasErrors(['district_name', 'city_name']);
        $this->assertSame('Nama Diperbaiki', $rw->fresh()->name);
        $this->patch(route('admin.rw.update', $rw), ['code' => '05', 'name' => 'RW Uji', ...$this->identity(), 'village_name' => 'Kelurahan Baru'])->assertSessionHasNoErrors();
        $this->assertSame('Kelurahan Baru', $rw->fresh()->village_name);
    }

    public function test_geo_identity_cannot_be_changed_by_non_administrators(): void
    {
        $rw = RwArea::query()->create(['code' => '05', 'name' => 'RW Uji', ...$this->identity()]);
        $this->actingAs(User::factory()->create(['role' => 'rw_operator', 'rw_area_id' => $rw->id]));
        $this->patch(route('admin.rw.update', $rw), ['code' => '05', 'name' => 'Gagal', ...$this->identity(), 'city_name' => 'Kota Baru'])->assertForbidden();
        $this->assertSame('Kota Uji', $rw->fresh()->city_name);
    }

    public function test_rw_creation_remains_available_before_geo_columns_are_installed(): void
    {
        Schema::table('rw_areas', fn ($table) => $table->renameColumn('city_name', 'city_name_unavailable'));
        try {
            $this->actingAs(User::factory()->create(['role' => 'super_admin']));
            $this->post(route('admin.rw.store'), ['code' => '05', 'name' => 'RW Uji', ...$this->identity()])->assertSessionHasErrors('rw_identity');
            $this->post(route('admin.rw.store'), ['code' => '05', 'name' => 'RW Uji'])->assertSessionHasNoErrors();
            $this->get(route('admin.setup'))->assertOk();
            $this->assertDatabaseCount('rw_areas', 1);
        } finally {
            Schema::table('rw_areas', fn ($table) => $table->renameColumn('city_name_unavailable', 'city_name'));
        }
    }
}
