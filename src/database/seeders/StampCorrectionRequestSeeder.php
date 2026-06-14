<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Database\Seeder;

class StampCorrectionRequestSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::first();

        $attendances = Attendance::take(2)->get();

        // 承認待ち
        StampCorrectionRequest::create([
            'user_id' => $attendances[0]->user_id,
            'attendance_id' => $attendances[0]->id,
            'requested_clock_in' => '08:45:00',
            'requested_clock_out' => '18:15:00',
            'reason' => '打刻漏れのため',
            'status' => 0,
        ]);

        // 承認済み
        $approvedRequest=StampCorrectionRequest::create([
            'user_id' => $attendances[1]->user_id,
            'attendance_id' => $attendances[1]->id,
            'requested_clock_in' => '08:50:00',
            'requested_clock_out' => '18:10:00',
            'reason' => '修正申請テスト',
            'status' => 1,
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);

        // 承認済みなので、正式な勤怠情報も更新する
        $approvedRequest->attendance->update([
            'clock_in' => '08:50:00',
            'clock_out' => '18:10:00',
        ]);

    }
}