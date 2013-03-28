<?php
global $tf;

// map of actions for the userCan() function
$tf['permissionMap'] = array(
	''      =>'view',
	'access'=>'view',
	'watch' =>'view',
	'see'   =>'view',
	'read'  =>'view',
	'r'     =>'view',
	'a'     =>'new',
	'add'   =>'new',
	'change'=>'edit',
	'chg'   =>'edit',
	'modify'=>'edit',
	'write' =>'edit',
	'w'     =>'edit',
	'update'=>'edit',
	'upd'   =>'edit',
	'remove'=>'del',
	'delete'=>'del',
	'erase' =>'del',
	'drop'  =>'del',
	'actualdel'   =>'del',
	'actualdelete'=>'del');

// log errors in global tf['log']
define('LOGINFO',1);
define('LOGBAD',2);
define('LOGGOOD',3);
define('LOGSAME',4);
define('LOGDEBUG',5);
define('LOGTITLE',6);
function addToLog($msg, $type=0,$line=0,$detail=false) {
	global $tf;
	///// On screen $tf[log] /////
	    if ($type==0) $style='logLine';
	elseif ($type==LOGGOOD ) $style='logGood' .(!$detail?' icon-ok-sign':'');
	elseif ($type==LOGBAD  ) $style='logBad'  .(!$detail?' icon-warning-sign':'');
	elseif ($type==LOGSAME ) $style='logSame' .(!$detail?' icon-info-sign':'');
	elseif ($type==LOGDEBUG) $style='logDebug'.(!$detail?' icon-wrench':'');
	elseif ($type==LOGTITLE) $style='logTitle';
	if ($detail) $style.=' logDetail';
	if ($line) $line="title='line $line'";
	$tf['log'].="<span class='$style' $line>$msg</span>";

	///// DATABASE tf_log ////
	if (!empty($tf['db.ok']) && !empty($tf['tbl.log'])) {
			if ($type==0) $style='logLine';
		elseif ($type==LOGGOOD ) $style='logGood';
		elseif ($type==LOGBAD  ) $style='logBad';
		elseif ($type==LOGSAME ) $style='logSame';
		elseif ($type==LOGDEBUG) $style='logDebug';
		elseif ($type==LOGTITLE) $style='logTitle';
		if ($detail) $style.='+';

		$who=null;
		if (@tfCheckLogin()) $who=@tfGetUserId();

		mysql_query('INSERT INTO '.sqlf($tf['tbl.log']).' SET'
			.' `what`='.sqlv($style)
			.' `who`='.sqlv($who)
			.' `ip`='.sqlv($_SERVER['REMOTE_ADDR'])
			.' `ex`='.sqlv($msg)
			.' `line`='.sqlv($line)
		);
	}
}

// add statistics information to database
function tbllog($what, $who = null, $ex = '') {
	global $tf;
	if (empty($who))
		$who = tfGetUserId();
	mysql_query('INSERT INTO ' . sqlf($tf['tbl.log']) . '(`what`,`who`,`ip`,`ex`) VALUES(' . sqlv($what) . ',' . sqlv($who) . ',' . sqlv($_SERVER['REMOTE_ADDR']) . ',' . sqlv($ex) . ')');
}

// shortcut to html entities
function he($str) {
	global $tf;
	return htmlentities($str,ENT_QUOTES,$tf['html.charset']);
}

