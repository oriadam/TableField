<?php
//*******************//
//  TF wizard - magically import selected tabels from DB into TF //
//*******************//
error_reporting(E_ALL);

global $tf;

require_once(__DIR__.'/inc/include.php');
if (!function_exists('fatal')) {
	function fatal($msg) {
		die($msg);
	}
}
require_once(__DIR__.'/inc/header.php');

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

//////////////// READ TABLES //////////////
// from TF - that was easy
$tftables=TftAllTables();
// from DB - not that hard either
$tables=array();
$res=mysql_query('SHOW TABLES');
while ($row=mysql_fetch_array($res)) $tables[]=$row[0];

//////////////// PROCESS SELECTED TABLES /////////////////
if (count($_POST)) {
	$errors=array();
	foreach ($_POST as $t=>$v) {
		if (in_array($t,$tables)) {
			if (!in_array($t,$tftables)) {
				/////////////////////////// MAGIC HAPPENS HERE ////////////////////
				addToLog(_('Adding Table')." <f>$t</f>",LOGTITLE,__LINE__);
				$sql='INSERT INTO `'.$tf['tbl.info'].'` SET `tname`='.sqlv($t).",`fname`='',`label`=".sqlv(ucwords(str_replace('_',' ',$t))).",`usersview`='root',`usersedit`='root',`usersdel`='root',`usersnew`='root'";
				;
				if (DEBUG) addToLog($sql,LOGDEBUG,__LINE__);
				mysql_query($sql);
				$cols = array();
				$res=mysql_query("SHOW COLUMNS FROM `$t`"); // already know $t is a valid table - no xss worries
				while ($row=mysql_fetch_assoc($res)) {
					$f=$row['Field'];
					$cols[$f]=array();
					$cols[$f]['tname']=$t;
					$cols[$f]['fname']=$f;
					$cols[$f]['label']=ucwords(str_replace('_',' ',$f));
					$cols[$f]['indexed']=!empty($row['Key']);
					$cols[$f]['oknull']=1*chkBool($row['Null'],0);
					$cols[$f]['okempty']=1;
					if ($row['Default']!==null) $cols[$f]['default']=$row['Default'];
					$cols[$f]['usersview']=$cols[$f]['usersedit']=$cols[$f]['usersnew']=$cols[$f]['usersdel']='root';

					//// Guess Class
					$type=preg_replace('/\(.*\)/','',strtolower($row['Type'])); // remove (size)
					$len=false; // guess allowed size length
					$match=array(); // preg matches
					if (preg_match('/\(([0-9]+)\)/',$row['Type'],$match)) {
						$len=$match[1];
					}

					if ($row['Key']=='PRI') {
						$cols[$f]['class']='pkey';
						$cols[$f]['searchable']=0;
					} else if (strpos($type,'char')!==false) {
						if ($len>500) {
							$cols[$f]['class']='text';
							$cols[$f]['okmax']=$len;
							$cols[$f]['searchable']=0;
						} else {
							$cols[$f]['class']='string';
							$cols[$f]['searchable']=1;
							if ($len>0) $cols[$f]['okmax']=$len;
						}
					} else if (strpos($type,'text')!==false) {
						if ($type=='tinytext') {
							$cols[$f]['class']='string';
							$cols[$f]['searchable']=1;
							$cols[$f]['okmax']=255;
						} else {
							$cols[$f]['class']='text';
							$cols[$f]['okmax']=null;
							$cols[$f]['searchable']=0;
						}
					} else if (strpos($type,'blob')!==false) {
						$cols[$f]['class']='blob';
						$cols[$f]['searchable']=0;
					} else if (strpos($type,'enum')!==false || strpos($type,'set')!==false) {
						if ($type=='set') $cols[$f]['class']='enums';
						else $cols[$f]['class']='enum';
						$cols[$f]['searchable']=1;
						// find values
						$match=array();
						if (preg_match('/\(([^)]+)\)/',$row['Type'],$match)) {
							$vals=explode(',',$match[1]);
							for ($i=0;$i<count($vals);$i++)
								$vals[$i]=urlencode(trim($vals[$i],"'"));
							$cols[$f]['meta']=array('values'=>implode(',',$vals));
						}
					} else if (strpos($type,'int')!==false || strpos($type,'double')!==false || strpos($type,'float')!==false || strpos($type,'numeric')!==false || strpos($type,'decimal')!==false || strpos($type,'bit')!==false) {
						$cols[$f]['class']='number';
						$cols[$f]['searchable']=1;
						if (strpos($type,'unsign')!==false)
							$cols[$f]['okmin']=0;
						if ($type=='bit' && $len<2) {
							$cols[$f]['okmin']=0;
							$cols[$f]['okmax']=1;
						}
					} else if (strpos($type,'timestamp')!==false || strpos($type,'time')!==false || strpos($type,'date')!==false || strpos($type,'year')!==false) {
						$cols[$f]['class']=$type;
						$cols[$f]['searchable']=0;
						if (@$cols[$f]['default']=='0000-00-00 00:00:00') unset($cols[$f]['default']); // here and not below, because
						if (@$cols[$f]['default']=='CURRENT_TIMESTAMP') unset($cols[$f]['default']);   // i cannot unset below
					} else {
						$cols[$f]['class']='';
						$cols[$f]['searchable']=0;
						addToLog(_('Could not detect type of')." $f=$row[Type]",LOGBAD,__LINE__);
					}

					$sql='INSERT INTO `'.$tf['tbl.info'].'` SET ';
					foreach($cols[$f] as $k=>$v) {
						if ($k!=='meta') {
							if ($v===true) {
								$v='1';
							} elseif ($v===false) {
								$v='0';
							} elseif ($v===null) {
								$v='NULL';
							} elseif (is_int($v)) {
								// leave $v as it is
							} else {
								$v=sqlv($v);
							}
							$sql.=sqlf($k).'='.$v.',';
						}
					}//foreach $cols[$f]
					addToLog(_('Adding field of class')." <f>$f</f>: <t><b>".$cols[$f]['class']."</b></t>",LOGGOOD,__LINE__,true);
					$sql=substr($sql,0,strlen($sql)-1); // remove last comma. I hate it.
					if (DEBUG) addToLog($sql,LOGDEBUG,__LINE__,true);
					mysql_query($sql);

					if (!empty($cols[$f]['meta'])) {
						$infoid=mysql_insert_id();
						if (!$infoid) {
							addToLog(_('No insert_id of last field! cannot add the meta values')." <f>$f</f>: <t>".var_export($cols[$f]['meta'],1).'</t>',LOGBAD,__LINE__,true);
						} else {
							foreach ($cols[$f]['meta'] as $k=>$v) {
								addToLog(_('Adding meta to field')." <f>$f</f>: <t><b>$k=$v</b></t>",LOGGOOD,__LINE__,true);
								$sql='INSERT INTO `'.$tf['tbl.meta']."` SET `infoid`=$infoid,`meta`=".sqlv($k).',`value`='.sqlv($v);
								if (DEBUG) addToLog($sql,LOGDEBUG,__LINE__,true);
								mysql_query($sql);
							}
						}
					}//if meta

				}// end while each fields
				/////////////////////////// MAGIC ENDS HERE ////////////////////
			} else {
				addToLog(_('Skipping previously added Table ')." <f>$t</f>",LOGSAME,__LINE__);
			}
		} else {
			addToLog(_('This Table was not found in your database')." <f>$t</f>",LOGBAD,__LINE__);
		}
	}

	// read now, including the new tables
	$tftables=TftAllTables();
}// post
if (!empty($tf['log'])) {
?>
<div id=idLog class="span20 container-fluid" style="margin:50px;">
	<div id=idLogText class='well text-info container-fluid'><?=$tf['log']?></div>
</div>
<?
}

