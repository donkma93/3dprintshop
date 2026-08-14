import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/network/api_envelope.dart';
import '../../core/providers.dart';
import '../../core/storage/session_store.dart';
import '../../core/utils/base_url.dart';
import 'user_model.dart';

export '../../core/providers.dart';

class AuthState {
  final bool bootstrapping;
  final bool loading;
  final String? baseUrl;
  final AdminUser? user;
  final String? error;

  const AuthState({
    this.bootstrapping = true,
    this.loading = false,
    this.baseUrl,
    this.user,
    this.error,
  });

  bool get isAuthenticated => user != null && baseUrl != null;

  AuthState copyWith({
    bool? bootstrapping,
    bool? loading,
    String? baseUrl,
    AdminUser? user,
    String? error,
    bool clearError = false,
    bool clearUser = false,
  }) {
    return AuthState(
      bootstrapping: bootstrapping ?? this.bootstrapping,
      loading: loading ?? this.loading,
      baseUrl: baseUrl ?? this.baseUrl,
      user: clearUser ? null : (user ?? this.user),
      error: clearError ? null : (error ?? this.error),
    );
  }
}

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._ref) : super(const AuthState()) {
    _bootstrap();
  }

  final Ref _ref;

  SessionStore get _store => _ref.read(sessionStoreProvider);
  ApiClient get _api => _ref.read(apiClientProvider);

  Future<void> _bootstrap() async {
    try {
      final base = await _store.getBaseUrl();
      final token = await _store.getToken();
      final user = await _store.getUser();

      if (base != null && base.isNotEmpty) {
        _api.setBaseUrl(base);
        state = state.copyWith(baseUrl: base);
      }

      if (token != null && token.isNotEmpty && base != null) {
        _api.setToken(token);
        _api.setOnUnauthorized(_handleUnauthorized);
        if (user != null) {
          state = state.copyWith(user: user, bootstrapping: false);
        }
        // Refresh me
        try {
          final env = await _api.get('/admin/me');
          final me = AdminUser.fromJson(
            Map<String, dynamic>.from(env.data as Map),
          );
          await _store.setUser(me);
          state = state.copyWith(
            user: me,
            bootstrapping: false,
            clearError: true,
          );
        } catch (_) {
          // token invalid
          await _store.clearSession(keepBase: true);
          _api.setToken(null);
          state = state.copyWith(
            clearUser: true,
            bootstrapping: false,
          );
        }
      } else {
        state = state.copyWith(bootstrapping: false);
      }
    } catch (e) {
      state = state.copyWith(
        bootstrapping: false,
        error: e.toString(),
      );
    }
  }

  void _handleUnauthorized() {
    // fire-and-forget
    Future(() async {
      await _store.clearSession(keepBase: true);
      _api.setToken(null);
      state = state.copyWith(clearUser: true);
    });
  }

  Future<List<String>> recentBases() => _store.recentBases();

  Future<void> login({
    required String apiUrl,
    required String email,
    required String password,
    bool rememberBase = true,
  }) async {
    state = state.copyWith(loading: true, clearError: true);
    try {
      final base = normalizeBaseUrl(apiUrl);
      _api.setBaseUrl(base);
      _api.setToken(null);

      final deviceName = await _deviceName();
      final env = await _api.post('/admin/login', data: {
        'email': email.trim(),
        'password': password,
        'device_name': deviceName,
      });

      final data = Map<String, dynamic>.from(env.data as Map);
      final token = data['token']?.toString() ?? '';
      if (token.isEmpty) {
        throw ApiException('Server không trả token.');
      }
      final user = AdminUser.fromJson(
        Map<String, dynamic>.from(data['user'] as Map),
      );

      await _store.setRememberBase(rememberBase);
      await _store.saveSession(baseUrl: base, token: token, user: user);
      _api.setToken(token);
      _api.setOnUnauthorized(_handleUnauthorized);

      state = state.copyWith(
        loading: false,
        baseUrl: base,
        user: user,
        clearError: true,
      );
    } catch (e) {
      state = state.copyWith(
        loading: false,
        error: e is ApiException ? e.message : e.toString(),
      );
      rethrow;
    }
  }

  Future<void> logout({bool allDevices = false}) async {
    try {
      if (allDevices) {
        await _api.post('/admin/logout-all');
      } else {
        await _api.post('/admin/logout');
      }
    } catch (_) {
      // vẫn xóa local
    }
    final keep = await _store.getRememberBase();
    await _store.clearSession(keepBase: keep);
    _api.setToken(null);
    state = state.copyWith(clearUser: true, clearError: true);
  }

  Future<String> _deviceName() async {
    try {
      final plugin = DeviceInfoPlugin();
      if (defaultTargetPlatform == TargetPlatform.android) {
        final a = await plugin.androidInfo;
        return 'android-${a.model}'.replaceAll(' ', '-').toLowerCase();
      }
      if (defaultTargetPlatform == TargetPlatform.iOS) {
        final i = await plugin.iosInfo;
        return 'ios-${i.utsname.machine}'.replaceAll(' ', '-').toLowerCase();
      }
    } catch (_) {}
    return 'flutter-admin-app';
  }
}

final authControllerProvider =
    StateNotifierProvider<AuthController, AuthState>((ref) {
  return AuthController(ref);
});
