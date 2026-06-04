@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v=2">
@endsection

@section('title', 'ログイン')

@section('header')
  @include('components.headers.guest')
@endsection

@section('content')
<div class="auth">
  <h1 class="auth__title">管理者ログイン</h1>

  <div class="auth__errors">
    @if ($errors->any())
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    @endif

    {{-- 認証失敗（Fortify）はここに出る想定。文言を要件に寄せる --}}
    @if (session('status'))
      <p>{{ session('status') }}</p>
    @endif
  </div>

  <form class="auth__form" method="POST" action="{{ route('login') }}">
    @csrf

    <input type="hidden" name="login_type" value="admin">
    <label class="auth__label">メールアドレス</label>
    <input class="auth__input" type="text" name="email" value="{{ old('email') }}">

    <label class="auth__label">パスワード</label>
    <input class="auth__input" type="password" name="password">

    <button class="auth__button" type="submit">管理者ログインする</button>
  </form>


@endsection