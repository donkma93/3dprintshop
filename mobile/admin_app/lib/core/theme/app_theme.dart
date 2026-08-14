import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class AppTheme {
  static const Color sidebar = Color(0xFF0F172A);
  static const Color accent = Color(0xFF38BDF8);

  static ThemeData light() {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: sidebar,
      primary: sidebar,
      secondary: accent,
      brightness: Brightness.light,
    );

    final base = ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: const Color(0xFFF1F5F9),
      visualDensity: VisualDensity.standard,
      materialTapTargetSize: MaterialTapTargetSize.padded,
      appBarTheme: const AppBarTheme(
        backgroundColor: sidebar,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
        systemOverlayStyle: SystemUiOverlayStyle.light,
        titleTextStyle: TextStyle(
          color: Colors.white,
          fontSize: 18,
          fontWeight: FontWeight.w600,
          letterSpacing: 0.1,
          height: 1.25,
        ),
        iconTheme: IconThemeData(color: Colors.white, size: 22),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: Colors.white,
        margin: EdgeInsets.zero,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
      listTileTheme: const ListTileThemeData(
        dense: false,
        visualDensity: VisualDensity(horizontal: 0, vertical: -1),
        contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 2),
        minLeadingWidth: 36,
        horizontalTitleGap: 12,
      ),
      navigationBarTheme: NavigationBarThemeData(
        height: 56,
        elevation: 2,
        backgroundColor: Colors.white,
        indicatorColor: accent.withValues(alpha: 0.22),
        // Labels hidden app-wide; destinations still keep tooltip text.
        labelBehavior: NavigationDestinationLabelBehavior.alwaysHide,
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(
            size: 24,
            color: selected ? sidebar : const Color(0xFF64748B),
          );
        }),
      ),
      textTheme: const TextTheme(
        titleLarge: TextStyle(
          fontSize: 20,
          fontWeight: FontWeight.w700,
          height: 1.25,
          letterSpacing: -0.2,
        ),
        titleMedium: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w700,
          height: 1.3,
        ),
        titleSmall: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          height: 1.3,
        ),
        bodyLarge: TextStyle(fontSize: 15, height: 1.4),
        bodyMedium: TextStyle(fontSize: 13.5, height: 1.4),
        bodySmall: TextStyle(fontSize: 12, height: 1.35, color: Color(0xFF64748B)),
        labelLarge: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, height: 1.2),
        labelMedium: TextStyle(fontSize: 12, fontWeight: FontWeight.w500, height: 1.2),
        labelSmall: TextStyle(fontSize: 11, fontWeight: FontWeight.w500, height: 1.2),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        isDense: true,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: sidebar,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 18),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          textStyle: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            height: 1.2,
          ),
        ),
      ),
      pageTransitionsTheme: const PageTransitionsTheme(
        builders: {
          TargetPlatform.android: ZoomPageTransitionsBuilder(),
          TargetPlatform.iOS: ZoomPageTransitionsBuilder(),
          TargetPlatform.windows: FadeUpwardsPageTransitionsBuilder(),
          TargetPlatform.macOS: FadeUpwardsPageTransitionsBuilder(),
          TargetPlatform.linux: FadeUpwardsPageTransitionsBuilder(),
        },
      ),
    );
    return base;
  }
}
