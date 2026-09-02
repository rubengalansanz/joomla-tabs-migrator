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
        $offset = 0;
        $length = strlen( $content );

        // Solo el cierre {/tabs}/{/sliders} es obligatorio: Joomla permite omitir el contenedor de apertura.
        while ( preg_match( '/\{\/(tabs|sliders)\}/i', $content, $close_m, PREG_OFFSET_CAPTURE, $offset ) ) {
            $close_start = $close_m[0][1];
            $close_len   = strlen( $close_m[0][0] );
            $group_type  = strtolower( $close_m[1][0] );
            $item_tag    = ( $group_type === 'tabs' ) ? 'tab' : 'slider';

            $segment = substr( $content, $offset, $close_start - $offset );

            if ( preg_match( '/\{' . $group_type . '\}/i', $segment, $open_m, PREG_OFFSET_CAPTURE ) ) {
                $group_start = $open_m[0][1];
                $inner_start = $group_start + strlen( $open_m[0][0] );
            } elseif ( preg_match( '/\{' . $item_tag . '\s+/i', $segment, $item_m, PREG_OFFSET_CAPTURE ) ) {
                $group_start = $item_m[0][1];
                $inner_start = $group_start;
            } else {
                // Ni contenedor ni items: el cierre suelto se trata como texto plano.
                $nodes[] = array(
                    'type' => 'html',
                    'html' => $segment . $close_m[0][0],
                );
                $offset = $close_start + $close_len;
                continue;
            }

            if ( $group_start > 0 ) {
                $nodes[] = array(
                    'type' => 'html',
                    'html' => substr( $segment, 0, $group_start ),
                );
            }

            $nodes[] = array(
                'type'  => $group_type === 'tabs' ? 'tabgroup' : 'slidergroup',
                'items' => self::parse_items( substr( $segment, $inner_start ), $item_tag ),
            );

            $offset = $close_start + $close_len;
        }

        if ( $offset < $length ) {
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

            // Parseo recursivo: un tab puede contener un grupo {slider}/{sliders} anidado.
            $items[] = self::parse_header( $header_raw, $tag ) + array( 'body' => self::parse( trim( $body ) ) );
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