import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../features/auth/user_model.dart';

class SessionStore {
  static const _kBase = 'api_base_url';
  static const _kRecent = 'recent_bases';
  static const _kToken = 'access_token';
  static const _kUser = 'user_json';
  static const _kRemember = 'remember_base';

  final FlutterSecureStorage _secure;
  SharedPreferences? _prefs;

  SessionStore({FlutterSecureStorage? secure})
      : _secure = secure ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(encryptedSharedPreferences: true),
            );

  Future<SharedPreferences> get prefs async =>
      _prefs ??= await SharedPreferences.getInstance();

  Future<String?> getBaseUrl() async {
    final p = await prefs;
    return p.getString(_kBase);
  }

  Future<void> setBaseUrl(String base) async {
    final p = await prefs;
    await p.setString(_kBase, base);
    await _pushRecent(base);
  }

  Future<List<String>> recentBases() async {
    final p = await prefs;
    return p.getStringList(_kRecent) ?? [];
  }

  Future<void> _pushRecent(String base) async {
    final p = await prefs;
    final list = p.getStringList(_kRecent) ?? [];
    list.remove(base);
    list.insert(0, base);
    if (list.length > 5) {
      list.removeRange(5, list.length);
    }
    await p.setStringList(_kRecent, list);
  }

  Future<bool> getRememberBase() async {
    final p = await prefs;
    return p.getBool(_kRemember) ?? true;
  }

  Future<void> setRememberBase(bool v) async {
    final p = await prefs;
    await p.setBool(_kRemember, v);
  }

  Future<String?> getToken() => _secure.read(key: _kToken);

  Future<void> setToken(String? token) async {
    if (token == null || token.isEmpty) {
      await _secure.delete(key: _kToken);
    } else {
      await _secure.write(key: _kToken, value: token);
    }
  }

  Future<AdminUser?> getUser() async {
    final raw = await _secure.read(key: _kUser);
    if (raw == null || raw.isEmpty) return null;
    try {
      return AdminUser.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }

  Future<void> setUser(AdminUser? user) async {
    if (user == null) {
      await _secure.delete(key: _kUser);
    } else {
      await _secure.write(key: _kUser, value: jsonEncode(user.toJson()));
    }
  }

  Future<void> saveSession({
    required String baseUrl,
    required String token,
    required AdminUser user,
  }) async {
    await setBaseUrl(baseUrl);
    await setToken(token);
    await setUser(user);
  }

  /// Xóa token + user. Giữ BASE nếu [keepBase].
  Future<void> clearSession({bool keepBase = true}) async {
    await setToken(null);
    await setUser(null);
    if (!keepBase) {
      final p = await prefs;
      await p.remove(_kBase);
    }
  }

  Future<bool> hasSession() async {
    final t = await getToken();
    final b = await getBaseUrl();
    return t != null && t.isNotEmpty && b != null && b.isNotEmpty;
  }
}
