import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/auth/auth_controller.dart';
import '../features/auth/login_screen.dart';
import '../features/chat/chat_list_screen.dart';
import '../features/chat/chat_thread_screen.dart';
import '../features/dashboard/dashboard_screen.dart';
import '../features/more/more_screen.dart';
import '../features/orders/orders_screen.dart';
import '../features/products/products_screen.dart';
import '../features/sales/history_screen.dart';
import '../features/sales/print_screen.dart';
import '../features/sales/report_screen.dart';
import '../features/sales/sales_hub_screen.dart';
import '../features/sales/scan_sell_screen.dart';
import '../features/shell/home_shell.dart';
import '../features/tax/tax_screen.dart';

final _rootKey = GlobalKey<NavigatorState>();

final appRouterProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authControllerProvider);

  return GoRouter(
    navigatorKey: _rootKey,
    initialLocation: '/dashboard',
    refreshListenable: _AuthListenable(ref),
    redirect: (context, state) {
      if (auth.bootstrapping) return null;
      final loggingIn = state.matchedLocation == '/login';
      if (!auth.isAuthenticated) {
        return loggingIn ? null : '/login';
      }
      if (loggingIn) return '/dashboard';
      return null;
    },
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return HomeShell(navigationShell: navigationShell);
        },
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/dashboard',
                builder: (context, state) => const DashboardScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/sales',
                builder: (context, state) => const SalesHubScreen(),
                routes: [
                  GoRoute(
                    path: 'scan',
                    builder: (context, state) => const ScanSellScreen(),
                  ),
                  GoRoute(
                    path: 'history',
                    builder: (context, state) => const SalesHistoryScreen(),
                  ),
                  GoRoute(
                    path: 'report',
                    builder: (context, state) => const SalesReportScreen(),
                  ),
                  GoRoute(
                    path: 'print/:id',
                    builder: (context, state) => SalePrintScreen(
                      saleId: state.pathParameters['id']!,
                    ),
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/chat',
                builder: (context, state) => const ChatListScreen(),
                routes: [
                  GoRoute(
                    path: ':id',
                    builder: (context, state) => ChatThreadScreen(
                      conversationId: state.pathParameters['id']!,
                    ),
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/orders',
                builder: (context, state) => const OrdersScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/more',
                builder: (context, state) => const MoreScreen(),
              ),
            ],
          ),
        ],
      ),
      GoRoute(
        path: '/products',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const ProductsScreen(),
      ),
      GoRoute(
        path: '/tax',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const TaxScreen(),
      ),
    ],
  );
});

/// go_router refresh when auth changes
class _AuthListenable extends ChangeNotifier {
  _AuthListenable(this.ref) {
    ref.listen<AuthState>(authControllerProvider, (prev, next) {
      notifyListeners();
    });
  }
  final Ref ref;
}
