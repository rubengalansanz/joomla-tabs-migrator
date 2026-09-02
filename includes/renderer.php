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
                esc_html( $item['title'] )
            );

            $panels .= sprintf(
                '<div id="%1$s" class="jtm-tab-panel%2$s" role="tabpanel" aria-labelledby="%3$s"%4$s>%5$s</div>',
                esc_attr( $panel_id ),
                $is_active ? ' jtm-active' : '',
                esc_attr( $tab_id ),
                $is_active ? '' : ' hidden',
                wpautop( $item['body'] )
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
                esc_html( $item['title'] ),
                $is_open ? '' : ' hidden',
                wpautop( $item['body'] )
            );
        }

        $out .= '</div>';
        return $out;
    }
}