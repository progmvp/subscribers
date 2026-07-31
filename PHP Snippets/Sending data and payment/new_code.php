<form id="donateForm">

<!-- Email -->
<input type="email"
       id="email"
       name="email"
       placeholder="Ваш email"
       required>

<!-- Имя -->
<input type="text"
       id="first_name"
       name="first_name"
       placeholder="Ваше имя"
       required>

<!-- Фамилия -->
<input type="text"
       id="last_name"
       name="full_last_name"
       placeholder="Ваша фамилия"
       required>

<!-- Для MBT -->
<input type="hidden"
       id="name"
       name="name">

<!-- 🔐 Honeypot -->
<input type="text"
       name="website"
       style="display:none">

<!-- План -->
<select id="plan" name="plan" required>
    <option value="">Выберите тариф</option>
    <option value="start">Стартовый — 250 MBT / 24 часа</option>
    <option value="base">Базовый — 450 MBT / 2 недели</option>
    <option value="full">Полный — 750 MBT / месяц</option>
</select>

<!-- Согласие -->
<label>
    <input type="checkbox" required>
    Я соглашаюсь с условиями
</label>

<!-- Кнопка -->
<button type="submit">
    Получить MBT для доступа
</button>

</form>

<!-- 📌 БЛОК ИНСТРУКЦИИ -->
<div id="access-info" style="
    display:none;
    margin-top:20px;
    padding:15px;
    border:1px solid #ddd;
    border-radius:8px;
    background:#fafafa;
">
    <h3>Как войти в личный кабинет</h3>

    <p><b>1.</b> Перейдите на страницу входа:</p>

    <p>
        <a href="https://podberimuzyku.ru/member-access/" target="_blank">
            https://podberimuzyku.ru/member-access/
        </a>
    </p>

    <p><b>2.</b> Используйте ваш email как логин</p>

    <p><b>3.</b> Пароль вы получите после оплаты</p>
</div>

<script>

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidName(text) {
    return /^[a-zA-Zа-яА-ЯёЁ\s\-]+$/.test(text);
}

function showAccessInfo() {
    document.getElementById('access-info').style.display = 'block';
}

document.getElementById('donateForm').addEventListener('submit', function (e) {

    e.preventDefault();

    let email = document.getElementById('email').value.trim();
    let firstName = document.getElementById('first_name').value.trim();
    let lastName = document.getElementById('last_name').value.trim();
    let plan = document.getElementById('plan').value;

    if (!isValidEmail(email)) {
        alert('Введите корректный email (пример: name@mail.ru)');
        return;
    }

    if (!isValidName(firstName) || firstName.length < 2) {
        alert('Введите корректное имя (только буквы, минимум 2 символа)');
        return;
    }

    if (!isValidName(lastName) || lastName.length < 2) {
        alert('Введите корректную фамилию');
        return;
    }

    if (plan === '') {
        alert('Выберите тариф.');
        return;
    }

    let finalName = firstName + ' ' + lastName.charAt(0);

    document.getElementById('name').value = finalName;

    showAccessInfo();

    window.location.href =
        '/member-subscribers/payment/?name=' + encodeURIComponent(finalName) +
        '&email=' + encodeURIComponent(email) +
        '&plan=' + encodeURIComponent(plan);

});

</script>
