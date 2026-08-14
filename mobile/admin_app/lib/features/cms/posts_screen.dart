import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_envelope.dart';
import '../../core/utils/api_list.dart';
import '../../core/widgets/async_body.dart';
import '../../core/widgets/resource_scaffold.dart';
import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';

final postsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/posts', query: {'per_page': 50});
  return parseListData(env.data);
});

class PostsScreen extends ConsumerWidget {
  const PostsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(postsProvider);
    return ResourceListScaffold(
      title: l10n.navPosts,
      async: async,
      onRefresh: () => ref.invalidate(postsProvider),
      onCreate: () => context.push('/posts/form'),
      itemBuilder: (context, item, _) {
        final pub = asBool(item['is_published']);
        return Card(
          child: ListTile(
            title: Text(item['title']?.toString() ?? '',
                style: const TextStyle(fontWeight: FontWeight.w600)),
            subtitle: Text(
              '${pub ? l10n.published : l10n.draft} · ${item['published_at'] ?? ''}',
            ),
            trailing: PopupMenuButton<String>(
              onSelected: (v) async {
                if (v == 'edit') {
                  context.push('/posts/form', extra: item);
                } else if (v == 'delete') {
                  final ok =
                      await confirmAction(context, title: l10n.confirmDelete);
                  if (!ok) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('/admin/posts/${item['id']}');
                    ref.invalidate(postsProvider);
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
            onTap: () => context.push('/posts/form', extra: item),
          ),
        );
      },
    );
  }
}

class PostFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const PostFormScreen({super.key, this.item});

  @override
  ConsumerState<PostFormScreen> createState() => _PostFormScreenState();
}

class _PostFormScreenState extends ConsumerState<PostFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _slug;
  late final TextEditingController _excerpt;
  late final TextEditingController _content;
  late bool _published;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final i = widget.item;
    _title = TextEditingController(text: i?['title']?.toString() ?? '');
    _slug = TextEditingController(text: i?['slug']?.toString() ?? '');
    _excerpt = TextEditingController(text: i?['excerpt']?.toString() ?? '');
    _content = TextEditingController(text: i?['content']?.toString() ?? '');
    _published = asBool(i?['is_published']);
  }

  @override
  void dispose() {
    _title.dispose();
    _slug.dispose();
    _excerpt.dispose();
    _content.dispose();
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
        'excerpt': _excerpt.text.trim().isEmpty ? null : _excerpt.text.trim(),
        'content':
            _content.text.trim().isEmpty ? null : _content.text.trim(),
        'is_published': _published,
      };
      final api = ref.read(apiClientProvider);
      final id = widget.item?['id'];
      if (id == null) {
        await api.post('/admin/posts', data: body);
      } else {
        await api.put('/admin/posts/$id', data: body);
      }
      ref.invalidate(postsProvider);
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
                controller: _excerpt,
                maxLines: 2,
                decoration: InputDecoration(labelText: l10n.excerpt),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _content,
                maxLines: 8,
                decoration: InputDecoration(labelText: l10n.body),
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(l10n.published),
                value: _published,
                onChanged: (v) => setState(() => _published = v),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
