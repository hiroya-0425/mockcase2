<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', '管理者画面')</title>
    <link rel="stylesheet" href="{{ asset('css/layouts/admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @stack('css')
</head>

<body>
    <header class="admin__header">
        <nav class="admin__header-nav">
            <div class="admin__header-inner">
                <h1 class="admin__header-logo">
                    <img src="{{ asset('images/logo.svg') }}" alt="COACHTECHロゴ" class="admin__header-logo-image">
                </h1>
            </div>
            <div class="admin__header-menu">
                <a href="/admin/attendances" class="admin__header-link">勤怠一覧</a>
                <a href="/admin/users" class="admin__header-link">スタッフ一覧</a>
                <a href="/admin/requests" class="admin__header-link">申請一覧</a>
                <form action="{{ route('admin.logout') }}" method="POST" class="admin__header-logout-form">
                    @csrf
                    <button type="submit" class="admin__header-logout-button">ログアウト</button>
                </form>
            </div>
        </nav>
    </header>

    <main>
        @yield('content') {{-- 子ビューの中身がここに入る --}}
    </main>
    @stack('scripts')
</body>
</html>