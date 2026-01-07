<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PasswordsAreHashedTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_user_passwords_are_hashed()
    {
        // seed a user to ensure the users table exists and contains data
        User::factory()->create();

        $users = User::all();

        foreach ($users as $user) {
            $this->assertTrue(Hash::isHashed($user->password), "Password for user {$user->email} is not hashed");
        }
    }
}
