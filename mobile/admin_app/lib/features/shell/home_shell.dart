import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/permissions/permission_guard.dart';
import '../../core/providers.dart';
import '../../l10n/app_localizations.dart';
import '../chat/chat_list_screen.dart';

class HomeShell extends ConsumerStatefulWidget {
  final StatefulNavigationShell navigationShell;

  const HomeShell({super.key, required this.navigationShell});

  @override
  ConsumerState<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends ConsumerState<HomeShell> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    // Poll total unread for bottom-nav chat badge even when not on chat tab.
    WidgetsBinding.instance.addPostFrameCallback((_) => _pollUnread());
    _timer = Timer.periodic(const Duration(seconds: 12), (_) => _pollUnread());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _pollUnread() async {
    if (!canAccess(ref, 'chat.manage')) return;
    try {
      final api = ref.read(apiClientProvider);
      final env = await api.get(
        '/admin/chat/notifications',
        query: {'with_list': 0},
      );
      final data = env.data;
      if (data is Map && mounted) {
        // Prefer conversation-level unread (open threads only). Message count
        // is a fallback; both are now scoped to open conversations server-side.
        final unread = data['unread_count'] ??
            data['unread_message_count'] ??
            data['count'] ??
            0;
        final n = unread is num
            ? unread.toInt()
            : int.tryParse(unread.toString()) ?? 0;
        ref.read(chatUnreadTotalProvider.notifier).state = n < 0 ? 0 : n;
      }
    } catch (_) {}
  }

  void _go(int index) {
    widget.navigationShell.goBranch(
      index,
      initialLocation: index == widget.navigationShell.currentIndex,
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final chatUnread = ref.watch(chatUnreadTotalProvider);

    final destinations = <_Dest>[
      if (canAccess(ref, 'dashboard.view'))
        _Dest(0, Icons.dashboard_outlined, Icons.dashboard, l10n.navDashboard),
      if (canAccess(ref, 'sales.sell'))
        _Dest(1, Icons.qr_code_scanner, Icons.qr_code_scanner, l10n.navSales),
      if (canAccess(ref, 'chat.manage'))
        _Dest(
          2,
          Icons.chat_bubble_outline,
          Icons.chat_bubble,
          l10n.navChat,
          badge: chatUnread,
        ),
      if (canAccess(ref, 'orders.manage'))
        _Dest(
          3,
          Icons.shopping_bag_outlined,
          Icons.shopping_bag,
          l10n.navOrders,
        ),
      _Dest(4, Icons.menu, Icons.menu, l10n.navMore),
    ];

    final visible = destinations;
    final currentBranch = widget.navigationShell.currentIndex;
    var selected = 0;
    for (var i = 0; i < visible.length; i++) {
      if (visible[i].branch == currentBranch) {
        selected = i;
        break;
      }
    }

    return Scaffold(
      body: widget.navigationShell,
      bottomNavigationBar: NavigationBar(
        height: 56,
        selectedIndex: selected.clamp(0, visible.length - 1),
        onDestinationSelected: (i) => _go(visible[i].branch),
        animationDuration: const Duration(milliseconds: 280),
        // Icon-only bottom bar (tooltips still show labels on long-press).
        labelBehavior: NavigationDestinationLabelBehavior.alwaysHide,
        destinations: [
          for (final d in visible)
            NavigationDestination(
              icon: _NavIcon(icon: d.icon, badge: d.badge, selected: false),
              selectedIcon:
                  _NavIcon(icon: d.activeIcon, badge: d.badge, selected: true),
              label: d.label,
              tooltip: d.label,
            ),
        ],
      ),
    );
  }
}

class _NavIcon extends StatelessWidget {
  final IconData icon;
  final int badge;
  final bool selected;

  const _NavIcon({
    required this.icon,
    required this.badge,
    required this.selected,
  });

  @override
  Widget build(BuildContext context) {
    final iconWidget = Icon(icon, size: 24);
    if (badge <= 0) return iconWidget;

    final label = badge > 99 ? '99+' : '$badge';
    return Badge(
      label: Text(
        label,
        style: const TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w800,
          color: Colors.white,
          height: 1.1,
        ),
      ),
      backgroundColor: const Color(0xFFDC2626),
      padding: const EdgeInsets.symmetric(horizontal: 5),
      smallSize: 16,
      largeSize: 18,
      child: iconWidget,
    );
  }
}

class _Dest {
  final int branch;
  final IconData icon;
  final IconData activeIcon;
  final String label;
  final int badge;

  _Dest(
    this.branch,
    this.icon,
    this.activeIcon,
    this.label, {
    this.badge = 0,
  });
}
