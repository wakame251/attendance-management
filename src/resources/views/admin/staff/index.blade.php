@extends('layouts.app')

@section('title', 'スタッフ一覧')

@section('header')
@include('components.headers.admin_authed')
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-index.css') }}">
@endsection

@section('content')
<div class="staff-list">

    <div class="staff-list__inner">

        <h1 class="staff-list__title">スタッフ一覧</h1>

        <div class="staff-list__table-wrapper">

            <table class="staff-list__table">

                <thead>
                    <tr>
                        <th>名前</th>
                        <th>メールアドレス</th>
                        <th>月次勤怠</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->user_name }}</td>
                        <td>{{ $user->email }}</td>

                        <td>
                            <a
                                href="{{ route('admin.staff.attendance', ['id' => $user->id]) }}"
                                class="staff-list__detail"
                            >
                                詳細
                            </a>
                        </td>
                    </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>
@endsection