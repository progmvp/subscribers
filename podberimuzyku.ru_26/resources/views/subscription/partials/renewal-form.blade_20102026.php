{{-- Ôîðìà ïðîäëåíèÿ ïîäïèñêè â ëè÷íîì êàáèíåòå --}}

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
            Òàðèô ïîäïèñêè
        </label>

        <select
            id="lk-renewal-plan"
            class="pm-select"
            name="plan_id"
            required
        >
            <option value="">Âûáåðèòå òàðèô</option>

            @foreach ($plans as $plan)
                @if ($plan->code !== 'test' && $plan->is_active)
                    <option
                        value="{{ $plan->id }}"
                        @selected((string) old('plan_id') === (string) $plan->id)
                    >
                        {{ $plan->name }} —
                        {{ number_format((float) $plan->price, 0, ',', ' ') }} ? /
                        {{ $plan->duration_days }} äí.
                    </option>
                @endif
            @endforeach
        </select>
    </div>

    <button class="pm-button" type="submit">
        <span>Ïåðåéòè ê îïëàòå</span>
        <span>></span>
    </button>
</form>
