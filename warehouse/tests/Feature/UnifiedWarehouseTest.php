<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ItemMaster;
use App\Models\Item;
use App\Models\FormItem;
use App\Models\UnregistrasiItem;

class UnifiedWarehouseTest extends TestCase
{
    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_login_page_renders_successfully()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('PORTAL WAREHOUSE');
        $response->assertSee('PT METALART ASTRA INDONESIA');
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $user = User::where('username', 'admin')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Executive Overview');
        $response->assertSee('MARS');
        $response->assertSee('SATURNUS');
    }

    public function test_user_can_access_mars_pages()
    {
        $user = User::where('username', 'admin')->first();

        // Mars Dashboard
        $response = $this->actingAs($user)->get('/mars');
        $response->assertStatus(200);

        // Mars Item Master
        $response = $this->actingAs($user)->get('/mars/item-master');
        $response->assertStatus(200);

        // Mars Data PO
        $response = $this->actingAs($user)->get('/mars/data-po');
        $response->assertStatus(200);

        // Mars Item Outstanding
        $response = $this->actingAs($user)->get('/mars/item-outstanding');
        $response->assertStatus(200);

        // Mars Item Minim
        $response = $this->actingAs($user)->get('/mars/item-minim');
        $response->assertStatus(200);

        // Mars Kedatangan Barang
        $response = $this->actingAs($user)->get('/mars/kedatangan-barang');
        $response->assertStatus(200);

        // Mars History
        $response = $this->actingAs($user)->get('/mars/history');
        $response->assertStatus(200);
    }

    public function test_user_can_access_saturnus_pages()
    {
        $user = User::where('username', 'admin')->first();

        // Saturnus Dashboard
        $response = $this->actingAs($user)->get('/saturnus');
        $response->assertStatus(200);

        // Saturnus Form Registrasi
        $response = $this->actingAs($user)->get('/saturnus/form-registrasi');
        $response->assertStatus(200);

        // Saturnus Form Unregistrasi
        $response = $this->actingAs($user)->get('/saturnus/form-unregistrasi');
        $response->assertStatus(200);
    }

    public function test_user_can_access_settings_pages()
    {
        $user = User::where('username', 'admin')->first();

        // Settings Users
        $response = $this->actingAs($user)->get('/settings/users');
        $response->assertStatus(200);

        // Settings Roles
        $response = $this->actingAs($user)->get('/settings/roles');
        $response->assertStatus(200);
    }

    public function test_guest_user_login()
    {
        $response = $this->get('/guest-login');
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}
