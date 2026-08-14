import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../features/auth/auth_controller.dart';

class PermissionGate extends ConsumerWidget {
  final String permission;
  final Widget child;
  final Widget? fallback;
  final bool requireRevenue;

  const PermissionGate({
    super.key,
    required this.permission,
    required this.child,
    this.fallback,
    this.requireRevenue = false,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).user;
    if (user == null) return fallback ?? const SizedBox.shrink();
    if (requireRevenue && !user.canViewRevenue) {
      return fallback ?? const SizedBox.shrink();
    }
    if (!user.can(permission)) {
      return fallback ?? const SizedBox.shrink();
    }
    return child;
  }
}

bool canAccess(WidgetRef ref, String permission, {bool revenue = false}) {
  final user = ref.read(authControllerProvider).user;
  if (user == null) return false;
  if (revenue && !user.canViewRevenue) return false;
  return user.can(permission);
}
