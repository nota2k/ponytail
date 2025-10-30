<?php
// Load composer dependencies
require_once __DIR__ . '/vendor/autoload.php';

define('APP_THEME_DIR', __DIR__ . '/');
define('APP_THEME_URL', get_stylesheet_directory_uri());

// Translations and native theme features
require_once APP_THEME_DIR . 'includes/theme-support.php';

// Register options and load additional functionality
add_action('init', 'app_init', 0);

function app_init() {
    require_once APP_THEME_DIR . 'includes/enqueues.php';
    require_once APP_THEME_DIR . 'includes/blocks.php';
    require_once APP_THEME_DIR . 'includes/sections.php';
    
    require_once APP_THEME_DIR . 'options/shortcodes.php';
    require_once APP_THEME_DIR . 'options/taxonomies.php';
    require_once APP_THEME_DIR . 'options/post-types.php';
    require_once APP_THEME_DIR . 'options/fields.php';
}