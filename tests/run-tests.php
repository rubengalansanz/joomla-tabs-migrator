<?php
/**
 * Runner de la suite de tests (sin dependencias).
 *
 * Uso: php tests/run-tests.php
 *
 * Cuando haya red/Composer disponible, estas mismas aserciones se pueden
 * portar 1:1 a PHPUnit: cada jtm_add_test( 'grupo: caso', ... ) equivale a
 * un método test_... con $this->assert* en lugar de jtm_assert_*.
 */

require_once __DIR__ . '/bootstrap.php';

$GLOBALS['jtm_tests'] = array();

function jtm_add_test( $name, callable $fn ) {
    $GLOBALS['jtm_tests'][ $name ] = $fn;
}

class JTM_Assertion_Failed extends Exception {}

function jtm_assert_true( $cond, $message = '' ) {
    if ( ! $cond ) {
        throw new JTM_Assertion_Failed( '' !== $message ? $message : 'Se esperaba true.' );
    }
}

function jtm_assert_false( $cond, $message = '' ) {
    if ( $cond ) {
        throw new JTM_Assertion_Failed( '' !== $message ? $message : 'Se esperaba false.' );
    }
}

function jtm_assert_same( $expected, $actual, $message = '' ) {
    if ( $expected !== $actual ) {
        throw new JTM_Assertion_Failed(
            ( '' !== $message ? $message . ' ' : '' )
            . 'Esperado ' . var_export( $expected, true ) . ', obtenido ' . var_export( $actual, true ) . '.'
        );
    }
}

function jtm_assert_contains( $needle, $haystack, $message = '' ) {
    if ( false === strpos( (string) $haystack, (string) $needle ) ) {
        throw new JTM_Assertion_Failed( ( '' !== $message ? $message . ' ' : '' ) . 'No se encontró ' . var_export( $needle, true ) . '.' );
    }
}

function jtm_assert_not_contains( $needle, $haystack, $message = '' ) {
    if ( false !== strpos( (string) $haystack, (string) $needle ) ) {
        throw new JTM_Assertion_Failed( ( '' !== $message ? $message . ' ' : '' ) . 'No debería aparecer ' . var_export( $needle, true ) . '.' );
    }
}

function jtm_assert_count( $expected, $array, $message = '' ) {
    jtm_assert_same( $expected, count( $array ), '' !== $message ? $message : 'Recuento inesperado.' );
}

function jtm_assert_match( $pattern, $subject, $message = '' ) {
    if ( 1 !== preg_match( $pattern, (string) $subject ) ) {
        throw new JTM_Assertion_Failed( ( '' !== $message ? $message . ' ' : '' ) . 'No coincide con ' . $pattern . '.' );
    }
}

require_once __DIR__ . '/parser-test.php';
require_once __DIR__ . '/renderer-test.php';
require_once __DIR__ . '/bootstrap-test.php';

$pass = 0;
$fail = 0;
$failures = array();

foreach ( $GLOBALS['jtm_tests'] as $name => $fn ) {
    try {
        $fn();
        $pass++;
        echo "PASS {$name}\n";
    } catch ( JTM_Assertion_Failed $e ) {
        $fail++;
        $failures[] = $name . ': ' . $e->getMessage();
        echo "FAIL {$name}: " . $e->getMessage() . "\n";
    } catch ( Throwable $e ) {
        $fail++;
        $failures[] = $name . ': ERROR ' . get_class( $e ) . ': ' . $e->getMessage();
        echo "ERROR {$name}: " . $e->getMessage() . "\n";
    }
}

echo "\n{$pass} pasados, {$fail} fallidos, " . count( $GLOBALS['jtm_tests'] ) . " en total.\n";

exit( $fail > 0 ? 1 : 0 );
