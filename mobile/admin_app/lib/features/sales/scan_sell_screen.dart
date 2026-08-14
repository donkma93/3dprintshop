import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../core/network/api_envelope.dart';
import '../../core/providers.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';

class ScanSellScreen extends ConsumerStatefulWidget {
  const ScanSellScreen({super.key});

  @override
  ConsumerState<ScanSellScreen> createState() => _ScanSellScreenState();
}

class _ScanSellScreenState extends ConsumerState<ScanSellScreen> {
  final _codeCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController(text: '1');
  final _priceCtrl = TextEditingController();
  final _noteCtrl = TextEditingController();
  final _customerName = TextEditingController();
  final _customerPhone = TextEditingController();
  final _customerAddress = TextEditingController();
  final _customerWard = TextEditingController();
  final _customerDistrict = TextEditingController();
  final _customerProvince = TextEditingController();

  Map<String, dynamic>? _product;
  bool _looking = false;
  bool _selling = false;
  bool _needsShipping = false;
  bool _showScanner = false;
  String _payment = 'cash';
  String _source = 'walk_in';
  String? _lastScanned;

  @override
  void dispose() {
    _codeCtrl.dispose();
    _qtyCtrl.dispose();
    _priceCtrl.dispose();
    _noteCtrl.dispose();
    _customerName.dispose();
    _customerPhone.dispose();
    _customerAddress.dispose();
    _customerWard.dispose();
    _customerDistrict.dispose();
    _customerProvince.dispose();
    super.dispose();
  }

  Future<void> _lookup([String? code]) async {
    final raw = (code ?? _codeCtrl.text).trim();
    if (raw.isEmpty) {
      showSnack(context, 'Nhập hoặc quét mã', error: true);
      return;
    }
    setState(() {
      _looking = true;
      _product = null;
    });
    try {
      final api = ref.read(apiClientProvider);
      final env = await api.get('/admin/sales/lookup', query: {'code': raw});
      final data = Map<String, dynamic>.from(env.data as Map);
      final product = Map<String, dynamic>.from(data['product'] as Map);
      _codeCtrl.text = raw;
      _priceCtrl.text =
          '${product['final_price'] ?? product['price'] ?? product['unit_price'] ?? ''}';
      if (!mounted) return;
      setState(() => _product = product);
      showSnack(context, 'Đã tìm: ${product['name']}');
    } catch (e) {
      if (!mounted) return;
      showSnack(context, e is ApiException ? e.message : '$e', error: true);
    } finally {
      if (mounted) setState(() => _looking = false);
    }
  }

  Future<void> _sell() async {
    if (_product == null) {
      showSnack(context, 'Chưa chọn sản phẩm', error: true);
      return;
    }
    final qty = int.tryParse(_qtyCtrl.text) ?? 0;
    if (qty < 1) {
      showSnack(context, 'Số lượng không hợp lệ', error: true);
      return;
    }
    if (_needsShipping) {
      if (_customerName.text.trim().isEmpty ||
          _customerPhone.text.trim().isEmpty ||
          _customerAddress.text.trim().isEmpty ||
          _customerProvince.text.trim().isEmpty) {
        showSnack(context, 'Giao hàng cần đủ tên, SĐT, địa chỉ, tỉnh',
            error: true);
        return;
      }
    }

    setState(() => _selling = true);
    try {
      final api = ref.read(apiClientProvider);
      final body = <String, dynamic>{
        'product_id': _product!['id'],
        'quantity': qty,
        'scan_payload': _codeCtrl.text.trim(),
        'note': _noteCtrl.text.trim().isEmpty ? null : _noteCtrl.text.trim(),
        'customer_name': _customerName.text.trim().isEmpty
            ? null
            : _customerName.text.trim(),
        'customer_phone': _customerPhone.text.trim().isEmpty
            ? null
            : _customerPhone.text.trim(),
        'customer_address': _customerAddress.text.trim().isEmpty
            ? null
            : _customerAddress.text.trim(),
        'customer_ward': _customerWard.text.trim().isEmpty
            ? null
            : _customerWard.text.trim(),
        'customer_district': _customerDistrict.text.trim().isEmpty
            ? null
            : _customerDistrict.text.trim(),
        'customer_province': _customerProvince.text.trim().isEmpty
            ? null
            : _customerProvince.text.trim(),
        'customer_source': _source,
        'needs_shipping': _needsShipping,
        'payment_method': _payment,
      };
      final price = num.tryParse(_priceCtrl.text.replaceAll(',', ''));
      if (price != null) body['unit_price'] = price;
      if (_needsShipping) {
        body['receiver_name'] = _customerName.text.trim();
        body['receiver_phone'] = _customerPhone.text.trim();
        body['receiver_address'] = _customerAddress.text.trim();
        body['receiver_ward'] = _customerWard.text.trim();
        body['receiver_district'] = _customerDistrict.text.trim();
        body['receiver_province'] = _customerProvince.text.trim();
      }

      final env = await api.post('/admin/sales/sell', data: body);
      final data = Map<String, dynamic>.from(env.data as Map);
      final sale = data['sale'] is Map
          ? Map<String, dynamic>.from(data['sale'] as Map)
          : null;
      final printData = data['print'];

      if (!mounted) return;
      showSnack(context, env.message.isNotEmpty ? env.message : 'Đã bán');

      if (printData != null && sale != null && sale['id'] != null) {
        final id = sale['id'];
        final goPrint = await showDialog<bool>(
          context: context,
          builder: (ctx) => AlertDialog(
            title: const Text('In phiếu gửi?'),
            content: const Text('Đơn có giao hàng. Mở dữ liệu phiếu gửi?'),
            actions: [
              TextButton(
                  onPressed: () => Navigator.pop(ctx, false),
                  child: const Text('Để sau')),
              FilledButton(
                  onPressed: () => Navigator.pop(ctx, true),
                  child: const Text('Mở phiếu')),
            ],
          ),
        );
        if (goPrint == true && mounted) {
          context.push('/sales/print/$id');
        }
      }

      if (!mounted) return;
      setState(() {
        _product = null;
        _qtyCtrl.text = '1';
        _noteCtrl.clear();
      });
    } catch (e) {
      if (!mounted) return;
      showSnack(context, e is ApiException ? e.message : '$e', error: true);
    } finally {
      if (mounted) setState(() => _selling = false);
    }
  }

