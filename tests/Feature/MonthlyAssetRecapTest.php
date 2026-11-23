<?php

namespace Tests\Feature;

use App\Models\FormApproval;
use App\Models\FormItem;
use App\Models\UnregistrasiApproval;
use App\Models\UnregistrasiItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyAssetRecapTest extends TestCase
{
    use RefreshDatabase;

    private function createMasterUser(): User
    {
        return User::create([
            'name'       => 'Admin Master',
            'username'   => 'admin_recap',
            'email'      => 'admin_recap@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'master',
            'department' => 'Warehouse Consumable',
        ]);
    }

    public function test_monthly_recap_combines_completed_registrations_and_unregistrations_and_excludes_pending_or_cancelled(): void
    {
        $user = $this->createMasterUser();

        // 1. Completed Registration in November 2026 (2 items on same form)
        FormApproval::create([
            'form_number'         => '01/PRODUCTION/11-2026',
            'user_id'             => $user->id,
            'requestor_name'      => 'Tester',
            'requestor_dept'      => 'Production',
            'form_date'           => '10-11-2026',
            'status'              => 'Item Telah didaftarkan',
            'warehouse_signed_at' => '2026-11-12 10:00:00',
        ]);
        FormItem::create([
            'form_number'         => '01/PRODUCTION/11-2026',
            'user_id'             => $user->id,
            'created_by_name'     => 'Tester',
            'created_by_dept'     => 'Production',
            'kode_barang'         => 'BRG-001',
            'nama_barang'         => 'Sarung Tangan Safety',
            'harga'               => 1500000,
            'estimasi_usia_pakai' => '24 Bulan',
        ]);
        FormItem::create([
            'form_number'         => '01/PRODUCTION/11-2026',
            'user_id'             => $user->id,
            'created_by_name'     => 'Tester',
            'created_by_dept'     => 'Production',
            'kode_barang'         => 'BRG-002',
            'nama_barang'         => 'Helm Proyek',
            'harga'               => 250000,
            'estimasi_usia_pakai' => '5 Tahun',
        ]);

        // 2. Pending Registration in November 2026 (must NOT appear)
        FormApproval::create([
            'form_number'    => '02/PRODUCTION/11-2026',
            'user_id'        => $user->id,
            'requestor_name' => 'Tester',
            'requestor_dept' => 'Production',
            'form_date'      => '11-11-2026',
            'status'         => 'Butuh Approval Staff / Section Head',
        ]);
        FormItem::create([
            'form_number'     => '02/PRODUCTION/11-2026',
            'user_id'         => $user->id,
            'created_by_dept' => 'Production',
            'kode_barang'     => 'BRG-PENDING',
            'nama_barang'     => 'Pending Item',
        ]);

        // 3. Completed Unregistration in November 2026
        UnregistrasiApproval::create([
            'form_number'         => '01/MAINTENANCE/11-2026',
            'user_id'             => $user->id,
            'requestor_name'      => 'Maint',
            'requestor_dept'      => 'Maintenance',
            'form_date'           => '14-11-2026',
            'status'              => 'Telah Discontinue oleh Warehouse Consumable',
            'warehouse_signed_at' => '2026-11-15 14:30:00',
        ]);
        UnregistrasiItem::create([
            'form_number'     => '01/MAINTENANCE/11-2026',
            'user_id'         => $user->id,
            'created_by_dept' => 'Maintenance',
            'kode_barang'     => 'BRG-001',
            'nama_barang'     => 'Sarung Tangan Safety',
            'keterangan'      => 'Discontinue',
        ]);

        // 4. Cancelled Unregistration in November 2026 (must NOT appear)
        UnregistrasiApproval::create([
            'form_number'         => '02/MAINTENANCE/11-2026',
            'user_id'             => $user->id,
            'requestor_name'      => 'Maint',
            'requestor_dept'      => 'Maintenance',
            'form_date'           => '16-11-2026',
            'status'              => 'Dibatalkan',
            'warehouse_signed_at' => '2026-11-16 09:00:00',
        ]);
        UnregistrasiItem::create([
            'form_number'     => '02/MAINTENANCE/11-2026',
            'user_id'         => $user->id,
            'created_by_dept' => 'Maintenance',
            'kode_barang'     => 'BRG-CANCEL',
            'nama_barang'     => 'Cancelled Item',
        ]);

        $response = $this->actingAs($user)->get('/saturnus/monthly-recap?month=11&year=2026');

        $response->assertStatus(200);
        $response->assertSee('MONTHLY ASSET RECAP');
        $response->assertSee('November 2026');
        $response->assertSee('01/PRODUCTION/11-2026');
        $response->assertSee('Registrasi');
        $response->assertSee('BRG-001');
        $response->assertSee('BRG-002');
        $response->assertSee('Rp 1.500.000');
        $response->assertSee('24 Bulan');
        $response->assertSee('5 Tahun');
        $response->assertSee('01/MAINTENANCE/11-2026');
        $response->assertSee('Unregistrasi');
        $response->assertDontSee('BRG-PENDING');
        $response->assertDontSee('BRG-CANCEL');
    }

    public function test_monthly_recap_search_and_type_filter_work(): void
    {
        $user = $this->createMasterUser();

        FormApproval::create([
            'form_number'         => '01/PRODUCTION/11-2026',
            'user_id'             => $user->id,
            'requestor_dept'      => 'Production',
            'status'              => 'Item Telah didaftarkan',
            'warehouse_signed_at' => '2026-11-12 10:00:00',
        ]);
        FormItem::create([
            'form_number'     => '01/PRODUCTION/11-2026',
            'user_id'         => $user->id,
            'created_by_dept' => 'Production',
            'kode_barang'     => 'REG-100',
            'nama_barang'     => 'Registered Wrench',
        ]);

        UnregistrasiApproval::create([
            'form_number'         => '02/MAINTENANCE/11-2026',
            'user_id'             => $user->id,
            'requestor_dept'      => 'Maintenance',
            'status'              => 'Telah Discontinue oleh Warehouse Consumable',
            'warehouse_signed_at' => '2026-11-15 10:00:00',
        ]);
        UnregistrasiItem::create([
            'form_number'     => '02/MAINTENANCE/11-2026',
            'user_id'         => $user->id,
            'created_by_dept' => 'Maintenance',
            'kode_barang'     => 'UNREG-200',
            'nama_barang'     => 'Unregistered Hammer',
        ]);

        $respUnreg = $this->actingAs($user)->get('/saturnus/monthly-recap?month=11&year=2026&type=unregistration');
        $respUnreg->assertStatus(200);
        $respUnreg->assertSee('UNREG-200');
        $respUnreg->assertDontSee('REG-100');

        $respSearch = $this->actingAs($user)->get('/saturnus/monthly-recap?month=11&year=2026&search=Wrench');
        $respSearch->assertStatus(200);
        $respSearch->assertSee('REG-100');
        $respSearch->assertDontSee('UNREG-200');
    }

    public function test_export_excel_and_pdf_return_valid_downloads(): void
    {
        $user = $this->createMasterUser();

        FormApproval::create([
            'form_number'         => '01/PRODUCTION/11-2026',
            'user_id'             => $user->id,
            'requestor_dept'      => 'Production',
            'status'              => 'Item Telah didaftarkan',
            'warehouse_signed_at' => '2026-11-12 10:00:00',
        ]);
        FormItem::create([
            'form_number'         => '01/PRODUCTION/11-2026',
            'user_id'             => $user->id,
            'created_by_dept'     => 'Production',
            'kode_barang'         => 'EXP-01',
            'nama_barang'         => 'Exported Item',
            'harga'               => 750000,
            'estimasi_usia_pakai' => '12 Bulan',
        ]);

        $excelResp = $this->actingAs($user)->get('/saturnus/monthly-recap/export-excel?month=11&year=2026');
        $excelResp->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $excelResp->headers->get('Content-Type')
        );

        $pdfResp = $this->actingAs($user)->get('/saturnus/monthly-recap/export-pdf?month=11&year=2026');
        $pdfResp->assertStatus(200);
        $this->assertSame('application/pdf', $pdfResp->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-1.4', $pdfResp->getContent());
        $this->assertStringContainsString('MONTHLY ASSET RECAP', $pdfResp->getContent());
        $this->assertStringContainsString('\(Registrasi\)', $pdfResp->getContent());
    }
}
