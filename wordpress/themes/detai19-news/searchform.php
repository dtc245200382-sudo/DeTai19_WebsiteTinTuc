<?php $search_id = wp_unique_id( 'news-search-' ); ?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <label class="screen-reader-text" for="<?php echo esc_attr( $search_id ); ?>">Từ khóa tìm kiếm bài viết</label>
    <input id="<?php echo esc_attr( $search_id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query( false ) ); ?>" placeholder="Tìm kiếm bài viết…" required>
    <input type="hidden" name="post_type" value="post">
    <button type="submit">Tìm kiếm</button>
</form>
