import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_envelope.dart';
import '../../core/utils/api_list.dart';
import '../../core/widgets/async_body.dart';
import '../../core/widgets/resource_scaffold.dart';
import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';

final trashProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/trash');
  return asMap(env.data);
});

class TrashScreen extends ConsumerWidget {
  const TrashScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(trashProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.navTrash),
        actions: [
          IconButton(
            tooltip: l10n.refresh,
            onPressed: () => ref.invalidate(trashProvider),
            icon: const Icon(Icons.refresh),
          ),
          IconButton(
            tooltip: l10n.emptyTrash,
            onPressed: () async {
              final ok = await confirmAction(
                context,
                title: l10n.emptyTrash,
                body: l10n.confirmDelete,
              );
              if (!ok) return;
              try {
                await ref.read(apiClientProvider).delete('/admin/trash');
                ref.invalidate(trashProvider);
                if (context.mounted) showSnack(context, l10n.deleted);
              } catch (e) {
                if (context.mounted) {
                  showSnack(context, e is ApiException ? e.message : '$e',
                      error: true);
                }
              }
            },
            icon: const Icon(Icons.delete_forever),
          ),
        ],
      ),
      body: async.when(
        loading: () => LoadingBody(message: l10n.loading),
        error: (e, _) => ErrorBody(
          error: e,
          onRetry: () => ref.invalidate(trashProvider),
        ),
        data: (data) {
          final items = parseListData(data['items'] ?? data);
          final total = data['total'] ?? items.length;
          if (items.isEmpty) {
            return EmptyBody(message: l10n.empty);
          }
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(trashProvider),
            child: ListView.separated(
              padding: const EdgeInsets.all(12),
              itemCount: items.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                if (i == 0) {
                  return Text(
                    '${l10n.total}: $total · ${l10n.daysLeft}: ${data['retention_days'] ?? '—'}',
                    style: TextStyle(color: Colors.grey.shade700),
                  );
                }
                final item = items[i - 1];
                return Card(
                  child: ListTile(
                    title: Text(item['name']?.toString() ?? '',
                        style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text(
                      '${item['label'] ?? item['type'] ?? ''} · '
                      '${item['meta'] ?? ''} · '
                      '${l10n.daysLeft}: ${item['days_left'] ?? '—'}',
                    ),
                    isThreeLine: true,
                    trailing: Wrap(
                      spacing: 0,
                      children: [
                        IconButton(
                          tooltip: l10n.restore,
                          icon: const Icon(Icons.restore),
                          onPressed: () async {
                            try {
                              await ref.read(apiClientProvider).post(
                                    '/admin/trash/${item['type']}/${item['id']}/restore',
                                  );
                              ref.invalidate(trashProvider);
                              if (context.mounted) {
                                showSnack(context, l10n.saved);
                              }
                            } catch (e) {
                              if (context.mounted) {
                                showSnack(
                                  context,
                                  e is ApiException ? e.message : '$e',
                                  error: true,
                                );
                              }
                            }
                          },
                        ),
                        IconButton(
                          tooltip: l10n.forceDelete,
                          icon: const Icon(Icons.delete_forever,
                              color: Colors.red),
                          onPressed: () async {
                            final ok = await confirmAction(
                              context,
                              title: l10n.forceDelete,
                            );
                            if (!ok) return;
                            try {
                              await ref.read(apiClientProvider).delete(
                                    '/admin/trash/${item['type']}/${item['id']}',
                                  );
                              ref.invalidate(trashProvider);
                              if (context.mounted) {
                                showSnack(context, l10n.deleted);
                              }
                            } catch (e) {
                              if (context.mounted) {
                                showSnack(
                                  context,
                                  e is ApiException ? e.message : '$e',
                                  error: true,
                                );
                              }
                            }
                          },
                        ),
                      ],
                    ),
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
