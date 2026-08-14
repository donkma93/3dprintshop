import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/network/api_envelope.dart';
import '../../core/providers.dart';
import '../../core/utils/money.dart';
import '../../core/widgets/async_body.dart';
import '../../l10n/app_localizations.dart';

class ChatThreadScreen extends ConsumerStatefulWidget {
  final String conversationId;
  const ChatThreadScreen({super.key, required this.conversationId});

  @override
  ConsumerState<ChatThreadScreen> createState() => _ChatThreadScreenState();
}

class _ChatThreadScreenState extends ConsumerState<ChatThreadScreen> {
  final _msgCtrl = TextEditingController();
  final _focus = FocusNode();
  final _scroll = ScrollController();
  final List<Map<String, dynamic>> _messages = [];

  int? _afterId;
  bool _loading = true;
  bool _sending = false;
  String? _error;
  String? _guestName;
  String? _status;

  /// Peer (guest) is typing.
  bool _guestTyping = false;
  String? _peerAdminTypingName;

  /// Local admin typing heartbeat.
  bool _localTyping = false;
  Timer? _poll;
  Timer? _typingRefresh;
  Timer? _typingIdle;
  Timer? _mentionDebounce;

  /// @ product mention
  bool _showMention = false;
  List<Map<String, dynamic>> _mentionItems = [];
  int _mentionActive = 0;
  _MentionMatch? _mentionMatch;
  bool _mentionLoading = false;
  Map<String, dynamic>? _pendingProduct;

  @override
  void initState() {
    super.initState();
    _load();
    _poll = Timer.periodic(const Duration(seconds: 2), (_) => _pollNew());
    _msgCtrl.addListener(_onComposerChanged);
  }

