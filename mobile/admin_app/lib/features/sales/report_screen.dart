import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../auth/auth_controller.dart';

final salesReportProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/sales/report');
  return Map<String, dynamic>.from(env.data as Map? ?? {});
});

class SalesReportScreen extends ConsumerWidget {
  const SalesReportScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).user;
    if (user == null || !user.canViewRevenue) {
      return Scaffold(
        appBar: AppBar(title: const Text('Báo cáo')),
        body: const EmptyBody(
          message: 'Bạn không có quyền xem doanh thu (revenue.view).',
          icon: Icons.lock_outline,
        ),
      );
    }

    final async = ref.watch(salesReportProvider);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Báo cáo lãi lỗ'),
        actions: [
          IconButton(
            onPressed: () => ref.invalidate(salesReportProvider),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: async.when(
        loading: () => const LoadingBody(),
        error: (e, _) => ErrorBody(
          error: e,
          onRetry: () => ref.invalidate(salesReportProvider),
        ),
        data: (data) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              for (final e in data.entries)
                if (e.value is! List && e.value is! Map)
                  ListTile(
                    title: Text(e.key),
                    trailing: Text(
                      e.value is num ? formatMoney(e.value) : '${e.value}',
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  )
                else if (e.value is Map)
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(e.key,
                              style:
                                  const TextStyle(fontWeight: FontWeight.bold)),
                          const SizedBox(height: 8),
                          ...Map<String, dynamic>.from(e.value as Map)
                              .entries
                              .map((x) => ListTile(
                                    dense: true,
                                    contentPadding: EdgeInsets.zero,
                                    title: Text(x.key),
                                    trailing: Text(
                                      x.value is num
                                          ? formatMoney(x.value)
                                          : '${x.value}',
                                    ),
                                  )),
                        ],
                      ),
                    ),
                  ),
            ],
          );
        },
      ),
    );
  }
}
