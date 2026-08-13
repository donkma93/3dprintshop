<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('is_admin', true)
            ->orderByRaw("CASE role WHEN 'super_admin' THEN 0 WHEN 'manager' THEN 1 WHEN 'staff' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = $this->assignableRoles();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        $data['is_admin'] = true;
        $data['is_active'] = $request->boolean('is_active', true);

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'Đã tạo tài khoản quản trị.');
    }

    public function edit(User $user)
    {
        $this->ensureAdminUser($user);
        $roles = $this->assignableRoles($user);

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdminUser($user);

        $data = $this->validated($request, $user);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_admin'] = true;
        $data['is_active'] = $request->boolean('is_active', true);

        // Không cho tự hạ / khóa chính mình
        if ($user->id === auth()->id()) {
            $data['role'] = $user->role;
            $data['is_active'] = true;
        }

        // Giữ ít nhất 1 super_admin active
        if ($user->isSuperAdmin() && ($data['role'] !== User::ROLE_SUPER_ADMIN || empty($data['is_active']))) {
            $otherSuper = User::where('is_admin', true)
                ->where('role', User::ROLE_SUPER_ADMIN)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->exists();
            if (! $otherSuper) {
                return back()->withInput()->with('error', 'Phải còn ít nhất một Quản trị viên đang hoạt động.');
            }
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function destroy(User $user)
    {
        $this->ensureAdminUser($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Bạn không thể xóa chính tài khoản đang đăng nhập.');
        }

        if ($user->isSuperAdmin()) {
            $otherSuper = User::where('is_admin', true)
                ->where('role', User::ROLE_SUPER_ADMIN)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->exists();
            if (! $otherSuper) {
                return back()->with('error', 'Không thể xóa Quản trị viên cuối cùng.');
            }
        }

        $user->tokens()->delete();
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Đã xóa tài khoản.');
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
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email đã được sử dụng.',
            'role.required' => 'Vui lòng chọn vai trò.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);
    }

    private function assignableRoles(?User $editing = null): array
    {
        $roles = Permission::roleOptions();

        // Chỉ super_admin mới gán được super_admin
        if (! auth()->user()?->isSuperAdmin()) {
            unset($roles[User::ROLE_SUPER_ADMIN]);
        }

        // Khi sửa, giữ role hiện tại trong list nếu đã bị ẩn
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
}
