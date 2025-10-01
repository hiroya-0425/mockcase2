<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ログイン</title>
    <link rel="stylesheet" href="{{ asset('css/admin/auth/login.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <h1 class="header__inner-logo"><img src="{{ asset('images/logo.svg') }}" alt="COACHTECHロゴ" class="header__inner-image"></h1>
        </div>
    </header>
    <form method="POST" action="{{ route('admin.login.submit') }}" novalidate>
        @csrf
        <div class="login__group">
            <label for="email" class="login__label">メールアドレス</label>
            <input id="email" type="email" name="email" class="login__input" value="{{ old('email') }}" required autofocus>
            @error('email')
            <div class="login__error">{{ $message }}</div>
            @enderror
        </div>

        <div class="login__group">
            <label for="password" class="login__label">パスワード</label>
            <input id="password" type="password" name="password" class="login__input" required>
            @error('password')
            <div class="login__error">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="login__btn">管理者ログインする</button>
    </form>
    <div class="user__login-link">
        <a class="user__button-submit" href="/login">一般ユーザーログインはこちら</a>
    </div>
</body>

</html>