///////////////////////////// GENERATE FORM //////////////////////////
?>

<div class="span20 container-fluid">
	<h1><?=_('Tables Wizard')?></h1>
	<p><?=_('Quickly add existing tables to the TableField system.')?>
	<p><?=_('Following Field types will be detected:')?> <?=_('primary-key, string, text, number, date/time/timestamp, enum, set <small>(=list of enums)</small>.')?></p>
	<p><?=_('When done here go to <a href=tftedit.php>TF Tables manager</a> for fine tuning of the Fields classes and meta parameters.')?>
		<?_('For example, you may want to change a text field to html, a number to xkey, a string to email, add calculated values etc...')?>
	</p>

<form method=POST class="form-horizontal">
	<fieldset class="form-inline">
		<label><input class="btn btn-large" type="submit" value="Start the Magic"></label>
	</fieldset>
	<fieldset class="bs-docs-example"><span class='bs-docs-example-legend well'><?=_('Tables currently not in TableField system')?></span>
<?

foreach ($tables as $t) {
	if (!in_array($t,$tftables)) {
		?>
		<div><label><input type=checkbox name="<?=$t?>"> <?=$t?></label></div>
		<?
	}
}

?>
	</fieldset>
	<fieldset class="form-inline">
		<label><input class="btn btn-large" type="submit" value="Start the Magic"></label>
	</fieldset>
	<fieldset class="bs-docs-example"><span class='bs-docs-example-legend well'><?=_('Tables already in TableField system')?></span>
<?
// analyse each field
foreach ($tftables as $t) {
	if ($t!='')
		if (in_array($t,$tables)) {
			?>
			<div><label><input type=checkbox readonly disabled name="<?=$t?>"> <?=$t?></label></div>
			<?
		} else {
			?>
			<div><label class="text-error"><input type=checkbox readonly disabled name="<?=$t?>"> <?=$t?> -- <?=_('Not found in your database')?></label></div>
			<?
		}
}

?>
</form></div>
<?

include(__DIR__.'/inc/footer.php');
