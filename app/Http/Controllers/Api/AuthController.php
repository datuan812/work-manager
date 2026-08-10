<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'role' => UserRole::PARENT->value], true)) {
            throw ValidationException::withMessages(['email' => 'Thông tin đăng nhập không đúng.']);
        }

        $request->session()->regenerate();

        return ['user' => $this->parentPayload($request->user())];
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ['ok' => true];
    }

    public function me(Request $request)
    {
        abort_unless($request->user()?->isParent(), 403);

        return ['user' => $this->parentPayload($request->user())];
    }

    private function parentPayload($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'avatar' => $user->avatar,
        ];
    }
}
