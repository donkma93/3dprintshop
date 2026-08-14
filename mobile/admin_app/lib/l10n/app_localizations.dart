import 'package:flutter/widgets.dart';

/// In-app strings (vi / en). Access via `context.l10n` or `AppLocalizations.of(context)`.
class AppLocalizations {
  AppLocalizations(this.localeCode);

  final String localeCode;

  static const supported = ['vi', 'en'];

  static AppLocalizations of(BuildContext context) {
    final value = Localizations.of<AppLocalizations>(context, AppLocalizations);
    assert(value != null, 'AppLocalizations not found in context');
    return value!;
  }

  bool get isVi => localeCode == 'vi';

  String t(String key) => _table[localeCode]?[key] ?? _table['vi']![key] ?? key;

  String get appName => t('appName');
  String get appSubtitle => t('appSubtitle');
  String get adminBrand => t('adminBrand');
  String get shopTagline => t('shopTagline');

  // Common
  String get ok => t('ok');
  String get cancel => t('cancel');
  String get save => t('save');
  String get delete => t('delete');
  String get edit => t('edit');
  String get create => t('create');
  String get add => t('add');
  String get search => t('search');
  String get refresh => t('refresh');
  String get retry => t('retry');
  String get loading => t('loading');
  String get empty => t('empty');
  String get confirm => t('confirm');
  String get yes => t('yes');
  String get no => t('no');
  String get back => t('back');
  String get next => t('next');
  String get close => t('close');
  String get actions => t('actions');
  String get status => t('status');
  String get active => t('active');
  String get inactive => t('inactive');
  String get required => t('required');
  String get optional => t('optional');
  String get name => t('name');
  String get email => t('email');
  String get password => t('password');
  String get phone => t('phone');
  String get address => t('address');
  String get note => t('note');
  String get description => t('description');
  String get image => t('image');
  String get slug => t('slug');
  String get sortOrder => t('sortOrder');
  String get price => t('price');
  String get costPrice => t('costPrice');
  String get stock => t('stock');
  String get sku => t('sku');
  String get unit => t('unit');
  String get quantity => t('quantity');
  String get total => t('total');
  String get date => t('date');
  String get fromDate => t('fromDate');
  String get toDate => t('toDate');
  String get filter => t('filter');
  String get all => t('all');
  String get details => t('details');
  String get noPermission => t('noPermission');
  String get errorGeneric => t('errorGeneric');
  String get saved => t('saved');
  String get deleted => t('deleted');
  String get created => t('created');
  String get updated => t('updated');
  String get confirmDelete => t('confirmDelete');
  String get language => t('language');
  String get vietnamese => t('vietnamese');
  String get english => t('english');
  String get system => t('system');
  String get content => t('content');
  String get title => t('title');
  String get metaTitle => t('metaTitle');
  String get metaDescription => t('metaDescription');
  String get metaKeywords => t('metaKeywords');
  String get isActive => t('isActive');
  String get restore => t('restore');
  String get forceDelete => t('forceDelete');
  String get emptyTrash => t('emptyTrash');
  String get daysLeft => t('daysLeft');
  String get role => t('role');
  String get logout => t('logout');
  String get logoutAll => t('logoutAll');
  String get logoutConfirm => t('logoutConfirm');
  String get logoutAllConfirm => t('logoutAllConfirm');
  String get logoutAllBody => t('logoutAllBody');
  String get aboutApp => t('aboutApp');
  String get viewStore => t('viewStore');
  String get profile => t('profile');
  String get more => t('more');
  String get menu => t('menu');
  String get sectionCatalog => t('sectionCatalog');
  String get sectionContent => t('sectionContent');
  String get sectionSales => t('sectionSales');
  String get sectionTax => t('sectionTax');
  String get sectionSystem => t('sectionSystem');
  String get sectionAccount => t('sectionAccount');

