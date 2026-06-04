<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        $attendances = Attendance::with(['user', 'breaks'])
            ->whereDate('work_date', $date->toDateString())
            ->orderBy('user_id')
            ->get();

        $previousDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();

        return view('attendances.admin.index', compact(
            'date',
            'attendances',
            'previousDate',
            'nextDate'
        ));
    }

    public function edit($id)
    {
        $attendance = Attendance::with(['user', 'breaks'])
            ->findOrFail($id);

        return view('attendances.admin.edit', compact('attendance'));
    }

    public function update(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);

        $attendance->update([
            'clock_in' => $this->combineDateAndTime($attendance->work_date, $request->clock_in),
            'clock_out' => $this->combineDateAndTime($attendance->work_date, $request->clock_out),
        ]);

        $attendance->breaks()->delete();

        foreach ($request->input('breaks', []) as $break) {
            if (empty($break['break_start']) && empty($break['break_end'])) {
                continue;
            }

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => !empty($break['break_start'])
                    ? $this->combineDateAndTime($attendance->work_date, $break['break_start'])
                    : null,
                'break_end' => !empty($break['break_end'])
                    ? $this->combineDateAndTime($attendance->work_date, $break['break_end'])
                    : null,
            ]);
        }

        return redirect()->route('admin.attendance.edit', ['id' => $attendance->id]);
    }

    private function combineDateAndTime($date, string $time): Carbon
    {
        return Carbon::parse(Carbon::parse($date)->format('Y-m-d') . ' ' . $time);
    }
}