  @override
  void dispose() {
    _poll?.cancel();
    _typingRefresh?.cancel();
    _typingIdle?.cancel();
    _mentionDebounce?.cancel();
    // Best-effort clear typing flag when leaving the thread.
    unawaited(_setTyping(false));
    _msgCtrl.removeListener(_onComposerChanged);
    _msgCtrl.dispose();
    _focus.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _onComposerChanged() {
    _handleLocalTyping();
    _updateMention();
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
      var maxId = 0;
      for (final m in list) {
        final id = (m['id'] as num?)?.toInt() ?? 0;
        if (id > maxId) maxId = id;
      }
      setState(() {
        _messages
          ..clear()
          ..addAll(list);
        _afterId = maxId;
        _guestName = data['guest_name']?.toString();
        _status = data['status']?.toString();
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
    try {
      final api = ref.read(apiClientProvider);
      final env = await api.get(
        '/admin/chat/${widget.conversationId}/poll',
        query: {'after_id': _afterId ?? 0},
      );
      final data = env.data;
      if (data is! Map) return;

      final typing = data['typing'];
      final staff = data['staff'];
      final prevStatus = _status;
      final nextStatus = data['status']?.toString();
      final becameClosed = nextStatus == 'closed' && prevStatus != 'closed';

      List list = [];
      if (data['messages'] is List) {
        list = data['messages'] as List;
      }

      final added = <Map<String, dynamic>>[];
      for (final m in list) {
        final map = Map<String, dynamic>.from(m as Map);
        final id = (map['id'] as num?)?.toInt();
        // Skip duplicates if already present.
        if (id != null && _messages.any((x) => (x['id'] as num?)?.toInt() == id)) {
          continue;
        }
        added.add(map);
        if (id != null && (_afterId == null || id > _afterId!)) {
          _afterId = id;
        }
      }

      if (!mounted) return;
      setState(() {
        if (typing is Map) {
          _guestTyping = typing['guest'] == true;
        }
        if (staff is Map) {
          final isMe = staff['is_typing_admin_me'] == true;
          final name = staff['typing_admin_name']?.toString();
          _peerAdminTypingName = (!isMe && name != null && name.isNotEmpty)
              ? name
              : null;
        }
        if (nextStatus != null) {
          _status = nextStatus;
        }
        if (added.isNotEmpty) {
          _messages.addAll(added);
          if (added.any((m) => _isGuest(m))) {
            _guestTyping = false;
          }
        }
      });
      if (added.isNotEmpty) _scrollToEnd();
      if (becameClosed) {
        showSnack(
          context,
          'Hội thoại đã tự đóng vì không có tin nhắn mới trong 30 phút.',
        );
      }
    } catch (_) {}
  }

  Future<void> _setTyping(bool typing) async {
    try {
      final api = ref.read(apiClientProvider);
      await api.post(
        '/admin/chat/${widget.conversationId}/typing',
        data: {'typing': typing},
      );
    } catch (_) {}
  }

  void _handleLocalTyping() {
    if (_status == 'closed') return;
    final hasText = _msgCtrl.text.trim().isNotEmpty;
    if (!hasText) {
      if (_localTyping) {
        _localTyping = false;
        _typingRefresh?.cancel();
        _typingIdle?.cancel();
        unawaited(_setTyping(false));
      }
      return;
    }

    if (!_localTyping) {
      _localTyping = true;
      unawaited(_setTyping(true));
    } else if (_typingRefresh == null || !_typingRefresh!.isActive) {
      // Refresh server TTL while still typing (< server TTL 6s).
      _typingRefresh = Timer(const Duration(milliseconds: 1800), () {
        if (_localTyping) {
          // Force re-POST by flipping local flag briefly.
          unawaited(_setTyping(true));
        }
      });
    }

    _typingIdle?.cancel();
    _typingIdle = Timer(const Duration(milliseconds: 2500), () {
      if (!_localTyping) return;
      _localTyping = false;
      unawaited(_setTyping(false));
    });
  }

  // ── @ product mention ──────────────────────────────────────────────

  _MentionMatch? _findMention(String text, int caret) {
    if (caret < 0 || caret > text.length) caret = text.length;
    final before = text.substring(0, caret);
    final re = RegExp(r'(^|[\s\n])@([^\s@]{0,40})$');
    final m = re.firstMatch(before);
    if (m == null) return null;
    final query = m.group(2) ?? '';
    final start = caret - query.length - 1;
    return _MentionMatch(start: start, end: caret, query: query);
  }

  void _updateMention() {
    final text = _msgCtrl.text;
    final caret = _msgCtrl.selection.baseOffset >= 0
        ? _msgCtrl.selection.baseOffset
        : text.length;
    final match = _findMention(text, caret);
    if (match == null) {
      if (_showMention) {
        setState(() {
          _showMention = false;
          _mentionItems = [];
          _mentionMatch = null;
        });
      }
      return;
    }
    _mentionMatch = match;
    _mentionDebounce?.cancel();
    _mentionDebounce = Timer(const Duration(milliseconds: 140), () {
      _searchProducts(match.query);
    });
  }

  Future<void> _searchProducts(String q) async {
    if (!mounted) return;
    setState(() {
      _showMention = true;
      _mentionLoading = true;
    });
    try {
      final api = ref.read(apiClientProvider);
      final env = await api.get(
        '/admin/products',
        query: {
          'q': q,
          'per_page': 12,
          'is_active': 1,
        },
      );
      final items = <Map<String, dynamic>>[];
      final data = env.data;
      List raw = const [];
      if (data is List) {
        raw = data;
      } else if (data is Map) {
        if (data['data'] is List) {
          raw = data['data'] as List;
        } else if (data['products'] is List) {
          raw = data['products'] as List;
        }
      }
      for (final e in raw) {
        if (e is Map) items.add(Map<String, dynamic>.from(e));
      }
      if (!mounted) return;
      setState(() {
        _mentionItems = items;
        _mentionActive = items.isEmpty ? 0 : 0;
        _mentionLoading = false;
        _showMention = true;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _mentionItems = [];
        _mentionLoading = false;
        _showMention = true;
      });
    }
  }

  void _pickProduct(Map<String, dynamic> p) {
    final match = _mentionMatch;
    final name = '${p['name'] ?? ''}';
    final sku = p['sku']?.toString();
    final url = p['url']?.toString();
    final slug = p['slug']?.toString();
    final price = p['price_formatted'] ??
        (p['final_price'] != null ? formatMoney(p['final_price']) : null);
    final insert = StringBuffer('Tôi đang tư vấn sản phẩm: $name');
    if (sku != null && sku.isNotEmpty) insert.write(' (SKU: $sku)');
    if (price != null) insert.write(' — $price');
    if (url != null && url.isNotEmpty) {
      insert.write(' — $url');
    } else if (slug != null && slug.isNotEmpty) {
      insert.write(' · slug: $slug');
    }
    insert.write(' ');

    final text = _msgCtrl.text;
    final start = match?.start ?? text.length;
    final end = match?.end ?? text.length;
    final next = text.replaceRange(start, end, insert.toString());
    _msgCtrl.value = TextEditingValue(
      text: next,
      selection: TextSelection.collapsed(
        offset: start + insert.length,
      ),
    );
    setState(() {
      _pendingProduct = Map<String, dynamic>.from(p);
      _showMention = false;
      _mentionItems = [];
      _mentionMatch = null;
    });
    _handleLocalTyping();
  }

  Map<String, dynamic>? _productOf(Map<String, dynamic> m) {
    final p = m['product'];
    if (p is Map) return Map<String, dynamic>.from(p);
    return null;
  }

  Future<void> _openProductUrl(String? url) async {
    if (url == null || url.isEmpty) return;
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {}
  }

  void _scrollToEnd() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.animateTo(
          _scroll.position.maxScrollExtent + 100,
          duration: const Duration(milliseconds: 280),
          curve: Curves.easeOutCubic,
        );
      }
    });
  }

  bool _isAdmin(Map<String, dynamic> m) {
    final sender = m['sender']?.toString();
    return sender == 'admin' ||
        m['sender_type'] == 'admin' ||
        m['is_admin'] == true ||
        m['from'] == 'admin' ||
        m['is_mine'] == true;
  }

  bool _isGuest(Map<String, dynamic> m) {
    final sender = m['sender']?.toString();
    return sender == 'guest' || m['from'] == 'guest';
  }

  bool _isBot(Map<String, dynamic> m) {
    return m['sender']?.toString() == 'bot';
  }

  Future<void> _send() async {
    var text = _msgCtrl.text.trim();
    if (_showMention && _mentionItems.isNotEmpty) {
      _pickProduct(_mentionItems[_mentionActive.clamp(0, _mentionItems.length - 1)]);
      return;
    }
    if (text.isEmpty && _pendingProduct == null) return;
    if (_sending) return;

    if (text.isEmpty && _pendingProduct != null) {
      final name = '${_pendingProduct!['name'] ?? ''}';
      final sku = _pendingProduct!['sku']?.toString();
      final url = _pendingProduct!['url']?.toString();
      text = 'Tôi đang tư vấn sản phẩm: $name'
          '${(sku != null && sku.isNotEmpty) ? ' (SKU: $sku)' : ''}'
          '${(url != null && url.isNotEmpty) ? ' — $url' : ''}';
    }

    setState(() => _sending = true);
    _typingRefresh?.cancel();
    _typingIdle?.cancel();
    _localTyping = false;
    unawaited(_setTyping(false));

    try {
      final api = ref.read(apiClientProvider);
      final payload = <String, dynamic>{'message': text};
      final pid = (_pendingProduct?['id'] as num?)?.toInt();
      if (pid != null && pid > 0) {
        payload['product_id'] = pid;
      }
      final env = await api.post(
        '/admin/chat/${widget.conversationId}/reply',
        data: payload,
      );
      _msgCtrl.clear();
      setState(() {
        _showMention = false;
        _mentionItems = [];
        _pendingProduct = null;
      });

      // Prefer optimistic append from reply payload.
      final data = env.data;
      if (data is Map) {
        final msg = data['message'];
        if (msg is Map) {
          final map = Map<String, dynamic>.from(msg);
          final id = (map['id'] as num?)?.toInt();
          if (id != null &&
              !_messages.any((x) => (x['id'] as num?)?.toInt() == id)) {
            setState(() {
              _messages.add(map);
              if (_afterId == null || id > _afterId!) _afterId = id;
            });
            _scrollToEnd();
          }
        }
        if (data['typing'] is Map) {
          setState(() => _guestTyping = data['typing']['guest'] == true);
        }
      }
      await _pollNew();
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
    final l10n = context.l10n;
    final title = _guestName?.isNotEmpty == true
        ? _guestName!
        : '${l10n.navChat} #${widget.conversationId}';
    final closed = _status == 'closed';

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
            ),
            if (_guestTyping)
              Text(
                'Khách đang nhập…',
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.lightBlue.shade100,
                  fontWeight: FontWeight.w500,
                ),
              )
            else if (_peerAdminTypingName != null)
              Text(
                '$_peerAdminTypingName đang nhập…',
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.amber.shade100,
                  fontWeight: FontWeight.w500,
                ),
              )
            else if (closed)
              const Text(
                'Đã đóng',
                style: TextStyle(fontSize: 11, color: Colors.white70),
              ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: l10n.refresh,
            onPressed: _load,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: _loading
                ? LoadingBody(message: l10n.loading)
                : _error != null
                    ? ErrorBody(error: _error!, onRetry: _load)
                    : ListView.builder(
                        controller: _scroll,
                        physics: const BouncingScrollPhysics(),
                        padding: const EdgeInsets.fromLTRB(12, 12, 12, 8),
                        itemCount: _messages.length + (_guestTyping ? 1 : 0),
                        itemBuilder: (context, i) {
                          if (_guestTyping && i == _messages.length) {
                            return _TypingBubble(
                              label: (_guestName?.isNotEmpty == true)
                                  ? '$_guestName đang nhập…'
                                  : 'Khách đang nhập…',
                            );
                          }
                          final m = _messages[i];
                          final admin = _isAdmin(m);
                          final bot = _isBot(m);
                          final body = '${m['body'] ?? m['message'] ?? m['content'] ?? ''}';
                          final product = _productOf(m);
                          final meta = m['sender_label']?.toString() ??
                              (admin
                                  ? (m['admin_name'] != null
                                      ? 'NV: ${m['admin_name']}'
                                      : 'Nhân viên')
                                  : bot
                                      ? 'Trợ lý ảo'
                                      : 'Khách');
                          final time = m['created_at_label']?.toString() ??
                              formatDate(m['created_at']?.toString());

                          return Align(
                            alignment: admin
                                ? Alignment.centerRight
                                : Alignment.centerLeft,
                            child: Container(
                              margin: const EdgeInsets.only(bottom: 8),
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 8,
                              ),
                              constraints: BoxConstraints(
                                maxWidth:
                                    MediaQuery.sizeOf(context).width * 0.78,
                              ),
                              decoration: BoxDecoration(
                                color: admin
                                    ? const Color(0xFF0F172A)
                                    : bot
                                        ? const Color(0xFFEEF2FF)
                                        : Colors.grey.shade200,
                                borderRadius: BorderRadius.only(
                                  topLeft: const Radius.circular(14),
                                  topRight: const Radius.circular(14),
                                  bottomLeft: Radius.circular(admin ? 14 : 4),
                                  bottomRight: Radius.circular(admin ? 4 : 14),
                                ),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    time.isNotEmpty ? '$meta · $time' : meta,
                                    style: TextStyle(
                                      fontSize: 10,
                                      color: admin
                                          ? Colors.white60
                                          : Colors.grey.shade600,
                                    ),
                                  ),
                                  if (body.isNotEmpty) ...[
                                    const SizedBox(height: 3),
                                    SelectableText(
                                      body,
                                      style: TextStyle(
                                        color: admin
                                            ? Colors.white
                                            : Colors.black87,
                                        height: 1.35,
                                        fontSize: 14,
                                      ),
                                    ),
                                  ],
                                  if (product != null) ...[
                                    const SizedBox(height: 8),
                                    _ProductShareCard(
                                      product: product,
                                      onDark: admin,
                                      onTap: () => _openProductUrl(
                                        product['url']?.toString(),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          );
                        },
                      ),
          ),

          // Pending product chip
          if (_pendingProduct != null)
            Material(
              color: const Color(0xFFEFF6FF),
              child: ListTile(
                dense: true,
                leading: ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: (_pendingProduct!['image_url']?.toString().isNotEmpty == true)
                      ? Image.network(
                          '${_pendingProduct!['image_url']}',
                          width: 40,
                          height: 40,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => _productPlaceholder(),
                        )
                      : _productPlaceholder(),
                ),
                title: Text(
                  '${_pendingProduct!['name'] ?? 'Sản phẩm'}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                ),
                subtitle: const Text(
                  'Sẽ gửi kèm thẻ sản phẩm (ảnh + link)',
                  style: TextStyle(fontSize: 11.5),
                ),
                trailing: IconButton(
                  icon: const Icon(Icons.close, size: 18),
                  onPressed: () => setState(() => _pendingProduct = null),
                ),
              ),
            ),

          // Mention picker
          if (_showMention)
            Material(
              elevation: 6,
              color: Colors.white,
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 220),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Padding(
                      padding: const EdgeInsets.fromLTRB(12, 8, 4, 4),
                      child: Row(
                        children: [
                          const Expanded(
                            child: Text(
                              'Chọn sản phẩm (@)',
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 12,
                                color: Color(0xFF475569),
                              ),
                            ),
                          ),
                          IconButton(
                            visualDensity: VisualDensity.compact,
                            onPressed: () => setState(() {
                              _showMention = false;
                              _mentionItems = [];
                            }),
                            icon: const Icon(Icons.close, size: 18),
                          ),
                        ],
                      ),
                    ),
                    if (_mentionLoading)
                      const Padding(
                        padding: EdgeInsets.all(16),
                        child: SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    else if (_mentionItems.isEmpty)
                      const Padding(
                        padding: EdgeInsets.fromLTRB(12, 4, 12, 14),
                        child: Text(
                          'Không tìm thấy sản phẩm',
                          style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                        ),
                      )
                    else
                      Flexible(
                        child: ListView.builder(
                          shrinkWrap: true,
                          itemCount: _mentionItems.length,
                          itemBuilder: (context, i) {
                            final p = _mentionItems[i];
                            final active = i == _mentionActive;
                            final img = p['image_url']?.toString();
                            final price = p['price_formatted'] ??
                                formatMoney(p['final_price'] ?? p['price']);
                            return ListTile(
                              dense: true,
                              selected: active,
                              selectedTileColor: const Color(0xFFEFF6FF),
                              leading: ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: img != null && img.isNotEmpty
                                    ? Image.network(
                                        img,
                                        width: 40,
                                        height: 40,
                                        fit: BoxFit.cover,
                                        errorBuilder: (_, __, ___) =>
                                            _productPlaceholder(),
                                      )
                                    : _productPlaceholder(),
                              ),
                              title: Text(
                                '${p['name'] ?? ''}',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w600,
                                  fontSize: 13.5,
                                ),
                              ),
                              subtitle: Text(
                                [
                                  if (price != null && '$price' != '—') price,
                                  if (p['sku'] != null) 'SKU: ${p['sku']}',
                                ].join(' · '),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(fontSize: 11.5),
                              ),
                              onTap: () => _pickProduct(p),
                            );
                          },
                        ),
                      ),
                  ],
                ),
              ),
            ),

          // Guest typing strip (also when list empty / loading done)
          if (_guestTyping && !_loading)
            Container(
              width: double.infinity,
              color: const Color(0xFFEFF6FF),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              child: Text(
                (_guestName?.isNotEmpty == true)
                    ? '$_guestName đang nhập tin nhắn…'
                    : 'Khách đang nhập tin nhắn…',
                style: const TextStyle(
                  fontSize: 12,
                  color: Color(0xFF2563EB),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),

          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(8, 6, 8, 8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(
                    child: TextField(
                      controller: _msgCtrl,
                      focusNode: _focus,
                      enabled: !closed && !_sending,
                      decoration: InputDecoration(
                        hintText: closed
                            ? 'Hội thoại đã đóng'
                            : 'Trả lời… Gõ @ để chọn SP',
                        isDense: true,
                      ),
                      minLines: 1,
                      maxLines: 5,
                      textInputAction: TextInputAction.send,
                      onSubmitted: (_) => _send(),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: (_sending || closed) ? null : _send,
                    icon: _sending
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(Icons.send_rounded),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _productPlaceholder() {
    return Container(
      width: 40,
      height: 40,
      color: const Color(0xFFE2E8F0),
      alignment: Alignment.center,
      child: const Icon(Icons.inventory_2_outlined, size: 18, color: Color(0xFF94A3B8)),
    );
  }
}

class _ProductShareCard extends StatelessWidget {
  final Map<String, dynamic> product;
  final bool onDark;
  final VoidCallback? onTap;

  const _ProductShareCard({
    required this.product,
    this.onDark = false,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final name = '${product['name'] ?? 'Sản phẩm'}';
    final price = product['price_formatted']?.toString() ??
        (product['price'] != null ? formatMoney(product['price']) : null);
    final url = product['url']?.toString();
    final img = product['image_url']?.toString();
    final border = onDark
        ? Colors.white24
        : const Color(0xFFE2E8F0);
    final bg = onDark ? Colors.white12 : Colors.white;

    return Material(
      color: bg,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: border),
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (img != null && img.isNotEmpty)
                Image.network(
                  img,
                  height: 120,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(
                    height: 90,
                    color: const Color(0xFFE2E8F0),
                    alignment: Alignment.center,
                    child: const Icon(Icons.inventory_2_outlined,
                        color: Color(0xFF94A3B8)),
                  ),
                )
              else
                Container(
                  height: 90,
                  color: const Color(0xFFE2E8F0),
                  alignment: Alignment.center,
                  child: const Icon(Icons.inventory_2_outlined,
                      color: Color(0xFF94A3B8)),
                ),
              Padding(
                padding: const EdgeInsets.fromLTRB(10, 8, 10, 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                        color: onDark ? Colors.white : Colors.black87,
                      ),
                    ),
                    if (price != null && price.isNotEmpty) ...[
                      const SizedBox(height: 3),
                      Text(
                        price,
                        style: TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 12.5,
                          color: onDark
                              ? const Color(0xFFFDE68A)
                              : const Color(0xFFB45309),
                        ),
                      ),
                    ],
                    if (url != null && url.isNotEmpty) ...[
                      const SizedBox(height: 3),
                      Text(
                        url,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 11,
                          color: onDark
                              ? const Color(0xFFBFDBFE)
                              : const Color(0xFF2563EB),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _MentionMatch {
  final int start;
  final int end;
  final String query;
  const _MentionMatch({
    required this.start,
    required this.end,
    required this.query,
  });
}

class _TypingBubble extends StatelessWidget {
  final String label;
  const _TypingBubble({required this.label});

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8, top: 2),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: Colors.grey.shade200,
          borderRadius: const BorderRadius.only(
            topLeft: Radius.circular(14),
            topRight: Radius.circular(14),
            bottomLeft: Radius.circular(4),
            bottomRight: Radius.circular(14),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            _Dot(delay: 0),
            const SizedBox(width: 4),
            _Dot(delay: 120),
            const SizedBox(width: 4),
            _Dot(delay: 240),
            const SizedBox(width: 8),
            Text(
              label,
              style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
            ),
          ],
        ),
      ),
    );
  }
}

class _Dot extends StatefulWidget {
  final int delay;
  const _Dot({required this.delay});

  @override
  State<_Dot> createState() => _DotState();
}

class _DotState extends State<_Dot> with SingleTickerProviderStateMixin {
  late final AnimationController _c;

  @override
  void initState() {
    super.initState();
    _c = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat();
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _c,
      builder: (context, _) {
        final t = ((_c.value + widget.delay / 900) % 1.0);
        final bounce = (t < 0.5) ? (t * 2) : (2 - t * 2);
        return Transform.translate(
          offset: Offset(0, -3 * bounce),
          child: Opacity(
            opacity: 0.35 + 0.65 * bounce,
            child: Container(
              width: 6,
              height: 6,
              decoration: BoxDecoration(
                color: Colors.grey.shade600,
                shape: BoxShape.circle,
              ),
            ),
          ),
        );
      },
    );
  }
}
