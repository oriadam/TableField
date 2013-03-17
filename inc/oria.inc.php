<?php

/* * *******************************************
  Collection of usefull PHP functions library

  filename: oria.inc.php
  author: Oria Adam
  License: GPLv3 (Open Source)
  http://tablefield.com
  http://code.google.com/p/oria-inc-php

 **** Functions reference ****
  undo_magic_quotes($force=false)
  does: undo damage done by automagic quotes to POST GET and such
  input: $force - optional.
  false = POST GET arrays will get fixed only when magic quotes are on, and will only run once.
  true  = POST GET arrays will get fixed in any case.
  output: nothing
  notes:  function only changes POST GET etc when when magic quotes are on (get_magic_quotes_gpc) or when $force is true.
  old name = oh_no_unescape_post()

  make_seed()
  does: returns timer based random seed.
  for mt_srand() and srand()
  input: none
  output: integer based on microtime()
  notes: srand(make_seed()) and mt_... are executed on including oria,inc.php for case of old php

  jsredirect($to)
  does: redirect to a url and die
  output: echo a javascript string and set header(Location)

  reGet($to=null,$url=null)
  does: recreate a link to current page, with different GET accoarding to $to
  input:
  $to : $_GET style associative array.
  values will be escaped with urlencode().
  to remove all parameters use empty array().
  $url: current url. when missing, use PHP_SELF
  output: url string

  mailparams($params)
  * This function was exported to mailparams.inc.php
  For more info see: http://tablefield.com/mailparams or http://code.google.com/p/mailparams-php
  does: Wrapper for mail() function
  notes: Calling this function is much slower than directly calling mail(), so do not use it when bulk sending.

  decabc($i)
  does: dec2abc. count using letters a-z
  input: integer
  output: string like: a,b...z,aa,ab,ac...az,ba,bb...zz,aaa,aab...,aaz,aba,...zzz...

  randstring($length,$chars='abcdefghijklmnopqrstuvwxyz')
  does: return a random string created from $chars
  input:
  $length: integer, length of output string
  $chars: string, optional set of characters to use. default a to z.
  output: string

  chkbox($field_or_value,$post_array=null)
  does: parse checkbox from html forms
  input:
  when $post_array is given:
  $field_or_value is the html name of the checkbox form field
  when $post_array is omitted:
  $field_or_value is a value taken from the post data
  output: true/false/null
  examples:
  if (chkbox($fname,$_POST))
  if (chkbox($_POST['dagim']))

  chkBool($value,$default=null)
  does: parse a boolean value
  input:
  $value:
  any type of boolean value
  for example: true/false/'yes'/'n'/'true'/'false'/'f'/1/0/'1'/'0'
  $default:
  value to returns for unknown values or null or ''
  output:
  true/false/$default

  tmplsimple($template,$values,$prekey='$',$postkey='')
  * old name tmpsimple
  does: process simple templating system -- replace all occurances of $key in $template with $values[$key]
  input:
  $template:
  string of the tempalte to be processed
  $values:
  array with keys that should be replaced
  $prekey,$postkey:
  optional - the way to mark start and end of a key. for example, for [keys] you need $prekey='[' $postkey=']'.
  output:
  string of the processed template
  notes:
  keys are case sensitive

  validateWhitehat($str,$valid)
  does: white-hat validation of a string
  input: $str string to check
  $valid allowed characters
  output: true/false

  removeWhitehat($str,$valid)
  does: Remove not valid characters from a string
  Return $str without chars not listed in $valid
  input: $str string to check
  $valid allowed characters
  output: string

  removeBlackhat($str,$invalid)
  does: Remove given invalid characters from a string
  Return $str without all the chars listed in $invalid
  input: $str string to check
  $invalid - characters not allowed
  output: string

  Get($key)
  does: wrapper for $_GET
  when $key not found in $_GET return null
  input: string key
  output: $_GET[$key] or null

  Post($key)
  does: wrapper for $_POST
  when $key not found in $_POST return null
  input: string key
  output: $_POST[$key] or null

  Session($key)
  does: wrapper for $_SESSION
  when $key not found in $_SESSION return null
  input: string key
  output: $_SESSION[$key] or null

  zeros($input,$length)
  does: Add leading zeros to a number
  input: $input - a number
         $length - total length of the number
  output: string of $length characters, or the $input number with leading zeros
  notes: Examples: zeros(12,5);    // '00012'
                   zeros('321',3); // '0321'
                   zeros(321,1);   // '321'

  autofilename($dir,$name,$footer,$zeros=4,$startfrom=1)
  does: Find the next available file name. Start counting from $startfrom.
  input: $dir - the path to look for files
         $name - base file name
         $footer - postfix of the file (usually the file extension with presceding dot, ie '.txt')
         $zeros - how many digits should the number have? to disable leading zeros set it to 0 or 1.
         $startfrom - the number to start couting from.
  output: the file name (without $dir)

  count_autofilename($dir,$name,$footer,$zeros=4,$startfrom=1)
  does: Count the number of sequencing files accoarding to their numbers
  input: see autofilename()
  output: the file
  notes: Unlike glob(), it stops couting when the series is stopped.
         For example, a bunch of files from 01 to 07, with number 05 missing, would return 4 (and not 6 or 7 as you might expect).
         If you just want to know how many files matching the pettern, use count(glob()).

  timestamp2str($ts)
  does: Convert timestamp to the simplest readable format
  input: a string like  '20030201123456'
  output: a string like '2003/02/01 12:34:56'

  getExtension($filename)
  does: Return the extension part of a file name


  /////////////////// Sql Functions - currently only mysql supported ///////////////////

  sqlGetType()
  does: return sql server type
  input: none
  output: 'mysql'
  notes: other servers may be supported one day... i think i'll get marry by then.

  sqlGetVersion()
  does: return sql server version
  input: none
  output: string
  notes: return "SELECT VERSION()"

  sqlremovestrings($sqlexpression,$replace='')
  does: remove all strings from an sql expression, and replace them with $replace
  input: an sql expression
  output: a strings-free expression. return false in case of an error (unclosed string)
  notes: This function cannot protect against sql injections or other attacks

  sqlvalidate($sqlexpression)
  does: make sure the sql expression is balanced in terms of 'quotes' and (parantheses)
  input: an sql expression
  output: true when the sql expressions looks ok, false otherwise
  notes: This function cannot protect against sql injections or other attacks

  sqle($string)
  does: alias to mysql_real_escape_string($string)
  input: string
  output: string (no quotes here)
  notes: the only purpose of this function is to make the long name of mysql_real_escape_string shorter...
  if input is null return string NULL (without quotes)

  sqlf($string)
  does: escape a string used as sql query field name
  input: string
  output: `string` (always bounded by `back quotes`)
  if input is null return string NULL (without quotes)
  usage example: sqlRun('SELECT '.sqlf($fieldname).' FROM '.sqlf($tablename).' WHERE '.sqlf($keyname).'='.sqlv($value))

  sqlv($string)
  does: escape a string used as sql query value string
  input: string
  output: 'string' (always bounded by 'single quotes')
  if input is null return string NULL (without quotes)
  usage example: sqlRun('SELECT '.sqlf($fieldname).' FROM '.sqlf($tablename).' WHERE '.sqlf($keyname).'='.sqlv($value))
  note: previously fix4sqlv

  sqln($string)
  does: escape a string used as sql query value numeric
  input: string of a number
  output: string or '00' (the string is always a number)
  notes: when $string is not numeric return '00'
  if input is null return string NULL (without quotes)
  usage example: sqlRun('SELECT `name` FROM `monkeys` WHERE `id`='.sqln($value))

  sqls($string)
  does: escape a string used as sql query search condition
  input: string
  output: 'string' (always bounded by 'single quotes'. % _ are escaped)
  notes: same as sqlv() plus escapes % and _
  if input is null return string NULL (without quotes)

  sqlRun($sql)
  does: execute an sql query
  input: sql query string
  output: sql result
  notes: check sqlError() to know if query went ok
  usage example: if (!sqlRun('SELECT count(*) FROM '.sqlf($tablename))) die("Error: ".sqlError()); else echo "Success";

  sqlError()
  does: return last sql query command error level
  input: none
  output: error code number.
  0 = success

  sqlVar($var)
  does: return an sql variable
  input: sql var name
  output: sql var value
  notes: uses SHOW VARIABLES

  sqlLastQuery()
  does: return last query executed using sqlRun
  input: nothing
  output: sql query string

  sqlLastInsertID()
  does: after INSERT command - return last auto numbered ID
  input: nothing
  output: number

  sqlLastInsert()
  alias to sqlLastInsertID()

  sqlLastID()
  alias to sqlLastInsertID()

  sqlFoundRows()
  does: return all rows found with last query's WHERE condition,
  when LIMIT is ignored
  input: nothing.
  but you must state SELECT SQL_CALC_FOUND_ROWS in your query.
  output: number, or null

  sqlAffectedRows()
  does: return number of affected rows of last query
  input: nothing
  output: number

  db_connect($dbdata,$die='db connect error. please notify the administrator.')
  does: Connect to Database
  input:
  $dbdata associative array stracture:
  type: type of database. values: mysql
  host,user,pass: database connection parameters, when connection fail try localhost.
  name: optional name of database to select. when database is missing try to create it.
  charset: optional set vars character_set_connection _client _database
  collation: optional set vars collation_connection _client _server

  $dbdata can also be an array of $dbdata objects.
  in that case the function will try to connect to each of the databases
  one after the other, until success.

  $die: can be false or string.
  by default: string "db connect error. please notify the administrator."
  false or empty: on fail the function will returns false.
  string: on fail php process will be killed by calling die($die).
  output: true/false
  can also kill php script (see $die parameter)

  **** html fixes (escape) ///////////////////

  fix4url($string)
  does: escape a string meant for url
  input: string
  output: url encoded string

  fix4html1($string)
  does: escape a string meant for html value bounded by single quotes ''
  input: string
  output: string
  ' are replaced with &#39;

  fix4html2($string)
  does: escape a string meant for html value bounded by double quotes ""
  input: string
  output: string
  " are replaced with &quot;

  fix4html3($string)
  does: escape a string meant for html value (length not limited)
  input: string
  output: string
  < are replaced with &lt;

  fix4html4($string_or_array, $maxchars = 50)
  does: Escape a string or array and prepare it to html view. I
        If string longer than $maxchars it will be added with ...
  input: string
  output: string
  < are replaced with &lt;
  Can handle arrays (using var_export)

  fix4js1($string)
  does: escape a string meant for javascript value bounded by 'single quotes'
  input: string
  output: string
  \ are replaced with \\
  ' are replaced with \'

  fix4js2($string)
  does: escape a string meant for javascript value bounded by "double quotes"
  input: string
  output: string
  \ are replaced with \\
  " are replaced with \"

 **** php5 functions on php4 ****
  microtime_float() - same as microtime(true) on php5
  getmicrotime() - alias to microtime_float()
  stripos()  - case insensitive strpos()
  strripos() - case insensitive strrpos()
  str_ireplace() - case insensitive str_replace(). its not perfect - always return lowercase!


***********************************************
 */
