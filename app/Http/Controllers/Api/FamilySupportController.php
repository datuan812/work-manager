<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Family;
use App\Models\User;
use App\Services\FamilySessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilySupportController extends Controller
{
    public function parents(Family $family)
    {
        return $family->users()->withoutGlobalScope('family')
            ->where('role', UserRole::PARENT->value)->where('is_active', true)
            ->orderBy('name')->get(['id', 'name']);
    }

    public function rotateCode(Request $request, Family $family, FamilySessionService $sessions)
    {
        return DB::transaction(function () use ($request, $family, $sessions) {
            $family = Family::query()->lockForUpdate()->findOrFail($family->id);
            $family->update(['code' => 'GD'.strtoupper(bin2hex(random_bytes(10)))]);
            $sessions->revoke($family);
            AdminAuditLog::record($request->user(), 'family.code_rotated', $family);

            return $family->loadCount('users');
        });
    }

    public function revokeSessions(Request $request, Family $family, FamilySessionService $sessions)
    {
        DB::transaction(function () use ($request, $family, $sessions) {
            $family = Family::query()->lockForUpdate()->findOrFail($family->id);
            $sessions->revoke($family);
            AdminAuditLog::record($request->user(), 'family.sessions_revoked', $family);
        });

        return response()->noContent();
    }

    public function issuePinReset(Request $request, Family $family)
    {
        $data = $request->validate(['user_id' => ['required', 'integer'], 'identity_verified' => ['required', 'accepted']]);
        $token = bin2hex(random_bytes(32));
        $expires = now()->addMinutes(30);

        DB::transaction(function () use ($request, $family, $data, $token, $expires) {
            $family = Family::query()->where('is_active', true)->lockForUpdate()->findOrFail($family->id);
            $parent = $family->users()->withoutGlobalScope('family')->where('role', UserRole::PARENT->value)
                ->where('is_active', true)->lockForUpdate()->findOrFail($data['user_id']);

            DB::table('parent_pin_resets')->updateOrInsert(
                ['user_id' => $parent->id],
                ['token_hash' => hash('sha256', $token), 'expires_at' => $expires],
            );

            AdminAuditLog::record($request->user(), 'parent.pin_reset_issued', $parent, ['family_id' => $family->id]);
        });

        return response()->json(['token' => $token, 'expires_at' => $expires->toIso8601String()], 201)
            ->header('Cache-Control', 'no-store');
    }

    public function resetPin(Request $request, FamilySessionService $sessions)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
            'pin' => ['required', 'string', 'digits_between:4,6', 'confirmed'],
        ]);
        $hash = hash('sha256', $data['token']);
        $invalid = fn () => ValidationException::withMessages(['token' => 'Liên kết đã hết hạn hoặc đã được sử dụng.']);

        DB::transaction(function () use ($data, $hash, $sessions, $invalid) {
            $target = DB::table('parent_pin_resets')->where('token_hash', $hash)->first();
            $familyId = User::withoutGlobalScope('family')->find($target?->user_id)?->family_id;
            $family = Family::query()->where('is_active', true)->lockForUpdate()->find($familyId);

            if (! $family) {
                throw $invalid();
            }

            $parent = $family->users()->withoutGlobalScope('family')->where('role', UserRole::PARENT->value)
                ->where('is_active', true)->lockForUpdate()->find($target->user_id);
            $valid = DB::table('parent_pin_resets')->where('user_id', $target->user_id)->where('token_hash', $hash)
                ->where('expires_at', '>', now())->lockForUpdate()->first();

            if (! $parent || ! $valid) {
                throw $invalid();
            }

            $parent->update(['parent_access_pin' => $data['pin'], 'parent_access_pin_enabled' => true]);
            $sessions->revoke($family);
            AdminAuditLog::record(null, 'parent.pin_reset_completed', $parent, ['family_id' => $family->id]);
        });

        return response()->noContent();
    }
}
