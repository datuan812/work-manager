<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function familyLogin(Request $request)
    {
        $data = $request->validate([
            'family_code' => ['required', 'string', 'max:32'],
        ]);

        $family = Family::query()
            ->where('code', strtoupper(trim($data['family_code'])))
            ->where('is_active', true)
            ->first();
        $parent = $family?->users()->withoutGlobalScopes()
            ->where('role', UserRole::PARENT->value)
            ->where('is_active', true)
            ->oldest('id')
            ->first();

        if (! $parent) {
            throw ValidationException::withMessages(['family_code' => 'Mã gia đình không đúng hoặc gia đình chưa có tài khoản phụ huynh.']);
        }

        Auth::login($parent, true);
        $request->session()->regenerate();
        $request->session()->put('family_id', $parent->family_id);

        return ['user' => $this->userPayload($parent), 'csrf_token' => csrf_token()];
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages(['email' => 'Thông tin đăng nhập không đúng.']);
        }

        $request->session()->regenerate();
        $request->session()->put('family_id', $request->user()->family_id);

        abort_unless($request->user()->is_active && in_array($request->user()->role, [UserRole::PARENT, UserRole::ADMIN], true), 403);

        return ['user' => $this->userPayload($request->user()), 'csrf_token' => csrf_token()];
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->forget('family_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ['ok' => true];
    }

    public function me(Request $request)
    {
        abort_unless($request->user()?->isParent() || $request->user()?->isAdmin(), 403);

        return ['user' => $this->userPayload($request->user())];
    }

    private function userPayload($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'avatar' => $user->avatar,
            'family_id' => $user->family_id,
            'family_code' => $user->family?->code,
        ];
    }
}
