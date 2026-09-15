<?php
get_header();

/* Подключаем стили */
wp_enqueue_style(
    'custom-category-style',
    get_stylesheet_directory_uri() . '/css/custom_category.css',
    array(),
    filemtime(get_stylesheet_directory() . '/css/custom_category.css')
);

$paged = max(1, get_query_var('paged'));

$posts_per_page = 12;

$args = array(
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => $posts_per_page,
    'paged'               => $paged,
    'ignore_sticky_posts' => true,
    'cat'                 => get_queried_object_id(),
);

$custom_query = new WP_Query($args);
?>

<div id="Content">
    <div class="content_wrapper clearfix">

        <div class="wpb_text_column wpb_content_element" style="padding: 5px 35px;">
            <div class="wpb_wrapper">
                <?php echo category_description(); ?>
            </div>
        </div>

        <main class="sections_group">

            <?php if ($custom_query->have_posts()) : ?>

                <section class="section">

                    <div class="section_wrapper clearfix" style="max-width:100%;">

                        <div class="column one column_blog">

                            <div class="mcb-column-inner clearfix">

                                <div class="post-grid">

                                    <?php while ($custom_query->have_posts()) : $custom_query->the_post(); ?>

                                        <?php
                                        $post_id = get_the_ID();

                                        /*
                                         * Миниатюра
                                         */
                                        $thumb_url = pmr_get_thumbnail($post_id);

                                        /*
                                         * Аудио
                                         */
                                        $audio_link = pmr_get_audio($post_id);

                                        /*
                                         * Заголовок
                                         */
                                        $title = pmr_get_title($post_id);

                                        $title = html_entity_decode(
                                            $title,
                                            ENT_QUOTES | ENT_HTML5,
                                            'UTF-8'
                                        );

                                        $short_title = mb_substr($title, 0, 50);

                                        /*
                                         * Товарный бейдж
                                         */
                                        $is_for_sale = has_category(
                                            array(1661, 1660, 1897),
                                            $post_id
                                        );
                                        ?>

                                        <div class="post-item">

                                            <div class="single-photo-wrapper image">

                                                <div class="image_frame scale-with-grid">

                                                    <div class="image_wrapper">

                                                        <a
                                                            href="<?php echo esc_url(get_permalink($post_id)); ?>"
                                                        >

                                                            <?php
                                                            /*
                                                             * Бейдж покупки
                                                             */
                                                            if ($is_for_sale) {
                                                                echo '<div class="post-sale-badge" title="Трек доступен для покупки">
                                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                                            <path fill="currentColor" d="M7 4h-2l-1 2v2h2l2.6 5.59-1.35 2.44A1 1 0 0 0 8.14 18H19v-2H8.53l.93-1.68h7.45a1 1 0 0 0 .9-.55L21 7H6.42l-.94-2ZM9 20a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0-8 0Z"/>
                                                                        </svg>
                                                                      </div>';
                                                            }

                                                            /*
                                                             * Миниатюра
                                                             */
                                                            if (!empty($thumb_url)) {
                                                                echo '<img src="' .
                                                                    esc_url($thumb_url) .
                                                                    '" alt="' .
                                                                    esc_attr(get_the_title()) .
                                                                    '">';
                                                            }

                                                            /*
                                                             * Аудио
                                                             */
                                                            if (!empty($audio_link)) {

                                                                echo '<div class="post-audio-widget">';

                                                                echo do_shortcode(
                                                                    '[zoomsounds_player source="' .
                                                                    esc_url($audio_link) .
                                                                    '" config="sample--boxed-inside" autoplay="off" cue="off" preview_on_hover="off"]'
                                                                );

                                                                echo '</div>';
                                                            }

                                                            ?>

                                                        </a>

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="post-title">
                                                <?php echo esc_html($short_title); ?>
                                            </div>

                                        </div>

                                    <?php endwhile; ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

                <?php
                /*
                 * Пагинация
                 */
                $big = 999999999;

                $links = paginate_links(array(
                    'base'      => str_replace(
                        $big,
                        '%#%',
                        esc_url(get_pagenum_link($big))
                    ),
                    'format'    => '',
                    'current'   => max(1, $paged),
                    'total'     => (int) $custom_query->max_num_pages,
                    'type'      => 'array',
                    'prev_next' => false,
                    'end_size'  => 1,
                    'mid_size'  => 1,
                ));

                $next_url = '';

                if ($paged < (int) $custom_query->max_num_pages) {
                    $next_url = get_pagenum_link($paged + 1);
                }

                if (!empty($links) && is_array($links)) {

                    echo '<div class="pager_wrapper">';

                    echo '<div class="pager_numbers">';

                    foreach ($links as $l) {

                        $l = str_replace(
                            'class="page-numbers dots"',
                            'class="dots"',
                            $l
                        );

                        $l = str_replace(
                            'class="page-numbers current"',
                            'class="current"',
                            $l
                        );

                        $l = str_replace(
                            'class="page-numbers"',
                            '',
                            $l
                        );

                        echo $l;
                    }

                    echo '</div>';

                    if ($next_url) {
                        echo '<a class="pager_next" href="' .
                            esc_url($next_url) .
                            '">Следующая страница &#8250;</a>';
                    }

                    echo '</div>';
                }
                ?>

                <?php
                /*
                 * Sidebar категории
                 */
                $current_category = get_queried_object();
                $sidebar_id = 'category-' . $current_category->slug;

                if (is_active_sidebar($sidebar_id)) {
                    dynamic_sidebar($sidebar_id);
                }
                ?>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php
wp_reset_postdata();
get_footer();
?>
