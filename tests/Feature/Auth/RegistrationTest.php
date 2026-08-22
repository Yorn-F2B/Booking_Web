<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Chiến',
            'last_name' => 'Trịnh Ngọc',
            'email' => 'register-test@example.com',
            'phone' => '0912345678',
            'cccd' => '012345678901',
            'birthday' => '2000-01-01',
            'gender' => 'male',
            'address' => 'Thanh Hóa',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'register-test@example.com',
            'role' => 'customer',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('customers', [
            'email' => 'register-test@example.com',
            'phone' => '0912345678',
            'cccd' => '012345678901',
            'status' => 'active',
        ]);

        $customer = Customer::where('email', 'register-test@example.com')->firstOrFail();
        $this->assertNotNull($customer->user_id);
    }
}
