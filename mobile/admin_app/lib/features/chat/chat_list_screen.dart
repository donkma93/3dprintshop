import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../../l10n/app_localizations.dart';

/// Total unread guest messages (for app bar + bottom-nav badge).
final chatUnreadTotalProvider = StateProvider<int>((ref) => 0);

final chatListProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final env = await api.get('/admin/chat', query: {'status': 'open'});
  final data = env.data;
  List raw = const [];
  if (data is List) {
    raw = data;
  } else if (data is Map) {
    if (data['conversations'] is List) {
      raw = data['conversations'] as List;
    } else if (data['data'] is List) {
      raw = data['data'] as List;
    } else if (data['data'] is Map &&
        (data['data'] as Map)['conversations'] is List) {
      raw = (data['data'] as Map)['conversations'] as List;
    }
  }
  final items = raw
      .whereType<Map>()
      .map((e) => Map<String, dynamic>.from(e))
      .toList();

  // Unread first, then latest activity.
  items.sort((a, b) {
    final ua = _unreadOf(a);
    final ub = _unreadOf(b);
    if (ua != ub) return ub.compareTo(ua);
    final ta = a['last_message_at']?.toString() ?? a['updated_at']?.toString() ?? '';
    final tb = b['last_message_at']?.toString() ?? b['updated_at']?.toString() ?? '';
    return tb.compareTo(ta);
  });

  final total = items.fold<int>(0, (sum, c) => sum + _unreadOf(c));
  // Keep bottom-nav badge in sync after list loads.
  Future.microtask(() {
    ref.read(chatUnreadTotalProvider.notifier).state = total;
  });

  return items;
});

int _unreadOf(Map<String, dynamic> c) {
  final v = c['unread_count'] ?? c['unread'] ?? 0;
  if (v is num) return v.toInt();
  return int.tryParse(v.toString()) ?? 0;
}

String _preview(Map<String, dynamic> c) {
  final last = c['last_message'];
  if (last is Map) {
    return '${last['body'] ?? last['message'] ?? last['content'] ?? ''}'.trim();
  }
  return '${c['last_message'] ?? c['preview'] ?? ''}'.trim();
}

class ChatListScreen extends ConsumerStatefulWidget {
  const ChatListScreen({super.key});

  @override
  ConsumerState<ChatListScreen> createState() => _ChatListScreenState();
}

