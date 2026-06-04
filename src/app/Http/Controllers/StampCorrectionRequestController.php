<?php

namespace App\Http\Controllers;

use App\Models\StampCorrectionRequest;
use Illuminate\Http\Request;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = StampCorrectionRequest::with([
                'attendance',
                'user',
            ])
            ->where('user_id', $request->user()->id);

        if ($status === 'approved') {
            $query->where('status', StampCorrectionRequest::STATUS_APPROVED);
        } else {
            $query->where('status', StampCorrectionRequest::STATUS_PENDING);
        }

        $requests = $query
            ->latest()
            ->get();

        return view('stamp_correction_requests.user.index', compact(
            'requests',
            'status'
        ));
    }
}