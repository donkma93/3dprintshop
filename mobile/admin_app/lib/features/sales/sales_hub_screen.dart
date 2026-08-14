import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';

class SalesHubScreen extends ConsumerWidget {
  const SalesHubScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final canRevenue =
        ref.watch(authControllerProvider).user?.canViewRevenue ?? false;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.navSales)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _tile(
            context,
            icon: Icons.qr_code_scanner,
            title: l10n.navSalesScan,
            subtitle: l10n.scanQr,
            route: '/sales/scan',
            color: const Color(0xFF0F172A),
          ),
          _tile(
            context,
            icon: Icons.receipt_long,
            title: l10n.navSalesHistory,
            subtitle: l10n.history,
            route: '/sales/history',
          ),
          if (canRevenue)
            _tile(
              context,
              icon: Icons.bar_chart,
              title: l10n.navSalesReport,
              subtitle: l10n.report,
              route: '/sales/report',
            ),
        ],
      ),
    );
  }

  Widget _tile(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required String route,
    Color? color,
  }) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color ?? Colors.blueGrey.shade100,
          child:
              Icon(icon, color: color != null ? Colors.white : Colors.blueGrey),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(subtitle),
        trailing: const Icon(Icons.chevron_right),
        onTap: () => context.push(route),
      ),
    );
  }
}
