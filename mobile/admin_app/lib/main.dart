import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/theme/app_theme.dart';
import 'features/auth/auth_controller.dart';
import 'l10n/app_localizations.dart';
import 'l10n/locale_controller.dart';
import 'router/app_router.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const ProviderScope(child: AdminApp()));
}

class AdminApp extends ConsumerWidget {
  const AdminApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final router = ref.watch(appRouterProvider);
    final locale = ref.watch(localeControllerProvider);

    if (auth.bootstrapping) {
      final l10n = AppLocalizations(locale.languageCode);
      return MaterialApp(
        debugShowCheckedModeBanner: false,
        locale: locale,
        supportedLocales: const [Locale('vi'), Locale('en')],
        localizationsDelegates: const [
          AppLocalizationsDelegate(),
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        theme: AppTheme.light(),
        home: Scaffold(
          body: Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const CircularProgressIndicator(),
                const SizedBox(height: 12),
                Text(l10n.restoringSession),
              ],
            ),
          ),
        ),
      );
    }

    return MaterialApp.router(
      title: '3D Print Shop Admin',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      locale: locale,
      supportedLocales: const [Locale('vi'), Locale('en')],
      localizationsDelegates: const [
        AppLocalizationsDelegate(),
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      // Keep admin UI readable on high system font scales (S22 Ultra accessibility).
      builder: (context, child) {
        final mq = MediaQuery.of(context);
        final scale = mq.textScaler.scale(1.0).clamp(0.9, 1.2);
        return MediaQuery(
          data: mq.copyWith(textScaler: TextScaler.linear(scale)),
          child: child ?? const SizedBox.shrink(),
        );
      },
      routerConfig: router,
    );
  }
}
