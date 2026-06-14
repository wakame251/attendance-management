<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceTest extends TestCase
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

    /**
     * ユーザーがログインする
     */
    private function createUser()
    {
        return User::create([
            'user_name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_datetime_is_displayed()
    {
        Carbon::setTestNow('2026-06-04 09:00:00');

        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('2026年6月4日');
        $response->assertSee('09:00');
    }

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_off_work_before_clock_in()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }
    
    /**
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_working_after_clock_in()
    {
        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }


    /**
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_on_break()
    {
        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }
    
    /**
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_finished_after_clock_out()
    {
        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }

    /**
     * 出勤ボタンが正しく機能する
     */
    public function test_user_can_clock_in()
    {
        Carbon::setTestNow('2026-06-04 09:00:00');

        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/attendance/clock-in');

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-06-04',
            'clock_in' => '09:00:00',
        ]);
    }

    /**
     * 出勤は一日一回のみできる
     */
    public function test_user_cannot_clock_in_twice_in_one_day()
    {
        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
        $response->assertSee('退勤');
        $response->assertSee('休憩入');
        $response->assertDontSee('attendance/clock-in');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_in_time_is_displayed_in_attendance_list()
    {
        Carbon::setTestNow('2026-06-04 09:00:00');

        $user = $this->createUser();

        $this->actingAs($user)
            ->post('/attendance/clock-in');

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertSee('09:00');
    }

    /**
     * 休憩ボタンが正しく機能する
     */
    public function test_user_can_start_break()
    {
        Carbon::setTestNow('2026-06-04 12:00:00');

        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/break-start');

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('breaks', [
            'break_start' => '12:00:00',
        ]);
    }

    /**
     * 休憩は一日に何回でもできる
     */
    public function test_user_can_take_multiple_breaks()
    {
        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee('休憩入');
    }

    /**
     * 休憩戻ボタンが正しく機能する
     */
    public function test_user_can_end_break()
    {
        Carbon::setTestNow('2026-06-04 13:00:00');

        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)->post('/attendance/break-end');

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);
    }

    /**
     * 休憩戻は一日に何回でもできる
     */
    public function test_user_can_return_from_multiple_breaks()
    {
        Carbon::setTestNow('2026-06-04 15:00:00');

        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/break-start');

        $response->assertRedirect('/attendance');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩戻');
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_break_time_is_displayed_in_attendance_list()
    {
        $user = $this->createUser();

        $attendance =Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertSee('1:00');
    }

    /**
     * 退勤ボタンが正しく機能する
     */
    public function test_user_can_clock_out()
    {
        Carbon::setTestNow('2026-06-04 18:00:00');

        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/clock-out');

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-06-04',
            'clock_out' => '18:00:00',
        ]);
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     */

    public function test_clock_out_time_is_displayed_in_attendance_list()
    {
        Carbon::setTestNow('2026-06-04 18:00:00');

        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/clock-out');

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertSee('18:00');
    }
}