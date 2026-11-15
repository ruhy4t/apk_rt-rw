<?php

namespace Tests\Feature;

use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterfaceNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_navigation_keeps_private_management_links_out_of_the_page(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('id="pengumuman"', false)
            ->assertSee('id="agenda"', false)
            ->assertSee('id="ringkasan"', false)
            ->assertDontSee('href="'.route('residents.index').'"', false)
            ->assertDontSee('href="'.route('users.index').'"', false);
    }

    public function test_resident_workspace_shows_own_services_and_excludes_management_navigation(): void
    {
        $user = User::factory()->create(['role' => 'resident']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('href="'.route('households.mine').'"', false)
            ->assertSee('href="'.route('requests.index').'"', false)
            ->assertSee('Menu navigasi')
            ->assertDontSee('href="'.route('residents.index').'"', false)
            ->assertDontSee('href="'.route('users.index').'"', false)
            ->assertDontSee('href="'.route('admin.setup').'"', false);
    }

    public function test_administrator_pages_render_with_navigation_and_existing_form_actions(): void
    {
        $rw = RwArea::query()->create(['code' => 'DEMO-RW', 'name' => 'Wilayah uji']);
        RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => 'DEMO-RT', 'name' => 'Lingkungan uji']);
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        foreach (['dashboard', 'residents.index', 'requests.index', 'finance.index', 'assets.index', 'announcements.index', 'agenda.index', 'users.index', 'admin.setup', 'profile.edit'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('aria-label="Navigasi utama"', false);
        }

        $this->get(route('residents.index'))->assertOk()
            ->assertSee('data-open-dialog="create-household"', false)
            ->assertSee('action="'.route('households.store').'"', false)
            ->assertSee('name="national_id"', false)
            ->assertSee('name="family_card_number"', false);
    }
}
