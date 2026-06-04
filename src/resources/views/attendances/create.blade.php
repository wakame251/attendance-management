@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('header')
<x-headers.authed :status="$status" />
@endsection

@section('content')
<main class="attendance">
    <div class="attendance__inner">

        <div class="attendance__status">
            {{ $status }}
        </div>

        <p class="attendance__date">
            {{ now()->isoFormat('YYYY年M月D日(ddd)') }}
        </p>

        <p class="attendance__time">
            {{ now()->format('H:i') }}
        </p>

        <div class="attendance__buttons">
            @if ($status === '勤務外')
                <form method="POST" action="{{ route('attendance.clock_in') }}">
                    @csrf
                    <button class="attendance__button attendance__button--black">
                        出勤
                    </button>
                </form>
            @endif

            @if ($status === '出勤中')
                <form method="POST" action="{{ route('attendance.clock_out') }}">
                    @csrf
                    <button class="attendance__button attendance__button--black">
                        退勤
                    </button>
                </form>

                <form method="POST" action="{{ route('attendance.break_start') }}">
                    @csrf
                    <button class="attendance__button attendance__button--white">
                        休憩入
                    </button>
                </form>
            @endif

            @if ($status === '休憩中')
                <form method="POST" action="{{ route('attendance.break_end') }}">
                    @csrf
                    <button class="attendance__button attendance__button--white">
                        休憩戻
                    </button>
                </form>
            @endif

            @if ($status === '退勤済')
                <p class="attendance__message">
                    お疲れ様でした。
                </p>
            @endif
        </div>

    </div>
</main>
@endsection