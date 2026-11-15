<?php

namespace Tests\Feature;

use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleRwTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_set_up_the_first_rw_but_cannot_add_a_second(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->get(route('admin.setup'))->assertOk()
            ->assertSee('data-open-dialog="create-rw"', false)
            ->assertDontSee('data-open-dialog="create-rt"', false);

        $this->post(route('admin.rw.store'), ['code' => '01', 'name' => 'RW Uji'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.rw.store'), ['code' => '02', 'name' => 'RW Tambahan'])
            ->assertSessionHasErrors('rw');

        $this->assertDatabaseCount('rw_areas', 1);
        $this->assertDatabaseHas('rw_areas', ['code' => '01', 'name' => 'RW Uji']);
        $this->assertDatabaseMissing('rw_areas', ['code' => '02']);
        $this->assertDatabaseCount('audit_events', 1);
        $this->get(route('admin.setup'))->assertOk()
            ->assertDontSee('data-open-dialog="create-rw"', false)
            ->assertDontSee('<dialog id="create-rw"', false)
            ->assertSee('data-open-dialog="create-rt"', false);
    }

    public function test_several_rts_can_be_added_under_the_single_rw(): void
    {
        $rw = RwArea::query()->create(['code' => '01', 'name' => 'RW Uji']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        foreach (['01', '02'] as $code) {
            $this->post(route('admin.rt.store'), ['rw_area_id' => $rw->id, 'code' => $code, 'name' => 'RT Uji '.$code])
                ->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('rw_areas', 1);
        $this->assertDatabaseCount('rt_areas', 2);
        $this->assertSame(2, $rw->rtAreas()->count());
        $this->get(route('admin.setup'))->assertOk()
            ->assertSee('name="rw_area_id" value="'.$rw->id.'"', false);
    }

    public function test_legacy_multiple_rw_data_is_preserved_and_requires_a_decision(): void
    {
        $first = RwArea::query()->create(['code' => '01', 'name' => 'RW Lama Satu']);
        RwArea::query()->create(['code' => '02', 'name' => 'RW Lama Dua']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));

        $this->get(route('admin.setup'))->assertOk()
            ->assertSee('Data lama masih berisi lebih dari satu RW')
            ->assertSee('RW Lama Satu')->assertSee('RW Lama Dua')
            ->assertDontSee('data-open-dialog="create-rw"', false)
            ->assertDontSee('data-open-dialog="create-rt"', false);
        $this->post(route('admin.rw.store'), ['code' => '03', 'name' => 'RW Baru'])
            ->assertSessionHasErrors('rw');
        $this->post(route('admin.rt.store'), ['rw_area_id' => $first->id, 'code' => '01', 'name' => 'RT Baru'])
            ->assertSessionHasErrors('rw_area_id');

        $this->assertDatabaseCount('rw_areas', 2);
        $this->assertDatabaseCount('rt_areas', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_other_roles_cannot_create_rw_or_rt(): void
    {
        $rw = RwArea::query()->create(['code' => '01', 'name' => 'RW Uji']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => '01', 'name' => 'RT Uji']);
        foreach ([
            ['role' => 'resident'],
            ['role' => 'rw_operator', 'rw_area_id' => $rw->id],
            ['role' => 'rt_operator', 'rt_area_id' => $rt->id],
        ] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->post(route('admin.rw.store'), ['code' => '02', 'name' => 'Dilarang'])->assertForbidden();
            $this->post(route('admin.rt.store'), ['rw_area_id' => $rw->id, 'code' => '02', 'name' => 'Dilarang'])->assertForbidden();
        }
        $this->assertDatabaseCount('rw_areas', 1);
        $this->assertDatabaseCount('rt_areas', 1);
    }

    public function test_rw_guard_works_when_account_data_is_encrypted(): void
    {
        config(['personal_data.encryption_enabled' => true]);
        $firstAdmin = User::factory()->create(['role' => 'super_admin']);
        $otherAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($otherAdmin)->post(route('admin.rw.store'), ['code' => '01', 'name' => 'RW Uji'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($firstAdmin)->post(route('admin.rw.store'), ['code' => '02', 'name' => 'RW Tambahan'])
            ->assertSessionHasErrors('rw');
        $this->assertDatabaseCount('rw_areas', 1);
    }
}
