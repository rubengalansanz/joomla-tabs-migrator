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

        while ( $offset < $length ) {
            // El tipo de grupo lo decide el marcador (contenedor o item) que aparezca antes:
            // así un {/sliders} anidado dentro de un tab nunca se confunde con el cierre del {tabs} exterior.
            $tabs_pos    = self::find_first( $content, array( '{tabs}', '{tab ' ), $offset );
            $sliders_pos = self::find_first( $content, array( '{sliders}', '{slider ' ), $offset );

            if ( false === $tabs_pos && false === $sliders_pos ) {
                break;
            }

            if ( false !== $tabs_pos && ( false === $sliders_pos || $tabs_pos <= $sliders_pos ) ) {
                $group_type = 'tabs';
                $group_pos  = $tabs_pos;
            } else {
                $group_type = 'sliders';
                $group_pos  = $sliders_pos;
            }

            $close_tag = '{/' . $group_type . '}';
            $close_pos = stripos( $content, $close_tag, $group_pos );

            if ( false === $close_pos ) {
                break; // Grupo sin cierre: se deja como texto plano más abajo.
            }

            if ( $group_pos > $offset ) {
                $nodes[] = array(
                    'type' => 'html',
                    'html' => substr( $content, $offset, $group_pos - $offset ),
                );
            }

            $open_tag    = '{' . $group_type . '}';
            $inner_start = ( stripos( $content, $open_tag, $group_pos ) === $group_pos ) ? $group_pos + strlen( $open_tag ) : $group_pos;
            $item_tag    = ( $group_type === 'tabs' ) ? 'tab' : 'slider';

            $nodes[] = array(
                'type'  => $group_type === 'tabs' ? 'tabgroup' : 'slidergroup',
                'items' => self::parse_items( substr( $content, $inner_start, $close_pos - $inner_start ), $item_tag ),
            );

            $offset = $close_pos + strlen( $close_tag );
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
     * Devuelve la posición más temprana (desde $offset) de cualquiera de los marcadores dados, o false.
     */
    private static function find_first( $content, array $needles, $offset ) {
        $best = false;
        foreach ( $needles as $needle ) {
            $pos = stripos( $content, $needle, $offset );
            if ( false !== $pos && ( false === $best || $pos < $best ) ) {
                $best = $pos;
            }
        }
        return $best;
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