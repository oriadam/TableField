<?php
//*******************//
//  TF configuration //
//*******************//
// Requires super-user 'edit' permission.
// Available field types are:
// boolean   --> true/false radio buttons
// string    --> simple input text field
// text/html --> text area
// numeric/number --> input text field that only allows numbers
// hidden    --> this line will be displayed as html comment
// read only --> will be written as text

if (!empty($_GET['noop'])) {
	session_start();
	header('Content-type: image/png');
	echo 'Keeping it real.';
	exit;
}
error_reporting(E_ALL);

define('HTACCESSFILE',__DIR__.'/custom/.htaccess');
define('HTACCESSCONTENT','deny from all');

global $tf;
$tf = array();

require_once(__DIR__.'/inc/include.php');
if (!function_exists('fatal')) {
	function fatal($msg) {
		die($msg);
	}
}

////////////////////////////////////////////////////
define('FILENAME','custom/tfconfig.php');
define('DOTWORKAROUND','_phpdot_wa_');
define('TRUEVALUE','_TrUE_ValUE_');
define('FALSEVALUE','_FalSE_ValUE_');
define('BACKUPFILE','make_backup_of_settings_file');

define('DIVIDER','-divider-');
define('BOOL','boolean');
define('TEXT','text');
define('STRING','string');
define('NUM','numeric');
define('THEME','theme');
define('READONLY','readonly');
define('LINK','link');


// JQueryUI Themes
// http://jqueryui.com/themeroller
// http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/$theme/jquery-ui.css
$themes=array('base','black-tie','blitzer','cupertino','dark-hive','dot-luv','eggplant','excite-bike','flick','hot-sneaks','humanity','le-frog','mint-choc','overcast','pepper-grinder','redmond','smoothness','south-street','start','sunny','swanky-purse','trontastic','ui-darkness','ui-lightness','vader');
// Bootstrap Themes
// http://bootswatch.com/#gallery
//$themes=array('','Spruce','Superhero','United','Spacelab','Slate','Simplex','Cyborg','Journal','Readable','Cosmo','Cerulean','Amelia','Shamrock');
$languages=array();
$files=glob('locale/*',GLOB_ONLYDIR|GLOB_NOSORT);
foreach($files as $f) $languages[]=str_replace('locale/','',$f);

