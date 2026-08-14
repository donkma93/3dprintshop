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
import '../categories/categories_screen.dart';

final productsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/products', query: {'per_page': 50});
  return parseListData(env.data);
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
    final l10n = context.l10n;
    final async = ref.watch(productsProvider);
    final canRevenue =
        ref.watch(authControllerProvider).user?.canViewRevenue ?? false;

    return ResourceListScaffold(
      title: l10n.navProducts,
      async: async.whenData((items) {
        final q = _q.text.trim().toLowerCase();
        if (q.isEmpty) return items;
        return items
            .where((p) =>
                '${p['name']}'.toLowerCase().contains(q) ||
                '${p['sku']}'.toLowerCase().contains(q))
            .toList();
      }),
      searchController: _q,
      searchHint: l10n.search,
      onSearch: (_) => setState(() {}),
      onRefresh: () => ref.invalidate(productsProvider),
      onCreate: () => context.push('/products/form'),
      emptyMessage: l10n.noItems,
      itemBuilder: (context, p, _) {
        return Card(
          child: ListTile(
            title: Text(
              p['name']?.toString() ?? '',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            subtitle: Text(
              'SKU: ${p['sku'] ?? '—'} · ${l10n.stock}: ${p['stock'] ?? '—'}\n'
              '${l10n.price}: ${formatMoney(p['final_price'] ?? p['price'])}'
              '${canRevenue && p['cost_price'] != null ? ' · ${l10n.costPrice}: ${formatMoney(p['cost_price'])}' : ''}',
            ),
            isThreeLine: true,
            trailing: PopupMenuButton<String>(
              onSelected: (v) async {
                if (v == 'edit') {
                  context.push('/products/form', extra: p);
                } else if (v == 'qr') {
                  context.push('/products/qr/${p['id']}');
                } else if (v == 'delete') {
                  final ok =
                      await confirmAction(context, title: l10n.confirmDelete);
                  if (!ok) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('/admin/products/${p['id']}');
                    ref.invalidate(productsProvider);
                    if (context.mounted) showSnack(context, l10n.deleted);
                  } catch (e) {
                    if (context.mounted) {
                      showSnack(context,
                          e is ApiException ? e.message : '$e',
                          error: true);
                    }
                  }
                }
              },
              itemBuilder: (_) => [
                PopupMenuItem(value: 'edit', child: Text(l10n.edit)),
                PopupMenuItem(value: 'qr', child: Text(l10n.qrCode)),
                PopupMenuItem(value: 'delete', child: Text(l10n.delete)),
              ],
            ),
            onTap: () => context.push('/products/form', extra: p),
          ),
        );
      },
    );
  }
}

class ProductFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const ProductFormScreen({super.key, this.item});

  @override
  ConsumerState<ProductFormScreen> createState() => _ProductFormScreenState();
}

