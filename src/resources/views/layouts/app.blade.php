<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'COACHTECH')</title>
  <link rel="stylesheet" href="{{ asset('css/common.css') }}">
  @yield('css')
</head>
<body>

  {{-- デフォルトヘッダー（ログイン後想定） --}}
  @hasSection('header')
    @yield('header')
  @else
    @include('components.headers.authed')
  @endif

  <main>
    @yield('content')
  </main>

</body>
</html>