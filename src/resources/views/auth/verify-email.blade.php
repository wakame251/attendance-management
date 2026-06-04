@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('header')
  @include('components.headers.guest')
@endsection

@section('content')
<div class="verify">
  <p class="verify__text">
    登録していただいたメールアドレスに認証メールを送付しました。<br>
    メール認証を完了してください。
  </p>

  <a class="verify__button" href="http://localhost:8025" target="_blank" rel="noopener">
    認証はこちらから
  </a>

  <form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button class="verify__link" type="submit">認証メールを再送する</button>
  </form>

  @if (session('message'))
    <p class="verify__message">{{ session('message') }}</p>
  @endif
</div>
@endsection