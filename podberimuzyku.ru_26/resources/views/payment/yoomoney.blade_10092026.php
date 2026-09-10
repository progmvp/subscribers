<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Оплата подписки — Подбери музыку</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            background: #f5f6f8;
            color: #222;
            font-family: Arial, Helvetica, sans-serif;
        }

        .header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }

        .header-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }

        .logo {
            color: #222;
            font-size: 20px;
            font-weight: 700;
            text-decoration: none;
        }

        .container {
            width: 100%;
            max-width: 620px;
            margin: 0 auto;
            padding: 55px 20px 70px;
        }

        .page-title {
            margin: 0 0 10px;
            font-size: 30px;
            line-height: 1.2;
            text-align: center;
        }

        .page-subtitle {
            margin: 0 0 30px;
            color: #777;
            font-size: 15px;
            line-height: 1.5;
            text-align: center;
        }

        .card {
            background: #fff;
            border: 1px solid #e7e8eb;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            margin: 0 0 20px;
            font-size: 18px;
            font-weight: 700;
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding: 16px 0;
            border-bottom: 1px solid #eee;
        }

        .row:last-of-type {
            border-bottom: 0;
        }

        .label {
            color: #777;
            font-size: 15px;
        }

        .value {
            color: #222;
            font-size: 15px;
            font-weight: 600;
            text-align: right;
        }

        .total {
            margin-top: 8px;
            padding: 20px 0 0;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .total-label {
            font-size: 17px;
            font-weight: 700;
        }

        .total-value {
            font-size: 25px;
            font-weight: 700;
        }

        .payment-form {
            margin-top: 28px;
        }

        .payment-button {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 15px 20px;
            background: #222;
            color: #fff;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .payment-button:hover {
            opacity: 0.88;
        }

        .payment-note {
            margin: 16px 0 0;
            color: #888;
            font-size: 13px;
            line-height: 1.5;
            text-align: center;
        }

        .back {
            display: block;
            margin-top: 22px;
            color: #666;
            font-size: 14px;
            text-align: center;
            text-decoration: none;
        }

        .back:hover {
            color: #222;
        }

        .secure {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 13px;
            line-height: 1.5;
            text-align: center;
        }

        @media (max-width: 600px) {
            .container {
                padding: 35px 15px 50px;
            }

            .page-title {
                font-size: 26px;
            }

            .card {
                padding: 22px 20px;
                border-radius: 14px;
            }

            .row {
                gap: 12px;
            }

            .label,
            .value {
                font-size: 14px;
            }

            .total-label {
                font-size: 16px;
            }

            .total-value {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

<header class="header">
    <div class="header-inner">
        <a href="{{ route('lk.dashboard') }}" class="logo">
            Подбери музыку
        </a>
    </div>
</header>

<main class="container">

    <h1 class="page-title">
        Оплата подписки
    </h1>

    <p class="page-subtitle">
        Проверьте данные заказа перед переходом к оплате
    </p>

    <div class="card">

        <h2 class="section-title">
            Ваш заказ
        </h2>

        <div class="row">
            <div class="label">
                Тариф
            </div>

            <div class="value">
                {{ $planName }}
            </div>
        </div>

        <div class="row">
            <div class="label">
                E-mail
            </div>

            <div class="value">
                {{ $email }}
            </div>
        </div>

        <div class="total">
            <div class="total-label">
                К оплате
            </div>

            <div class="total-value">
                {{ number_format((float) $amount, 2, ',', ' ') }} ₽
            </div>
        </div>

        <form
            class="payment-form"
            method="POST"
            action="https://yoomoney.ru/quickpay/confirm"
        >

            <input
                type="hidden"
                name="receiver"
                value="{{ $yoomoneyReceiver }}"
            >

            <input
                type="hidden"
                name="quickpay-form"
                value="button"
            >

            <input
                type="hidden"
                name="paymentType"
                value="AC"
            >

            <input
                type="hidden"
                name="sum"
                value="{{ number_format((float) $amount, 2, '.', '') }}"
            >

            <input
                type="hidden"
                name="label"
                value="{{ $paymentId }}"
            >

            <input
                type="hidden"
                name="successURL"
                value="{{ route('lk.dashboard') }}"
            >

            <button
                type="submit"
                class="payment-button"
            >
                Перейти к оплате
            </button>

        </form>

        <p class="payment-note">
            После нажатия вы будете перенаправлены на защищённую страницу YooMoney
            для завершения платежа.
        </p>

        <a
            href="{{ route('lk.dashboard') }}"
            class="back"
        >
            ← Вернуться в личный кабинет
        </a>

        <div class="secure">
            Платёж обрабатывается платёжной системой YooMoney.
            Данные банковской карты не передаются сайту.
        </div>

    </div>

</main>

</body>
</html>
