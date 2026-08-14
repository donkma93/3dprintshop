import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/permissions/permission_guard.dart';
import '../auth/auth_controller.dart';

class HomeShell extends ConsumerWidget {
  final StatefulNavigationShell navigationShell;

  const HomeShell({super.key, required this.navigationShell});

  void _go(int index) {
    navigationShell.goBranch(
      index,
      initialLocation: index == navigationShell.currentIndex,
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).user;
    final destinations = <_Dest>[
      if (canAccess(ref, 'dashboard.view'))
        _Dest(0, Icons.dashboard_outlined, Icons.dashboard, 'Tổng quan'),
      if (canAccess(ref, 'sales.sell'))
        _Dest(1, Icons.qr_code_scanner, Icons.qr_code_scanner, 'Bán hàng'),
      if (canAccess(ref, 'chat.manage'))
        _Dest(2, Icons.chat_bubble_outline, Icons.chat_bubble, 'Chat'),
      if (canAccess(ref, 'orders.manage'))
        _Dest(3, Icons.shopping_bag_outlined, Icons.shopping_bag, 'Đơn'),
      _Dest(4, Icons.menu, Icons.menu, 'Thêm'),
    ];

    // Map branch index from go_router (fixed 5 branches) to visible nav
    final visible = destinations;
    final currentBranch = navigationShell.currentIndex;
    int selected = 0;
    for (var i = 0; i < visible.length; i++) {
      if (visible[i].branch == currentBranch) {
        selected = i;
        break;
      }
    }

    return Scaffold(
      body: navigationShell,
      bottomNavigationBar: NavigationBar(
        selectedIndex: selected.clamp(0, visible.length - 1),
        onDestinationSelected: (i) => _go(visible[i].branch),
        destinations: [
          for (final d in visible)
            NavigationDestination(
              icon: Icon(d.icon),
              selectedIcon: Icon(d.activeIcon),
              label: d.label,
            ),
        ],
      ),
      // ignore unused for analyzer
      floatingActionButton: user == null
          ? null
          : null,
    );
  }
}

class _Dest {
  final int branch;
  final IconData icon;
  final IconData activeIcon;
  final String label;
  _Dest(this.branch, this.icon, this.activeIcon, this.label);
}
