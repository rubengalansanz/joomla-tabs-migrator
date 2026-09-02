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

require_once __DIR__ . '/includes/parser.php';
require_once __DIR__ . '/includes/renderer.php';

// Contenido Joomla de prueba (pega aquí cualquier otro fragmento que quieras probar).
$content = <<<'JOOMLA'
{tab Texto |green}

1. La Junta Consultiva de Contratación Administrativa del Estado pondrá a disposición de todos los órganos de contratación una plataforma electrónica.

2. La plataforma deberá contar con un dispositivo que permita acreditar fehacientemente el inicio de la difusión pública.

{tab Desarrollo Reglamentario|blue}

* Sin referencias *

{tab Juntas Consultivas|orange}

{slider JCCAMEH 31/2011. Órganos y entidades que deben entenderse sujetas a **la obligación de integración de su perfil de contratante**.}

1. La obligación que impone el artículo 309 de la Ley de Contratos del Sector Público a los órganos de contratación.

Ver texto completo[![pdf](https://csp.alamoconsulting.com/wp-content/uploads/pdf.png)](https://csp.alamoconsulting.com/wp-content/uploads/JCCAMEH-31-2011.pdf)

{slider JCCAMEH Informe 72/2008. Configuración del **perfil del contratante** de cada órgano de contratación.}

La organización interna de la difusión de información sobre los contratos establecida en la LCSP en las Corporaciones locales.

Ver texto completo [![pdf](https://csp.alamoconsulting.com/wp-content/uploads/pdf.png)](https://csp.alamoconsulting.com/wp-content/uploads/JCCAMEH-72-2008.pdf)

{/sliders}

{tab Otros|white}

Guía del Operador Económico en la Plataforma de Contratación del Sector Público

Descargar la Guía [![pdf](https://csp.alamoconsulting.com/wp-content/uploads/pdf.png)](https://csp.alamoconsulting.com/wp-content/uploads/GuiaOperadorEconomico_v03.00.pdf)

{slider PRÁCTICO CSP sobre **El Perfil de Contratante y la Plataforma de Contratación del Sector Publico**.}

Contenido de ejemplo del práctico, con [un enlace normal](https://csp.alamoconsulting.com/) de prueba.

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
    <link rel="stylesheet" href="assets/css/tabs.css">
    <style>body { max-width: 800px; margin: 40px auto; font-family: sans-serif; }</style>
</head>
<body>
    <h1>Prueba local del contenido migrado</h1>
    <?php echo $html; ?>

    <script src="assets/js/tabs.js"></script>
</body>
</html>
