<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧
     */
    public function index()
    {
        $users = User::orderBy('id')->get();

        return view('admin.staff.index', compact('users'));
    }

    /**
     * スタッフ別勤怠一覧
     */
    public function show(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::now();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->work_date)->format('Y-m-d');
            });

        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        return view('admin.staff.attendance', compact(
            'user',
            'currentMonth',
            'attendances',
            'dates'
        ));
    }

    /**
     * CSV出力
     */
    public function exportCsv(Request $request, $id): StreamedResponse
    {
        $user = User::findOrFail($id);

        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::now();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date')
            ->get();

        $fileName = 'attendance_' . $user->id . '_' . $currentMonth->format('Y_m') . '.csv';

        return response()->streamDownload(function () use ($attendances) {
            $handle = fopen('php://output', 'w');

            $header = ['日付', '出勤', '退勤', '休憩', '合計'];
            mb_convert_variables('SJIS-win', 'UTF-8', $header);
            fputcsv($handle, $header);

            foreach ($attendances as $attendance) {
                $breakMinutes = $attendance->breaks->sum(function ($break) {
                    if ($break->break_start && $break->break_end) {
                        return Carbon::parse($break->break_end)
                            ->diffInMinutes(Carbon::parse($break->break_start));
                    }

                    return 0;
                });

                $workMinutes = 0;

                if ($attendance->clock_in && $attendance->clock_out) {
                    $workMinutes = Carbon::parse($attendance->clock_out)
                        ->diffInMinutes(Carbon::parse($attendance->clock_in))
                        - $breakMinutes;
                }

                $row = [
                    Carbon::parse($attendance->work_date)->format('Y/m/d'),
                    $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $breakMinutes > 0 ? floor($breakMinutes / 60) . ':' . sprintf('%02d', $breakMinutes % 60) : '',
                    $workMinutes > 0 ? floor($workMinutes / 60) . ':' . sprintf('%02d', $workMinutes % 60) : '',
                ];

                mb_convert_variables('SJIS-win', 'UTF-8', $row);
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}