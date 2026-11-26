<?php

namespace Tests\Feature;

use App\Mail\FormCommentNotificationMail;
use App\Models\FormApproval;
use App\Models\FormComment;
use App\Models\FormItem;
use App\Models\UnregistrasiApproval;
use App\Models\UnregistrasiItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SaturnusCommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_comment_on_form_registrasi_sends_database_and_email_notifications_to_stakeholders(): void
    {
        Mail::fake();

        $requestor = User::create([
            'name'       => 'Requestor Prod',
            'username'   => 'req_prod',
            'email'      => 'req@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'User',
            'department' => 'Production',
        ]);

        $staffProd = User::create([
            'name'       => 'Staff Prod',
            'username'   => 'staff_prod',
            'email'      => 'staff@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'Staff Approver',
            'department' => 'Production',
        ]);

        $accounting = User::create([
            'name'       => 'Acc Approver',
            'username'   => 'acc_user',
            'email'      => 'acc@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'Accounting Approver',
            'department' => 'Accounting',
        ]);

        $warehouse = User::create([
            'name'       => 'WHC Approver',
            'username'   => 'whc_user',
            'email'      => '', // Verify in-app notification works even with empty email
            'password'   => bcrypt('password'),
            'role'       => 'Warehouse Consumable',
            'department' => 'Warehouse Consumable',
        ]);

        $master = User::create([
            'name'       => 'Master Admin',
            'username'   => 'master_admin',
            'email'      => 'master@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'master',
            'department' => 'Warehouse Consumable',
        ]);

        $prevCommenter = User::create([
            'name'       => 'Prev Commenter',
            'username'   => 'prev_comm',
            'email'      => 'prev@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'User',
            'department' => 'Production',
        ]);

        $formNo = '01/PRODUCTION/11-2026';

        FormApproval::create([
            'form_number'    => $formNo,
            'user_id'        => $requestor->id,
            'requestor_name' => $requestor->name,
            'requestor_dept' => 'Production',
            'status'         => 'Butuh Approval Staff / Section Head',
        ]);

        FormItem::create([
            'form_number'     => $formNo,
            'user_id'         => $requestor->id,
            'created_by_dept' => 'Production',
            'kode_barang'     => 'KODE-1',
            'nama_barang'     => 'Baut Baja',
        ]);

        FormComment::create([
            'form_number' => $formNo,
            'user_id'     => $prevCommenter->id,
            'user_name'   => $prevCommenter->name,
            'user_dept'   => 'Production',
            'user_role'   => 'User',
            'comment'     => 'Komentar awal',
        ]);

        $resp = $this->actingAs($requestor)
            ->postJson('/saturnus/form-registrasi/comment', [
                'form_number' => $formNo,
                'comment'     => 'Mohon dicek kembali estimasi usia pakainya.',
            ]);

        $resp->assertStatus(200)
            ->assertJson([
                'success'        => true,
                'notified_count' => 5,
            ]);

        // Commenter does NOT notify themselves
        $this->assertCount(0, $requestor->fresh()->notifications);

        // All other 5 stakeholders receive database notification
        foreach ([$staffProd, $accounting, $warehouse, $master, $prevCommenter] as $recipient) {
            $notifs = $recipient->fresh()->notifications;
            $this->assertCount(1, $notifs);
            $this->assertSame('Registrasi', $notifs->first()->data['module']);
            $this->assertSame($formNo, $notifs->first()->data['form_number']);
        }

        // Users with email receive FormCommentNotificationMail; user without email does not
        Mail::assertSent(FormCommentNotificationMail::class, 4);
        Mail::assertSent(FormCommentNotificationMail::class, fn ($mail) => $mail->hasTo('staff@example.com'));
        Mail::assertSent(FormCommentNotificationMail::class, fn ($mail) => $mail->hasTo('acc@example.com'));
        Mail::assertSent(FormCommentNotificationMail::class, fn ($mail) => $mail->hasTo('master@example.com'));
        Mail::assertSent(FormCommentNotificationMail::class, fn ($mail) => $mail->hasTo('prev@example.com'));
        Mail::assertNotSent(FormCommentNotificationMail::class, fn ($mail) => $mail->hasTo('req@example.com'));
    }

    public function test_comment_on_form_unregistrasi_notifies_relevant_stakeholders_and_skips_accounting(): void
    {
        Mail::fake();

        $requestor = User::create([
            'name'       => 'Req Maint',
            'username'   => 'req_maint',
            'email'      => 'reqmaint@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'User',
            'department' => 'Maintenance',
        ]);

        $staffMaint = User::create([
            'name'       => 'Staff Maint',
            'username'   => 'staff_maint',
            'email'      => 'staffmaint@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'Staff Approver',
            'department' => 'Maintenance',
        ]);

        $accounting = User::create([
            'name'       => 'Acc Only',
            'username'   => 'acc_only',
            'email'      => 'acconly@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'Accounting Approver',
            'department' => 'Accounting',
        ]);

        $warehouse = User::create([
            'name'       => 'WHC Unreg',
            'username'   => 'whc_unreg',
            'email'      => 'whcunreg@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'Warehouse Consumable',
            'department' => 'Warehouse Consumable',
        ]);

        $formNo = '01/MAINTENANCE/11-2026';

        UnregistrasiApproval::create([
            'form_number'    => $formNo,
            'user_id'        => $requestor->id,
            'requestor_name' => $requestor->name,
            'requestor_dept' => 'Maintenance',
            'status'         => 'Butuh Approval Staff / Section Head',
        ]);

        UnregistrasiItem::create([
            'form_number'     => $formNo,
            'user_id'         => $requestor->id,
            'created_by_dept' => 'Maintenance',
            'kode_barang'     => 'KODE-U1',
            'nama_barang'     => 'Oli Mesin',
        ]);

        $resp = $this->actingAs($staffMaint)
            ->postJson('/saturnus/form-unregistrasi/comment', [
                'form_number' => $formNo,
                'comment'     => 'Stok fisik sudah habis di gudang.',
            ]);

        $resp->assertStatus(200)
            ->assertJson([
                'success'        => true,
                'notified_count' => 2,
            ]);

        $this->assertCount(1, $requestor->fresh()->notifications);
        $this->assertCount(1, $warehouse->fresh()->notifications);
        $this->assertCount(0, $staffMaint->fresh()->notifications);
        $this->assertCount(0, $accounting->fresh()->notifications);

        Mail::assertSent(FormCommentNotificationMail::class, 2);
        Mail::assertNotSent(FormCommentNotificationMail::class, fn ($mail) => $mail->hasTo('acconly@example.com'));
    }

    public function test_notification_endpoints_index_open_mark_read_and_isolation(): void
    {
        Mail::fake();

        $requestor = User::create([
            'name'       => 'Req User',
            'username'   => 'req_u',
            'email'      => 'requ@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'User',
            'department' => 'Production',
        ]);

        $master = User::create([
            'name'       => 'Master User',
            'username'   => 'master_u',
            'email'      => 'masteru@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'master',
            'department' => 'Warehouse Consumable',
        ]);

        $formNo = '02/PRODUCTION/11-2026';

        FormApproval::create([
            'form_number'    => $formNo,
            'user_id'        => $requestor->id,
            'requestor_dept' => 'Production',
            'status'         => 'Butuh Approval Staff / Section Head',
        ]);

        FormItem::create([
            'form_number'     => $formNo,
            'user_id'         => $requestor->id,
            'created_by_dept' => 'Production',
            'kode_barang'     => 'KODE-2',
            'nama_barang'     => 'Masker',
        ]);

        $this->actingAs($requestor)->postJson('/saturnus/form-registrasi/comment', [
            'form_number' => $formNo,
            'comment'     => 'Tolong segera diproses.',
        ])->assertStatus(200);

        $notif = $master->fresh()->notifications()->first();
        $this->assertNotNull($notif);

        // Index returns unread notification
        $this->actingAs($master)
            ->getJson('/notifications')
            ->assertStatus(200)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.form_number', $formNo);

        // Other user cannot access master's notification
        $this->actingAs($requestor)
            ->postJson("/notifications/{$notif->id}/read")
            ->assertStatus(404);

        // Open marks as read and redirects to form
        $openResp = $this->actingAs($master)->get("/notifications/{$notif->id}/open");
        $openResp->assertRedirect(route('saturnus.form_registrasi', ['form' => $formNo]));
        $this->assertNotNull($notif->fresh()->read_at);

        // Create another comment so markAllAsRead can be verified
        $this->actingAs($requestor)->postJson('/saturnus/form-registrasi/comment', [
            'form_number' => $formNo,
            'comment'     => 'Komentar kedua.',
        ])->assertStatus(200);

        $this->assertSame(1, $master->fresh()->unreadNotifications()->count());

        $this->actingAs($master)
            ->postJson('/notifications/read-all')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'unread_count' => 0]);

        $this->assertSame(0, $master->fresh()->unreadNotifications()->count());
    }
}
