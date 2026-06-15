<?php
/**
 * Plugin Name: GH Repo Gallery
 * Description: Displays a filterable, sortable gallery of GitHub repositories with selectable grid/list views and themes.
 * Version: 2.0.0
 * Author: David Kranz
 * Text Domain: gh-repo-gallery
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'GHRG_VERSION', '2.0.0' );
define( 'GHRG_PATH', plugin_dir_path( __FILE__ ) );
define( 'GHRG_URL', plugin_dir_url( __FILE__ ) );
require_once GHRG_PATH . 'includes/class-ghrg-settings.php';
require_once GHRG_PATH . 'includes/class-ghrg-api.php';
require_once GHRG_PATH . 'includes/class-ghrg-shortcode.php';
function ghrg_init() {
	new GHRG_Settings();
	new GHRG_Shortcode();
}
add_action( 'plugins_loaded', 'ghrg_init' );
function ghrg_enqueue_assets() {
	wp_register_style( 'ghrg-style', GHRG_URL . 'assets/css/gh-repo-gallery.css', array(), GHRG_VERSION );
	wp_register_script( 'ghrg-script', GHRG_URL . 'assets/js/gh-repo-gallery.js', array(), GHRG_VERSION, true );
	wp_register_style( 'ghrg-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=EB+Garamond:ital@0;1&family=JetBrains+Mono:wght@400;500&family=Inter:wght@400;500;600&display=swap', array(), null );
	wp_enqueue_style( 'ghrg-fonts' );
}
add_action( 'wp_enqueue_scripts', 'ghrg_enqueue_assets' );