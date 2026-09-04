<?php
/**
 * Tests del renderer (JTM_Renderer::render).
 */

function jtm_render_string( $content ) {
    return JTM_Renderer::render( JTM_Parser::parse( $content ) );
}

jtm_add_test( 'renderer: tabgroup con nav accesible y primer tab activo', function () {
    $html = jtm_render_string( '{tabs}{tab A}Uno{tab B}Dos{/tabs}' );
    jtm_assert_contains( 'class="jtm-tabs"', $html );
    jtm_assert_contains( 'role="tablist"', $html );
    jtm_assert_contains( 'role="tabpanel"', $html );
    jtm_assert_contains( 'aria-selected="true"', $html );
    jtm_assert_contains( 'aria-selected="false"', $html );
    jtm_assert_contains( ' hidden', $html );
} );

jtm_add_test( 'renderer: sliders open/closed', function () {
    $html = jtm_render_string( '{sliders}{slider A|open}X{slider B}Y{/sliders}' );
    jtm_assert_contains( 'aria-expanded="true"', $html );
    jtm_assert_contains( 'aria-expanded="false"', $html );
    jtm_assert_contains( 'role="region"', $html );
} );

jtm_add_test( 'renderer: negrita en título y en cuerpo', function () {
    $html = jtm_render_string( '{tabs}{tab T con **N**}Cuerpo con **fuerte**{/tabs}' );
    jtm_assert_contains( '<strong>N</strong>', $html );
    jtm_assert_contains( '<strong>fuerte</strong>', $html );
} );

jtm_add_test( 'renderer: enlace markdown con texto escapado', function () {
    $html = jtm_render_string( '{tabs}{tab T}Un [<b>roto</b>](https://example.com/) aquí{/tabs}' );
    jtm_assert_contains( '<a href="https://example.com/">&lt;b&gt;roto&lt;/b&gt;</a>', $html );
    jtm_assert_not_contains( '<b>roto</b>', $html );
} );

jtm_add_test( 'renderer: enlace normal e imagen', function () {
    $html = jtm_render_string( '{tabs}{tab T}Un [normal](https://example.com/) y ![alt](https://example.com/i.png){/tabs}' );
    jtm_assert_contains( '<a href="https://example.com/">normal</a>', $html );
    jtm_assert_contains( '<img src="https://example.com/i.png" alt="alt" />', $html );
} );

jtm_add_test( 'renderer: imagen enlazada', function () {
    $html = jtm_render_string( '{tabs}{tab T}Ver[![pdf](https://example.com/i.png)](https://example.com/d.pdf){/tabs}' );
    jtm_assert_contains( '<a href="https://example.com/d.pdf"><img src="https://example.com/i.png" alt="pdf" /></a>', $html );
} );

jtm_add_test( 'renderer: negrita dentro del texto de un enlace', function () {
    $html = jtm_render_string( '{tabs}{tab T}Un [**fuerte**](https://example.com/){/tabs}' );
    jtm_assert_contains( '<a href="https://example.com/"><strong>fuerte</strong></a>', $html );
} );

jtm_add_test( 'renderer: color hex con estilo inline', function () {
    $html = jtm_render_string( '{tabs}{tab T|#ff0000}X{/tabs}' );
    jtm_assert_contains( 'style="border-top-color:#ff0000"', $html );
    jtm_assert_contains( 'jtm-color-custom', $html );
    jtm_assert_not_contains( 'jtm-color-#', $html );
} );

jtm_add_test( 'renderer: color nombrado con clase', function () {
    $html = jtm_render_string( '{tabs}{tab T|green}X{/tabs}' );
    jtm_assert_contains( 'jtm-color-green', $html );
    jtm_assert_not_contains( 'border-top-color', $html );
} );

jtm_add_test( 'renderer: alias explícito en IDs y data-alias', function () {
    $html = jtm_render_string( '{tabs}{tab Primero|green|mi-alias}Cuerpo{/tabs}' );
    jtm_assert_contains( 'mi-alias', $html );
    jtm_assert_contains( 'data-alias="mi-alias"', $html );
} );

jtm_add_test( 'renderer: alias duplicados se deduplican', function () {
    $html = jtm_render_string( '{tabs}{tab A|green|dup}X{tab B|blue|dup}Y{/tabs}' );
    jtm_assert_contains( '-dup"', $html );
    jtm_assert_contains( '-dup-2"', $html );
} );

jtm_add_test( 'renderer: sin alias explícito usa el slug del título', function () {
    $html = jtm_render_string( '{tabs}{tab Mi Titulo}X{/tabs}' );
    jtm_assert_contains( '-mi-titulo"', $html );
} );

jtm_add_test( 'renderer: no duplica wpautop con bloques existentes', function () {
    $GLOBALS['jtm_test_autop_calls'] = 0;
    $html = jtm_render_string( '{tabs}{tab T}<p>Ya formateado</p>{/tabs}' );
    jtm_assert_contains( '<p>Ya formateado</p>', $html );
    jtm_assert_not_contains( '<p><p>', str_replace( array( "\n", ' ' ), '', $html ) );
    jtm_assert_same( 0, $GLOBALS['jtm_test_autop_calls'] );
} );

jtm_add_test( 'renderer: texto plano sí recibe wpautop', function () {
    $GLOBALS['jtm_test_autop_calls'] = 0;
    $html = jtm_render_string( '{tabs}{tab T}Plano sin formato{/tabs}' );
    jtm_assert_true( $GLOBALS['jtm_test_autop_calls'] > 0, 'wpautop debería aplicarse al texto plano.' );
    jtm_assert_contains( '<p>', $html );
} );

jtm_add_test( 'renderer: contenido de test-1.php sin tags residuales', function () {
    $file = file_get_contents( __DIR__ . '/test-1.php' );
    preg_match( "/\\\$content = <<<'JOOMLA'\\s(.*?)\\sJOOMLA;/s", $file, $m );
    $html = jtm_render_string( trim( $m[1] ) );
    jtm_assert_contains( 'class="jtm-tabs"', $html );
    jtm_assert_false( (bool) preg_match( '/\{(tabs|sliders|tab|slider)\b/i', $html ), 'No deben quedar etiquetas Joomla.' );
    jtm_assert_contains( '<strong>', $html );
    jtm_assert_contains( 'data-alias=', $html );
} );
