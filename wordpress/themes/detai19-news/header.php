<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Đến nội dung chính</a>
<header class="site-header">
    <div class="container masthead">
        <a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?><span>TIN TỨC &amp; CỔNG THÔNG TIN</span></a>
        <div class="header-search"><?php get_search_form(); ?></div>
    </div>
    <nav class="navigation" aria-label="Danh mục tin tức"><div class="container"><ul>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a></li>
        <?php wp_list_categories( array( 'title_li' => '', 'depth' => 1, 'hide_empty' => true ) ); ?>
    </ul></div></nav>
</header>
