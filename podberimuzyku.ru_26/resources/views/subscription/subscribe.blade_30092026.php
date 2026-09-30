<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>РџРѕРґРїРёСЃРєР° вЂ” PODBERIMUZYKU.RU</title>

    <!-- Jivo -->
    <script src="//code.jivosite.com/widget/DaJfRsVrnb?ver=1.3.6.1"></script>

    <style>
        :root {
            --pm-black: #0E0510;
            --pm-dark: #2C1C2C;
            --pm-purple: #4F506B;

            --pm-orange: #FF5900;
            --pm-orange-light: #FB8B24;
            --pm-gold: #F3B61F;
            --pm-yellow: #F9D671;
            --pm-cream: #FFF5C3;

            --pm-gray: #DEDFEA;
            --pm-white: #FCFCFE;

            --pm-text: #0E0510;
            --pm-muted: #4F506B;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--pm-white);
            color: var(--pm-text);

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select {
            font: inherit;
        }

        img {
            max-width: 100%;
        }


        /* =========================================================
           HEADER
        ========================================================= */

        .pm-site-header {
            width: 100%;
            background: #fff;
        }

        /* Р’РµСЂС…РЅСЏСЏ РєРѕРЅС‚Р°РєС‚РЅР°СЏ СЃС‚СЂРѕРєР° */

        .pm-top-contact {
            min-height: 42px;

            background: #f8f8f8;
            border-bottom: 1px solid #eeeeee;
        }

        .pm-header-container {
            width: min(1240px, calc(100% - 40px));
            margin: 0 auto;
        }

        .pm-top-contact-inner {
            min-height: 42px;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .pm-contact-details {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;

            margin: 0;
            padding: 0;

            list-style: none;

            color: #777;
            font-size: 12px;
        }

        .pm-contact-details a {
            transition: color .2s ease;
        }

        .pm-contact-details a:hover {
            color: var(--pm-orange);
        }

        .pm-contact-slogan {
            color: #555;
        }

        .pm-social-top {
            display: flex;
            align-items: center;
            gap: 10px;

            margin: 0;
            padding: 0;

            list-style: none;
        }

        .pm-social-top a {
            width: 27px;
            height: 27px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            color: #777;
            font-size: 11px;

            transition:
                background .2s ease,
                color .2s ease;
        }

        .pm-social-top a:hover {
            background: var(--pm-orange);
            color: #fff;
        }


        /* РћСЃРЅРѕРІРЅР°СЏ С€Р°РїРєР° */

        .pm-main-header {
            min-height: 92px;

            background: #fff;
            border-bottom: 1px solid #eeeeee;

            position: relative;
            z-index: 20;
        }

        .pm-main-header-inner {
            min-height: 92px;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .pm-header-logo {
            display: flex;
            align-items: center;

            flex: 0 0 auto;
        }

        .pm-header-logo img {
            display: block;

            width: auto;
            height: 60px;
            max-width: 251px;
        }

        .pm-main-nav {
            display: flex;
            align-items: center;

            margin-left: auto;
        }

        .pm-main-nav > ul {
            display: flex;
            align-items: center;

            margin: 0;
            padding: 0;

            list-style: none;
        }

        .pm-main-nav > ul > li {
            position: relative;
        }

        .pm-main-nav > ul > li > a {
            min-height: 92px;

            display: flex;
            align-items: center;

            padding: 0 15px;

            color: #333;

            font-size: 12px;
            font-weight: 700;

            letter-spacing: .01em;

            white-space: nowrap;

            transition:
                color .2s ease,
                background .2s ease;
        }

        .pm-main-nav > ul > li > a:hover {
            color: var(--pm-orange);
        }

        .pm-home-link {
            font-size: 20px !important;
        }

        /* Desktop dropdown */

        .pm-main-nav .pm-has-dropdown > a::after {
            content: "вЊ„";

            margin-left: 7px;

            color: #aaa;
            font-size: 11px;
        }

        .pm-dropdown {
            position: absolute;
            top: 100%;
            left: 0;

            min-width: 250px;

            margin: 0;
            padding: 10px 0;

            background: #fff;

            border: 1px solid #eeeeee;

            box-shadow:
                0 12px 35px rgba(0,0,0,.10);

            opacity: 0;
            visibility: hidden;

            transform: translateY(8px);

            transition:
                opacity .2s ease,
                visibility .2s ease,
                transform .2s ease;

            list-style: none;
        }

        .pm-has-dropdown:hover > .pm-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .pm-dropdown li {
            position: relative;
        }

        .pm-dropdown a {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 10px 17px;

            color: #555;

            font-size: 12px;
            line-height: 1.35;
        }

        .pm-dropdown a:hover {
            background: #fafafa;
            color: var(--pm-orange);
        }

        .pm-dropdown .pm-dropdown {
            top: -10px;
            left: 100%;
        }

        .pm-dropdown li:hover > .pm-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .pm-submenu-arrow {
            color: #aaa;
            margin-left: 12px;
        }

        /* Mobile header */

        .pm-mobile-menu-button {
            display: none;

            width: 44px;
            height: 44px;

            align-items: center;
            justify-content: center;

            border: 0;
            border-radius: 8px;

            background: transparent;

            color: #333;

            font-size: 25px;

            cursor: pointer;
        }


        /* =========================================================
           PAGE
        ========================================================= */

        .pm-page {
            min-height: 600px;

            padding: 55px 0 70px;

            background:
                radial-gradient(
                    circle at 85% 10%,
                    rgba(255, 245, 195, .45),
                    transparent 28%
                ),
                var(--pm-white);
        }

        .pm-content {
            width: min(1180px, calc(100% - 40px));
            margin: 0 auto;
        }

        .pm-heading {
            margin-bottom: 32px;
        }

        .pm-heading h1 {
            margin: 0 0 10px;

            color: var(--pm-black);

            font-size: clamp(31px, 4vw, 45px);
            line-height: 1.08;
            letter-spacing: -.035em;
            font-weight: 800;
        }

        .pm-heading p {
            max-width: 700px;

            margin: 0;

            color: var(--pm-muted);

            font-size: 15px;
            line-height: 1.65;
        }

        .pm-layout {
            display: grid;

            grid-template-columns:
                minmax(0, 1.35fr)
                minmax(300px, .65fr);

            gap: 25px;

            align-items: start;
        }


        /* =========================================================
           FORM CARD
        ========================================================= */

        .pm-card {
            background: #fff;

            border: 1px solid rgba(79,80,107,.10);
            border-radius: 25px;

            box-shadow:
                0 18px 55px rgba(14,5,16,.07);
        }

        .pm-form-card {
            padding: 35px;
        }

        .pm-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;
        }

        .pm-card-title {
            margin: 0 0 7px;

            color: var(--pm-black);

            font-size: 21px;
            line-height: 1.25;
            font-weight: 800;
        }

        .pm-card-description {
            margin: 0;

            color: var(--pm-muted);

            font-size: 13px;
            line-height: 1.55;
        }

        .pm-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-width: 45px;
            height: 40px;

            padding: 0 10px;

            border-radius: 12px;

            background: var(--pm-cream);
            color: var(--pm-orange);

            font-size: 11px;
            font-weight: 800;

            white-space: nowrap;
        }

        .pm-field {
            margin-bottom: 20px;
        }

        .pm-label {
            display: block;

            margin-bottom: 8px;

            color: var(--pm-black);

            font-size: 13px;
            font-weight: 700;
        }

        .pm-input,
        .pm-select {
            display: block;

            width: 100%;
            height: 54px;

            padding: 0 16px;

            border: 1px solid var(--pm-gray);
            border-radius: 14px;

            outline: none;

            background: #fff;
            color: var(--pm-black);

            font-size: 14px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .pm-input::placeholder {
            color: #999;
        }

        .pm-input:focus,
        .pm-select:focus {
            border-color: var(--pm-orange);

            box-shadow:
                0 0 0 4px rgba(255,89,0,.10);
        }

        .pm-errors {
            margin-bottom: 22px;
            padding: 15px 17px;

            border: 1px solid rgba(196,48,48,.18);
            border-radius: 14px;

            background: #fff5f5;
            color: #9d2525;

            font-size: 13px;
            line-height: 1.55;
        }

        .pm-errors ul {
            margin: 0;
            padding-left: 18px;
        }

        .pm-conditions {
            margin: 26px 0 22px;
            padding: 18px;

            border-radius: 17px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,245,195,.72),
                    rgba(249,214,113,.24)
                );

            border: 1px solid rgba(243,182,31,.16);
        }

        .pm-conditions-title {
            margin: 0 0 6px;

            font-size: 13px;
            font-weight: 800;
        }

        .pm-conditions-text {
            margin: 0;

            color: var(--pm-muted);

            font-size: 12px;
            line-height: 1.6;
        }

        .pm-button {
            width: 100%;
            min-height: 56px;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            padding: 0 22px;

            border: 0;
            border-radius: 15px;

            background:
                linear-gradient(
                    135deg,
                    var(--pm-orange),
                    var(--pm-orange-light)
                );

            color: #fff;

            font-size: 14px;
            font-weight: 800;

            cursor: pointer;

            box-shadow:
                0 12px 28px rgba(255,89,0,.22);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .pm-button:hover {
            transform: translateY(-1px);

            box-shadow:
                0 15px 32px rgba(255,89,0,.28);
        }

        .pm-note {
            margin: 15px 0 0;

            color: #888;

            font-size: 11px;
            line-height: 1.55;

            text-align: center;
        }


        /* =========================================================
           RIGHT COLUMN
        ========================================================= */

        .pm-info-column {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .pm-info-card {
            padding: 25px;
        }

        .pm-info-accent {
            position: relative;
            overflow: hidden;

            min-height: 210px;

            background:
                linear-gradient(
                    135deg,
                    var(--pm-dark),
                    #17101a
                );

            color: #fff;
        }

        .pm-info-accent::after {
            content: "";

            position: absolute;

            width: 220px;
            height: 220px;

            right: -85px;
            bottom: -100px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(255,89,0,.48),
                    rgba(255,89,0,.08) 50%,
                    transparent 70%
                );
        }

        .pm-info-icon {
            position: relative;
            z-index: 2;

            width: 44px;
            height: 44px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 21px;

            border-radius: 13px;

            background: rgba(255,89,0,.16);
            color: var(--pm-orange);

            font-size: 20px;
        }

        .pm-info-title {
            position: relative;
            z-index: 2;

            margin: 0 0 9px;

            font-size: 19px;
            line-height: 1.25;
            font-weight: 800;
        }

        .pm-info-text {
            position: relative;
            z-index: 2;

            margin: 0;

            color: rgba(255,255,255,.65);

            font-size: 12px;
            line-height: 1.65;
        }

        .pm-info-light {
            background: #fff;
        }

        .pm-info-light-title {
            margin: 0 0 15px;

            font-size: 15px;
            font-weight: 800;
        }

        .pm-info-list {
            display: flex;
            flex-direction: column;
            gap: 13px;

            margin: 0;
            padding: 0;

            list-style: none;
        }

        .pm-info-list li {
            display: flex;
            align-items: flex-start;
            gap: 11px;

            color: var(--pm-muted);

            font-size: 12px;
            line-height: 1.5;
        }

        .pm-info-check {
            width: 20px;
            height: 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex: 0 0 20px;

            border-radius: 50%;

            background: var(--pm-cream);
            color: var(--pm-orange);

            font-size: 11px;
            font-weight: 900;
        }

        /* Jivo */

        .pm-jivo-button {
            width: 100%;
            min-height: 48px;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            margin-top: 16px;

            border: 1px solid rgba(255,255,255,.15);
            border-radius: 13px;

            background: rgba(255,255,255,.07);
            color: #fff;

            font-size: 12px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background .2s ease,
                border-color .2s ease;
        }

        .pm-jivo-button:hover {
            background: rgba(255,89,0,.18);
            border-color: rgba(255,89,0,.35);
        }


        /* =========================================================
           FOOTER
        ========================================================= */

        .pm-site-footer {
            background: #0E0510;
            color: rgba(255,255,255,.72);
        }

        .pm-footer-main {
            padding: 55px 0 45px;
        }

        .pm-footer-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 45px;
        }

        .pm-footer-column {
            min-width: 0;
        }

        .pm-footer-separator {
            height: 1px;

            margin-bottom: 24px;

            background:
                rgba(255,255,255,.10);
        }

        .pm-footer-title {
            margin: 0 0 20px;

            color: #fff;

            font-size: 14px;
            font-weight: 700;

            letter-spacing: .02em;
        }

        .pm-footer-menu {
            margin: 0;
            padding: 0;

            list-style: none;
        }

        .pm-footer-menu li {
            margin-bottom: 12px;
        }

        .pm-footer-menu a {
            display: flex;
            align-items: flex-start;
            gap: 9px;

            color: rgba(255,255,255,.58);

            font-size: 12px;
            line-height: 1.45;

            transition: color .2s ease;
        }

        .pm-footer-menu a:hover {
            color: var(--pm-yellow);
        }

        .pm-footer-icon {
            width: 17px;

            flex: 0 0 17px;

            color: var(--pm-yellow);

            text-align: center;
        }

        .pm-footer-sup {
            color: rgba(255,255,255,.38);
            font-size: 9px;
        }

        .pm-footer-logo {
            display: block;

            width: 251px;
            max-width: 100%;
            height: auto;

            margin: 27px 0 25px;
        }

        .pm-footer-search {
            display: flex;

            width: 100%;
        }

        .pm-footer-search input {
            min-width: 0;
            flex: 1;

            height: 42px;

            padding: 0 12px;

            border: 1px solid rgba(255,255,255,.12);
            border-right: 0;

            border-radius: 6px 0 0 6px;

            background: rgba(255,255,255,.04);

            color: #fff;

            outline: none;
        }

        .pm-footer-search button {
            width: 46px;
            height: 42px;

            border: 1px solid rgba(255,255,255,.12);

            border-radius: 0 6px 6px 0;

            background: var(--pm-orange);
            color: #fff;

            cursor: pointer;
        }

        .pm-footer-contact {
            margin-top: 20px;

            color: rgba(255,255,255,.62);

            font-size: 12px;
            line-height: 2;
        }

        .pm-footer-contact strong {
            color: var(--pm-yellow);
            font-weight: 400;
        }

        .pm-footer-address {
            margin-top: 20px;

            color: rgba(255,255,255,.55);

            font-size: 12px;
            line-height: 1.6;
        }

        .pm-footer-bottom {
            border-top: 1px solid rgba(255,255,255,.10);
        }

        .pm-footer-bottom-inner {
            min-height: 82px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 25px;
        }

        .pm-copyright {
            color: rgba(255,255,255,.45);

            font-size: 11px;
        }

        .pm-copyright a {
            color: rgba(255,255,255,.65);
        }

        .pm-footer-social {
            display: flex;
            align-items: center;
            gap: 9px;

            margin: 0;
            padding: 0;

            list-style: none;
        }

        .pm-footer-social a {
            width: 32px;
            height: 32px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: rgba(255,255,255,.05);

            color: rgba(255,255,255,.55);

            font-size: 12px;

            transition:
                background .2s ease,
                color .2s ease;
        }

        .pm-footer-social a:hover {
            background: var(--pm-orange);
            color: #fff;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {

            .pm-main-nav > ul > li > a {
                padding-left: 9px;
                padding-right: 9px;

                font-size: 10px;
            }

            .pm-header-logo img {
                max-width: 205px;
            }

            .pm-layout {
                grid-template-columns: 1fr;
            }

            .pm-info-column {
                display: grid;

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }


        @media (max-width: 850px) {

            .pm-top-contact {
                display: none;
            }

            .pm-main-header,
            .pm-main-header-inner {
                min-height: 75px;
            }

            .pm-header-logo img {
                height: 52px;
                max-width: 205px;
            }

            .pm-mobile-menu-button {
                display: flex;
            }

            .pm-main-nav {
                position: absolute;

                top: 100%;
                left: 0;
                right: 0;

                display: none;

                padding: 10px 20px 20px;

                background: #fff;

                border-top: 1px solid #eee;

                box-shadow:
                    0 12px 30px rgba(0,0,0,.10);
            }

            .pm-main-nav.is-open {
                display: block;
            }

            .pm-main-nav > ul {
                display: block;
            }

            .pm-main-nav > ul > li > a {
                min-height: 45px;

                padding: 0 5px;

                font-size: 12px;
            }

            .pm-dropdown {
                position: static;

                display: none;

                min-width: 0;

                padding: 0 0 5px 15px;

                border: 0;

                box-shadow: none;

                opacity: 1;
                visibility: visible;
                transform: none;
            }

            .pm-has-dropdown:hover > .pm-dropdown {
                display: block;
            }

            .pm-footer-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
        }


        @media (max-width: 700px) {

            .pm-header-container,
            .pm-content {
                width: min(100% - 28px, 1180px);
            }

            .pm-page {
                padding: 38px 0 50px;
            }

            .pm-heading h1 {
                font-size: 31px;
            }

            .pm-heading p {
                font-size: 14px;
            }

            .pm-form-card {
                padding: 23px 18px;

                border-radius: 21px;
            }

            .pm-info-column {
                grid-template-columns: 1fr;
            }

            .pm-footer-main {
                padding: 40px 0 30px;
            }

            .pm-footer-bottom-inner {
                min-height: auto;

                padding: 22px 0;

                flex-direction: column;
                align-items: flex-start;
            }
        }


        @media (max-width: 420px) {

            .pm-header-container,
            .pm-content {
                width: calc(100% - 22px);
            }

            .pm-header-logo img {
                height: 45px;
                max-width: 175px;
            }

            .pm-page {
                padding-top: 30px;
            }

            .pm-heading h1 {
                font-size: 28px;
            }

            .pm-form-card {
                padding: 20px 15px;
            }

            .pm-input,
            .pm-select {
                height: 52px;
            }

            .pm-button {
                min-height: 54px;
            }
        }

    </style>
</head>

<body>


<!-- =============================================================
     HEADER
============================================================= -->

<header class="pm-site-header">

    <!-- Р’РµСЂС…РЅСЏСЏ РєРѕРЅС‚Р°РєС‚РЅР°СЏ СЃС‚СЂРѕРєР° -->

    <div class="pm-top-contact">

        <div class="pm-header-container">

            <div class="pm-top-contact-inner">

                <ul class="pm-contact-details">

                    <li class="pm-contact-slogan">
                        РњРѕРЅС‚Р°Р¶, СЃРІРµРґРµРЅРёРµ РјСѓР·С‹РєРё |
                    </li>

                    <li>
                        <a href="tel:+79252760168">
                            +7 925 276-01-68
                        </a>
                    </li>

                    <li>
                        <a href="mailto:info@podberimuzyku.ru">
                            info@podberimuzyku.ru
                        </a>
                    </li>

                </ul>


                <ul class="pm-social-top">

                    <li>
                        <a
                            href="https://api.whatsapp.com/send?phone=79252760168"
                            target="_blank"
                            rel="noopener"
                            aria-label="WhatsApp"
                        >
                            WA
                        </a>
                    </li>

                    <li>
                        <a
                            href="https://vk.com/podberimuzykuru"
                            target="_blank"
                            rel="noopener"
                            aria-label="VKontakte"
                        >
                            VK
                        </a>
                    </li>

                    <li>
                        <a
                            href="https://www.youtube.com/channel/UCj-hwMN9py-DDVgOcxd5ghw"
                            target="_blank"
                            rel="noopener"
                            aria-label="YouTube"
                        >
                            в–¶
                        </a>
                    </li>

                    <li>
                        <a
                            href="https://t.me/PODBERIMUZYKURU"
                            target="_blank"
                            rel="noopener"
                            aria-label="Telegram"
                        >
                            TG
                        </a>
                    </li>

                    <li>
                        <a
                            href="https://podberimuzyku.ru/feed/"
                            target="_blank"
                            rel="noopener"
                            aria-label="RSS"
                        >
                            RSS
                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </div>


    <!-- РћСЃРЅРѕРІРЅР°СЏ С€Р°РїРєР° -->

    <div class="pm-main-header">

        <div class="pm-header-container">

            <div class="pm-main-header-inner">


                <a
                    class="pm-header-logo"
                    href="https://podberimuzyku.ru/"
                    aria-label="PODBERIMUZYKU.RU"
                >

                    <img
                        src="https://res.cloudinary.com/dgvvtwlzl/image/upload/v1682190921/Sites%20Folder/retina-podberimuzyku.svg"
                        alt="PODBERIMUZYKU.RU"
                    >

                </a>


                <button
                    class="pm-mobile-menu-button"
                    type="button"
                    aria-label="РћС‚РєСЂС‹С‚СЊ РјРµРЅСЋ"
                    onclick="document.querySelector('.pm-main-nav').classList.toggle('is-open')"
                >
                    в°
                </button>


                <nav
                    class="pm-main-nav"
                    aria-label="Р“Р»Р°РІРЅРѕРµ РјРµРЅСЋ"
                >

                    <ul>

                        <li>
                            <a
                                class="pm-home-link"
                                href="https://podberimuzyku.ru/"
                                aria-label="Р“Р»Р°РІРЅР°СЏ"
                            >
                                вЊ‚
                            </a>
                        </li>


                        <li class="pm-has-dropdown">

                            <a href="#">
                                РџР РРњР•Р Р« Р РђР‘РћРў
                            </a>

                            <ul class="pm-dropdown">

                                <li class="pm-has-dropdown">

                                    <a href="https://podberimuzyku.ru/category/figurnoe-katanie/">
                                        Р¤РёРіСѓСЂРЅРѕРµ РєР°С‚Р°РЅРёРµ
                                        <span class="pm-submenu-arrow">вЂє</span>
                                    </a>

                                    <ul class="pm-dropdown">

                                        <li>
                                            <a href="https://podberimuzyku.ru/category/figurnoe-katanie/kupit-trek-dlya-fk/">
                                                РљСѓРїРёС‚СЊ С‚СЂРµРє РґР»СЏ Р¤Рљ
                                            </a>
                                        </li>

                                    </ul>

                                </li>


                                <li class="pm-has-dropdown">

                                    <a href="https://podberimuzyku.ru/category/xudozhestvennaya-gimnastika/">
                                        РҐСѓРґРѕР¶РµСЃС‚РІРµРЅРЅР°СЏ РіРёРјРЅР°СЃС‚РёРєР°
                                        <span class="pm-submenu-arrow">вЂє</span>
                                    </a>

                                    <ul class="pm-dropdown">

                                        <li>
                                            <a href="https://podberimuzyku.ru/category/xudozhestvennaya-gimnastika/kupit-trek-dlya-hg/">
                                                РљСѓРїРёС‚СЊ С‚СЂРµРє РґР»СЏ РҐР“
                                            </a>
                                        </li>

                                    </ul>

                                </li>


                                <li class="pm-has-dropdown">

                                    <a href="https://podberimuzyku.ru/category/rollersport/">
                                        Р РѕР»Р»РµСЂ РЎРїРѕСЂС‚
                                        <span class="pm-submenu-arrow">вЂє</span>
                                    </a>

                                    <ul class="pm-dropdown">

                                        <li>
                                            <a href="https://podberimuzyku.ru/category/rollersport/kupit-trek-dlya-rs/">
                                                РљСѓРїРёС‚СЊ С‚СЂРµРє РґР»СЏ Р РЎ
                                            </a>
                                        </li>

                                    </ul>

                                </li>

                            </ul>

                        </li>


                        <li class="pm-has-dropdown">

                            <a href="https://podberimuzyku.ru/audio-category/muzyikalnyie-sborniki/">
                                РњРЈР—Р«РљРђР›Р¬РќР«Р• РЎР‘РћР РќРРљР
                            </a>

                            <ul class="pm-dropdown">

                                <li>
                                    <a href="https://podberimuzyku.ru/audio-category/instrumentalnaya-muzyika/">
                                        РРЅСЃС‚СЂСѓРјРµРЅС‚Р°Р»СЊРЅР°СЏ РјСѓР·С‹РєР°
                                    </a>
                                </li>

                                <li>
                                    <a href="https://podberimuzyku.ru/audio-category/originalnyiy-saundtrek/">
                                        РћСЂРёРіРёРЅР°Р»СЊРЅС‹Р№ СЃР°СѓРЅРґС‚СЂРµРє
                                    </a>
                                </li>

                                <li>
                                    <a href="https://podberimuzyku.ru/audio-category/muzyika-kino/">
                                        РњСѓР·С‹РєР° РєРёРЅРѕ
                                    </a>
                                </li>

                            </ul>

                        </li>


                        <li>
                            <a href="https://podberimuzyku.ru/about/">
                                Рћ РџР РћР•РљРўР•
                            </a>
                        </li>


                        <li>
                            <a href="https://podberimuzyku.ru/contacts/">
                                РљРћРќРўРђРљРўР«
                            </a>
                        </li>

                    </ul>

                </nav>

            </div>

        </div>

    </div>

</header>


<!-- =============================================================
     CONTENT
============================================================= -->

<main class="pm-page">

    <div class="pm-content">


        <header class="pm-heading">

            <h1>
                РџРѕР»СѓС‡РёС‚СЊ РїРѕРґРїРёСЃРєСѓ
            </h1>

            <p>
                Р’С‹Р±РµСЂРёС‚Рµ РїРѕРґС…РѕРґСЏС‰РёР№ С‚Р°СЂРёС„, СѓРєР°Р¶РёС‚Рµ СЃРІРѕРё РґР°РЅРЅС‹Рµ
                Рё РїРµСЂРµР№РґРёС‚Рµ Рє Р±РµР·РѕРїР°СЃРЅРѕР№ РѕРїР»Р°С‚Рµ.
            </p>

        </header>


        <div class="pm-layout">


            <!-- FORM -->

            <section class="pm-card pm-form-card">

                <div class="pm-card-head">

                    <div>

                        <h2 class="pm-card-title">
                            РћС„РѕСЂРјР»РµРЅРёРµ РїРѕРґРїРёСЃРєРё
                        </h2>

                        <p class="pm-card-description">
                            Р—Р°РїРѕР»РЅРёС‚Рµ РґР°РЅРЅС‹Рµ РЅРёР¶Рµ. РџРѕСЃР»Рµ РѕС‚РїСЂР°РІРєРё
                            РІС‹ РїРµСЂРµР№РґС‘С‚Рµ Рє РѕРїР»Р°С‚Рµ РІС‹Р±СЂР°РЅРЅРѕРіРѕ С‚Р°СЂРёС„Р°.
                        </p>

                    </div>

                    <div class="pm-step">
                        РЁРђР“ 1
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
                            Р’Р°С€Рµ РёРјСЏ
                        </label>

                        <input
                            id="pm-subscription-name"
                            class="pm-input"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            maxlength="100"
                            autocomplete="name"
                            placeholder="Р’РІРµРґРёС‚Рµ РІР°С€Рµ РёРјСЏ"
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
                            РўР°СЂРёС„ РїРѕРґРїРёСЃРєРё
                        </label>

                        <select
                            id="pm-subscription-plan"
                            class="pm-select"
                            name="plan_id"
                            required
                        >

                            <option value="">
                                Р’С‹Р±РµСЂРёС‚Рµ С‚Р°СЂРёС„
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
                                    вЂ”
                                    {{ number_format(
                                        (float) $plan->price,
                                        0,
                                        ',',
                                        ' '
                                    ) }}
                                    в‚Ѕ
                                    /
                                    {{ $plan->duration_days }}
                                    РґРЅ.
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="pm-conditions">

                        <p class="pm-conditions-title">
                            РЈСЃР»РѕРІРёСЏ РїРѕРґРїРёСЃРєРё
                        </p>

                        <p class="pm-conditions-text">
                            РЎСЂРѕРє РґРµР№СЃС‚РІРёСЏ Рё СЃС‚РѕРёРјРѕСЃС‚СЊ РѕРїСЂРµРґРµР»СЏСЋС‚СЃСЏ
                            РІС‹Р±СЂР°РЅРЅС‹Рј С‚Р°СЂРёС„РѕРј. РџРѕСЃР»Рµ СѓСЃРїРµС€РЅРѕР№ РѕРїР»Р°С‚С‹
                            РїРѕРґРїРёСЃРєР° Р°РєС‚РёРІРёСЂСѓРµС‚СЃСЏ Р°РІС‚РѕРјР°С‚РёС‡РµСЃРєРё.
                        </p>

                    </div>


                    <button
                        class="pm-button"
                        type="submit"
                    >

                        <span>
                            РџРµСЂРµР№С‚Рё Рє РѕРїР»Р°С‚Рµ
                        </span>

                        <span>
                            в†’
                        </span>

                    </button>


                    <p class="pm-note">
                        РџРѕСЃР»Рµ СЃРѕР·РґР°РЅРёСЏ Р·Р°РєР°Р·Р° РІС‹ Р±СѓРґРµС‚Рµ
                        РїРµСЂРµРЅР°РїСЂР°РІР»РµРЅС‹ РЅР° СЃС‚СЂР°РЅРёС†Сѓ РѕРїР»Р°С‚С‹ YooMoney.
                    </p>

                </form>

            </section>


            <!-- INFORMATION -->

            <aside class="pm-info-column">


                <div class="pm-card pm-info-card pm-info-accent">

                    <div class="pm-info-icon">
                        в™Є
                    </div>

                    <h2 class="pm-info-title">
                        Р’Р°С€Р° РјСѓР·С‹РєР° вЂ”
                        РІР°С€ РґРѕСЃС‚СѓРї
                    </h2>

                    <p class="pm-info-text">
                        РџРѕРґРїРёСЃРєР° РѕС‚РєСЂС‹РІР°РµС‚ РґРѕСЃС‚СѓРї Рє РјР°С‚РµСЂРёР°Р»Р°Рј
                        Рё РїСЂРѕСЃР»СѓС€РёРІР°РЅРёСЋ Р·Р°РєСЂС‹С‚РѕРіРѕ РєРѕРЅС‚РµРЅС‚Р°
                        PODBERIMUZYKU.RU.
                    </p>

                </div>


                <div class="pm-card pm-info-card pm-info-light">

                    <h3 class="pm-info-light-title">
                        РљР°Рє СЌС‚Рѕ СЂР°Р±РѕС‚Р°РµС‚
                    </h3>

                    <ul class="pm-info-list">

                        <li>
                            <span class="pm-info-check">вњ“</span>

                            <span>
                                Р’С‹Р±РµСЂРёС‚Рµ РїРѕРґС…РѕРґСЏС‰РёР№ С‚Р°СЂРёС„.
                            </span>
                        </li>

                        <li>
                            <span class="pm-info-check">вњ“</span>

                            <span>
                                РЈРєР°Р¶РёС‚Рµ РёРјСЏ Рё e-mail.
                            </span>
                        </li>

                        <li>
                            <span class="pm-info-check">вњ“</span>

                            <span>
                                РџРµСЂРµР№РґРёС‚Рµ РЅР° СЃС‚СЂР°РЅРёС†Сѓ РѕРїР»Р°С‚С‹.
                            </span>
                        </li>

                        <li>
                            <span class="pm-info-check">вњ“</span>

                            <span>
                                РџРѕСЃР»Рµ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ РѕРїР»Р°С‚С‹
                                РґРѕСЃС‚СѓРї Р°РєС‚РёРІРёСЂСѓРµС‚СЃСЏ Р°РІС‚РѕРјР°С‚РёС‡РµСЃРєРё.
                            </span>
                        </li>

                    </ul>

                </div>


                <!-- JIVO -->

                <div class="pm-card pm-info-card pm-info-accent">

                    <div class="pm-info-icon">
                        ?
                    </div>

                    <h3 class="pm-info-title">
                        РќСѓР¶РЅР° РїРѕРјРѕС‰СЊ?
                    </h3>

                    <p class="pm-info-text">
                        Р•СЃР»Рё Сѓ РІР°СЃ РІРѕР·РЅРёРєР»Рё РІРѕРїСЂРѕСЃС‹ РїРѕ РїРѕРґРїРёСЃРєРµ,
                        С‚Р°СЂРёС„Р°Рј РёР»Рё РѕРїР»Р°С‚Рµ вЂ” РЅР°РїРёС€РёС‚Рµ РЅР°Рј.
                    </p>

                    <button
                        type="button"
                        class="pm-jivo-button"
                        onclick="
                            if (typeof jivo_api !== 'undefined' && jivo_api.open) {
                                jivo_api.open();
                            }
                        "
                    >
                        рџ’¬ РћС‚РєСЂС‹С‚СЊ С‡Р°С‚ Jivo
                    </button>

                </div>


            </aside>

        </div>

    </div>

</main>


<!-- =============================================================
     FOOTER
============================================================= -->

<footer class="pm-site-footer">


    <div class="pm-footer-main">

        <div class="pm-content">

            <div class="pm-footer-grid">


                <!-- =========================
                     РЈРЎР›РЈР“Р
                ========================== -->

                <div class="pm-footer-column">

                    <div class="pm-footer-separator"></div>

                    <h3 class="pm-footer-title">
                        РЈРЎР›РЈР“Р
                    </h3>

                    <ul class="pm-footer-menu">

                        <li>
                            <a href="https://podberimuzyku.ru/category/dopolnitelnyie-uslugi-obrabotka/">
                                <span class="pm-footer-icon">в—†</span>
                                <span>
                                    Р”РћРџРћР›РќРРўР•Р›Р¬РќР«Р• РЈРЎР›РЈР“Р
                                    <sup class="pm-footer-sup">
                                        (РѕР±СЂР°Р±РѕС‚РєР°)
                                    </sup>
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="https://podberimuzyku.ru/category/podbor-muzyikalnyih-trekov/">
                                <span class="pm-footer-icon">в™Є</span>
                                <span>
                                    РђРќРђР›РР— РњРЈР—Р«РљРђР›Р¬РќР«РҐ РўР Р•РљРћР’
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="https://podberimuzyku.ru/">
                                <span class="pm-footer-icon">в—‰</span>
                                <span>
                                    + Р’РР”Р•Рћ + РђРЈР”РРћ
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="https://podberimuzyku.ru/category/zvukozapis/">
                                <span class="pm-footer-icon">в—Џ</span>
                                <span>
                                    РЈРЎР›РЈР“Р Р—Р’РЈРљРћР—РђРџРРЎР
                                </span>
                            </a>
                        </li>

                        <li>
                            <a
                                href="https://audio-editor.ru/"
                                target="_blank"
                                rel="noopener"
                            >
                                <span class="pm-footer-icon">в–Ј</span>
                                <span>
                                    РђРЈР”РРћ Р Р•Р”РђРљРўРћР 
                                    <sup class="pm-footer-sup">
                                        (Р±РµСЃРїР»Р°С‚РЅС‹Р№)
                                    </sup>
                                </span>
                            </a>
                        </li>

                    </ul>


                    <img
                        class="pm-footer-logo"
                        src="https://res.cloudinary.com/dgvvtwlzl/image/upload/v1682190921/Sites%20Folder/retina-podberimuzyku.svg"
                        alt="Р›РѕРіРѕС‚РёРї PODBERIMUZYKU.RU"
                    >


                    <form
                        class="pm-footer-search"
                        method="get"
                        action="https://podberimuzyku.ru/"
                    >

                        <input
                            type="search"
                            name="s"
                            placeholder="РџРѕРёСЃРє..."
                            aria-label="РџРѕРёСЃРє"
                        >

                        <button type="submit">
                            рџ”Ќ
                        </button>

                    </form>

                </div>


                <!-- =========================
                     РРќР¤РћР РњРђР¦РРЇ
                ========================== -->

                <div class="pm-footer-column">

                    <div class="pm-footer-separator"></div>

                    <h3 class="pm-footer-title">
                        РРќР¤РћР РњРђР¦РРЇ
                    </h3>

                    <ul class="pm-footer-menu">

                        <li>
                            <a href="https://podberimuzyku.ru/ostavit-zayavku/">
                                <span class="pm-footer-icon">в—Џ</span>
                                <span>
                                    РџР Р•Р”Р’РђР РРўР•Р›Р¬РќРђРЇ Р—РђРЇР’РљРђ
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="https://podberimuzyku.ru/dlya-pravoobladatelej/">
                                <span class="pm-footer-icon">в—Џ</span>
                                <span>
                                    Р”Р›РЇ РџР РђР’РћРћР‘Р›РђР”РђРўР•Р›Р•Р™
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="https://podberimuzyku.ru/polzovatelskogo-soglasheniya/">
                                <span class="pm-footer-icon">в—Џ</span>
                                <span>
                                    РџРћР›Р¬Р—РћР’РђРўР•Р›Р¬РЎРљРћР“Рћ РЎРћР“Р›РђРЁР•РќРРЇ
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="https://podberimuzyku.ru/politika-konfidencialnosti/">
                                <span class="pm-footer-icon">в—Џ</span>
                                <span>
                                    РџРћР›РРўРРљРђ РљРћРќР¤РР”Р•РќР¦РРђР›Р¬РќРћРЎРўР
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                <span class="pm-footer-icon">в—Џ</span>
                                <span>
                                    Р’РћРџР РћРЎР«-РћРўР’Р•РўР«
                                </span>
                            </a>
                        </li>

                    </ul>


                    <div class="pm-footer-contact">

                        <div>
                            <strong>вЋ</strong>
                            +7 925 276-01-68
                        </div>

                        <div>
                            <strong>вњ‰</strong>
                            info@podberimuzyku.ru
                        </div>

                    </div>

                </div>


                <!-- =========================
                     РћРџР›РђРўРђ Р Р”РћРЎРўРђР’РљРђ
                ========================== -->

                <div class="pm-footer-column">

                    <div class="pm-footer-separator"></div>

                    <h3 class="pm-footer-title">
                        РћРџР›РђРўРђ Р Р”РћРЎРўРђР’РљРђ
                    </h3>

                    <ul class="pm-footer-menu">

                        <li>
                            <a href="https://podberimuzyku.ru/gde-kupit-trek/">
                                <span class="pm-footer-icon">в—†</span>
                                <span>
                                    Р“Р”Р• РљРЈРџРРўР¬ РўР Р•Рљ?
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="https://podberimuzyku.ru/price-list/">
                                <span class="pm-footer-icon">в–Ј</span>
                                <span>
                                    РџР РђР™РЎ-Р›РРЎРў
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="https://podberimuzyku.ru/conditions-for-composing/">
                                <span class="pm-footer-icon">вњ“</span>
                                <span>
                                    РЈРЎР›РћР’РРЇ РР—Р“РћРўРћР’Р›Р•РќРРЇ
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="https://podberimuzyku.ru/terms-of-delivery/">
                                <span class="pm-footer-icon">в†‘</span>
                                <span>
                                    РЈРЎР›РћР’РРЇ Р”РћРЎРўРђР’РљР
                                </span>
                            </a>
                        </li>

                        <li>
                            <a href="https://podberimuzyku.ru/terms-of-return/">
                                <span class="pm-footer-icon">в†“</span>
                                <span>
                                    РЈРЎР›РћР’РРЇ Р’РћР—Р’Р РђРўРђ
                                </span>
                            </a>
                        </li>

                    </ul>


                    <div class="pm-footer-address">

                        <span style="color:#ebd54e;">
                            в—Џ
                        </span>

                        Рі. РњРѕСЃРєРІР°, РњРѕСЃРєРѕРІСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ,
                        Р¦РµРЅС‚СЂР°Р»СЊРЅС‹Р№ С„РµРґРµСЂР°Р»СЊРЅС‹Р№ РѕРєСЂСѓРі,
                        Р Р¤, Р РѕСЃСЃРёСЏ

                    </div>

                </div>


            </div>

        </div>

    </div>


    <!-- РќРёР¶РЅСЏСЏ СЃС‚СЂРѕРєР° -->

    <div class="pm-footer-bottom">

        <div class="pm-content">

            <div class="pm-footer-bottom-inner">


                <div class="pm-copyright">

                    Р’СЃРµ РїСЂР°РІР° Р·Р°С‰РёС‰РµРЅС‹.
                    В© 2026

                    <a href="https://podberimuzyku.ru/">
                        PODBERIMUZYKU.RU
                    </a>

                </div>


                <ul class="pm-footer-social">

                    <li>
                        <a
                            href="https://api.whatsapp.com/send?phone=79252760168"
                            target="_blank"
                            rel="noopener"
                            aria-label="WhatsApp"
                        >
                            WA
                        </a>
                    </li>

                    <li>
                        <a
                            href="https://vk.com/podberimuzykuru"
                            target="_blank"
                            rel="noopener"
                            aria-label="VKontakte"
                        >
                            VK
                        </a>
                    </li>

                    <li>
                        <a
                            href="https://www.youtube.com/channel/UCj-hwMN9py-DDVgOcxd5ghw"
                            target="_blank"
                            rel="noopener"
                            aria-label="YouTube"
                        >
                            в–¶
                        </a>
                    </li>

                    <li>
                        <a
                            href="https://t.me/PODBERIMUZYKURU"
                            target="_blank"
                            rel="noopener"
                            aria-label="Telegram"
                        >
                            TG
                        </a>
                    </li>

                    <li>
                        <a
                            href="https://podberimuzyku.ru/feed/"
                            target="_blank"
                            rel="noopener"
                            aria-label="RSS"
                        >
                            RSS
                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </div>

</footer>


</body>
</html>
