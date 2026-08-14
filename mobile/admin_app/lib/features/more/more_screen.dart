import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/permissions/permission_guard.dart';
import '../auth/auth_controller.dart';

class MoreScreen extends ConsumerWidget {
  const MoreScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final user = auth.user;

    return Scaffold(
      appBar: AppBar(title: const Text('Thêm')),
      body: ListView(
        children: [
          ListTile(
            leading: const CircleAvatar(child: Icon(Icons.person)),
            title: Text(user?.name ?? '—'),
            subtitle: Text('${user?.email}\n${user?.roleLabel}\n${auth.baseUrl}'),
            isThreeLine: true,
          ),
          const Divider(),
          if (canAccess(ref, 'products.manage'))
            ListTile(
              leading: const Icon(Icons.inventory_2_outlined),
              title: const Text('Sản phẩm'),
              onTap: () => context.push('/products'),
            ),
          if (canAccess(ref, 'tax.manage'))
            ListTile(
              leading: const Icon(Icons.calculate_outlined),
              title: const Text('Thuế HKD (chuẩn bị)'),
              onTap: () => context.push('/tax'),
            ),
          if (canAccess(ref, 'sales.sell'))
            ListTile(
              leading: const Icon(Icons.qr_code_scanner),
              title: const Text('Bán hàng QR'),
              onTap: () => context.go('/sales'),
            ),
          ListTile(
            leading: const Icon(Icons.info_outline),
            title: const Text('Về ứng dụng'),
            subtitle: const Text('Admin mobile · BASE URL cấu hình lúc login'),
            onTap: () {
              showAboutDialog(
                context: context,
                applicationName: '3D Print Shop Admin',
                applicationVersion: '1.0.0',
                children: [
                  Text('API: ${auth.baseUrl ?? '—'}'),
                  const Text(
                    'Gọi REST /api/v1/admin/*. Token Sanctum lưu secure storage.',
                  ),
                ],
              );
            },
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.logout, color: Colors.red),
            title: const Text('Đăng xuất', style: TextStyle(color: Colors.red)),
            onTap: () async {
              final ok = await showDialog<bool>(
                context: context,
                builder: (ctx) => AlertDialog(
                  title: const Text('Đăng xuất?'),
                  actions: [
                    TextButton(
                        onPressed: () => Navigator.pop(ctx, false),
                        child: const Text('Huỷ')),
                    FilledButton(
                        onPressed: () => Navigator.pop(ctx, true),
                        child: const Text('Đăng xuất')),
                  ],
                ),
              );
              if (ok == true) {
                await ref.read(authControllerProvider.notifier).logout();
              }
            },
          ),
          ListTile(
            leading: const Icon(Icons.phonelink_erase, color: Colors.orange),
            title: const Text('Đăng xuất mọi thiết bị'),
            onTap: () async {
              final ok = await showDialog<bool>(
                context: context,
                builder: (ctx) => AlertDialog(
                  title: const Text('Đăng xuất tất cả thiết bị?'),
                  content: const Text(
                      'Xóa mọi token Sanctum của tài khoản trên server.'),
                  actions: [
                    TextButton(
                        onPressed: () => Navigator.pop(ctx, false),
                        child: const Text('Huỷ')),
                    FilledButton(
                        onPressed: () => Navigator.pop(ctx, true),
                        child: const Text('Xác nhận')),
                  ],
                ),
              );
              if (ok == true) {
                await ref
                    .read(authControllerProvider.notifier)
                    .logout(allDevices: true);
              }
            },
          ),
        ],
      ),
    );
  }
}
