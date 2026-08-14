import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/responsive.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../../core/widgets/fade_slide_in.dart';
import '../../l10n/app_localizations.dart';
import '../auth/auth_controller.dart';

final dashboardProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/dashboard');
  return Map<String, dynamic>.from(env.data as Map? ?? {});
});

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final async = ref.watch(dashboardProvider);
    final canRevenue = auth.user?.canViewRevenue ?? false;
    final l10n = context.l10n;
    final scheme = Theme.of(context).colorScheme;
    final r = R.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          l10n.navDashboard,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        actions: [
          IconButton(
            tooltip: l10n.refresh,
            onPressed: () => ref.invalidate(dashboardProvider),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: R.clampTextScale(
        context,
        async.when(
          loading: () => LoadingBody(message: l10n.loading),
          error: (e, _) => ErrorBody(
            error: e,
            onRetry: () => ref.invalidate(dashboardProvider),
          ),
          data: (data) {
            final stats =
                Map<String, dynamic>.from(data['stats'] as Map? ?? {});
            final charts =
                Map<String, dynamic>.from(data['charts'] as Map? ?? {});
            final canView = data['can_view_revenue'] == true || canRevenue;
            final lowStock = _asMapList(data['low_stock']);

            final months = _asStringList(charts['months']);
            final productsCreated = _asNumList(charts['products_created']);
            final inputsCount = _asNumList(charts['inputs_count']);
            final inputsSpend = _asNumList(charts['inputs_spend']);

            final stockOverview = Map<String, dynamic>.from(
              charts['stock_overview'] as Map? ?? {},
            );
            final stockValues = _asNumList(stockOverview['values']);
            final stockLabels = [
              l10n.stockActiveProducts,
              l10n.stockHiddenProducts,
              l10n.stockOkMaterials,
              l10n.stockLowMaterials,
            ];

            final categories = Map<String, dynamic>.from(
              charts['categories'] as Map? ?? {},
            );
            final categoryLabels = _asStringList(categories['labels']);
            final categoryValues = _asNumList(categories['values']);

            final assetBreakdown = Map<String, dynamic>.from(
              charts['asset_breakdown'] as Map? ?? {},
            );
            final assetValues = _asNumList(assetBreakdown['values']);
            final assetLabels = [
              l10n.assetMaterialStock,
              l10n.assetEquipment,
              l10n.assetCatalogSales,
              l10n.assetCatalogCost,
            ];

            var anim = 0;
            Widget section(Widget child) => FadeSlideIn(
                  index: anim++,
                  child: child,
                );

            return RefreshIndicator(
              displacement: 48,
              strokeWidth: 2.4,
              onRefresh: () async {
                ref.invalidate(dashboardProvider);
                await ref.read(dashboardProvider.future);
              },
              child: CustomScrollView(
                physics: const BouncingScrollPhysics(
                  parent: AlwaysScrollableScrollPhysics(),
                ),
                slivers: [
                  // Header
                  SliverPadding(
                    padding: EdgeInsets.fromLTRB(
                      r.pagePadding,
                      r.pagePadding,
                      r.pagePadding,
                      4,
                    ),
                    sliver: SliverToBoxAdapter(
                      child: section(
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              auth.user?.name ?? '',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: Theme.of(context)
                                  .textTheme
                                  .titleMedium
                                  ?.copyWith(fontWeight: FontWeight.w800),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              auth.user?.roleLabel ?? '',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),

                  // KPI grid
                  SliverPadding(
                    padding: EdgeInsets.fromLTRB(
                      r.pagePadding,
                      12,
                      r.pagePadding,
                      0,
                    ),
                    sliver: SliverGrid(
                      gridDelegate: _kpiDelegate(r),
                      delegate: SliverChildListDelegate.fixed([
                        section(_StatCard(
                          title: l10n.productsCount,
                          value: '${stats['products'] ?? '—'}',
                          subtitle:
                              '${stats['active_products'] ?? 0} ${l10n.activeProducts}',
                          icon: Icons.inventory_2_outlined,
                          accent: const Color(0xFF2563EB),
                        )),
                        section(_StatCard(
                          title: l10n.materialsCount,
                          value: '${stats['materials'] ?? '—'}',
                          subtitle:
                              '${stats['low_stock_materials'] ?? 0} ${l10n.lowStock}',
                          icon: Icons.water_drop_outlined,
                          accent: const Color(0xFFCA8A04),
                        )),
                        section(_StatCard(
                          title: l10n.equipmentCount,
                          value: '${stats['equipment'] ?? '—'}',
                          subtitle:
                              '${stats['material_inputs'] ?? 0} ${l10n.materialInputsCount}',
                          icon: Icons.print_outlined,
                          accent: const Color(0xFF7C3AED),
                        )),
                        section(_StatCard(
                          title: l10n.openChats,
                          value: '${stats['open_chats'] ?? '—'}',
                          subtitle:
                              '${stats['categories'] ?? 0} ${l10n.categoriesCount}',
                          icon: Icons.chat_outlined,
                          accent: const Color(0xFF0F766E),
                        )),
                      ]),
                    ),
                  ),

                  // Money / restricted
                  if (canView)
                    SliverPadding(
                      padding: EdgeInsets.fromLTRB(
                        r.pagePadding,
                        10,
                        r.pagePadding,
                        0,
                      ),
                      sliver: SliverGrid(
                        gridDelegate: _kpiDelegate(r),
                        delegate: SliverChildListDelegate.fixed([
                          section(_MoneyCard(
                            title: l10n.materialStockValue,
                            value: formatMoney(stats['material_stock_value']),
                            accent: const Color(0xFF0F766E),
                          )),
                          section(_MoneyCard(
                            title: l10n.equipmentValue,
                            value: formatMoney(stats['equipment_value']),
                            accent: const Color(0xFFCA8A04),
                          )),
                          section(_MoneyCard(
                            title: l10n.catalogSalesValue,
                            value: formatMoney(stats['catalog_sales_value']),
                            subtitle:
                                '${l10n.potentialMargin}: ${formatMoney(stats['potential_margin'])}',
                            accent: const Color(0xFF2563EB),
                          )),
                          section(_MoneyCard(
                            title: l10n.inputsTotal30d,
                            value: formatMoney(stats['inputs_total_30d']),
                            subtitle:
                                '${l10n.inputsTotalAll}: ${formatMoney(stats['inputs_total_all'])}',
                            accent: const Color(0xFFDC2626),
                          )),
                        ]),
                      ),
                    )
                  else
                    SliverPadding(
                      padding: EdgeInsets.fromLTRB(
                        r.pagePadding,
                        10,
                        r.pagePadding,
                        0,
                      ),
                      sliver: SliverToBoxAdapter(
                        child: section(
                          Card(
                            color: scheme.surfaceContainerHighest
                                .withValues(alpha: 0.55),
                            child: Padding(
                              padding: const EdgeInsets.all(14),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Icon(Icons.shield_outlined,
                                      size: 20, color: Colors.grey.shade600),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      l10n.revenueRestricted,
                                      style: Theme.of(context)
                                          .textTheme
                                          .bodySmall
                                          ?.copyWith(
                                            color: Colors.grey.shade700,
                                            height: 1.4,
                                          ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),

                  // Charts
                  SliverPadding(
                    padding: EdgeInsets.fromLTRB(
                      r.pagePadding,
                      14,
                      r.pagePadding,
                      0,
                    ),
                    sliver: SliverList(
                      delegate: SliverChildListDelegate([
                        section(_ChartCard(
                          title: l10n.chartActivity,
                          chartHeight: r.isPhone ? 220 : 240,
                          child: months.isEmpty
                              ? _EmptyChart(l10n.noChartData)
                              : _GroupedBarChart(
                                  labels: months,
                                  series: [
                                    _BarSeries(
                                      label: l10n.chartProductsCreated,
                                      values: productsCreated,
                                      color: const Color(0xFF2563EB),
                                    ),
                                    _BarSeries(
                                      label: l10n.chartInputsCount,
                                      values: inputsCount,
                                      color: const Color(0xFF0F766E),
                                    ),
                                  ],
                                ),
                        )),
                        SizedBox(height: r.gap + 2),
                        section(_ChartCard(
                          title: l10n.chartStockOverview,
                          chartHeight: r.isPhone ? 200 : 220,
                          child: stockValues.every((v) => v == 0)
                              ? _EmptyChart(l10n.noChartData)
                              : _PieChartBlock(
                                  labels: stockLabels,
                                  values: stockValues,
                                  colors: const [
                                    Color(0xFF2563EB),
                                    Color(0xFF94A3B8),
                                    Color(0xFF16A34A),
                                    Color(0xFFF59E0B),
                                  ],
                                  compact: r.isPhone,
                                ),
                        )),
                        SizedBox(height: r.gap + 2),
                        section(_ChartCard(
                          title: l10n.chartCategories,
                          chartHeight: r.isPhone
                              ? (categoryLabels.isEmpty
                                  ? 120.0
                                  : (categoryLabels.length * 36.0)
                                      .clamp(120.0, 240.0))
                              : 240,
                          child: categoryLabels.isEmpty
                              ? _EmptyChart(l10n.noChartData)
                              : _HorizontalBarChart(
                                  labels: categoryLabels,
                                  values: categoryValues,
                                  color: const Color(0xFF7C3AED),
                                ),
                        )),
                        if (canView) ...[
                          SizedBox(height: r.gap + 2),
                          section(_ChartCard(
                            title: l10n.chartInputsSpend,
                            chartHeight: r.isPhone ? 210 : 230,
                            child: months.isEmpty || inputsSpend.isEmpty
                                ? _EmptyChart(l10n.noChartData)
                                : _LineChartBlock(
                                    labels: months,
                                    values: inputsSpend,
                                    color: const Color(0xFFDC2626),
                                    money: true,
                                  ),
                          )),
                          SizedBox(height: r.gap + 2),
                          section(_ChartCard(
                            title: l10n.chartAssetBreakdown,
                            chartHeight: r.isPhone ? 200 : 220,
                            child: assetValues.every((v) => v == 0)
                                ? _EmptyChart(l10n.noChartData)
                                : _PieChartBlock(
                                    labels: assetLabels,
                                    values: assetValues,
                                    colors: const [
                                      Color(0xFF0F766E),
                                      Color(0xFFCA8A04),
                                      Color(0xFF2563EB),
                                      Color(0xFFDC2626),
                                    ],
                                    money: true,
                                    compact: r.isPhone,
                                  ),
                          )),
                        ],
                      ]),
                    ),
                  ),

                  // Low stock
                  if (lowStock.isNotEmpty)
                    SliverPadding(
                      padding: EdgeInsets.fromLTRB(
                        r.pagePadding,
                        16,
                        r.pagePadding,
                        0,
                      ),
                      sliver: SliverList(
                        delegate: SliverChildListDelegate([
                          section(
                            Text(
                              l10n.lowStockMaterials,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: Theme.of(context).textTheme.titleSmall,
                            ),
                          ),
                          const SizedBox(height: 8),
                          for (var i = 0; i < lowStock.length; i++)
                            section(_LowStockTile(
                              material: lowStock[i],
                              minStockLabel: l10n.minStock,
                            )),
                        ]),
                      ),
                    ),

                  const SliverToBoxAdapter(child: SizedBox(height: 28)),
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  static SliverGridDelegate _kpiDelegate(R r) {
    return SliverGridDelegateWithFixedCrossAxisCount(
      crossAxisCount: r.kpiColumns,
      mainAxisSpacing: r.gap,
      crossAxisSpacing: r.gap,
      // Fixed main-axis extent avoids uneven card heights / row skew.
      mainAxisExtent: r.isPhone ? 118 : 124,
    );
  }
}

// ── helpers ──────────────────────────────────────────────────────────

List<Map<String, dynamic>> _asMapList(dynamic raw) {
  if (raw is! List) return const [];
  return raw
      .whereType<Map>()
      .map((e) => Map<String, dynamic>.from(e))
      .toList();
}

List<String> _asStringList(dynamic raw) {
  if (raw is! List) return const [];
  return raw.map((e) => e?.toString() ?? '').toList();
}

List<double> _asNumList(dynamic raw) {
  if (raw is! List) return const [];
  return raw.map((e) {
    if (e is num) return e.toDouble();
    return double.tryParse(e?.toString() ?? '') ?? 0;
  }).toList();
}

// ── cards ────────────────────────────────────────────────────────────

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final String? subtitle;
  final IconData icon;
  final Color accent;

  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.accent,
    this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return PressableScale(
      child: SizedBox.expand(
        child: Card(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 34,
                      height: 34,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: accent.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(icon, color: accent, size: 18),
                    ),
                    const Spacer(),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.labelMedium?.copyWith(
                    color: Colors.grey.shade600,
                  ),
                ),
                const SizedBox(height: 2),
                Expanded(
                  child: Align(
                    alignment: Alignment.centerLeft,
                    child: FittedBox(
                      fit: BoxFit.scaleDown,
                      alignment: Alignment.centerLeft,
                      child: Text(
                        value,
                        maxLines: 1,
                        style: theme.textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w800,
                          fontSize: 22,
                          height: 1.1,
                        ),
                      ),
                    ),
                  ),
                ),
                if (subtitle != null)
                  Text(
                    subtitle!,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.labelSmall?.copyWith(
                      color: Colors.grey.shade600,
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _MoneyCard extends StatelessWidget {
  final String title;
  final String value;
  final String? subtitle;
  final Color accent;

  const _MoneyCard({
    required this.title,
    required this.value,
    required this.accent,
    this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return PressableScale(
      child: SizedBox.expand(
        child: Card(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
            side: BorderSide(color: accent.withValues(alpha: 0.28)),
          ),
          child: Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(14),
              border: Border(left: BorderSide(color: accent, width: 4)),
            ),
            padding: const EdgeInsets.fromLTRB(12, 10, 10, 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.labelSmall?.copyWith(
                    color: Colors.grey.shade600,
                    height: 1.25,
                  ),
                ),
                const Spacer(),
                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Text(
                    value,
                    maxLines: 1,
                    style: theme.textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                      fontSize: 15,
                      height: 1.15,
                    ),
                  ),
                ),
                if (subtitle != null) ...[
                  const SizedBox(height: 3),
                  Text(
                    subtitle!,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.labelSmall?.copyWith(
                      color: Colors.grey.shade600,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ChartCard extends StatelessWidget {
  final String title;
  final Widget child;
  final double chartHeight;

  const _ChartCard({
    required this.title,
    required this.child,
    this.chartHeight = 240,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 12, 12, 10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 10),
            SizedBox(height: chartHeight, width: double.infinity, child: child),
          ],
        ),
      ),
    );
  }
}

class _EmptyChart extends StatelessWidget {
  final String message;
  const _EmptyChart(this.message);

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Text(
        message,
        textAlign: TextAlign.center,
        style: Theme.of(context).textTheme.bodySmall,
      ),
    );
  }
}

class _LowStockTile extends StatelessWidget {
  final Map<String, dynamic> material;
  final String minStockLabel;

  const _LowStockTile({
    required this.material,
    required this.minStockLabel,
  });

  @override
  Widget build(BuildContext context) {
    final name = '${material['name'] ?? material['title'] ?? '—'}';
    final qty = material['stock_quantity'] ?? material['stock'] ?? '—';
    final unit = material['unit'] ?? '';
    final min = material['min_stock'];
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Card(
        child: ListTile(
          leading: Container(
            width: 40,
            height: 40,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: const Color(0xFFF59E0B).withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.warning_amber_rounded,
              color: Color(0xFFCA8A04),
              size: 20,
            ),
          ),
          title: Text(
            name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: theme.textTheme.titleSmall?.copyWith(fontSize: 13.5),
          ),
          subtitle: min != null
              ? Text(
                  '$minStockLabel: $min $unit',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.bodySmall,
                )
              : null,
          trailing: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 88),
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(
                '$qty $unit',
                maxLines: 1,
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  color: Color(0xFFCA8A04),
                  fontSize: 13,
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// ── chart widgets ────────────────────────────────────────────────────

class _BarSeries {
  final String label;
  final List<double> values;
  final Color color;
  const _BarSeries({
    required this.label,
    required this.values,
    required this.color,
  });
}

class _GroupedBarChart extends StatelessWidget {
  final List<String> labels;
  final List<_BarSeries> series;

  const _GroupedBarChart({required this.labels, required this.series});

  @override
  Widget build(BuildContext context) {
    final n = labels.length;
    if (n == 0) return const SizedBox.shrink();
    final narrow = MediaQuery.sizeOf(context).width < 400;
    final groups = <BarChartGroupData>[];
    for (var i = 0; i < n; i++) {
      final rods = <BarChartRodData>[];
      for (var s = 0; s < series.length; s++) {
        final vals = series[s].values;
        final v = i < vals.length ? vals[i] : 0.0;
        rods.add(
          BarChartRodData(
            toY: v,
            color: series[s].color,
            width: narrow ? 7 : 10,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(3)),
          ),
        );
      }
      groups.add(
        BarChartGroupData(x: i, barRods: rods, barsSpace: narrow ? 2 : 3),
      );
    }

    double maxY = 0;
    for (final s in series) {
      for (final v in s.values) {
        if (v > maxY) maxY = v;
      }
    }
    maxY = maxY <= 0 ? 4 : (maxY * 1.25).ceilToDouble();

    return Column(
      children: [
        Expanded(
          child: BarChart(
            BarChartData(
              maxY: maxY,
              barGroups: groups,
              alignment: BarChartAlignment.spaceAround,
              gridData: FlGridData(
                show: true,
                drawVerticalLine: false,
                getDrawingHorizontalLine: (v) => FlLine(
                  color: Colors.grey.shade200,
                  strokeWidth: 1,
                ),
              ),
              borderData: FlBorderData(show: false),
              titlesData: FlTitlesData(
                topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false)),
                rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false)),
                leftTitles: AxisTitles(
                  sideTitles: SideTitles(
                    showTitles: true,
                    reservedSize: narrow ? 24 : 28,
                    getTitlesWidget: (v, m) => Text(
                      v.toInt().toString(),
                      style: TextStyle(
                        fontSize: 9,
                        color: Colors.grey.shade600,
                        height: 1,
                      ),
                    ),
                  ),
                ),
                bottomTitles: AxisTitles(
                  sideTitles: SideTitles(
                    showTitles: true,
                    reservedSize: 26,
                    getTitlesWidget: (v, m) {
                      final i = v.toInt();
                      if (i < 0 || i >= labels.length) {
                        return const SizedBox.shrink();
                      }
                      return Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          labels[i],
                          maxLines: 1,
                          overflow: TextOverflow.clip,
                          style: TextStyle(
                            fontSize: narrow ? 8 : 9,
                            color: Colors.grey.shade600,
                            height: 1,
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ),
              barTouchData: BarTouchData(
                touchTooltipData: BarTouchTooltipData(
                  fitInsideHorizontally: true,
                  fitInsideVertically: true,
                  getTooltipItem: (group, groupIndex, rod, rodIndex) {
                    final s =
                        rodIndex < series.length ? series[rodIndex] : null;
                    return BarTooltipItem(
                      '${s?.label ?? ''}: ${rod.toY.toInt()}',
                      const TextStyle(color: Colors.white, fontSize: 11),
                    );
                  },
                ),
              ),
            ),
            duration: const Duration(milliseconds: 450),
            curve: Curves.easeOutCubic,
          ),
        ),
        const SizedBox(height: 6),
        Wrap(
          spacing: 10,
          runSpacing: 4,
          alignment: WrapAlignment.center,
          children: series
              .map(
                (s) => Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 9,
                      height: 9,
                      decoration: BoxDecoration(
                        color: s.color,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      s.label,
                      style: const TextStyle(fontSize: 10, height: 1.1),
                    ),
                  ],
                ),
              )
              .toList(),
        ),
      ],
    );
  }
}

class _HorizontalBarChart extends StatelessWidget {
  final List<String> labels;
  final List<double> values;
  final Color color;

  const _HorizontalBarChart({
    required this.labels,
    required this.values,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    final n = labels.length;
    double maxV = 0;
    for (final v in values) {
      if (v > maxV) maxV = v;
    }
    if (maxV <= 0) maxV = 1;

    return LayoutBuilder(
      builder: (context, constraints) {
        return SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          child: Column(
            children: [
              for (var i = 0; i < n; i++) ...[
                Builder(builder: (context) {
                  final v = i < values.length ? values[i] : 0.0;
                  final label = labels[i];
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                label,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 12,
                                  height: 1.2,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Text(
                              v.toInt().toString(),
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                height: 1.2,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(4),
                          child: TweenAnimationBuilder<double>(
                            tween: Tween(begin: 0, end: (v / maxV).clamp(0.0, 1.0)),
                            duration: Duration(milliseconds: 500 + i * 40),
                            curve: Curves.easeOutCubic,
                            builder: (context, value, _) {
                              return LinearProgressIndicator(
                                value: value,
                                minHeight: 8,
                                backgroundColor: color.withValues(alpha: 0.12),
                                color: color,
                              );
                            },
                          ),
                        ),
                      ],
                    ),
                  );
                }),
              ],
            ],
          ),
        );
      },
    );
  }
}

class _PieChartBlock extends StatelessWidget {
  final List<String> labels;
  final List<double> values;
  final List<Color> colors;
  final bool money;
  final bool compact;

  const _PieChartBlock({
    required this.labels,
    required this.values,
    required this.colors,
    this.money = false,
    this.compact = false,
  });

  @override
  Widget build(BuildContext context) {
    final total = values.fold<double>(0, (a, b) => a + b);
    if (total <= 0) return const SizedBox.shrink();

    final sections = <PieChartSectionData>[];
    for (var i = 0; i < values.length; i++) {
      final v = values[i];
      if (v <= 0) continue;
      final c = colors[i % colors.length];
      sections.add(
        PieChartSectionData(
          value: v,
          color: c,
          radius: compact ? 44 : 52,
          title: total > 0 ? '${((v / total) * 100).round()}%' : '',
          titleStyle: TextStyle(
            fontSize: compact ? 10 : 11,
            fontWeight: FontWeight.w700,
            color: Colors.white,
            height: 1,
          ),
        ),
      );
    }

    final chart = PieChart(
      PieChartData(
        sections: sections,
        centerSpaceRadius: compact ? 22 : 28,
        sectionsSpace: 2,
        borderData: FlBorderData(show: false),
      ),
      duration: const Duration(milliseconds: 500),
      curve: Curves.easeOutCubic,
    );

    final legend = Column(
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (var i = 0; i < labels.length; i++)
          if (i < values.length)
            Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 9,
                    height: 9,
                    margin: const EdgeInsets.only(top: 3),
                    decoration: BoxDecoration(
                      color: colors[i % colors.length],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      '${labels[i]}\n${money ? formatMoney(values[i]) : values[i].toInt()}',
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: compact ? 10 : 11,
                        height: 1.25,
                      ),
                    ),
                  ),
                ],
              ),
            ),
      ],
    );

    // On very narrow widths, stack pie above legend to avoid horizontal squash.
    final narrow = MediaQuery.sizeOf(context).width < 360;
    if (narrow) {
      return Column(
        children: [
          Expanded(flex: 3, child: chart),
          const SizedBox(height: 6),
          Expanded(
            flex: 2,
            child: SingleChildScrollView(child: legend),
          ),
        ],
      );
    }

    return Row(
      children: [
        Expanded(flex: 5, child: chart),
        const SizedBox(width: 8),
        Expanded(
          flex: 5,
          child: SingleChildScrollView(child: legend),
        ),
      ],
    );
  }
}

class _LineChartBlock extends StatelessWidget {
  final List<String> labels;
  final List<double> values;
  final Color color;
  final bool money;

  const _LineChartBlock({
    required this.labels,
    required this.values,
    required this.color,
    this.money = false,
  });

  @override
  Widget build(BuildContext context) {
    final spots = <FlSpot>[];
    for (var i = 0; i < values.length; i++) {
      spots.add(FlSpot(i.toDouble(), values[i]));
    }
    double maxY = 0;
    for (final v in values) {
      if (v > maxY) maxY = v;
    }
    maxY = maxY <= 0 ? 1000 : maxY * 1.2;
    final narrow = MediaQuery.sizeOf(context).width < 400;

    return LineChart(
      LineChartData(
        minY: 0,
        maxY: maxY,
        gridData: FlGridData(
          show: true,
          drawVerticalLine: false,
          getDrawingHorizontalLine: (v) => FlLine(
            color: Colors.grey.shade200,
            strokeWidth: 1,
          ),
        ),
        borderData: FlBorderData(show: false),
        titlesData: FlTitlesData(
          topTitles:
              const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          rightTitles:
              const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          leftTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: narrow ? 36 : 44,
              getTitlesWidget: (v, m) {
                final text = money
                    ? (v >= 1000000
                        ? '${(v / 1000000).toStringAsFixed(1)}M'
                        : v >= 1000
                            ? '${(v / 1000).toStringAsFixed(0)}K'
                            : v.toInt().toString())
                    : v.toInt().toString();
                return Text(
                  text,
                  maxLines: 1,
                  style: TextStyle(
                    fontSize: 9,
                    color: Colors.grey.shade600,
                    height: 1,
                  ),
                );
              },
            ),
          ),
          bottomTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 26,
              getTitlesWidget: (v, m) {
                final i = v.toInt();
                if (i < 0 || i >= labels.length) {
                  return const SizedBox.shrink();
                }
                return Padding(
                  padding: const EdgeInsets.only(top: 6),
                  child: Text(
                    labels[i],
                    maxLines: 1,
                    style: TextStyle(
                      fontSize: narrow ? 8 : 9,
                      color: Colors.grey.shade600,
                      height: 1,
                    ),
                  ),
                );
              },
            ),
          ),
        ),
        lineTouchData: LineTouchData(
          touchTooltipData: LineTouchTooltipData(
            fitInsideHorizontally: true,
            fitInsideVertically: true,
            getTooltipItems: (touched) => touched.map((t) {
              final text = money ? formatMoney(t.y) : t.y.toInt().toString();
              return LineTooltipItem(
                text,
                const TextStyle(color: Colors.white, fontSize: 11),
              );
            }).toList(),
          ),
        ),
        lineBarsData: [
          LineChartBarData(
            spots: spots,
            isCurved: true,
            color: color,
            barWidth: 3,
            dotData: FlDotData(
              show: true,
              getDotPainter: (spot, percent, bar, index) =>
                  FlDotCirclePainter(
                radius: 3.5,
                color: color,
                strokeWidth: 1.5,
                strokeColor: Colors.white,
              ),
            ),
            belowBarData: BarAreaData(
              show: true,
              color: color.withValues(alpha: 0.12),
            ),
          ),
        ],
      ),
      duration: const Duration(milliseconds: 500),
      curve: Curves.easeOutCubic,
    );
  }
}
