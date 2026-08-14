import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_envelope.dart';
import '../../core/utils/api_list.dart';
import '../../core/widgets/async_body.dart';
import '../../core/widgets/resource_scaffold.dart';
import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';

final pagesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/pages', query: {'per_page': 50});
  return parseListData(env.data);
});

class PagesScreen extends ConsumerWidget {
  const PagesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(pagesProvider);
    return ResourceListScaffold(
      title: l10n.navPages,
      async: async,
      onRefresh: () => ref.invalidate(pagesProvider),
      onCreate: () => context.push('/pages/form'),
      itemBuilder: (context, item, _) {
        final pub = asBool(item['is_published']);
        return Card(
          child: ListTile(
            title: Text(item['title']?.toString() ?? '',
                style: const TextStyle(fontWeight: FontWeight.w600)),
            subtitle: Text(
              '${item['slug'] ?? ''} · ${pub ? l10n.published : l10n.draft}',
            ),
            trailing: PopupMenuButton<String>(
              onSelected: (v) async {
                if (v == 'edit') {
                  context.push('/pages/form', extra: item);
                } else if (v == 'delete') {
                  final ok =
                      await confirmAction(context, title: l10n.confirmDelete);
                  if (!ok) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('/admin/pages/${item['id']}');
                    ref.invalidate(pagesProvider);
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
            onTap: () => context.push('/pages/form', extra: item),
          ),
        );
      },
    );
  }
}

class PageFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const PageFormScreen({super.key, this.item});

  @override
  ConsumerState<PageFormScreen> createState() => _PageFormScreenState();
}

class _PageFormScreenState extends ConsumerState<PageFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _slug;
  late final TextEditingController _content;
  late final TextEditingController _sort;
  late bool _published;
  late bool _showInMenu;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final i = widget.item;
    _title = TextEditingController(text: i?['title']?.toString() ?? '');
    _slug = TextEditingController(text: i?['slug']?.toString() ?? '');
    _content = TextEditingController(text: i?['content']?.toString() ?? '');
    _sort = TextEditingController(text: '${i?['sort_order'] ?? 0}');
    _published = asBool(i?['is_published']);
    _showInMenu = asBool(i?['show_in_menu']);
  }

  @override
  void dispose() {
    _title.dispose();
    _slug.dispose();
    _content.dispose();
    _sort.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final l10n = context.l10n;
    try {
      final body = {
        'title': _title.text.trim(),
        'slug': _slug.text.trim().isEmpty ? null : _slug.text.trim(),
        'content':
            _content.text.trim().isEmpty ? null : _content.text.trim(),
        'is_published': _published,
        'show_in_menu': _showInMenu,
        'sort_order': int.tryParse(_sort.text) ?? 0,
      };
      final api = ref.read(apiClientProvider);
      final id = widget.item?['id'];
      if (id == null) {
        await api.post('/admin/pages', data: body);
      } else {
        await api.put('/admin/pages/$id', data: body);
      }
      ref.invalidate(pagesProvider);
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
                controller: _title,
                decoration: InputDecoration(labelText: '${l10n.title} *'),
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
                controller: _content,
                maxLines: 10,
                decoration: InputDecoration(labelText: l10n.body),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _sort,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: l10n.sortOrder),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(l10n.published),
                value: _published,
                onChanged: (v) => setState(() => _published = v),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(l10n.menu),
                value: _showInMenu,
                onChanged: (v) => setState(() => _showInMenu = v),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
