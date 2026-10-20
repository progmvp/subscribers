
{{-- /var/www/podberimuzyku.ru_26/resources/views/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Личный кабинет — Подбери музыку</title>

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <script src="{{ asset('js/dashboard.js') }}" defer></script>
</head>
<body>
<div class="lk-layout">

    <aside class="sidebar" id="lk-sidebar">
        <div class="sidebar-brand">
            {{-- ЗАГЛУШКА ЛОГОТИПА: вставить SVG сюда --}}
            <div class="logo-placeholder" aria-label="Место для логотипа"></div>
        </div>

        <div class="sidebar-caption">ЛИЧНЫЙ КАБИНЕТ</div>

        <nav class="sidebar-nav" aria-label="Основная навигация">
            <a class="nav-link is-active" href="#overview">
                <span class="nav-icon">⌂</span>
                <span>Обзор</span>
            </a>

            <a class="nav-link" href="#subscription">
                <span class="nav-icon">◷</span>
                <span>Моя подписка</span>
            </a>

            <a class="nav-link" href="#payment">
                <span class="nav-icon">↗</span>
                <span>Оплата</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-help">
                <div class="help-mark">?</div>
                <div>
                    <div class="help-title">Нужна помощь?</div>
                    <div class="help-text">Мы готовы помочь</div>
                </div>
            </div>

            <form method="POST" action="{{ route('lk.logout') }}">
                @csrf
                <button class="logout-button" type="submit">
                    <span class="nav-icon">↪</span>
                    <span>Выйти из кабинета</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="lk-main">

        <header class="topbar">
            <div class="topbar-left">
                <button
                    class="menu-toggle"
                    id="lk-menu-toggle"
                    type="button"
                    aria-label="Открыть меню"
                    aria-controls="lk-sidebar"
                    aria-expanded="false"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="breadcrumb">
                    <span>Личный кабинет</span>
                    <span class="breadcrumb-separator">/</span>
                    <strong>Обзор</strong>
                </div>
            </div>

            <div class="topbar-user">
                <div class="user-avatar">
                    {{ mb_strtoupper(mb_substr($user->name ?? 'П', 0, 1)) }}
                </div>
                <div class="topbar-user-info">
                    <span class="topbar-user-name">{{ $user->name }}</span>
                    <span class="topbar-user-caption">Личный кабинет</span>
                </div>
            </div>
        </header>

        <main class="dashboard-content" id="overview">

            <section class="welcome-section">
                <div>
                    <div class="eyebrow">ВАШЕ ПРОСТРАНСТВО</div>
                    <h1>Здравствуйте, {{ $user->name }}!</h1>
                    <p class="welcome-description">
                        Здесь собрана информация о вашем профиле и подписке.
                    </p>
                </div>

                <div class="welcome-decoration" aria-hidden="true">
                    <span></span><span></span><span></span><span></span>
                </div>
            </section>



            <section class="overview-grid" aria-label="Сводка">

                <article class="summary-card profile-card">
                    <div class="summary-card-heading">
                        <div class="card-icon profile-icon">◎</div>
                        <span class="card-overline">ВАШ ПРОФИЛЬ</span>
                    </div>

                    <h2>Личные данные</h2>
                    <p class="card-description">
                        Данные вашей учётной записи
                    </p>

                    <div class="detail-block">
                        <span class="detail-label">Имя</span>
                        <span class="detail-value">{{ $user->name }}</span>
                    </div>

                    <div class="detail-block">
                        <span class="detail-label">Электронная почта</span>
                        <span class="detail-value email-value">{{ $user->email }}</span>
                    </div>
                </article>

                <article class="summary-card subscription-card" id="subscription">
                    <div class="summary-card-heading">
                        <div class="card-icon subscription-icon">◷</div>
                        <span class="card-overline">ВАШ ДОСТУП</span>
                    </div>

                    @php
                        $subscriptionActive = $user->subscription_expires_at
                            && $user->subscription_expires_at->isFuture();

                        $planNames = [
                            'test'  => 'Тестовый',
                            'start' => 'Стартовый',
                            'base'  => 'Базовый',
                            'full'  => 'Полный',
                        ];

                        $planCode = strtolower((string) $user->subscription_plan);
                    @endphp

                    <div class="subscription-title-row">
                        <h2>Моя подписка</h2>

                        @if ($subscriptionActive)
                            <span class="status-pill">
                                <span class="status-dot"></span>
                                Активна
                            </span>
                        @else
                            <span class="status-pill status-inactive">
                                <span class="status-dot"></span>
                                Не активна
                            </span>
                        @endif
                    </div>

                    <p class="card-description">
                        Текущий тариф и срок действия доступа
                    </p>

                    @if ($subscriptionActive)

                        <div class="plan-panel">
                            <span class="detail-label">Ваш тариф</span>
                            <strong class="plan-name">
                                {{ $planNames[$planCode] ?? $user->subscription_plan }}
                            </strong>
                        </div>

                        <div class="detail-block expiry-block">
                            <span class="detail-label">Подписка действует до</span>
                            <span class="detail-value">
                                {{ $user->subscription_expires_at->format('d.m.Y H:i') }}
                            </span>
                        </div>

                    @else

                        <div class="detail-block expiry-block">
                            <span class="detail-label">Подписка</span>
                            <span class="detail-value">
                                Нет активной подписки
                            </span>
                        </div>

                        <div class="detail-block">
                            <span class="detail-label">Текущие дата и время</span>
                            <span class="detail-value">
                                {{ now()->format('d.m.Y H:i') }}
                            </span>
                        </div>

                    @endif
                </article>

            </section>

            <section class="payment-section" id="payment">
                <div class="section-heading">
                    <div>
                        <div class="eyebrow">ПЛАТЁЖИ</div>
                        <h2>Управление подпиской</h2>
                        <p>Здесь будет доступна информация о продлении доступа.</p>
                    </div>
                </div>

                {{-- Временный тест оплаты. Существующий маршрут сохранён. --}}
                <div class="payment-test-card">
                    <div class="payment-test-copy">
                        <div class="payment-test-icon">↗</div>
                        <div>
                            <h3>Тест новой оплаты</h3>
                            <p>
                                Временная кнопка для проверки платёжного процесса.
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('subscription.payment') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="1">

                        <button class="primary-button" type="submit">
                            Тест оплаты
                            <span aria-hidden="true">→</span>
                        </button>
                    </form>
                </div>
            </section>

            <footer class="dashboard-footer">
                <span>Подбери музыку</span>
                <span>Личный кабинет подписчика</span>
            </footer>

        </main>
    </div>
</div>
</body>
</html>
