<?php

namespace App\Support;

class Permission
{
    public static function roles(): array
    {
        return config('permissions.roles', []);
    }

    public static function allPermissions(): array
    {
        return config('permissions.permissions', []);
    }

    public static function permissionsForRole(?string $role): array
    {
        if (! $role) {
            return [];
        }

        $map = config('permissions.role_permissions', []);
        $list = $map[$role] ?? [];

        if (in_array('*', $list, true)) {
            return array_keys(self::allPermissions());
        }

        return $list;
    }

    public static function roleLabel(?string $role): string
    {
        return self::roles()[$role] ?? ($role ?: '—');
    }

    public static function permissionLabel(string $permission): string
    {
        return self::allPermissions()[$permission] ?? $permission;
    }

    public static function roleOptions(): array
    {
        return self::roles();
    }

    /**
     * Permission required for a named admin route (or null if unrestricted beyond is_admin).
     */
    public static function forRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $map = config('permissions.route_permissions', []);

        if (isset($map[$routeName])) {
            return $map[$routeName];
        }

        foreach ($map as $pattern => $permission) {
            if (! str_contains($pattern, '*')) {
                continue;
            }
            $regex = '/^'.str_replace(['\*', '\.'], ['.*', '\.'], preg_quote($pattern, '/')).'$/';
            if (preg_match($regex, $routeName)) {
                return $permission;
            }
        }

        return null;
    }

    /**
     * Permission for API admin path, e.g. api/v1/admin/products/1 → products.manage
     */
    public static function forApiPath(string $path): ?string
    {
        // normalize: api/v1/admin/xxx/...
        $path = trim($path, '/');
        if (! preg_match('#^api/v1/admin/([^/]+)#', $path, $m)) {
            return null;
        }

        $segment = $m[1];
        // me, login, logout — không map
        if (in_array($segment, ['me', 'login', 'logout', 'logout-all'], true)) {
            return null;
        }

        $map = config('permissions.api_path_permissions', []);

        return $map[$segment] ?? null;
    }
}
