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
            // El tipo de grupo lo decide el primer marcador (contenedor o item) que aparezca:
            // así un {/sliders} anidado dentro de un tab nunca se confunde con el cierre del {tabs} exterior.
            // Acepta {tab}, {tab Título}, {tabs}, {tabs params}, y equivalentes de slider (case-insensitive).
            if ( ! preg_match( '/\{(tabs|sliders|tab|slider)(?:\s[^}]*)?\}/i', $content, $m, PREG_OFFSET_CAPTURE, $offset ) ) {
                break;
            }

            $marker     = strtolower( $m[1][0] );
            $group_pos  = $m[0][1];
            $marker_len = strlen( $m[0][0] );

            if ( 'tabs' === $marker || 'tab' === $marker ) {
                $group_type = 'tabs';
            } else {
                $group_type = 'sliders';
            }

            $is_container_start = ( 'tabs' === $marker || 'sliders' === $marker );

            // Cierre balanceado: cuenta los contenedores anidados del mismo tipo
            // (excluyendo la apertura exterior), así un {tabs} anidado dentro de
            // un tab no cierra el grupo exterior.
            $found = self::find_group_close( $content, $group_type, $group_pos + $marker_len, 0 );

            if ( false === $found ) {
                break; // Grupo sin cierre: se deja como texto plano más abajo.
            }

            list( $close_pos, $close_len ) = $found;

            if ( $group_pos > $offset ) {
                $nodes[] = array(
                    'type' => 'html',
                    'html' => substr( $content, $offset, $group_pos - $offset ),
                );
            }

            $inner_start = $is_container_start ? $group_pos + $marker_len : $group_pos;
            $item_tag    = ( $group_type === 'tabs' ) ? 'tab' : 'slider';
            $inner       = substr( $content, $inner_start, $close_pos - $inner_start );
            $items       = self::parse_items( $inner, $item_tag );

            if ( empty( $items ) ) {
                // Grupo vacío o sin items válidos: se conserva el texto original.
                $nodes[] = array(
                    'type' => 'html',
                    'html' => substr( $content, $group_pos, $close_pos + $close_len - $group_pos ),
                );
            } else {
                $nodes[] = array(
                    'type'  => $group_type === 'tabs' ? 'tabgroup' : 'slidergroup',
                    'items' => $items,
                );
            }

            $offset = $close_pos + $close_len;
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
     * Busca el cierre {/tabs} o {/sliders} que equilibra el grupo, contando
     * los contenedores de apertura anidados del mismo tipo.
     *
     * @param string $content Contenido completo.
     * @param string $group_type 'tabs' o 'sliders'.
     * @param int    $from Posición desde la que escanear.
     * @param int    $depth Profundidad inicial (1 si el grupo abrió con contenedor, 0 si abrió con item).
     * @return array|false array( $close_pos, $close_len ) o false si no hay cierre.
     */
    private static function find_group_close( $content, $group_type, $from, $depth ) {
        $re = ( $group_type === 'tabs' )
            ? '/\{tabs(?:\s[^}]*)?\}|\{\/tabs\}/i'
            : '/\{sliders(?:\s[^}]*)?\}|\{\/sliders\}/i';

        $pos = $from;

        while ( preg_match( $re, $content, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
            $token     = $m[0][0];
            $token_pos = $m[0][1];
            $token_len = strlen( $token );

            if ( isset( $token[1] ) && '/' === $token[1] ) {
                if ( $depth <= 0 ) {
                    return array( $token_pos, $token_len );
                }
                $depth--;
            } else {
                $depth++;
            }

            $pos = $token_pos + $token_len;
        }

        return false;
    }

    /**
     * Divide el contenido interno de {tabs}/{sliders} en items individuales.
     *
     * Solo divide en los marcadores de item de nivel superior: los que aparecen
     * dentro de contenedores anidados pertenecen al grupo anidado (recursión).
     * Acepta {tab} vacío, {tab Título} y tabulaciones/espacios múltiples.
     *
     * @param string $inner Contenido entre {tabs}...{/tabs}.
     * @param string $tag   'tab' o 'slider'.
     */
    private static function parse_items( $inner, $tag ) {
        $starts = array();

        if ( ! preg_match_all( '/\{\/?(?:tabs|sliders|tab|slider)(?:\s[^}]*)?\}/i', $inner, $m, PREG_OFFSET_CAPTURE ) ) {
            return array();
        }

        $depth = 0;

        foreach ( $m[0] as $match ) {
            $token     = $match[0];
            $token_pos = $match[1];

            if ( preg_match( '/^\{(tabs|sliders)(\s|\})/i', $token ) ) {
                $depth++;
                continue;
            }

            if ( preg_match( '/^\{\/(tabs|sliders)\}/i', $token ) ) {
                $depth = max( 0, $depth - 1 );
                continue;
            }

            // Marcador de item de nuestro tipo en nivel superior.
            if ( 0 === $depth && preg_match( '/^\{' . preg_quote( $tag, '/' ) . '(\s|\})/i', $token ) ) {
                $closing = strrpos( $token, '}' );
                $inside  = substr( $token, 1, $closing - 1 );

                if ( preg_match( '/^\S+\s+(.*)$/s', $inside, $hm ) ) {
                    $header_raw = trim( $hm[1] );
                } else {
                    $header_raw = '';
                }

                $starts[] = array(
                    'header'      => $header_raw,
                    'marker_pos'  => $token_pos,
                    'marker_end'  => $token_pos + strlen( $token ),
                );
            }
        }

        $items = array();
        $count = count( $starts );

        for ( $i = 0; $i < $count; $i++ ) {
            $marker_end = $starts[ $i ]['marker_end'];
            $next_start = ( $i + 1 < $count ) ? $starts[ $i + 1 ]['marker_pos'] : strlen( $inner );
            $body       = substr( $inner, $marker_end, $next_start - $marker_end );

            // Elimina el cierre {/tab} o {/slider} si existe al final del bloque.
            $body = preg_replace( '/\{\/' . $tag . '\}\s*$/i', '', $body );

            // Parseo recursivo: un tab puede contener un grupo {slider}/{sliders} anidado.
            $items[] = self::parse_header( $starts[ $i ]['header'], $tag ) + array( 'body' => self::parse( trim( $body ) ) );
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
            } elseif ( '' !== ( $normalized = self::normalize_color( $part ) ) ) {
                $color = $normalized;
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

    /**
     * Normaliza un color a su forma canónica o devuelve '' si no es color.
     * Acepta nombres Joomla y hex de 3, 6 u 8 dígitos. grey → gray.
     */
    private static function normalize_color( $value ) {
        $lower = strtolower( trim( $value ) );

        if ( 'grey' === $lower ) {
            return 'gray';
        }

        $named = array( 'red', 'orange', 'yellow', 'green', 'blue', 'purple', 'gray', 'black', 'white' );
        if ( in_array( $lower, $named, true ) ) {
            return $lower;
        }

        if ( preg_match( '/^#[0-9a-f]{3}([0-9a-f]{3}([0-9a-f]{2})?)?$/i', $lower ) ) {
            return $lower;
        }

        return '';
    }

    private static function is_color( $value ) {
        return '' !== self::normalize_color( $value );
    }
}
