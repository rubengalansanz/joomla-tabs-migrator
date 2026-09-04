<?php
/**
 * Plugin Name: Joomla Tabs Migrator
 * Description: Convierte la sintaxis de tabs/sliders de Joomla ({tab}, {slider}, etc.) a HTML nativo de WordPress.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Rubén Rafael Galán Sanz
 * Author URI: https://github.com/rubengalansanz
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

add_action( 'init', 'jtm_load_textdomain' );
function jtm_load_textdomain() {
    load_plugin_textdomain( 'joomla-tabs-migrator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function jtm_get_pattern_by_slug( $slug ) {
    $pattern = get_page_by_path(
        sanitize_title( $slug ),
        OBJECT,
        'wp_block'
    );

    if ( ! $pattern || 'publish' !== $pattern->post_status ) {
        return null;
    }

    return $pattern;
}

function jtm_render_pattern( $slug ) {
    $pattern = jtm_get_pattern_by_slug( $slug );

    if ( ! $pattern ) {
        return '';
    }

    return do_blocks( $pattern->post_content );
}

function jtm_render_pattern_with_joomla( $slug ) {
    $pattern = jtm_get_pattern_by_slug( $slug );

    if ( ! $pattern ) {
        return '';
    }

    // Renderiza primero los bloques nativos de WordPress.
    $content = do_blocks( $pattern->post_content );

    return jtm_render_joomla_tags( $content );
}

/**
 * Núcleo de conversión, reutilizado por the_content y por el shortcode.
 * Incluye guard anti-doble-parse y anti-recursión.
 *
 * @param string $content Contenido ya con bloques renderizados.
 * @return string
 */
function jtm_render_joomla_tags( $content ) {
    static $rendering = false;

    if ( $rendering ) {
        return $content;
    }

    // Sin sintaxis Joomla no hay nada que hacer (también evita re-parsear HTML ya generado).
    if ( ! jtm_has_joomla_tags( $content ) ) {
        return $content;
    }

    $rendering = true;

    $tree = JTM_Parser::parse( $content );
    $out  = JTM_Renderer::render( $tree );

    $rendering = false;

    // Fallback: si el pre-chequeo de wp_enqueue_scripts falló (archivos, AJAX,
    // patrón vía shortcode), asegura los assets al detectar salida ya renderizada.
    if ( false !== strpos( $out, 'jtm-tabs-' ) || false !== strpos( $out, 'jtm-sliders-' ) ) {
        jtm_maybe_enqueue_assets();
    }

    return $out;
}

/**
 * Handler del shortcode [jtm_pattern slug="mi-patron"].
 * Acepta slug posicional: [jtm_pattern "mi-patron"].
 */
function jtm_pattern_shortcode( $atts ) {
    $atts = shortcode_atts(
        array( 'slug' => '' ),
        $atts,
        'jtm_pattern'
    );

    // Soporte posicional [jtm_pattern "slug"].
    if ( empty( $atts['slug'] ) && isset( $atts[0] ) ) {
        $atts['slug'] = $atts[0];
    }

    $slug = sanitize_title( $atts['slug'] );

    if ( '' === $slug ) {
        return '';
    }

    return jtm_render_pattern_with_joomla( $slug );
}

if ( function_exists( 'add_shortcode' ) ) {
    add_shortcode( 'jtm_pattern', 'jtm_pattern_shortcode' );
}

function jtm_enqueue_asset_files() {
    wp_enqueue_style( 'jtm-tabs', JTM_URL . 'assets/css/tabs.css', array(), JTM_VERSION );
    wp_enqueue_script( 'jtm-tabs', JTM_URL . 'assets/js/tabs.js', array(), JTM_VERSION, true );
}

/**
 * Enqueue diferido: seguro de llamar desde the_content/shortcode aunque
 * wp_enqueue_scripts ya haya corrido (el script va en footer y el CSS en body es válido en HTML5).
 */
function jtm_maybe_enqueue_assets() {
    if ( function_exists( 'wp_style_is' ) && wp_style_is( 'jtm-tabs', 'enqueued' ) ) {
        return;
    }

    if ( function_exists( 'did_action' ) && did_action( 'wp_enqueue_scripts' ) ) {
        jtm_enqueue_asset_files();
        return;
    }

    jtm_enqueue_asset_files();
}

add_action( 'wp_enqueue_scripts', 'jtm_enqueue_assets' );
function jtm_enqueue_assets() {
    // Pre-chequeo barato: solo carga si algún contenido visible usa etiquetas Joomla.
    if ( jtm_current_request_needs_assets() ) {
        jtm_enqueue_asset_files();
    }
}

/**
 * Comprueba si la request actual necesita assets, cubriendo singular y archivos/home.
 *
 * @return bool
 */
function jtm_current_request_needs_assets() {
    global $post, $wp_query;

    // Caso singular: basta con el $post actual.
    if ( function_exists( 'is_singular' ) && is_singular() ) {
        return isset( $post ) && jtm_has_joomla_tags( $post->post_content );
    }

    // Archivos/home/búsqueda: revisa los posts del loop principal.
    if ( isset( $wp_query ) && ! empty( $wp_query->posts ) && is_array( $wp_query->posts ) ) {
        foreach ( $wp_query->posts as $queried ) {
            if ( isset( $queried->post_content ) && jtm_has_joomla_tags( $queried->post_content ) ) {
                return true;
            }
        }
    }

    // Último recurso: $post global (p. ej. fuera del loop en algunos temas).
    return isset( $post->post_content ) && jtm_has_joomla_tags( $post->post_content );
}

/**
 * Detecta sintaxis Joomla o el shortcode [jtm_pattern] en un contenido dado.
 * Si se omite $content, revisa el $post global (compat con el helper anterior).
 *
 * @param string|null $content Contenido a inspeccionar. Null = $post global.
 * @return bool
 */
function jtm_has_joomla_tags( $content = null ) {
    if ( null === $content ) {
        global $post;

        if ( ! isset( $post->post_content ) ) {
            return false;
        }

        $content = $post->post_content;
    }

    if ( ! is_string( $content ) || '' === $content ) {
        return false;
    }

    if ( preg_match( '/\{(tabs|sliders|tab|slider)\b/i', $content ) ) {
        return true;
    }

    if ( function_exists( 'has_shortcode' ) && has_shortcode( $content, 'jtm_pattern' ) ) {
        return true;
    }

    return false;
}

add_filter( 'the_content', 'jtm_render_content', 20 );
function jtm_render_content( $content ) {
    return jtm_render_joomla_tags( $content );
}