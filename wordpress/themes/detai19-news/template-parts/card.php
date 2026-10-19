<article <?php post_class( 'news-card' ); ?>>
    <a class="card-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
    <?php if ( has_post_thumbnail() ) : ?>
        <?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
    <?php else : ?><span class="image-placeholder">TIN TỨC<span>Cập nhật &amp; chia sẻ</span></span><?php endif; ?>
    </a>
    <div class="card-body">
        <div class="meta"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></div>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p>
        <a class="read-more" href="<?php the_permalink(); ?>">Đọc bài viết <span aria-hidden="true">→</span><span class="screen-reader-text">: <?php the_title(); ?></span></a>
    </div>
</article>