// create the tf info table
function TftCreateTft() {
	global $tf;

	if (substr(mysql_get_server_info(), 0, 3) < 4.1) {
		$charsetf = '';
		$charsett = '';
	} else {
		$charsetf = 'CHARACTER SET   utf8  COLLATE utf8_general_ci';
		$charsett = 'DEFAULT CHARSET=utf8  COLLATE=utf8_general_ci';
	}

	// create tfinfo table
	$sql = "cREATE TABLE IF NOT EXISTS `" . $tf['tbl.info'] . "` (
         `tname`       VARCHAR(36)      DEFAULT ''
        ,`fname`       VARCHAR(36)      DEFAULT ''
        ,`class`       VARCHAR(36)
        ,`label`       VARCHAR(255)     $charsetf
        ,`okmax`       BIGINT           DEFAULT NULL
        ,`okmin`       BIGINT           DEFAULT NULL
        ,`okempty`     BOOL             DEFAULT 1
        ,`oknull`      BOOL             DEFAULT 1
        ,`indexed`     BOOL             DEFAULT 0
        ,`searchable`  BOOL             DEFAULT NULL
        ,`order`       INT(5)  SIGNED   DEFAULT 0 NOT NULL
        ,`show`        TINYINT UNSIGNED DEFAULT 0 NOT NULL
        ,`orderby`     TINYINT UNSIGNED DEFAULT 0 NOT NULL
        ,`odirasc`     BOOL             DEFAULT 1 NOT NULL
        ,`default`     VARCHAR(255)     $charsetf
        ,`usersview`   VARCHAR(255)     $charsetf
        ,`usersedit`   VARCHAR(255)     $charsetf
        ,`usersnew`    VARCHAR(255)     $charsetf
        ,`usersdel`    VARCHAR(255)     $charsetf
        ,`commentview` VARCHAR(255)     $charsetf
        ,`commentedit` VARCHAR(255)     $charsetf
        ,`commentnew`  VARCHAR(255)     $charsetf
        ,`commentdel`  VARCHAR(255)     $charsetf
        ,`actionsview` VARCHAR(255)     $charsetf
        ,`actionsedit` VARCHAR(255)     $charsetf
        ,`actionsnew`  VARCHAR(255)     $charsetf
        ,`actionsdel`  VARCHAR(255)     $charsetf
        ,`remarks`     VARCHAR(255)     $charsetf
        ,`params`        VARCHAR(255)     DEFAULT ''
        ,INDEX(`tname`,`fname`)
        ,INDEX(`order`)
        ) $charsett AUTO_INCREMENT=1
        ";
	$ok = mysql_query($sql);

	// create users table
	if ($ok) {
		$sql = "
        CrEATE TABLE IF NOT EXISTS `" . $tf['tbl.users'] . "` (
        `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `type` VARCHAR( 60 ) NOT NULL ,
        `login` VARCHAR( 60 ) NOT NULL ,
        `name` VARCHAR( 100 ) NOT NULL ,
        `added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
        `pass` VARCHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'md5',
        UNIQUE ( `login` )
        )";
		$ok = mysql_query($sql);
	}

	// create tbllog actions table
	if ($ok) {
		$sql = "
        CReATE TABLE IF NOT EXISTS `" . $tf['tbl.log'] . "` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `what` VARCHAR( 60 ) NOT NULL ,
        `who` MEDIUMINT NULL DEFAULT NULL ,
        `ip` CHAR( 23 ) CHARACTER SET latin1 COLLATE latin1_bin NULL ,
        `ex` VARCHAR( 255 ) NULL ,
        `when` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
        INDEX ( `what` , `who` )
        )";
		if (false && sqlVar('have_archive')) {
			$sql = "$sql ENGINE = ARCHIVE";  // 'archive' doesnt support auto_increment
		}
		$ok = mysql_query($sql);
	}

	return $ok;
}

// for the use of TftCreateTables.
// it receives an array with all the important tabla definition data, and create the table.
function TftCreateTable($table, $just_return_sql = false) {
	$fields = '';
	$indexs = '';

	if (!is_array($table)) {
		$tname = $table;
		$table = TftFetchTable($tname);
	}

	if (!isset($table['fields']) || !is_array($table['fields'])) {
		addToLog('Missing fields property at <f>$table[label]</f>',LOGBAD);
		return false;
	}
	foreach ($table['fields'] as $field) {
		//eval('$f=new TfType'.$field['class'].'();');
		$class = "TfType" . $field['class'];
		if (class_exists($class)) {
			$f = new $class();
		} else {
			addToLog("<t>$class</t> "._('Bad class at')." <f>$tname.$f[fname]</f>=<f>$table[label].$f[label]</f>",'bad',__LINE__);
			$f = new TfTypestring();
		}
		$sqltype = $f->sqltype();
		if ($sqltype != '') {
			$def = null;
			if (isset($field['default'])) {
				if ($field['default'] == '') {
					if ($field['oknull']) {
						$def = 'NULL';
					}
				} else {
					$def = sqlv($field['default']);
				}
			}
			if ($def !== null) {
				$sqltype.=' DEFAULT ' . $def;
			}
			$fields.=",`$field[fname]` " . $sqltype;
			if ($field['indexed'])
				$indexs.=",KEY `$field[fname]` (`$field[fname]`)";
			if ($field['class'] == 'pkey' && !empty($table['pkey']))
				$table['pkey'] = $field['fname'];
		}
	}
	$fields = substr($fields, 1);  // remove first ,
	// collation charset - only since mysql 4.1
	if (substr(mysql_get_server_info(), 0, 3) < 4.1) {
		//$charsetfield='';
		$charsettable = '';
	} else {
		//$charsetfield='CHARACTER SET   utf8  COLLATE utf8_general_ci';
		$charsettable = 'DEFAULT CHARSET=utf8  COLLATE=utf8_general_ci';
	}

	$pkey = '';
	$params = '';
	$paramss = parse_str($table['params']);
	if (!empty($paramss['engine']))
		$params.=" engine=$paramss[engine] ";
	if (!empty($paramss['charset']))
		$params.=" charset=$paramss[charset] ";
	if (!empty($paramss['checksum']))
		$params.=" checksum=$paramss[checksum] ";
	if (!empty($paramss['pack_keys']))
		$params.=" pack_keys=$paramss[pack_keys] ";
	if (!empty($paramss['auto_increment']))
		$params.=" auto_increment=$paramss[auto_increment] ";
	if (!empty($paramss['delay_key_write']))
		$params.=" delay_key_write=$paramss[delay_key_write] ";
	if (!empty($table['pkey']))
		$pkey = ",primary key (`$table[pkey]`) ";
	$params.=$charsettable;
	if ($fields != '') {
		$sql = "CREaTE TABLE `$table[tname]` (
        $fields
        $indexs
        $pkey
        ) $params";
	} else {
		//$sql="CREAtE TABLE `$table[tname]` $params";
		$sql = "";
	}

	if ($just_return_sql) {
		return $sql;
	} else {
		return mysql_query($sql);
	}
}

