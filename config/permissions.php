<?php

/**
 * Lộ trình phân quyền admin.
 *
 * Vai trò:
 * - super_admin: Quản trị viên — toàn quyền, duy nhất xem doanh thu/doanh số
 * - manager: Quản lý — vận hành kho, sản phẩm, nội dung, chat (không xem doanh thu, không quản lý user)
 * - staff: Nhân viên — sản phẩm, nguyên liệu, nhập kho, chat
 * - content: Biên tập — banner, bài viết, trang tĩnh, chat
 */

return [
    'roles' => [
        'super_admin' => 'Quản trị viên',
        'manager' => 'Quản lý',
        'staff' => 'Nhân viên',
        'content' => 'Biên tập nội dung',
    ],

    'permissions' => [
        'dashboard.view' => 'Xem tổng quan',
        'products.manage' => 'Quản lý sản phẩm',
        'categories.manage' => 'Quản lý danh mục',
        'materials.manage' => 'Quản lý nguyên liệu',
        'material_inputs.manage' => 'Nhập nguyên liệu',
        'equipment.manage' => 'Quản lý thiết bị',
        'banners.manage' => 'Quản lý banner',
        'posts.manage' => 'Quản lý bài viết',
        'pages.manage' => 'Quản lý trang tĩnh',
        'chat.manage' => 'Chat khách hàng',
        'orders.manage' => 'Yêu cầu đặt hàng / liên hệ lại',
        'sales.sell' => 'Quét QR & bán sản phẩm nội bộ',
        'settings.manage' => 'Cài đặt & SEO',
        'trash.manage' => 'Thùng rác',
        'users.manage' => 'Quản lý người dùng admin',
        'revenue.view' => 'Xem doanh thu / doanh số / giá trị tài chính',
        'tax.manage' => 'Module chuẩn bị thuế HKD (hồ sơ, sổ, báo cáo kỳ)',
    ],

    'role_permissions' => [
        'super_admin' => ['*'],

        'manager' => [
            'dashboard.view',
            'products.manage',
            'categories.manage',
            'materials.manage',
            'material_inputs.manage',
            'equipment.manage',
            'banners.manage',
            'posts.manage',
            'pages.manage',
            'chat.manage',
            'orders.manage',
            'sales.sell',
            'trash.manage',
            // không: revenue.view, users.manage, settings.manage
        ],

        'staff' => [
            'dashboard.view',
            'products.manage',
            'categories.manage',
            'materials.manage',
            'material_inputs.manage',
            'equipment.manage',
            'chat.manage',
            'orders.manage',
            'sales.sell',
        ],

        'content' => [
            'dashboard.view',
            'banners.manage',
            'posts.manage',
            'pages.manage',
            'chat.manage',
        ],
    ],

    /**
     * Map permission → route name patterns (để ẩn menu & chặn route).
     */
    'route_permissions' => [
        'admin.dashboard' => 'dashboard.view',
        'admin.products.*' => 'products.manage',
        'admin.categories.*' => 'categories.manage',
        'admin.materials.*' => 'materials.manage',
        'admin.material-inputs.*' => 'material_inputs.manage',
        'admin.equipment.*' => 'equipment.manage',
        'admin.banners.*' => 'banners.manage',
        'admin.posts.*' => 'posts.manage',
        'admin.pages.*' => 'pages.manage',
        'admin.chat.*' => 'chat.manage',
        'admin.orders.*' => 'orders.manage',
        'admin.sales.report' => 'revenue.view',
        'admin.sales.*' => 'sales.sell',
        'admin.tax.*' => 'tax.manage',
        'admin.settings.*' => 'settings.manage',
        'admin.trash.*' => 'trash.manage',
        'admin.users.*' => 'users.manage',
    ],

    /**
     * Map API path prefix (sau /api/v1/admin/) → permission.
     */
    'api_path_permissions' => [
        'dashboard' => 'dashboard.view',
        'categories' => 'categories.manage',
        'products' => 'products.manage',
        'materials' => 'materials.manage',
        'material-inputs' => 'material_inputs.manage',
        'equipment' => 'equipment.manage',
        'banners' => 'banners.manage',
        'posts' => 'posts.manage',
        'pages' => 'pages.manage',
        'settings' => 'settings.manage',
        'chat' => 'chat.manage',
        'orders' => 'orders.manage',
        'sales' => 'sales.sell',
        'tax' => 'tax.manage',
        'trash' => 'trash.manage',
        'users' => 'users.manage',
        'roles' => 'users.manage',
    ],
];
