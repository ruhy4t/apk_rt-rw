<?php

namespace Tests\Feature;

use App\Models\RwArea;
use App\Services\RwIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RwIdentityInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_may_start_empty_but_partial_administrative_names_are_rejected(): void
    {
        $this->assertFalse(Validator::make([], RwIdentity::rules())->fails());
        $this->assertTrue(Validator::make(['village_name' => 'Kelurahan Uji'], RwIdentity::rules())->fails());
        $this->assertTrue(Validator::make(['village_name' => 'Kelurahan Uji', 'district_name' => 'Kecamatan Uji'], RwIdentity::rules())->fails());
        $this->assertTrue(Validator::make([], RwIdentity::rules(true))->fails());
        $this->assertTrue(Validator::make(['village_name' => str_repeat('x', 121), 'district_name' => 'Kecamatan Uji', 'city_name' => 'Kota Uji'], RwIdentity::rules())->fails());
    }

    public function test_valid_administrative_identity_is_stored_without_changing_chairman_encryption(): void
    {
        $data = ['code' => '05', 'name' => 'RW Uji', 'chairman_name' => 'Ketua Uji', 'village_name' => ' Kelurahan Uji ', 'district_name' => 'Kecamatan Uji', 'city_name' => 'Kota Uji'];
        $this->assertFalse(Validator::make($data, RwIdentity::rules(true))->fails());
        $rw = RwArea::query()->create(RwIdentity::forStorage($data));
        $this->assertSame('Kelurahan Uji', $rw->fresh()->village_name);
        $this->assertSame('Kecamatan Uji', $rw->fresh()->district_name);
        $this->assertSame('Kota Uji', $rw->fresh()->city_name);
        $this->assertSame('Ketua Uji', $rw->fresh()->chairman_name);
        $this->assertNotSame('Ketua Uji', $rw->getRawOriginal('chairman_name'));
        $this->assertArrayNotHasKey('chairman_name', $rw->toArray());
        $this->assertTrue(RwIdentity::ready());
        $rw->update(RwIdentity::forStorage(['code' => '05', 'name' => 'RW Nama Diperbaiki']));
        $this->assertSame('Kelurahan Uji', $rw->fresh()->village_name);
        $this->assertSame('Kecamatan Uji', $rw->fresh()->district_name);
        $this->assertSame('Kota Uji', $rw->fresh()->city_name);
    }

    public function test_uninstalled_identity_columns_do_not_accept_nonblank_values_or_break_existing_region_data(): void
    {
        Schema::table('rw_areas', fn ($table) => $table->renameColumn('city_name', 'city_name_unavailable'));
        try {
            $this->assertFalse(RwIdentity::ready());
            $this->assertSame(['code' => '05', 'name' => 'RW Uji'], RwIdentity::forStorage(['code' => '05', 'name' => 'RW Uji', 'village_name' => '', 'district_name' => null, 'city_name' => '']));
            try {
                RwIdentity::forStorage(['village_name' => 'Kelurahan Uji', 'district_name' => 'Kecamatan Uji', 'city_name' => 'Kota Uji']);
                $this->fail('Uninstalled schema must reject identity writes.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('rw_identity', $exception->errors());
            }
        } finally {
            Schema::table('rw_areas', fn ($table) => $table->renameColumn('city_name_unavailable', 'city_name'));
        }
    }
}
