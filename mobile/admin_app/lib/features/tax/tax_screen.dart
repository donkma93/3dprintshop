import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_envelope.dart';
import '../../core/utils/api_list.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../../core/widgets/resource_scaffold.dart';
import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';

final taxSummaryProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/tax/summary');
  return asMap(env.data);
});

final taxLedgerProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/tax/ledger', query: {'per_page': 50});
  return parseListData(env.data);
});

final taxProfileProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/tax/profile');
  return asMap(env.data);
});

final taxPeriodsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/tax/periods');
  return parseListData(env.data);
});

class TaxScreen extends ConsumerWidget {
  const TaxScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(taxSummaryProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.navTaxOverview),
        actions: [
          IconButton(
            tooltip: l10n.taxSync,
            onPressed: () async {
              try {
                final env =
                    await ref.read(apiClientProvider).post('/admin/tax/sync');
                ref.invalidate(taxSummaryProvider);
                if (context.mounted) {
                  showSnack(
                    context,
                    env.message.isNotEmpty ? env.message : l10n.saved,
                  );
                }
              } catch (e) {
                if (context.mounted) {
                  showSnack(context, e is ApiException ? e.message : '$e',
                      error: true);
                }
              }
            },
            icon: const Icon(Icons.sync),
          ),
          IconButton(
            tooltip: l10n.refresh,
            onPressed: () => ref.invalidate(taxSummaryProvider),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: async.when(
        loading: () => LoadingBody(message: l10n.loading),
        error: (e, _) => ErrorBody(
          error: e,
          onRetry: () => ref.invalidate(taxSummaryProvider),
        ),
        data: (data) {
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(taxSummaryProvider),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    ActionChip(
                      label: Text(l10n.navTaxLedger),
                      onPressed: () => context.push('/tax/ledger'),
                    ),
                    ActionChip(
                      label: Text(l10n.navTaxReport),
                      onPressed: () => context.push('/tax/report'),
                    ),
                    ActionChip(
                      label: Text(l10n.navTaxProfile),
                      onPressed: () => context.push('/tax/profile'),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                ...data.entries.map((e) {
                  final key = e.key;
                  final val = e.value;
                  String display;
                  if (val is num ||
                      key.contains('amount') ||
                      key.contains('revenue') ||
                      key.contains('tax')) {
                    display = formatMoney(val);
                  } else if (val is Map || val is List) {
                    display = val.toString();
                  } else {
                    display = '$val';
                  }
                  return Card(
                    child: ListTile(
                      title: Text(key),
                      trailing: ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 180),
                        child: Text(
                          display,
                          textAlign: TextAlign.end,
                          style: const TextStyle(fontWeight: FontWeight.w600),
                        ),
                      ),
                    ),
                  );
                }),
              ],
            ),
          );
        },
      ),
    );
  }
}

class TaxLedgerScreen extends ConsumerWidget {
  const TaxLedgerScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(taxLedgerProvider);
    return ResourceListScaffold(
      title: l10n.navTaxLedger,
      async: async,
      onRefresh: () => ref.invalidate(taxLedgerProvider),
      itemBuilder: (context, item, _) {
        return Card(
          child: ListTile(
            title: Text(
              item['description']?.toString() ??
                  item['source']?.toString() ??
                  '#${item['id']}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            subtitle: Text(
              '${item['entry_date'] ?? item['date'] ?? ''} · '
              '${item['type'] ?? ''} · ${formatMoney(item['amount'] ?? item['revenue'])}',
            ),
          ),
        );
      },
    );
  }
}

