@extends('layouts.app')

@section('title', '修正申請承認')

@section('header')
@include('components.headers.admin_authed')
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')
<main class="attendance-detail">
    <div class="attendance-detail__inner">
        <h1 class="attendance-detail__title">勤怠詳細</h1>

        @php
            $isApproved =
                $displayRequest->status === \App\Models\StampCorrectionRequest::STATUS_APPROVED;

            $clockIn = $displayRequest->requested_clock_in
                ? \Carbon\Carbon::parse($displayRequest->requested_clock_in)->format('H:i')
                : '';

            $clockOut = $displayRequest->requested_clock_out
                ? \Carbon\Carbon::parse($displayRequest->requested_clock_out)->format('H:i')
                : '';

            $breakRows = $displayRequest->breaks->values();
        @endphp

        <div class="attendance-detail__card">
            <div class="attendance-detail__row">
                <div class="attendance-detail__label">名前</div>
                <div class="attendance-detail__content">
                    {{ $displayRequest->user->user_name ?? $displayRequest->user->name }}
                </div>
            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">日付</div>
                <div class="attendance-detail__content attendance-detail__date">
                    <span>{{ $attendance->work_date->format('Y年') }}</span>
                    <span>{{ $attendance->work_date->format('n月j日') }}</span>
                </div>
            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">出勤・退勤</div>
                <div class="attendance-detail__content attendance-detail__time-group">
                    <span>{{ $clockIn }}</span>
                    <span>〜</span>
                    <span>{{ $clockOut }}</span>
                </div>
            </div>

            @foreach ($breakRows as $index => $break)
                @php
                    $breakStart = $break->break_start
                        ? \Carbon\Carbon::parse($break->break_start)->format('H:i')
                        : '';

                    $breakEnd = $break->break_end
                        ? \Carbon\Carbon::parse($break->break_end)->format('H:i')
                        : '';
                @endphp

                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">
                        {{ $index === 0 ? '休憩' : '休憩 ' . ($index + 1) }}
                    </div>

                    <div class="attendance-detail__content attendance-detail__time-group">
                        <span>{{ $breakStart }}</span>
                        <span>〜</span>
                        <span>{{ $breakEnd }}</span>
                    </div>
                </div>
            @endforeach

            <div class="attendance-detail__row attendance-detail__row--textarea">
                <div class="attendance-detail__label">備考</div>
                <div class="attendance-detail__content">
                    <p class="attendance-detail__reason">
                        {{ $displayRequest->reason }}
                    </p>
                </div>
            </div>
        </div>

        @if ($isApproved)
            <div class="attendance-detail__button-area">
                <button type="button" class="attendance-detail__approved-button" disabled>
                    承認済み
                </button>
            </div>
        @else
            <form
                method="POST"
                action="{{ route('stamp_correction_requests.admin.store', ['attendance_correct_request_id' => $displayRequest->id]) }}"
                class="attendance-detail__button-area"
            >
                @csrf

                <button type="submit" class="attendance-detail__button">
                    承認
                </button>
            </form>
        @endif
    </div>
</main>
@endsection