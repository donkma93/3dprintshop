import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_envelope.dart';
import '../../core/utils/api_list.dart';
import '../../core/widgets/async_body.dart';
import '../../core/widgets/resource_scaffold.dart';
import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';

final settingsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/settings');
  return asMap(env.data);
});

class SettingsScreen extends ConsumerStatefulWidget {
  const SettingsScreen({super.key});

  @override
  ConsumerState<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends ConsumerState<SettingsScreen> {
  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _ctrls = {};
  bool _ready = false;
  bool _saving = false;

  static const _fields = [
    'site_name',
    'site_tagline',
    'meta_title',
    'meta_description',
    'meta_keywords',
    'phone',
    'hotline',
    'email',
    'address',
    'working_hours',
    'facebook',
    'zalo',
    'youtube',
    'footer_about',
    'footer_copyright',
  ];

  TextEditingController _c(String key) =>
      _ctrls.putIfAbsent(key, () => TextEditingController());

  @override
  void dispose() {
    for (final c in _ctrls.values) {
      c.dispose();
    }
    super.dispose();
  }

  void _hydrate(Map<String, dynamic> data) {
    if (_ready) return;
    for (final f in _fields) {
      _c(f).text = data[f]?.toString() ?? '';
    }
    _ready = true;
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final l10n = context.l10n;
    try {
      final body = {
        for (final f in _fields)
          f: _c(f).text.trim().isEmpty && f != 'site_name'
              ? null
              : _c(f).text.trim(),
      };
      await ref.read(apiClientProvider).put('/admin/settings', data: body);
      ref.invalidate(settingsProvider);
      if (!mounted) return;
      showSnack(context, l10n.saved);
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
    final async = ref.watch(settingsProvider);

    return async.when(
      loading: () => Scaffold(
        appBar: AppBar(title: Text(l10n.navSettings)),
        body: LoadingBody(message: l10n.loading),
      ),
      error: (e, _) => Scaffold(
        appBar: AppBar(title: Text(l10n.navSettings)),
        body: ErrorBody(
          error: e,
          onRetry: () => ref.invalidate(settingsProvider),
        ),
      ),
      data: (data) {
        _hydrate(data);
        return FormScaffold(
          title: l10n.navSettings,
          saving: _saving,
          onSave: _save,
          children: [
            Form(
              key: _formKey,
              child: Column(
                children: [
                  TextFormField(
                    controller: _c('site_name'),
                    decoration:
                        InputDecoration(labelText: '${l10n.siteName} *'),
                    validator: (v) => (v == null || v.trim().isEmpty)
                        ? l10n.fieldRequired
                        : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('site_tagline'),
                    decoration: InputDecoration(labelText: l10n.siteTagline),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('meta_title'),
                    decoration: InputDecoration(labelText: l10n.metaTitle),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('meta_description'),
                    maxLines: 2,
                    decoration:
                        InputDecoration(labelText: l10n.metaDescription),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('meta_keywords'),
                    decoration: InputDecoration(labelText: l10n.metaKeywords),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('phone'),
                    decoration: InputDecoration(labelText: l10n.phone),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('hotline'),
                    decoration: InputDecoration(labelText: l10n.hotline),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('email'),
                    decoration: InputDecoration(labelText: l10n.email),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('address'),
                    maxLines: 2,
                    decoration: InputDecoration(labelText: l10n.address),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('working_hours'),
                    decoration: InputDecoration(labelText: l10n.workingHours),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('facebook'),
                    decoration: InputDecoration(labelText: l10n.facebook),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('zalo'),
                    decoration: InputDecoration(labelText: l10n.zalo),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('youtube'),
                    decoration: InputDecoration(labelText: l10n.youtube),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('footer_about'),
                    maxLines: 3,
                    decoration: InputDecoration(labelText: l10n.footerAbout),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _c('footer_copyright'),
                    decoration:
                        InputDecoration(labelText: l10n.footerCopyright),
                  ),
                ],
              ),
            ),
          ],
        );
      },
    );
  }
}
