<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 255);
            $table->string('owner_name', 255)->nullable();
            $table->string('tax_code', 20)->nullable(); // MST
            $table->string('id_number', 30)->nullable(); // CCCD
            $table->string('phone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('ward', 120)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('province', 120)->nullable();
            $table->string('business_line', 255)->nullable(); // ngành nghề đăng ký
            $table->string('tax_office', 255)->nullable(); // CQT quản lý
            $table->string('method', 30)->default('presumptive'); // presumptive|declaration
            $table->string('filing_cycle', 20)->default('quarter'); // month|quarter|year
            $table->decimal('vat_rate', 8, 4)->default(1); // % ước tính GTGT
            $table->decimal('pit_rate', 8, 4)->default(0.5); // % ước tính TNCN
            $table->decimal('revenue_threshold', 15, 2)->nullable(); // ngưỡng cảnh báo năm
            $table->unsignedTinyInteger('filing_day')->default(30); // ngày hạn trong kỳ (tham chiếu)
            $table->unsignedTinyInteger('filing_month_offset')->default(1); // +tháng sau kỳ
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->text('disclaimer')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('source_type', 30); // product_sale | manual | adjustment
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('entry_code', 40)->unique();
            $table->string('description', 500);
            $table->decimal('amount', 15, 2); // + doanh thu, - điều chỉnh/trả
            $table->string('tax_group', 30)->default('commerce'); // commerce|service|production|other
            $table->string('payment_method', 30)->nullable();
            $table->string('customer_name', 120)->nullable();
            $table->string('customer_phone', 40)->nullable();
            $table->string('invoice_status', 20)->default('none'); // none|pending|issued|cancelled
            $table->string('invoice_number', 80)->nullable();
            $table->boolean('is_excluded')->default(false); // loại khỏi thuế (test)
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entry_date']);
            $table->index(['source_type', 'source_id']);
            $table->index(['is_excluded', 'entry_date']);
            $table->index(['tax_group', 'entry_date']);
        });

        Schema::create('tax_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_key', 20)->unique(); // 2026-Q1, 2026-03, 2026
            $table->string('period_type', 20); // month|quarter|year
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->date('due_on')->nullable();
            $table->string('status', 20)->default('open'); // open|closed
            $table->decimal('revenue_total', 15, 2)->default(0);
            $table->decimal('adjustment_total', 15, 2)->default(0);
            $table->decimal('taxable_revenue', 15, 2)->default(0);
            $table->decimal('estimated_vat', 15, 2)->default(0);
            $table->decimal('estimated_pit', 15, 2)->default(0);
            $table->decimal('estimated_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->date('paid_on')->nullable();
            $table->string('payment_ref', 120)->nullable();
            $table->text('snapshot_json')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['year', 'status']);
            $table->index(['due_on', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_periods');
        Schema::dropIfExists('tax_ledger_entries');
        Schema::dropIfExists('tax_profiles');
    }
};