// return an array of all tables+fields which are subtables of the given table
// the format is a normal fetched information from the tf info table
function TftGetSubtables_cancel($table) {
	$subs = array();
	//$res = mysql_query("SELEcT * FROM " . sqlf($tf['tbl.info']) . " WHERE (CONCAT('&',`params`,'&') LIKE ".sqlv("%&xtable=$tname&%").") OR (CONCAT('&',`params`,'&') LIKE ".sqlv("%&xtable=%")." AND `tname`=".sqlv($tname).") ORDER BY `order` DESC,`tname`,`fname`");
	$res = mysql_query("SELEcT * FROM " . sqlf($tf['tbl.info']) . " WHERE (CONCAT('&',`params`,'&') LIKE ".sqlv("%&xtable=$table->tname&%").") ORDER BY `order` DESC,`tname`,`fname`");
	while ($row = mysql_fetch_assoc($res)) {
		$row['pkey'] = TftTablePkey($row['tname']);
		$subs[] = $row;
	}
	return $subs;
}

// return an array of fields and each field is an array of all fetched
// information from tf info table, about that field.
function TftFetchTableFields_cancel($tname) {
	global $tf;
	$fields = array();
	$res = mysql_query("SELeCT * FROM " . sqlf($tf['tbl.info']) . " WHERE (`tname` LIKE " . sqlv($tname) . ") AND (`fname`<>'') ORDER BY `order` DESC,`fname`");
	// ^ use LIKE and not = for case insensitive search
	while ($row = mysql_fetch_assoc($res)) {
		$fields[$row['fname']] = $row;
	}
	return $fields;
}

// will create the entire db tables and fields from the tf info table.
// assuming the tables doesnt exist!
function TftCreateTables($where = '', $just_return_sql = false) {
	global $tf;
	if ($where != '') {
		$where = " WHERE `tname` LIKE " . sqlv($where) . " ";
	} else {
		$where = " WHERE `tname`<>'' ";
	}
	$res = mysql_query("SElECT * FROM " . sqlf($tf['tbl.info']) . " $where ORDER BY `fname` ASC");
	// ^ keep it sorder by fname because we want to get the table definitions first (where fname=='')
	$tables = array();
	while ($row = mysql_fetch_assoc($res)) {
		$tname = $row['tname'];
		if ($tname != '') {
			if ($row['fname'] == '') { // this is the table definition row
				$tables[$tname] = $row;
				$tables[$tname]['fields'] = array();
				// set table parameters from `params`
				$params = paramsfromstring($row['params']);
				if (isset($params['pkey']))
					$tables[$tname]['pkey'] = $params['pkey'];
				if (isset($params['charset']))
					$tables[$tname]['charset'] = $params['charset'];
				if (isset($params['engine']))
					$tables[$tname]['engine'] = $params['engine'];
				if (isset($params['auto_increment']))
					$tables[$tname]['auto_increment'] = $params['auto_increment'];
			} else {  // field
				$tables[$tname]['fields'][$row['fname']] = $row;
				if ($row['class'] == 'pkey') {
					if (empty($tables[$tname]['pkey']))
						$tables[$tname]['pkey'] = $row['fname'];
					if (!isset($tables[$tname]['auto_increment']))
						$tables[$tname]['auto_increment'] = 1;
				}
			}
		}
	}
	if ($just_return_sql) {
		$sql = '';
		foreach ($tables as $tname => $table) {
			$sql.=TftCreateTable($table, true) . ';';
		}
		return $sql;
	} else {
		$ok = true;
		foreach ($tables as $tname => $table) {
			$ok&=TftCreateTable($table);
		}
		return $ok;
	}
}

