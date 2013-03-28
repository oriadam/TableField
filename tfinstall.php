<?php
define('FILENAME',__DIR__.'/custom/dbconfig.php');
define('FLAGNAME',__DIR__.'/custom/install_done');
define('HTACCESSFILE',__DIR__.'/custom/.htaccess');
define('HTACCESSCONTENT','deny from all');
define('CONFIGURATION','tfconfigure.php');

global $tf;
if (!is_array($tf)) $tf=array();
error_reporting(E_ALL ^ E_NOTICE);

if (!function_exists('fatal')) {
	function fatal($msg) {
		die($msg);
	}
}

// Set \ -> \\ and ' -> \'
function fixstr($str) {
	$str = str_replace('\\', '\\\\', $str);
	$str = str_replace("'", "\\'", $str);
	return $str;
}

$errors = array();
////////////////////////////////////////////////////////////////
// Prevent access to custom folder
if (!file_exists(HTACCESSFILE)) {
	file_put_contents(HTACCESSFILE,HTACCESSCONTENT);
}

///////////////////////////////////////////////////////////////////////////////////////////////
// Process and validate data from post
if (!file_exists(FLAGNAME) && count($_POST)) {

	// Validate empty fields
	if (empty($_POST['db_user']))
		$errors['db_user'] = 'Please enter DB Connection User';
	if (empty($_POST['db_pass']))
		$errors['db_pass'] = 'Please enter DB Connection Password';
	if (empty($_POST['db_host']))
		$errors['db_host'] = 'Please enter DB Connection Host';
	if (empty($_POST['db_name']))
		$errors['db_name'] = 'Please enter the Database Name';
	if (empty($_POST['db_collation']))
		$errors['db_collation'] = 'Please select DB Collation';
	if (empty($_POST['db_charset']))
		$errors['db_charset'] = 'Please select DB Collation';
	//if (empty($_POST['db_pre'      ])) $errors['db_pre'      ]='If you are using several TF on same the site, please select a unique short prefix (usually tf_)';
	if (empty($_POST['tbl_info']))
		$errors['tbl_info'] = 'Please enter main TF info table name. Default tf_info';
	if (empty($_POST['tbl_users']))
		$errors['tbl_users'] = 'Please enter main TF users table name. Default tf_users';
	if (empty($_POST['tbl_log']))
		$errors['tbl_log'] = 'Please enter main TF log table name. Default tf_log';
	if (empty($_POST['tbl_meta']))
		$errors['tbl_meta'] = 'Please enter main TF information meta table name. Default tf_meta';
	if (empty($_POST['root_user']))
		$errors['root_user'] = 'Please set a user name for the super user. Default `root`';
	if (empty($_POST['root_pass']))
		$errors['root_pass'] = 'Please set a password for the super user';
	if (empty($_POST['root_name']))
		$errors['root_name'] = 'Please enter your real name';
	if (empty($_POST['root_email']))
		$errors['root_email'] = 'Please enter your email address (to allow super user password reset)';

	// Validate using regular expressions
	$v_name = '/[a-zA-Z0-9_]/';
	$e_name = 'Please use only Abc and underscores.';
	$vec = '0-9a-zA-Z.!#$%&*+-=?^_`{|}~'; // valid email chars
	$v_email = "/[$vec]+\\@[$vec]+\\.[$vec][$vec]+/";

	if (!isset($errors['db_pre']) && !empty($_POST['db_pre']) && !preg_match($v_name, $_POST['db_pre']))
		$errors['db_pre'] = $e_name;
	if (!isset($errors['db_user']) && !preg_match($v_name, $_POST['db_user']))
		$errors['db_user'] = $e_name;
	if (!isset($errors['db_host']) && !preg_match($v_name, $_POST['db_host']))
		$errors['db_host'] = $e_name;
	if (!isset($errors['db_name']) && !preg_match($v_name, $_POST['db_name']))
		$errors['db_name'] = $e_name;
	if (!isset($errors['db_collation']) && !preg_match($v_name, $_POST['db_collation']))
		$errors['db_collation'] = $e_name;
	if (!isset($errors['db_charset']) && !preg_match($v_name, $_POST['db_charset']))
		$errors['db_charset'] = $e_name;
	if (!isset($errors['tbl_info']) && !preg_match($v_name, $_POST['tbl_info']))
		$errors['tbl_info'] = $e_name;
	if (!isset($errors['tbl_users']) && !preg_match($v_name, $_POST['tbl_users']))
		$errors['tbl_users'] = $e_name;
	if (!isset($errors['tbl_log']) && !preg_match($v_name, $_POST['tbl_log']))
		$errors['tbl_log'] = $e_name;
	if (!isset($errors['tbl_meta']) && !preg_match($v_name, $_POST['tbl_meta']))
		$errors['tbl_meta'] = $e_name;
	if (!isset($errors['root_user']) && !preg_match($v_name, $_POST['root_user']))
		$errors['root_user'] = $e_name;
	if (!isset($errors['root_pass']) && strlen($_POST['root_pass']) < 6)
		$errors['root_pass'] = 'Please use a longer password';
	if (!isset($errors['root_passV']) && $_POST['root_pass'] != $_POST['root_passV'])
		$errors['root_passV'] = 'The password validation failed. Please type it again.';
	if (!isset($errors['root_email']) && !preg_match($v_email, $_POST['root_email']))
		$errors['root_email'] = 'Your email seems invalid. Please double check it';

	// Test DB Connection
	if (count($errors) == 0) {
		if (mysql_connect($_POST['db_host'], $_POST['db_user'], $_POST['db_pass'])) {
			if (mysql_select_db($_POST['db_name'])) {
				$tf['db.ok'] = true; // Hooray, DB connected and selected!
			} else {
				// DB Name does not exist - try to create it
				$res = mysql_query("CREATE DATABASE `" . $_POST['db_name'] . "` DEFAULT CHARACTER SET " . $_POST['db_charset'] . " COLLATE " . $_POST['db_collation']);
				if (mysql_select_db($_POST['db_name'])) {
					$tf['db.ok'] = true; // Hooray, DB connected and selected!
				}
			}
		}
	}

	if (!$tf['db.ok']) {
		$errors['db'] = 'Cannot connect to DB! Please check your DB connection settings and try again';
	}

	// All OK? Start next step - Create dbconfig file
	if (!count($errors)) {
		$rootpassmd5 = md5($_POST['root_pass']);
		if (file_exists(FILENAME)) {
			unlink(FILENAME);
		}
		if (file_exists(FILENAME)) {
			$errors['fatal'] = 'Cannot remove previously created file ('.FILENAME.') Please remove it yourself, make sure it has write permissions then refresh to resend data';
		} else {

			// Generate dbconfig code
			$str = '<' . '?' . 'php
global $tf;
';
			$str.="\n\$tf['db.user'     ]='" . fixstr($_POST['db_user'])."';";
			$str.="\n\$tf['db.pass'     ]='" . fixstr($_POST['db_pass'])."';";
			$str.="\n\$tf['db.host'     ]='" . fixstr($_POST['db_host'])."';";
			$str.="\n\$tf['db.name'     ]='" . fixstr($_POST['db_name'])."';";
			$str.="\n\$tf['db.collation']='" . fixstr($_POST['db_collation'])."';";
			$str.="\n\$tf['db.charset'  ]='" . fixstr($_POST['db_charset'])."';";
			$str.="\n\$tf['db.pre'      ]='" . fixstr($_POST['db_pre'])."';";
			$str.="\n\$tf['tbl.info'    ]='" . fixstr($_POST['tbl_info'])."';";
			$str.="\n\$tf['tbl.users'   ]='" . fixstr($_POST['tbl_users'])."';";
			$str.="\n\$tf['tbl.log'     ]='" . fixstr($_POST['tbl_log'])."';";
			$str.="\n\$tf['tbl.meta'    ]='" . fixstr($_POST['tbl_meta'])."';";
//			$str.="\n\$tf['root.email']='" . fixstr($_POST['root_email'])."';";
			// Write it to a real file
			if (!file_put_contents(FILENAME, $str)) {
				$errors['fatal'] = 'Cannot write to '.FILENAME.' file! Please make sure "custom" folder has write permissions';
			} else {
				echo "<h4 class=text-success>Config file created successfully!</h4>";
				$newdb = str_replace(array(
				    'PUT_DB_USER_HERE',
				    'PUT_DB_PASS_HERE',
				    'PUT_DB_HOST_HERE',
				    'PUT_DB_NAME_HERE',
				    'PUT_DB_COLLATION_HERE',
				    'PUT_DB_CHARSET_HERE',
				    'PUT_DB_PRE_HERE',
				    'PUT_TBL_INFO_HERE',
				    'PUT_TBL_USERS_HERE',
				    'PUT_TBL_LOG_HERE',
				    'PUT_TBL_META_HERE',
				    'PUT_ROOT_USER_HERE',
				    'PUT_ROOT_PASS_HERE',
				    'PUT_ROOT_NAME_HERE',
				    'PUT_ROOT_EMAIL_HERE'), array(
				    $_POST['db_user'],
				    $_POST['db_pass'],
				    $_POST['db_host'],
				    $_POST['db_name'],
				    $_POST['db_collation'],
				    $_POST['db_charset'],
				    $_POST['db_pre'],
				    $_POST['tbl_info'],
				    $_POST['tbl_users'],
				    $_POST['tbl_log'],
				    $_POST['tbl_meta'],
				    $_POST['root_user'],
				    $rootpassmd5,
				    $_POST['root_name'],
				    $_POST['root_email']), file_get_contents(__DIR__.'/inc/newdb.sql'));
				$sqls = explode('--START_QUERY--', $newdb);
				foreach ($sqls as $sql) {
					if (!empty($sql)) {
						mysql_query($sql);
						$err = mysql_error();
						if (!empty($err)) {
							$errors['fatal'].="SQL Error: $err";
						}
					}
				}
			}//no problem writing to tfconfig
		}// no tfconfig already exist problem
	}// no errors
}//post has values

