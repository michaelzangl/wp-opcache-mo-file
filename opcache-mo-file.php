<?php
/*
Plugin Name:  OPCache MO-Files
Plugin URI:   https://github.com/michaelzangl/wp-opcache-mo-file/
Description:  Improve site speed by caching mo files
Version:      1.0
Author:       Michael Zangl
Author URI:   https://github.com/michaelzangl/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) or die();

function opcachemofile_mufile() {
	return WPMU_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'opcache-mo-file-loader.php';
}

function opcachemofile_activate() {
	$pluginDirName = plugin_dir_path(__FILE__);
	if (!is_writable($pluginDirName)) {
		wp_die('Could not activate: Please ensure that PHP has write permissions for the .../plugins/opcache-mo-file directory?');
	}
	
	$file = opcachemofile_mufile();
	$dir = dirname($file);
	if (!is_dir($dir)) {
		if (!mkdir($dir)) {
			wp_die('Could not activate: Could not create mu-plugins directory.');
		}
	}

	// Not hardcoding plugin path here - wordpress may be moved.
	if (!file_put_contents($file, '<?php' . PHP_EOL
			. '/* AUTOGENERATED by plugin opcache-mo-file. Decativate plugin to remove.*/' . PHP_EOL
			. 'include WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . ' . var_export(basename(dirname(__FILE__)) . '', true) . ' . DIRECTORY_SEPARATOR . "opcache-mo-file-mu.php";' . PHP_EOL)) {
		wp_die('Could not activate: Could not register mu-plugins loader file.');
	}
}

function opcachemofile_deactivate() {
	$file = opcachemofile_mufile();
	unlink($file);
	$dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
	foreach (array_diff(scandir($dir), array('.','..')) as $file) {
		unlink($dir . $file);
	}
}

register_activation_hook( __FILE__, 'opcachemofile_activate' );
register_deactivation_hook( __FILE__, 'opcachemofile_deactivate' );

if (is_dir(dirname(__FILE__) . '/plugin-update-checker')) {
	// Download updates from github for github snapshots - only enabled during github builds
	require dirname(__FILE__) . '/plugin-update-checker/plugin-update-checker.php';
	$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://github.com/michaelzangl/wp-opcache-mo-file/',
			__FILE__,
			'opcache-mo-file'
			);
}