// drop all the tables that are listed in the tf info table.
function TftDropTables() {
	global $tf;
	$res = mysql_query("SeLECT DISTINCT `tname` FROM `" . $tf['tbl.info'] . "`");
	while ($row = mysql_fetch_row($res)) {
		mysql_query("DROP TABLE `$row[0]`");
	}
}

// empty all the tables that are listed in the tf info table.
function TftEmptyTables() {
	global $tf;
	$res = mysql_query("sELECT DISTINCT `tname` FROM `" . $tf['tbl.info'] . "`");
	while ($row = mysql_fetch_row($res)) {
		mysql_query("TRUNCATE TABLE `$row[0]`");
	}
}

// return true or false, whether the $user can do $action on the $table.$field
// $action can be either 'view','edit','del' or 'new'
function TftUserCan($user, $action, $tname, $fname = '', $row = null) {
	global $tf;
	if (array_key_exists($action, $tf['permissionMap']))
		$action = $tf['permissionMap'][$action];
	if (empty($user))
		$user = tfGetUserGroup();

	if (!is_array($row) || !array_key_exists('users' . $action, $row)) {
		if (!empty($tf['cache']["row..$tname..$fname"])) {
			$row=$tf['cache']["row..$tname..$fname"];
		} else {
			$res=mysql_query('SelECT * FROM '.sqlf($tf['tbl.info']).' WHERE (`tname`='.sqlv($tname).' AND `fname`='.sqlv($fname).')');
			if (!$res) fatal("Error reading tf info table at line ".__LINE__);
			$row=mysql_fetch_assoc($res);
			$tf['cache']["row..$tname..$fname"]=$row;
		}
	}
	return strpos(',' . strtolower($row['users' . $action]) . ',', ',' . $user . ',') !== false;

	/*/ Quick database option
	$res = mysql_query('SEleCT COUNT(*) FROM '.sqlf($tf['tbl.info']).' WHERE (`tname`='.sqlv($tname).' AND `fname`='.sqlv($fname).' AND FIND_IN_SET('.sqlv($user).','.sqlf('users'.$action).')>0)';);
	if (!$res) fatal("Error reading tf info table at line ".__LINE__);
	$row = mysql_fetch_row($res);
	return $row[0] > 0;//*/
}

// return an array of all tables listed
function TftAllTables() {
	global $tf;
	$res = mysql_query("SELecT DISTINCT `tname` FROM `" . $tf['tbl.info'] . "` WHERE `fname`='' ORDER BY `order` DESC,`label`,`tname`");
	if (!$res) {
		die('Critical DB Error - main table is unreadable (' . $tf['tbl.info'] . '). If you have reset your db, please edit custom/dbconfig.php or remove it');
	}
	$ret = array();
	while ($row = mysql_fetch_row($res)) {
		$ret[] = $row[0];
	}
	return $ret;
}

// return an array of all tables as keys and their fetched data as associative array
function TftFetchAllTables() {
	global $tf;
	$res = mysql_query("SELecT * FROM `" . $tf['tbl.info'] . "` WHERE `fname`='' ORDER BY `order` DESC,`label`,`tname`");
	if (!$res) fatal('Critical DB Error - main table is unreadable (' . $tf['tbl.info'] . '). If you have reset your db, please edit custom/dbconfig.php or remove it');
	$ret = array();
	while ($row = mysql_fetch_assoc($res))
		$ret[$row['tname']] = $row;
	return $ret;
}

// return an array of all fetched information about that field
function TftFetch($tname, $fname) {
	global $tf;
	$res = mysql_query("SELECt * FROM " . sqlf($tf['tbl.info']) . " WHERE (`tname`=".sqlv($tname).") AND (`fname`=".sqlv($fname).")");
	if ($res) {
		return mysql_fetch_assoc($res);
	} else {
		return false;
	}
}

// return an array of all fetched information about that table + pkey
function TftFetchTable($tname) {
	return TftFetch($tname, '');
}

