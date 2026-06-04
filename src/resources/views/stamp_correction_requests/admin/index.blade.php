@extends('layouts.app')

@section('title', '申請一覧')

@section('header')
@include('components.headers.admin_authed')
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/stamp-correction-request-list.css') }}">
@endsection

@section('content')
<main class="request-list">
    <div class="request-list__inner">

        <h1 class="request-list__title">申請一覧</h1>

        <div class="request-list__tabs">
            <a
                href="{{ route('stamp_correction_requests.admin.index', ['status' => 'pending']) }}"
                class="request-list__tab {{ $status === 'pending' ? 'is-active' : '' }}"
            >
                承認待ち
            </a>

            <a
                href="{{ route('stamp_correction_requests.admin.index', ['status' => 'approved']) }}"
                class="request-list__tab {{ $status === 'approved' ? 'is-active' : '' }}"
            >
                承認済み
            </a>
        </div>

        <table class="request-list__table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($requests as $requestItem)
                    <tr>
                        <td>
                            {{ $requestItem->status === \App\Models\StampCorrectionRequest::STATUS_PENDING
                                ? '承認待ち'
                                : '承認済み' }}
                        </td>

                        <td>
                            {{ $requestItem->user->user_name ?? $requestItem->user->name }}
                        </td>

                        <td>
                            {{ optional($requestItem->attendance->work_date)->format('Y/m/d') }}
                        </td>

                        <td>
                            {{ $requestItem->reason }}
                        </td>

                        <td>
                            {{ $requestItem->created_at->format('Y/m/d') }}
                        </td>

                        <td>
                            <a href="{{ route('stamp_correction_requests.admin.create',
                                    ['attendance_correct_request_id' => $requestItem->id]) }}"
                                class="request-list__detail-link">
                                詳細
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</main>
@endsection