// taken from http://stackoverflow.com/a/11495941
function is_indexed_array(&$arr) {
  for (reset($arr); is_int(key($arr)); next($arr));
  return is_null(key($arr));
}
function is_sequential_array(&$arr, $base = 0) {
  for (reset($arr), $base = (int) $base; key($arr) === $base++; next($arr));
  return is_null(key($arr));
}
function is_assoc(&$arr) {
  for (reset($arr), $base = (int) $base; key($arr) === $base++; next($arr));
  return !is_null(key($arr));
}


function make_seed() {
	list($usec, $sec) = explode(' ', microtime());
	return (float) $sec + ((float) $usec * 100000);
}

mt_srand(make_seed());
srand(make_seed());

////////////////// add missing functions stripos strripos str_ireplace /////////////////////

//// fix magic quotes damage (taken from php.net)
function stripslashes_deep($value) {
	$value = is_array($value) ?
		   array_map('stripslashes_deep', $value) :
		   stripslashes($value);

	return $value;
}

// unescape POST string escaped for '"\
function undo_magic_quotes($force = false) { //old name: oh_no_unescape_post()
	global $undo_magic_quotes_done;
	if ($force || (get_magic_quotes_gpc() && empty($undo_magic_quotes_done))) {
		$undo_magic_quotes_done = true;
		$_POST = array_map('stripslashes_deep', $_POST);
		$_GET = array_map('stripslashes_deep', $_GET);
		$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
		$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
	}
}