$fields['tf.version']=array(READONLY,_('TableField version'));
$fields['-- database']=array(DIVIDER,_('Database'));
$fields['db.name']=array(READONLY,_('Database name'));
$fields['db.autobackup']=array(NUM,_('Backup database every X days. 0 to disable.'));
$fields['wizardlink']=array(LINK,'<span class=text-success>'._('Use the <a href=tfwizard.php>New-Tables Wizard</a> to import existing tables into TableField system').'</span>');
//$fields['db.pre']=array(READONLY,_('Database and session unique project prefix'));
//$fields['db.charset']=array(READONLY,_('Database connection charset'));
//$fields['db.collation']=array(READONLY,_('Database connection collation'));
$fields['reinstall']=array(LINK,_('To change database connection: Remove <code>custom/dbconfig.php</code> and <code>custom/install_done</code>, then go to <a href=tfinstall.php>tfinstall.php</a>'));
$fields['-- html']=array(DIVIDER,_('Project, HTML and display options'));
$fields['html.title']=array(STRING,_('Project name (html &lt;title> tag and home link)'));
//$fields['html.theme']=array(THEME,_('Select a theme. <A href="http://bootswatch.com/#gallery" target="_blank">View gallery</A>'));
$fields['html.chosen'] = array(BOOL,_('Use pretty select boxes'));
$fields['html.toplinks']=array(TEXT,_('Links to the top bar. One link per line.<br>Format: <code>name|link|parameters (e.g target=_blank)</code>'));
$fields['html.head']=array(TEXT,_('Will be put inside the <head> tag'));
$fields['html.body']=array(TEXT,_('Will be put first in the <body>'));
$fields['html.footer']=array(TEXT,_('Will be put last in the <body>'));
$fields['lang']=array('language',_('Set the locale translation language of Table Field. Gettext-compatible language code. To add languages see gettext documentation.'));
$fields['html.rtl']=array(BOOL,_('Use a right-to-left UI?'));
$fields['-- path']=array(DIVIDER,_('URL and path locations'));
$fields['url.rel']=array(STRING,_('Relative location of website root, relative to TF admin url. Set to <code class=inline>../</code> when TableField is installed at <u>example.com/tf</u> or <u>example.com/admin</u>'));
$fields['path.rel']=array(STRING,_('Relative location of file paths of the actual website, relative to the TF admin location. Set to <code class=inline>../</code> when TableField is installed under something like <u>/var/www/example.com/tf</u>'));
$fields['-- auth']=array(DIVIDER,_('Authorization'));
$fields['auth.anonymous']=array(BOOL,_('Allow anonymous access? Anonymous users group is <code>anonymous</code>'));
$fields['auth.keepalive']=array(BOOL,_('Keep user always logged in as long as the browser is still open'));
$fields['auth.sessionexpire']=array(NUM,_('User session expires after X minutes'));
$fields['-- adv']=array(DIVIDER,_('Advanced and Developer options'));
$fields['tf.nosy']=array(BOOL,_('When updating an item report the fields that were changed.'));
$fields['debug']=array(BOOL,_('Show debug notifications? Defines PHP const DEBUG'));
$fields['html.errors']=array(NUM,_('Show PHP errors and warnings? which ones? see <A href="http://php.net/errorfunc.constants" target=_blank>error_reporting() documentation</A>'));
$fields['sql.printall']=array(BOOL,_('Print ALL sql queries into document (printed as remarks, visible with view-source)'));
$fields['html.charset']=array(STRING,_('Character set for HTML. Should be <code>UTF-8</code>'));
$fields['custom_fields']=array(TEXT,_('Custom configuration keys. Values saved in PHP in $tf[\'<i>fieldname</i>\'].<br>One line per field. Format: <code>fieldname|type|Description of the field</code><br>Available field types: <code>string,numeric,boolean,text,html,language,theme,readonly,'.DIVIDER.'</code>'));


if (!empty($_POST['custom_fields']))
	$tf['custom_fields']=$_POST['custom_fields'];
if (!empty($tf['custom_fields'])) {
	$fff=explode("\n",$tf['custom_fields']);
	$fi=0;
	foreach ($fff as $ff) {
		$fi++;
		$f=explode('|',trim($ff));
		if (count($f)!=3) {
			if (trim($ff)!='')
				addToLog(_('Error parsing custom field - Please note the correct format. Line number:')." $fi",LOGBAD,__LINE__);
		} else {
			$f[0]=trim($f[0]);
			$f[1]=trim($f[1]);
			if (!preg_match('/^([a-zA-Z0-9_]+)$/',$f[0])) {
				addToLog(_('Error parsing custom field - Field name can only consist of abc and _. Line number:')." $fi",LOGBAD,__LINE__);
			} else {
				if (array_key_exists($f[0],$fields)) {
					addToLog(_('Error parsing custom field - field key already exists. Line number:')." $fi (<i>$f[0]</i>)",LOGBAD,__LINE__);
				} else {
					// add section when necessary
					if ($f[1]!=DIVIDER && $fi==1) $fields['-- custom']=array(DIVIDER,'Custom Fields');

					// add the custom field
					$fields[$f[0]]=array($f[1],$f[2]);
				}
			}
		}
	}
}

///////////////////////////////////////////////////////////
// Set \ -> \\ and ' -> \'
function fixstr($str) {
	$str = str_replace('\\', '\\\\', $str);
	$str = str_replace("'", "\\'", $str);
	return $str;
}

