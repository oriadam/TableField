<?php
/*
  ================
  Handle mysqldump
  ================

  Requires super-user 'view' permission.

  URL Parameters:
  act=dl&fn=filename.ext - Download a file
  act=dump - Create a mysql dump backup of current database, using PHP and no exec()
  act=dump&exec=1 - Create a mysqldump of current database, using exec() mysqldump. Quick, but may contain charset mistakes!
  act=dump&zip=1 - Create a zipped mysql dump backup of current database
  act=zip&fn=filename.ext - zip a specific file to filename.ext.zip
  act=delete&fn=filename.ext - Delete a file

  Note: Scheduled backups are called from footer.php like this:
	$_GET=array('act'=>'dump','zip'=>'2','silent'=>1);
	include('tfout.php')

 */
global $tf;
require_once(__DIR__.'/inc/include.php'); // include also handles login
define('OUTDIR','custom/output/'); // trailing slash is important!

chdir(__DIR__);
if (!is_dir(OUTDIR)) mkdir(OUTDIR);
chdir(__DIR__.'/'.OUTDIR);

$act=@$_GET['act'];

if (empty($tf['tf.tfout-no-user-check'])) {
	// check super user
	$user = tfGetUserGroup();
	if (empty($user)) {
		include(__DIR__.'/inc/header.php');
		echo "<!-- start login form -->";
		tfShowLogin();
		echo "<!-- end login form -->";
		include(__DIR__.'/inc/footer.php');
		exit;
	}
	// check user permissions
	if (!TftUserCan($user, 'view', '', '')) {
		include(__DIR__.'/inc/header.php');
		echo('<h4 class=text-error>'._('Sorry, you are not super user').'</h4>');
		include(__DIR__.'/inc/footer.php');
		exit;
	}
}
if (empty($_GET['fn'])) {
	$fnu=$fn=false;
} else {
	$fn=preg_replace('/^'.preg_quote(OUTDIR,'/').'/','',$_GET['fn']);
	if (strpos($fn,'/')!==false) {
		addToLog(_('Action aborted - Bad file name'),LOGBAD,__LINE__);
		$fnu=$fn=false;
	} else {
		$fnu=urlencode($fn);
	}
}

if ($act=='dl' && $fn) {
	if (!file_exists($fn)) addToLog(_('Download failed - File not found'),LOGBAD,__LINE__);
	elseif (headers_sent()) addToLog(_('Download failed - headers already sent. Please notify admin'),LOGBAD,__LINE__);
	else {
		header("Content-Disposition: attachment; filename=\"$fn\"");
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		set_time_limit(0);
		readfile($fn);
		exit;
	}

}//$act=='dl'
elseif ($act=='delete' && $fn) {
	if (!file_exists($fn)) addToLog(_('Delete - File not found'),LOGGOOD,__LINE__);
	elseif (unlink($fn)) addToLog(_('File deleted')." <t>$fn</t>",LOGGOOD,__LINE__);
	else addToLog(_('Unable to delete file'),LOGBAD,__LINE__);

}//$act=='delete'
elseif ($act=='zip' && $fn) {
	if (!file_exists($fn)) addToLog(_('Zip failed - File not found'),LOGGOOD,__LINE__);
	else {
		require_once(__DIR__.'/inc/pclzip.lib.php');
		$zip=new PclZip("$fn.zip");
		$zip->create(array($fn),PCLZIP_OPT_REMOVE_PATH,true);
		if (file_exists("$fn.zip") && filesize("$fn.zip")>5) // file zip ok ?
			addToLog(_('Zipped file created at')." <t><a href=\"?act=dl&fn=$fnu.zip\">$fn.zip</a></t>",LOGGOOD,__LINE__);
		else {
			addToLog(_('Unable to zip file'),LOGBAD,__LINE__);
			addToLog($zip->error_string,LOGBAD,__LINE__,true);
		}
	}

}//$act=='zip'

