<?php

namespace App\Models;

use App\Support\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_STAFF = 'staff';

    public const ROLE_CONTENT = 'content';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin && $this->is_active !== false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isActive(): bool
    {
        return $this->is_active !== false;
    }

    public function permissions(): array
    {
        return Permission::permissionsForRole($this->role);
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissions(), true);
    }

    public function canViewRevenue(): bool
    {
        return $this->hasPermission('revenue.view');
    }

    public function roleLabel(): string
    {
        return Permission::roleLabel($this->role);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Đồng bộ is_admin khi gán role admin.
     */
    public static function syncAdminFlag(array $data): array
    {
        if (isset($data['role'])) {
            $data['is_admin'] = array_key_exists($data['role'], Permission::roles());
        }

        return $data;
    }
}
