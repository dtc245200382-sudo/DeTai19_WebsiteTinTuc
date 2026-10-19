<?php get_header(); ?>
<main id="main" class="container main-content">
    <div class="section-heading"><div><p class="eyebrow">DÒNG CHẢY THÔNG TIN</p><h1>Tin tức mới nhất</h1></div><span class="section-note">Cập nhật từ các chuyên mục</span></div>
    <?php if ( have_posts() ) : ?>
    <?php if ( ! is_paged() ) : the_post(); ?>
        <section class="featured" aria-label="Bài viết nổi bật"><div class="feature-label">BÀI VIẾT NỔI BẬT</div><?php get_template_part( 'template-parts/card' ); ?></section>
        <h2 class="list-heading">Bản tin mới</h2>
    <?php endif; ?>
    <div class="news-grid"><?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/card' ); endwhile; ?></div>
    <?php the_posts_pagination( array( 'prev_text' => '← Trước', 'next_text' => 'Sau →' ) ); ?>
    <?php else : ?><p>Chưa có bài viết để hiển thị.</p><?php endif; ?>
</main>
<?php get_footer(); ?>