class TaxReportScreen extends ConsumerWidget {
  const TaxReportScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(taxPeriodsProvider);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.navTaxReport)),
      body: async.when(
        loading: () => LoadingBody(message: l10n.loading),
        error: (e, _) => ErrorBody(
          error: e,
          onRetry: () => ref.invalidate(taxPeriodsProvider),
        ),
        data: (items) {
          if (items.isEmpty) return EmptyBody(message: l10n.empty);
          return ListView.separated(
            padding: const EdgeInsets.all(12),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (context, i) {
              final p = items[i];
              return Card(
                child: ListTile(
                  title: Text(
                    '${p['label'] ?? p['period'] ?? p['year_month'] ?? p['id']}',
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text(
                    '${p['status'] ?? ''} · ${formatMoney(p['total_revenue'] ?? p['revenue'])}',
                  ),
                  trailing: PopupMenuButton<String>(
                    onSelected: (v) async {
                      try {
                        final api = ref.read(apiClientProvider);
                        final body = {
                          'period_id': p['id'],
                          'year_month': p['year_month'] ?? p['period'],
                        };
                        if (v == 'close') {
                          await api.post('/admin/tax/period/close', data: body);
                        } else if (v == 'reopen') {
                          await api.post('/admin/tax/period/reopen',
                              data: body);
                        } else if (v == 'paid') {
                          await api.post('/admin/tax/period/paid', data: body);
                        }
                        ref.invalidate(taxPeriodsProvider);
                        if (context.mounted) showSnack(context, l10n.saved);
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
                    itemBuilder: (_) => [
                      PopupMenuItem(
                          value: 'close', child: Text(l10n.closePeriod)),
                      PopupMenuItem(
                          value: 'reopen', child: Text(l10n.reopenPeriod)),
                      PopupMenuItem(
                          value: 'paid', child: Text(l10n.markPaid)),
                    ],
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class TaxProfileScreen extends ConsumerStatefulWidget {
  const TaxProfileScreen({super.key});

  @override
  ConsumerState<TaxProfileScreen> createState() => _TaxProfileScreenState();
}

class _TaxProfileScreenState extends ConsumerState<TaxProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _ctrls = {};
  bool _ready = false;
  bool _saving = false;

  TextEditingController _c(String k) =>
      _ctrls.putIfAbsent(k, () => TextEditingController());

  @override
  void dispose() {
    for (final c in _ctrls.values) {
      c.dispose();
    }
    super.dispose();
  }

  void _hydrate(Map<String, dynamic> data) {
    if (_ready) return;
    for (final e in data.entries) {
      if (e.value is Map || e.value is List) continue;
      _c(e.key).text = e.value?.toString() ?? '';
    }
    // common fields
    for (final k in [
      'business_name',
      'tax_code',
      'owner_name',
      'address',
      'phone',
      'email',
      'business_type',
      'notes',
    ]) {
      _c(k);
      if (data[k] != null) _c(k).text = data[k].toString();
    }
    _ready = true;
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    final l10n = context.l10n;
    try {
      final body = {
        for (final e in _ctrls.entries)
          if (e.value.text.trim().isNotEmpty) e.key: e.value.text.trim(),
      };
      await ref.read(apiClientProvider).put('/admin/tax/profile', data: body);
      ref.invalidate(taxProfileProvider);
      if (!mounted) return;
      showSnack(context, l10n.saved);
    } catch (e) {
      if (!mounted) return;
      showSnack(context, e is ApiException ? e.message : '$e', error: true);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final async = ref.watch(taxProfileProvider);

    return async.when(
      loading: () => Scaffold(
        appBar: AppBar(title: Text(l10n.navTaxProfile)),
        body: LoadingBody(message: l10n.loading),
      ),
      error: (e, _) => Scaffold(
        appBar: AppBar(title: Text(l10n.navTaxProfile)),
        body: ErrorBody(
            error: e, onRetry: () => ref.invalidate(taxProfileProvider)),
      ),
      data: (data) {
        _hydrate(data);
        return FormScaffold(
          title: l10n.navTaxProfile,
          saving: _saving,
          onSave: _save,
          children: [
            Form(
              key: _formKey,
              child: Column(
                children: [
                  for (final k in [
                    'business_name',
                    'tax_code',
                    'owner_name',
                    'address',
                    'phone',
                    'email',
                    'business_type',
                    'notes',
                  ]) ...[
                    TextFormField(
                      controller: _c(k),
                      maxLines: k == 'notes' || k == 'address' ? 2 : 1,
                      decoration: InputDecoration(labelText: k),
                    ),
                    const SizedBox(height: 12),
                  ],
                ],
              ),
            ),
          ],
        );
      },
    );
  }
}
