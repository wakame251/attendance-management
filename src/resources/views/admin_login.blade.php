@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v=2">
@endsection

@section('title', '管理者ログイン')

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

    @if (session('status'))
      <p>{{ session('status') }}</p>
    @endif
  </div>

  <form class="auth__form" method="POST" action="{{ route('admin.login.store') }}">
    @csrf

    <input type="hidden" name="login_type" value="admin">

    <label class="auth__label" for="email">メールアドレス</label>
    <input id="email" class="auth__input" type="email" name="email" value="{{ old('email') }}">

    <label class="auth__label" for="password">パスワード</label>
    <input id="password" class="auth__input" type="password" name="password">

    <button class="auth__button" type="submit">管理者ログインする</button>
  </form>
</div>
@endsection