<!-- FORM -->

<section class="pm-card pm-form-card">

    <div class="pm-card-head">

        <div>

            <h2 class="pm-card-title">
                Оформление подписки
            </h2>

            <p class="pm-card-description">
                Заполните данные ниже. После отправки
                вы перейдёте к оплате выбранного тарифа.
            </p>

        </div>

        <div class="pm-step">
            ШАГ 1
        </div>

    </div>


    @if ($errors->any())

        <div class="pm-errors">

            <ul>

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <form
        method="POST"
        action="{{ route('subscription.subscribe.store') }}"
    >

        @csrf


        <div class="pm-field">

            <label
                class="pm-label"
                for="pm-subscription-name"
            >
                Ваше имя
            </label>

            <input
                id="pm-subscription-name"
                class="pm-input"
                type="text"
                name="name"
                value="{{ old('name') }}"
                maxlength="100"
                autocomplete="name"
                placeholder="Введите ваше имя"
                required
            >

        </div>


        <div class="pm-field">

            <label
                class="pm-label"
                for="pm-subscription-email"
            >
                E-mail
            </label>

            <input
                id="pm-subscription-email"
                class="pm-input"
                type="email"
                name="email"
                value="{{ old('email') }}"
                maxlength="255"
                autocomplete="email"
                placeholder="you@example.com"
                required
            >

        </div>


        <div class="pm-field">

            <label
                class="pm-label"
                for="pm-subscription-plan"
            >
                Тариф подписки
            </label>

            <select
                id="pm-subscription-plan"
                class="pm-select"
                name="plan_id"
                required
            >

                <option value="">
                    Выберите тариф
                </option>

                @foreach ($plans as $plan)

                    <option
                        value="{{ $plan->id }}"
                        @selected(
                            (string) old('plan_id') ===
                            (string) $plan->id
                        )
                    >
                        {{ $plan->name }}
                        —
                        {{ number_format(
                            (float) $plan->price,
                            0,
                            ',',
                            ' '
                        ) }}
                        ₽
                        /
                        {{ $plan->duration_days }}
                        дн.
                    </option>

                @endforeach

            </select>

        </div>


        <div class="pm-conditions">

            <p class="pm-conditions-title">
                Условия подписки
            </p>

            <p class="pm-conditions-text">
                Срок действия и стоимость определяются
                выбранным тарифом. После успешной оплаты
                подписка активируется автоматически.
            </p>

        </div>


        <button
            class="pm-button"
            type="submit"
        >

            <span>
                Перейти к оплате
            </span>

            <span>
                →
            </span>

        </button>


        <p class="pm-note">
            После создания заказа вы будете
            перенаправлены на страницу оплаты YooMoney.
        </p>

    </form>

</section>
