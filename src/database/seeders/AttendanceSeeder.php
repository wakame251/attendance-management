<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {

            for ($i = 0; $i < 30; $i++) {

                $date = Carbon::today()->subDays($i);

                $attendance = Attendance::updateOrCreate([
                    'user_id' => $user->id,
                    'work_date' => $date->toDateString(),
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                ]);

                BreakTime::updateOrCreate([
                    'attendance_id' => $attendance->id,
                    'break_start' => '12:00:00',
                    'break_end' => '13:00:00',
                ]);
            }
        }
    }
}