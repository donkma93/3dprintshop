import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/permissions/permission_guard.dart';
import '../../l10n/app_localizations.dart';

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
    final l10n = context.l10n;
    final destinations = <_Dest>[
      if (canAccess(ref, 'dashboard.view'))
        _Dest(0, Icons.dashboard_outlined, Icons.dashboard, l10n.navDashboard),
      if (canAccess(ref, 'sales.sell'))
        _Dest(1, Icons.qr_code_scanner, Icons.qr_code_scanner, l10n.navSales),
      if (canAccess(ref, 'chat.manage'))
        _Dest(2, Icons.chat_bubble_outline, Icons.chat_bubble, l10n.navChat),
      if (canAccess(ref, 'orders.manage'))
        _Dest(3, Icons.shopping_bag_outlined, Icons.shopping_bag, l10n.navOrders),
      _Dest(4, Icons.menu, Icons.menu, l10n.navMore),
    ];

    final visible = destinations;
    final currentBranch = navigationShell.currentIndex;
    var selected = 0;
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
