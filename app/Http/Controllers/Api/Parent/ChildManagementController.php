<?php

namespace App\Http\Controllers\Api\Parent;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChildManagementController extends Controller
{
    public function index()
    {
        return User::query()->where('role', UserRole::CHILD)->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data = $this->storeAvatar($request, $data);
        $data['role'] = UserRole::CHILD;
        $data['email'] = null;
        $data['password'] = null;

        return response()->json(User::create($data), 201);
    }

    public function show(User $user)
    {
        abort_unless($user->isChild(), 404);

        return $user;
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->isChild(), 404);

        $data = $this->storeAvatar($request, $request->validate($this->rules(false)), $user);

        $user->update($data);

        return $user;
    }

    public function destroy(User $user)
    {
        abort_unless($user->isChild(), 404);
        $user->delete();

        return response()->noContent();
    }

    private function rules(bool $creating = true): array
    {
        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'avatar_file' => ['nullable', 'image', 'max:2048'],
            'date_of_birth' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function storeAvatar(Request $request, array $data, ?User $user = null): array
    {
        unset($data['avatar_file']);

        if (! $request->hasFile('avatar_file')) {
            return $data;
        }

        $this->deleteLocalAvatar($user?->avatar);

        $path = $request->file('avatar_file')->store('avatars', 'public');

        return [
            ...$data,
            'avatar' => Storage::url($path),
        ];
    }

    private function deleteLocalAvatar(?string $avatar): void
    {
        if (! $avatar || ! str_starts_with($avatar, '/storage/avatars/')) {
            return;
        }

        Storage::disk('public')->delete(str_replace('/storage/', '', $avatar));
    }
}