/////////////////////// SAVE SETTINGS ///////////////
if (count($_POST)) {

	// set new next backup time
	$k = str_replace('.',DOTWORKAROUND, 'db.autobackup');
	if (array_key_exists($k,$_POST) && (1*$_POST[$k]>0) && $_POST[$k]!=$tf['db.autobackup'] && file_exists(__DIR__.'/custom/nextbackup')) {
		$lasttime=1*file_get_contents(__DIR__.'/custom/nextbackup')-(86400*$tf['db.autobackup']);
		$nexttime=$lasttime+(86400*$_POST[$k]);
		file_put_contents((__DIR__.'/custom/nextbackup'),$nexttime);
		addToLog(_('Next backup time updated to').' <t>'.date('Y-m-d',$nexttime).'</t> '._('Last backup created at').' <t>'.date('Y-m-d',$lasttime).'</t>',LOGINFO,__LINE__);
	}

	$content = '<' . '?' . 'php
global $tf;
';
	foreach ($_POST as $k => $v) {
		$k = str_replace(DOTWORKAROUND, '.', $k);
		if (array_key_exists($k,$fields)) {
			$f=$fields[$k];
			if ($f[0]!=DIVIDER && $f[0]!=READONLY) {
				if ($v==TRUEVALUE && $f[0]==BOOL) {
					$v='true';
					$tf[$k]=true; // update current runtime values
				} elseif ($v==FALSEVALUE && $f[0]==BOOL) {
					$v='false';
					$tf[$k]=false; // update current runtime values
				} elseif ($f[0]==NUM) {
					$v=1*$v;
					$tf[$k]=$v; // update current runtime values
				} else {
					$tf[$k]=$v; // update current runtime values
					$v = "'" . fixstr($v) . "'";
				}
				$content .= "\$tf['$k']=$v;\n";
			}
		} else {
			if ($k!=BACKUPFILE) {
				addToLog(_('Unknown Post key')." [$k]",LOGBAD,__LINE__);
			}
		}
	}
	if (file_exists(FILENAME) && file_get_contents(FILENAME) == $content) {
		addToLog(_('No changes made.'),LOGSAME,__LINE__);
	} else {
		if (file_exists(FILENAME) && !empty($_POST[BACKUPFILE])) {
			$now=date('Ymd-his-').mt_rand(1111,9999);
			$newname = str_replace('.php',".$now.php",FILENAME);
			if ($newname==FILENAME) {
				$newname=FILENAME.".$now.php";
			}
			addToLog(_('Backing up to ')." <code>$newname</code>... ",LOGINFO,__LINE__);
			rename(FILENAME, $newname);
		}

		if (!file_put_contents(FILENAME, $content)) {
			addToLog(_('Save settings FAILED to').' <t>'.FILENAME.'</t>... ',LOGBAD,__LINE__);
		} else {
			addToLog(_('Settings saved to').' <t>'.FILENAME.'</t>... ',LOGGOOD,__LINE__);
		}
	}
}

/////////////////////////////////////////////////////////////////////
require_once(__DIR__.'/inc/header.php');

/////////// Test world access to custom folder
if (!file_exists(HTACCESSFILE))	file_put_contents(HTACCESSFILE,HTACCESSCONTENT);

$url='http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1).'custom';
$ch=curl_init("$url/install_done");
curl_setopt($ch,CURLOPT_FAILONERROR,true);
curl_exec($ch);
if (curl_getinfo($ch,CURLINFO_HTTP_CODE)==200) {
	addToLog(_('<t>/custom</t> folder is accessible to the world! This is a major security issue. Please restrict access to')." <t>$url</t>",LOGBAD,__LINE__);
}

if (!empty($tf['log'])) echo "<div id=idLog><div id=idLogText style='margin-top:50px;'>$tf[log]</div></div>";

// check db connection
if (!$tf['db.ok']) {
	echo '<h4 class=text-error>'._('No DB connection! Try again later, go to <A href="tfinstall.php">tfinstall.php</A> or report to administrator').'</h4>';
	exit;
}
// check super user
$user = tfGetUserGroup();
if (empty($user)) {
	echo "<!-- start login form -->";
	tfShowLogin();
	echo "<!-- end login form -->";
	include(__DIR__.'/inc/footer.php');
	exit;
}
// check user permissions
if (!TftUserCan($user, 'edit', '', '')) {
	echo('<h4 class=text-error>'._('Sorry, you are not super user').'</h4>');
	include(__DIR__.'/inc/footer.php');
	exit;
}

