<?php

add_action( 'after_setup_theme', 'app_setup_theme' );

function app_setup_theme() {
	// Load translations
	load_theme_textdomain( 'app', APP_THEME_DIR . 'languages' );

	// Various theme support options
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'menus' );
	add_theme_support( 'html5', [ 'gallery' ] );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	

}
