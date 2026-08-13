<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('is_admin', true)
            ->orderByRaw("CASE role WHEN 'super_admin' THEN 0 WHEN 'manager' THEN 1 WHEN 'staff' THEN 2 ELSE 3 END")
            ->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }

        $users = $query->paginate((int) $request->input('per_page', 15));

        return $this->ok(UserResource::collection($users));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        $data['is_admin'] = true;
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::create($data);

        return $this->created(new UserResource($user), 'Đã tạo tài khoản quản trị.');
    }

    public function show(User $user): JsonResponse
    {
        $this->ensureAdminUser($user);

        return $this->ok(new UserResource($user));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->ensureAdminUser($user);

        $data = $this->validated($request, $user);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_admin'] = true;
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        if ($user->id === $request->user()->id) {
            $data['role'] = $user->role;
            $data['is_active'] = true;
        }

        if ($user->isSuperAdmin() && (
            (isset($data['role']) && $data['role'] !== User::ROLE_SUPER_ADMIN)
            || (array_key_exists('is_active', $data) && ! $data['is_active'])
        )) {
            if (! $this->hasOtherActiveSuperAdmin($user)) {
                return $this->fail('Phải còn ít nhất một Quản trị viên đang hoạt động.', 422);
            }
        }

        $user->update($data);

        return $this->ok(new UserResource($user->fresh()), 'Đã cập nhật tài khoản.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->ensureAdminUser($user);

        if ($user->id === $request->user()->id) {
            return $this->fail('Bạn không thể xóa chính tài khoản đang đăng nhập.', 422);
        }

        if ($user->isSuperAdmin() && ! $this->hasOtherActiveSuperAdmin($user)) {
            return $this->fail('Không thể xóa Quản trị viên cuối cùng.', 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->ok(null, 'Đã xóa tài khoản.');
    }

    public function roles(): JsonResponse
    {
        $roles = $this->assignableRoles();
        $all = Permission::allPermissions();
        $map = [];
        foreach ($roles as $key => $label) {
            $perms = Permission::permissionsForRole($key);
            $map[] = [
                'key' => $key,
                'label' => $label,
                'permissions' => array_map(fn ($p) => [
                    'key' => $p,
                    'label' => $all[$p] ?? $p,
                ], $perms),
            ];
        }

        return $this->ok($map);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $roles = array_keys($this->assignableRoles($user));

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in($roles)],
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::min(6),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function assignableRoles(?User $editing = null): array
    {
        $roles = Permission::roleOptions();

        if (! auth()->user()?->isSuperAdmin()) {
            unset($roles[User::ROLE_SUPER_ADMIN]);
        }

        if ($editing && $editing->role && ! isset($roles[$editing->role])) {
            $roles[$editing->role] = Permission::roleLabel($editing->role);
        }

        return $roles;
    }

    private function ensureAdminUser(User $user): void
    {
        if (! $user->is_admin) {
            abort(404);
        }
    }

    private function hasOtherActiveSuperAdmin(User $user): bool
    {
        return User::where('is_admin', true)
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->exists();
    }
}
