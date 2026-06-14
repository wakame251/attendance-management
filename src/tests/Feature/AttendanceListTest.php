<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceListTest extends TestCase
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

    private function createUser()
    {
        return User::create([
            'user_name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }

    private function createAdmin()
    {
        return Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function createAttendance($user, $date, $clockIn = '09:00:00', $clockOut = '18:00:00')
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);
    }

    /**
     * 自分が行った勤怠情報が全て表示されている
     */
    public function test_attendance_list_displays_user_attendances()
    {
        $user = $this->createUser();

        $this->createAttendance($user, '2026-06-01');
        $this->createAttendance($user, '2026-06-02');

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('06/01');
        $response->assertSee('06/02');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
    
    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_attendance_list_displays_current_month()
    {
        Carbon::setTestNow('2026-06-05');

        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026/06');
    }
    
    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_attendance_list_displays_previous_month()
    {
        Carbon::setTestNow('2026-06-05');

        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-05');

        $response->assertStatus(200);
        $response->assertSee('2026/05');
    }

    /**
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_attendance_list_displays_next_month()
    {
        Carbon::setTestNow('2026-06-05');

        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-07');

        $response->assertStatus(200);
        $response->assertSee('2026/07');
    }
    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_redirects_to_attendance_detail()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance($user, '2026-06-01');

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
    }

    /**
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_attendance_detail_displays_user_name()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance($user, '2026-06-01');

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('テスト太郎');
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_attendance_detail_displays_selected_date()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance($user, '2026-06-01');

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('2026年');
        $response->assertSee('6月1日');
    }

    /**
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_displays_clock_in_and_clock_out()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance($user, '2026-06-01', '09:00:00', '18:00:00');

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_displays_break_time()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance($user, '2026-06-01');

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_clock_in_after_clock_out_shows_error()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance(
            $user,
            '2026-06-01'
        );

        $response = $this->actingAs($user)
            ->patch('/attendance/detail/' . $attendance->id, [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'reason' => '修正理由',
            ]);

        $response->assertSessionHasErrors([
            'clock_in'
        ]);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_start_after_clock_out_shows_error()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance(
            $user,
            '2026-06-01'
        );

        $response = $this->actingAs($user)
            ->patch('/attendance/detail/' . $attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'reason' => '修正理由',
                'breaks' => [
                    [
                        'break_start' => '19:00',
                        'break_end' => '19:30',
                    ]
                ]
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_start'
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_end_after_clock_out_shows_error()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance(
            $user,
            '2026-06-01'
        );

        $response = $this->actingAs($user)
            ->patch('/attendance/detail/' . $attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'reason' => '修正理由',
                'breaks' => [
                    [
                        'break_start' => '12:00',
                        'break_end' => '19:00',
                    ]
                ]
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_end'
        ]);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_reason_is_required()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance(
            $user,
            '2026-06-01'
        );

        $response = $this->actingAs($user)
            ->patch('/attendance/detail/' . $attendance->id, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'reason' => '',
            ]);

        $response->assertSessionHasErrors([
            'reason'
        ]);
    }

    /**
     * 修正申請処理が実行される
     */
    public function test_user_can_submit_correction_request()
    {
        $user = $this->createUser();
        $admin = $this->createAdmin();

        $attendance = $this->createAttendance(
            $user,
            '2026-06-01'
        );

        $response = $this->actingAs($user)
            ->patch('/attendance/detail/' . $attendance->id, [
                'clock_in' => '08:50',
                'clock_out' => '18:10',
                'reason' => '修正申請テスト',
            ]);

        $response->assertRedirect(
            '/attendance/detail/' . $attendance->id
        );

        $this->assertDatabaseHas('stamp_correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'reason' => '修正申請テスト',
            'status' => StampCorrectionRequest::STATUS_PENDING,
        ]);

        $correctionRequest = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($correctionRequest);

        // 管理者の申請一覧画面に表示される
        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('修正申請テスト');

        // 管理者の承認画面に表示される
        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/stamp_correction_request/approve/' . $correctionRequest->id);

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('08:50');
        $response->assertSee('18:10');
        $response->assertSee('修正申請テスト');
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_pending_requests_are_displayed()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance(
            $user,
            '2026-06-01'
        );

        \App\Models\StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '08:50:00',
            'requested_clock_out' => '18:10:00',
            'reason' => 'テスト申請',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        $response->assertSee('テスト申請');
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_approved_requests_are_displayed()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance(
            $user,
            '2026-06-01'
        );

        \App\Models\StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '08:50:00',
            'requested_clock_out' => '18:10:00',
            'reason' => '承認済み申請',
            'status' => 1,
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);

        $response->assertSee('承認済み申請');
    }

    /**
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function test_request_detail_redirects_to_attendance_detail()
    {
        $user = $this->createUser();

        $attendance = $this->createAttendance(
            $user,
            '2026-06-01'
        );

        \App\Models\StampCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '08:50:00',
            'requested_clock_out' => '18:10:00',
            'reason' => 'テスト申請',
            'status' => 0,
        ]);

        // 1. 申請一覧画面を開く
        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        // 2. 一覧に申請内容と詳細リンクが表示されている
        $response->assertSee('テスト申請');
        $response->assertSee('詳細');
        $response->assertSee('/attendance/detail/' . $attendance->id, false);

        // 3. 詳細リンク先にアクセスすると、勤怠詳細画面が表示される
        $response = $this->actingAs($user)
            ->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('2026年');
        $response->assertSee('6月1日');
    }


}