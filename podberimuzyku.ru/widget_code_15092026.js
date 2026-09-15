<script>
(function () {
    'use strict';

    /*
     * ============================================================
     * DZSAP RANDOM PREVIEW
     * Блок: "Похожие проекты"
     *
     * Гость:
     *   8–10 секунд
     *
     * Авторизованный пользователь:
     *   15–25 секунд
     *
     * Обрабатываются ТОЛЬКО:
     *   .post-related-audio .audioplayer
     *
     * Основные треки страницы НЕ ЗАТРАГИВАЮТСЯ.
     * ============================================================
     */


    var CONFIG = {
        guest: {
            min: 8,
            max: 10
        },

        authorized: {
            min: 15,
            max: 25
        }
    };


    /*
     * Определяем режим пользователя.
     */

    var isAuthorized =
        document.body.classList.contains('logged-in');


    var range = isAuthorized
        ? CONFIG.authorized
        : CONFIG.guest;


    console.log(
        '%c[DZSAP RELATED RANDOM] Запуск',
        'color: green; font-weight: bold;'
    );

    console.log(
        '[DZSAP RELATED RANDOM] Режим:',
        isAuthorized
            ? 'АВТОРИЗОВАННЫЙ'
            : 'ГОСТЬ'
    );

    console.log(
        '[DZSAP RELATED RANDOM] Диапазон:',
        range.min + '–' + range.max + ' сек.'
    );


    /*
     * ============================================================
     * Случайное целое число
     * ============================================================
     */

    function randomInt(min, max) {

        return Math.floor(
            Math.random() * (max - min + 1)
        ) + min;

    }


    /*
     * ============================================================
     * Создание случайного фрагмента
     * ============================================================
     */

    function createFragment(totalDuration) {

        if (
            !totalDuration ||
            !isFinite(totalDuration)
        ) {
            return null;
        }


        /*
         * Если трек короче максимальной длины,
         * используем доступную длину.
         */

        var maxDuration = Math.min(
            range.max,
            Math.floor(totalDuration)
        );


        var minDuration = Math.min(
            range.min,
            maxDuration
        );


        if (maxDuration <= 0) {
            return null;
        }


        /*
         * Случайная длительность.
         */

        var duration = randomInt(
            minDuration,
            maxDuration
        );


        /*
         * Случайное начало.
         */

        var maxStart = Math.max(
            0,
            totalDuration - duration
        );


        var start =
            Math.random() * maxStart;


        return {
            start: Number(
                start.toFixed(2)
            ),

            duration: duration,

            end: Number(
                (start + duration).toFixed(2)
            )
        };

    }


    /*
     * ============================================================
     * Получение длительности оригинального аудио
     * ============================================================
     */

    function getDuration(url) {

        return new Promise(function (resolve, reject) {

            var audio =
                document.createElement('audio');


            audio.preload = 'metadata';


            audio.addEventListener(
                'loadedmetadata',
                function () {

                    if (
                        audio.duration &&
                        isFinite(audio.duration)
                    ) {

                        resolve(
                            audio.duration
                        );

                    } else {

                        reject();

                    }

                },
                { once: true }
            );


            audio.addEventListener(
                'error',
                function () {
                    reject();
                },
                { once: true }
            );


            audio.src = url;

        });

    }


    /*
     * ============================================================
     * Подготовка конкретного плеера
     * ============================================================
     */

    function preparePlayer(player) {

        var url =
            player.getAttribute('data-source');


        if (!url) {
            return;
        }


        var audio =
            player.querySelector('audio');


        if (!audio) {
            return;
        }


        /*
         * Не обрабатываем один плеер дважды.
         */

        if (
            audio.dataset.relatedRandomReady === '1'
        ) {
            return;
        }


        audio.dataset.relatedRandomReady = '1';


        /*
         * Состояние конкретного плеера.
         */

        var state = {

            prepared: false,

            preparing: false,

            fragment: null,

            allowPlay: false,

            ended: false

        };


        /*
         * ========================================================
         * Перехватываем первое воспроизведение.
         * ========================================================
         *
         * Важно:
         * не даём DZSAP сначала начать с 0:00.
         */

        audio.addEventListener(
            'play',
            function (event) {


                /*
                 * Это уже разрешённый нами запуск.
                 */

                if (state.allowPlay) {

                    state.allowPlay = false;

                    return;

                }


                /*
                 * Фрагмент уже выбран.
                 *
                 * При повторном Play возвращаемся
                 * к началу этого же фрагмента.
                 */

                if (state.prepared) {

                    event.preventDefault();


                    audio.pause();


                    try {

                        audio.currentTime =
                            state.fragment.start;

                    } catch (e) {}


                    state.allowPlay = true;


                    audio.play().catch(
                        function () {}
                    );


                    return;

                }


                /*
                 * Пока duration ещё определяется,
                 * ничего не запускаем.
                 */

                if (state.preparing) {

                    event.preventDefault();

                    audio.pause();

                    return;

                }


                /*
                 * =================================================
                 * ПЕРВЫЙ PLAY
                 * =================================================
                 */

                event.preventDefault();

                audio.pause();


                state.preparing = true;


                console.log(
                    '%c[DZSAP RELATED RANDOM] Play',
                    'color: blue; font-weight: bold;'
                );

                console.log(
                    '[DZSAP RELATED RANDOM] URL:',
                    url
                );


                /*
                 * Получаем полную длительность.
                 */

                getDuration(url)

                    .then(function (totalDuration) {


                        state.fragment =
                            createFragment(
                                totalDuration
                            );


                        if (!state.fragment) {

                            state.preparing = false;

                            return;

                        }


                        state.prepared = true;

                        state.preparing = false;


                        console.log(
                            '[DZSAP RELATED RANDOM] Полная длительность:',
                            totalDuration.toFixed(2) +
                            ' сек.'
                        );

                        console.log(
                            '[DZSAP RELATED RANDOM] START:',
                            state.fragment.start +
                            ' сек.'
                        );

                        console.log(
                            '[DZSAP RELATED RANDOM] END:',
                            state.fragment.end +
                            ' сек.'
                        );

                        console.log(
                            '[DZSAP RELATED RANDOM] DURATION:',
                            state.fragment.duration +
                            ' сек.'
                        );


                        /*
                         * Устанавливаем позицию
                         * ДО запуска воспроизведения.
                         */

                        try {

                            audio.currentTime =
                                state.fragment.start;

                        } catch (e) {}


                        /*
                         * Теперь разрешаем Play.
                         */

                        state.allowPlay = true;


                        audio.play().catch(
                            function (error) {

                                console.log(
                                    '[DZSAP RELATED RANDOM] Play error:',
                                    error
                                );

                            }
                        );


                    })

                    .catch(function () {

                        state.preparing = false;


                        console.error(
                            '[DZSAP RELATED RANDOM] Не удалось получить duration:',
                            url
                        );

                    });

            },
            true
        );


        /*
         * ========================================================
         * Контроль окончания фрагмента.
         * ========================================================
         */

        audio.addEventListener(
            'timeupdate',
            function () {


                if (
                    !state.fragment ||
                    !state.prepared
                ) {

                    return;

                }


                if (
                    audio.currentTime >=
                    state.fragment.end
                ) {


                    audio.pause();


                    state.ended = true;


                    /*
                     * При следующем Play
                     * начинаем тот же фрагмент.
                     */

                    try {

                        audio.currentTime =
                            state.fragment.start;

                    } catch (e) {}


                    console.log(
                        '%c[DZSAP RELATED RANDOM] Превью завершено',
                        'color: green; font-weight: bold;'
                    );

                }

            }
        );

    }


    /*
     * ============================================================
     * Инициализация ТОЛЬКО "Похожих проектов"
     * ============================================================
     */

    function init() {

        var players =
            document.querySelectorAll(
                '.post-related-audio .audioplayer[data-source]'
            );


        console.log(
            '[DZSAP RELATED RANDOM] Найдено плееров:',
            players.length
        );


        players.forEach(function (player) {

            preparePlayer(player);

        });

    }


    /*
     * ============================================================
     * Запуск
     * ============================================================
     */

    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            init
        );

    } else {

        init();

    }


    /*
     * DZSAP может создавать audio после загрузки.
     */

    window.addEventListener(
        'load',
        function () {

            setTimeout(
                init,
                1000
            );

        }
    );


})();
</script>
