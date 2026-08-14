import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../l10n/app_localizations.dart';
import 'async_body.dart';

typedef ItemBuilder = Widget Function(
  BuildContext context,
  Map<String, dynamic> item,
  int index,
);

class ResourceListScaffold extends ConsumerWidget {
  final String title;
  final AsyncValue<List<Map<String, dynamic>>> async;
  final VoidCallback onRefresh;
  final ItemBuilder itemBuilder;
  final VoidCallback? onCreate;
  final String? searchHint;
  final ValueChanged<String>? onSearch;
  final TextEditingController? searchController;
  final List<Widget>? actions;
  final String? emptyMessage;

  const ResourceListScaffold({
    super.key,
    required this.title,
    required this.async,
    required this.onRefresh,
    required this.itemBuilder,
    this.onCreate,
    this.searchHint,
    this.onSearch,
    this.searchController,
    this.actions,
    this.emptyMessage,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(
        title: Text(title),
        actions: [
          ...?actions,
          IconButton(
            tooltip: l10n.refresh,
            onPressed: onRefresh,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      floatingActionButton: onCreate == null
          ? null
          : FloatingActionButton(
              onPressed: onCreate,
              child: const Icon(Icons.add),
            ),
      body: Column(
        children: [
          if (searchController != null)
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
              child: TextField(
                controller: searchController,
                decoration: InputDecoration(
                  hintText: searchHint ?? l10n.search,
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: IconButton(
                    icon: const Icon(Icons.clear),
                    onPressed: () {
                      searchController!.clear();
                      onSearch?.call('');
                    },
                  ),
                ),
                onSubmitted: onSearch,
                textInputAction: TextInputAction.search,
              ),
            ),
          Expanded(
            child: async.when(
              loading: () => LoadingBody(message: l10n.loading),
              error: (e, _) => ErrorBody(error: e, onRetry: onRefresh),
              data: (items) {
                if (items.isEmpty) {
                  return EmptyBody(message: emptyMessage ?? l10n.noItems);
                }
                return RefreshIndicator(
                  onRefresh: () async => onRefresh(),
                  child: ListView.separated(
                    padding: const EdgeInsets.all(12),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, i) =>
                        itemBuilder(context, items[i], i),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class FormScaffold extends StatelessWidget {
  final String title;
  final List<Widget> children;
  final VoidCallback? onSave;
  final bool saving;
  final List<Widget>? actions;

  const FormScaffold({
    super.key,
    required this.title,
    required this.children,
    this.onSave,
    this.saving = false,
    this.actions,
  });

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(
        title: Text(title),
        actions: actions,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          ...children,
          const SizedBox(height: 24),
          if (onSave != null)
            FilledButton(
              onPressed: saving ? null : onSave,
              child: saving
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : Text(l10n.save),
            ),
        ],
      ),
    );
  }
}

Future<bool> confirmAction(
  BuildContext context, {
  required String title,
  String? body,
}) async {
  final l10n = context.l10n;
  final ok = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text(title),
      content: body == null ? null : Text(body),
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
  return ok == true;
}
