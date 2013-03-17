<?php
global $tf;
// Load user includes from ?
if (file_exists(__DIR__.'/../custom/include.inc.php')) {
	include_once(__DIR__.'/../custom/include.inc.php');
}
if (!function_exists('fatal')) {

	function fatal($msg) {
		die($msg);
	}

}

// Load settings configuration files
require_once(__DIR__.'/tfconfig.php'); // it will also include the user config from custom/tfconfig.php
// Test the need for install.php
if (empty($tf['db.name'])) {
	header("Location: tfinstall.php");
	exit;
}
if (!$tf['db.ok']) {
	fatal('Error connection to DB');
}

require_once(__DIR__.'/oria.inc.php'); // general usable functions
// Start session
if (session_id() == '') {
	session_cache_expire($tf['auth.sessionexpire']); // set session timeout to 20 minutes
	session_start();
}

// Undo magic quotes damage
undo_magic_quotes(); // it will execute only if magic quotes are on
// Load general tf functions
require_once(__DIR__.'/tf.inc.php');
// Load basic set of TFType Field Classes PHP
require_once(__DIR__.'/tftypes.inc.php');

// Load authentication system
require_once(__DIR__.'/auth.php'); // TODO: move this to inc/auth.inc.php which will also handle custom authentications
