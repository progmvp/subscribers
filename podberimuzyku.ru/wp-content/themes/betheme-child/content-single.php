<?php
/**
 * The template for displaying content in the single.php template
 *
 * @package Betheme
 * @author Muffin group
 * @link https://muffingroup.com
 */

// prev & next post

$single_post_nav = array(
	'hide-header'	=> false,
	'hide-sticky'	=> false,
	'in-same-term' => false,
);

$opts_single_post_nav = mfn_opts_get('prev-next-nav');
if (is_array($opts_single_post_nav)) {
	if (isset($opts_single_post_nav['hide-header'])) {
		$single_post_nav['hide-header'] = true;
	}
	if (isset($opts_single_post_nav['hide-sticky'])) {
		$single_post_nav['hide-sticky'] = true;
	}
	if (isset($opts_single_post_nav['in-same-term'])) {
		$single_post_nav['in-same-term'] = true;
	}
}

$post_prev = get_adjacent_post($single_post_nav['in-same-term'], '', true);
$post_next = get_adjacent_post($single_post_nav['in-same-term'], '', false);
$blog_page_id = get_option('page_for_posts');

// post classes

$classes = array();
if (! mfn_post_thumbnail(get_the_ID())) {
	$classes[] = 'no-img';
}
if (get_post_meta(get_the_ID(), 'mfn-post-hide-image', true)) {
	$classes[] = 'no-img';
}
if (post_password_required()) {
	$classes[] = 'no-img';
}
if (! mfn_opts_get('blog-title')) {
	$classes[] = 'no-title';
}

if (mfn_opts_get('share') == 'hide-mobile') {
	$classes[] = 'no-share-mobile';
} elseif (! mfn_opts_get('share')) {
	$classes[] = 'no-share';
}

if (mfn_opts_get('share-style')) {
	$classes[] = 'share-'. mfn_opts_get('share-style');
}

// translate

$translate['published'] = mfn_opts_get('translate') ? mfn_opts_get('translate-published', 'Published by') : __('Published by', 'betheme');
$translate['at'] = mfn_opts_get('translate') ? mfn_opts_get('translate-at', 'at') : __('at', 'betheme');
$translate['tags'] = mfn_opts_get('translate') ? mfn_opts_get('translate-tags', 'Tags') : __('Tags', 'betheme');
$translate['categories'] = mfn_opts_get('translate') ? mfn_opts_get('translate-categories', 'Categories') : __('Categories', 'betheme');
$translate['all'] = mfn_opts_get('translate') ? mfn_opts_get('translate-all', 'Show all') : __('Show all', 'betheme');
$translate['related']	= mfn_opts_get('translate') ? mfn_opts_get('translate-related', 'Related posts') : __('Related posts', 'betheme');
$translate['readmore'] = mfn_opts_get('translate') ? mfn_opts_get('translate-readmore', 'Read more') : __('Read more', 'betheme');

$button_text = $translate['readmore'];
?>

