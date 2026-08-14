import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../../l10n/app_localizations.dart';

final chatListProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/chat', query: {'status': 'open'});
  final data = env.data;
  if (data is List) {
    return data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }
  if (data is Map && data['data'] is List) {
    return (data['data'] as List)
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
  }
  if (data is Map && data['conversations'] is List) {
    return (data['conversations'] as List)
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
  }
  return [];
});

class ChatListScreen extends ConsumerStatefulWidget {
  const ChatListScreen({super.key});

  @override
  ConsumerState<ChatListScreen> createState() => _ChatListScreenState();
}

class _ChatListScreenState extends ConsumerState<ChatListScreen> {
  Timer? _timer;
  int _badge = 0;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 8), (_) => _pollNotif());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _pollNotif() async {
    try {
      final api = ref.read(apiClientProvider);
      final env =
          await api.get('/admin/chat/notifications', query: {'with_list': 1});
      final data = env.data;
      if (data is Map) {
        final unread = data['unread_count'] ?? data['count'] ?? 0;
        if (mounted) setState(() => _badge = (unread as num).toInt());
      }
      ref.invalidate(chatListProvider);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(chatListProvider);
    final l10n = context.l10n;
    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Text(l10n.navChat),
            if (_badge > 0) ...[
              const SizedBox(width: 8),
              CircleAvatar(
                radius: 11,
                backgroundColor: Colors.red,
                child: Text('$_badge',
                    style: const TextStyle(fontSize: 11, color: Colors.white)),
              ),
            ],
          ],
        ),
        actions: [
          IconButton(
            onPressed: () {
              ref.invalidate(chatListProvider);
              _pollNotif();
            },
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: async.when(
        loading: () => LoadingBody(message: l10n.loading),
        error: (e, _) => ErrorBody(
          error: e,
          onRetry: () => ref.invalidate(chatListProvider),
        ),
        data: (items) {
          if (items.isEmpty) {
            return EmptyBody(
                message: l10n.conversations, icon: Icons.chat_outlined);
          }
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(chatListProvider),
            child: ListView.separated(
              itemCount: items.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final c = items[i];
                final id = c['id'];
                final name = c['guest_name'] ??
                    c['customer_name'] ??
                    c['name'] ??
                    'Khách';
                final last = c['last_message'] is Map
                    ? (c['last_message'] as Map)['body'] ??
                        (c['last_message'] as Map)['message']
                    : c['last_message'] ?? c['preview'];
                return ListTile(
                  leading: const CircleAvatar(child: Icon(Icons.person)),
                  title: Text('$name',
                      style: const TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: Text(
                    '${last ?? ''}\n${formatDate(c['updated_at']?.toString() ?? c['last_message_at']?.toString())}',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  isThreeLine: true,
                  onTap: () => context.push('/chat/$id'),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
