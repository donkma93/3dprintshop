import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../core/network/api_envelope.dart';
import '../../core/providers.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../../l10n/app_localizations.dart';

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
    final l10n = context.l10n;
    if (_product == null) {
      showSnack(context, l10n.noProductSelected, error: true);
      return;
    }
    final qty = int.tryParse(_qtyCtrl.text) ?? 0;
    if (qty < 1) {
      showSnack(context, l10n.invalidQty, error: true);
      return;
    }
    if (_needsShipping) {
      if (_customerName.text.trim().isEmpty ||
          _customerPhone.text.trim().isEmpty ||
          _customerAddress.text.trim().isEmpty ||
          _customerProvince.text.trim().isEmpty) {
        showSnack(context, l10n.shippingRequired, error: true);
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
      showSnack(
          context, env.message.isNotEmpty ? env.message : l10n.sellSuccess);

      if (printData != null && sale != null && sale['id'] != null) {
        final id = sale['id'];
        final goPrint = await showDialog<bool>(
          context: context,
          builder: (ctx) => AlertDialog(
            title: Text(l10n.printSlip),
            actions: [
              TextButton(
                  onPressed: () => Navigator.pop(ctx, false),
                  child: Text(l10n.later)),
              FilledButton(
                  onPressed: () => Navigator.pop(ctx, true),
                  child: Text(l10n.openPrint)),
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

  bool get _scannerSupported =>
      !kIsWeb &&
      (defaultTargetPlatform == TargetPlatform.android ||
          defaultTargetPlatform == TargetPlatform.iOS);

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.navSalesScan),
        actions: [
          if (_scannerSupported)
            IconButton(
              tooltip: l10n.scanQr,
              onPressed: () => setState(() => _showScanner = !_showScanner),
              icon: Icon(_showScanner ? Icons.keyboard : Icons.qr_code_scanner),
            ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (!_scannerSupported)
            Card(
              color: Colors.amber.shade50,
              child: ListTile(
                leading: const Icon(Icons.info_outline),
                title: Text(l10n.cameraUnavailable),
              ),
            ),
          if (_showScanner && _scannerSupported)
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: SizedBox(
                height: 240,
                child: MobileScanner(onDetect: _onDetect),
              ),
            ),
          if (_showScanner && _scannerSupported) const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _codeCtrl,
                  decoration: InputDecoration(
                    labelText: l10n.enterCode,
                    prefixIcon: const Icon(Icons.tag),
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
                    : Text(l10n.lookup),
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
                  'SKU: ${_product!['sku'] ?? '—'} · ${l10n.stock}: ${_product!['stock'] ?? '—'}\n'
                  '${l10n.price}: ${formatMoney(_product!['final_price'] ?? _product!['price'])}',
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
                    decoration: InputDecoration(labelText: l10n.quantity),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: _priceCtrl,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(labelText: l10n.unitPrice),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(
              // ignore: deprecated_member_use
              value: _source,
              decoration: const InputDecoration(labelText: 'Source'),
              items: const [
                DropdownMenuItem(value: 'walk_in', child: Text('Walk-in')),
                DropdownMenuItem(value: 'phone', child: Text('Phone')),
                DropdownMenuItem(value: 'web_chat', child: Text('Web chat')),
                DropdownMenuItem(value: 'contact', child: Text('Contact')),
                DropdownMenuItem(
                    value: 'order_request', child: Text('Order request')),
                DropdownMenuItem(value: 'other', child: Text('Other')),
              ],
              onChanged: (v) => setState(() => _source = v ?? 'walk_in'),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(
              // ignore: deprecated_member_use
              value: _payment,
              decoration: InputDecoration(labelText: l10n.paymentMethod),
              items: [
                DropdownMenuItem(value: 'cash', child: Text(l10n.cash)),
                DropdownMenuItem(value: 'transfer', child: Text(l10n.transfer)),
                const DropdownMenuItem(value: 'cod', child: Text('COD')),
              ],
              onChanged: (v) => setState(() => _payment = v ?? 'cash'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _customerName,
              decoration: InputDecoration(labelText: l10n.customerName),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _customerPhone,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(labelText: l10n.customerPhone),
            ),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(l10n.needsShipping),
              value: _needsShipping,
              onChanged: (v) => setState(() => _needsShipping = v),
            ),
            if (_needsShipping) ...[
              TextField(
                controller: _customerAddress,
                decoration: InputDecoration(labelText: l10n.customerAddress),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _customerWard,
                decoration: InputDecoration(labelText: l10n.ward),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _customerDistrict,
                decoration: InputDecoration(labelText: l10n.district),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _customerProvince,
                decoration: InputDecoration(labelText: '${l10n.province} *'),
              ),
            ],
            const SizedBox(height: 10),
            TextField(
              controller: _noteCtrl,
              decoration: InputDecoration(labelText: l10n.note),
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
              label: Text(_selling ? l10n.loading : l10n.sell),
            ),
          ] else
            EmptyBody(
              message: l10n.enterCode,
              icon: Icons.qr_code_2,
            ),
        ],
      ),
    );
  }
}
