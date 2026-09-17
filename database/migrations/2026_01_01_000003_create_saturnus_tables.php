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
        // 1. Items (Registered/Unregistered Consumable Directory)
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('status')->default('registered'); // 'registered', 'unregistered'
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('unregistered_at')->nullable();
            $table->text('unregistration_reason')->nullable();
            $table->timestamps();
        });

        // 2. Form Items (Checksheet Item Registrasi)
        Schema::create('form_items', function (Blueprint $table) {
            $table->id();
            $table->string('form_number')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name')->nullable();
            $table->string('created_by_dept')->nullable();
            $table->string('kode_barang')->nullable();
            $table->string('nama_barang');
            $table->decimal('harga', 15, 2)->nullable();
            $table->string('estimasi_usia_pakai')->nullable();
            $table->string('kategori_penggunaan')->nullable();
            $table->string('kategori_ukuran')->nullable();
            $table->integer('min')->nullable();
            $table->integer('titik_order')->nullable();
            $table->integer('max')->nullable();
            $table->string('lead_time')->nullable();
            $table->boolean('is_b3')->default(false);
            $table->boolean('is_non_b3')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Form Approvals (4-Tier Workflow: User -> Staff -> Accounting -> Warehouse)
        Schema::create('form_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('form_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requestor_name')->nullable();
            $table->string('requestor_dept')->nullable();
            $table->string('form_date')->nullable();
            $table->string('status')->default('BUTUH APPROVAL STAFF');

            // Step 1: User / Pembuat
            $table->timestamp('user_signed_at')->nullable();
            $table->string('user_signer_name')->nullable();
            $table->text('user_comment')->nullable();

            // Step 2: Staff Approver
            $table->timestamp('staff_signed_at')->nullable();
            $table->string('staff_signer_name')->nullable();
            $table->text('staff_comment')->nullable();

            // Step 3: Accounting Approver
            $table->timestamp('accounting_signed_at')->nullable();
            $table->string('accounting_signer_name')->nullable();
            $table->text('accounting_comment')->nullable();

            // Step 4: Warehouse Consumable (Registrasi)
            $table->timestamp('warehouse_signed_at')->nullable();
            $table->string('warehouse_signer_name')->nullable();
            $table->text('warehouse_comment')->nullable();

            $table->timestamps();
        });

        // 4. Form Comments
        Schema::create('form_comments', function (Blueprint $table) {
            $table->id();
            $table->string('form_number')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');
            $table->string('user_dept')->nullable();
            $table->string('user_role')->nullable();
            $table->text('comment');
            $table->timestamps();
        });

        // 5. Unregistrasi Items
        Schema::create('unregistrasi_items', function (Blueprint $table) {
            $table->id();
            $table->string('form_number')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name')->nullable();
            $table->string('created_by_dept')->nullable();
            $table->string('kode_barang')->nullable();
            $table->string('nama_barang');
            $table->string('spesifikasi')->nullable();
            $table->string('kategori')->nullable();
            $table->text('keterangan')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 6. Unregistrasi Approvals (3-Step Workflow: User -> Staff -> Warehouse)
        Schema::create('unregistrasi_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('form_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requestor_name')->nullable();
            $table->string('requestor_dept')->nullable();
            $table->string('form_date')->nullable();
            $table->string('status')->default('Butuh Approval Staff / Section Head');

            // User stage
            $table->timestamp('user_signed_at')->nullable();
            $table->string('user_signer_name')->nullable();
            $table->text('user_comment')->nullable();

            // Staff stage
            $table->timestamp('staff_signed_at')->nullable();
            $table->string('staff_signer_name')->nullable();
            $table->text('staff_comment')->nullable();

            // Warehouse stage (Discontinue)
            $table->timestamp('warehouse_signed_at')->nullable();
            $table->string('warehouse_signer_name')->nullable();
            $table->text('warehouse_comment')->nullable();

            $table->timestamps();
        });

        // 7. Unregistrasi Comments
        Schema::create('unregistrasi_comments', function (Blueprint $table) {
            $table->id();
            $table->string('form_number')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');
            $table->string('user_dept')->nullable();
            $table->string('user_role')->nullable();
            $table->text('comment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unregistrasi_comments');
        Schema::dropIfExists('unregistrasi_approvals');
        Schema::dropIfExists('unregistrasi_items');
        Schema::dropIfExists('form_comments');
        Schema::dropIfExists('form_approvals');
        Schema::dropIfExists('form_items');
        Schema::dropIfExists('items');
    }
};
