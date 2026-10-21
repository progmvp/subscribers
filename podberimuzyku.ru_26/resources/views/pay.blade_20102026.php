<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доступ к подбору музыки на PODBERIMUZYKU.RU</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #fff;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: auto;
        }

        h1 {
            margin-bottom: 10px;
        }

        .card {
            background: #1e293b;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .plan {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .mbt {
            font-size: 22px;
            font-weight: bold;
        }

        .btn-main {
            display: block;
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            background: #facc15;
            color: #000;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            box-sizing: border-box;
        }

        .btn-main:hover {
            background: #fde047;
        }

        .note {
            font-size: 14px;
            color: #cbd5f5;
            margin-top: 10px;
        }

        .qr-block {
            text-align: center;
            margin: 30px 0;
        }

        .qr-block img {
            width: 220px;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
        }

        .access-card {
            background: #334155;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .access-card h3 {
            margin-top: 0;
        }

        .access-card a {
            color: #facc15;
            word-break: break-word;
        }

        img {
            max-width: 100%;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Доступ к подбору музыки на PODBERIMUZYKU.RU</h1>

    <p>
        Вы получаете доступ к ограниченному контенту сервиса по подбору музыки для спорта.
    </p>

    <div class="card">

        <div class="plan">
            {{ $currentPlan['name'] }}
        </div>

        <div class="mbt">
            {{ $currentPlan['price'] }} MBT
        </div>

        <p class="note">
            MonoBit [MBT] — это внутренняя цифровая единица доступа к функциям сервиса.<br>
            Курс 1 MBT = 1 ₽ (<i>российский рубль</i>)<br>
            <small>Остальные валюты переводите по официальному курсу Центробанка.</small>
        </p>

        <p class="name">
            Имя: {{ $name }}
        </p>

        <p class="note">
            Email: {{ $email }}
        </p>

        <p class="note">
            ID операции: {{ $paymentId }}
        </p>

        <p class="note">
            Донат обрабатывается вручную.
        </p>

<div class="access-card">
    <h3>Важно</h3>

    <p>
        ⚠️ При оформлении доната в CloudTips указывайте те же имя и email, которые были введены ранее в форме доступа.
    </p>
</div>

        <div class="qr-block">

            <p style="font-size:18px; margin-bottom:20px;">
                Оплатите по QR-коду
            </p>

            @if(request('plan') === 'start')
                <img src="/images/qrCode-7ef07a77.png" alt="QR-код">
            @elseif(request('plan') === 'base')
                <img src="/images/qrCode-18052abe.png" alt="QR-код">
            @else
                <img src="/images/qrCode-70e847a2.png" alt="QR-код">
            @endif

        </div>

        <a class="btn-main"
           href="/donate/select?payment_id={{ $paymentId }}&source=cloudtips">
            Продолжить оплату в Т-Банке
        </a>

    </div>

    <div class="access-card">

        <h3>Как войти в личный кабинет</h3>

        <p>
            <b>1.</b> Перейдите на страницу входа:
        </p>

        <p>
            <a href="https://podberimuzyku.ru/member-access/" target="_blank">
                https://podberimuzyku.ru/member-access/
            </a>
        </p>

        <p>
            <b>2.</b> Используйте ваш email как логин
        </p>

        <p>
            <b>3.</b> Пароль вы получите после оплаты
        </p>

    </div>

</div>

</body>
</html>
