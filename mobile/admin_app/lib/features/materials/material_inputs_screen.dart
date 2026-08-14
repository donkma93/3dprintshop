import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/network/api_envelope.dart';
import '../../core/utils/api_list.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../../core/widgets/resource_scaffold.dart';
import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';
import 'materials_screen.dart';

final materialInputsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env =
      await api.get('/admin/material-inputs', query: {'per_page': 50});
  return parseListData(env.data);
});

class MaterialInputsScreen extends ConsumerWidget {
  const MaterialInputsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(materialInputsProvider);

    return ResourceListScaffold(
      title: l10n.navMaterialInputs,
      async: async,
      onRefresh: () => ref.invalidate(materialInputsProvider),
      onCreate: () => context.push('/material-inputs/form'),
      itemBuilder: (context, item, _) {
        final mat = item['material'] is Map
            ? Map<String, dynamic>.from(item['material'] as Map)
            : null;
        return Card(
          child: ListTile(
            title: Text(
              mat?['name']?.toString() ??
                  item['material_name']?.toString() ??
                  '#${item['id']}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            subtitle: Text(
              '${item['input_date'] ?? ''} · '
              '${l10n.quantity}: ${item['quantity']} · '
              '${formatMoney(item['total_price'] ?? item['unit_price'])}',
            ),
            trailing: PopupMenuButton<String>(
              onSelected: (v) async {
                if (v == 'edit') {
                  context.push('/material-inputs/form', extra: item);
                } else if (v == 'delete') {
                  final ok =
                      await confirmAction(context, title: l10n.confirmDelete);
                  if (!ok) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('/admin/material-inputs/${item['id']}');
                    ref.invalidate(materialInputsProvider);
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
            onTap: () => context.push('/material-inputs/form', extra: item),
          ),
        );
      },
    );
  }
}

class MaterialInputFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const MaterialInputFormScreen({super.key, this.item});

  @override
  ConsumerState<MaterialInputFormScreen> createState() =>
      _MaterialInputFormScreenState();
}

class _MaterialInputFormScreenState
    extends ConsumerState<MaterialInputFormScreen> {
  final _formKey = GlobalKey<FormState>();
  int? _materialId;
  late final TextEditingController _qty;
  late final TextEditingController _price;
  late final TextEditingController _supplier;
  late final TextEditingController _notes;
  late DateTime _date;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final i = widget.item;
    _materialId = (i?['material_id'] as num?)?.toInt() ??
        (i?['material'] is Map
            ? (i!['material']['id'] as num?)?.toInt()
            : null);
    _qty = TextEditingController(text: '${i?['quantity'] ?? ''}');
    _price = TextEditingController(text: '${i?['unit_price'] ?? ''}');
    _supplier = TextEditingController(text: i?['supplier']?.toString() ?? '');
    _notes = TextEditingController(text: i?['notes']?.toString() ?? '');
    _date = DateTime.tryParse(i?['input_date']?.toString() ?? '') ??
        DateTime.now();
  }

  @override
  void dispose() {
    _qty.dispose();
    _price.dispose();
    _supplier.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    if (_materialId == null) return;
    setState(() => _saving = true);
    final l10n = context.l10n;
    try {
      final body = {
        'material_id': _materialId,
        'input_date': DateFormat('yyyy-MM-dd').format(_date),
        'quantity': num.parse(_qty.text.trim()),
        'unit_price': num.parse(_price.text.trim()),
        'supplier':
            _supplier.text.trim().isEmpty ? null : _supplier.text.trim(),
        'notes': _notes.text.trim().isEmpty ? null : _notes.text.trim(),
      };
      final api = ref.read(apiClientProvider);
      final id = widget.item?['id'];
      if (id == null) {
        await api.post('/admin/material-inputs', data: body);
      } else {
        await api.put('/admin/material-inputs/$id', data: body);
      }
      ref.invalidate(materialInputsProvider);
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
    final materials = ref.watch(materialsProvider);

    return FormScaffold(
      title: widget.item == null ? l10n.create : l10n.edit,
      saving: _saving,
      onSave: _save,
      children: [
        Form(
          key: _formKey,
          child: Column(
            children: [
              materials.when(
                loading: () => const LinearProgressIndicator(),
                error: (e, _) => Text('$e'),
                data: (list) {
                  return DropdownButtonFormField<int>(
                    // ignore: deprecated_member_use
                    value: _materialId,
                    decoration: InputDecoration(labelText: '${l10n.material} *'),
                    items: list
                        .map((m) => DropdownMenuItem(
                              value: (m['id'] as num).toInt(),
                              child: Text(m['name']?.toString() ?? ''),
                            ))
                        .toList(),
                    onChanged: (v) => setState(() => _materialId = v),
                    validator: (v) => v == null ? l10n.fieldRequired : null,
                  );
                },
              ),
              const SizedBox(height: 12),
              ListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(l10n.date),
                subtitle: Text(DateFormat('yyyy-MM-dd').format(_date)),
                trailing: const Icon(Icons.calendar_today),
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: _date,
                    firstDate: DateTime(2020),
                    lastDate: DateTime.now().add(const Duration(days: 365)),
                  );
                  if (picked != null) setState(() => _date = picked);
                },
              ),
              TextFormField(
                controller: _qty,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: '${l10n.quantity} *'),
                validator: (v) =>
                    num.tryParse(v ?? '') == null ? l10n.fieldRequired : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _price,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: '${l10n.unitPrice} *'),
                validator: (v) =>
                    num.tryParse(v ?? '') == null ? l10n.fieldRequired : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _supplier,
                decoration: InputDecoration(labelText: l10n.supplier),
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
