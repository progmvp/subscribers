<style>
    .pm-subscription {
        width: 100%;
        max-width: 620px;
        margin: 0 auto;
        font-family: Arial, Helvetica, sans-serif;
        color: #222;
    }

    .pm-subscription * {
        box-sizing: border-box;
    }

    .pm-subscription-title {
        margin: 0 0 8px;
        font-size: 22px;
        line-height: 1.3;
        font-weight: 700;
        text-align: center;
    }

    .pm-subscription-subtitle {
        margin: 0 0 22px;
        color: #777;
        font-size: 14px;
        line-height: 1.5;
        text-align: center;
    }

    .pm-subscription-card {
        background: #fff;
        border: 1px solid #ddd;
        padding: 22px;
    }

    .pm-subscription-field {
        margin-bottom: 16px;
    }

    .pm-subscription-label {
        display: block;
        margin-bottom: 6px;
        font-size: 14px;
        font-weight: 600;
    }

    .pm-subscription-input,
    .pm-subscription-select {
        display: block;
        width: 100%;
        height: 42px;
        padding: 8px 10px;
        border: 1px solid #ccc;
        border-radius: 2px;
        background: #fff;
        color: #222;
        font-size: 14px;
    }

    .pm-subscription-input:focus,
    .pm-subscription-select:focus {
        outline: none;
        border-color: #999;
    }

    .pm-subscription-plan {
        margin: 0 0 18px;
        padding: 14px;
        background: #f7f7f7;
        border: 1px solid #e2e2e2;
    }

    .pm-subscription-plan-title {
        margin: 0 0 5px;
        font-size: 15px;
        font-weight: 700;
    }

    .pm-subscription-plan-text {
        margin: 0;
        color: #666;
        font-size: 13px;
    }

    .pm-subscription-button {
        display: block;
        width: 100%;
        min-height: 44px;
        padding: 10px 18px;
        border: 0;
        border-radius: 2px;
        background: #f5a623;
        color: #222;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
    }

    .pm-subscription-button:hover {
        opacity: .9;
    }

    .pm-subscription-note {
        margin: 14px 0 0;
        color: #777;
        font-size: 12px;
        line-height: 1.5;
        text-align: center;
    }

    .pm-subscription-errors {
        margin: 0 0 18px;
        padding: 12px 14px;
        background: #fff4f4;
        border: 1px solid #e5baba;
        color: #8a2222;
        font-size: 13px;
        line-height: 1.5;
    }

    .pm-subscription-errors ul {
        margin: 0;
        padding-left: 18px;
    }

    .pm-subscription-errors li + li {
        margin-top: 5px;
    }

    @media (max-width: 600px) {
        .pm-subscription-card {
            padding: 16px;
        }

        .pm-subscription-title {
            font-size: 20px;
        }
    }
</style>

<div class="pm-subscription">

    <h2 class="pm-subscription-title">
        Получить доступ к подписке
    </h2>

    <p class="pm-subscription-subtitle">
        Выберите тариф, укажите имя и e-mail, затем перейдите к оплате.
    </p>

    @if ($errors->any())
        <div class="pm-subscription-errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="pm-subscription-card">

        <form
            method="POST"
            action="{{ route('subscription.subscribe.store') }}"
        >
            @csrf

            <div class="pm-subscription-field">
                <label
                    class="pm-subscription-label"
                    for="pm-subscription-name"
                >
                    Ваше имя
                </label>

                <input
                    id="pm-subscription-name"
                    class="pm-subscription-input"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    maxlength="100"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="pm-subscription-field">
                <label
                    class="pm-subscription-label"
                    for="pm-subscription-email"
                >
                    Ваш e-mail
                </label>

                <input
                    id="pm-subscription-email"
                    class="pm-subscription-input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    maxlength="255"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="pm-subscription-field">

                <label
                    class="pm-subscription-label"
                    for="pm-subscription-plan"
                >
                    Тариф
                </label>

                <select
                    id="pm-subscription-plan"
                    class="pm-subscription-select"
                    name="plan_id"
                    required
                >
                    <option value="">
                        Выберите тариф
                    </option>

                    @foreach ($plans as $plan)
                        <option
                            value="{{ $plan->id }}"
                            @selected((string) old('plan_id') === (string) $plan->id)
                        >
                            {{ $plan->name }}
                            — {{ number_format((float) $plan->price, 0, ',', ' ') }} ₽
                            / {{ $plan->duration_days }} дн.
                        </option>
                    @endforeach
                </select>

            </div>

            <div class="pm-subscription-plan">

                <p class="pm-subscription-plan-title">
                    Условия подписки
                </p>

                <p class="pm-subscription-plan-text">
                    Срок действия и стоимость определяются выбранным
                    тарифом. После успешной оплаты подписка активируется
                    автоматически.
                </p>

            </div>

            <button
                class="pm-subscription-button"
                type="submit"
            >
                Получить доступ и перейти к оплате
            </button>

            <p class="pm-subscription-note">
                После создания заказа вы будете перенаправлены
                на защищённую страницу YooMoney.
            </p>

        </form>

    </div>

</div>
