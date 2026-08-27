<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Item Masters
        Schema::create('item_masters', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->index();
            $table->string('item_name');
            $table->integer('outstanding')->default(0);
            $table->integer('ending_balance')->default(0);
            $table->integer('maximal_stock')->default(0);
            $table->integer('order_point')->default(0);
            $table->integer('minimal_stock')->default(0);
            $table->string('user')->nullable();
            $table->string('outstanding_pp')->nullable();
            $table->text('note')->nullable();
            
            // Follow up & Request WHC fields
            $table->string('sudah_follow')->nullable();
            $table->timestamp('sudah_follow_edited_at')->nullable();
            $table->date('pengiriman_tanggal')->nullable();
            $table->timestamp('pengiriman_tanggal_edited_at')->nullable();
            $table->integer('qty_akan_dikirim')->nullable();
            $table->string('selected_po_no')->nullable();
            $table->integer('request_whc')->nullable();
            $table->timestamp('request_whc_edited_at')->nullable();
            $table->date('request_whc_date')->nullable();
            $table->timestamp('request_whc_date_edited_at')->nullable();
            
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        // 2. Data POs
        Schema::create('data_pos', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->index();
            $table->string('item_name');
            $table->string('supplier_name')->nullable();
            $table->integer('scheduled_receipt_qty')->default(0);
            $table->string('po_no')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        // 3. Kedatangan Barangs
        Schema::create('kedatangan_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->index();
            $table->string('item_name');
            $table->string('supplier_name')->nullable();
            $table->integer('scheduled_receipt_qty')->default(0);
            $table->string('po_no')->nullable();
            $table->date('arrival_date');
            $table->integer('arrived_qty')->default(0);
            $table->string('po_validation')->nullable();
            $table->date('pengiriman_tanggal')->nullable();
            $table->integer('request_whc')->nullable();
            $table->date('request_whc_date')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        // 4. Histories
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->date('arrival_date');
            $table->string('item_code')->index();
            $table->string('item_name');
            $table->string('supplier_name')->nullable();
            $table->string('po_no')->nullable();
            $table->integer('scheduled_receipt_qty')->default(0);
            $table->integer('jumlah_item_datang')->default(0);
            $table->date('pengiriman_tanggal')->nullable();
            $table->integer('request_whc')->nullable();
            $table->date('request_whc_date')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
        });

        // 5. Item Outstandings
        Schema::create('item_outstandings', function (Blueprint $table) {
            $table->id();
            $table->date('request_date')->nullable();
            $table->string('item_code')->index();
            $table->string('item_name');
            $table->string('user')->nullable();
            $table->integer('outstanding')->default(0);
            $table->integer('sudah_pp')->default(0);
            $table->string('outstanding_pp')->nullable();
            $table->integer('ending_balance')->default(0);
            $table->integer('maximal_stock')->default(0);
            $table->integer('order_point')->default(0);
            $table->integer('minimal_stock')->default(0);
            $table->text('note')->nullable();
            $table->string('duplicate_note')->nullable();
            
            // Follow up fields
            $table->string('sudah_follow')->nullable();
            $table->timestamp('sudah_follow_edited_at')->nullable();
            $table->date('pengiriman_tanggal')->nullable();
            $table->timestamp('pengiriman_tanggal_edited_at')->nullable();
            $table->integer('qty_akan_dikirim')->nullable();
            $table->string('selected_po_no')->nullable();
            $table->integer('request_whc')->nullable();
            $table->timestamp('request_whc_edited_at')->nullable();
            $table->date('request_whc_date')->nullable();
            $table->timestamp('request_whc_date_edited_at')->nullable();
            
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        // 6. Follow Up POs
        Schema::create('follow_up_pos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_master_id')->constrained('item_masters')->onDelete('cascade');
            $table->string('po_no')->nullable();
            $table->integer('qty_akan_dikirim')->nullable();
            $table->date('pengiriman_tanggal')->nullable();
            $table->string('sudah_follow')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_pos');
        Schema::dropIfExists('item_outstandings');
        Schema::dropIfExists('histories');
        Schema::dropIfExists('kedatangan_barangs');
        Schema::dropIfExists('data_pos');
        Schema::dropIfExists('item_masters');
    }
};
