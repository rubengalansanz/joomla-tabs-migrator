<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Convierte el árbol de nodos de JTM_Parser en HTML accesible.
 */
class JTM_Renderer {

    private static $instance_counter = 0;

    public static function render( array $nodes ) {
        $out = '';
        foreach ( $nodes as $node ) {
            switch ( $node['type'] ) {
                case 'html':
                    $out .= $node['html'];
                    break;
                case 'tabgroup':
                    $out .= self::render_tabgroup( $node['items'] );
                    break;
                case 'slidergroup':
                    $out .= self::render_slidergroup( $node['items'] );
                    break;
            }
        }
        return $out;
    }

    /**
     * Renderiza el body de un item, ya parseado recursivamente (puede contener grupos anidados).
     */
    private static function render_body( array $nodes ) {
        $out = '';
        foreach ( $nodes as $node ) {
            switch ( $node['type'] ) {
                case 'html':
                    $out .= wpautop( self::markdown_to_html( $node['html'] ) );
                    break;
                case 'tabgroup':
                    $out .= self::render_tabgroup( $node['items'] );
                    break;
                case 'slidergroup':
                    $out .= self::render_slidergroup( $node['items'] );
                    break;
            }
        }
        return $out;
    }

    /**
     * Convierte **texto** en <strong>texto</strong> (aplicar tras esc_html).
     */
    private static function convert_bold( $text ) {
        return preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text );
    }

    /**
     * Convierte enlaces e imágenes en sintaxis Markdown básica a HTML.
     */
    private static function markdown_to_html( $text ) {
        // Imagen enlazada: [![alt](img)](url)
        $text = preg_replace_callback(
            '/\[!\[([^\]]*)\]\(([^)]+)\)\]\(([^)]+)\)/',
            function ( $m ) {
                return sprintf(
                    '<a href="%1$s"><img src="%2$s" alt="%3$s" /></a>',
                    esc_url( $m[3] ),
                    esc_url( $m[2] ),
                    esc_attr( $m[1] )
                );
            },
            $text
        );

        // Imagen suelta: ![alt](img)
        $text = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)]+)\)/',
            function ( $m ) {
                return sprintf( '<img src="%1$s" alt="%2$s" />', esc_url( $m[2] ), esc_attr( $m[1] ) );
            },
            $text
        );

        // Enlace suelto: [texto](url)
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            function ( $m ) {
                return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $m[2] ), $m[1] );
            },
            $text
        );

        return $text;
    }

    private static function render_tabgroup( array $items ) {
        self::$instance_counter++;
        $group_id = 'jtm-tabs-' . self::$instance_counter;

        $nav    = '<ul class="jtm-tab-nav" role="tablist">';
        $panels = '';

        foreach ( $items as $index => $item ) {
            $panel_id  = $group_id . '-panel-' . $index;
            $tab_id    = $group_id . '-tab-' . $index;
            $is_active = ( $index === 0 );

            $nav .= sprintf(
                '<li class="jtm-tab-item jtm-color-%1$s%2$s" role="presentation">
                    <button type="button" id="%3$s" class="jtm-tab-link" role="tab" aria-selected="%4$s" aria-controls="%5$s">%6$s</button>
                </li>',
                esc_attr( $item['color'] ),
                $is_active ? ' jtm-active' : '',
                esc_attr( $tab_id ),
                $is_active ? 'true' : 'false',
                esc_attr( $panel_id ),
                self::convert_bold( esc_html( $item['title'] ) )
            );

            $panels .= sprintf(
                '<div id="%1$s" class="jtm-tab-panel%2$s" role="tabpanel" aria-labelledby="%3$s"%4$s>%5$s</div>',
                esc_attr( $panel_id ),
                $is_active ? ' jtm-active' : '',
                esc_attr( $tab_id ),
                $is_active ? '' : ' hidden',
                self::render_body( $item['body'] )
            );
        }
        $nav .= '</ul>';

        return sprintf( '<div class="jtm-tabs" id="%1$s">%2$s<div class="jtm-tab-panels">%3$s</div></div>', esc_attr( $group_id ), $nav, $panels );
    }

    private static function render_slidergroup( array $items ) {
        self::$instance_counter++;
        $group_id = 'jtm-sliders-' . self::$instance_counter;

        $out = '<div class="jtm-sliders" id="' . esc_attr( $group_id ) . '">';

        foreach ( $items as $index => $item ) {
            $panel_id = $group_id . '-panel-' . $index;
            $header_id = $group_id . '-header-' . $index;
            $is_open  = ( $item['state'] === 'open' );

            $out .= sprintf(
                '<div class="jtm-slider-item jtm-color-%1$s%2$s">
                    <h3 class="jtm-slider-header">
                        <button type="button" id="%3$s" class="jtm-slider-toggle" aria-expanded="%4$s" aria-controls="%5$s">%6$s</button>
                    </h3>
                    <div id="%5$s" class="jtm-slider-panel" role="region" aria-labelledby="%3$s"%7$s>%8$s</div>
                </div>',
                esc_attr( $item['color'] ),
                $is_open ? ' jtm-active' : '',
                esc_attr( $header_id ),
                $is_open ? 'true' : 'false',
                esc_attr( $panel_id ),
                self::convert_bold( esc_html( $item['title'] ) ),
                $is_open ? '' : ' hidden',
                self::render_body( $item['body'] )
            );
        }

        $out .= '</div>';
        return $out;
    }
}