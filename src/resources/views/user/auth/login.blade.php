<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH勤怠</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/user/auth/login.css') }}" />
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <h1 class="header__inner-logo"><img src="{{ asset('images/logo.svg') }}" alt="COACHTECHロゴ" class="header__inner-image"></h1>
        </div>
    </header>
    <main>
        <div class="login__form-heading">
            <h2>ログイン</h2>
        </div>
        <div class="login__form-content">
            <form class="form" action="{{ route('login') }}" method="post" novalidate>
                @csrf
                <div class="form__group">
                    <div class="form__group-title">
                        <span class="form__label-item">メールアドレス</span>
                    </div>
                    <div class="form__group-content">
                        <div class="form__input-text">
                            <input type="email" name="email" value="{{ old('email') }}" />
                        </div>
                        <div class="form__error">
                            @error('email')
                            {{ $message }}
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="form__group">
                    <div class="form__group-title">
                        <span class="form__label-item">パスワード</span>
                    </div>
                    <div class="form__group-content">
                        <div class="form__input-text">
                            <input type="password" name="password" />
                        </div>
                        <div class="form__error">
                            @error('password')
                            {{ $message }}
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="form__button">
                    <button class="form__button-submit" type="submit">ログインする</button>
                </div>
            </form>
            <div class="link">
                <div class="register__link">
                    <a class="register__button-submit" href="{{ route('register') }}">会員登録はこちら</a>
                </div>
                <div class="admin__link">
                    <a class="admin__button-submit" href="admin/login">管理者ログインはこちら</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>