class _ProductFormScreenState extends ConsumerState<ProductFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _sku;
  late final TextEditingController _slug;
  late final TextEditingController _short;
  late final TextEditingController _desc;
  late final TextEditingController _price;
  late final TextEditingController _sale;
  late final TextEditingController _cost;
  late final TextEditingController _stock;
  int? _categoryId;
  late bool _active;
  late bool _featured;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final i = widget.item;
    _name = TextEditingController(text: i?['name']?.toString() ?? '');
    _sku = TextEditingController(text: i?['sku']?.toString() ?? '');
    _slug = TextEditingController(text: i?['slug']?.toString() ?? '');
    _short =
        TextEditingController(text: i?['short_description']?.toString() ?? '');
    _desc = TextEditingController(text: i?['description']?.toString() ?? '');
    _price = TextEditingController(text: '${i?['price'] ?? ''}');
    _sale = TextEditingController(text: '${i?['sale_price'] ?? ''}');
    _cost = TextEditingController(text: '${i?['cost_price'] ?? 0}');
    _stock = TextEditingController(text: '${i?['stock'] ?? 0}');
    _categoryId = (i?['category_id'] as num?)?.toInt() ??
        (i?['category'] is Map
            ? (i!['category']['id'] as num?)?.toInt()
            : null);
    _active = asBool(i?['is_active'], fallback: true);
    _featured = asBool(i?['is_featured']);
  }

  @override
  void dispose() {
    _name.dispose();
    _sku.dispose();
    _slug.dispose();
    _short.dispose();
    _desc.dispose();
    _price.dispose();
    _sale.dispose();
    _cost.dispose();
    _stock.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final l10n = context.l10n;
    try {
      final body = <String, dynamic>{
        'name': _name.text.trim(),
        'sku': _sku.text.trim().isEmpty ? null : _sku.text.trim(),
        'slug': _slug.text.trim().isEmpty ? null : _slug.text.trim(),
        'category_id': _categoryId,
        'short_description':
            _short.text.trim().isEmpty ? null : _short.text.trim(),
        'description':
            _desc.text.trim().isEmpty ? null : _desc.text.trim(),
        'price': num.parse(_price.text.trim()),
        'sale_price':
            _sale.text.trim().isEmpty ? null : num.tryParse(_sale.text.trim()),
        'cost_price': num.tryParse(_cost.text) ?? 0,
        'stock': int.tryParse(_stock.text) ?? 0,
        'is_active': _active,
        'is_featured': _featured,
      };
      final api = ref.read(apiClientProvider);
      final id = widget.item?['id'];
      if (id == null) {
        await api.post('/admin/products', data: body);
      } else {
        await api.put('/admin/products/$id', data: body);
      }
      ref.invalidate(productsProvider);
      if (!mounted) return;
      showSnack(context, l10n.saved);
      context.pop();
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
    final cats = ref.watch(categoriesProvider);
    final canRevenue =
        ref.watch(authControllerProvider).user?.canViewRevenue ?? false;

    return FormScaffold(
      title: widget.item == null ? l10n.create : l10n.edit,
      saving: _saving,
      onSave: _save,
      actions: [
        if (widget.item?['id'] != null)
          IconButton(
            tooltip: l10n.qrCode,
            icon: const Icon(Icons.qr_code),
            onPressed: () =>
                context.push('/products/qr/${widget.item!['id']}'),
          ),
      ],
      children: [
        Form(
          key: _formKey,
          child: Column(
            children: [
              TextFormField(
                controller: _name,
                decoration: InputDecoration(labelText: '${l10n.name} *'),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? l10n.fieldRequired : null,
              ),
              const SizedBox(height: 12),
              cats.when(
                loading: () => const LinearProgressIndicator(),
                error: (e, _) => Text('$e'),
                data: (list) => DropdownButtonFormField<int?>(
                  // ignore: deprecated_member_use
                  value: _categoryId,
                  decoration: InputDecoration(labelText: l10n.category),
                  items: [
                    DropdownMenuItem(value: null, child: Text('—')),
                    ...list.map((c) => DropdownMenuItem(
                          value: (c['id'] as num).toInt(),
                          child: Text(c['name']?.toString() ?? ''),
                        )),
                  ],
                  onChanged: (v) => setState(() => _categoryId = v),
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _sku,
                decoration: InputDecoration(labelText: l10n.sku),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _slug,
                decoration: InputDecoration(labelText: l10n.slug),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _price,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: '${l10n.price} *'),
                validator: (v) =>
                    num.tryParse(v ?? '') == null ? l10n.fieldRequired : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _sale,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Sale price'),
              ),
              if (canRevenue) ...[
                const SizedBox(height: 12),
                TextFormField(
                  controller: _cost,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(labelText: l10n.costPrice),
                ),
              ],
              const SizedBox(height: 12),
              TextFormField(
                controller: _stock,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: l10n.stock),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _short,
                maxLines: 2,
                decoration: InputDecoration(labelText: l10n.excerpt),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _desc,
                maxLines: 5,
                decoration: InputDecoration(labelText: l10n.description),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(l10n.isActive),
                value: _active,
                onChanged: (v) => setState(() => _active = v),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Featured'),
                value: _featured,
                onChanged: (v) => setState(() => _featured = v),
              ),
              Text(l10n.pickImageOptional,
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
            ],
          ),
        ),
      ],
    );
  }
}

class ProductQrScreen extends ConsumerStatefulWidget {
  final String productId;
  const ProductQrScreen({super.key, required this.productId});

  @override
  ConsumerState<ProductQrScreen> createState() => _ProductQrScreenState();
}

class _ProductQrScreenState extends ConsumerState<ProductQrScreen> {
  Map<String, dynamic>? _data;
  Object? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final env = await ref
          .read(apiClientProvider)
          .get('/admin/products/${widget.productId}/qr');
      setState(() => _data = asMap(env.data));
    } catch (e) {
      setState(() => _error = e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _regen() async {
    final l10n = context.l10n;
    try {
      await ref
          .read(apiClientProvider)
          .post('/admin/products/${widget.productId}/qr/regenerate');
      await _load();
      if (mounted) showSnack(context, l10n.saved);
    } catch (e) {
      if (mounted) {
        showSnack(context, e is ApiException ? e.message : '$e', error: true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.qrCode),
        actions: [
          IconButton(
            tooltip: l10n.regenerateQr,
            onPressed: _regen,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: _loading
          ? LoadingBody(message: l10n.loading)
          : _error != null
              ? ErrorBody(error: _error!, onRetry: _load)
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    if (_data?['qr_image_url'] != null ||
                        _data?['image_url'] != null)
                      Image.network(
                        (_data?['qr_image_url'] ?? _data?['image_url'])
                            .toString(),
                        height: 220,
                        errorBuilder: (_, __, ___) =>
                            const Icon(Icons.qr_code, size: 120),
                      )
                    else
                      const Icon(Icons.qr_code, size: 120),
                    const SizedBox(height: 16),
                    SelectableText(
                      '${_data?['payload'] ?? _data?['code'] ?? _data}',
                    ),
                  ],
                ),
    );
  }
}
