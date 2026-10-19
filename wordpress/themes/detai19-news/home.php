<?php get_header(); ?>
<main id="main" class="container main-content">
    <div class="section-heading"><div><p class="eyebrow">DÒNG CHẢY THÔNG TIN</p><h1>Tin tức mới nhất</h1></div><span class="section-note">Cập nhật từ các chuyên mục</span></div>
    <div class="content-layout"><div class="primary-content">
    <?php if ( have_posts() ) : ?>
    <?php if ( ! is_paged() ) : the_post(); ?>
        <section class="featured" aria-label="Bài viết nổi bật"><div class="feature-label">BÀI VIẾT NỔI BẬT</div><?php get_template_part( 'template-parts/card' ); ?></section>
        <h2 class="list-heading">Bản tin mới</h2>
    <?php endif; ?>
    <div class="news-grid"><?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/card' ); endwhile; ?></div>
    <?php the_posts_pagination( array( 'prev_text' => '← Trước', 'next_text' => 'Sau →' ) ); ?>
    <?php else : ?><p>Chưa có bài viết để hiển thị.</p><?php endif; ?>
    <?php if ( ! is_paged() ) : ?>
        <div class="category-sections">
        <?php foreach ( get_categories( array( 'hide_empty' => true, 'number' => 4, 'orderby' => 'count', 'order' => 'DESC' ) ) as $news_category ) :
            $category_posts = new WP_Query( array( 'post_type' => 'post', 'cat' => $news_category->term_id, 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
            if ( $category_posts->have_posts() ) : ?>
            <section class="category-section">
                <div class="category-heading"><h2 class="list-heading"><?php echo esc_html( $news_category->name ); ?></h2><a href="<?php echo esc_url( get_category_link( $news_category ) ); ?>">Xem tất cả →</a></div>
                <div class="news-grid"><?php while ( $category_posts->have_posts() ) : $category_posts->the_post(); get_template_part( 'template-parts/card' ); endwhile; ?></div>
            </section>
            <?php endif; wp_reset_postdata(); endforeach; ?>
        </div>
    <?php endif; ?>
    </div><?php get_sidebar(); ?></div>
</main>
<?php get_footer(); ?>
