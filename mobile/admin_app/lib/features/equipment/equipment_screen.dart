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

final equipmentProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/equipment', query: {'per_page': 100});
  return parseListData(env.data);
});

const _statuses = ['active', 'maintenance', 'retired', 'broken'];

class EquipmentScreen extends ConsumerWidget {
  const EquipmentScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(equipmentProvider);

    return ResourceListScaffold(
      title: l10n.navEquipment,
      async: async,
      onRefresh: () => ref.invalidate(equipmentProvider),
      onCreate: () => context.push('/equipment/form'),
      itemBuilder: (context, item, _) {
        return Card(
          child: ListTile(
            title: Text(item['name']?.toString() ?? '',
                style: const TextStyle(fontWeight: FontWeight.w600)),
            subtitle: Text(
              '${item['brand'] ?? ''} ${item['model'] ?? ''} · '
              '${item['status'] ?? ''} · ${formatMoney(item['purchase_price'])}',
            ),
            trailing: PopupMenuButton<String>(
              onSelected: (v) async {
                if (v == 'edit') {
                  context.push('/equipment/form', extra: item);
                } else if (v == 'delete') {
                  final ok =
                      await confirmAction(context, title: l10n.confirmDelete);
                  if (!ok) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('/admin/equipment/${item['id']}');
                    ref.invalidate(equipmentProvider);
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
            onTap: () => context.push('/equipment/form', extra: item),
          ),
        );
      },
    );
  }
}

class EquipmentFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const EquipmentFormScreen({super.key, this.item});

  @override
  ConsumerState<EquipmentFormScreen> createState() =>
      _EquipmentFormScreenState();
}

class _EquipmentFormScreenState extends ConsumerState<EquipmentFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _type;
  late final TextEditingController _brand;
  late final TextEditingController _model;
  late final TextEditingController _serial;
  late final TextEditingController _price;
  late final TextEditingController _supplier;
  late final TextEditingController _notes;
  String _status = 'active';
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final i = widget.item;
    _name = TextEditingController(text: i?['name']?.toString() ?? '');
    _type = TextEditingController(text: i?['type']?.toString() ?? '');
    _brand = TextEditingController(text: i?['brand']?.toString() ?? '');
    _model = TextEditingController(text: i?['model']?.toString() ?? '');
    _serial =
        TextEditingController(text: i?['serial_number']?.toString() ?? '');
    _price = TextEditingController(text: '${i?['purchase_price'] ?? 0}');
    _supplier = TextEditingController(text: i?['supplier']?.toString() ?? '');
    _notes = TextEditingController(text: i?['notes']?.toString() ?? '');
    _status = i?['status']?.toString() ?? 'active';
    if (!_statuses.contains(_status)) _status = 'active';
  }

  @override
  void dispose() {
    _name.dispose();
    _type.dispose();
    _brand.dispose();
    _model.dispose();
    _serial.dispose();
    _price.dispose();
    _supplier.dispose();
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
        'brand': _brand.text.trim().isEmpty ? null : _brand.text.trim(),
        'model': _model.text.trim().isEmpty ? null : _model.text.trim(),
        'serial_number':
            _serial.text.trim().isEmpty ? null : _serial.text.trim(),
        'purchase_price': num.tryParse(_price.text) ?? 0,
        'supplier':
            _supplier.text.trim().isEmpty ? null : _supplier.text.trim(),
        'status': _status,
        'notes': _notes.text.trim().isEmpty ? null : _notes.text.trim(),
      };
      final api = ref.read(apiClientProvider);
      final id = widget.item?['id'];
      if (id == null) {
        await api.post('/admin/equipment', data: body);
      } else {
        await api.put('/admin/equipment/$id', data: body);
      }
      ref.invalidate(equipmentProvider);
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
                  decoration: const InputDecoration(labelText: 'Type')),
              const SizedBox(height: 12),
              TextFormField(
                  controller: _brand,
                  decoration: const InputDecoration(labelText: 'Brand')),
              const SizedBox(height: 12),
              TextFormField(
                  controller: _model,
                  decoration: const InputDecoration(labelText: 'Model')),
              const SizedBox(height: 12),
              TextFormField(
                  controller: _serial,
                  decoration: const InputDecoration(labelText: 'Serial')),
              const SizedBox(height: 12),
              TextFormField(
                controller: _price,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: l10n.price),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _supplier,
                decoration: InputDecoration(labelText: l10n.supplier),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                // ignore: deprecated_member_use
                value: _status,
                decoration: InputDecoration(labelText: l10n.status),
                items: _statuses
                    .map((s) => DropdownMenuItem(value: s, child: Text(s)))
                    .toList(),
                onChanged: (v) => setState(() => _status = v ?? 'active'),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _notes,
                maxLines: 2,
                decoration: InputDecoration(labelText: l10n.note),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
