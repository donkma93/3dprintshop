import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_envelope.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../auth/auth_controller.dart';

final taxSummaryProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/tax/summary');
  return Map<String, dynamic>.from(env.data as Map? ?? {});
});

class TaxScreen extends ConsumerWidget {
  const TaxScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).user;
    if (user == null || !user.can('tax.manage')) {
      return Scaffold(
        appBar: AppBar(title: const Text('Thuế HKD')),
        body: const EmptyBody(
          message: 'Không có quyền tax.manage',
          icon: Icons.lock_outline,
        ),
      );
    }

    final async = ref.watch(taxSummaryProvider);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Chuẩn bị thuế'),
        actions: [
          IconButton(
            tooltip: 'Đồng bộ bán → sổ',
            onPressed: () async {
              try {
                final api = ref.read(apiClientProvider);
                final env = await api.post('/admin/tax/sync');
                ref.invalidate(taxSummaryProvider);
                if (context.mounted) {
                  showSnack(context, env.message);
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
            onPressed: () => ref.invalidate(taxSummaryProvider),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: async.when(
        loading: () => const LoadingBody(),
        error: (e, _) => ErrorBody(
          error: e,
          onRetry: () => ref.invalidate(taxSummaryProvider),
        ),
        data: (data) {
          final period = Map<String, dynamic>.from(data['period'] as Map? ?? {});
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Card(
                color: Colors.amber.shade50,
                child: const Padding(
                  padding: EdgeInsets.all(12),
                  child: Text(
                    'Module chuẩn bị nội bộ — không nộp tờ khai điện tử. '
                    'Đối chiếu CQT / văn bản hiện hành trước khi kê khai.',
                    style: TextStyle(fontSize: 13),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                period['label']?.toString() ?? period['key']?.toString() ?? 'Kỳ',
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.bold),
              ),
              Text(
                '${period['starts_on'] ?? ''} → ${period['ends_on'] ?? ''} · Hạn ${period['due_on'] ?? ''}',
                style: TextStyle(color: Colors.grey.shade700, fontSize: 13),
              ),
              const SizedBox(height: 16),
              _moneyTile('Doanh thu tính thuế', data['taxable_revenue']),
              _moneyTile('Ước GTGT', data['estimated_vat']),
              _moneyTile('Ước TNCN', data['estimated_pit']),
              _moneyTile('Tổng ước thuế', data['estimated_total'], bold: true),
              _moneyTile('Lũy kế năm (YTD)', data['ytd_revenue']),
              if (data['threshold_warning'] == true)
                Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Text(
                    'Cảnh báo: gần / vượt ngưỡng doanh thu cấu hình '
                    '(${formatMoney(data['threshold'])}).',
                    style: TextStyle(
                        color: Colors.red.shade700, fontWeight: FontWeight.w600),
                  ),
                ),
              const SizedBox(height: 12),
              Text('Dòng sổ: ${data['entry_count'] ?? 0} · '
                  'Loại trừ: ${data['excluded_count'] ?? 0} · '
                  '${data['locked'] == true ? 'Đã khóa sổ' : 'Đang mở'}'),
            ],
          );
        },
      ),
    );
  }

  Widget _moneyTile(String title, dynamic value, {bool bold = false}) {
    return Card(
      child: ListTile(
        title: Text(title),
        trailing: Text(
          formatMoney(value),
          style: TextStyle(
            fontWeight: bold ? FontWeight.bold : FontWeight.w600,
            fontSize: bold ? 16 : 14,
          ),
        ),
      ),
    );
  }
}