  // Nav / modules (match web admin sidebar)
  String get navDashboard => t('navDashboard');
  String get navProducts => t('navProducts');
  String get navCategories => t('navCategories');
  String get navMaterials => t('navMaterials');
  String get navMaterialInputs => t('navMaterialInputs');
  String get navEquipment => t('navEquipment');
  String get navBanners => t('navBanners');
  String get navPosts => t('navPosts');
  String get navPages => t('navPages');
  String get navChat => t('navChat');
  String get navOrders => t('navOrders');
  String get navSalesScan => t('navSalesScan');
  String get navSalesHistory => t('navSalesHistory');
  String get navSalesReport => t('navSalesReport');
  String get navTaxOverview => t('navTaxOverview');
  String get navTaxLedger => t('navTaxLedger');
  String get navTaxReport => t('navTaxReport');
  String get navTaxProfile => t('navTaxProfile');
  String get navUsers => t('navUsers');
  String get navSettings => t('navSettings');
  String get navTrash => t('navTrash');
  String get navSales => t('navSales');
  String get navMore => t('navMore');

  // Login
  String get loginTitle => t('loginTitle');
  String get loginSubtitle => t('loginSubtitle');
  String get apiUrl => t('apiUrl');
  String get apiUrlHint => t('apiUrlHint');
  String get apiUrlHelp => t('apiUrlHelp');
  String get apiUrlRequired => t('apiUrlRequired');
  String get apiUrlInvalid => t('apiUrlInvalid');
  String get emailRequired => t('emailRequired');
  String get emailInvalid => t('emailInvalid');
  String get passwordRequired => t('passwordRequired');
  String get rememberUrl => t('rememberUrl');
  String get login => t('login');
  String get loginFootnote => t('loginFootnote');
  String get restoringSession => t('restoringSession');

  // Dashboard
  String get dashboard => t('dashboard');
  String get productsCount => t('productsCount');
  String get categoriesCount => t('categoriesCount');
  String get materialsCount => t('materialsCount');
  String get equipmentCount => t('equipmentCount');
  String get lowStock => t('lowStock');
  String get recentSales => t('recentSales');
  String get openOrders => t('openOrders');

  // Sales
  String get sell => t('sell');
  String get lookup => t('lookup');
  String get scanQr => t('scanQr');
  String get enterCode => t('enterCode');
  String get foundProduct => t('foundProduct');
  String get sellSuccess => t('sellSuccess');
  String get needsShipping => t('needsShipping');
  String get paymentMethod => t('paymentMethod');
  String get customerName => t('customerName');
  String get customerPhone => t('customerPhone');
  String get customerAddress => t('customerAddress');
  String get province => t('province');
  String get district => t('district');
  String get ward => t('ward');
  String get printSlip => t('printSlip');
  String get openPrint => t('openPrint');
  String get later => t('later');
  String get cash => t('cash');
  String get transfer => t('transfer');
  String get other => t('other');
  String get noProductSelected => t('noProductSelected');
  String get invalidQty => t('invalidQty');
  String get shippingRequired => t('shippingRequired');
  String get cameraUnavailable => t('cameraUnavailable');
  String get history => t('history');
  String get report => t('report');
  String get revenue => t('revenue');
  String get profit => t('profit');
  String get cost => t('cost');

  // Chat / orders
  String get conversations => t('conversations');
  String get reply => t('reply');
  String get typeMessage => t('typeMessage');
  String get closeConversation => t('closeConversation');
  String get reopenConversation => t('reopenConversation');
  String get orderRequests => t('orderRequests');
  String get orderStatus => t('orderStatus');
  String get markProcessed => t('markProcessed');

  // Tax
  String get taxSummary => t('taxSummary');
  String get taxSync => t('taxSync');
  String get taxLedger => t('taxLedger');
  String get taxProfile => t('taxProfile');
  String get taxReport => t('taxReport');
  String get period => t('period');
  String get closePeriod => t('closePeriod');
  String get reopenPeriod => t('reopenPeriod');
  String get markPaid => t('markPaid');