///////////////////////////////////////////////////////////////////////////////
// Test installation completed successfully - auto redirect to configuration page
include(__DIR__.'/inc/tfconfig.php');

if (file_exists(FILENAME) && file_exists(FLAGNAME)) {
	if ($tf['db.ok']) {
		// tfinstall.php is done (forever!) - redirect to config utility
		if (headers_sent()) {
			echo '<h4>Ok, please <a href="'.CONFIGURATION.'">continue to configuration page</a></h4><script>document.location.href="'.CONFIGURATION.'";</script>';
		} else {
			header('Location: '.CONFIGURATION);
		}
		exit;
	} else {
		echo 'Error connecting to DB. Please make sure DB server is running and review '.FILENAME.'. to edit it here please remove '.FLAGNAME;
		exit;
	}
}

if (file_exists(FILENAME) && !file_exists(FLAGNAME) && $tf['db.ok']) {
	$res = mysql_query("SELECT count(*) FROM " . $tf['tbl.info'] . " WHERE 1=1");
	$count = 0;
	if ($res) {
		$row = mysql_fetch_row($res);
		if (!empty($row[0])) {
			$count = $row[0];
		}
	}
	if ($count > 0) {
		// All seems OK - disable tfinstall.php because it finished its work
		touch(FLAGNAME);
		// tfinstall.php is done (forever!) - redirect to config utility
		if (headers_sent()) {
			echo '<h4>Ok, please <a href="'.CONFIGURATION.'">continue to configuration page</a></h4><script>document.location.href="'.CONFIGURATION.'";</script>';
		} else {
			header('Location: '.CONFIGURATION);
		}
		exit;
	} else {
		$errors['fatal']='Connected to DB but could not read TF tables! Please review settings again and check DB integrity';
	}
}

