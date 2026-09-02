<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Parsea la sintaxis de Joomla {tabs}/{sliders} en un árbol de nodos.
 */
class JTM_Parser {

    /**
     * @param string $content Contenido crudo con etiquetas Joomla.
     * @return array Lista de nodos: HTML plano o bloques tabs/sliders.
     */
    public static function parse( $content ) {
        $nodes  = array();
        $pattern = '/\{(tabs|sliders)\}(.*?)\{\/\1\}/is';

        $offset = 0;
        while ( preg_match( $pattern, $content, $m, PREG_OFFSET_CAPTURE, $offset ) ) {
            $start = $m[0][1];
            $len   = strlen( $m[0][0] );

            if ( $start > $offset ) {
                $nodes[] = array(
                    'type' => 'html',
                    'html' => substr( $content, $offset, $start - $offset ),
                );
            }

            $group_type = strtolower( $m[1][0] ); // tabs | sliders
            $inner      = $m[2][0];

            $nodes[] = array(
                'type'  => $group_type === 'tabs' ? 'tabgroup' : 'slidergroup',
                'items' => self::parse_items( $inner, $group_type === 'tabs' ? 'tab' : 'slider' ),
            );

            $offset = $start + $len;
        }

        if ( $offset < strlen( $content ) ) {
            $nodes[] = array(
                'type' => 'html',
                'html' => substr( $content, $offset ),
            );
        }

        return $nodes;
    }

    /**
     * Divide el contenido interno de {tabs}/{sliders} en items individuales.
     *
     * @param string $inner Contenido entre {tabs}...{/tabs}.
     * @param string $tag   'tab' o 'slider'.
     */
    private static function parse_items( $inner, $tag ) {
        $items   = array();
        $pattern = '/\{' . $tag . '\s+([^}]*)\}/i';

        preg_match_all( $pattern, $inner, $matches, PREG_OFFSET_CAPTURE );

        $count = count( $matches[0] );
        for ( $i = 0; $i < $count; $i++ ) {
            $header_raw = $matches[1][ $i ][0];
            $marker_end = $matches[0][ $i ][1] + strlen( $matches[0][ $i ][0] );

            $next_start = ( $i + 1 < $count ) ? $matches[0][ $i + 1 ][1] : strlen( $inner );
            $body       = substr( $inner, $marker_end, $next_start - $marker_end );

            // Elimina el cierre {/tab} o {/slider} si existe al final del bloque.
            $body = preg_replace( '/\{\/' . $tag . '\}\s*$/i', '', $body );

            $items[] = self::parse_header( $header_raw, $tag ) + array( 'body' => trim( $body ) );
        }

        return $items;
    }

    /**
     * Interpreta "Título|color|alias" respetando estados especiales open/close.
     */
    private static function parse_header( $header_raw, $tag ) {
        $parts = array_map( 'trim', explode( '|', $header_raw ) );

        $title = isset( $parts[0] ) ? $parts[0] : '';
        $color = '';
        $alias = '';
        $state = ( $tag === 'slider' ) ? 'closed' : 'default'; // sliders cierran por defecto en Joomla

        foreach ( array_slice( $parts, 1 ) as $part ) {
            $lower = strtolower( $part );
            if ( in_array( $lower, array( 'open', 'close', 'closed' ), true ) ) {
                $state = ( $lower === 'open' ) ? 'open' : 'closed';
            } elseif ( self::is_color( $part ) ) {
                $color = $lower;
            } else {
                $alias = $part; // Puede contener # para anclas personalizadas.
            }
        }

        return array(
            'title' => $title,
            'color' => $color ? $color : 'default',
            'alias' => $alias ? sanitize_title( str_replace( '#', '', $alias ) ) : sanitize_title( $title ),
            'state' => $state,
        );
    }

    private static function is_color( $value ) {
        $named = array( 'red', 'orange', 'yellow', 'green', 'blue', 'purple', 'gray', 'grey', 'black', 'white' );
        if ( in_array( strtolower( $value ), $named, true ) ) {
            return true;
        }
        return (bool) preg_match( '/^#[0-9a-f]{3,6}$/i', $value );
    }
}