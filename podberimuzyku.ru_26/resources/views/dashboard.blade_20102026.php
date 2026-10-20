<!DOCTYPE html>

<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>Личный кабинет</title>

<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: #f5f5f5;
        font-family: Arial, sans-serif;
        color: #222;
    }

    .header {
        background: #fff;
        border-bottom: 1px solid #e5e5e5;
    }

    .header-inner {
        max-width: 1100px;
        margin: 0 auto;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .logo {
        font-size: 20px;
        font-weight: 700;
    }

    .logout {
        border: 0;
        background: transparent;
        color: #555;
        cursor: pointer;
        font-size: 15px;
    }

    .container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    h1 {
        margin: 0 0 30px;
        font-size: 32px;
    }

    .card {
        background: #fff;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
    }

    .row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 16px 0;
        border-bottom: 1px solid #eee;
    }

    .row:last-child {
        border-bottom: 0;
    }

    .label {
        color: #777;
    }

    .value {
        font-weight: 600;
        text-align: right;
    }

    .active {
        color: #16803c;
    }

    .test-payment {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #eee;
    }

    .test-payment-title {
        margin: 0 0 15px;
        font-size: 18px;
    }

    .test-payment-button {
        border: 0;
        border-radius: 8px;
        padding: 12px 20px;
        background: #222;
        color: #fff;
        cursor: pointer;
        font-size: 15px;
    }

    .test-payment-button:hover {
        opacity: 0.9;
    }
</style>


</head>

<body>
<header class="header">


<div class="header-inner">

    <div class="logo">
        Личный кабинет
    </div>

    <form method="POST" action="{{ route('lk.logout') }}">
        @csrf

        <button class="logout" type="submit">
            Выйти
        </button>
    </form>

</div>

</header>

<main class="container">


<h1>
    Здравствуйте, {{ $user->name }}!
</h1>

<div class="card">

    <div class="row">
        <div class="label">E-mail</div>
        <div class="value">{{ $user->email }}</div>
    </div>

    <div class="row">
        <div class="label">Тариф</div>
        <div class="value">{{ $user->subscription_plan }}</div>
    </div>

    <div class="row">
        <div class="label">Подписка действует до</div>
        <div class="value">
            {{ $user->subscription_expires_at?->format('d.m.Y H:i') }}
        </div>
    </div>

    <div class="row">
        <div class="label">Статус</div>
        <div class="value active">
            Активна
        </div>
    </div>

    <div class="test-payment">

        <h2 class="test-payment-title">
            Тест новой оплаты
        </h2>

        <form method="POST" action="{{ route('subscription.payment') }}">
            @csrf

            <input type="hidden" name="plan_id" value="1">

            <button
                class="test-payment-button"
                type="submit"
            >
                Тест оплаты
            </button>
        </form>

    </div>

</div>


</main>

</body>
</html>
