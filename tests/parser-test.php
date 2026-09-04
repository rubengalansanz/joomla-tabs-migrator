<?php
/**
 * Tests del parser (JTM_Parser::parse).
 */

jtm_add_test( 'parser: tabs básico con dos pestañas', function () {
    $nodes = JTM_Parser::parse( '{tabs}{tab Primera}Uno{tab Segunda}Dos{/tabs}' );
    jtm_assert_count( 1, $nodes );
    jtm_assert_same( 'tabgroup', $nodes[0]['type'] );
    jtm_assert_count( 2, $nodes[0]['items'] );
    jtm_assert_same( 'Primera', $nodes[0]['items'][0]['title'] );
    jtm_assert_same( 'Segunda', $nodes[0]['items'][1]['title'] );
} );

jtm_add_test( 'parser: grupo sin contenedor de apertura', function () {
    $nodes = JTM_Parser::parse( '{tab A}X{tab B}Y{/tabs}' );
    jtm_assert_count( 1, $nodes );
    jtm_assert_same( 'tabgroup', $nodes[0]['type'] );
    jtm_assert_count( 2, $nodes[0]['items'] );
} );

jtm_add_test( 'parser: {tab} vacío aceptado', function () {
    $nodes = JTM_Parser::parse( '{tabs}{tab}Vacío{/tabs}' );
    jtm_assert_same( 'tabgroup', $nodes[0]['type'] );
    jtm_assert_same( '', $nodes[0]['items'][0]['title'] );
} );

jtm_add_test( 'parser: tabulación como separador', function () {
    $nodes = JTM_Parser::parse( "{tabs}{tab\tConTab}X{/tabs}" );
    jtm_assert_same( 'ConTab', $nodes[0]['items'][0]['title'] );
} );

jtm_add_test( 'parser: contenedor con parámetros', function () {
    $nodes = JTM_Parser::parse( '{tabs alias=test}{tab A}X{/tabs}' );
    jtm_assert_same( 'tabgroup', $nodes[0]['type'] );
    jtm_assert_same( 'A', $nodes[0]['items'][0]['title'] );
} );

jtm_add_test( 'parser: sliders open/close y cerrado por defecto', function () {
    $nodes = JTM_Parser::parse( '{sliders}{slider A|open}X{slider B|close}Y{slider C}Z{/sliders}' );
    jtm_assert_same( 'slidergroup', $nodes[0]['type'] );
    jtm_assert_same( 'open', $nodes[0]['items'][0]['state'] );
    jtm_assert_same( 'closed', $nodes[0]['items'][1]['state'] );
    jtm_assert_same( 'closed', $nodes[0]['items'][2]['state'] );
} );

jtm_add_test( 'parser: slider anidado dentro de tab', function () {
    $nodes = JTM_Parser::parse( '{tabs}{tab T}Texto{sliders}{slider S}Cuerpo{/sliders}{/tabs}' );
    $found = false;
    foreach ( $nodes[0]['items'][0]['body'] as $child ) {
        if ( 'slidergroup' === $child['type'] ) {
            $found = true;
        }
    }
    jtm_assert_true( $found, 'El slider anidado debe parsearse por recursión.' );
} );

jtm_add_test( 'parser: tabs anidados no cierran el grupo exterior', function () {
    $nodes = JTM_Parser::parse( '{tabs}{tab A}fuera {tabs}{tab X}dentro{/tabs} más{/tab}{tab B}segundo{/tabs}' );
    jtm_assert_same( 'tabgroup', $nodes[0]['type'] );
    jtm_assert_count( 2, $nodes[0]['items'], 'El {/tabs} interior no debe cerrar el grupo exterior.' );
    $found = false;
    foreach ( $nodes[0]['items'][0]['body'] as $child ) {
        if ( 'tabgroup' === $child['type'] ) {
            $found = true;
        }
    }
    jtm_assert_true( $found, 'El grupo interior debe quedar en el body del primer tab.' );
} );

jtm_add_test( 'parser: colores nombrados y grey normalizado', function () {
    $nodes = JTM_Parser::parse( '{tabs}{tab T|green}X{/tabs}' );
    jtm_assert_same( 'green', $nodes[0]['items'][0]['color'] );
    $nodes = JTM_Parser::parse( '{tabs}{tab T|grey}X{/tabs}' );
    jtm_assert_same( 'gray', $nodes[0]['items'][0]['color'] );
} );

jtm_add_test( 'parser: hex de 3, 6 y 8 dígitos', function () {
    $nodes = JTM_Parser::parse( '{tabs}{tab T|#FFF}X{/tabs}' );
    jtm_assert_same( '#fff', $nodes[0]['items'][0]['color'] );
    $nodes = JTM_Parser::parse( '{tabs}{tab T|#AABBCC}X{/tabs}' );
    jtm_assert_same( '#aabbcc', $nodes[0]['items'][0]['color'] );
    $nodes = JTM_Parser::parse( '{tabs}{tab T|#AABBCCDD}X{/tabs}' );
    jtm_assert_same( '#aabbccdd', $nodes[0]['items'][0]['color'] );
} );

jtm_add_test( 'parser: hex de 4 y 5 dígitos rechazados', function () {
    $nodes = JTM_Parser::parse( '{tabs}{tab T|#FFFF}X{/tabs}' );
    jtm_assert_same( 'default', $nodes[0]['items'][0]['color'] );
    $nodes = JTM_Parser::parse( '{tabs}{tab T|#FFFFF}X{/tabs}' );
    jtm_assert_same( 'default', $nodes[0]['items'][0]['color'] );
} );

jtm_add_test( 'parser: alias explícito y por defecto', function () {
    $nodes = JTM_Parser::parse( '{tabs}{tab Título|green|mi-alias}X{/tabs}' );
    jtm_assert_same( 'mi-alias', $nodes[0]['items'][0]['alias'] );
    $nodes = JTM_Parser::parse( '{tabs}{tab Mi Título}X{/tabs}' );
    jtm_assert_same( 'mi-t-tulo', $nodes[0]['items'][0]['alias'] );
} );

jtm_add_test( 'parser: grupo sin cierre queda como texto plano', function () {
    $content = 'Hola {tabs}{tab A}sin cierre';
    $nodes   = JTM_Parser::parse( $content );
    jtm_assert_count( 1, $nodes );
    jtm_assert_same( 'html', $nodes[0]['type'] );
    jtm_assert_same( $content, $nodes[0]['html'] );
} );

jtm_add_test( 'parser: grupo vacío conserva el original', function () {
    $content = 'antes{tabs}{/tabs}después';
    $nodes   = JTM_Parser::parse( $content );
    $join    = '';
    foreach ( $nodes as $node ) {
        jtm_assert_same( 'html', $node['type'] );
        $join .= $node['html'];
    }
    jtm_assert_same( $content, $join );
} );
