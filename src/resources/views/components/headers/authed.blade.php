@props(['status' => null])

<header class="header">
  <div class="header__inner header__inner--authed">
    <a href="{{ route('attendance.create') }}" class="header__logo-link">
      <img src="{{ asset('images/coachtech-logo.png') }}" alt="COACHTECH" class="header__logo-img">
    </a>

    <nav class="header__nav">
      @if ($status === '退勤済')
        <a href="{{ route('attendance.index') }}" class="header__nav-link">今月の出勤一覧</a>
        <a href="{{ route('stamp_correction_requests.user.index') }}" class="header__nav-link">申請一覧</a>
      @else
        <a href="{{ route('attendance.create') }}" class="header__nav-link">勤怠</a>
        <a href="{{ route('attendance.index') }}" class="header__nav-link">勤怠一覧</a>
        <a href="{{ route('stamp_correction_requests.user.index') }}" class="header__nav-link">申請</a>
      @endif

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="header__logout">ログアウト</button>
      </form>
    </nav>
  </div>
</header>