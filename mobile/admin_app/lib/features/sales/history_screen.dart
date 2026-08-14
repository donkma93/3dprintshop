import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../../l10n/app_localizations.dart';

final salesHistoryProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/sales/history', query: {'per_page': 50});
  final data = env.data;
  if (data is List) {
    return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }
  if (data is Map && data['data'] is List) {
    return (data['data'] as List)
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
  }
  // envelope may put list at top with meta
  if (data is Map) {
    // try common keys
    for (final k in ['sales', 'items', 'results']) {
      if (data[k] is List) {
        return (data[k] as List)
            .map((e) => Map<String, dynamic>.from(e as Map))
            .toList();
      }
    }
  }
  return [];
});

class SalesHistoryScreen extends ConsumerWidget {
  const SalesHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(salesHistoryProvider);
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.navSalesHistory),
        actions: [
          IconButton(
            onPressed: () => ref.invalidate(salesHistoryProvider),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: async.when(
        loading: () => const LoadingBody(),
        error: (e, _) => ErrorBody(
          error: e,
          onRetry: () => ref.invalidate(salesHistoryProvider),
        ),
        data: (items) {
          if (items.isEmpty) {
            return const EmptyBody(message: 'Chưa có giao dịch');
          }
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(salesHistoryProvider),
            child: ListView.separated(
              padding: const EdgeInsets.all(12),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final s = items[i];
                final product = s['product'] is Map
                    ? Map<String, dynamic>.from(s['product'] as Map)
                    : null;
                final id = s['id'];
                final needs = s['needs_shipping'] == true;
                return Card(
                  child: ListTile(
                    title: Text(
                      product?['name']?.toString() ??
                          s['sale_code']?.toString() ??
                          '#$id',
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                    subtitle: Text(
                      '${formatDate(s['sold_at']?.toString())}\n'
                      'SL: ${s['quantity']} · ${formatMoney(s['total_price'])}'
                      '${needs ? ' · Giao hàng' : ''}',
                    ),
                    isThreeLine: true,
                    trailing: needs
                        ? IconButton(
                            icon: const Icon(Icons.print_outlined),
                            onPressed: () => context.push('/sales/print/$id'),
                          )
                        : null,
                    onTap: () => context.push('/sales/print/$id'),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
