<?php
/**
 * Arnés de prueba standalone nº 3: anidados, contenedores y alias.
 *
 * Cubre: tabs anidados dentro de tabs (cierre balanceado), sliders dentro
 * de tabs, contenedor con parámetros, {tab} sin título, alias explícito
 * frente a slug del título, y fragmento ya formateado con <p> (sin doble
 * wpautop).
 *
 * Uso:
 *   php -S localhost:8000
 *   Abrir http://localhost:8000/tests/test-3.php en el navegador
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
{tabs alias=demo}

{tab Exterior uno|blue}

Texto exterior con **negrita en el cuerpo**.

{tabs}
{tab Interior A}Contenido del tab interior A.{/tab}
{tab Interior B|orange}Contenido del tab interior B.{/tabs}

Más texto del exterior tras el grupo anidado.

{tab Exterior dos|purple|segundo-exterior}

Segundo panel con alias explícito y un slider dentro:

{sliders}
{slider Subpanel|green|open}Abierto por defecto, con [enlace](https://example.com/).{/sliders}

{tab}

Tab sin título: aceptado e incluido en la navegación.

{tab Con HTML previo}

<p>Este párrafo ya viene formateado (como lo dejaría wpautop de WordPress): no debe duplicarse.</p>

{/tabs}
JOOMLA;

$tree = JTM_Parser::parse( $content );
$html = JTM_Renderer::render( $tree );
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba local 3 — Anidados y alias</title>
    <link rel="stylesheet" href="../assets/css/tabs.css">
    <style>body { max-width: 800px; margin: 40px auto; font-family: sans-serif; }</style>
</head>
<body>
    <h1>Prueba local 3: anidados, contenedores y alias</h1>
    <p>Consejo: prueba el teclado (flechas, Home, End) sobre las pestañas y el deep-link con el alias <code>segundo-exterior</code>.</p>
    <?php echo $html; ?>

    <script src="../assets/js/tabs.js"></script>
</body>
</html>
