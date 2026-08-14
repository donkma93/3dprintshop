import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../../l10n/app_localizations.dart';

final ordersProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/orders');
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

class OrdersScreen extends ConsumerWidget {
  const OrdersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(ordersProvider);
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.navOrders),
        actions: [
          IconButton(
            onPressed: () => ref.invalidate(ordersProvider),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: async.when(
        loading: () => LoadingBody(message: l10n.loading),
        error: (e, _) =>
            ErrorBody(error: e, onRetry: () => ref.invalidate(ordersProvider)),
        data: (items) {
          if (items.isEmpty) {
            return EmptyBody(message: l10n.empty);
          }
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(ordersProvider),
            child: ListView.separated(
              padding: const EdgeInsets.all(12),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final o = items[i];
                return Card(
                  child: ListTile(
                    title: Text(
                      o['customer_name']?.toString() ?? 'Khách',
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                    subtitle: Text(
                      '${o['customer_phone'] ?? ''}\n'
                      '${o['status'] ?? o['status_label'] ?? ''} · '
                      '${formatDate(o['created_at']?.toString())}',
                    ),
                    isThreeLine: true,
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => _showDetail(context, ref, o),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }

  Future<void> _showDetail(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> o,
  ) async {
    final id = o['id'];
    final api = ref.read(apiClientProvider);
    Map<String, dynamic> detail = o;
    try {
      final env = await api.get('/admin/orders/$id');
      if (env.data is Map) {
        detail = Map<String, dynamic>.from(env.data as Map);
      }
    } catch (_) {}

    if (!context.mounted) return;
    final statusCtrl =
        TextEditingController(text: detail['status']?.toString() ?? 'new');
    final noteCtrl =
        TextEditingController(text: detail['admin_note']?.toString() ?? '');

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) {
        return Padding(
          padding: EdgeInsets.only(
            left: 16,
            right: 16,
            top: 16,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 16,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(detail['customer_name']?.toString() ?? 'Chi tiết',
                  style: const TextStyle(
                      fontWeight: FontWeight.bold, fontSize: 18)),
              Text('SĐT: ${detail['customer_phone'] ?? '—'}'),
              Text('Ghi chú KH: ${detail['note'] ?? '—'}'),
              const SizedBox(height: 12),
              TextField(
                controller: statusCtrl,
                decoration: const InputDecoration(
                  labelText: 'Trạng thái (new/processing/done/cancelled…)',
                ),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: noteCtrl,
                decoration: const InputDecoration(labelText: 'Ghi chú nội bộ'),
                maxLines: 2,
              ),
              const SizedBox(height: 12),
              FilledButton(
                onPressed: () async {
                  try {
                    await api.put('/admin/orders/$id', data: {
                      'status': statusCtrl.text.trim(),
                      'admin_note': noteCtrl.text.trim(),
                    });
                    ref.invalidate(ordersProvider);
                    if (ctx.mounted) Navigator.pop(ctx);
                    if (context.mounted) {
                      showSnack(context, 'Đã cập nhật đơn');
                    }
                  } catch (e) {
                    if (context.mounted) {
                      showSnack(context, '$e', error: true);
                    }
                  }
                },
                child: const Text('Lưu'),
              ),
            ],
          ),
        );
      },
    );
  }
}
