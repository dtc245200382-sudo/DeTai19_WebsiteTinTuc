<?php get_header(); ?>
<main id="main" class="container main-content">
    <h1><?php echo is_search() ? esc_html( 'Kết quả tìm kiếm: ' . get_search_query() ) : esc_html( get_bloginfo( 'name' ) ); ?></h1>
    <?php if ( have_posts() ) : ?>
    <div class="news-grid"><?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/card' ); endwhile; ?></div>
    <?php the_posts_pagination(); ?>
    <?php else : ?><p>Không tìm thấy nội dung.</p><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Về trang chủ</a><?php endif; ?>
</main>
<?php get_footer(); ?>
