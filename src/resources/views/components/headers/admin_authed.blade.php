<header class="header">
  <div class="header__inner header__inner--authed">
    <a class="header__logo-link">
      <img src="{{ asset('images/coachtech-logo.png') }}" alt="COACHTECH" class="header__logo-img">
    </a>

    <nav class="header__nav">
        <a href="{{ route('admin.attendance.index') }}" class="header__nav-link">勤怠一覧</a>
        <a href="{{ route('admin.staff.index') }}">スタッフ一覧</a>
        <a href="{{ route('stamp_correction_requests.admin.index') }}">申請一覧</a>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <input type="hidden" name="logout_type" value="admin">
        <button type="submit" class="header__logout">ログアウト</button>
      </form>
    </nav>
  </div>
</header>