import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/auth/auth_controller.dart';
import '../features/auth/login_screen.dart';
import '../features/categories/categories_screen.dart';
import '../features/chat/chat_list_screen.dart';
import '../features/chat/chat_thread_screen.dart';
import '../features/cms/banners_screen.dart';
import '../features/cms/pages_screen.dart';
import '../features/cms/posts_screen.dart';
import '../features/dashboard/dashboard_screen.dart';
import '../features/equipment/equipment_screen.dart';
import '../features/materials/material_inputs_screen.dart';
import '../features/materials/materials_screen.dart';
import '../features/more/more_screen.dart';
import '../features/orders/orders_screen.dart';
import '../features/products/products_screen.dart';
import '../features/sales/history_screen.dart';
import '../features/sales/print_screen.dart';
import '../features/sales/report_screen.dart';
import '../features/sales/sales_hub_screen.dart';
import '../features/sales/scan_sell_screen.dart';
import '../features/settings/settings_screen.dart';
import '../features/shell/home_shell.dart';
import '../features/tax/tax_screen.dart';
import '../features/trash/trash_screen.dart';
import '../features/users/users_screen.dart';

final _rootKey = GlobalKey<NavigatorState>();

Map<String, dynamic>? _extraMap(Object? extra) {
  if (extra is Map<String, dynamic>) return extra;
  if (extra is Map) return Map<String, dynamic>.from(extra);
  return null;
}

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

      // Catalog
      GoRoute(
        path: '/products',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const ProductsScreen(),
      ),
      GoRoute(
        path: '/products/form',
        parentNavigatorKey: _rootKey,
        builder: (context, state) =>
            ProductFormScreen(item: _extraMap(state.extra)),
      ),
      GoRoute(
        path: '/products/qr/:id',
        parentNavigatorKey: _rootKey,
        builder: (context, state) =>
            ProductQrScreen(productId: state.pathParameters['id']!),
      ),
      GoRoute(
        path: '/categories',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const CategoriesScreen(),
      ),
      GoRoute(
        path: '/categories/form',
        parentNavigatorKey: _rootKey,
        builder: (context, state) =>
            CategoryFormScreen(item: _extraMap(state.extra)),
      ),
      GoRoute(
        path: '/materials',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const MaterialsScreen(),
      ),
      GoRoute(
        path: '/materials/form',
        parentNavigatorKey: _rootKey,
        builder: (context, state) =>
            MaterialFormScreen(item: _extraMap(state.extra)),
      ),
      GoRoute(
        path: '/material-inputs',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const MaterialInputsScreen(),
      ),
      GoRoute(
        path: '/material-inputs/form',
        parentNavigatorKey: _rootKey,
        builder: (context, state) =>
            MaterialInputFormScreen(item: _extraMap(state.extra)),
      ),
      GoRoute(
        path: '/equipment',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const EquipmentScreen(),
      ),
      GoRoute(
        path: '/equipment/form',
        parentNavigatorKey: _rootKey,
        builder: (context, state) =>
            EquipmentFormScreen(item: _extraMap(state.extra)),
      ),

      // CMS
      GoRoute(
        path: '/banners',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const BannersScreen(),
      ),
      GoRoute(
        path: '/banners/form',
        parentNavigatorKey: _rootKey,
        builder: (context, state) =>
            BannerFormScreen(item: _extraMap(state.extra)),
      ),
      GoRoute(
        path: '/posts',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const PostsScreen(),
      ),
      GoRoute(
        path: '/posts/form',
        parentNavigatorKey: _rootKey,
        builder: (context, state) =>
            PostFormScreen(item: _extraMap(state.extra)),
      ),
      GoRoute(
        path: '/pages',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const PagesScreen(),
      ),
      GoRoute(
        path: '/pages/form',
        parentNavigatorKey: _rootKey,
        builder: (context, state) =>
            PageFormScreen(item: _extraMap(state.extra)),
      ),

      // Tax
      GoRoute(
        path: '/tax',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const TaxScreen(),
      ),
      GoRoute(
        path: '/tax/ledger',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const TaxLedgerScreen(),
      ),
      GoRoute(
        path: '/tax/report',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const TaxReportScreen(),
      ),
      GoRoute(
        path: '/tax/profile',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const TaxProfileScreen(),
      ),

      // System
      GoRoute(
        path: '/users',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const UsersScreen(),
      ),
      GoRoute(
        path: '/users/form',
        parentNavigatorKey: _rootKey,
        builder: (context, state) =>
            UserFormScreen(item: _extraMap(state.extra)),
      ),
      GoRoute(
        path: '/settings',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const SettingsScreen(),
      ),
      GoRoute(
        path: '/trash',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const TrashScreen(),
      ),
    ],
  );
});

class _AuthListenable extends ChangeNotifier {
  _AuthListenable(this.ref) {
    ref.listen<AuthState>(authControllerProvider, (prev, next) {
      notifyListeners();
    });
  }
  final Ref ref;
}
