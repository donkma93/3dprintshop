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

final materialsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/materials', query: {'per_page': 100});
  return parseListData(env.data);
});

class MaterialsScreen extends ConsumerWidget {
  const MaterialsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(materialsProvider);
    final canRevenue =
        ref.watch(authControllerProvider).user?.canViewRevenue ?? false;

    return ResourceListScaffold(
      title: l10n.navMaterials,
      async: async,
      onRefresh: () => ref.invalidate(materialsProvider),
      onCreate: () => context.push('/materials/form'),
      itemBuilder: (context, item, _) {
        return Card(
          child: ListTile(
            title: Text(item['name']?.toString() ?? '',
                style: const TextStyle(fontWeight: FontWeight.w600)),
            subtitle: Text(
              '${item['type'] ?? ''} ${item['color'] ?? ''} · '
              '${l10n.stock}: ${item['stock_quantity'] ?? 0} ${item['unit'] ?? ''}'
              '${canRevenue ? ' · ${formatMoney(item['unit_price'])}' : ''}',
            ),
            trailing: PopupMenuButton<String>(
              onSelected: (v) async {
                if (v == 'edit') {
                  context.push('/materials/form', extra: item);
                } else if (v == 'delete') {
                  final ok =
                      await confirmAction(context, title: l10n.confirmDelete);
                  if (!ok) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('/admin/materials/${item['id']}');
                    ref.invalidate(materialsProvider);
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
                PopupMenuItem(value: 'delete', child: Text(l10n.delete)),
              ],
            ),
            onTap: () => context.push('/materials/form', extra: item),
          ),
        );
      },
    );
  }
}

class MaterialFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const MaterialFormScreen({super.key, this.item});

  @override
  ConsumerState<MaterialFormScreen> createState() => _MaterialFormScreenState();
}

class _MaterialFormScreenState extends ConsumerState<MaterialFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _type;
  late final TextEditingController _color;
  late final TextEditingController _brand;
  late final TextEditingController _unit;
  late final TextEditingController _stock;
  late final TextEditingController _price;
  late final TextEditingController _minStock;
  late final TextEditingController _notes;
  late bool _active;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final i = widget.item;
    _name = TextEditingController(text: i?['name']?.toString() ?? '');
    _type = TextEditingController(text: i?['type']?.toString() ?? '');
    _color = TextEditingController(text: i?['color']?.toString() ?? '');
    _brand = TextEditingController(text: i?['brand']?.toString() ?? '');
    _unit = TextEditingController(text: i?['unit']?.toString() ?? 'g');
    _stock =
        TextEditingController(text: '${i?['stock_quantity'] ?? 0}');
    _price = TextEditingController(text: '${i?['unit_price'] ?? 0}');
    _minStock = TextEditingController(text: '${i?['min_stock'] ?? 0}');
    _notes = TextEditingController(text: i?['notes']?.toString() ?? '');
    _active = asBool(i?['is_active'], fallback: true);
  }

  @override
  void dispose() {
    _name.dispose();
    _type.dispose();
    _color.dispose();
    _brand.dispose();
    _unit.dispose();
    _stock.dispose();
    _price.dispose();
    _minStock.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final l10n = context.l10n;
    try {
      final body = {
        'name': _name.text.trim(),
        'type': _type.text.trim().isEmpty ? null : _type.text.trim(),
        'color': _color.text.trim().isEmpty ? null : _color.text.trim(),
        'brand': _brand.text.trim().isEmpty ? null : _brand.text.trim(),
        'unit': _unit.text.trim(),
        'stock_quantity': num.tryParse(_stock.text) ?? 0,
        'unit_price': num.tryParse(_price.text) ?? 0,
        'min_stock': num.tryParse(_minStock.text) ?? 0,
        'notes': _notes.text.trim().isEmpty ? null : _notes.text.trim(),
        'is_active': _active,
      };
      final api = ref.read(apiClientProvider);
      final id = widget.item?['id'];
      if (id == null) {
        await api.post('/admin/materials', data: body);
      } else {
        await api.put('/admin/materials/$id', data: body);
      }
      ref.invalidate(materialsProvider);
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
    return FormScaffold(
      title: widget.item == null ? l10n.create : l10n.edit,
      saving: _saving,
      onSave: _save,
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
              TextFormField(
                controller: _type,
                decoration: const InputDecoration(labelText: 'Type'),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _color,
                decoration: const InputDecoration(labelText: 'Color'),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _brand,
                decoration: const InputDecoration(labelText: 'Brand'),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _unit,
                decoration: InputDecoration(labelText: '${l10n.unit} *'),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? l10n.fieldRequired : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _stock,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: l10n.stock),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _price,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: l10n.unitPrice),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _minStock,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: l10n.minStock),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _notes,
                maxLines: 2,
                decoration: InputDecoration(labelText: l10n.note),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(l10n.isActive),
                value: _active,
                onChanged: (v) => setState(() => _active = v),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
