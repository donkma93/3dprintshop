<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            // Địa chỉ khách — chi tiết theo chuẩn gửi hàng VN
            $table->string('customer_ward', 120)->nullable()->after('customer_address');
            $table->string('customer_district', 120)->nullable()->after('customer_ward');
            $table->string('customer_province', 120)->nullable()->after('customer_district');
            $table->string('customer_postal_code', 20)->nullable()->after('customer_province');

            // Địa chỉ người nhận
            $table->string('receiver_ward', 120)->nullable()->after('receiver_address');
            $table->string('receiver_district', 120)->nullable()->after('receiver_ward');
            $table->string('receiver_province', 120)->nullable()->after('receiver_district');
            $table->string('receiver_postal_code', 20)->nullable()->after('receiver_province');

            // Thông tin kiện / hãng vận chuyển (in phiếu)
            $table->string('carrier', 80)->nullable()->after('shipping_note');
            $table->string('shipping_service', 40)->nullable()->after('carrier'); // standard, express, economy
            $table->unsignedTinyInteger('package_count')->default(1)->after('package_weight');
            $table->decimal('declared_value', 15, 2)->nullable()->after('package_count');
            $table->string('goods_content', 255)->nullable()->after('declared_value');
        });
    }

    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->dropColumn([
                'customer_ward',
                'customer_district',
                'customer_province',
                'customer_postal_code',
                'receiver_ward',
                'receiver_district',
                'receiver_province',
                'receiver_postal_code',
                'carrier',
                'shipping_service',
                'package_count',
                'declared_value',
                'goods_content',
            ]);
        });
    }
};
