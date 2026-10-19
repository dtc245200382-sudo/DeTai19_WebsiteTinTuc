<aside class="news-sidebar" aria-label="Khám phá tin tức">
    <section class="sidebar-panel">
        <h2>Bài viết mới nhất</h2>
        <?php $latest = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 5, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) ); ?>
        <?php if ( $latest->have_posts() ) : ?><ul class="latest-posts">
        <?php while ( $latest->have_posts() ) : $latest->the_post(); ?>
            <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></li>
        <?php endwhile; ?></ul><?php else : ?><p>Chưa có bài viết.</p><?php endif; wp_reset_postdata(); ?>
    </section>
    <section class="sidebar-panel">
        <h2>Danh mục tin tức</h2>
        <ul class="sidebar-categories"><?php wp_list_categories( array( 'title_li' => '', 'show_count' => true, 'hide_empty' => false ) ); ?></ul>
    </section>
</aside>
