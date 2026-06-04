<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\StampCorrectionRequestBreak;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminStampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = StampCorrectionRequest::with([
                'attendance',
                'user',
            ]);

        if ($status === 'approved') {
            $query->where('status', StampCorrectionRequest::STATUS_APPROVED);
        } else {
            $query->where('status', StampCorrectionRequest::STATUS_PENDING);
        }

        $requests = $query
            ->latest()
            ->get();

        return view(
            'stamp_correction_requests.admin.index',
            compact('requests', 'status')
        );
    }

    /**
     * 修正申請詳細
     */
    public function create($attendance_correct_request_id)
    {
        $displayRequest = StampCorrectionRequest::with([
                'attendance',
                'user',
                'breaks',
            ])
            ->findOrFail($attendance_correct_request_id);

        $attendance = $displayRequest->attendance;

        $pendingRequest = $displayRequest->status
            === StampCorrectionRequest::STATUS_APPROVED
            ? $displayRequest
            : null;

        return view(
            'stamp_correction_requests.admin.create',
            compact(
                'attendance',
                'displayRequest',
                'pendingRequest'
            )
        );
    }

    /**
     * 承認処理
     */
    public function store($attendance_correct_request_id)
    {
        $requestData = StampCorrectionRequest::with('breaks')
            ->findOrFail($attendance_correct_request_id);

        $attendance = Attendance::findOrFail(
            $requestData->attendance_id
        );

        /*
        |--------------------------------------------------------------------------
        | 勤怠更新
        |--------------------------------------------------------------------------
        */

        $attendance->update([
            'clock_in' => $requestData->requested_clock_in,
            'clock_out' => $requestData->requested_clock_out,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 休憩更新
        |--------------------------------------------------------------------------
        */

        $attendance->breaks()->delete();

        foreach ($requestData->breaks as $break) {

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => $break->break_start,
                'break_end' => $break->break_end,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 承認済みに変更
        |--------------------------------------------------------------------------
        */

        $requestData->update([
            'status' => StampCorrectionRequest::STATUS_APPROVED,
            'approved_by' => auth('admin')->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route(
            'stamp_correction_requests.admin.create',
            ['attendance_correct_request_id' => $requestData->id]
        );
    }
}