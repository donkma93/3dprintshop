<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'print_time')) {
                $table->dropColumn('print_time');
            }
            $table->decimal('sale_price', 15, 2)->nullable()->after('price');
            $table->timestamp('sale_starts_at')->nullable()->after('sale_price');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_starts_at');
            $table->string('promo_label', 80)->nullable()->after('sale_ends_at')->comment('Nhãn KM: Sale, -20%, Flash...');
        });

        Schema::create('order_requests', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone', 40);
            $table->string('customer_email')->nullable();
            $table->string('customer_address')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->nullable()->comment('Snapshot tên SP lúc đặt');
            $table->unsignedInteger('quantity')->default(1);
            $table->text('note')->nullable();
            $table->string('status', 32)->default('new'); // new, contacted, confirmed, cancelled
            $table->text('admin_note')->nullable();
            $table->string('source', 40)->default('home'); // home, product, api
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_requests');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sale_price', 'sale_starts_at', 'sale_ends_at', 'promo_label']);
            $table->string('print_time')->nullable()->after('image');
        });
    }
};
