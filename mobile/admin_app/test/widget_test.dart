import 'package:admin_app/core/utils/base_url.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('normalizeBaseUrl works', () {
    expect(normalizeBaseUrl('https://a.com'), 'https://a.com/api/v1');
  });
}
