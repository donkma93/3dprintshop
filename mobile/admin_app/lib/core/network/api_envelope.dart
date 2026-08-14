class ApiMeta {
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  const ApiMeta({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  factory ApiMeta.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return const ApiMeta(currentPage: 1, lastPage: 1, perPage: 15, total: 0);
    }
    return ApiMeta(
      currentPage: (json['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (json['last_page'] as num?)?.toInt() ?? 1,
      perPage: (json['per_page'] as num?)?.toInt() ?? 15,
      total: (json['total'] as num?)?.toInt() ?? 0,
    );
  }
}

class ApiEnvelope {
  final bool success;
  final String message;
  final dynamic data;
  final ApiMeta? meta;
  final Map<String, dynamic>? errors;

  const ApiEnvelope({
    required this.success,
    required this.message,
    this.data,
    this.meta,
    this.errors,
  });

  factory ApiEnvelope.fromJson(Map<String, dynamic> json) {
    Map<String, dynamic>? err;
    final rawErr = json['errors'];
    if (rawErr is Map<String, dynamic>) {
      err = rawErr;
    }

    ApiMeta? meta;
    if (json['meta'] is Map<String, dynamic>) {
      meta = ApiMeta.fromJson(json['meta'] as Map<String, dynamic>);
    }

    return ApiEnvelope(
      success: json['success'] == true,
      message: (json['message'] ?? '').toString(),
      data: json['data'],
      meta: meta,
      errors: err,
    );
  }

  String fieldError(String key) {
    final e = errors?[key];
    if (e is List && e.isNotEmpty) return e.first.toString();
    if (e != null) return e.toString();
    return message;
  }
}

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  ApiException(this.message, {this.statusCode, this.errors});

  @override
  String toString() => message;
}