<article id="post-<?php the_ID(); ?>" <?php post_class($classes); ?>>

	<?php
		// single post navigation | sticky
		if (! $single_post_nav['hide-sticky']) {
			echo mfn_post_navigation_sticky($post_prev, 'prev', 'icon-left-open-big');
			echo mfn_post_navigation_sticky($post_next, 'next', 'icon-right-open-big');
		}
	?>

	<?php if (get_post_meta(get_the_ID(), 'mfn-post-template', true) != 'intro'): ?>

		<header class="section mcb-section section-post-header">
			<div class="section_wrapper clearfix">

				<?php
					// single post navigation | header
					if (! $single_post_nav['hide-header']) {
						echo mfn_post_navigation_header($post_prev, $post_next, $blog_page_id, $translate);
					}
				?>

				<div class="column one post-header">
					<div class="mcb-column-inner">

						<?php if (mfn_opts_get('share-style') != 'simple'): ?>
							<div class="button-love"><?php echo mfn_love() ?></div>
						<?php endif; ?>

						<div class="title_wrapper">

							<?php
								if (mfn_opts_get('blog-title')) {
									if (get_post_format() == 'quote') {
										echo '<blockquote>'. wp_kses(get_the_title(), mfn_allowed_html()) .'</blockquote>';
									} else {
										$h = mfn_opts_get('title-heading', 1);
										//echo '<h'. esc_attr($h) .' class="entry-title" itemprop="headline">'. wp_kses(get_the_title(), mfn_allowed_html()) .'</h'. esc_attr($h) .'>';
										echo '<h'. esc_attr($h) .' class="entry-title" itemprop="headline">'. wp_kses(pmr_get_title(), mfn_allowed_html()) .'</h'. esc_attr($h) .'>';
									}
								}
							?>

							<?php
								if (get_post_format() == 'link') {
									$link = get_post_meta(get_the_ID(), 'mfn-post-link', true);
									echo '<a href="'. esc_url($link) .'" target="_blank">'. esc_url($link) .'</a>';
								}
							?>

							<?php
								$show_meta = false;
								$single_meta = mfn_opts_get('blog-meta');

								if (is_array($single_meta)) {
									if (isset($single_meta['author']) || isset($single_meta['date']) || isset($single_meta['categories'])) {
										$show_meta = true;
									}
								}
							?>

							<?php if ($show_meta): ?>
								<div class="post-meta clearfix">

									<div class="author-date">

										<?php if (isset($single_meta['author'])): ?>
											<span class="vcard author post-author" itemprop="author" itemscope itemtype="https://schema.org/Person">
												<span class="label"><?php echo esc_html($translate['published']); ?></span>
												<i class="icon-user" aria-label="<?php _e('author', 'betheme'); ?>"></i>
												<span class="fn" itemprop="name"><a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"><?php the_author_meta('display_name'); ?></a></span>
											</span>
										<?php endif; ?>

										<?php if (isset($single_meta['date'])): ?>
											<span class="date">
												<?php if (isset($single_meta['author'])): ?>
													<span class="label"><?php echo esc_html($translate['at']); ?></span>
												<?php endif; ?>
												<i class="icon-clock"></i>
												<time class="entry-date updated" datetime="<?php echo esc_attr(get_the_date('c')); ?>" itemprop="datePublished" ><?php echo esc_html(get_the_date()); ?></time>
												<meta itemprop="dateModified" content="<?php echo esc_attr(get_the_date('c')); ?>"/>
											</span>
										<?php endif; ?>

										<?php if (mfn_opts_get('mfn-seo-schema-type')): ?>

											<meta itemscope itemprop="mainEntityOfPage" itemType="https://schema.org/WebPage"/>

											<div itemprop="publisher" itemscope itemtype="https://schema.org/Organization" style="display:none;">
					    						<meta itemprop="name" content="<?php bloginfo('name'); ?>"/>

												<div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
													<img src="<?php echo esc_url(mfn_opts_get('logo-img')) ?>" itemprop="url" content="<?php echo esc_url(mfn_opts_get('logo-img')) ?>"/>
												</div>

					  						</div>

					  					<?php endif; ?>

									</div>

									<?php if (isset($single_meta['categories'])): ?>
										<div class="category meta-categories">
											<span class="cat-btn"><?php echo esc_html($translate['categories']); ?> <i class="icon-down-dir" aria-hidden="true"></i></span>
											<div class="cat-wrapper"><?php echo wp_kses_post(get_the_category_list()); ?></div>
										</div>

										<div class="category mata-tags">
											<span class="cat-btn"><?php echo esc_html($translate['tags']); ?> <i class="icon-down-dir" aria-hidden="true"></i></span>
											<div class="cat-wrapper">
												<ul>
													<?php
														if ($terms = get_the_terms(false, 'post_tag')) {
															foreach ($terms as $term) {
																$link = get_term_link($term, 'post_tag');
																echo '<li><a href="' . esc_url($link) . '">'. esc_html($term->name) .'</a></li>';
															}
														}
													?>
												</ul>
											</div>
										</div>
									<?php endif; ?>

								</div>
							<?php endif; ?>

						</div>

					</div>
				</div>

				<?php if( ! mfn_opts_get('blog-featured-image-hide') ): ?>

					<div class="column one single-photo-wrapper <?php echo mfn_post_thumbnail_type(get_the_ID()); ?>">
						<div class="mcb-column-inner">

							<?php echo mfn_share('header'); ?>

							<?php if (! post_password_required()): ?>
								<div class="image_frame scale-with-grid <?php if (! mfn_opts_get('blog-single-zoom')){ echo 'disabled'; } ?>">

									<div class="image_wrapper">
										<?php echo mfn_post_thumbnail(get_the_ID()); ?>
									</div>

									<?php
										if( $post_thumbnail_id = get_post_thumbnail_id() ){
											if ( $caption = wp_get_attachment_caption($post_thumbnail_id) ) {
												echo '<p class="wp-caption-text '. esc_attr(mfn_opts_get('featured-image-caption')) .'">'. wp_kses($caption, mfn_allowed_html('caption')) .'</p>';
											}
										}
									?>

								</div>
							<?php endif; ?>

						</div>
					</div>

				<?php endif; ?>

			</div>
		</header>

	<?php endif; ?>

	<div class="post-wrapper-content">

		<?php
			$mfn_builder = new Mfn_Builder_Front($post->ID);
			$mfn_builder->show();
		?>

		<section class="section mcb-section section-post-footer">
			<div class="section_wrapper clearfix">

				<div class="column one post-pager">
					<div class="mcb-column-inner">
						<?php
							wp_link_pages(array(
								'before' => '<div class="pager-single">',
								'after' => '</div>',
								'link_before' => '<span>',
								'link_after' => '</span>',
								'next_or_number' => 'number'
							));
						?>
					</div>
				</div>

			</div>
		</section>

		<?php if (mfn_opts_get('share')): ?>

			<?php
				$type = '';
				if (mfn_opts_get('share-style')) {
					$type = 'footer';
				} elseif (get_post_meta(get_the_ID(), 'mfn-post-template', true) == 'intro') {
					$type = 'intro';
				}
			?>

			<?php if ($type): ?>
				<section class="section section-post-intro-share">
					<div class="section_wrapper clearfix">
						<div class="column one">
							<div class="mcb-column-inner">
								<?php echo mfn_share($type); ?>
							</div>
						</div>
					</div>
				</section>
			<?php endif; ?>

		<?php endif; ?>

		<section class="section mcb-section section-post-about">
			<div class="section_wrapper clearfix">

				<?php if (mfn_opts_get('blog-author')): ?>
				<div class="column one author-box">
					<div class="mcb-column-inner">
						<div class="author-box-wrapper">
							<div class="avatar-wrapper">
								<?php echo get_avatar(get_the_author_meta('email'), '64', false, get_the_author_meta('display_name')); ?>
							</div>
							<div class="desc-wrapper">
								<h5><a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"><?php the_author_meta('display_name'); ?></a></h5>
								<div class="desc"><?php the_author_meta('description'); ?></div>
							</div>
						</div>
					</div>
				</div>
				<?php endif; ?>

			</div>
		</section>

	</div>

	<section class="section mcb-section section-post-related">
	    <div class="section_wrapper clearfix">

	        <?php

	        $custom_template = get_stylesheet_directory() . '/includes/blog-related-columns.php';

	        if (file_exists($custom_template)) {
	            include $custom_template;
	        }

	        ?>

	    </div>
	</section>

	<?php if (mfn_opts_get('blog-comments')): ?>
		<section class="section mcb-section section-post-comments">
			<div class="section_wrapper clearfix">

				<div class="column one comments">
					<div class="mcb-column-inner">
						<?php comments_template('', true); ?>
					</div>
				</div>

			</div>
		</section>
	<?php endif; ?>

</article>
