{{-- Форма продления подписки в личном кабинете --}}

@if ($errors->any())
    <div class="pm-errors">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('subscription.payment') }}">
    @csrf

    <div class="pm-field">
        <label class="pm-label" for="lk-renewal-plan">
            Тариф подписки
        </label>

        <select
            id="lk-renewal-plan"
            class="pm-select"
            name="plan_id"
            required
        >
            <option value="">Выберите тариф</option>

            @foreach ($plans as $plan)
                @if ($plan->is_active)
                    <option
                        value="{{ $plan->id }}"
                        @selected((string) old('plan_id') === (string) $plan->id)
                    >
                        {{ $plan->name }} —
                        {{ number_format((float) $plan->price, 0, ',', ' ') }} MBT /
                        {{ $plan->duration_days }} дн.
                    </option>
                @endif
            @endforeach
        </select>
    </div>

    <button class="pm-button" type="submit">
        <span>Перейти к оплате</span>
        <span>></span>
    </button>
</form>
