@extends('layouts.app')

@section('title', '管理者勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_index.css') }}?v=1">
@endsection

@section('header')
@include('components.headers.admin_authed')
@endsection

@section('content')
<div class="admin-attendance">
  <h1 class="admin-attendance__title">
    {{ $date->format('Y年n月j日') }}の勤怠
  </h1>

  <div class="admin-attendance__date-nav">
    <a class="admin-attendance__date-link"
       href="{{ route('admin.attendance.index', ['date' => $previousDate]) }}">
      ← 前日
    </a>

    <div class="admin-attendance__date-current">
      <span class="admin-attendance__calendar">▣</span>
      {{ $date->format('Y/m/d') }}
    </div>

    <a class="admin-attendance__date-link"
       href="{{ route('admin.attendance.index', ['date' => $nextDate]) }}">
      翌日 →
    </a>
  </div>

  <table class="admin-attendance__table">
    <thead>
      <tr>
        <th>名前</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th>詳細</th>
      </tr>
    </thead>

    <tbody>
      @forelse ($attendances as $attendance)
        @php
          $clockIn = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in) : null;
          $clockOut = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out) : null;

          $breakMinutes = $attendance->breaks->sum(function ($break) {
              if (!$break->break_start || !$break->break_end) {
                  return 0;
              }

              return \Carbon\Carbon::parse($break->break_start)
                  ->diffInMinutes(\Carbon\Carbon::parse($break->break_end));
          });

          $totalMinutes = 0;

          if ($clockIn && $clockOut) {
              $totalMinutes = $clockIn->diffInMinutes($clockOut) - $breakMinutes;
          }

          $breakTime = sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60);
          $totalTime = sprintf('%d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
        @endphp

        <tr>
          <td>{{ $attendance->user->name ?? $attendance->user->user_name ?? '名前未設定' }}</td>
          <td>{{ $clockIn ? $clockIn->format('H:i') : '' }}</td>
          <td>{{ $clockOut ? $clockOut->format('H:i') : '' }}</td>
          <td>{{ $breakTime }}</td>
          <td>{{ $totalTime }}</td>
          <td>
            <a class="admin-attendance__detail"
               href="{{ route('admin.attendance.edit', ['id' => $attendance->id]) }}">
              詳細
            </a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="admin-attendance__empty">
            この日の勤怠情報はありません
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection