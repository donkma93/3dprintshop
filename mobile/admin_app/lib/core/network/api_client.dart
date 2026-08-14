import 'package:dio/dio.dart';

import 'api_envelope.dart';

typedef UnauthorizedCallback = void Function();

class ApiClient {
  ApiClient({UnauthorizedCallback? onUnauthorized})
      : _onUnauthorized = onUnauthorized {
    _dio = Dio(
      BaseOptions(
        connectTimeout: const Duration(seconds: 20),
        receiveTimeout: const Duration(seconds: 45),
        sendTimeout: const Duration(seconds: 45),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        validateStatus: (s) => s != null && s < 600,
      ),
    );
  }

  late final Dio _dio;
  UnauthorizedCallback? _onUnauthorized;
  String? _baseUrl;
  String? _token;

  String? get baseUrl => _baseUrl;

  void setOnUnauthorized(UnauthorizedCallback? cb) => _onUnauthorized = cb;

  void setBaseUrl(String base) {
    _baseUrl = base.endsWith('/') ? base.substring(0, base.length - 1) : base;
    _dio.options.baseUrl = _baseUrl!;
  }

  void setToken(String? token) {
    _token = token;
    if (token == null || token.isEmpty) {
      _dio.options.headers.remove('Authorization');
    } else {
      _dio.options.headers['Authorization'] = 'Bearer $token';
    }
  }

  String? get token => _token;

  Future<ApiEnvelope> get(
    String path, {
    Map<String, dynamic>? query,
  }) =>
      _request(() => _dio.get(path, queryParameters: query));

  Future<ApiEnvelope> post(
    String path, {
    Object? data,
    Map<String, dynamic>? query,
  }) =>
      _request(() => _dio.post(path, data: data, queryParameters: query));

  Future<ApiEnvelope> put(
    String path, {
    Object? data,
    Map<String, dynamic>? query,
  }) =>
      _request(() => _dio.put(path, data: data, queryParameters: query));

  Future<ApiEnvelope> patch(
    String path, {
    Object? data,
  }) =>
      _request(() => _dio.patch(path, data: data));

  Future<ApiEnvelope> delete(
    String path, {
    Object? data,
    Map<String, dynamic>? query,
  }) =>
      _request(() => _dio.delete(path, data: data, queryParameters: query));

  Future<ApiEnvelope> upload(
    String path, {
    required FormData formData,
    String method = 'POST',
  }) =>
      _request(() {
        if (method.toUpperCase() == 'PUT') {
          return _dio.put(path, data: formData);
        }
        return _dio.post(path, data: formData);
      });

  Future<ApiEnvelope> _request(Future<Response> Function() call) async {
    if (_baseUrl == null || _baseUrl!.isEmpty) {
      throw ApiException('Chưa cấu hình URL API.');
    }
    try {
      final res = await call();
      final status = res.statusCode ?? 0;
      final body = res.data;

      if (status == 401) {
        _onUnauthorized?.call();
        throw ApiException('Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại.',
            statusCode: 401);
      }

      if (body is! Map) {
        if (status >= 200 && status < 300) {
          return ApiEnvelope(success: true, message: 'OK', data: body);
        }
        throw ApiException('Phản hồi không hợp lệ ($status).', statusCode: status);
      }

      final map = Map<String, dynamic>.from(body);
      final envelope = ApiEnvelope.fromJson(map);

      if (status >= 200 && status < 300) {
        return envelope;
      }

      throw ApiException(
        envelope.message.isNotEmpty
            ? envelope.message
            : 'Lỗi máy chủ ($status).',
        statusCode: status,
        errors: envelope.errors,
      );
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.sendTimeout) {
        throw ApiException(
            'Hết thời gian kết nối. Kiểm tra URL API và mạng.');
      }
      if (e.type == DioExceptionType.connectionError) {
        throw ApiException(
            'Không kết nối được server. Kiểm tra URL API / Wi‑Fi.');
      }
      final status = e.response?.statusCode;
      final data = e.response?.data;
      if (data is Map) {
        final env = ApiEnvelope.fromJson(Map<String, dynamic>.from(data));
        throw ApiException(
          env.message.isNotEmpty ? env.message : (e.message ?? 'Lỗi mạng'),
          statusCode: status,
          errors: env.errors,
        );
      }
      throw ApiException(e.message ?? 'Lỗi mạng', statusCode: status);
    }
  }
}