if ($act=='dump') {
//	if (!$fn) $fn=$tf['db.name'].'.'.date('Y-m-d-H-i-s').'.'.bin2hex(openssl_random_pseudo_bytes(6)).'.sql';
	if (!$fn) $fn=$tf['db.name'].'.'.date('Y-m-d-H-i-s').'.sql';
	if (empty($_GET['zip'])) {
		define('LASTBACKUP',OUTDIR.$fn);
	} else {
		define('LASTBACKUP',OUTDIR."$fn.zip");
	}

	if(function_exists('exec') && !empty($_GET['exec'])) {
		// use exec() quick and easy
		$result=null;
		exec('mysqldump --user='.$tf['db.user'].' --password='.$tf['db.pass'].' --host='.$tf['db.host'].' '.$tf['db.name'].' > '.$fn,$result);

	} else {
		// use queries and php - long and painfull ;)

		$sql='-- TableField backup to '.$fn;

		$rest = mysql_query('SHOW TABLES');
		while($rowt = mysql_fetch_row($rest))
		{
			$table=$rowt[0];
			$row = mysql_fetch_row(mysql_query("SHOW CREATE TABLE `$table`"));
			$sql.= "\n\n$row[1];\n\n";

			$result = mysql_query("SELECT * FROM `$table`");
			$num_fields = mysql_num_fields($result);

			for ($i = 0; $i < $num_fields; $i++)
			{
				while($row = mysql_fetch_row($result))
				{
					$sql.= "INSERT DELAYED INTO `$table` VALUES(";
					for($j=0; $j<$num_fields; $j++)
					{
						$row[$j] = mysql_real_escape_string($row[$j]);
						if (isset($row[$j])) { $sql.= "'".$row[$j]."'" ; } else { $sql.= "''"; }
						if ($j<($num_fields-1)) { $sql.= ','; }
					}
					$sql.= ");\n";
				}
			}
			$sql.="\n\n";
		}

		file_put_contents($fn,$sql);
		$sql=null;
	}

	if (file_exists($fn) && filesize($fn)>5) { // file dump ok ?
		addToLog(_('Database backup created at')." <t><a href=\"?act=dl&fn=$fnu\">$fn</a></t>",LOGGOOD,__LINE__);

		// create zip archive file
		if (!empty($_GET['zip'])) {
			require_once(__DIR__.'/inc/pclzip.lib.php');
			$zip=new PclZip("$fn.zip");
			$zip->create(array($fn),PCLZIP_OPT_REMOVE_PATH,true);
			if (file_exists("$fn.zip") && filesize("$fn.zip")>5) { // file zip ok
				addToLog(_('Zip file created at')." <t><a href=\"?act=dl&fn=$fnu.zip\">$fn.zip</a></t>",LOGGOOD,__LINE__);
				if ($_GET['zip']==2)
					unlink($fn); // remove original file
			} else {
				addToLog(_('Unable to zip file'),LOGBAD,__LINE__);
			}
		}
	} else {
		addToLog(_('Failed mysql dump to')." <t>filename=$fn</t>",LOGBAD,__LINE__);
		addToLog(he(var_export($result,1)),LOGDEBUG,__LINE__,true);
	}
}//$act=='dump'

if (empty($_GET['silent'])) {

	/////////// Test world access to custom folder
	$url='http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1).'custom';
	$ch=curl_init("$url/install_done");
	curl_setopt($ch,CURLOPT_FAILONERROR,true);
	curl_exec($ch);
	if (curl_getinfo($ch,CURLINFO_HTTP_CODE)==200) {
		addToLog(_('<t>/custom</t> folder is accessible to the world! This is a major security issue. Please restrict access to')." <t>$url</t>",LOGBAD,__LINE__);
	}

	include(__DIR__.'/inc/header.php');

	echo "<div id=idLog><div id=idLogText style='margin-top:50px;'>$tf[log]</div></div>";

	echo '<fieldset class="bs-docs-example"><span class="bs-docs-example-legend well">'._('Download created files').'</span><div id=filelist>';

	$files=glob('*');
	rsort($files);
	foreach ($files as $fn) {
		$fnu=urlencode($fn);
		echo "<div class=filename><a href=\"?act=dl&fn=$fnu\">$fn</a> "
				."<i class='act icon-trash' title=\""._('Delete this file')."\" onclick=\"godelete('$fnu')\"></i>";
				if (!preg_match('/\.zip$/',$fn) && !file_exists("$fn.zip"))
					echo " <a class='act icon-gift' href=\"?act=zip&fn=$fnu\">"._('Zip it')."</a>";
			echo '</div>';
	}

	echo '</div></fieldset>'
		.'<fieldset class="bs-docs-example"><span class="bs-docs-example-legend well">'._('Actions').'</span>'
			.'<a class="btn btn-large" href="?act=dump&zip=1">'._('Backup Database').'</a>'
			.'<a class="btn btn-large" href="?act=dump&zip=2">'._('Backup Database (zip only)').'</a>'
		.'</fieldset>';
	?>
	<div id="dialog-confirm" title="<?=_('Delete this file?')?>">
	  <p><span id=fn></span></p>
	</div>

	<script>
		function godelete(fn) {
			document.getElementById('fn').innerHTML=decodeURI(fn);
			$("#dialog-confirm").dialog({
				resizable: false,
				modal: true,
				buttons: {
					"Delete file": function() {
						document.location.href='?act=delete&fn='+fn;
					},
					Cancel: function() { $(this).dialog('close');}
				}
			});
		}
	</script>
	<?

	include(__DIR__.'/inc/footer.php');
}