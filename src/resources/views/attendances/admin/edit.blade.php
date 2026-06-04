@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}?v=2">
@endsection

@section('header')
@include('components.headers.admin_authed')
@endsection

@section('content')
<main class="attendance-detail">
    <div class="attendance-detail__inner">
        <h1 class="attendance-detail__title">勤怠詳細</h1>

        @php
            $clockIn = old('clock_in', optional($attendance->clock_in)->format('H:i'));
            $clockOut = old('clock_out', optional($attendance->clock_out)->format('H:i'));
            $breakRows = $attendance->breaks->values();
        @endphp

        <form method="POST" action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}">
            @csrf
            @method('PATCH')

            <div class="attendance-detail__card">
                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">名前</div>
                    <div class="attendance-detail__content">
                        {{ $attendance->user->user_name ?? $attendance->user->name }}
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
                        <input type="time" name="clock_in" value="{{ $clockIn }}" class="attendance-detail__time-input">
                        <span>〜</span>
                        <input type="time" name="clock_out" value="{{ $clockOut }}" class="attendance-detail__time-input">
                    </div>
                </div>

                @error('clock_in')
                    <p class="attendance-detail__error">{{ $message }}</p>
                @enderror
                @error('clock_out')
                    <p class="attendance-detail__error">{{ $message }}</p>
                @enderror

                @foreach ($breakRows as $index => $break)
                    @php
                        $breakStart = old(
                            "breaks.$index.break_start",
                            $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : ''
                        );

                        $breakEnd = old(
                            "breaks.$index.break_end",
                            $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : ''
                        );
                    @endphp

                    <div class="attendance-detail__row">
                        <div class="attendance-detail__label">
                            {{ $index === 0 ? '休憩' : '休憩 ' . ($index + 1) }}
                        </div>

                        <div class="attendance-detail__content attendance-detail__time-group">
                            <input type="time" name="breaks[{{ $index }}][break_start]" value="{{ $breakStart }}" class="attendance-detail__time-input">
                            <span>〜</span>
                            <input type="time" name="breaks[{{ $index }}][break_end]" value="{{ $breakEnd }}" class="attendance-detail__time-input">
                        </div>
                    </div>

                    @error("breaks.$index.break_start")
                        <p class="attendance-detail__error">{{ $message }}</p>
                    @enderror
                    @error("breaks.$index.break_end")
                        <p class="attendance-detail__error">{{ $message }}</p>
                    @enderror
                @endforeach

                @php
                    $newBreakIndex = $breakRows->count();
                @endphp

                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">
                        {{ $newBreakIndex === 0 ? '休憩' : '休憩 ' . ($newBreakIndex + 1) }}
                    </div>

                    <div class="attendance-detail__content attendance-detail__time-group">
                        <input type="time" name="breaks[{{ $newBreakIndex }}][break_start]" value="{{ old("breaks.$newBreakIndex.break_start") }}" class="attendance-detail__time-input">
                        <span>〜</span>
                        <input type="time" name="breaks[{{ $newBreakIndex }}][break_end]" value="{{ old("breaks.$newBreakIndex.break_end") }}" class="attendance-detail__time-input">
                    </div>
                </div>

                @error("breaks.$newBreakIndex.break_start")
                    <p class="attendance-detail__error">{{ $message }}</p>
                @enderror
                @error("breaks.$newBreakIndex.break_end")
                    <p class="attendance-detail__error">{{ $message }}</p>
                @enderror

                <div class="attendance-detail__row attendance-detail__row--textarea">
                    <div class="attendance-detail__label">備考</div>
                    <div class="attendance-detail__content">
                        <textarea name="reason" class="attendance-detail__textarea">{{ old('reason') }}</textarea>
                    </div>
                </div>

                @error('reason')
                    <p class="attendance-detail__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="attendance-detail__button-area">
                <button type="submit" class="attendance-detail__button">修正</button>
            </div>
        </form>
    </div>
</main>
@endsection