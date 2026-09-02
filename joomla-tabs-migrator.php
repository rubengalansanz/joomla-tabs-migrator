<?php
/**
 * Plugin Name: Joomla Tabs Migrator
 * Description: Convierte la sintaxis de tabs/sliders de Joomla ({tab}, {slider}, etc.) a HTML nativo de WordPress.
 * Version: 1.0.0
 * Author: Rubén Rafael Galán Sanz
 * Author URI: https://github.com/rubengalansanz
 * Text Domain: joomla-tabs-migrator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'JTM_PATH', plugin_dir_path( __FILE__ ) );
define( 'JTM_URL', plugin_dir_url( __FILE__ ) );
define( 'JTM_VERSION', '1.0.0' );

require_once JTM_PATH . 'includes/parser.php';
require_once JTM_PATH . 'includes/renderer.php';

add_action( 'wp_enqueue_scripts', 'jtm_enqueue_assets' );
function jtm_enqueue_assets() {
    // Solo carga si el contenido del post actual usa etiquetas Joomla, evita peso innecesario.
    if ( is_singular() && has_shortcode_like_tags() ) {
        wp_enqueue_style( 'jtm-tabs', JTM_URL . 'assets/css/tabs.css', array(), JTM_VERSION );
        wp_enqueue_script( 'jtm-tabs', JTM_URL . 'assets/js/tabs.js', array(), JTM_VERSION, true );
    }
}

function has_shortcode_like_tags() {
    global $post;
    if ( ! $post ) {
        return false;
    }
    return (bool) preg_match( '/\{(tabs|sliders|tab|slider)\b/i', $post->post_content );
}

add_filter( 'the_content', 'jtm_render_content', 20 );
function jtm_render_content( $content ) {
    if ( ! preg_match( '/\{(tabs|sliders|tab|slider)\b/i', $content ) ) {
        return $content;
    }
    $tree = JTM_Parser::parse( $content );
    return JTM_Renderer::render( $tree );
}