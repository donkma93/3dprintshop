import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class SalesHubScreen extends StatelessWidget {
  const SalesHubScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Bán hàng QR')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _tile(
            context,
            icon: Icons.qr_code_scanner,
            title: 'Quét / bán hàng',
            subtitle: 'Lookup QR · ghi nhận bán · KH & giao hàng',
            route: '/sales/scan',
            color: const Color(0xFF0F172A),
          ),
          _tile(
            context,
            icon: Icons.receipt_long,
            title: 'Lịch sử bán',
            subtitle: 'Giao dịch, in phiếu gửi',
            route: '/sales/history',
          ),
          _tile(
            context,
            icon: Icons.bar_chart,
            title: 'Báo cáo lãi lỗ',
            subtitle: 'Chỉ super_admin (revenue.view)',
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
          child: Icon(icon, color: color != null ? Colors.white : Colors.blueGrey),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(subtitle),
        trailing: const Icon(Icons.chevron_right),
        onTap: () => context.push(route),
      ),
    );
  }
}
