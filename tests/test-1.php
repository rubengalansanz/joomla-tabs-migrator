<?php
/**
 * Arnés de prueba standalone: simula el entorno de WordPress para probar
 * JTM_Parser/JTM_Renderer sin instalar WordPress.
 *
 * Uso:
 *   php -S localhost:8000
 *   Abrir http://localhost:8000/test-local.php en el navegador
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
{tab Información general|green}

1. Este contenido sirve para comprobar que las pestañas se muestran correctamente en el navegador.

2. El texto de prueba incluye varios párrafos y caracteres especiales como á, é, í, ó, ú y ñ.

{tab Documentación|blue}

* Sin referencias *

{tab Recursos relacionados|orange}

{slider Documento de ejemplo 1 sobre **información importante para la prueba**.}

1. Este panel comprueba el comportamiento de un slider anidado dentro de una pestaña.

Ver documento de ejemplo[![pdf](https://example.com/pdf-icon.png)](https://example.com/documento-1.pdf)

{slider Documento de ejemplo 2 sobre la configuración del **contenido de prueba**.}

Este segundo panel permite verificar que varios sliders pueden convivir dentro de la misma pestaña.

Ver documento de ejemplo [![pdf](https://example.com/pdf-icon.png)](https://example.com/documento-2.pdf)

{/sliders}

{tab Otros contenidos|white}

Guía de prueba del plugin Joomla Tabs Migrator

Descargar la guía de ejemplo [![pdf](https://example.com/pdf-icon.png)](https://example.com/guia-de-ejemplo.pdf)

{slider Prueba adicional sobre **pestañas y paneles desplegables**.}

Contenido de ejemplo con [un enlace normal](https://example.com/) de prueba.

{/sliders}

{/tabs}
JOOMLA;

$tree = JTM_Parser::parse( $content );
$html = JTM_Renderer::render( $tree );
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba local — Joomla Tabs Migrator</title>
    <link rel="stylesheet" href="../assets/css/tabs.css">
    <style>body { max-width: 800px; margin: 40px auto; font-family: sans-serif; }</style>
</head>
<body>
    <h1>Prueba local del contenido migrado</h1>
    <?php echo $html; ?>
    
    <script src="../assets/js/tabs.js"></script>
</body>
</html>
