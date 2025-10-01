<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH勤怠</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />

    <link rel="stylesheet" href="{{ asset('css/user/auth/register.css') }}" />
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <h1 class="header__inner-logo"><img src="{{ asset('images/logo.svg') }}" alt="COACHTECHロゴ" class="header__inner-image"></h1>
        </div>
    </header>
    <main>
        <div class="register__form-heading">
            <h2>会員登録</h2>
        </div>
        <div class="register__form-content">
            <div class="register__form-inner">
                <form class="form" action="{{ route('register') }}" method="post">
                    @csrf
                    <div class="form__group">
                        <div class="form__group-title">
                            <span class="form__label-item">名前</span>
                        </div>
                        <div class="form__group-content">
                            <div class="form__input-text">
                                <input type="text" name="name" value="{{ old('name') }}" />
                            </div>
                            <div class="form__error">
                                @error('name')
                                {{ $message }}
                                @enderror
                            </div>
                        </div>
                    </div>
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
                    <div class="form__group">
                        <div class="form__group-title">
                            <span class="form__label-item">確認用パスワード</span>
                        </div>
                        <div class="form__group-content">
                            <div class="form__input-text">
                                <input type="password" name="password_confirmation" />
                            </div>
                            <div class="form__error">
                                @error('password_confirmation')
                                {{ $message }}
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form__button">
                        <button class="form__button-submit" type="submit">登録する</button>
                    </div>
                </form>
                <div class="login__link">
                    <a class="login__button-submit" href="{{ route('login') }}">ログインはこちら</a>
                </div>
            </div>
        </div>
    </main>
</body>

</html>