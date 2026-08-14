import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_envelope.dart';
import '../../core/utils/api_list.dart';
import '../../core/widgets/async_body.dart';
import '../../core/widgets/resource_scaffold.dart';
import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';

final categoriesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/categories', query: {'per_page': 100});
  return parseListData(env.data);
});

class CategoriesScreen extends ConsumerWidget {
  const CategoriesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(categoriesProvider);
    return ResourceListScaffold(
      title: l10n.navCategories,
      async: async,
      onRefresh: () => ref.invalidate(categoriesProvider),
      onCreate: () => context.push('/categories/form'),
      itemBuilder: (context, item, _) {
        final active = asBool(item['is_active'], fallback: true);
        return Card(
          child: ListTile(
            title: Text(item['name']?.toString() ?? '',
                style: const TextStyle(fontWeight: FontWeight.w600)),
            subtitle: Text(
              'SKU: ${item['sku_prefix'] ?? '—'} · '
              '${l10n.productsCount}: ${item['products_count'] ?? item['products_count'] ?? '—'} · '
              '${active ? l10n.active : l10n.inactive}',
            ),
            trailing: PopupMenuButton<String>(
              onSelected: (v) async {
                if (v == 'edit') {
                  context.push('/categories/form', extra: item);
                } else if (v == 'delete') {
                  final ok = await confirmAction(
                    context,
                    title: l10n.confirmDelete,
                  );
                  if (!ok) return;
                  try {
                    final api = ref.read(apiClientProvider);
                    await api.delete('/admin/categories/${item['id']}');
                    ref.invalidate(categoriesProvider);
                    if (context.mounted) showSnack(context, l10n.deleted);
                  } catch (e) {
                    if (context.mounted) {
                      showSnack(
                        context,
                        e is ApiException ? e.message : '$e',
                        error: true,
                      );
                    }
                  }
                }
              },
              itemBuilder: (_) => [
                PopupMenuItem(value: 'edit', child: Text(l10n.edit)),
                PopupMenuItem(value: 'delete', child: Text(l10n.delete)),
              ],
            ),
            onTap: () => context.push('/categories/form', extra: item),
          ),
        );
      },
    );
  }
}

class CategoryFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const CategoryFormScreen({super.key, this.item});

  @override
  ConsumerState<CategoryFormScreen> createState() => _CategoryFormScreenState();
}

class _CategoryFormScreenState extends ConsumerState<CategoryFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _slug;
  late final TextEditingController _skuPrefix;
  late final TextEditingController _desc;
  late final TextEditingController _sort;
  late bool _active;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final i = widget.item;
    _name = TextEditingController(text: i?['name']?.toString() ?? '');
    _slug = TextEditingController(text: i?['slug']?.toString() ?? '');
    _skuPrefix = TextEditingController(text: i?['sku_prefix']?.toString() ?? '');
    _desc = TextEditingController(text: i?['description']?.toString() ?? '');
    _sort = TextEditingController(text: '${i?['sort_order'] ?? 0}');
    _active = asBool(i?['is_active'], fallback: true);
  }

  @override
  void dispose() {
    _name.dispose();
    _slug.dispose();
    _skuPrefix.dispose();
    _desc.dispose();
    _sort.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final l10n = context.l10n;
    try {
      final api = ref.read(apiClientProvider);
      final body = {
        'name': _name.text.trim(),
        'slug': _slug.text.trim().isEmpty ? null : _slug.text.trim(),
        'sku_prefix':
            _skuPrefix.text.trim().isEmpty ? null : _skuPrefix.text.trim(),
        'description': _desc.text.trim().isEmpty ? null : _desc.text.trim(),
        'sort_order': int.tryParse(_sort.text) ?? 0,
        'is_active': _active,
      };
      final id = widget.item?['id'];
      if (id == null) {
        await api.post('/admin/categories', data: body);
      } else {
        await api.put('/admin/categories/$id', data: body);
      }
      ref.invalidate(categoriesProvider);
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
    final isEdit = widget.item != null;
    return FormScaffold(
      title: isEdit ? l10n.edit : l10n.create,
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
                controller: _slug,
                decoration: InputDecoration(labelText: l10n.slug),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _skuPrefix,
                decoration: InputDecoration(labelText: l10n.skuPrefix),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _desc,
                maxLines: 3,
                decoration: InputDecoration(labelText: l10n.description),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _sort,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: l10n.sortOrder),
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
