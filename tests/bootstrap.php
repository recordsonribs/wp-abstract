<?php
/**
 * Bootstrap the plugin unit testing environment. Customize 'active_plugins'
 * setting below to point to your main plugin file.
 *
 * Requires WordPress Unit Tests (http://unit-test.svn.wordpress.org/trunk/).
 *
 * @package wordpress-plugin-tests
 */

// Add this plugin to WordPress for activation so it can be tested.

$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( "wp-abstract/wp-abstract.php" ),
);

require dirname( __FILE__ ) . '/lib/bootstrap.php';
