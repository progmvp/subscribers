<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Подтверждение входа — Личный кабинет</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            font-family: Arial, sans-serif;
            color: #222;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin: 0 0 10px;
            font-size: 28px;
        }

        .description {
            margin: 0 0 30px;
            color: #666;
            line-height: 1.5;
        }

        .email {
            font-weight: 600;
            color: #222;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input {
            width: 100%;
            height: 52px;
            padding: 0 14px;
            border: 1px solid #d5d5d5;
            border-radius: 7px;
            font-size: 24px;
            letter-spacing: 7px;
            text-align: center;
            outline: none;
        }

        input:focus {
            border-color: #555;
        }

        button {
            width: 100%;
            height: 48px;
            margin-top: 20px;
            border: 0;
            border-radius: 7px;
            background: #222;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #333;
        }

        .error {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 7px;
            background: #fff0f0;
            color: #a00000;
        }

        .status {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 7px;
            background: #f0fff4;
            color: #176b35;
        }

        .back {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #555;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="login-box">

    <h1>Введите код</h1>

    <p class="description">
        Код отправлен на:
        <br>
        <span class="email">{{ $email }}</span>
    </p>

    @if ($errors->any())
        <div class="error">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('status'))
        <div class="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('lk.verify-code') }}">
        @csrf

        <label for="code">Код</label>

        <input
            type="text"
            id="code"
            name="code"
            inputmode="numeric"
            pattern="[0-9]{6}"
            maxlength="6"
            autocomplete="one-time-code"
            required
            autofocus
        >

        <button type="submit">
            Войти
        </button>
    </form>

    <a class="back" href="{{ route('lk.login') }}">
        Изменить e-mail
    </a>

</div>

</body>
</html>