///////////////////////////////////////////////////////////////////
// Generate form
include(__DIR__.'/inc/header.php');
// Set default values into $_POST array
if (!count($_POST)) {
	$_POST = array();
	// set defaults:
	$_POST['db_pre'] = 'tf_';
	$_POST['db_charset'] = 'utf8';
	$_POST['db_collation'] = 'utf8_unicode_ci';
	$_POST['root_user'] = 'root';
}

?>
<img class="img span3" src="tflogo.png">
<div class="span20">
	<h3>Welcome to TableField installation wizard</h3>
	<h4>Please fill in all fields then hit 'Next'</h4>
</div>
<form method=POST class="form-horizontal span20">
	<div class=error><?=@$errors['fatal']?></div>
	<fieldset class="bs-docs-example">
		<span class="bs-docs-example-legend">Database connection settings</span>
		<div class=text-error><?=@$errors['db']?></div>
		<div class=control-group><label><div class=control-label>Database Name    </div><div class=controls><input type=text name='db_name'      value="<?=htmlentities(@$_POST['db_name'])?>"> <span class="text-error"><?=@$errors['db_name']?></div></label></div>
		<div class=control-group><label><div class=control-label>Connection Host  </div><div class=controls><input type=text name='db_host'      value="<?=htmlentities(@$_POST['db_host'])?>"> <span class="text-error"><?=@$errors['db_host']?> <small class=muted>usually <code>localhost</code></small></div></label></div>
		<div class=control-group><label><div class=control-label>Connection User  </div><div class=controls><input type=text name='db_user'      value="<?=htmlentities(@$_POST['db_user'])?>"> <span class="text-error"><?=@$errors['db_user']?></div></label></div>
		<div class=control-group><label><div class=control-label>Password         </div><div class=controls><input type=password name='db_pass'  value="<?=htmlentities(@$_POST['db_pass'])?>"> <span class="text-error"><?=@$errors['db_pass']?></div></label></div>
		<div class=control-group><label><div class=control-label>TF tables prefix </div><div class=controls><input type=text name='db_pre'       value="<?=htmlentities(@$_POST['db_pre'])?>" onchange="setTbl()"> <span class="text-error"><?=@$errors['db_pre']?> <small class=muted>Short lowercased abc-only string which ends with <b>_</b></small>
		</div></label></div>
		<div class="controls">
			<small class="text-warning"><i class="icon-chevron-up"></i> Prefix applies only to TF inner tables</small>
			<u class="b1tn text-small" style="cursor:pointer" onclick="$('#dbtech').slideDown();$(this).css('position','absolute').fadeOut()"><small>Advanced: TableField tables names and charset <i class="icon-chevron-down"></i></small></u>
		</div>
		<div id=dbtech style="display:none">
			<div class=control-group><label><div class=control-label>Charset       </div><div class=controls><input type=text name='db_charset'   value="<?=htmlentities(@$_POST['db_charset'])?>" > <span class="text-error"><?=@$errors['db_charset']?> <small class=muted>usually <code>utf8</code></small></div></label></div>
			<div class=control-group><label><div class=control-label>Collation     </div><div class=controls><input type=text name='db_collation' value="<?=htmlentities(@$_POST['db_collation'])?>" > <span class="text-error"><?=@$errors['db_collation']?> <small class=muted>usually <code>utf8_unicode_ci</code></small></div></label></div>
			<div class=control-group><label><div class=control-label>TF info table </div><div class=controls><input type=text name='tbl_info'     value="<?=htmlentities(@$_POST['tbl_info'])?>" > <span class="text-error"><?=@$errors['tbl_info']?></div></label></div>
			<div class=control-group><label><div class=control-label>TF info meta  </div><div class=controls><input type=text name='tbl_meta'     value="<?=htmlentities(@$_POST['tbl_meta'])?>" > <span class="text-error"><?=@$errors['tbl_meta']?></div></label></div>
			<div class=control-group><label><div class=control-label>TF users table</div><div class=controls><input type=text name='tbl_users'    value="<?=htmlentities(@$_POST['tbl_users'])?>" > <span class="text-error"><?=@$errors['tbl_users']?></div></label></div>
			<div class=control-group><label><div class=control-label>TF log table  </div><div class=controls><input type=text name='tbl_log'      value="<?=htmlentities(@$_POST['tbl_log'])?>" > <span class="text-error"><?=@$errors['tbl_log']?></div></label></div>
		</div>
	</div></fieldset>
	<fieldset class="bs-docs-example">
		<span class="bs-docs-example-legend">TableField super user login details</span>
		<div class=text-error><?=@$errors['root']?></div>
		<div class=control-group><label><div class=control-label>Super user name     </div><div class=controls><input type=text     name='root_user'  value="<?=htmlentities(@$_POST['root_user'])?>" > <span class="text-error"><?=@$errors['root_user']?></div></label></div>
		<div class=control-group><label><div class=control-label>Super user password </div><div class=controls><input type=password name='root_pass'  value=""                   onchange="checkPW()"> <span class="text-error" id='e_root_pass'><?=@$errors['root_pass']?></span></div></label></div>
		<div class=control-group><label><div class=control-label>...and again        </div><div class=controls><input type=password name='root_passV' value=""                   onchange="checkPW()"> <span class="text-error" id='e_root_passV'><?=@$errors['root_passV']?></span></div></label></div>
		<div class=control-group><label><div class=control-label>Super user real name</div><div class=controls><input type=text     name='root_name'  value="<?=htmlentities(@$_POST['root_name'])?>" > <span class="text-error"><?=@$errors['root_name']?></div></label></div>
		<div class=control-group><label><div class=control-label>Super user email    </div><div class=controls><input type=text     name='root_email' value="<?=htmlentities(@$_POST['root_email'])?>"> <span class="text-error"><?=@$errors['root_email']?> <small class=muted>Will be used to reset password and send critical alerts</small></div></label></div>
	</fieldset>
	<fieldset class="bs-docs-example">
		<span class="bs-docs-example-legend">Finish installation</span>
		<input class="btn btn-large controls" type='submit' value='Next'>
	</fieldset>
</form>

<script>
var e=document.forms[0].elements;
function setTbl(){
	var p=e['db_pre'].value;
	e['tbl_info' ].value=p+'info';
	e['tbl_users'].value=p+'users';
	e['tbl_log'  ].value=p+'log';
	e['tbl_meta' ].value=p+'meta';
}
setTbl();
function checkPW () {
	var em=document.getElementById('e_root_pass');
	var eV=document.getElementById('e_root_passV');
	var pm=e['root_pass'].value;
	var pV=e['root_passV'].value;
	if (pm.length>0 && pm.length<6) {
		em.innerHTML = 'Too short';
	} else {
		em.innerHTML = '';
	}
	if (pm!=pV) {
		eV.innerHTML = 'Passwords do not match';
	} else {
		eV.innerHTML = '<b class=text-success>Passwords match</b>';
	}

}
</script>
<?

include(__DIR__.'/inc/footer.php');
