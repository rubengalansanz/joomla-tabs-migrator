<?php
/**
 * Bootstrap de la suite de tests: stubs mínimos de WordPress para probar
 * el plugin (parser, renderer y bootstrap) con PHP puro, sin WordPress,
 * sin Composer y sin PHPUnit.
 *
 * Uso: php tests/run-tests.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

// Constantes del núcleo de WordPress usadas por get_page_by_path().
if ( ! defined( 'OBJECT' ) ) {
    define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
    define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
    define( 'ARRAY_N', 'ARRAY_N' );
}

$GLOBALS['jtm_test_shortcodes'] = array();
$GLOBALS['jtm_test_enqueued']   = array();
$GLOBALS['jtm_test_page']       = null;

function plugin_dir_path( $file ) {
    return rtrim( dirname( $file ), '/\\' ) . '/';
}

function plugin_dir_url( $file ) {
    return 'http://example.com/wp-content/plugins/joomla-tabs-migrator/';
}

function plugin_basename( $file ) {
    return 'joomla-tabs-migrator/' . basename( $file );
}

function load_plugin_textdomain( $domain, $deprecated = false, $path = '' ) {
    return true;
}

function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
    return true;
}

function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
    return true;
}

function add_shortcode( $tag, $callback ) {
    $GLOBALS['jtm_test_shortcodes'][ $tag ] = $callback;
}

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
    $atts = (array) $atts;
    $out  = array();
    foreach ( $pairs as $name => $default ) {
        $out[ $name ] = isset( $atts[ $name ] ) ? $atts[ $name ] : $default;
    }
    if ( isset( $atts[0] ) ) {
        $out[0] = $atts[0];
    }
    return $out;
}

function has_shortcode( $content, $tag ) {
    return is_string( $content ) && false !== strpos( $content, '[' . $tag );
}

function get_page_by_path( $slug, $output = OBJECT, $post_type = '' ) {
    return $GLOBALS['jtm_test_page'];
}

function do_blocks( $content ) {
    return $content;
}

function is_singular() {
    return ! empty( $GLOBALS['jtm_test_is_singular'] );
}

function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
    $GLOBALS['jtm_test_enqueued'][ $handle ] = true;
}

function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
    $GLOBALS['jtm_test_enqueued'][ $handle ] = true;
}

function wp_style_is( $handle, $list = 'enqueued' ) {
    return ! empty( $GLOBALS['jtm_test_enqueued'][ $handle ] );
}

function did_action( $hook ) {
    return 1;
}

function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
    return htmlspecialchars( filter_var( (string) $url, FILTER_SANITIZE_URL ), ENT_QUOTES, 'UTF-8' );
}

function sanitize_title( $text ) {
    $text = strtolower( trim( (string) $text ) );
    $text = preg_replace( '/[^a-z0-9]+/', '-', $text );
    return trim( $text, '-' );
}

function wpautop( $text ) {
    $GLOBALS['jtm_test_autop_calls'] = isset( $GLOBALS['jtm_test_autop_calls'] ) ? $GLOBALS['jtm_test_autop_calls'] + 1 : 1;
    $paragraphs = preg_split( '/\n\s*\n/', trim( (string) $text ) );
    $html       = array_map( function ( $p ) {
        return '<p>' . nl2br( trim( $p ) ) . '</p>';
    }, array_filter( $paragraphs, 'strlen' ) );
    return implode( "\n", $html );
}

require_once __DIR__ . '/../includes/parser.php';
require_once __DIR__ . '/../includes/renderer.php';
