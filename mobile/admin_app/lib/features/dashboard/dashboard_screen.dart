import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../auth/auth_controller.dart';

final dashboardProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/dashboard');
  return Map<String, dynamic>.from(env.data as Map? ?? {});
});

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final async = ref.watch(dashboardProvider);
    final canRevenue = auth.user?.canViewRevenue ?? false;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Tổng quan'),
        actions: [
          IconButton(
            tooltip: 'Làm mới',
            onPressed: () => ref.invalidate(dashboardProvider),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: async.when(
        loading: () => const LoadingBody(),
        error: (e, _) => ErrorBody(
          error: e,
          onRetry: () => ref.invalidate(dashboardProvider),
        ),
        data: (data) {
          final stats = Map<String, dynamic>.from(data['stats'] as Map? ?? {});
          final canView = data['can_view_revenue'] == true || canRevenue;

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(dashboardProvider),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(
                  'Xin chào, ${auth.user?.name ?? ''}',
                  style: Theme.of(context)
                      .textTheme
                      .titleMedium
                      ?.copyWith(fontWeight: FontWeight.bold),
                ),
                Text(
                  '${auth.user?.roleLabel ?? ''} · ${auth.baseUrl ?? ''}',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                ),
                const SizedBox(height: 16),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    _StatCard(
                      title: 'Sản phẩm',
                      value: '${stats['products'] ?? stats['product_count'] ?? '—'}',
                      icon: Icons.inventory_2_outlined,
                    ),
                    _StatCard(
                      title: 'Đơn chờ',
                      value: '${stats['orders_pending'] ?? stats['order_requests'] ?? '—'}',
                      icon: Icons.shopping_bag_outlined,
                    ),
                    _StatCard(
                      title: 'Chat mở',
                      value: '${stats['open_chats'] ?? stats['chat_open'] ?? '—'}',
                      icon: Icons.chat_outlined,
                    ),
                    if (canView)
                      _StatCard(
                        title: 'Doanh thu',
                        value: formatMoney(
                          stats['revenue'] ??
                              stats['sales_revenue'] ??
                              stats['revenue_today'],
                        ),
                        icon: Icons.payments_outlined,
                        highlight: true,
                      ),
                  ],
                ),
                const SizedBox(height: 16),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Server API',
                            style: TextStyle(fontWeight: FontWeight.w600)),
                        const SizedBox(height: 6),
                        SelectableText(auth.baseUrl ?? '—'),
                        const SizedBox(height: 8),
                        Text(
                          canView
                              ? 'Bạn có quyền xem số liệu tài chính.'
                              : 'Số liệu doanh thu ẩn theo phân quyền.',
                          style: TextStyle(
                              fontSize: 13, color: Colors.grey.shade700),
                        ),
                      ],
                    ),
                  ),
                ),
                // Raw keys helper for varying backend payloads
                if (stats.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Text('Chỉ số khác',
                      style: Theme.of(context).textTheme.titleSmall),
                  const SizedBox(height: 8),
                  ...stats.entries.take(12).map((e) {
                    if (e.key.contains('revenue') ||
                        e.key.contains('price') ||
                        e.key.contains('profit')) {
                      if (!canView) return const SizedBox.shrink();
                    }
                    return ListTile(
                      dense: true,
                      contentPadding: EdgeInsets.zero,
                      title: Text(e.key),
                      trailing: Text(
                        e.value is num &&
                                (e.key.contains('revenue') ||
                                    e.key.contains('price') ||
                                    e.key.contains('amount'))
                            ? formatMoney(e.value)
                            : '${e.value}',
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                    );
                  }),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final bool highlight;

  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    this.highlight = false,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: (MediaQuery.of(context).size.width - 42) / 2,
      child: Card(
        color: highlight ? const Color(0xFF0F172A) : null,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon,
                  color: highlight ? Colors.lightBlueAccent : Colors.blueGrey),
              const SizedBox(height: 8),
              Text(
                title,
                style: TextStyle(
                  fontSize: 12,
                  color: highlight ? Colors.white70 : Colors.grey.shade600,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                value,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: highlight ? Colors.white : null,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
