<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            // Khách hàng / nguồn đơn
            $table->string('customer_name', 120)->nullable()->after('note');
            $table->string('customer_phone', 40)->nullable()->after('customer_name');
            $table->string('customer_email', 120)->nullable()->after('customer_phone');
            $table->string('customer_address', 500)->nullable()->after('customer_email');
            $table->string('customer_source', 40)->nullable()->after('customer_address'); // phone, web_chat, contact, walk_in, other

            // Người nhận / địa chỉ gửi hàng (có thể khác khách hàng)
            $table->boolean('needs_shipping')->default(false)->after('customer_source');
            $table->string('receiver_name', 120)->nullable()->after('needs_shipping');
            $table->string('receiver_phone', 40)->nullable()->after('receiver_name');
            $table->string('receiver_address', 500)->nullable()->after('receiver_phone');
            $table->string('shipping_note', 500)->nullable()->after('receiver_address');

            // Thanh toán khi giao / COD
            $table->string('payment_method', 30)->nullable()->after('shipping_note'); // cash, transfer, cod
            $table->decimal('cod_amount', 15, 2)->nullable()->after('payment_method');
            $table->unsignedInteger('package_weight')->nullable()->after('cod_amount'); // gram

            $table->index(['customer_phone']);
            $table->index(['customer_name']);
            $table->index(['needs_shipping', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->dropIndex(['customer_phone']);
            $table->dropIndex(['customer_name']);
            $table->dropIndex(['needs_shipping', 'sold_at']);
            $table->dropColumn([
                'customer_name',
                'customer_phone',
                'customer_email',
                'customer_address',
                'customer_source',
                'needs_shipping',
                'receiver_name',
                'receiver_phone',
                'receiver_address',
                'shipping_note',
                'payment_method',
                'cod_amount',
                'package_weight',
            ]);
        });
    }
};