// declare microtime_float according to php5 or php4
if (function_exists('stripos')) { // php5

	function microtime_float() {
		return microtime(true);
	}

} else {

	function microtime_float() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float) $usec + (float) $sec);
	}

}

function getmicrotime() {
	return microtime_float();
}

if (!function_exists('br2nl')) {

	function br2nl($string) {
		$str = str_replace("\r\n", "\n", $string);
		return preg_replace('/\<br(\s*)?\/?\>(\n)?/i', "\n", $str);
	}

}

// declaring fnmatch: (doesnt exists on windows hosting, only on posix
if (!function_exists('fnmatch')) {

	function fnmatch($pattern, $string) {
		return preg_match("#^" . strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.')) . "$#i", $string);
	}

}
// declaring stripos: (doesnt exists as for php version 4.3.4) it should be added in php5
if (!function_exists('stripos')) {

	function stripos($haystack, $needle, $offset = 0) {
		return strpos(strtolower($haystack), strtolower($needle), $offset);
	}

}
// declaring strripos: (doesnt exists as for php version 4.3.4) it should be added in php5
if (!function_exists('strripos')) {

	function strripos($haystack, $needle, $offset = 0) {
		return strrpos(strtolower($haystack), strtolower($needle), $offset);
	}

}
// declaring strripos: (doesnt exists as for php version 4.3.4) it should be added in php5
// its not perfect - always return lowercased string!
// TODO: keep original casing
if (!function_exists('str_ireplace')) {

	function str_ireplace($haystack, $needle, $subject, $count = null) {
		if ($count === null) {
			return str_replace(strtolower($haystack), $needle, strtolower($subject));
		} else {
			return str_replace(strtolower($haystack), $needle, strtolower($subject), $count);
		}
	}

}

// redirect to a url and die
function jsredirect($to) {
	if (!headers_sent()) {
		header("Location: $to");
		exit;
	} else {
		echo "
    <script type='text/javascript'>
      document.location.href=\"" . fix4js2($to) . "\";
    </script>";
	}
}

// recreate current page link, with different GET accoarding to $newget
function reGet($newget = null, $url = null) {
	global $reGetParams;
	if ($newget === null)
		$newget = array();
	if ($url === null && !empty($reGetParams) && !empty($reGetParams['url']))
		$url = $reGetParams['url'];
	if ($url === null)
		$url = preg_replace('/\?.*$/','',$_SERVER['REQUEST_URI']);

	$get = $_GET;
	foreach ($newget as $k => $v) {
		if ($v === null)
			unset($get[$k]);
		else
			$get[$k] = $v;
	}
	$url = preg_replace('/\?.*$/','',$url) . '?' . http_build_query($get);

	return $url;
}

if (!function_exists('headerReload')) {
	function headerReload() {
		header("Location: ".preg_replace('/\?.*$/','',$_SERVER['REQUEST_URI']).'?'.http_build_query($_GET));
		exit;
	}
}

// return a,b...z,aa,ab,ac...az,ba,bb...zz,aaa,aab...,aaz,aba,...zzz...
function decabc($i) {
	$s = '';
	$s.=chr(ord('a') + ($i % 26));
	while ($i >= 26) {
		$i = floor($i / 26) - 1;
		$s = chr(ord('a') + ($i % 26)) . $s;
	}
	return $s;
}

// return a lower cased random set of a-z letters
function randstring($length, $chars = 'abcdefghijklmnopqrstuvwxyz') {
	$z = strlen($chars) - 1;
	for ($s = ''; $length > 0; $length--) {
		$s.=$chars{mt_rand(0, $z)};
	}
	return $s;
}

//////////////// safe-sorting hebrew - @@ remove this?
// hebsortby() - return an english string represents the correct
// order of the hebrew string.
// Attention - mixing heb with eng letters would cause troubles.
/*
  function hebsortby($heb){
  static $alef; $alef=ord('א');
  static $taf;  $taf =ord('ת');
  static $letters=array(
  // א I ב I ג I ד I ה I ו I ז I ח I ט I י I ך I כ I ל I ם I מ I ן I נ I ס I ע I ף I פ I ץ I צ I ק I ר I ש I ת
  'A','B','C','D','E','F','G','H','I','J','K','K','L','M','M','N','N','O','P','Q','Q','R','R','S','T','U','V');
  $return='';
  for ($i=0;$i<strlen($heb);$i++){
  $l=ord($heb{$i});
  if ($l>=$alef && $l<=$taf){
  $return.=$letters[$l-$alef];
  } else {
  $return.=$heb[$i];
  }
  }
  return $return;
  }
  // backward compatibility - just in case... it also fixes final letters םןךףץ
  function hebsortby2heb($hebsortby){
  static $letters=array(
  'א','ב','ג','ד','ה','ו','ז','ח','ט','י','כ','ל','מ','נ','ס','ע','פ','צ','ק','ר','ש','ת');
  //'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V');
  static $a; $a=ord('A');
  static $z; $z=ord('V');
  $return='';
  for ($i=0;$i<strlen($hebsortby);$i++){
  $l=ord($hebsortby{$i});
  if ($l>=$a && $l<=$z){
  $return.=$letters[$l-$a];
  } else {
  $return.=$heb[$i]; // @@ TODO: Fixed - there's no such thing as $heb
  }
  }

  // fix ןםףץך
  static $alef; $alef=ord('א');
  static $taf;  $taf =ord('ת');
  for ($i=0;$i<256;$i++){
  if ($i<$alef || $i>$taf) {  // any letter other then alef-taf cause the previous heb letter to be final.
  $c=chr($i);
  $return=str_replace('נ'.$c,'ן'.$c,$return);
  $return=str_replace('כ'.$c,'ך'.$c,$return);
  $return=str_replace('פ'.$c,'ף'.$c,$return);
  $return=str_replace('צ'.$c,'ץ'.$c,$return);
  $return=str_replace('מ'.$c,'ם'.$c,$return);
  }
  }
  $return=substr($return,0,strlen($return)-1); // remove the ' ' we just added
  return $return;
  } */

/////////////////////////////////////
// return 1/0 for an html form checkbox input.
// you can directly give a value, or give a key and an array
// example: if (chkbox($fname,$_POST))
function chkbox($value, $arr = null) {
	if (is_array($arr)) {
		if (!isset($arr[$value]))
			return null;
		$value = $arr[$value];
	}
	if (!isset($value))
		return null;
	if ($value == 'on' || $value == -1 || $value == 1 || $value == 'true' || $value == true || $value == 'checked' || $value == 'selected')
		return true;
	return false;
}


// Return true or false for a standart given string,
// or $default when string is unrecognized or empty. Default=null by default.
function chkBool($d, $default=null) {
	if (is_string($d)) {
		$d=strtolower($d);
		if ($d==='1'||$d==='true'||$d==='on'||$d==='yes'||$d==='y'||$d==='checked'||$d==='check'||$d==='select'||$d==='selected'||$d==='v'||$d==='+')
			return true;
		elseif ($d==='0'||$d==='false'||$d==='off'||$d==='no'||$d==='n'||$d==='not'||$d==='unchecked'||$d==='uncheck'||$d==='x'||$d==='-')
			return false;
	} else {
		if ($d === true || $d === 1 || $d === -1)
			return true;
		elseif ($d === false || $d === 0)
			return false;
	}
	return $default;
}

// process simple templating system -- replace all occurances of $key in $template with $values[$key]
// keys are case sensitive
function tmplsimple($template, $values, $prekey = '$', $postkey = '') {
	foreach ($values as $k => $v) {
		$template = str_replace($prekey . $k . $postkey, $v, $template);
	}
	return $template;
}

////////////////// String functions ////////////////////
// Return false when there's a char in $str not listed in $valid
function validateWhitehat($str, $valid) {
	if (is_array($valid))
		$valid = implode('', $valid);
	for ($i = 0; $i < strlen($str); $i++) {
		if (strpos($valid, $str{$i}) === false)
			return false;
	}
	return true;
}

// Return $str without all the chars not listed in $valid
function removeWhitehat($str, $valid) {
	if (is_array($valid))
		$valid = implode('', $valid);
	$ret = '';
	$valid = "$valid"; // convert to string
	for ($i = 0; $i < strlen($str); $i++)
		if (strrpos($valid, '' . $str{$i}) !== FALSE)
			$ret.=$str{$i};
	return $ret;
}

// Return $str without all the chars listed in $invalid
function removeBlackhat($str, $invalid) {
	if (is_array($invalid))
		$invalid = implode('', $invalid);
	$ret = '';
	$invalid = "$invalid"; // convert to string
	for ($i = 0; $i < strlen($str); $i++)
		if (strrpos($invalid, '' . $str{$i}) === FALSE)
			$ret.=$str{$i};
	return $ret;
}

///////////////////// Get/Post without the need of using isset - if it wasnt set returns null //////////
function Get($key) {  // return null if key not found in $_GET
	if (array_key_exists($key, $_GET)) {
		return trim($_GET[$key]);
	} else {
		return null;
	}
}

function Post($key) {  // return null if key not found in $_POST
	if (array_key_exists($key, $_POST)) {
		return trim($_POST[$key]);
	} else {
		return null;
	}
}

function Session($key) {  // return null if key not found in $_SESSION
	if (array_key_exists($key, $_SESSION)) {
		return trim($_SESSION[$key]);
	} else {
		return null;
	}
}

///////////////////// remember website vars in $_SERVER //////////////////
global $websitename;
if ($websitename == '' && !empty($_SERVER['HTTP_HOST']))
	$websitename = $_SERVER['HTTP_HOST'];

// set a var
function sitevarSet($name, $value) {
	global $websitename;
	if (!isset($_SERVER[$websitename . '_vars']))
		$_SERVER[$websitename . '_vars'] = array();
	$_SERVER[$websitename . '_vars'][$name] = $value;
}

// get a var
function sitevarGet($name) {
	global $websitename;
	if (!isset($_SERVER[$websitename . '_vars']))
		$_SERVER[$websitename . '_vars'] = array();
	if (!isset($_SERVER[$websitename . '_vars'][$name])) {
		if (defined('DEBUG') && DEBUG) echo "no such sitevar $name";
		return false;
	}
	return $_SERVER[$websitename . '_vars'][$name];
}

// set a var reference
function sitevarSetPtr($name, &$value) {
	global $websitename;
	if (!isset($_SERVER[$websitename . '_vars']))
		$_SERVER[$websitename . '_vars'] = array();
	$_SERVER[$websitename . '_vars'][$name] = &$value;
}

// set a ptr to a reference of the site var
function sitevarGetPtr($name, &$ptr) {
	global $websitename;
	if (!isset($_SERVER[$websitename . '_vars']))
		$_SERVER[$websitename . '_vars'] = array();
	if (!isset($_SERVER[$websitename . '_vars'][$name])) {
		if (defined('DEBUG') && DEBUG) echo "no such sitevar $name";
		return false;
	}
	return $ptr = &$_SERVER[$websitename . '_vars'][$name];
}

// check if a site var was set
function sitevarExists($name) {
	global $websitename;
	if (!isset($_SERVER[$websitename . '_vars']))
		$_SERVER[$websitename . '_vars'] = array();
	return isset($_SERVER[$websitename . '_vars'][$name]);
}

/////////////////// Sql Functions
global $sql_last_error, $sql_last_query, $sql_last_insert_id, $sql_found_rows,$sql_print_errors,$sql_print_every_query;

function sqlGetType() {
	return 'mysql';
}

function sqlVar($var) {
	$res = mysql_query("SHOW VARIABLES LIKE " . sqls($var) . ";");
	if ($res) {
		if ($row = mysql_fetch_row($res)) {
			return $row[0];
		}
	}
	return null;
}

function sqlGetVersion() {
	$res = mysql_query("SELECT VERSION();");
	$res = mysql_fetch_row($res);
	$res = $res[0];
	return $res;
}

function sqlremovestrings($sqlexpression,$replace='') {
	// TODO: support ANSI_QUOTES mode
	$out=''; // a strings-free expression to validate
	$instr=false; // are we currently inside a string?
	for($i=0;$i<strlen($sqlexpression);$i++) {
		$c=$sqlexpression{$i}; // get current char
		if ($c=="'") { // start/end of a string
			$instr = !$instr;
			if (!$instr) $out.=$replace; // just finished a string, add the replacement to output
		} else {
			if ($c=="\\") { // escape character
				if ($instr)
					$i++; // skip next char
			}
			if (!$instr) $out.=$c;
		}
	}
	if ($instr) // unclosed string
		return false;
	return $out;
}

function sqlvalidate($sqlexpression,&$error='') {
	/////// first remove all strings to make life easier //////
	$sqlexpression=sqlremovestrings($sqlexpression);
	if ($sqlexpression===false)  // unclosed string
		return ($error='11: Unclosed string') && false;
	/////// validate parantheses
	$brackets=preg_replace('@[^\(\)]+@','',$sqlexpression); // leave only parantheses
	while (strpos($brackets,'()')!==false) {
		$brackets=str_replace('()','',$brackets); // slowly remove all matching parantheses one by one (pair by pair)
	}
	if (strlen($brackets)>0) // a bracket had no matching bracket!
		return ($error='21: No matching bracket') && false;

	////// look for invalid table/field names characters
	// TODO: support ANSI_QUOTES mode
	$instr=false; // are we currently inside a backtick?
	$prevc=''; // previous char
	for($i=0;$i<strlen($sqlexpression);$i++) {
		$c=$sqlexpression{$i}; // get current char
		if ($c=="`") { // start/end of a string
			$instr = !$instr;
			// * Database, table, and column names cannot end with space characters.
			if ($instr==false && $prevc==' ')
				return ($error='31: Object name cannot end with a space (`xxx `)') && false;
			if ($prevc=='`' && !$instr) // detect `` outside a string (because `bla``blo` is ok, but an empty `` is not)
				return ($error='32: Object name cannot be empty (``)') && false;
		} else {
			// * Database and table names cannot contain “/”, “\”, “.”, or characters that are not permitted in file names.
			if ($instr && ((strpos("/\\.,\n\r\0",$c)!==false)))
				return ($error='33: Object name cannot contain the character #'.ord($c)) && false;
			// Starting with a space is not a good idea neither
			if ($instr && $c==' ' && $prevc=='`')
				return ($error='34: Object name cannot start with a space (` xxx`)') && false;
		}
		$prevc=$c;
	}
	if ($instr)
		return ($error='30: Object name not closed (`xxx)') && false;
	$error='';
	return true;
}

// return true if the expression a valid field/table name that can be `backquoted`
// false for empty string or any char not a-z or underscore
// see also: sqlvalidate($str)
function sqlname($str) {
	return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/',$str);
}
// alias to mysql_real_escape_string() - fix quotes and other chars for sql
function sqle($str) {
	if ($str === null) return 'NULL';
	if (is_long($str)) return $str;
	return mysql_real_escape_string($str);
}
 // fix quotes for sql fields and tables names. removes extra `back quotes`
function sqlf($str) {
	if ($str===null || $str===false || $str===true) return 'NULL';
	if ($str==='') return '';
	return "`" . mysql_real_escape_string(str_replace('`', '', $str)) . "`";
}
// fix quotes for sql values. null returns NULL, numbers are left alone and everything else is escaped and added 'single quotes'
function sqlv($str) {
	if ($str === null) return 'NULL';
	if (is_long($str)) return $str;
	return "'" . mysql_real_escape_string($str) . "'";
}
// fix quotes for sql numeric values. null and empty string returns NULL, numbers are left alone, others changed to default OR triggers an error and returns '00'
function sqln($str,$default='00') {
	if ($str === null) return 'NULL';
	if (strtolower($str) == 'null' || $str==='' || $str === null || $str === "\0" || $str === "\\0") return 'NULL';
	if (!is_numeric($str)) {
		if (func_num_args()==1) trigger_error("sql-num error: not a number", E_USER_WARNING);
		return $default;
	}
	return $str;
}
 // fix quotes for sql search. same as sqlv(), plus escapes \ % _
 // NOTE: Not adding 'single quotes'
function sqls($str) {
	if ($str === null) return 'NULL';
	if ($str{0}=="'" && $str{count($str)-1}=="'") $str=substr($str,1,count($str)-1);
	return mysql_real_escape_string(str_replace(array('\\','_', '%'), array('\\','\_', '\%'), $str));
}

function sqlSetCollation($collation, $charset) {
	if (!empty($collation)) {
		$res = mysql_query("SHOW VARIABLES LIKE '%collation\_%';");
		while ($row = mysql_fetch_row($res)) {
			mysql_query("SET $row[0]='$collation';");
		}
	}
	if (!empty($charset)) {
		$res = mysql_query("SHOW VARIABLES LIKE '%character\_set\_%';");
		while ($row = mysql_fetch_row($res)) {
			mysql_query("SET $row[0]='$charset';");
		}
		mysql_query("SET CHARACTER SET '$charset';");
	}
	return true;
}

function sqlRun($sql) {
	global $sql_last_error, $sql_last_query, $sql_last_insert_id, $sql_found_rows, $sql_print_every_query, $sql_dont_run_queries,$sql_print_errors;

	if ($sql_print_every_query) echo "<!--[ $sql ]-->";
	if ($sql_dont_run_queries) return null;

	// Security: Allow only 1 sql sentence each time, and do not allow -- comments
	$sql = preg_replace('/^\\s|[\\s;]$/','',$sql);
	if ($sql == '') {
		$sql_last_error="sqlRun called with empty query (" . var_export($sql, 1) . ")";
		if ($sql_print_errors) print "<code>$sql_last_error</code>";
		return false;
	}
	// search for ; --
	$temp = sqlremovestrings($sql); // remove all strings for the test
	if (strpos($temp, '--') !== FALSE || strpos($temp, ';') !== FALSE) {
		$sql_last_error="sqlRun Security: ; or -- found. sql query not called.";
		if ($sql_print_errors) print "<code>$sql_last_error</code>";
		return false;
	}

	$sql_last_query = $sql;
	$result = mysql_query($sql);
	$sql_last_error = mysql_error();
	if ($sql_print_errors && $sql_last_error!='') echo "<code>$sql_last_error</code>";

	if (strtolower(substr($sql, 0, 6)) == 'insert' || strtolower(substr($sql, 0, 7)) == 'replace')
		$sql_last_insert_id = mysql_insert_id();

	if (stripos($sql, "SQL_CALC_FOUND_ROWS") !== false) {
		$sql_found_rows = mysql_query("SELECT FOUND_ROWS()");
		$sql_found_rows = mysql_fetch_row($sql_found_rows);
		$sql_found_rows = $sql_found_rows[0];
	} else {
		$sql_found_rows = null;
	}
	return $result;
}

function sqlError() {
	global $sql_last_error;
	return $sql_last_error;
}

function sqlLastQuery() {
	global $sql_last_query;
	return $sql_last_query;
}

function sqlLastInsertID() {
	global $sql_last_insert_id;
	return $sql_last_insert_id;
}

function sqlLastInsert() {
	global $sql_last_insert_id;
	return $sql_last_insert_id;
}

function sqlLastID() {
	global $sql_last_insert_id;
	return $sql_last_insert_id;
}

function sqlFoundRows() {
	global $sql_found_rows;
	return $sql_found_rows;
}

function sqlAffectedRows() {
	return mysql_affected_rows();
}

function db_connect($dbdata, $die = 'db connect error. please notify the administrator.') {
	if (is_array($dbdata) && count($dbdata) > 0 && (!array_key_exists('name', $dbdata))) {
		// try each and every dbdata settings in array
		for ($i = 0; $i < count($dbdata); $i++) {
			if (@db_connect($dbdata[$i], false))
				return true;
		}
		if (!empty($die))
			die($die);
		return false;
	} else {

		if (array_key_exists('type', $dbdata) && $dbdata['type'] != '' && $dbdata['type'] != "mysql") {
			trigger_error("Unhandled server type=$dbdata[type] (only mysql currently supported)", E_USER_WARNING);
			if (!empty($die))
				die($die);
			return false;
		}

		if (!mysql_connect($dbdata['host'], $dbdata['user'], $dbdata['pass'])) {
			trigger_error("Failed to Connect to MySql Server: host=$dbdata[host] user=$dbdata[user] pass #" . (strlen($dbdata['pass'])), E_USER_WARNING);
			if (!empty($die))
				die($die);
			return false;
		}
		if (!empty($dbdata['collation']) || !empty($dbdata['collation'])) {
			sqlSetCollation($dbdata['collation'], $dbdata['charset']);
		}
		if (!empty($dbdata['name'])) {
			if (!mysql_select_db($dbdata['name'])) {
				// failed to select so try to create it
				if (sqlRun("CREATE DATABASE `$dbdata[name]`")) {
					trigger_error("DB Created $dbdata[name]", E_USER_NOTICE);
				} else {
					trigger_error("Failed to Create DB $dbdata[name]", E_USER_WARNING);
				}
				if (!mysql_select_db($dbdata['name'])) {
					trigger_error("Failed to Select DB $dbdata[name]", E_USER_WARNING);
					if (!empty($die))
						die($die);
					return false;
				}
			}
		}

		return true;
	}
}

////// fill zeros before a number
function zeros($input, $length) {
	return str_pad($input, $length, '0', STR_PAD_LEFT);    // its ok, str_pad doesnt truncate longer numbers
}

// start counting numbers until a file name is available. return base file name (without $dir)
function autofilename($dir, $name, $footer, $zeros = 4, $startfrom = 1) {
	clearstatcache();  // must do it so the info would be current and not cached
	$i = $startfrom;
	while (file_exists($dir . $name . zeros($i, $zeros) . $footer))
		$i++;
	return $name . zeros($i, $zeros) . $footer;
}

// get the extension part of the filename
function getExtension($filename) {
	$path = pathinfo($filename);
	return $path['extension'];
}

// return the number of files found fitting to the numbering and format
// unlike glob(), it stops when the series is stopped.
// for example, a bunch of files from 01 to 07, with number 05 missing, would return 4.
// thus, if the count starting from 0 - the function would return 0.
// if you just want to know how many files matching the pettern, use count(glob()).
function count_autofilename($dir, $name, $footer, $zeros = 4, $startfrom = 1) {
	clearstatcache();  // must do it so the info would be current and not cached
	$i = $startfrom;
	while (file_exists($dir . $name . zeros($i, $zeros) . $footer))
		$i++;
	return $i - $startfrom;
}

// insert symbols into a time stamp string
// 20030201123456 --> 2003/02/01 12:34:56
function timestamp2str($ts) {
	$s = '';
	$s.= substr($ts, 0, 4);  // year
	$s.='-' . substr($ts, 4, 2);  // month
	$s.='-' . substr($ts, 6, 2);  // day
	$s.=' ' . substr($ts, 8, 2);  // hour
	$s.=':' . substr($ts, 10, 2);  // minute
	$s.=':' . substr($ts, 12, 2);  // second
	return $s;
}

/////////// fixes - fix ' " quotes and such

function fix4url($str) {
	return urlencode($str);
}

function fix4html1($str) {	  // fix quotes for html - values bounded by '
	return str_replace("'", '&#39;', $str);
	//return htmlspecialchars($str,ENT_QUOTES); // not 100%
}

function fix4html2($str) {	  // fix quotes for html - values bounded by "
	return str_replace('"', '$quot;', $str);
	//return htmlspecialchars($str,ENT_COMPAT); // not 100%
}

function fix4html3($str) {	  // fix <> for html - replace < with &lt;
	return str_replace('<', '&lt;', $str);
	//return htmlspecialchars($str); // not 100%
}

// make a string short (with ...) and without html codes and without line breaks
function fix4html4($val, $maxchars = 50) {
	if (is_array($val))
		if (is_assoc($val))	$val=preg_replace('/\barray (/','(',var_export($val,true));
		else $val='('.implode(',',$val).')';

	$val = substr($val, 0, $maxchars);
	$val = str_replace(array("\n", "\r"), ' ', $val);
	if (strlen($val) > $maxchars) $val = substr($val, 0, $maxchars - 3) . '...';
	return str_replace('<', '&lt;', $val);
}

function fix4js1($str) {	 // fix quotes for javascript - values bounded by '
	$str = str_replace("\\", "\\\\", $str);
	$str = str_replace("'", "\\'", $str);
	return $str;
}

function fix4js2($str) {	 // fix quotes for javascript - values bounded by "
	$str = str_replace("\\", "\\\\", $str);
	$str = str_replace('"', '\\"', $str);
	return $str;
}

/////// Auto Buttons, Anchors

function oria_butlink($label, $href, $target = '', $params = '', $class = 'but') {
	// buttons method:
	//    if (strtolower($target)=='_current') $target='';
	//    return button($label,gohref($href,$win_name),$params,$class);
	// links method:
	return oria_link($label, $href, $target, $params, $class);
}

function oria_button($label, $cmd, $params = '', $class = 'but') {
	if ($class != '')
		$class = "class='$class'";
	return "<button $class onClick=\"{$cmd}\" $params>$label</button>";
}

function oria_link($label, $cmd, $target = '', $params = '', $class = 'but') {
	if ($class != '')
		$class = " class='$class' ";
	if ($target != '')
		$target = " target='$target' ";
	if (false && strtolower(substr($cmd, 0, 11)) == 'javascript:') { // cancelled cause not working good
		$link = " href='javascript:void(0);' onClick=\"" . fix4html2(substr($cmd, 11)) . "\" ";
	} else {
		$link = " href=\"" . fix4html2($cmd) . "\" ";
	}
	return "<a $class $link $target $params>$label</a>";
}

function gohref($href, $win_name = '') {
	if (strtolower($win_name) == '_current')
		$win_name = '';
	if ($win_name == '') {
		return "document.location.href='" . fix4js1($href) . "';";
	} else {
		return "window.open('" . fix4js1($href) . "','" . fix4js1($win_name) . "');";
	}
}

function jumpto($href, $do_jump, $msg = '') {
	if ($msg == '')
		$msg = _('Continue');
	if ($do_jump) {
		if (defined('DEBUG') && DEBUG) {
			echo oria_butlink("If we weren't on debug-mode, i'd jump here", $href);
		} else {
			echo "<script type='text/javascript'>document.location.href='" . fix4js1($href) . "';</script>";
		}
	} else {
		echo oria_butlink($msg, $href);
	}
}

function oria_jumpback($times, $do_jump) {
	if ($do_jump) {
		if (defined('DEBUG')&&DEBUG) {
			echo oria_button("If we weren't on debug-mode, i'd jump back $times", "window.history.go(-{$times});");
		} else {
			echo "<script type='text/javascript'>window.history.go(-{$times});</script>";
		}
	} else {
		echo oria_button(_('Go Back'), "window.history.go(-{$times});");
	}
}

function checkbackto($do_jump, $msg = '') {
	if ($msg == '')
		$msg = _('Continue');
	$backto = Post('backto');
	if ($backto == '')
		$backto = Get('backto');
	if ($backto != '') {
		if ($do_jump)
			echo oria_jumpto($backto, $msg);
		else
			echo oria_butlink($msg, $backto);
	}
}
