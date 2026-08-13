<?php

namespace App\Http\Resources;

use App\Support\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => (bool) $this->is_admin,
            'is_active' => (bool) ($this->is_active ?? true),
            'role' => $this->role,
            'role_label' => Permission::roleLabel($this->role),
            'permissions' => $this->permissions(),
            'can_view_revenue' => $this->canViewRevenue(),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
