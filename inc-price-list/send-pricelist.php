<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');

header('Content-Type: application/json; charset=utf-8');


// =====================================================
// ЛОГ БЛОКИРОВОК + СООБЩЕНИЯ ПОЛЬЗОВАТЕЛЮ
// =====================================================

function pm_pricelist_block($reason){

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';


    // Лог блокировок

    $log_file = $_SERVER['DOCUMENT_ROOT'].'/inc-price-list/pdf/pricelist-block-log.txt';


    $line = date("Y-m-d H:i:s")
        ." | ".$ip
        ." | BLOCK"
        ." | ".$reason
        ."\n";


    file_put_contents(
        $log_file,
        $line,
        FILE_APPEND
    );



    // Сообщения пользователю

    $messages = [


        'empty-fields' =>
            'Заполните все обязательные поля.',


        'short-name' =>
            'Укажите ваше имя.',


        'long-name' =>
            'Имя слишком длинное.',


        'repeat-name' =>
            'Похоже, имя указано некорректно.',


        'mixed-case-name' =>
            'Проверьте правильность написания имени.',


        'bad-name-symbols' =>
            'Имя должно содержать только буквы, пробел и дефис.',


        'invalid-email' =>
            'Укажите корректный адрес электронной почты.',


        'temporary-email' =>
            'Используйте постоянный адрес электронной почты.',


        'fast-submit' =>
            'Пожалуйста, заполните форму внимательнее и повторите отправку.',


        'nonce' =>
            'Ошибка безопасности. Обновите страницу и попробуйте снова.',


        'no-js' =>
            'Для отправки формы необходимо включить JavaScript.',


        'repeat-request' =>
            'Вы уже отправляли запрос недавно. Попробуйте позже.'


    ];



    $message = $messages[$reason]
        ?? 'Ошибка отправки формы. Обновите страницу и попробуйте ещё раз.';



    echo json_encode([

        "status"  => "error",
        "message" => $message

    ]);


    exit;

}


// =====================================================
// ПРОВЕРКА NONCE
// =====================================================

if(
    !isset($_POST['pricelist_nonce']) ||
    !wp_verify_nonce(
        $_POST['pricelist_nonce'],
        'pricelist_request'
    )
){

    pm_pricelist_block('nonce');

}



// =====================================================
// HONEYPOT
// =====================================================

if(
    !empty($_POST['website'])
){

    pm_pricelist_block('honeypot');

}



// =====================================================
// ПРОВЕРКА JS ТОКЕНА
// =====================================================

if(
    empty($_POST['js_check']) ||
    $_POST['js_check'] !== 'ok'
){

    pm_pricelist_block('no-js');

}



// =====================================================
// ПРОВЕРКА ВРЕМЕНИ ЗАПОЛНЕНИЯ
// =====================================================

$form_time = intval($_POST['form_time'] ?? 0);


if(!$form_time){

    pm_pricelist_block('no-form-time');

}


$fill_time = time() - $form_time;


if($fill_time < 5){

    pm_pricelist_block('fast-submit');

}


if($fill_time > 86400){

    pm_pricelist_block('old-form');

}



// =====================================================
// USER AGENT
// =====================================================

$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';


if(empty($user_agent)){

    pm_pricelist_block('no-user-agent');

}


$bad_agents = [
    'bot',
    'crawler',
    'spider',
    'curl',
    'wget',
    'python',
    'httpclient'
];


foreach($bad_agents as $agent){

    if(
        stripos($user_agent,$agent)!==false
    ){

        pm_pricelist_block('bad-agent-'.$agent);

    }

}

// =====================================================
// ПРОВЕРКА ОБЯЗАТЕЛЬНЫХ ПОЛЕЙ
// =====================================================

if(
    !isset($_POST['name']) ||
    !isset($_POST['email'])
){

    pm_pricelist_block('empty-fields');

}



// =====================================================
// ПОЛУЧЕНИЕ ДАННЫХ
// =====================================================

$name  = sanitize_text_field($_POST['name']);
$email = sanitize_email($_POST['email']);



// =====================================================
// ПРОВЕРКА ИМЕНИ
// =====================================================


// Минимальная длина

if(
    mb_strlen($name) < 2
){

    pm_pricelist_block('short-name');

}



// Максимальная длина

if(
    mb_strlen($name) > 40
){

    pm_pricelist_block('long-name');

}



// Имя не должно состоять только из одной буквы

if(
    preg_match('/^(.)\1+$/u',$name)
){

    pm_pricelist_block('repeat-name');

}



// Запрещаем цифры и спецсимволы в имени

if(
    !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u',$name)
){

    pm_pricelist_block('bad-name-symbols');

}



// Проверка подозрительных наборов букв

$bad_names = [
    'qwerty',
    'asdf',
    'test',
    'admin',
    'user',
    'guest',
    'unknown'
];


$name_lower = mb_strtolower($name);


foreach($bad_names as $bad){

    if(
        mb_strpos($name_lower,$bad)!==false
    ){

        pm_pricelist_block('bad-name-'.$bad);

    }

}



