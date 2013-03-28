<?php
//***************************//
//   TF configuration file   //
//***************************//
global $tf;
$tf = array();
if (empty($tf) || !is_array($tf)) {
	$tf = array();
}

// Field type must be the first word in the in-line comment
// Available field types are:
// boolean   --> True/False radio buttons
// string    --> Simple input text field
// text/html --> Text area
// numeric/number --> Input text field that only allows numbers
// hidden    --> This line will be ignored
// read only --> Will be written as text
// Default values:

// MySQL Database
$tf['db.user'] = ''; // Datebase connection user name
$tf['db.pass'] = ''; // Datebase connection user password
$tf['db.host'] = 'localhost'; // Database server connection host
$tf['db.name'] = ''; // Database name for selection
$tf['db.charset'] = 'utf8'; // Database connection character set
$tf['db.collation'] = 'utf8_unicode_ci'; // Database connection collation
$tf['db.pre'] = 'tf_'; // Database Prefix for TF tables and session variables
$tf['db.autobackup'] = 0; // numeric. Backup database every X days. 0 to disable. Affects footer.php to include tfout.php

// TableField info tables names
$tf['tbl.info'] = ''; // The name of the TF information table (traditionally "tf_info")
$tf['tbl.meta'] = ''; // The name of the TF Meta information table - additional fields for the information table (traditionally "tf_meta")
$tf['tbl.users'] = ''; // The name of the TF users table (traditionally "tf_users")
$tf['tbl.log'] = ''; // The name of the TF actions log table (traditionally "tf_log")

// start of configuration utility
// General information
$tf['tf.version'] = '3.0.1'; // read-only. Current TableField version
$tf['db.ok'] = false; // read-only. Is the DB connected?
// HTML defaults
$tf['html.theme'] = ''; // theme. Select a theme from http://bootswatch.com/#gallery
$tf['html.title'] = 'TableField Admin System'; // string. Title of the window (html <title> tag)
$tf['html.toplinks'] = ''; // text. Links to the top bar. format: name|link|in-link parameters (for example 'target=_blank'
$tf['html.head'] = ''; // html. Will be put inside the <head> tag
$tf['html.body'] = ''; // html. Will be put first in the <body>
$tf['html.footer'] = ''; // html. Will be put last in the <body>
$tf['html.footerscript'] = ''; // text. A javascript that will run at the footer, used by TfType's to apply styles etc.
$tf['html.rtl'] = false; // boolean. Is the UI right-to-left? setting this to TRUE will include tfimg/tfrtl.css
$tf['html.charset'] = 'UTF-8'; // string. Character set of TF. Should be UTF-8 (html charset <meta> tag)
$tf['html.chosen'] = true; // boolean. Use Chosen jQueryUI plugin for selects.
$tf['tf.nosy'] = false; // boolean. call a select and only update changed values. report to log which values were changed.
$tf['debug'] = false; // boolean. Should you want debug notifications - turn this on.
$tf['html.errors'] = 1 * E_ALL; // numeric. Show errors? which ones? see error_reporting() documentation for help: http://php.net/errorfunc.constants
$tf['lang'] = 'en_US'; // language. Set the current language
$tf['sql.printall'] = false; // boolean. Echo all queries to screen for debug.
// Authorization
$tf['auth.anonymous'] = false; // boolean. Allow users to login without a password? anonymous users are from group 'anonymous'
$tf['auth.keepalive'] = false; // boolean. Keep user always logged in as long as the browser is still open')
$tf['auth.sessionexpire'] = 20; // numeric. Server session expires after X minutes

// URL, path and folders settings (TODO: add url.abs and path.abs options)
$tf['url.rel'] = '../'; // string. Location of relative urls of the actual website, relative to TF admin url. Usually "../" (when TF is located at example.com/tf or example.com/admin)
$tf['path.rel'] = '../'; // string. Location of file paths of the actual website, relative to the TF admin location. Usually "../" (when TF is located at example.com/tf or example.com/admin)
$tf['url.img'] = 'tfimg'; // string. The table-field images and style css files folder. Should be "tfimg". Note: This is not the custom/img folder, which has the custom images and style css files.


// general log
if (!array_key_exists('log',$tf)) $tf['log'] = '';
// general cache
if (!array_key_exists('cache',$tf)) $tf['cache'] = array();

////////// INCLUDE DATABASE PREFERENCES ////////
$fn = __DIR__.'/../custom/dbconfig.php';
if (file_exists($fn)) {
	require_once($fn);
	// Connect to DB
	if (!empty($tf['db.name']) && @mysql_ping()) {
		// already connected
		if ($tf['db.ok'] = @mysql_select_db($tf['db.name']))
			if (!empty($tf['db.charset']))
				mysql_set_charset($tf['db.charset']);
	} else {
		if (!empty($tf['db.name']))
		// db connection configuration is set - try to connect
			if (@mysql_connect($tf['db.host'], $tf['db.user'], $tf['db.pass']))
				if ($tf['db.ok'] = @mysql_select_db($tf['db.name']))
					if (!empty($tf['db.charset']))
						mysql_set_charset($tf['db.charset']);
	}
}

////////// INCLUDE USER PREFERENCES //////////
$fn = __DIR__.'/../custom/tfconfig.php';
if (file_exists($fn)) {
	require_once($fn);
}

////////// error reporting
error_reporting($tf['html.errors']);
ini_set('display_errors', 1 * (!!$tf['html.errors']));

////////// Language
putenv("LC_ALL=$tf[lang]");
if (setlocale(LC_ALL, $tf['lang'])===false && $tf['lang']!='en_US') $tf['log'].="SET LOCALE FAILED TO: $tf[lang]<br>";
bindtextdomain("messages", dirname(__FILE__)."/../locale/");
bind_textdomain_codeset('messages', 'UTF-8');
textdomain("messages");

////////// SET DEBUG CONST and other values and functions //////////
if (!defined('DEBUG')) define('DEBUG',$tf['debug']);
global $sql_print_every_query,$sql_print_errors;
$sql_print_every_query=$tf['sql.printall'];
$sql_print_errors=DEBUG;
