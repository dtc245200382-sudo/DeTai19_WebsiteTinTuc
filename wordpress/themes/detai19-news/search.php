<?php get_header(); ?>
<main id="main" class="container main-content">
    <p class="eyebrow">TÌM KIẾM BÀI VIẾT</p>
    <h1>Kết quả tìm kiếm: <?php echo esc_html( get_search_query( false ) ); ?></h1>
    <div class="results-search"><?php get_search_form(); ?></div>
    <div class="content-layout"><div class="primary-content">
    <?php if ( have_posts() ) : ?>
        <p class="result-count"><?php global $wp_query; echo esc_html( number_format_i18n( $wp_query->found_posts ) ); ?> bài viết phù hợp</p>
        <div class="news-grid"><?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/card' ); endwhile; ?></div>
        <?php the_posts_pagination( array( 'prev_text' => '← Trước', 'next_text' => 'Sau →' ) ); ?>
    <?php else : ?>
        <div class="empty-state"><h2>Không tìm thấy bài viết</h2><p>Hãy thử từ khóa khác hoặc khám phá các danh mục bên dưới.</p><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Về trang chủ</a></div>
    <?php endif; ?>
    </div><?php get_sidebar(); ?></div>
</main>
<?php get_footer(); ?>
