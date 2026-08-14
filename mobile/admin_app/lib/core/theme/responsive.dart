import 'package:flutter/material.dart';

/// Breakpoints & spacing tuned for phones (S22 Ultra ~412dp) → tablets → desktop.
class R {
  R._(this.width, this.textScale);

  final double width;
  final double textScale;

  factory R.of(BuildContext context) {
    final mq = MediaQuery.of(context);
    return R._(mq.size.width, mq.textScaler.scale(1.0).clamp(0.85, 1.25));
  }

  bool get isPhone => width < 600;
  bool get isTablet => width >= 600 && width < 1024;
  bool get isDesktop => width >= 1024;

  /// KPI / money grid columns
  int get kpiColumns {
    if (width >= 1100) return 4;
    if (width >= 700) return 3;
    if (width >= 360) return 2;
    return 1;
  }

  double get pagePadding => isPhone ? 12 : 16;
  double get gap => isPhone ? 8 : 10;
  double get cardRadius => 14;

  double sp(double base) => (base * textScale).clamp(base * 0.9, base * 1.15);

  /// Cap system font scale so dense admin UI does not overflow.
  static Widget clampTextScale(BuildContext context, Widget child) {
    final mq = MediaQuery.of(context);
    final scale = mq.textScaler.scale(1.0).clamp(0.9, 1.2);
    return MediaQuery(
      data: mq.copyWith(textScaler: TextScaler.linear(scale)),
      child: child,
    );
  }
}
