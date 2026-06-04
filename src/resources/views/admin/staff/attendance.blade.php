@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('header')
@include('components.headers.admin_authed')
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-attendance.css') }}">
@endsection

@section('content')
<main class="staff-attendance">
    <div class="staff-attendance__inner">

        <h1 class="staff-attendance__title">
            {{ $user->user_name }}さんの勤怠
        </h1>

        @php
            $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
            $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
        @endphp

        <div class="staff-attendance__month">
            <a
                href="{{ route('admin.staff.attendance', ['id' => $user->id, 'month' => $prevMonth]) }}"
                class="staff-attendance__month-link"
            >
                ← 前月
            </a>

            <div class="staff-attendance__month-current">
                <span class="staff-attendance__calendar">📅</span>
                {{ $currentMonth->format('Y/m') }}
            </div>

            <a
                href="{{ route('admin.staff.attendance', ['id' => $user->id, 'month' => $nextMonth]) }}"
                class="staff-attendance__month-link"
            >
                翌月 →
            </a>
        </div>

        <div class="staff-attendance__table-wrapper">
            <table class="staff-attendance__table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($dates as $date)
                        @php
                            $attendance = $attendances[$date->format('Y-m-d')] ?? null;

                            $breakMinutes = 0;
                            $workMinutes = 0;

                            if ($attendance) {
                                $breakMinutes = $attendance->breaks->sum(function ($break) {
                                    if ($break->break_start && $break->break_end) {
                                        return \Carbon\Carbon::parse($break->break_end)
                                            ->diffInMinutes(\Carbon\Carbon::parse($break->break_start));
                                    }

                                    return 0;
                                });

                                if ($attendance->clock_in && $attendance->clock_out) {
                                    $workMinutes = \Carbon\Carbon::parse($attendance->clock_out)
                                        ->diffInMinutes(\Carbon\Carbon::parse($attendance->clock_in))
                                        - $breakMinutes;
                                }
                            }

                            $breakTime = $breakMinutes > 0
                                ? floor($breakMinutes / 60) . ':' . sprintf('%02d', $breakMinutes % 60)
                                : '';

                            $workTime = $workMinutes > 0
                                ? floor($workMinutes / 60) . ':' . sprintf('%02d', $workMinutes % 60)
                                : '';
                        @endphp

                        <tr>
                            <td>{{ $date->format('m/d') }}({{ ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek] }})</td>

                            <td>
                                {{ $attendance && $attendance->clock_in
                                    ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')
                                    : ''
                                }}
                            </td>

                            <td>
                                {{ $attendance && $attendance->clock_out
                                    ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
                                    : ''
                                }}
                            </td>

                            <td>{{ $breakTime }}</td>

                            <td>{{ $workTime }}</td>

                            <td>
                                @if ($attendance)
                                    <a
                                        href="{{ route('admin.attendance.edit', ['id' => $attendance->id]) }}"
                                        class="staff-attendance__detail"
                                    >
                                        詳細
                                    </a>
                                @else
                                    <span class="staff-attendance__detail-empty">詳細</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="staff-attendance__csv-area">
            <a
                href="{{ route('admin.staff.attendance.csv', ['id' => $user->id, 'month' => $currentMonth->format('Y-m')]) }}"
                class="staff-attendance__csv-button"
            >
                CSV出力
            </a>
        </div>

    </div>
</main>
@endsection