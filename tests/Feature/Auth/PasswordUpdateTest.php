<?php

use App\Models\Log;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'Nueva#Segura2026',
            'password_confirmation' => 'Nueva#Segura2026',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('Nueva#Segura2026', $user->refresh()->password));
    expect(Log::where('user_id', $user->id)->where('accion', 'CAMBIO_CONTRASENA')->exists())->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'Nueva#Segura2026',
            'password_confirmation' => 'Nueva#Segura2026',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});
