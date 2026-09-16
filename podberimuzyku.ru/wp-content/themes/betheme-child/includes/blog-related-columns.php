<?php

defined('ABSPATH') || exit;

$current_post_id = get_the_ID();

$aCategories = wp_get_post_categories($current_post_id);

if (empty($aCategories)) {
    return;
}

$related_cols  = 'col-' . absint(mfn_opts_get('blog-related-columns', 4));
$related_style = mfn_opts_get('related-style');

$args = array(
    'category__in'         => array($aCategories[0]),
    'post__not_in'         => array($current_post_id),
    'posts_per_page'       => 8,
    'orderby'              => 'rand',
    //'orderby'              => 'DESEC',
    'ignore_sticky_posts'  => true,
    'no_found_rows'        => true,
    'post_status'          => 'publish',
);

$query_related_posts = new WP_Query($args);

if (!$query_related_posts->have_posts()) {
    return;
}

echo '<div class="section-related-adjustment ' . esc_attr($related_style) . '">';

echo '<h4>' . esc_html__('Похожие проекты', 'betheme') . '</h4>';

echo '<div class="section-related-ul ' . esc_attr($related_cols) . '">';

while ($query_related_posts->have_posts()) {

    $query_related_posts->the_post();

    $post_id = get_the_ID();

    $related_class = '';

    /*
     * Миниатюра
     */

    $thumb_url = pmr_get_thumbnail($post_id);

    if (empty($thumb_url)) {
        $related_class = 'no-img';
    }

    /*
     * Аудио
     */

    $audio_link = pmr_get_audio($post_id);

    /*
     * Заголовок
     */

    $title = pmr_get_title($post_id);

    echo '<div class="column mobile-one post-related ' .
        esc_attr(implode(' ', get_post_class($related_class, $post_id))) .
    '">';

    echo '<div class="mcb-column-inner">';

    /*
     * Миниатюра
     */

    echo '<div class="single-photo-wrapper image">';

    echo '<div class="image_frame scale-with-grid">';

    echo '<div class="image_wrapper">';

    echo '<a href="' . esc_url(get_permalink($post_id)) . '">';

    if (!empty($thumb_url)) {
        echo '<img src="' .
            esc_url($thumb_url) .
            '" alt="' .
            esc_attr(wp_strip_all_tags($title)) .
            '" />';
    }

    if (!empty($audio_link)) {

        /*
         * Этот класс нужен только для нашего Shuffle-скрипта.
         * Основные DZSAP-плееры страницы его не имеют.
         */

        echo '<div class="post-audio-widget post-related-audio">';

        echo do_shortcode(
            '[zoomsounds_player source="' .
            esc_url($audio_link) .
            '" config="sample--boxed-inside" autoplay="off" cue="off" preview_on_hover="off"]'
        );

        echo '</div>';
    }

    echo '</a>';

    echo '</div>';

    echo '</div>';

    echo '</div>';

    /*
     * Дата
     */

    $list_meta = mfn_opts_get('blog-meta');

    if (isset($list_meta['date'])) {

        echo '<div class="date_label">' .
            esc_html(get_the_date()) .
            '</div>';
    }

    /*
     * Описание
     */

    echo '<div class="desc">';

    echo '<h4>';

    echo '<a href="' . esc_url(get_permalink($post_id)) . '">';

    echo esc_html($title);

    echo '</a>';

    echo '</h4>';

    echo '<hr class="hr_color" />';

    echo '</div>';

    echo '</div>';

    echo '</div>';
}

echo '</div>';

echo '</div>';

wp_reset_postdata();