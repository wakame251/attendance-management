<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Http\Request;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\StampCorrectionRequest;
use App\Models\StampCorrectionRequestBreak;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $attendance = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        $status = '勤務外';

        if ($attendance) {
            $activeBreak = $attendance->breaks()
                ->whereNull('break_end')
                ->latest('break_start')
                ->first();

            if ($attendance->clock_out) {
                $status = '退勤済';
            } elseif ($activeBreak) {
                $status = '休憩中';
            } elseif ($attendance->clock_in) {
                $status = '出勤中';
            }
        }

        return view('attendances.create', compact('attendance', 'status'));
    }

    public function clockIn(Request $request)
    {
        $today = now()->toDateString();

        Attendance::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'work_date' => $today,
            ],
            [
                'clock_in' => now(),
            ]
        );

        return redirect()->route('attendance.create');
    }

    public function clockOut(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('work_date', now()->toDateString())
            ->whereNull('clock_out')
            ->firstOrFail();

        $attendance->update([
            'clock_out' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function breakStart(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('work_date', now()->toDateString())
            ->whereNull('clock_out')
            ->firstOrFail();

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function breakEnd(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('work_date', now()->toDateString())
            ->firstOrFail();

        $break = $attendance->breaks()
            ->whereNull('break_end')
            ->latest('break_start')
            ->firstOrFail();

        $break->update([
            'break_end' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $month = $request->query('month', now()->format('Y-m'));

        $currentMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString(),
            ])
            ->get()
            ->keyBy(function ($attendance) {
                return $attendance->work_date->format('Y-m-d');
            });

        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $attendanceList = [];

        foreach ($dates as $date) {
            $dateKey = $date->format('Y-m-d');
            $attendance = $attendances->get($dateKey);

            $breakMinutes = 0;
            $workMinutes = null;

            if ($attendance) {
                foreach ($attendance->breaks as $break) {
                    if ($break->break_start && $break->break_end) {
                        $breakMinutes += Carbon::parse($break->break_start)
                            ->diffInMinutes(Carbon::parse($break->break_end));
                    }
                }

                if ($attendance->clock_in && $attendance->clock_out) {
                    $totalMinutes = Carbon::parse($attendance->clock_in)
                        ->diffInMinutes(Carbon::parse($attendance->clock_out));

                    $workMinutes = $totalMinutes - $breakMinutes;
                }
            }

            $attendanceList[] = [
                'date' => $date->copy(),
                'attendance' => $attendance,
                'break_time' => $breakMinutes > 0 ? $this->formatMinutes($breakMinutes) : '',
                'work_time' => !is_null($workMinutes) ? $this->formatMinutes($workMinutes) : '',
            ];
        }

        return view('attendances.user.index', [
            'currentMonth' => $currentMonth,
            'prevMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
            'attendanceList' => $attendanceList,
        ]);
    }

    private function formatMinutes(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%d:%02d', $hours, $mins);
    }

    public function edit(Request $request, $id)
    {
        $attendance = Attendance::with([
                'breaks',
                'stampCorrectionRequests.breaks',
            ])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $pendingRequest = $attendance->stampCorrectionRequests()
            ->where('status', StampCorrectionRequest::STATUS_PENDING)
            ->with('breaks')
            ->latest()
            ->first();

        $displayRequest = $pendingRequest;

        return view('attendances.user.edit', compact('attendance', 'pendingRequest', 'displayRequest'));
    }

    public function update(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $hasPendingRequest = $attendance->stampCorrectionRequests()
            ->where('status', StampCorrectionRequest::STATUS_PENDING)
            ->exists();

        if ($hasPendingRequest) {
            return redirect()->route('attendance.edit', ['id' => $attendance->id]);
        }

        $correctionRequest = StampCorrectionRequest::create([
            'user_id' => $request->user()->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => $this->combineDateAndTime($attendance->work_date, $request->clock_in),
            'requested_clock_out' => $this->combineDateAndTime($attendance->work_date, $request->clock_out),
            'reason' => $request->reason,
            'status' => StampCorrectionRequest::STATUS_PENDING,
        ]);

        foreach ($request->input('breaks', []) as $break) {
            if (empty($break['break_start']) && empty($break['break_end'])) {
                continue;
            }

            StampCorrectionRequestBreak::create([
                'stamp_correction_request_id' => $correctionRequest->id,
                'break_start' => !empty($break['break_start'])
                    ? $this->combineDateAndTime($attendance->work_date, $break['break_start'])
                    : null,
                'break_end' => !empty($break['break_end'])
                    ? $this->combineDateAndTime($attendance->work_date, $break['break_end'])
                    : null,
            ]);
        }

        return redirect()->route('attendance.edit', ['id' => $attendance->id]);
    }

    private function combineDateAndTime($date, string $time): Carbon
    {
        return Carbon::parse($date->format('Y-m-d') . ' ' . $time);
    }
}