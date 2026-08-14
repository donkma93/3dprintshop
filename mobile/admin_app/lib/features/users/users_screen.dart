import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_envelope.dart';
import '../../core/utils/api_list.dart';
import '../../core/widgets/async_body.dart';
import '../../core/widgets/resource_scaffold.dart';
import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';

final usersProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/users', query: {'per_page': 50});
  return parseListData(env.data);
});

final rolesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/roles');
  return parseListData(env.data);
});

class UsersScreen extends ConsumerWidget {
  const UsersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final async = ref.watch(usersProvider);
    return ResourceListScaffold(
      title: l10n.navUsers,
      async: async,
      onRefresh: () => ref.invalidate(usersProvider),
      onCreate: () => context.push('/users/form'),
      itemBuilder: (context, item, _) {
        final active = asBool(item['is_active'], fallback: true);
        return Card(
          child: ListTile(
            leading: CircleAvatar(
              child: Text((item['name']?.toString() ?? '?')
                  .characters
                  .take(1)
                  .toString()
                  .toUpperCase()),
            ),
            title: Text(item['name']?.toString() ?? '',
                style: const TextStyle(fontWeight: FontWeight.w600)),
            subtitle: Text(
              '${item['email'] ?? ''} · ${item['role'] ?? item['role_label'] ?? ''} · '
              '${active ? l10n.active : l10n.inactive}',
            ),
            isThreeLine: true,
            trailing: PopupMenuButton<String>(
              onSelected: (v) async {
                if (v == 'edit') {
                  context.push('/users/form', extra: item);
                } else if (v == 'delete') {
                  final ok =
                      await confirmAction(context, title: l10n.confirmDelete);
                  if (!ok) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('/admin/users/${item['id']}');
                    ref.invalidate(usersProvider);
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
            onTap: () => context.push('/users/form', extra: item),
          ),
        );
      },
    );
  }
}

class UserFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const UserFormScreen({super.key, this.item});

  @override
  ConsumerState<UserFormScreen> createState() => _UserFormScreenState();
}

class _UserFormScreenState extends ConsumerState<UserFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _email;
  late final TextEditingController _password;
  late final TextEditingController _passwordConfirm;
  String? _role;
  late bool _active;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final i = widget.item;
    _name = TextEditingController(text: i?['name']?.toString() ?? '');
    _email = TextEditingController(text: i?['email']?.toString() ?? '');
    _password = TextEditingController();
    _passwordConfirm = TextEditingController();
    _role = i?['role']?.toString();
    _active = asBool(i?['is_active'], fallback: true);
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _passwordConfirm.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final l10n = context.l10n;
    try {
      final body = <String, dynamic>{
        'name': _name.text.trim(),
        'email': _email.text.trim(),
        'role': _role,
        'is_active': _active,
      };
      if (_password.text.isNotEmpty) {
        body['password'] = _password.text;
        body['password_confirmation'] = _passwordConfirm.text;
      } else if (widget.item == null) {
        body['password'] = _password.text;
        body['password_confirmation'] = _passwordConfirm.text;
      }
      final api = ref.read(apiClientProvider);
      final id = widget.item?['id'];
      if (id == null) {
        await api.post('/admin/users', data: body);
      } else {
        await api.put('/admin/users/$id', data: body);
      }
      ref.invalidate(usersProvider);
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
    final roles = ref.watch(rolesProvider);
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
                controller: _email,
                keyboardType: TextInputType.emailAddress,
                decoration: InputDecoration(labelText: '${l10n.email} *'),
                validator: (v) =>
                    (v == null || !v.contains('@')) ? l10n.emailInvalid : null,
              ),
              const SizedBox(height: 12),
              roles.when(
                loading: () => const LinearProgressIndicator(),
                error: (e, _) => Text('$e'),
                data: (list) {
                  final keys = list
                      .map((r) => r['key']?.toString() ?? '')
                      .where((k) => k.isNotEmpty)
                      .toList();
                  final value =
                      keys.contains(_role) ? _role : (keys.isEmpty ? null : keys.first);
                  if (_role == null && value != null) {
                    WidgetsBinding.instance.addPostFrameCallback((_) {
                      if (mounted) setState(() => _role = value);
                    });
                  }
                  return DropdownButtonFormField<String>(
                    // ignore: deprecated_member_use
                    value: value,
                    decoration: InputDecoration(labelText: '${l10n.role} *'),
                    items: list
                        .map((r) => DropdownMenuItem(
                              value: r['key']?.toString(),
                              child: Text(r['label']?.toString() ??
                                  r['key']?.toString() ??
                                  ''),
                            ))
                        .toList(),
                    onChanged: (v) => setState(() => _role = v),
                    validator: (v) => v == null ? l10n.fieldRequired : null,
                  );
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _password,
                obscureText: true,
                decoration: InputDecoration(
                  labelText: isEdit
                      ? '${l10n.newPassword} (${l10n.leaveBlankKeep})'
                      : '${l10n.password} *',
                ),
                validator: (v) {
                  if (!isEdit && (v == null || v.length < 6)) {
                    return l10n.passwordRequired;
                  }
                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _passwordConfirm,
                obscureText: true,
                decoration: InputDecoration(
                  labelText: '${l10n.password} (confirm)',
                ),
                validator: (v) {
                  if (_password.text.isNotEmpty && v != _password.text) {
                    return '≠';
                  }
                  return null;
                },
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(l10n.isAdminActive),
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