  void _onDetect(BarcodeCapture capture) {
    final barcodes = capture.barcodes;
    if (barcodes.isEmpty) return;
    final raw = barcodes.first.rawValue;
    if (raw == null || raw.isEmpty) return;
    if (_lastScanned == raw && _looking) return;
    _lastScanned = raw;
    _lookup(raw);
    setState(() => _showScanner = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Quét / bán'),
        actions: [
          IconButton(
            tooltip: _showScanner ? 'Ẩn camera' : 'Bật camera',
            onPressed: () => setState(() => _showScanner = !_showScanner),
            icon: Icon(_showScanner ? Icons.keyboard : Icons.qr_code_scanner),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_showScanner)
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: SizedBox(
                height: 240,
                child: MobileScanner(onDetect: _onDetect),
              ),
            ),
          if (_showScanner) const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _codeCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Mã QR / SKU / payload',
                    prefixIcon: Icon(Icons.tag),
                  ),
                  onSubmitted: (_) => _lookup(),
                ),
              ),
              const SizedBox(width: 8),
              FilledButton(
                onPressed: _looking ? null : () => _lookup(),
                child: _looking
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Tìm'),
              ),
            ],
          ),
          const SizedBox(height: 16),
          if (_product != null) ...[
            Card(
              child: ListTile(
                title: Text(_product!['name']?.toString() ?? '',
                    style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text(
                  'SKU: ${_product!['sku'] ?? '—'} · Tồn: ${_product!['stock'] ?? '—'}\n'
                  'Giá: ${formatMoney(_product!['final_price'] ?? _product!['price'])}',
                ),
                isThreeLine: true,
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _qtyCtrl,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Số lượng'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: _priceCtrl,
                    keyboardType: TextInputType.number,
                    decoration:
                        const InputDecoration(labelText: 'Đơn giá (tuỳ chọn)'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(
              // ignore: deprecated_member_use
              value: _source,
              decoration: const InputDecoration(labelText: 'Nguồn KH'),
              items: const [
                DropdownMenuItem(value: 'walk_in', child: Text('Walk-in')),
                DropdownMenuItem(value: 'phone', child: Text('Điện thoại')),
                DropdownMenuItem(value: 'web_chat', child: Text('Web chat')),
                DropdownMenuItem(value: 'contact', child: Text('Liên hệ')),
                DropdownMenuItem(
                    value: 'order_request', child: Text('Đặt hàng web')),
                DropdownMenuItem(value: 'other', child: Text('Khác')),
              ],
              onChanged: (v) => setState(() => _source = v ?? 'walk_in'),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(
              // ignore: deprecated_member_use
              value: _payment,
              decoration: const InputDecoration(labelText: 'Thanh toán'),
              items: const [
                DropdownMenuItem(value: 'cash', child: Text('Tiền mặt')),
                DropdownMenuItem(value: 'transfer', child: Text('Chuyển khoản')),
                DropdownMenuItem(value: 'cod', child: Text('COD')),
              ],
              onChanged: (v) => setState(() => _payment = v ?? 'cash'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _customerName,
              decoration: const InputDecoration(labelText: 'Tên khách'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _customerPhone,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(labelText: 'SĐT khách'),
            ),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Cần giao hàng'),
              value: _needsShipping,
              onChanged: (v) => setState(() => _needsShipping = v),
            ),
            if (_needsShipping) ...[
              TextField(
                controller: _customerAddress,
                decoration:
                    const InputDecoration(labelText: 'Địa chỉ (số nhà, đường)'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _customerWard,
                decoration: const InputDecoration(labelText: 'Phường/Xã'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _customerDistrict,
                decoration: const InputDecoration(labelText: 'Quận/Huyện'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _customerProvince,
                decoration: const InputDecoration(labelText: 'Tỉnh/TP *'),
              ),
            ],
            const SizedBox(height: 10),
            TextField(
              controller: _noteCtrl,
              decoration: const InputDecoration(labelText: 'Ghi chú'),
            ),
            const SizedBox(height: 18),
            FilledButton.icon(
              onPressed: _selling ? null : _sell,
              icon: _selling
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white),
                    )
                  : const Icon(Icons.point_of_sale),
              label: Text(_selling ? 'Đang xử lý…' : 'Xác nhận bán'),
            ),
          ] else
            const EmptyBody(
              message: 'Quét QR hoặc nhập mã rồi bấm Tìm',
              icon: Icons.qr_code_2,
            ),
        ],
      ),
    );
  }
}
