import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../auth/auth_controller.dart';

final productsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/products', query: {'per_page': 50});
  final data = env.data;
  if (data is List) {
    return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }
  if (data is Map && data['data'] is List) {
    return (data['data'] as List)
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
  }
  return [];
});

class ProductsScreen extends ConsumerStatefulWidget {
  const ProductsScreen({super.key});

  @override
  ConsumerState<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends ConsumerState<ProductsScreen> {
  final _q = TextEditingController();

  @override
  void dispose() {
    _q.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(productsProvider);
    final canRevenue =
        ref.watch(authControllerProvider).user?.canViewRevenue ?? false;

    return Scaffold(
      appBar: AppBar(title: const Text('Sản phẩm')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: TextField(
              controller: _q,
              decoration: InputDecoration(
                hintText: 'Tìm sản phẩm…',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.refresh),
                  onPressed: () => ref.invalidate(productsProvider),
                ),
              ),
              onSubmitted: (_) => ref.invalidate(productsProvider),
            ),
          ),
          Expanded(
            child: async.when(
              loading: () => const LoadingBody(),
              error: (e, _) => ErrorBody(
                error: e,
                onRetry: () => ref.invalidate(productsProvider),
              ),
              data: (items) {
                final q = _q.text.trim().toLowerCase();
                final filtered = q.isEmpty
                    ? items
                    : items
                        .where((p) =>
                            '${p['name']}'.toLowerCase().contains(q) ||
                            '${p['sku']}'.toLowerCase().contains(q))
                        .toList();
                if (filtered.isEmpty) {
                  return const EmptyBody(message: 'Không có sản phẩm');
                }
                return RefreshIndicator(
                  onRefresh: () async => ref.invalidate(productsProvider),
                  child: ListView.separated(
                    padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                    itemCount: filtered.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final p = filtered[i];
                      return Card(
                        child: ListTile(
                          title: Text(
                            p['name']?.toString() ?? '',
                            style:
                                const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            'SKU: ${p['sku'] ?? '—'} · Tồn: ${p['stock'] ?? '—'}\n'
                            'Giá: ${formatMoney(p['final_price'] ?? p['price'])}'
                            '${canRevenue && p['cost_price'] != null ? ' · Vốn: ${formatMoney(p['cost_price'])}' : ''}',
                          ),
                          isThreeLine: true,
                          trailing: Icon(
                            p['is_active'] == false
                                ? Icons.visibility_off
                                : Icons.chevron_right,
                          ),
                        ),
                      );
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
