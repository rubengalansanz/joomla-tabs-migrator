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
                    $out .= self::maybe_autop( self::convert_bold( self::markdown_to_html( $node['html'] ) ) );
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
     * En el cuerpo se aplica después de markdown_to_html, sobre HTML con
     * etiquetas inline; los ** dentro de atributos son un caso raro asumido.
     */
    private static function convert_bold( $text ) {
        return preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text );
    }

    /**
     * Aplica wpautop solo si el texto aún no trae bloques HTML.
     *
     * El filtro the_content ya trae wpautop aplicado (prioridad 10) antes de
     * nuestro filtro (prioridad 20); volver a aplicar wpautop anidaría <p>.
     * Si el fragmento ya contiene bloques (<p>, <div>, <ul>, <h1-6>, ...),
     * se devuelve tal cual.
     */
    private static function maybe_autop( $text ) {
        if ( preg_match( '/<\s*(p|div|ul|ol|li|h[1-6]|table|blockquote|pre|section|article)[\s>]/i', $text ) ) {
            return $text;
        }

        if ( function_exists( 'wpautop' ) ) {
            return wpautop( $text );
        }

        return $text;
    }

    /**
     * Descompone un color en clase CSS y estilo inline.
     *
     * Los colores nombrados usan la clase jtm-color-{nombre} (ver tabs.css).
     * Los hexadecimales (#rrggbb) no son clases válidas, así que usan
     * jtm-color-custom + style="border-top-color:...".
     *
     * @return array array( $class, $style_attr ) con $style_attr listo para interpolar ('' o ' style="..."').
     */
    private static function color_presentation( $color ) {
        if ( is_string( $color ) && '' !== $color && '#' === $color[0] ) {
            return array( 'jtm-color-custom', ' style="border-top-color:' . esc_attr( $color ) . '"' );
        }

        $safe = ( is_string( $color ) && '' !== $color ) ? $color : 'default';

        return array( 'jtm-color-' . $safe, '' );
    }

    /**
     * Genera IDs de panel legibles a partir del alias, deduplicados por grupo.
     *
     * Sin alias se mantiene el formato anterior ({grupo}-panel-{i}) para no
     * romper CSS/JS. Con alias: {grupo}-{alias} (sufijo -2, -3 si colisiona).
     *
     * @param array  $items Items del grupo (con clave 'alias').
     * @param string $group_id ID del grupo.
     * @return array Lista de IDs de panel, una por item.
     */
    private static function panel_ids_for_items( array $items, $group_id ) {
        $ids  = array();
        $used = array();

        foreach ( $items as $index => $item ) {
            $alias = isset( $item['alias'] ) ? trim( (string) $item['alias'] ) : '';
            $base  = ( '' !== $alias ) ? $group_id . '-' . $alias : $group_id . '-panel-' . $index;

            $candidate = $base;
            $suffix    = 2;
            while ( isset( $used[ $candidate ] ) ) {
                $candidate = $base . '-' . $suffix;
                $suffix++;
            }

            $used[ $candidate ] = true;
            $ids[]              = $candidate;
        }

        return $ids;
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

        // Enlace suelto: [texto](url). El texto se escapa (luego convert_bold
        // aún convierte **negrita** dentro del enlace, pues * no se escapa).
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            function ( $m ) {
                return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $m[2] ), esc_html( $m[1] ) );
            },
            $text
        );

        return $text;
    }

    private static function render_tabgroup( array $items ) {
        self::$instance_counter++;
        $group_id  = 'jtm-tabs-' . self::$instance_counter;
        $panel_ids = self::panel_ids_for_items( $items, $group_id );

        $nav    = '<ul class="jtm-tab-nav" role="tablist">';
        $panels = '';

        foreach ( $items as $index => $item ) {
            $panel_id  = $panel_ids[ $index ];
            $tab_id    = $group_id . '-tab-' . $index;
            $is_active = ( $index === 0 );

            list( $color_class, $color_style ) = self::color_presentation( $item['color'] );
            $alias_attr = ( isset( $item['alias'] ) && '' !== $item['alias'] ) ? ' data-alias="' . esc_attr( $item['alias'] ) . '"' : '';

            $nav .= sprintf(
                '<li class="jtm-tab-item%2$s" role="presentation">
                    <button type="button" id="%3$s" class="jtm-tab-link %1$s"%7$s%8$s role="tab" aria-selected="%4$s" aria-controls="%5$s">%6$s</button>
                </li>',
                esc_attr( $color_class ),
                $is_active ? ' jtm-active' : '',
                esc_attr( $tab_id ),
                $is_active ? 'true' : 'false',
                esc_attr( $panel_id ),
                self::convert_bold( esc_html( $item['title'] ) ),
                $color_style,
                $alias_attr
            );

            $panels .= sprintf(
                '<div id="%1$s" class="jtm-tab-panel%2$s" role="tabpanel" aria-labelledby="%3$s"%6$s %4$s>%5$s</div>',
                esc_attr( $panel_id ),
                $is_active ? ' jtm-active' : '',
                esc_attr( $tab_id ),
                $is_active ? '' : ' hidden',
                self::render_body( $item['body'] ),
                $alias_attr
            );
        }
        $nav .= '</ul>';

        return sprintf( '<div class="jtm-tabs" id="%1$s">%2$s<div class="jtm-tab-panels">%3$s</div></div>', esc_attr( $group_id ), $nav, $panels );
    }

    private static function render_slidergroup( array $items ) {
        self::$instance_counter++;
        $group_id  = 'jtm-sliders-' . self::$instance_counter;
        $panel_ids = self::panel_ids_for_items( $items, $group_id );

        $out = '<div class="jtm-sliders" id="' . esc_attr( $group_id ) . '">';

        foreach ( $items as $index => $item ) {
            $panel_id = $panel_ids[ $index ];
            $header_id = $group_id . '-header-' . $index;
            $is_open  = ( $item['state'] === 'open' );

            list( $color_class, $color_style ) = self::color_presentation( $item['color'] );
            $alias_attr = ( isset( $item['alias'] ) && '' !== $item['alias'] ) ? ' data-alias="' . esc_attr( $item['alias'] ) . '"' : '';

            $out .= sprintf(
                '<div class="jtm-slider-item%2$s">
                    <h3 class="jtm-slider-header">
                        <button type="button" id="%3$s" class="jtm-slider-toggle %1$s"%7$s%9$s aria-expanded="%4$s" aria-controls="%5$s">%6$s</button>
                    </h3>
                    <div id="%5$s" class="jtm-slider-panel" role="region" aria-labelledby="%3$s"%9$s %8$s>%10$s</div>
                </div>',
                esc_attr( $color_class ),
                $is_open ? ' jtm-active' : '',
                esc_attr( $header_id ),
                $is_open ? 'true' : 'false',
                esc_attr( $panel_id ),
                self::convert_bold( esc_html( $item['title'] ) ),
                $color_style,
                $is_open ? '' : ' hidden',
                $alias_attr,
                self::render_body( $item['body'] )
            );
        }

        $out .= '</div>';
        return $out;
    }
}