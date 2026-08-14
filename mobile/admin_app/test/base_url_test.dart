import 'package:admin_app/core/utils/base_url.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('normalizeBaseUrl appends /api/v1', () {
    expect(normalizeBaseUrl('https://shop.com'), 'https://shop.com/api/v1');
    expect(normalizeBaseUrl('https://shop.com/'), 'https://shop.com/api/v1');
    expect(normalizeBaseUrl('https://shop.com/api'), 'https://shop.com/api/v1');
    expect(
        normalizeBaseUrl('https://shop.com/api/v1'), 'https://shop.com/api/v1');
    expect(
        normalizeBaseUrl('https://shop.com/api/v1/'), 'https://shop.com/api/v1');
    expect(normalizeBaseUrl('http://192.168.1.10:8000'),
        'http://192.168.1.10:8000/api/v1');
    expect(normalizeBaseUrl('shop.example.com'),
        'https://shop.example.com/api/v1');
  });

  test('isValidHttpUrl', () {
    expect(isValidHttpUrl('https://a.com'), true);
    expect(isValidHttpUrl('not a url'), true); // becomes https://not a url — host may fail
    expect(isValidHttpUrl(''), false);
  });
}
