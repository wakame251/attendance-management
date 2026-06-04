@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('header')
<x-headers.authed />
@endsection

@section('content')
<main class="attendance-list">
    <div class="attendance-list__inner">
        <h1 class="attendance-list__title">勤怠一覧</h1>

        <div class="attendance-list__month">
            <a href="{{ route('attendance.index', ['month' => $prevMonth]) }}" class="attendance-list__month-link">
                ← 前月
            </a>

            <div class="attendance-list__month-current">
                📅 {{ $currentMonth->format('Y/m') }}
            </div>

            <a href="{{ route('attendance.index', ['month' => $nextMonth]) }}" class="attendance-list__month-link">
                翌月 →
            </a>
        </div>

        <table class="attendance-list__table">
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
                @foreach ($attendanceList as $row)
                    @php
                        $attendance = $row['attendance'];
                    @endphp

                    <tr>
                        <td>{{ $row['date']->isoFormat('MM/DD(ddd)') }}</td>

                        <td>
                            {{ $attendance && $attendance->clock_in
                                ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')
                                : '' }}
                        </td>

                        <td>
                            {{ $attendance && $attendance->clock_out
                                ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
                                : '' }}
                        </td>

                        <td>{{ $row['break_time'] }}</td>

                        <td>{{ $row['work_time'] }}</td>

                        <td>
                            @if ($attendance)
                                <a href="{{ route('attendance.edit', ['id' => $attendance->id]) }}" class="attendance-list__detail-link">詳細</a>
                            @else
                                <span class="attendance-list__detail-link">詳細</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</main>
@endsection