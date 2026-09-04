<?php
/**
 * Tests del bootstrap del plugin (fase P1).
 */

require_once __DIR__ . '/../joomla-tabs-migrator.php';

function jtm_test_reset_request() {
    $GLOBALS['jtm_test_is_singular'] = false;
    $GLOBALS['post']                 = null;
    $GLOBALS['wp_query']             = null;
    $GLOBALS['jtm_test_enqueued']    = array();
    $GLOBALS['jtm_test_page']        = null;
    unset( $GLOBALS['post'], $GLOBALS['wp_query'] );
}

jtm_add_test( 'bootstrap: shortcode jtm_pattern registrado', function () {
    jtm_assert_true( isset( $GLOBALS['jtm_test_shortcodes']['jtm_pattern'] ), 'add_shortcode( jtm_pattern ) debe llamarse.' );
} );

jtm_add_test( 'bootstrap: jtm_has_joomla_tags detecta sintaxis y shortcode', function () {
    jtm_assert_true( jtm_has_joomla_tags( '{tab Título}' ) );
    jtm_assert_true( jtm_has_joomla_tags( '{tabs}x{/tabs}' ) );
    jtm_assert_true( jtm_has_joomla_tags( '[jtm_pattern slug="a"]' ) );
    jtm_assert_false( jtm_has_joomla_tags( 'Hola mundo' ) );
    jtm_assert_false( jtm_has_joomla_tags( '' ) );
} );

jtm_add_test( 'bootstrap: jtm_has_joomla_tags sin contenido usa $post global', function () {
    jtm_test_reset_request();
    jtm_assert_false( jtm_has_joomla_tags( null ) );
    $GLOBALS['post'] = (object) array( 'post_content' => '{tab X}y' );
    jtm_assert_true( jtm_has_joomla_tags( null ) );
    jtm_test_reset_request();
} );

jtm_add_test( 'bootstrap: render idempotente con enqueue de reserva', function () {
    jtm_test_reset_request();
    $content = '{tabs}{tab A}Hola{/tabs}';
    $once    = jtm_render_joomla_tags( $content );
    $twice   = jtm_render_joomla_tags( $once );
    jtm_assert_same( 1, substr_count( $once, 'class="jtm-tabs"' ) );
    jtm_assert_same( $once, $twice, 'El segundo pase no debe modificar el HTML.' );
    jtm_assert_true( ! empty( $GLOBALS['jtm_test_enqueued']['jtm-tabs'] ), 'El render debe asegurar los assets.' );
    jtm_test_reset_request();
} );

jtm_add_test( 'bootstrap: assets en singular', function () {
    jtm_test_reset_request();
    $GLOBALS['jtm_test_is_singular'] = true;
    $GLOBALS['post']                 = (object) array( 'post_content' => '{tab X}y' );
    jtm_assert_true( jtm_current_request_needs_assets() );
    $GLOBALS['post'] = (object) array( 'post_content' => 'nada' );
    jtm_assert_false( jtm_current_request_needs_assets() );
    jtm_test_reset_request();
} );

jtm_add_test( 'bootstrap: assets en archivo/home', function () {
    jtm_test_reset_request();
    $GLOBALS['jtm_test_is_singular'] = false;
    $GLOBALS['wp_query']             = (object) array(
        'posts' => array(
            (object) array( 'post_content' => 'nada' ),
            (object) array( 'post_content' => '{slider T}z' ),
        ),
    );
    jtm_assert_true( jtm_current_request_needs_assets() );
    $GLOBALS['wp_query'] = (object) array(
        'posts' => array( (object) array( 'post_content' => 'nada' ) ),
    );
    $GLOBALS['post']     = (object) array( 'post_content' => 'nada' );
    jtm_assert_false( jtm_current_request_needs_assets() );
    jtm_test_reset_request();
} );

jtm_add_test( 'bootstrap: shortcode renderiza el patrón con Joomla', function () {
    jtm_test_reset_request();
    $GLOBALS['jtm_test_page'] = (object) array(
        'post_status'  => 'publish',
        'post_content' => '{tabs}{tab A}X{/tabs}',
    );
    $cb  = $GLOBALS['jtm_test_shortcodes']['jtm_pattern'];
    $out = $cb( array( 'slug' => 'mi-patron' ) );
    jtm_assert_contains( 'class="jtm-tabs"', $out );
    // Soporte posicional [jtm_pattern "slug"].
    $out = $cb( array( 'mi-patron' ) );
    jtm_assert_contains( 'class="jtm-tabs"', $out );
    // Slug vacío y patrón no publicado.
    jtm_assert_same( '', $cb( array( 'slug' => '' ) ) );
    $GLOBALS['jtm_test_page'] = (object) array( 'post_status' => 'draft', 'post_content' => 'x' );
    jtm_assert_same( '', $cb( array( 'slug' => 'mi-patron' ) ) );
    jtm_test_reset_request();
} );
