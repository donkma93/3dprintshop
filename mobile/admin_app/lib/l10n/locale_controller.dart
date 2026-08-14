import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _kLocale = 'app_locale';

final localeControllerProvider =
    StateNotifierProvider<LocaleController, Locale>((ref) {
  return LocaleController();
});

class LocaleController extends StateNotifier<Locale> {
  LocaleController() : super(const Locale('vi')) {
    _load();
  }

  Future<void> _load() async {
    final p = await SharedPreferences.getInstance();
    final code = p.getString(_kLocale);
    if (code == 'en' || code == 'vi') {
      state = Locale(code!);
    }
  }

  Future<void> setLocale(Locale locale) async {
    state = locale;
    final p = await SharedPreferences.getInstance();
    await p.setString(_kLocale, locale.languageCode);
  }

  Future<void> toggle() async {
    final next = state.languageCode == 'vi' ? const Locale('en') : const Locale('vi');
    await setLocale(next);
  }
}
