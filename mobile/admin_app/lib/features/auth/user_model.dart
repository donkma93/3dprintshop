class AdminUser {
  final int id;
  final String name;
  final String email;
  final String role;
  final String roleLabel;
  final List<String> permissions;
  final bool canViewRevenue;
  final bool isAdmin;
  final bool isActive;

  const AdminUser({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.roleLabel,
    required this.permissions,
    required this.canViewRevenue,
    required this.isAdmin,
    required this.isActive,
  });

  factory AdminUser.fromJson(Map<String, dynamic> json) {
    final perms = <String>[];
    final raw = json['permissions'];
    if (raw is List) {
      for (final p in raw) {
        perms.add(p.toString());
      }
    }
    return AdminUser(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      role: (json['role'] ?? '').toString(),
      roleLabel: (json['role_label'] ?? json['role'] ?? '').toString(),
      permissions: perms,
      canViewRevenue: json['can_view_revenue'] == true,
      isAdmin: json['is_admin'] == true,
      isActive: json['is_active'] != false,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'role': role,
        'role_label': roleLabel,
        'permissions': permissions,
        'can_view_revenue': canViewRevenue,
        'is_admin': isAdmin,
        'is_active': isActive,
      };

  bool can(String permission) {
    if (role == 'super_admin') return true;
    if (permissions.contains('*')) return true;
    return permissions.contains(permission);
  }
}