////////////////////////////////////////////////////////////////////////
// generate configuration form
?>
<div class="span20 container-fluid">
<form method=POST class="form-horizontal">
	<fieldset class="form-inline">
		<input class="btn btn-large" type="submit" value="Save Settings">
		<label><input id="bkset" name="<?=BACKUPFILE?>" type="checkbox" class="bkset" <?=empty($_POST[BACKUPFILE])?'':'checked'?>
			onclick="document.getElementById('bkset2').checked=this.checked"> <?=_('Backup previous settings file')?></label>
	</fieldset>
<?

// analyse each field
foreach ($fields as $k=>$f) {
	if ($f[0]==DIVIDER) {
		echo "</fieldset><fieldset class='bs-docs-example'>";
		if (!empty($f[1])) echo "<span class='bs-docs-example-legend well'>$f[1]</span>";
	} elseif ($f[0]=='hidden') {
		echo "<!-- $k $f[1] -->";
	} else {
		$name = str_replace('.', DOTWORKAROUND, $k);
		if (!array_key_exists($k,$tf)) $tf[$k]=null;
		echo "<div class='control-group'>";
		if ($f[0]!==LINK) echo "<div class='control-label'>$k</div>";
		echo "<div class='controls'>";
		if ($f[0]==STRING) { // string
			echo '<input name="' . $name . '" class="" type="text" value="'.htmlentities($tf[$k], ENT_QUOTES,'UTF-8').'">';
		} elseif ($f[0]==TEXT) { // text block
			echo '<textarea name="' . $name . '" class="span7">'.htmlentities($tf[$k], ENT_NOQUOTES,'UTF-8').'</textarea>';
		} elseif ($f[0]==NUM) { // numeric
			echo '<input name="' . $name . '" class="" type="number" value="' . (1 * $tf[$k]) . '" onchange="this.value=1*this.value">';
		} elseif ($f[0]==BOOL) { // true/false
			echo '<div><label class="radio inline"><input name="' . $name . '" type="radio" value="'.TRUEVALUE.'" ' . ($tf[$k] ? 'checked' : '') . '>True</label><label class="radio inline"><input name="' . $name . '" type="radio" value="'.FALSEVALUE.'" ' . ($tf[$k] ? '' : 'checked') . '>False</label></div>';
		} elseif ($f[0]==LINK) { // true/false
			echo $f[1];
		} elseif ($f[0]==THEME) {
			echo '<select name="' . $name . '" class=""><option selected>'.$tf[$k].'</option>';
			foreach ($themes as $t) {
				if ($t!=$tf[$k]) {
					echo '<option value="'.strtolower($t).'">'.$t.'</option>';
				}
			}
			echo '</select>';
		} elseif ($f[0]=='language') { // google compatible language code
			echo '<select name="' . $name . '" class="input language">';
			foreach ($languages as $lang) {
				if ($lang == $tf[$k]) {
					echo "<option selected checked>$lang";
				} else {
					echo "<option>$lang";
				}
			}
			echo '</select>';
		} elseif ($f[0]==READONLY) { // read-only
			if ($tf[$k] === false) {
				echo '<span class="btn disabled"><i class=icon-remove></i> False</span>';
			} else if ($tf[$k] === true) {
				echo '<span class="btn disabled"><i class=icon-ok></i> True</span>';
			} else {
				echo '<input type=text readonly value="'.htmlentities($tf[$k], ENT_QUOTES,'UTF-8').'">'; // important - no "name" attribute
			}
		} else {
			echo '<input name="' . $name . '" class="input string" type="text" value="'.htmlentities($tf[$k], ENT_QUOTES,'UTF-8').'">';
			echo '<span class=text-error>This key has a wrong type ('.htmlentities($f[0],ENT_QUOTES,'UTF-8').')</span>';
		}
		if (!empty($f[1]) && $f[0]!=LINK) echo " <span class=muted style='display:inline-block'>$f[1]</span>";
		echo "</div></div>";
	}
}

// end the form
?>
	</fieldset><fieldset class="form-inline">
		<input class="btn btn-large" type="submit" value="Save Settings">
		<label><input id="bkset2" type="checkbox" class="bkset" <?=empty($_POST[BACKUPFILE])?'':'checked'?>
			onclick="document.getElementById('bkset').checked=this.checked"> <?=_('Backup previous settings file')?></label>
	</fieldset>

</form></div>
<?

include(__DIR__.'/inc/footer.php');
