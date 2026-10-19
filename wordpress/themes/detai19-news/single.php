<?php get_header(); ?>
<main id="main" class="container main-content">
<?php while ( have_posts() ) : the_post(); ?>
    <article <?php post_class( 'single-article' ); ?>>
        <a class="back-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">← Trang chủ</a>
        <div class="article-categories"><?php the_category( ' · ' ); ?></div>
        <h1><?php the_title(); ?></h1>
        <div class="meta">Bởi <?php the_author(); ?> · <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></div>
        <?php if ( has_post_thumbnail() && ! post_password_required() ) : ?><div class="article-cover"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
        <div class="entry-content"><?php the_content(); wp_link_pages(); ?></div>
    </article>
    <?php if ( ! post_password_required() ) :
        $related = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 3, 'post__not_in' => array( get_the_ID() ), 'category__in' => wp_get_post_categories( get_the_ID() ), 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
        if ( $related->have_posts() ) : ?>
        <section class="related"><h2 class="list-heading">Tin cùng chuyên mục</h2><div class="news-grid">
        <?php while ( $related->have_posts() ) : $related->the_post(); get_template_part( 'template-parts/card' ); endwhile; ?>
        </div></section>
        <?php endif; wp_reset_postdata(); endif; ?>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
