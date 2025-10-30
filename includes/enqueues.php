<?php
$vite_assets_loader = new Uup\ViteAssetsLoader(
	APP_THEME_DIR . 'dist/manifest.json',
	APP_THEME_URL . '/dist/manifest.json'
);

$vite_assets_loader->enqueue_script( 'theme-js-bundle', 'resources/js/main.js' );
$vite_assets_loader->enqueue_editor_script( 'editor-js-bundle', 'resources/js/editor.js' );
$vite_assets_loader->enqueue_style( 'theme-css-bundle', 'resources/scss/theme.scss');
$vite_assets_loader->enqueue_editor_style( 'block-editor-bundle', 'resources/scss/block-editor.scss' );

// Enqueue JS and CSS assets on the front-end
add_action( 'wp_enqueue_scripts', 'app_enqueue_assets' );
function app_enqueue_assets() {
	# The theme style.css file may contain overrides for the bundled styles
	app_enqueue_style( 'theme-styles', APP_THEME_URL . '/style.css' );

	# Enqueue Comments JS file
	if ( is_singular() ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

# Enqueue JS and CSS assets on admin pages
add_action( 'admin_enqueue_scripts', 'app_admin_enqueue_scripts' );
function app_admin_enqueue_scripts() {
	# Enqueue Scripts
	# @app_enqueue_script attributes -- id, location, dependencies, in_footer = false
	# app_enqueue_script( 'theme-admin-functions', APP_THEME_URL . '/js/admin-functions.js', [ 'jquery' ] );

	# Enqueue Styles
	# @app_enqueue_style attributes -- id, location, dependencies, media = all
	# app_enqueue_style( 'theme-admin-styles', APP_THEME_URL . '/css/admin-style.css' );
}

add_action( 'login_enqueue_scripts', 'app_login_enqueue' );
function app_login_enqueue() {
	# app_enqueue_style( 'theme-login-styles', APP_THEME_URL . '/css/login-style.css' );
}

wp_enqueue_script( 'jquery' );
