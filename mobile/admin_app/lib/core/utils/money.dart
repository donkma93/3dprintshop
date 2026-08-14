import 'package:intl/intl.dart';

final _vn = NumberFormat.decimalPattern('vi_VN');

String formatMoney(dynamic value, {String suffix = ' đ'}) {
  if (value == null) return '—';
  final n = value is num ? value : num.tryParse(value.toString());
  if (n == null) return value.toString();
  return '${_vn.format(n.round())}$suffix';
}

String formatDate(String? iso) {
  if (iso == null || iso.isEmpty) return '—';
  try {
    final d = DateTime.parse(iso).toLocal();
    return DateFormat('dd/MM/yyyy HH:mm').format(d);
  } catch (_) {
    return iso;
  }
}

String formatDay(String? iso) {
  if (iso == null || iso.isEmpty) return '—';
  try {
    final d = DateTime.parse(iso).toLocal();
    return DateFormat('dd/MM/yyyy').format(d);
  } catch (_) {
    return iso;
  }
}
