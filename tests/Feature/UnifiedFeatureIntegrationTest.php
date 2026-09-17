<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ItemMaster;
use App\Models\DataPO;
use App\Models\FollowUpPO;
use App\Models\ItemOutstanding;
use App\Models\KedatanganBarang;
use App\Models\History;
use App\Models\Item;
use App\Models\FormItem;
use App\Models\FormApproval;
use App\Models\UnregistrasiItem;
use App\Models\UnregistrasiApproval;

class UnifiedFeatureIntegrationTest extends TestCase
{
    public function test_mars_item_master_and_data_po_flow()
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Create a test item master
        $testCode = 'TEST-ITEM-' . time();
        $response = $this->actingAs($admin)->post('/mars/item-master', [
            'item_code'      => $testCode,
            'item_name'      => 'Baut M10x50 Special',
            'outstanding'    => 100,
            'ending_balance' => 10,
            'maximal_stock'  => 500,
            'order_point'    => 50,
            'minimal_stock'  => 20,
            'user'           => 'Production Test',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('item_masters', ['item_code' => $testCode]);

        // 2. Add PO for this item
        $poNumber = 'PO-' . time();
        $dataPO = DataPO::create([
            'item_code'             => $testCode,
            'item_name'             => 'Baut M10x50 Special',
            'supplier_name'         => 'PT Fastener Jaya',
            'scheduled_receipt_qty' => 100,
            'po_no'                 => $poNumber,
            'imported_at'           => now(),
        ]);
        $this->assertDatabaseHas('data_pos', ['po_no' => $poNumber]);

        // 3. Purchasing updates follow up
        $purchasing = User::where('username', 'purchasing')->first();
        $master = ItemMaster::where('item_code', $testCode)->first();

        $response = $this->actingAs($purchasing)->post("/mars/item-minim/{$master->id}/update-follow-up", [
            'qty_akan_dikirim'   => 100,
            'pengiriman_tanggal' => '2026-09-01',
            'selected_po_no'     => $poNumber,
            'sudah_follow'       => 'YES',
        ]);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('follow_up_pos', [
            'item_master_id'   => $master->id,
            'po_no'            => $poNumber,
            'qty_akan_dikirim' => 100,
        ]);
    }

    public function test_saturnus_form_registration_and_approval_workflow()
    {
        $budiUser = User::where('username', 'budi_user')->first();
        $staff = User::where('username', 'staff')->first();
        $whc = User::where('username', 'whc')->first();

        $uniqueFormNumber = '99/TEST-UNIT/' . date('m-Y');

        // Clean up if already exists
        FormItem::where('form_number', $uniqueFormNumber)->delete();
        FormApproval::where('form_number', $uniqueFormNumber)->delete();

        // 1. Production User submits a consumable registration form
        $response = $this->actingAs($budiUser)->post('/saturnus/form-registrasi/item', [
            'nama_barang'         => 'Sarung Tangan Nitrile Blue XL',
            'harga'               => 150000,
            'estimasi_usia_pakai' => '30 Hari',
            'kategori_penggunaan' => 'NON B3',
            'kategori_ukuran'     => 'Box',
            'min'                 => 10,
            'titik_order'         => 20,
            'max'                 => 50,
            'lead_time'           => '3 Hari',
            'form_number'         => $uniqueFormNumber,
            'form_action_type'    => 'add_item',
        ]);
        $response->assertSessionHasNoErrors();

        $formItem = FormItem::where('form_number', $uniqueFormNumber)->first();
        $this->assertNotNull($formItem);

        $approval = FormApproval::where('form_number', $uniqueFormNumber)->first();
        $this->assertNotNull($approval);
        $this->assertEquals('Butuh Approval Staff / Section Head', $approval->status);

        // 2. Staff approves the form
        $response = $this->actingAs($staff)->post('/saturnus/form-registrasi/approve', [
            'form_number' => $uniqueFormNumber,
            'stage'       => 'staff',
            'signer_name' => $staff->name,
            'comment'     => 'Disetujui untuk pengadaan bulanan.',
        ]);
        $response->assertSessionHasNoErrors();

        $approval->refresh();
        $this->assertEquals('Butuh Registrasi Kode (Warehouse Consumable)', $approval->status);

        // 3. Warehouse Consumable performs final code registration
        $assignedCode = 'CSM-2026-UNIT-' . time();
        $response = $this->actingAs($whc)->post('/saturnus/form-registrasi/approve', [
            'form_number'    => $uniqueFormNumber,
            'stage'          => 'warehouse',
            'signer_name'    => $whc->name,
            'comment'        => 'Kode barang diterbitkan dan aktif.',
            'assigned_codes' => [
                $formItem->id => $assignedCode,
            ],
        ]);
        $response->assertSessionHasNoErrors();

        $approval->refresh();
        $this->assertEquals('SELESAI (SUDAH TERDAFTAR)', $approval->status);

        // Item should now exist in items directory
        $this->assertDatabaseHas('items', [
            'item_code' => $assignedCode,
            'name'      => 'Sarung Tangan Nitrile Blue XL',
            'status'    => 'registered',
        ]);
    }
}
