<?php
/**
 * PHPUnit bootstrap file
 */

require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = sys_get_temp_dir() . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    throw new Exception( "Could not find WordPress tests in \$_tests_dir: {$_tests_dir}" );
}

require_once $_tests_dir . '/includes/functions.php';

function _bdpwr_tests_load_plugin() {
    require dirname( __DIR__ ) . '/bdvs-password-reset.php';
}

tests_add_filter( 'muplugins_loaded', '_bdpwr_tests_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
