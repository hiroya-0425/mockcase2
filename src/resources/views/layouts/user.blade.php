<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'ユーザー画面')</title>
    <link rel="stylesheet" href="{{ asset('css/layouts/user.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <header class="user__header">
        <nav class="user__header-nav">
            <div class="user__header-inner">
                <h1 class="user__header-logo">
                    <img src="{{ asset('images/logo.svg') }}" alt="COACHTECHロゴ" class="user__header-logo-image">
                </h1>
            </div>
            <a href="/attendance" class="user__header-link">勤怠</a> |
            <a href="/attendance/list" class="user__header-link">勤怠一覧</a> |
            <a href="/stamp_correction_request/list" class="user__header-link">申請</a> |
            <form action="{{ route('logout') }}" method="POST" class="user__header-logout-form">
                @csrf
                <button type="submit" class="user__header-logout-button">ログアウト</button>
            </form>
        </nav>
    </header>
    <main>
        @yield('content') {{-- 子ビューの中身がここに入る --}}
    </main>
    @stack('scripts')
</body>
</html>