class _ChatListScreenState extends ConsumerState<ChatListScreen> {
  Timer? _timer;

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
          await api.get('/admin/chat/notifications', query: {'with_list': 0});
      final data = env.data;
      if (data is Map) {
        // Open-conversation unread only (matches list badges). Avoid message
        // totals that used to include closed / already-read threads.
        final unread = data['unread_count'] ??
            data['unread_message_count'] ??
            data['count'] ??
            0;
        final n = unread is num
            ? unread.toInt()
            : int.tryParse(unread.toString()) ?? 0;
        if (mounted) {
          ref.read(chatUnreadTotalProvider.notifier).state = n < 0 ? 0 : n;
        }
      }
      ref.invalidate(chatListProvider);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(chatListProvider);
    final badge = ref.watch(chatUnreadTotalProvider);
    final l10n = context.l10n;
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Flexible(
              child: Text(
                l10n.navChat,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (badge > 0) ...[
              const SizedBox(width: 8),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                decoration: BoxDecoration(
                  color: const Color(0xFFDC2626),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  badge > 99 ? '99+' : '$badge',
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                    height: 1.2,
                  ),
                ),
              ),
            ],
          ],
        ),
        actions: [
          IconButton(
            tooltip: l10n.refresh,
            onPressed: () {
              ref.invalidate(chatListProvider);
              _pollNotif();
            },
            icon: const Icon(Icons.refresh_rounded),
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
              message: l10n.conversations,
              icon: Icons.chat_outlined,
            );
          }
          return RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(chatListProvider);
              await ref.read(chatListProvider.future);
            },
            child: ListView.separated(
              physics: const BouncingScrollPhysics(
                parent: AlwaysScrollableScrollPhysics(),
              ),
              itemCount: items.length,
              separatorBuilder: (_, __) => Divider(
                height: 1,
                thickness: 1,
                color: Colors.grey.shade200,
              ),
              itemBuilder: (context, i) {
                final c = items[i];
                final id = c['id'];
                final name = '${c['guest_name'] ?? c['customer_name'] ?? c['name'] ?? 'Khách'}';
                final last = _preview(c);
                final unread = _unreadOf(c);
                final hasUnread = unread > 0;
                final when = formatDate(
                  c['last_message_at']?.toString() ??
                      c['updated_at']?.toString(),
                );

                final nameStyle = theme.textTheme.titleSmall?.copyWith(
                  fontSize: 15,
                  fontWeight: hasUnread ? FontWeight.w800 : FontWeight.w500,
                  color: hasUnread
                      ? const Color(0xFF0F172A)
                      : const Color(0xFF334155),
                  height: 1.25,
                );
                final previewStyle = theme.textTheme.bodySmall?.copyWith(
                  fontSize: 13,
                  fontWeight: hasUnread ? FontWeight.w700 : FontWeight.w400,
                  color: hasUnread
                      ? const Color(0xFF1E293B)
                      : const Color(0xFF64748B),
                  height: 1.3,
                );
                final timeStyle = theme.textTheme.labelSmall?.copyWith(
                  fontSize: 11,
                  fontWeight: hasUnread ? FontWeight.w700 : FontWeight.w400,
                  color: hasUnread
                      ? const Color(0xFF2563EB)
                      : const Color(0xFF94A3B8),
                );

                return Material(
                  color: hasUnread
                      ? const Color(0xFFEFF6FF)
                      : Colors.transparent,
                  child: InkWell(
                    onTap: () async {
                      await context.push('/chat/$id');
                      if (mounted) {
                        ref.invalidate(chatListProvider);
                        _pollNotif();
                      }
                    },
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 12,
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Stack(
                            clipBehavior: Clip.none,
                            children: [
                              CircleAvatar(
                                radius: 22,
                                backgroundColor: hasUnread
                                    ? const Color(0xFF2563EB)
                                        .withValues(alpha: 0.15)
                                    : const Color(0xFFE2E8F0),
                                child: Icon(
                                  Icons.person_rounded,
                                  size: 22,
                                  color: hasUnread
                                      ? const Color(0xFF2563EB)
                                      : const Color(0xFF64748B),
                                ),
                              ),
                              if (hasUnread)
                                Positioned(
                                  right: -2,
                                  top: -2,
                                  child: Container(
                                    constraints: const BoxConstraints(
                                      minWidth: 18,
                                      minHeight: 18,
                                    ),
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 5,
                                    ),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFDC2626),
                                      borderRadius: BorderRadius.circular(999),
                                      border: Border.all(
                                        color: Colors.white,
                                        width: 1.5,
                                      ),
                                    ),
                                    alignment: Alignment.center,
                                    child: Text(
                                      unread > 99 ? '99+' : '$unread',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 10,
                                        fontWeight: FontWeight.w800,
                                        height: 1.1,
                                      ),
                                    ),
                                  ),
                                ),
                            ],
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        name,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: nameStyle,
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Text(when, style: timeStyle),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Expanded(
                                      child: Text(
                                        last.isEmpty ? '—' : last,
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                        style: previewStyle,
                                      ),
                                    ),
                                    if (hasUnread) ...[
                                      const SizedBox(width: 8),
                                      Container(
                                        margin: const EdgeInsets.only(top: 2),
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 7,
                                          vertical: 3,
                                        ),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFDC2626),
                                          borderRadius:
                                              BorderRadius.circular(999),
                                        ),
                                        child: Text(
                                          unread > 99 ? '99+' : '$unread',
                                          style: const TextStyle(
                                            color: Colors.white,
                                            fontSize: 11,
                                            fontWeight: FontWeight.w800,
                                            height: 1.1,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
