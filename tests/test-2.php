<?php
/**
 * Arnés de prueba standalone nº 2: sliders, colores y estados.
 *
 * Cubre: open/close/por defecto, colores nombrados, grey→gray, hex inline,
 * alias explícitos (deep-link por hash), negrita en cuerpo, Markdown,
 * grupo vacío (fallback a texto) y grupo sin cierre (texto plano).
 *
 * Uso:
 *   php -S localhost:8000
 *   Abrir http://localhost:8000/tests/test-2.php en el navegador
 */

define( 'ABSPATH', __DIR__ . '/' );

// Stubs mínimos de las funciones de WordPress usadas por el plugin.
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
    $paragraphs = preg_split( '/\n\s*\n/', trim( (string) $text ) );
    $html       = array_map( function ( $p ) {
        return '<p>' . nl2br( trim( $p ) ) . '</p>';
    }, array_filter( $paragraphs, 'strlen' ) );
    return implode( "\n", $html );
}

require_once __DIR__ . '/../includes/parser.php';
require_once __DIR__ . '/../includes/renderer.php';

// Contenido Joomla de prueba (pega aquí cualquier otro fragmento que quieras probar).
$content = <<<'JOOMLA'
{sliders}

{slider Panel abierto por defecto|green|open}

Contenido con **negrita en el cuerpo** y [un enlace](https://example.com/).

{slider Panel cerrado explícito|blue|close}

Este panel empieza cerrado aunque lleve color.

{slider Panel rojo hex|#ff0000}

Color hexadecimal personalizado: se aplica como estilo inline, no como clase.

{slider Panel gris británico|grey}

`grey` se normaliza a `gray`.

{slider Panel blanco|white}

El blanco usa un gris visible como equivalente de borde.

{slider Panel sin estado}

Sin `|open` ni `|close`: cerrado por defecto, como en Joomla.

{/sliders}

Grupo vacío (debe mostrarse tal cual, sin romperse):

{sliders}{/sliders}

Grupo sin cierre (también debe quedar como texto plano):

{slider Panel roto} este contenido no se convierte.

Descarga de ejemplo [![pdf](https://example.com/pdf-icon.png)](https://example.com/documento.pdf)
JOOMLA;

$tree = JTM_Parser::parse( $content );
$html = JTM_Renderer::render( $tree );
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba local 2 — Sliders y colores</title>
    <link rel="stylesheet" href="../assets/css/tabs.css">
    <style>body { max-width: 800px; margin: 40px auto; font-family: sans-serif; }</style>
</head>
<body>
    <h1>Prueba local 2: sliders, colores y estados</h1>
    <p>Consejo: abre un slider y observa cómo la URL publica su hash (deep-link).</p>
    <?php echo $html; ?>

    <script src="../assets/js/tabs.js"></script>
</body>
</html>
