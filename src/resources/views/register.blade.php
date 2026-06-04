@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v=2">
@endsection

@section('title', '会員登録')


@section('header')
 @include('components.headers.guest')
@endsection

@section('content')
<div class="auth">
  <h1 class="auth__title">会員登録</h1>

  <div class="auth__errors">
    @if ($errors->any())
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    @endif
  </div>

  <form class="auth__form" method="POST" action="{{ route('register') }}">
    @csrf

    <label class="auth__label">ユーザー名</label>
    <input class="auth__input" type="text" name="user_name" value="{{ old('user_name') }}">

    <label class="auth__label">メールアドレス</label>
    <input class="auth__input" type="text" name="email" value="{{ old('email') }}">

    <label class="auth__label">パスワード</label>
    <input class="auth__input" type="password" name="password">

    <label class="auth__label">確認用パスワード</label>
    <input class="auth__input" type="password" name="password_confirmation">

    <button class="auth__button" type="submit">登録する</button>
  </form>

  <div class="auth__link">
    <a href="{{ route('login') }}">ログインはこちら</a>
  </div>
</div>
@endsection
