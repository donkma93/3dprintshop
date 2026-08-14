import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:share_plus/share_plus.dart';

import '../../core/providers.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';

final salePrintProvider =
    FutureProvider.autoDispose.family<Map<String, dynamic>, String>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/sales/$id/print');
  return Map<String, dynamic>.from(env.data as Map? ?? {});
});

class SalePrintScreen extends ConsumerWidget {
  final String saleId;
  const SalePrintScreen({super.key, required this.saleId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(salePrintProvider(saleId));
    return Scaffold(
      appBar: AppBar(
        title: Text('Phiếu gửi #$saleId'),
        actions: [
          IconButton(
            icon: const Icon(Icons.share),
            onPressed: () async {
              final data = await ref.read(salePrintProvider(saleId).future);
              final text = _formatSlip(data);
              await Share.share(text, subject: 'Phiếu gửi #$saleId');
            },
          ),
          IconButton(
            icon: const Icon(Icons.copy),
            onPressed: () async {
              final data = await ref.read(salePrintProvider(saleId).future);
              await Clipboard.setData(ClipboardData(text: _formatSlip(data)));
              if (context.mounted) {
                showSnack(context, 'Đã copy nội dung phiếu');
              }
            },
          ),
        ],
      ),
      body: async.when(
        loading: () => const LoadingBody(),
        error: (e, _) => ErrorBody(
          error: e,
          onRetry: () => ref.invalidate(salePrintProvider(saleId)),
        ),
        data: (data) {
          final text = _formatSlip(data);
          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: SelectableText(
                  text,
                  style: const TextStyle(
                    fontFamily: 'monospace',
                    fontSize: 13,
                    height: 1.35,
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  String _formatSlip(Map<String, dynamic> data) {
    final buf = StringBuffer();
    buf.writeln('=== PHIẾU GỬI HÀNG ===');
    void line(String k, dynamic v) {
      if (v == null || '$v'.isEmpty) return;
      buf.writeln('$k: $v');
    }

    // Flexible keys from API
    final sender = data['sender'] is Map
        ? Map<String, dynamic>.from(data['sender'] as Map)
        : data;
    final receiver = data['receiver'] is Map
        ? Map<String, dynamic>.from(data['receiver'] as Map)
        : data;
    final goods = data['goods'] is Map
        ? Map<String, dynamic>.from(data['goods'] as Map)
        : data;

    buf.writeln('-- NGƯỜI GỬI --');
    line('Tên', sender['name'] ?? data['sender_name']);
    line('SĐT', sender['phone'] ?? data['sender_phone']);
    line('Địa chỉ', sender['full_address'] ?? sender['address'] ?? data['sender_address']);

    buf.writeln('-- NGƯỜI NHẬN --');
    line('Tên', receiver['name'] ?? data['receiver_name']);
    line('SĐT', receiver['phone'] ?? data['receiver_phone']);
    line(
      'Địa chỉ',
      receiver['full_address'] ??
          receiver['address'] ??
          data['receiver_full_address'] ??
          data['receiver_address'],
    );

    buf.writeln('-- HÀNG / COD --');
    line('Nội dung', goods['content'] ?? data['goods_content']);
    line('SL kiện', goods['package_count'] ?? data['package_count']);
    line('Khối lượng', goods['weight'] ?? data['package_weight']);
    line('COD', formatMoney(data['cod_amount'] ?? goods['cod_amount']));
    line('Giá trị KB', formatMoney(data['declared_value'] ?? goods['declared_value']));
    line('VC', data['carrier'] ?? data['carrier_label']);
    line('DV', data['shipping_service'] ?? data['service_label']);
    line('Ghi chú', data['shipping_note']);
    line('Mã ĐH', data['sale_code'] ?? data['order_code']);
    buf.writeln('======================');
    return buf.toString();
  }
}