  // Forms extras
  String get skuPrefix => t('skuPrefix');
  String get category => t('category');
  String get material => t('material');
  String get supplier => t('supplier');
  String get minStock => t('minStock');
  String get unitPrice => t('unitPrice');
  String get linkUrl => t('linkUrl');
  String get buttonText => t('buttonText');
  String get published => t('published');
  String get draft => t('draft');
  String get excerpt => t('excerpt');
  String get body => t('body');
  String get siteName => t('siteName');
  String get siteTagline => t('siteTagline');
  String get hotline => t('hotline');
  String get workingHours => t('workingHours');
  String get facebook => t('facebook');
  String get zalo => t('zalo');
  String get youtube => t('youtube');
  String get footerAbout => t('footerAbout');
  String get footerCopyright => t('footerCopyright');
  String get newPassword => t('newPassword');
  String get leaveBlankKeep => t('leaveBlankKeep');
  String get isAdminActive => t('isAdminActive');
  String get qrCode => t('qrCode');
  String get regenerateQr => t('regenerateQr');
  String get downloadQr => t('downloadQr');
  String get noItems => t('noItems');
  String get loadMore => t('loadMore');
  String get fieldRequired => t('fieldRequired');
  String get pickImageOptional => t('pickImageOptional');

  static const Map<String, Map<String, String>> _table = {
    'vi': {
      'appName': '3D Print Shop Admin',
      'appSubtitle': 'Quản lý bán hàng in 3D',
      'adminBrand': '3D Admin',
      'shopTagline': 'Quản lý bán hàng in 3D',
      'ok': 'OK',
      'cancel': 'Huỷ',
      'save': 'Lưu',
      'delete': 'Xoá',
      'edit': 'Sửa',
      'create': 'Tạo mới',
      'add': 'Thêm',
      'search': 'Tìm kiếm',
      'refresh': 'Làm mới',
      'retry': 'Thử lại',
      'loading': 'Đang tải…',
      'empty': 'Không có dữ liệu',
      'confirm': 'Xác nhận',
      'yes': 'Có',
      'no': 'Không',
      'back': 'Quay lại',
      'next': 'Tiếp',
      'close': 'Đóng',
      'actions': 'Thao tác',
      'status': 'Trạng thái',
      'active': 'Đang bật',
      'inactive': 'Tắt',
      'required': 'Bắt buộc',
      'optional': 'Tuỳ chọn',
      'name': 'Tên',
      'email': 'Email',
      'password': 'Mật khẩu',
      'phone': 'Điện thoại',
      'address': 'Địa chỉ',
      'note': 'Ghi chú',
      'description': 'Mô tả',
      'image': 'Hình ảnh',
      'slug': 'Slug',
      'sortOrder': 'Thứ tự',
      'price': 'Giá bán',
      'costPrice': 'Giá vốn',
      'stock': 'Tồn kho',
      'sku': 'SKU',
      'unit': 'Đơn vị',
      'quantity': 'Số lượng',
      'total': 'Tổng',
      'date': 'Ngày',
      'fromDate': 'Từ ngày',
      'toDate': 'Đến ngày',
      'filter': 'Lọc',
      'all': 'Tất cả',
      'details': 'Chi tiết',
      'noPermission': 'Bạn không có quyền truy cập.',
      'errorGeneric': 'Đã xảy ra lỗi.',
      'saved': 'Đã lưu.',
      'deleted': 'Đã xoá.',
      'created': 'Đã tạo.',
      'updated': 'Đã cập nhật.',
      'confirmDelete': 'Bạn chắc chắn muốn xoá mục này?',
      'language': 'Ngôn ngữ',
      'vietnamese': 'Tiếng Việt',
      'english': 'English',
      'system': 'Hệ thống',
      'content': 'Nội dung',
      'title': 'Tiêu đề',
      'metaTitle': 'Meta title',
      'metaDescription': 'Meta description',
      'metaKeywords': 'Meta keywords',
      'isActive': 'Kích hoạt',
      'restore': 'Khôi phục',
      'forceDelete': 'Xoá vĩnh viễn',
      'emptyTrash': 'Dọn thùng rác',
      'daysLeft': 'Còn (ngày)',
      'role': 'Vai trò',
      'logout': 'Đăng xuất',
      'logoutAll': 'Đăng xuất mọi thiết bị',
      'logoutConfirm': 'Đăng xuất?',
      'logoutAllConfirm': 'Đăng xuất tất cả thiết bị?',
      'logoutAllBody': 'Xoá mọi token Sanctum của tài khoản trên server.',
      'aboutApp': 'Về ứng dụng',
      'viewStore': 'Xem cửa hàng',
      'profile': 'Hồ sơ',
      'more': 'Thêm',
      'menu': 'Menu',
      'sectionCatalog': 'Kho & sản phẩm',
      'sectionContent': 'Nội dung website',
      'sectionSales': 'Bán hàng nội bộ',
      'sectionTax': 'Thuế HKD (chuẩn bị)',
      'sectionSystem': 'Hệ thống',
      'sectionAccount': 'Tài khoản',
      'navDashboard': 'Tổng quan',
      'navProducts': 'Sản phẩm',
      'navCategories': 'Danh mục',
      'navMaterials': 'Nguyên liệu',
      'navMaterialInputs': 'Nhập nguyên liệu',
      'navEquipment': 'Thiết bị',
      'navBanners': 'Banner / Slider',
      'navPosts': 'Bài viết',
      'navPages': 'Trang tĩnh',
      'navChat': 'Chat khách hàng',
      'navOrders': 'Đặt hàng / liên hệ',
      'navSalesScan': 'Quét QR bán hàng',
      'navSalesHistory': 'Lịch sử bán',
      'navSalesReport': 'Doanh thu / lãi lỗ',
      'navTaxOverview': 'Tổng quan thuế',
      'navTaxLedger': 'Sổ doanh thu',
      'navTaxReport': 'Báo cáo kỳ',
      'navTaxProfile': 'Hồ sơ HKD',
      'navUsers': 'Người dùng',
      'navSettings': 'Cài đặt & SEO',
      'navTrash': 'Thùng rác',
      'navSales': 'Bán hàng',
      'navMore': 'Thêm',
      'loginTitle': '3D Print Shop',
      'loginSubtitle': 'Admin — nhập URL API của shop',
      'apiUrl': 'URL API',
      'apiUrlHint': 'https://shop.com hoặc http://IP:8000',
      'apiUrlHelp': 'App tự thêm /api/v1 nếu thiếu.',
      'apiUrlRequired': 'Nhập URL API',
      'apiUrlInvalid': 'URL không hợp lệ',
      'emailRequired': 'Nhập email',
      'emailInvalid': 'Email không hợp lệ',
      'passwordRequired': 'Nhập mật khẩu',
      'rememberUrl': 'Ghi nhớ URL trên máy này',
      'login': 'Đăng nhập',
      'loginFootnote': 'Token Sanctum lưu an toàn trên máy. Mỗi shop một URL.',
      'restoringSession': 'Đang khôi phục phiên…',
      'dashboard': 'Tổng quan',
      'productsCount': 'Sản phẩm',
      'categoriesCount': 'Danh mục',
      'materialsCount': 'Nguyên liệu',
      'equipmentCount': 'Thiết bị',
      'lowStock': 'Sắp hết hàng',
      'recentSales': 'Bán gần đây',
      'openOrders': 'Đơn chờ xử lý',
      'sell': 'Bán',
      'lookup': 'Tra mã',
      'scanQr': 'Quét QR',
      'enterCode': 'Nhập mã / SKU',
      'foundProduct': 'Đã tìm sản phẩm',
      'sellSuccess': 'Đã bán',
      'needsShipping': 'Cần giao hàng',
      'paymentMethod': 'Thanh toán',
      'customerName': 'Tên khách',
      'customerPhone': 'SĐT khách',
      'customerAddress': 'Địa chỉ',
      'province': 'Tỉnh/TP',
      'district': 'Quận/Huyện',
      'ward': 'Phường/Xã',
      'printSlip': 'In phiếu gửi?',
      'openPrint': 'Mở phiếu',
      'later': 'Để sau',
      'cash': 'Tiền mặt',
      'transfer': 'Chuyển khoản',
      'other': 'Khác',
      'noProductSelected': 'Chưa chọn sản phẩm',
      'invalidQty': 'Số lượng không hợp lệ',
      'shippingRequired': 'Giao hàng cần đủ tên, SĐT, địa chỉ, tỉnh',
      'cameraUnavailable': 'Camera quét QR không khả dụng trên thiết bị này. Dùng nhập mã thủ công.',
      'history': 'Lịch sử',
      'report': 'Báo cáo',
      'revenue': 'Doanh thu',
      'profit': 'Lãi',
      'cost': 'Chi phí',
      'conversations': 'Hội thoại',
      'reply': 'Trả lời',
      'typeMessage': 'Nhập tin nhắn…',
      'closeConversation': 'Đóng hội thoại',
      'reopenConversation': 'Mở lại',
      'orderRequests': 'Yêu cầu đặt hàng',
      'orderStatus': 'Trạng thái đơn',
      'markProcessed': 'Đánh dấu đã xử lý',
      'taxSummary': 'Tổng quan thuế',
      'taxSync': 'Đồng bộ từ bán hàng',
      'taxLedger': 'Sổ doanh thu',
      'taxProfile': 'Hồ sơ HKD',
      'taxReport': 'Báo cáo kỳ',
      'period': 'Kỳ',
      'closePeriod': 'Chốt kỳ',
      'reopenPeriod': 'Mở lại kỳ',
      'markPaid': 'Đánh dấu đã nộp',
      'skuPrefix': 'Tiền tố SKU',
      'category': 'Danh mục',
      'material': 'Nguyên liệu',
      'supplier': 'Nhà cung cấp',
      'minStock': 'Tồn tối thiểu',
      'unitPrice': 'Đơn giá',
      'linkUrl': 'Liên kết',
      'buttonText': 'Chữ nút',
      'published': 'Đã xuất bản',
      'draft': 'Nháp',
      'excerpt': 'Tóm tắt',
      'body': 'Nội dung',
      'siteName': 'Tên website',
      'siteTagline': 'Slogan',
      'hotline': 'Hotline',
      'workingHours': 'Giờ làm việc',
      'facebook': 'Facebook',
      'zalo': 'Zalo',
      'youtube': 'YouTube',
      'footerAbout': 'Giới thiệu chân trang',
      'footerCopyright': 'Copyright',
      'newPassword': 'Mật khẩu mới',
      'leaveBlankKeep': 'Để trống nếu giữ nguyên',
      'isAdminActive': 'Tài khoản hoạt động',
      'qrCode': 'Mã QR',
      'regenerateQr': 'Tạo lại QR',
      'downloadQr': 'Tải QR',
      'noItems': 'Không có mục nào',
      'loadMore': 'Tải thêm',
      'fieldRequired': 'Trường bắt buộc',
      'pickImageOptional': 'Ảnh (tuỳ chọn, gửi multipart trên mobile)',
    },
    'en': {
      'appName': '3D Print Shop Admin',
      'appSubtitle': '3D print shop management',
      'adminBrand': '3D Admin',
      'shopTagline': '3D print shop management',
      'ok': 'OK',
      'cancel': 'Cancel',
      'save': 'Save',
      'delete': 'Delete',
      'edit': 'Edit',
      'create': 'Create',
      'add': 'Add',
      'search': 'Search',
      'refresh': 'Refresh',
      'retry': 'Retry',
      'loading': 'Loading…',
      'empty': 'No data',
      'confirm': 'Confirm',
      'yes': 'Yes',
      'no': 'No',
      'back': 'Back',
      'next': 'Next',
      'close': 'Close',
      'actions': 'Actions',
      'status': 'Status',
      'active': 'Active',
      'inactive': 'Inactive',
      'required': 'Required',
      'optional': 'Optional',
      'name': 'Name',
      'email': 'Email',
      'password': 'Password',
      'phone': 'Phone',
      'address': 'Address',
      'note': 'Note',
      'description': 'Description',
      'image': 'Image',
      'slug': 'Slug',
      'sortOrder': 'Sort order',
      'price': 'Price',
      'costPrice': 'Cost',
      'stock': 'Stock',
      'sku': 'SKU',
      'unit': 'Unit',
      'quantity': 'Quantity',
      'total': 'Total',
      'date': 'Date',
      'fromDate': 'From',
      'toDate': 'To',
      'filter': 'Filter',
      'all': 'All',
      'details': 'Details',
      'noPermission': 'You do not have permission.',
      'errorGeneric': 'Something went wrong.',
      'saved': 'Saved.',
      'deleted': 'Deleted.',
      'created': 'Created.',
      'updated': 'Updated.',
      'confirmDelete': 'Delete this item?',
      'language': 'Language',
      'vietnamese': 'Vietnamese',
      'english': 'English',
      'system': 'System',
      'content': 'Content',
      'title': 'Title',
      'metaTitle': 'Meta title',
      'metaDescription': 'Meta description',
      'metaKeywords': 'Meta keywords',
      'isActive': 'Active',
      'restore': 'Restore',
      'forceDelete': 'Delete permanently',
      'emptyTrash': 'Empty trash',
      'daysLeft': 'Days left',
      'role': 'Role',
      'logout': 'Log out',
      'logoutAll': 'Log out all devices',
      'logoutConfirm': 'Log out?',
      'logoutAllConfirm': 'Log out all devices?',
      'logoutAllBody': 'Revokes every Sanctum token for this account on the server.',
      'aboutApp': 'About',
      'viewStore': 'View storefront',
      'profile': 'Profile',
      'more': 'More',
      'menu': 'Menu',
      'sectionCatalog': 'Catalog & inventory',
      'sectionContent': 'Website content',
      'sectionSales': 'Internal sales',
      'sectionTax': 'HKD tax prep',
      'sectionSystem': 'System',
      'sectionAccount': 'Account',
      'navDashboard': 'Dashboard',
      'navProducts': 'Products',
      'navCategories': 'Categories',
      'navMaterials': 'Materials',
      'navMaterialInputs': 'Material receipts',
      'navEquipment': 'Equipment',
      'navBanners': 'Banners / Slider',
      'navPosts': 'Posts',
      'navPages': 'Pages',
      'navChat': 'Customer chat',
      'navOrders': 'Orders / contact',
      'navSalesScan': 'Scan QR to sell',
      'navSalesHistory': 'Sales history',
      'navSalesReport': 'Revenue / P&L',
      'navTaxOverview': 'Tax overview',
      'navTaxLedger': 'Revenue ledger',
      'navTaxReport': 'Period report',
      'navTaxProfile': 'HKD profile',
      'navUsers': 'Users',
      'navSettings': 'Settings & SEO',
      'navTrash': 'Trash',
      'navSales': 'Sales',
      'navMore': 'More',
      'loginTitle': '3D Print Shop',
      'loginSubtitle': 'Admin — enter your shop API URL',
      'apiUrl': 'API URL',
      'apiUrlHint': 'https://shop.com or http://IP:8000',
      'apiUrlHelp': 'App appends /api/v1 when missing.',
      'apiUrlRequired': 'Enter API URL',
      'apiUrlInvalid': 'Invalid URL',
      'emailRequired': 'Enter email',
      'emailInvalid': 'Invalid email',
      'passwordRequired': 'Enter password',
      'rememberUrl': 'Remember URL on this device',
      'login': 'Sign in',
      'loginFootnote': 'Sanctum token is stored securely. One URL per shop.',
      'restoringSession': 'Restoring session…',
      'dashboard': 'Dashboard',
      'productsCount': 'Products',
      'categoriesCount': 'Categories',
      'materialsCount': 'Materials',
      'equipmentCount': 'Equipment',
      'lowStock': 'Low stock',
      'recentSales': 'Recent sales',
      'openOrders': 'Open orders',
      'sell': 'Sell',
      'lookup': 'Look up',
      'scanQr': 'Scan QR',
      'enterCode': 'Enter code / SKU',
      'foundProduct': 'Product found',
      'sellSuccess': 'Sold',
      'needsShipping': 'Needs shipping',
      'paymentMethod': 'Payment',
      'customerName': 'Customer name',
      'customerPhone': 'Customer phone',
      'customerAddress': 'Address',
      'province': 'Province/City',
      'district': 'District',
      'ward': 'Ward',
      'printSlip': 'Print shipping slip?',
      'openPrint': 'Open slip',
      'later': 'Later',
      'cash': 'Cash',
      'transfer': 'Transfer',
      'other': 'Other',
      'noProductSelected': 'No product selected',
      'invalidQty': 'Invalid quantity',
      'shippingRequired': 'Shipping requires name, phone, address, province',
      'cameraUnavailable': 'QR camera is not available on this device. Use manual code entry.',
      'history': 'History',
      'report': 'Report',
      'revenue': 'Revenue',
      'profit': 'Profit',
      'cost': 'Cost',
      'conversations': 'Conversations',
      'reply': 'Reply',
      'typeMessage': 'Type a message…',
      'closeConversation': 'Close conversation',
      'reopenConversation': 'Reopen',
      'orderRequests': 'Order requests',
      'orderStatus': 'Order status',
      'markProcessed': 'Mark processed',
      'taxSummary': 'Tax overview',
      'taxSync': 'Sync from sales',
      'taxLedger': 'Revenue ledger',
      'taxProfile': 'HKD profile',
      'taxReport': 'Period report',
      'period': 'Period',
      'closePeriod': 'Close period',
      'reopenPeriod': 'Reopen period',
      'markPaid': 'Mark paid',
      'skuPrefix': 'SKU prefix',
      'category': 'Category',
      'material': 'Material',
      'supplier': 'Supplier',
      'minStock': 'Min stock',
      'unitPrice': 'Unit price',
      'linkUrl': 'Link URL',
      'buttonText': 'Button text',
      'published': 'Published',
      'draft': 'Draft',
      'excerpt': 'Excerpt',
      'body': 'Body',
      'siteName': 'Site name',
      'siteTagline': 'Tagline',
      'hotline': 'Hotline',
      'workingHours': 'Working hours',
      'facebook': 'Facebook',
      'zalo': 'Zalo',
      'youtube': 'YouTube',
      'footerAbout': 'Footer about',
      'footerCopyright': 'Copyright',
      'newPassword': 'New password',
      'leaveBlankKeep': 'Leave blank to keep current',
      'isAdminActive': 'Account active',
      'qrCode': 'QR code',
      'regenerateQr': 'Regenerate QR',
      'downloadQr': 'Download QR',
      'noItems': 'No items',
      'loadMore': 'Load more',
      'fieldRequired': 'Required field',
      'pickImageOptional': 'Image (optional; multipart on mobile)',
    },
  };
}

class AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) =>
      AppLocalizations.supported.contains(locale.languageCode);

  @override
  Future<AppLocalizations> load(Locale locale) async =>
      AppLocalizations(locale.languageCode);

  @override
  bool shouldReload(covariant LocalizationsDelegate<AppLocalizations> old) =>
      false;
}

extension L10nX on BuildContext {
  AppLocalizations get l10n => AppLocalizations.of(this);
}
