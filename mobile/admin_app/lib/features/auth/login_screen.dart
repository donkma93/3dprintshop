import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_envelope.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/base_url.dart';
import '../../l10n/app_localizations.dart';
import '../../l10n/locale_controller.dart';
import 'auth_controller.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _urlCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _obscure = true;
  bool _remember = true;
  List<String> _recent = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final store = ref.read(sessionStoreProvider);
    final base = await store.getBaseUrl();
    final remember = await store.getRememberBase();
    final recent = await store.recentBases();
    if (!mounted) return;
    setState(() {
      _remember = remember;
      _recent = recent;
      if (base != null && base.isNotEmpty) {
        _urlCtrl.text = base;
      }
    });
  }

  @override
  void dispose() {
    _urlCtrl.dispose();
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    FocusScope.of(context).unfocus();
    try {
      await ref.read(authControllerProvider.notifier).login(
            apiUrl: _urlCtrl.text,
            email: _emailCtrl.text,
            password: _passCtrl.text,
            rememberBase: _remember,
          );
    } catch (e) {
      if (!mounted) return;
      final msg = e is ApiException ? e.message : e.toString();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(msg), backgroundColor: Colors.red.shade700),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);
    final l10n = context.l10n;
    final locale = ref.watch(localeControllerProvider);

    return Scaffold(
      body: Container(
        width: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [AppTheme.sidebar, Color(0xFF1E293B)],
          ),
        ),
        child: SafeArea(
          child: Stack(
            children: [
              Positioned(
                top: 8,
                right: 8,
                child: PopupMenuButton<String>(
                  tooltip: l10n.language,
                  icon: const Icon(Icons.language, color: Colors.white),
                  onSelected: (code) {
                    ref
                        .read(localeControllerProvider.notifier)
                        .setLocale(Locale(code));
                  },
                  itemBuilder: (ctx) => [
                    CheckedPopupMenuItem(
                      value: 'vi',
                      checked: locale.languageCode == 'vi',
                      child: Text(l10n.vietnamese),
                    ),
                    CheckedPopupMenuItem(
                      value: 'en',
                      checked: locale.languageCode == 'en',
                      child: Text(l10n.english),
                    ),
                  ],
                ),
              ),
              Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(20),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 420),
                    child: Card(
                      child: Padding(
                        padding: const EdgeInsets.all(22),
                        child: Form(
                          key: _formKey,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(
                                l10n.loginTitle,
                                textAlign: TextAlign.center,
                                style: Theme.of(context)
                                    .textTheme
                                    .headlineSmall
                                    ?.copyWith(fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                l10n.loginSubtitle,
                                textAlign: TextAlign.center,
                                style: TextStyle(color: Colors.grey.shade600),
                              ),
                              const SizedBox(height: 22),
                              TextFormField(
                                controller: _urlCtrl,
                                keyboardType: TextInputType.url,
                                textInputAction: TextInputAction.next,
                                decoration: InputDecoration(
                                  labelText: '${l10n.apiUrl} *',
                                  hintText: l10n.apiUrlHint,
                                  prefixIcon: const Icon(Icons.language),
                                ),
                                validator: (v) {
                                  if (v == null || v.trim().isEmpty) {
                                    return l10n.apiUrlRequired;
                                  }
                                  if (!isValidHttpUrl(v)) {
                                    return l10n.apiUrlInvalid;
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(height: 6),
                              Text(
                                l10n.apiUrlHelp,
                                style: TextStyle(
                                    fontSize: 12, color: Colors.grey.shade600),
                              ),
                              if (_recent.isNotEmpty) ...[
                                const SizedBox(height: 10),
                                Wrap(
                                  spacing: 6,
                                  runSpacing: 6,
                                  children: _recent.map((b) {
                                    return ActionChip(
                                      label: Text(
                                        b.replaceFirst(
                                            RegExp(r'https?://'), ''),
                                        style: const TextStyle(fontSize: 11),
                                      ),
                                      onPressed: () =>
                                          setState(() => _urlCtrl.text = b),
                                    );
                                  }).toList(),
                                ),
                              ],
                              const SizedBox(height: 14),
                              TextFormField(
                                controller: _emailCtrl,
                                keyboardType: TextInputType.emailAddress,
                                textInputAction: TextInputAction.next,
                                decoration: InputDecoration(
                                  labelText: '${l10n.email} *',
                                  prefixIcon: const Icon(Icons.email_outlined),
                                ),
                                validator: (v) {
                                  if (v == null || v.trim().isEmpty) {
                                    return l10n.emailRequired;
                                  }
                                  if (!v.contains('@')) {
                                    return l10n.emailInvalid;
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(height: 14),
                              TextFormField(
                                controller: _passCtrl,
                                obscureText: _obscure,
                                textInputAction: TextInputAction.done,
                                onFieldSubmitted: (_) => _submit(),
                                decoration: InputDecoration(
                                  labelText: '${l10n.password} *',
                                  prefixIcon: const Icon(Icons.lock_outline),
                                  suffixIcon: IconButton(
                                    icon: Icon(_obscure
                                        ? Icons.visibility
                                        : Icons.visibility_off),
                                    onPressed: () =>
                                        setState(() => _obscure = !_obscure),
                                  ),
                                ),
                                validator: (v) {
                                  if (v == null || v.isEmpty) {
                                    return l10n.passwordRequired;
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(height: 8),
                              CheckboxListTile(
                                contentPadding: EdgeInsets.zero,
                                value: _remember,
                                onChanged: (v) =>
                                    setState(() => _remember = v ?? true),
                                title: Text(l10n.rememberUrl,
                                    style: const TextStyle(fontSize: 14)),
                                controlAffinity:
                                    ListTileControlAffinity.leading,
                              ),
                              if (auth.error != null) ...[
                                Text(auth.error!,
                                    style:
                                        TextStyle(color: Colors.red.shade700)),
                                const SizedBox(height: 8),
                              ],
                              const SizedBox(height: 8),
                              FilledButton(
                                onPressed: auth.loading ? null : _submit,
                                child: auth.loading
                                    ? const SizedBox(
                                        height: 22,
                                        width: 22,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                          color: Colors.white,
                                        ),
                                      )
                                    : Text(l10n.login),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                l10n.loginFootnote,
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                    fontSize: 11, color: Colors.grey.shade600),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
