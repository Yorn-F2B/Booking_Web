<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test Guest redirects.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/bookings');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/staffs');
        $response->assertRedirect('/login');
    }

    /**
     * Test Customer role permissions and redirects.
     */
    public function test_customer_cannot_access_any_admin_routes(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertRedirect('/');

        $response = $this->actingAs($user)->get('/admin/bookings');
        $response->assertRedirect('/');

        $response = $this->actingAs($user)->get('/admin/staffs');
        $response->assertRedirect('/');
    }

    /**
     * Test Receptionist role permissions and redirects.
     */
    public function test_receptionist_permissions(): void
    {
        $user = User::factory()->create(['role' => 'receptionist']);

        // Receptionist cannot access super admin dashboard, gets redirected to bookings
        $response = $this->actingAs($user)->get('/admin');
        $response->assertRedirect('/admin/bookings');

        // Can access bookings
        $response = $this->actingAs($user)->get('/admin/bookings');
        $response->assertOk();

        // Cannot access staffs, gets redirected to bookings
        $response = $this->actingAs($user)->get('/admin/staffs');
        $response->assertRedirect('/admin/bookings');
    }

    /**
     * Test Receptionist Lead role permissions and redirects.
     */
    public function test_receptionist_lead_permissions(): void
    {
        $user = User::factory()->create(['role' => 'receptionist_lead']);

        // Cannot access super admin dashboard, gets redirected to bookings
        $response = $this->actingAs($user)->get('/admin');
        $response->assertRedirect('/admin/bookings');

        // Can access bookings
        $response = $this->actingAs($user)->get('/admin/bookings');
        $response->assertOk();

        // Cannot access staffs, gets redirected to bookings
        $response = $this->actingAs($user)->get('/admin/staffs');
        $response->assertRedirect('/admin/bookings');
    }

    /**
     * Test Housekeeping role permissions and redirects.
     */
    public function test_housekeeping_permissions(): void
    {
        $user = User::factory()->create(['role' => 'housekeeping']);

        // Cannot access super admin dashboard, gets redirected to housekeeping
        $response = $this->actingAs($user)->get('/admin');
        $response->assertRedirect('/admin/housekeeping');

        // Cannot access bookings, gets redirected to housekeeping
        $response = $this->actingAs($user)->get('/admin/bookings');
        $response->assertRedirect('/admin/housekeeping');

        // Cannot access staffs, gets redirected to housekeeping
        $response = $this->actingAs($user)->get('/admin/staffs');
        $response->assertRedirect('/admin/housekeeping');

        // Can access housekeeping
        $response = $this->actingAs($user)->get('/admin/housekeeping');
        $response->assertOk();
    }

    /**
     * Test Housekeeping Supervisor role permissions and redirects.
     */
    public function test_housekeeping_supervisor_permissions(): void
    {
        $user = User::factory()->create(['role' => 'housekeeping_supervisor']);

        // Cannot access super admin dashboard, gets redirected to housekeeping
        $response = $this->actingAs($user)->get('/admin');
        $response->assertRedirect('/admin/housekeeping');

        // Cannot access bookings, gets redirected to housekeeping
        $response = $this->actingAs($user)->get('/admin/bookings');
        $response->assertRedirect('/admin/housekeeping');

        // Cannot access staffs, gets redirected to housekeeping
        $response = $this->actingAs($user)->get('/admin/staffs');
        $response->assertRedirect('/admin/housekeeping');

        // Can access housekeeping
        $response = $this->actingAs($user)->get('/admin/housekeeping');
        $response->assertOk();
    }

    /**
     * Test Manager role permissions and redirects.
     */
    public function test_manager_permissions(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        // Cannot access super admin dashboard, gets redirected to bookings
        $response = $this->actingAs($user)->get('/admin');
        $response->assertRedirect('/admin/bookings');

        // Can access bookings
        $response = $this->actingAs($user)->get('/admin/bookings');
        $response->assertOk();

        // Cannot access staffs, gets redirected to bookings
        $response = $this->actingAs($user)->get('/admin/staffs');
        $response->assertRedirect('/admin/bookings');
    }

    /**
     * Test Super Admin role permissions.
     */
    public function test_super_admin_permissions(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        // Can access everything
        $response = $this->actingAs($user)->get('/admin');
        $response->assertOk();

        $response = $this->actingAs($user)->get('/admin/bookings');
        $response->assertOk();

        $response = $this->actingAs($user)->get('/admin/staffs');
        $response->assertOk();
    }
}