// Проверка случайного смешения регистра
// Пример: LngWyrCfxMnrMcREA

$upper_count = preg_match_all(
    '/[A-ZА-ЯЁ]/u',
    $name
);


$lower_count = preg_match_all(
    '/[a-zа-яё]/u',
    $name
);



if(
    $upper_count > 3 &&
    $lower_count > 3 &&
    mb_strlen($name) > 12
){

    pm_pricelist_block('mixed-case-name');

}



// =====================================================
// ПРОВЕРКА EMAIL
// =====================================================


if(
    !is_email($email)
){

    pm_pricelist_block('invalid-email');

}



// =====================================================
// БЛОКИРОВКА ВРЕМЕННЫХ ПОЧТ
// =====================================================


$email_domain = strtolower(
    substr(
        strrchr($email,'@'),
        1
    )
);


$temp_domains = [

    'mailinator.com',
    'guerrillamail.com',
    'guerrillamail.de',
    '10minutemail.com',
    '10minutemail.net',
    'tempmail.com',
    'temp-mail.org',
    'throwawaymail.com',
    'yopmail.com',
    'fakeinbox.com'

];


if(
    in_array($email_domain,$temp_domains)
){

    pm_pricelist_block('temporary-email');

}



// =====================================================
// ПРОВЕРКА ПОДОЗРИТЕЛЬНЫХ EMAIL ИМЁН
// =====================================================


$email_local = strtolower(
    strstr($email,'@',true)
);


if(
    preg_match('/^[a-z0-9]{15,}$/',$email_local)
){

    pm_pricelist_block('random-email');

}



// =====================================================
// ОГРАНИЧЕНИЕ ЧАСТОТЫ
// =====================================================


$user_ip = $_SERVER['REMOTE_ADDR'] ?? '';


// Ограничение по IP + Email

$transient_key = 'pm_pricelist_' . md5(
    $user_ip . '_' . strtolower($email)
);



if(
    get_transient($transient_key)
){

    pm_pricelist_block('repeat-request');

}



set_transient(
    $transient_key,
    true,
    600
);


// =====================================================
// ФАЙЛ ПРАЙС-ЛИСТА
// =====================================================

$file = $_SERVER['DOCUMENT_ROOT'].'/inc-price-list/pdf/price-list-2026.pdf';


if(!file_exists($file)){

    echo json_encode([
        "status"=>"error",
        "message"=>"Файл прайс-листа временно недоступен"
    ]);

    exit;

}



// =====================================================
// HTML ПИСЬМО
// =====================================================

$subject = 'Прайс-лист PODBERIMUZYKU.RU';


$message = '
<html>
<body style="font-family:Arial,sans-serif;line-height:1.6;color:#333">

<p>Здравствуйте, <strong>'.$name.'</strong>!</p>

<p>
Вы запросили <strong>прайс-лист на создание и компоновку музыкальных треков
для спортивных программ</strong>.
</p>

<p>
Файл прайс-листа находится во вложении письма.
</p>

<hr>

<p>
Проект <strong>PODBERIMUZYKU.RU</strong> работает с 2012 года.<br>
Создано более <strong>1600 музыкальных треков</strong>.
</p>

<p>
Примеры работ:
</p>

<p>
https://podberimuzyku.ru/category/figurnoe-katanie<br>
https://podberimuzyku.ru/category/xudozhestvennaya-gimnastika<br>
https://podberimuzyku.ru/category/rollersport
</p>

<p>
Условия подготовки материалов:<br>
https://podberimuzyku.ru/usloviya-dlya-komponovki-audio-treka
</p>

<hr>

<p>
С уважением<br>
Команда <strong>PODBERIMUZYKU.RU</strong>
</p>

</body>
</html>
';



// =====================================================
// ЗАГОЛОВКИ
// =====================================================

$headers = [

    'Content-Type: text/html; charset=UTF-8',
    'From: PODBERIMUZYKU.RU <info@podberimuzyku.ru>',
    'Reply-To: info@podberimuzyku.ru'

];



// =====================================================
// ОТПРАВКА
// =====================================================

$mail = wp_mail(
    $email,
    $subject,
    $message,
    $headers,
    [$file]
);



// =====================================================
// ЛОГ УСПЕШНЫХ ЗАЯВОК
// =====================================================

$log_file = $_SERVER['DOCUMENT_ROOT'].'/inc-price-list/pdf/pricelist-log.txt';


$log_line =
    date("Y-m-d H:i:s")
    ." | "
    .$user_ip
    ." | "
    .$name
    ." | "
    .$email
    ."\n";

file_put_contents($log_file,$log_line,FILE_APPEND);



// =====================================================
// ОТВЕТ ФОРМЕ
// =====================================================

if($mail){

    echo json_encode([

        "status"=>"success",
        "message"=>"Прайс-лист отправлен на ваш e-mail"

    ]);

}else{

    echo json_encode([

        "status"=>"error",
        "message"=>"Ошибка отправки письма"

    ]);

}


exit;

?>