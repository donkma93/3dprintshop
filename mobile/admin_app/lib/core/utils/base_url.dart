/// Chuẩn hóa URL người dùng nhập thành BASE API `…/api/v1`.
String normalizeBaseUrl(String raw) {
  var s = raw.trim();
  if (s.isEmpty) {
    throw ArgumentError('URL API không được để trống.');
  }
  if (!s.startsWith('http://') && !s.startsWith('https://')) {
    s = 'https://$s';
  }
  while (s.endsWith('/')) {
    s = s.substring(0, s.length - 1);
  }
  final lower = s.toLowerCase();
  if (lower.endsWith('/api/v1')) {
    return s;
  }
  if (lower.endsWith('/api')) {
    return '$s/v1';
  }
  return '$s/api/v1';
}

bool isValidHttpUrl(String raw) {
  try {
    final u = Uri.parse(normalizeBaseUrl(raw));
    return u.hasScheme && (u.scheme == 'http' || u.scheme == 'https') && u.host.isNotEmpty;
  } catch (_) {
    return false;
  }
}
