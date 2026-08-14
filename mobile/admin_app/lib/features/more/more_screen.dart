import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/permissions/permission_guard.dart';
import '../../l10n/app_localizations.dart';
import '../../l10n/locale_controller.dart';
import '../auth/auth_controller.dart';

class MoreScreen extends ConsumerWidget {
  const MoreScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final user = auth.user;
    final l10n = context.l10n;
    final locale = ref.watch(localeControllerProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.menu),
        actions: [
          PopupMenuButton<String>(
            tooltip: l10n.language,
            icon: const Icon(Icons.language),
            onSelected: (code) {
              ref.read(localeControllerProvider.notifier).setLocale(Locale(code));
            },
            itemBuilder: (ctx) => [
              CheckedPopupMenuItem(
                value: 'vi',
                checked: locale.languageCode == 'vi',
                child: Text(l10n.vietnamese),
              ),
              CheckedPopupMenuItem(
                value: 'en',
                checked: locale.languageCode == 'en',
                child: Text(l10n.english),
              ),
            ],
          ),
        ],
      ),
      body: ListView(
        children: [
          ListTile(
            leading: const CircleAvatar(child: Icon(Icons.person)),
            title: Text(user?.name ?? '—'),
            subtitle: Text(
              '${user?.email ?? ''}\n${user?.roleLabel ?? ''}\n${auth.baseUrl ?? ''}',
            ),
            isThreeLine: true,
          ),
          const Divider(),

          // Catalog — mirror web sidebar
          if (_any(ref, [
            'products.manage',
            'categories.manage',
            'materials.manage',
            'material_inputs.manage',
            'equipment.manage',
          ]))
            _section(l10n.sectionCatalog),
          if (canAccess(ref, 'products.manage'))
            _item(context, Icons.inventory_2_outlined, l10n.navProducts, '/products'),
          if (canAccess(ref, 'categories.manage'))
            _item(context, Icons.category_outlined, l10n.navCategories, '/categories'),
          if (canAccess(ref, 'materials.manage'))
            _item(context, Icons.water_drop_outlined, l10n.navMaterials, '/materials'),
          if (canAccess(ref, 'material_inputs.manage'))
            _item(context, Icons.input, l10n.navMaterialInputs, '/material-inputs'),
          if (canAccess(ref, 'equipment.manage'))
            _item(context, Icons.print_outlined, l10n.navEquipment, '/equipment'),

          if (_any(ref, ['banners.manage', 'posts.manage', 'pages.manage']))
            _section(l10n.sectionContent),
          if (canAccess(ref, 'banners.manage'))
            _item(context, Icons.view_carousel_outlined, l10n.navBanners, '/banners'),
          if (canAccess(ref, 'posts.manage'))
            _item(context, Icons.article_outlined, l10n.navPosts, '/posts'),
          if (canAccess(ref, 'pages.manage'))
            _item(context, Icons.description_outlined, l10n.navPages, '/pages'),

          if (canAccess(ref, 'sales.sell') ||
              (user?.canViewRevenue ?? false))
            _section(l10n.sectionSales),
          if (canAccess(ref, 'sales.sell')) ...[
            _item(context, Icons.qr_code_scanner, l10n.navSalesScan, '/sales/scan'),
            _item(context, Icons.receipt_long, l10n.navSalesHistory, '/sales/history'),
          ],
          if (user?.canViewRevenue ?? false)
            _item(context, Icons.bar_chart, l10n.navSalesReport, '/sales/report'),

          if (canAccess(ref, 'tax.manage')) ...[
            _section(l10n.sectionTax),
            _item(context, Icons.calculate_outlined, l10n.navTaxOverview, '/tax'),
            _item(context, Icons.menu_book_outlined, l10n.navTaxLedger, '/tax/ledger'),
            _item(context, Icons.assessment_outlined, l10n.navTaxReport, '/tax/report'),
            _item(context, Icons.badge_outlined, l10n.navTaxProfile, '/tax/profile'),
          ],

          if (_any(ref, ['users.manage', 'settings.manage', 'trash.manage']))
            _section(l10n.sectionSystem),
          if (canAccess(ref, 'users.manage'))
            _item(context, Icons.people_outline, l10n.navUsers, '/users'),
          if (canAccess(ref, 'settings.manage'))
            _item(context, Icons.settings_outlined, l10n.navSettings, '/settings'),
          if (canAccess(ref, 'trash.manage'))
            _item(context, Icons.delete_outline, l10n.navTrash, '/trash'),

          _section(l10n.sectionAccount),
          ListTile(
            leading: const Icon(Icons.language),
            title: Text(l10n.language),
            subtitle: Text(
              locale.languageCode == 'vi' ? l10n.vietnamese : l10n.english,
            ),
            trailing: SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: 'vi', label: Text('VI')),
                ButtonSegment(value: 'en', label: Text('EN')),
              ],
              selected: {locale.languageCode},
              onSelectionChanged: (s) {
                ref
                    .read(localeControllerProvider.notifier)
                    .setLocale(Locale(s.first));
              },
            ),
          ),
          ListTile(
            leading: const Icon(Icons.storefront_outlined),
            title: Text(l10n.viewStore),
            onTap: () async {
              final base = auth.baseUrl;
              if (base == null) return;
              // BASE is .../api/v1 → storefront is origin
              final origin = base
                  .replaceFirst(RegExp(r'/api/v1/?$'), '')
                  .replaceFirst(RegExp(r'/api/?$'), '');
              final uri = Uri.tryParse(origin);
              if (uri != null) {
                await launchUrl(uri, mode: LaunchMode.externalApplication);
              }
            },
          ),
          ListTile(
            leading: const Icon(Icons.info_outline),
            title: Text(l10n.aboutApp),
            subtitle: Text(l10n.loginFootnote),
            onTap: () {
              showAboutDialog(
                context: context,
                applicationName: l10n.appName,
                applicationVersion: '1.0.0',
                children: [
                  Text('API: ${auth.baseUrl ?? '—'}'),
                  Text(l10n.shopTagline),
                ],
              );
            },
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.logout, color: Colors.red),
            title: Text(l10n.logout, style: const TextStyle(color: Colors.red)),
            onTap: () async {
              final ok = await showDialog<bool>(
                context: context,
                builder: (ctx) => AlertDialog(
                  title: Text(l10n.logoutConfirm),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(ctx, false),
                      child: Text(l10n.cancel),
                    ),
                    FilledButton(
                      onPressed: () => Navigator.pop(ctx, true),
                      child: Text(l10n.logout),
                    ),
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
            title: Text(l10n.logoutAll),
            onTap: () async {
              final ok = await showDialog<bool>(
                context: context,
                builder: (ctx) => AlertDialog(
                  title: Text(l10n.logoutAllConfirm),
                  content: Text(l10n.logoutAllBody),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(ctx, false),
                      child: Text(l10n.cancel),
                    ),
                    FilledButton(
                      onPressed: () => Navigator.pop(ctx, true),
                      child: Text(l10n.confirm),
                    ),
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
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  bool _any(WidgetRef ref, List<String> perms) =>
      perms.any((p) => canAccess(ref, p));

  Widget _section(String title) => Padding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 4),
        child: Text(
          title.toUpperCase(),
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.6,
            color: Colors.grey.shade600,
          ),
        ),
      );

  Widget _item(
    BuildContext context,
    IconData icon,
    String title,
    String route,
  ) {
    return ListTile(
      leading: Icon(icon),
      title: Text(title),
      trailing: const Icon(Icons.chevron_right),
      onTap: () => context.push(route),
    );
  }
}
