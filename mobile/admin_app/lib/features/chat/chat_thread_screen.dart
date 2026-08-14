import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_envelope.dart';
import '../../core/providers.dart';
import '../../core/widgets/async_body.dart';

class ChatThreadScreen extends ConsumerStatefulWidget {
  final String conversationId;
  const ChatThreadScreen({super.key, required this.conversationId});

  @override
  ConsumerState<ChatThreadScreen> createState() => _ChatThreadScreenState();
}

class _ChatThreadScreenState extends ConsumerState<ChatThreadScreen> {
  final _msgCtrl = TextEditingController();
  final _scroll = ScrollController();
  final List<Map<String, dynamic>> _messages = [];
  int? _afterId;
  bool _loading = true;
  bool _sending = false;
  String? _error;
  Timer? _poll;

  @override
  void initState() {
    super.initState();
    _load();
    _poll = Timer.periodic(const Duration(seconds: 3), (_) => _pollNew());
  }

  @override
  void dispose() {
    _poll?.cancel();
    _msgCtrl.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final api = ref.read(apiClientProvider);
      final env = await api.get('/admin/chat/${widget.conversationId}');
      final data = Map<String, dynamic>.from(env.data as Map? ?? {});
      final list = <Map<String, dynamic>>[];
      final raw = data['messages'] ?? data['data'];
      if (raw is List) {
        for (final m in raw) {
          list.add(Map<String, dynamic>.from(m as Map));
        }
      }
      int? maxId;
      for (final m in list) {
        final id = (m['id'] as num?)?.toInt();
        if (id != null && (maxId == null || id > maxId)) maxId = id;
      }
      setState(() {
        _messages
          ..clear()
          ..addAll(list);
        _afterId = maxId;
        _loading = false;
      });
      _scrollToEnd();
    } catch (e) {
      setState(() {
        _loading = false;
        _error = e is ApiException ? e.message : '$e';
      });
    }
  }

  Future<void> _pollNew() async {
    if (_afterId == null) return;
    try {
      final api = ref.read(apiClientProvider);
      final env = await api.get(
        '/admin/chat/${widget.conversationId}/poll',
        query: {'after_id': _afterId},
      );
      final data = env.data;
      List list = [];
      if (data is Map && data['messages'] is List) {
        list = data['messages'] as List;
      } else if (data is List) {
        list = data;
      }
      if (list.isEmpty) return;
      final added = <Map<String, dynamic>>[];
      for (final m in list) {
        final map = Map<String, dynamic>.from(m as Map);
        added.add(map);
        final id = (map['id'] as num?)?.toInt();
        if (id != null && (_afterId == null || id > _afterId!)) {
          _afterId = id;
        }
      }
      if (added.isNotEmpty && mounted) {
        setState(() => _messages.addAll(added));
        _scrollToEnd();
      }
    } catch (_) {}
  }

  void _scrollToEnd() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.animateTo(
          _scroll.position.maxScrollExtent + 80,
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _send() async {
    final text = _msgCtrl.text.trim();
    if (text.isEmpty) return;
    setState(() => _sending = true);
    try {
      final api = ref.read(apiClientProvider);
      await api.post('/admin/chat/${widget.conversationId}/reply', data: {
        'message': text,
      });
      _msgCtrl.clear();
      await _pollNew();
      // fallback reload if poll empty
      if (_messages.isEmpty) await _load();
    } catch (e) {
      if (mounted) {
        showSnack(context, e is ApiException ? e.message : '$e', error: true);
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Chat #${widget.conversationId}')),
      body: Column(
        children: [
          Expanded(
            child: _loading
                ? const LoadingBody()
                : _error != null
                    ? ErrorBody(error: _error!, onRetry: _load)
                    : ListView.builder(
                        controller: _scroll,
                        padding: const EdgeInsets.all(12),
                        itemCount: _messages.length,
                        itemBuilder: (context, i) {
                          final m = _messages[i];
                          final isAdmin = m['sender_type'] == 'admin' ||
                              m['is_admin'] == true ||
                              m['from'] == 'admin';
                          final body = m['body'] ??
                              m['message'] ??
                              m['content'] ??
                              '';
                          return Align(
                            alignment: isAdmin
                                ? Alignment.centerRight
                                : Alignment.centerLeft,
                            child: Container(
                              margin: const EdgeInsets.only(bottom: 8),
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 12, vertical: 8),
                              constraints: BoxConstraints(
                                maxWidth:
                                    MediaQuery.of(context).size.width * 0.78,
                              ),
                              decoration: BoxDecoration(
                                color: isAdmin
                                    ? const Color(0xFF0F172A)
                                    : Colors.grey.shade200,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                '$body',
                                style: TextStyle(
                                  color:
                                      isAdmin ? Colors.white : Colors.black87,
                                ),
                              ),
                            ),
                          );
                        },
                      ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(8, 4, 8, 8),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _msgCtrl,
                      decoration: const InputDecoration(
                        hintText: 'Nhập trả lời…',
                      ),
                      minLines: 1,
                      maxLines: 4,
                      onSubmitted: (_) => _send(),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _sending ? null : _send,
                    icon: _sending
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.send),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