// return the table pkey accoarding to the tf info table
function TftTablePkey($tname) {
	global $tf;
	$res = mysql_query("SELEct `fname` FROM `" . $tf['tbl.info'] . "` WHERE (`tname`='$tname') AND (`class`='pkey') ORDER BY `order` DESC,`fname` LIMIT 1");
	$row = mysql_fetch_row($res);
	if ($row)
		return $row[0];
	$res = mysql_query("SELect `params` FROM `" . $tf['tbl.info'] . "` WHERE (`tname`='$tname') AND (`fname`='') ORDER BY `order` DESC,`fname` LIMIT 1");
	$row = mysql_fetch_row($res);
	if ($row) {
		$params=paramsfromstring($row[0]);
		if (!empty($params['pkey']))
			return $params['pkey'];
	}
	return false;
}

// return an array of TfType based classes containing all of the table fields.
function TftTableFields(&$table) {
	global $tf;
	$fs = TftFetchTableFields($table->tname);
	$fields = array();
	foreach ($fs as $fname => $f) {
		//eval('$fields["'.$fname.'"]=new TfType'.$f['class'].'();');
		$class = "TfType" . $f['class'];
		if (class_exists($class)) {
			$fields[$fname] = new $class();
		} else {
			addToLog("<t>$class</t> "._('Bad class at').' <f>'.$table->tname.".$f[fname] $f[label]</f>",'bad',__LINE__);
			$fields[$fname] = new TfType();
		}
		$fields[$fname]->populate($f,$table);
		$fields[$fname]->populate_intag();
	}
	return $fields;
}

// return a TfType based class containing the specified field.
function TftField_DEPRECATED(&$table, $fname, $fetch_row = null) {
	global $tf;
	if (empty($fetch_row))
		$fetch_row = TftFetch($table->tname, $fname);
	//eval('$field=new TfType'.$f['class'].';');
	$class = "TfType" . $fetch_row['class'];
	if (class_exists($class)) {
		$field = new $class();
	} else {
		addToLog("<t>$class</t> "._('Bad class at').' <f>'.$table->name.".$fname</f>",'bad',__LINE__);
		$field = new TfType();
	}
	$field->init();
	$field->populate($fetch_row,$table);
	$field->populate_intag();
	$field->frm = 'frm';
	$field->defaultParameters(); // assign default parameters actually read from info-tabelfield 'params'.
	return $field;
}

// add the table to tf info table. all the tf info table parameters should be set as keys
// in $table, including $table['tname'].
function TftAddTable_DEPRECATED($table) {
	global $tf;
	$table = array_change_key_case($table, CASE_LOWER);
	$table['fname'] = '';
	$names = '';
	$values = '';
	foreach ($table as $col => $value) {
		if ($col != 'fields') {
			if ($value === null) {
				$values.=',NULL';
			} else {
				$values.="," . sqlv($value);
			}
			$names.=",`$col`";
		}
	}
	$names = substr($names, 1); // remove first ','
	$values = substr($values, 1); // remove first ','
	mysql_query("INSERT InTO `" . $tf['tbl.info'] . "` ($names) VALUES ($values)");
	if (isset($table['fields']) && is_array($table['fields'])) {
		foreach ($table['fields'] as $field) {
			if (!isset($field['tname']))
				$field['tname'] = $table['tname'];
			TftAddField_DEPRECATED($field);
		}
	}
}

// add the field to tf info table. all the parameters should be set as keys in $field.
function TftAddField_DEPRECATED($field) {
	global $tf;
	$field = array_change_key_case($field, CASE_LOWER);
	$names = '';
	$values = '';
	foreach ($field as $col => $value) {
		if ($value === null) {
			$values.=',NULL';
		} else {
			$values.="," . sqlv($value);
		}
		$names.=",`$col`";
	}
	$names = substr($names, 1); // remove first ','
	$values = substr($values, 1); // remove first ','
	return mysql_query("INSERT INtO `" . $tf['tbl.info'] . "` ($names) VALUES ($values)");
}

function revAscDesc($dir) { // return ASC when DESC and visa versa. null otherwise
	$dir = strtoupper($dir);
	if ($dir == 'ASC')
		return 'DESC';
	if ($dir == 'DESC')
		return 'ASC';
	return null;
}

// Handle the extra params field:
function paramsfromstring($string) {
	$return = array();
	parse_str($string, $return);
	if (get_magic_quotes_gpc())
		$return = array_map('stripslashes_deep', $return);
	return $return;
}

function stringfromparams($params) {
	return http_build_query($params);
}

