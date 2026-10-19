<?php
defined( 'ABSPATH' ) || exit;
add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
} );
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'detai19-news', get_stylesheet_uri(), array(), (string) filemtime( get_stylesheet_directory() . '/style.css' ) );
    if ( is_singular( 'post' ) ) {
        wp_enqueue_script( 'detai19-share', get_theme_file_uri( '/assets/share.js' ), array(), (string) filemtime( get_theme_file_path( '/assets/share.js' ) ), true );
    }
} );

// Keep frontend search focused on published posts; leave admin queries untouched.
add_action( 'pre_get_posts', function ( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
        $query->set( 'post_type', 'post' );
    }
} );
