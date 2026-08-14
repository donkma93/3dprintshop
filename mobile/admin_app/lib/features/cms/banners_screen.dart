import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_envelope.dart';
import '../../core/utils/api_list.dart';
import '../../core/widgets/async_body.dart';
import '../../core/widgets/resource_scaffold.dart';
import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';

final bannersProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/banners', query: {'per_page': 50});
  return parseListData(env.data);
});

class BannersScreen extends ConsumerWidget {
  const BannersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(bannersProvider);
    return ResourceListScaffold(
      title: l10n.navBanners,
      async: async,
      onRefresh: () => ref.invalidate(bannersProvider),
      onCreate: () => context.push('/banners/form'),
      itemBuilder: (context, item, _) {
        final active = asBool(item['is_active'], fallback: true);
        return Card(
          child: ListTile(
            title: Text(item['title']?.toString() ?? '',
                style: const TextStyle(fontWeight: FontWeight.w600)),
            subtitle: Text(
              '${item['position'] ?? ''} · ${item['subtitle'] ?? ''} · '
              '${active ? l10n.active : l10n.inactive}',
            ),
            trailing: PopupMenuButton<String>(
              onSelected: (v) async {
                if (v == 'edit') {
                  context.push('/banners/form', extra: item);
                } else if (v == 'delete') {
                  final ok =
                      await confirmAction(context, title: l10n.confirmDelete);
                  if (!ok) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('/admin/banners/${item['id']}');
                    ref.invalidate(bannersProvider);
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
            onTap: () => context.push('/banners/form', extra: item),
          ),
        );
      },
    );
  }
}

class BannerFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const BannerFormScreen({super.key, this.item});

  @override
  ConsumerState<BannerFormScreen> createState() => _BannerFormScreenState();
}

class _BannerFormScreenState extends ConsumerState<BannerFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _subtitle;
  late final TextEditingController _link;
  late final TextEditingController _button;
  late final TextEditingController _sort;
  String _position = 'home';
  late bool _active;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final i = widget.item;
    _title = TextEditingController(text: i?['title']?.toString() ?? '');
    _subtitle = TextEditingController(text: i?['subtitle']?.toString() ?? '');
    _link = TextEditingController(text: i?['link']?.toString() ?? '');
    _button = TextEditingController(text: i?['button_text']?.toString() ?? '');
    _sort = TextEditingController(text: '${i?['sort_order'] ?? 0}');
    _position = i?['position']?.toString() ?? 'home';
    _active = asBool(i?['is_active'], fallback: true);
  }

  @override
  void dispose() {
    _title.dispose();
    _subtitle.dispose();
    _link.dispose();
    _button.dispose();
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
        'subtitle':
            _subtitle.text.trim().isEmpty ? null : _subtitle.text.trim(),
        'link': _link.text.trim().isEmpty ? null : _link.text.trim(),
        'button_text':
            _button.text.trim().isEmpty ? null : _button.text.trim(),
        'position': _position,
        'sort_order': int.tryParse(_sort.text) ?? 0,
        'is_active': _active,
      };
      final api = ref.read(apiClientProvider);
      final id = widget.item?['id'];
      if (id == null) {
        await api.post('/admin/banners', data: body);
      } else {
        await api.put('/admin/banners/$id', data: body);
      }
      ref.invalidate(bannersProvider);
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
                controller: _subtitle,
                decoration: InputDecoration(labelText: l10n.excerpt),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _link,
                decoration: InputDecoration(labelText: l10n.linkUrl),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _button,
                decoration: InputDecoration(labelText: l10n.buttonText),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                // ignore: deprecated_member_use
                value: _position,
                decoration: const InputDecoration(labelText: 'Position'),
                items: const [
                  DropdownMenuItem(value: 'home', child: Text('home')),
                  DropdownMenuItem(value: 'shop', child: Text('shop')),
                  DropdownMenuItem(value: 'promo', child: Text('promo')),
                ],
                onChanged: (v) => setState(() => _position = v ?? 'home'),
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
              Text(l10n.pickImageOptional,
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
            ],
          ),
        ),
      ],
    );
  }
}
