<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\StampCorrectionRequestBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 各テスト後に、Carbonで固定した現在時刻を解除する
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createAdmin()
    {
        return Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function createUser($name = 'テスト太郎', $email = 'user@example.com')
    {
        return User::create([
            'user_name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }

    private function createAttendance($user, $date = '2026-06-05')
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_admin_can_see_all_users_attendance_for_selected_date()
    {
        $admin = $this->createAdmin();

        $user1 = $this->createUser('山田 太郎', 'yamada@example.com');
        $user2 = $this->createUser('佐藤 花子', 'sato@example.com');

        $this->createAttendance($user1, '2026-06-05');
        $this->createAttendance($user2, '2026-06-05');

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/list?date=2026-06-05');

        $response->assertStatus(200);
        $response->assertSee('山田 太郎');
        $response->assertSee('佐藤 花子');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_admin_attendance_page_displays_current_date()
    {
        Carbon::setTestNow('2026-06-05');

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026/06/05');
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_admin_can_see_previous_day_attendance()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->createAttendance($user, '2026-06-04');

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/list?date=2026-06-04');

        $response->assertStatus(200);
        $response->assertSee('2026/06/04');
        $response->assertSee('テスト太郎');
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_admin_can_see_next_day_attendance()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->createAttendance($user, '2026-06-06');

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/list?date=2026-06-06');

        $response->assertStatus(200);
        $response->assertSee('2026/06/06');
        $response->assertSee('テスト太郎');
    }

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_admin_can_see_attendance_detail()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $attendance = $this->createAttendance($user, '2026-06-05');

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('2026年');
        $response->assertSee('6月5日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_attendance_edit_clock_in_after_clock_out_shows_error()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $attendance = $this->createAttendance($user, '2026-06-05');

        $response = $this->actingAs($admin, 'admin')
            ->patch('/admin/attendance/' . $attendance->id, [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'breaks' => [],
                'reason' => '修正テスト',
            ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_attendance_edit_break_start_after_clock_out_shows_error()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $attendance = $this->createAttendance($user, '2026-06-05');

        $response = $this->actingAs($admin, 'admin')
            ->patch('/admin/attendance/' . $attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '19:00',
                        'break_end' => '20:00',
                    ],
                ],
                'reason' => '修正テスト',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_start' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_attendance_edit_break_end_after_clock_out_shows_error()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $attendance = $this->createAttendance($user, '2026-06-05');

        $response = $this->actingAs($admin, 'admin')
            ->patch('/admin/attendance/' . $attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '12:00',
                        'break_end' => '19:00',
                    ],
                ],
                'reason' => '修正テスト',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_admin_attendance_edit_reason_is_required()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $attendance = $this->createAttendance($user, '2026-06-05');

        $response = $this->actingAs($admin, 'admin')
            ->patch('/admin/attendance/' . $attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [],
                'reason' => '',
            ]);

        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください',
        ]);
    }

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_admin_can_see_all_users()
    {
        $admin = $this->createAdmin();

        $this->createUser('山田 太郎', 'yamada@example.com');
        $this->createUser('佐藤 花子', 'sato@example.com');

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('山田 太郎');
        $response->assertSee('yamada@example.com');
        $response->assertSee('佐藤 花子');
        $response->assertSee('sato@example.com');
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     */
    public function test_admin_can_see_staff_attendance()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser('山田 太郎', 'yamada@example.com');

        $this->createAttendance($user, '2026-06-05');

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/staff/' . $user->id . '?month=2026-06');

        $response->assertStatus(200);
        $response->assertSee('山田 太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_admin_can_see_staff_attendance_list_displays_previous_month()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/staff/' . $user->id . '?month=2026-05');

        $response->assertStatus(200);
        $response->assertSee('2026/05');
    }

    /**
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function 
    test_admin_can_see_staff_attendance_list_displays_next_month()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/staff/' . $user->id . '?month=2026-07');

        $response->assertStatus(200);
        $response->assertSee('2026/07');
    }

    /**
     * 承認待ちの修正申請が全て表示されている
     */
    public function test_admin_can_see_pending_stamp_correction_requests()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $attendance = $this->createAttendance($user);

        StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '08:50:00',
            'requested_clock_out' => '18:10:00',
            'reason' => '打刻漏れのため',
            'status' => 0,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('打刻漏れのため');
    }

    /**
     * 承認済みの修正申請が全て表示されている
     */
    public function test_admin_can_see_approved_stamp_correction_requests()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $attendance = $this->createAttendance($user);

        StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '08:50:00',
            'requested_clock_out' => '18:10:00',
            'reason' => '承認済みテスト',
            'status' => 1,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('承認済みテスト');
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     */
    public function test_admin_can_see_stamp_correction_request_detail()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $attendance = $this->createAttendance($user);

        $request = StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '08:50:00',
            'requested_clock_out' => '18:10:00',
            'reason' => '申請詳細テスト',
            'status' => 0,
        ]);

        StampCorrectionRequestBreak::create([
            'stamp_correction_request_id' => $request->id,
            'break_start' => '12:10:00',
            'break_end' => '13:10:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/stamp_correction_request/approve/' . $request->id);

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('08:50');
        $response->assertSee('18:10');
        $response->assertSee('12:10');
        $response->assertSee('13:10');
        $response->assertSee('申請詳細テスト');
    }

    /**
     * 修正申請の承認処理が正しく行われる
     */
    public function test_admin_can_approve_stamp_correction_request()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $attendance = $this->createAttendance($user);

        $request = StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '08:50:00',
            'requested_clock_out' => '18:10:00',
            'reason' => '打刻修正',
            'status' => 0,
        ]);

        StampCorrectionRequestBreak::create([
            'stamp_correction_request_id' => $request->id,
            'break_start' => '12:10:00',
            'break_end' => '13:10:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/stamp_correction_request/approve/' . $request->id);

        $response->assertRedirect(
            '/admin/stamp_correction_request/approve/' . $request->id
        );

        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $request->id,
            'status' => 1,
            'approved_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '08:50:00',
            'clock_out' => '18:10:00',
        ]);

        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => '12:10:00',
            'break_end' => '13:10:00',
        ]);
    }
}