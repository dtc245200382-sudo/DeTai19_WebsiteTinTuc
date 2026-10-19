<?php get_header(); ?>
<main id="main" class="container main-content">
    <p class="eyebrow">CHUYÊN MỤC</p><h1><?php single_cat_title(); ?></h1>
    <?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
    <?php if ( have_posts() ) : ?>
    <div class="news-grid"><?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/card' ); endwhile; ?></div>
    <?php the_posts_pagination( array( 'prev_text' => '← Trước', 'next_text' => 'Sau →' ) ); ?>
    <?php else : ?><p>Chuyên mục này chưa có bài viết.</p><?php endif; ?>
</main>
<?php get_footer(); ?>
