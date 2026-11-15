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

        foreach (['announcements.index' => 'create-announcement', 'agenda.index' => 'create-agenda', 'assets.index' => 'create-inventory', 'finance.index' => 'create-finance'] as $route => $dialog) {
            $this->get(route($route))->assertOk()
                ->assertSee('data-open-dialog="'.$dialog.'"', false)
                ->assertSee('<dialog id="'.$dialog.'"', false);
        }
    }

    public function test_failed_creation_reopens_the_correct_popup(): void
    {
        config(['session.driver' => 'database']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        foreach ([
            ['announcements.index', 'announcements.store', 'create-announcement'],
            ['agenda.index', 'agenda.store', 'create-agenda'],
            ['assets.index', 'assets.store', 'create-inventory'],
            ['finance.index', 'finance.entries.store', 'create-finance'],
        ] as [$index, $store, $dialog]) {
            $post = $this->flushSession()->from(route($index))->post(route($store), ['_form' => $dialog])
                ->assertRedirect(route($index))->assertSessionHasErrors()->assertSessionHas('_old_input._form', $dialog);
            $cookie = $post->getCookie(config('session.cookie'), false);
            $this->assertNotNull($cookie);
            $response = $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
                ->get(route($index))->assertOk();
            $this->assertMatchesRegularExpression('/<dialog\b[^>]*id="'.$dialog.'"[^>]*data-auto-open/s', $response->getContent());
        }
    }
}
