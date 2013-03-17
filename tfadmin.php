<?php
/*
  ==================================
  TableField main end-user interface
  ==================================

  URL Parameters:
  t: table name (required) TfTable is populated to $tf['t']/$t

  d:  Layout mode (Display). $tf['d']
  d=b/box    = table for each row
  d=l/list   = tr for each row
  d=q/quiet  = TODO: show clean update results, for ajax

  a:  Action to be taken. $tf['act']
  a=v/view       = just view the data (*default)
  a=e/edit       = edit view of the records
  a=n/new        = start edit a new row

  p:  Page number. $page
  pp: Items (rows) Per Page. $perpage
  te: Repeat Title-row Every x rows. For 'never' use '-'. $titleevery
  o:  Order by this field name. For descending order add '-' to the end of the field name, for example "&o=name-". $order
  n:  Number of New rows to be displayed for optional adding. Defaults to perpage

  sc: Show Const xkey=xid fields given from above? 1/0. $tf['showconst']
  sv: Show View-only fields on Edit mode? 1/0. Use this to hide fields without edit permission, on Edit mode. $tf['showview']
  nn: No add New rows option
  ne: No 'Edit this record' link on view mode? 1/0. $tf['noedit']
  no: No Options of changing mode and other options. 1/0. $tf['nooptions']
  np: No Page select tool bar. 1/0. $tf['nopage']
  ns: No Search tool bar. 1/0. $tf['nosearch']
  nt: No Top Bar. 1/0. $tf['notopbar']

  i: MIni tIny mode, which means only data table is displayed and no other options. $tf['mini']

  s: Field name to be Searched. $searchkey
  q: Text to search within the above field name. $searchval

  xkey,xid:     if you want to filter the list by specific field(s).
  for example, to retrieve only rows with `sign` field equals to `leo` use:
  xkey1=sign&xid1=leo
  you can numbers, or any other postfix for the xkey/xid (xkeybla=sign&xidbla=leo)
  and use as many filters as you want.

  HTML:
  An <input class=remove_me> would be removed before form submit.

 */
require_once(__DIR__.'/inc/include.php'); // include also handles login

global $reGetParams; // this is for oria.inc.php reGet() function // todo - remove this
$reGetParams['url'] = str_ireplace("index.php", "", $_SERVER['PHP_SELF']);

global $tf; // tfadmin vars that are shared among the functions

////////////////////////////////////////////// PREPARE VARS //////////////////////////////////

$tf['user'] = tfGetUserGroup();
if (empty($tf['user']))
	fatal('Error with login - no user. If you changed custom/auth.php please fix it.');

///// t = current table name
if (empty($_GET['t'])) {
	$tf['t'] = null;
	$t = null;
	$tname = null;
} else {
	$tname=$_GET['t'];
	$tf['t'] = new TfTable($tname); // get table info from tf info table
	$t=$tf['t']; // note - $t is not global
	if (empty($t->pkey)) fatal("Table not found in current TableField system ".he($tname));
	$tf['sqlf_tname']=sqlf($t->tname);
	$tf['sqlf_pkey']=sqlf($t->pkey);
}

// set xkey=idname&xid=12 xkey1=idname&xid1=12, xkey2=idname&xid2=12...
if ($t) {
	foreach ($t->fields as $k => $f) $t->fields[$k]->fetch['const']=null;
	for($i=1;$i<=count($_GET);$i++)
		if (isset($_GET["xkey$i"]) && isset($_GET["xid$i"])) {
			$_GET["xkey$i"]=str_replace('`','',$_GET["xkey$i"]);
			if (isset($t->fields[$_GET["xkey$i"]]))
				$t->fields[$_GET["xkey$i"]]->fetch['const'] = $_GET["xid$i"];
		}
}

///// d = Layout (Display mode)
// 'mm' = main menu = no current table, 'b' = list of records as Boxes, 'l' = List, 's' = Spreadsheet, 'q' = Quiet (ajax)
if (!$t) {
	$tf['d']='mm'; // main menu
} else {
	if (empty($_GET['d'])) {
		$tf['d']=$t->param('d');
		if (empty($tf['d'])) {
			$tf['d']='l';
		}
	} else {
		$tf['d'] = $_GET['d'];
		if (!in_array($tf['d'], array('mm','b','l','s','q'))) {
			fatal("Unknown Layout (".he($tf['d']).")");
		}
	}
}

if ($tf['d']=='s'){
	// todo: spreadsheet
	addToLog('Spreadsheet layout mode is not done yet - switching to List layout',LOGDEBUG,__LINE__);
	$tf['d']='l';
}

/////// MODES and ACTIONS
// quiet mode
$tf['quiet'] = $tf['d']=='q'; // quiet

// GET id to display first from the query string
$tf['id'] = @$_GET['id'];
if ($tf['id'] == '') $tf['id'] = @$_POST['id'];
if ($tf['id']=='') $tf['id']=array();
else {

	if (strpos($tf['id'],',')!==false) {  // multiple id's

		$tf['id']=explode(',',$tf['id']);

	} else { // single id
		$tf['id']=array($tf['id']);
	}
}

// GET Action
$tf['act'] = @$_GET['a'];
if (empty($tf['act'])) $tf['act'] = @$t->params['a'];
if (empty($tf['act'])) $tf['act'] = @$t->params['action'];
if (empty($tf['act'])) $tf['act'] = @$t->params['act'];
if (empty($tf['act'])) $tf['act'] = 'v'; // default
if ($tf['act'] == 'e') $tf['act'] = 'edit';
elseif ($tf['act'] == 'n') $tf['act'] = 'new';
elseif ($tf['act'] == 'v') $tf['act'] = 'view';
elseif ($tf['act']!=='view' && $tf['act']!=='edit' && $tf['act']!=='new') {
	echo ('Unknown action');
	if (DEBUG) echo '['.he($tf['act']).']';
	exit;
}

// hide stuff mini mode etc
$tf['mini'] = chkBool(Get('i'), 0);
$tf['showconst'] = chkBool(Get('sc'), chkBool(@$t->params['showconst'], chkBool(@$t->params['sc'], 1)));
$tf['showview'] = chkBool(Get('sv'), chkBool(@$t->params['showview'], chkBool(@$t->params['sv'], 1)));
$tf['nooptions'] = chkBool(Get('no'), chkBool(@$t->params['nooptions'], chkBool(@$t->params['no'], 0)));
$tf['nopage'] = chkBool(Get('np'), chkBool(@$t->params['nopaging'], chkBool(@$t->params['np'], 0)));
$tf['nosearch'] = chkBool(Get('ns'), chkBool(@$t->params['nosearch'], chkBool(@$t->params['ns'], 0)));
$tf['notopbar'] = chkBool(Get('nt'), !empty($tf['mini']));
$tf['nonew'] = chkBool(Get('nn'), chkBool(@$t->params['nonew'], chkBool(@$t->params['nn'], 0)));
if ($tf['nonew']) $tf['news'] = 0;
elseif (array_key_exists('news',$_GET)) $tf['news']=1*$_GET['news'];
elseif ($t && $t->params && array_key_exists('news',$t->params)) $tf['news']=1*$t->params['news'];
elseif ($tf['mini']) $tf['news']=1;
else $tf['news']=10;

$tf['noedit'] = chkBool(Get('ne'), chkBool(@$t->params['noedit'], chkBool(@$t->params['ne'], $tf['mini'])));
if ($tf['act'] == 'new') $tf['nopage'] = 1;  // no paging on 'add mode'
if ($t && !$t->userCan($tf['user'], 'new')) $tf['nonew'] = 1; // no 'add mode' when no permission

/////////////////////////////////////////////////////////////////////////////////////////////////

if (!$tf['quiet'])
	include(__DIR__.'/inc/header.php');

if ($t) {
	if (DEBUG && count($_POST) && !$tf['quiet']) echo "<!-- ".var_export($_POST,1)." -->";
	if (count($_POST)) processPost($t,$tf,$_POST,$_GET);
	if (!$tf['quiet']) displayTable($t,$tf,$_GET);
} else {
	if (!$tf['quiet']) {
		displayMainMenu();
	}
}

if (!$tf['quiet'])
	include(__DIR__.'/inc/footer.php');

function processPost(&$t,&$tf,&$POST) { // pass by reference because there's no reason not to, and it save some memory/cpu load
	$u = $tf['user']; // shortcut

	// is there a POST?
	if (count($POST)) {
		// undo_magic_quotes(); // already called on include.php

		//if (DEBUG) addToLog(fix4html3(var_export($POST,1)),LOGDEBUG,__LINE__);
		addToLog(_("Processing Request..."),LOGTITLE);

		//////////////// handle files upload
		// $POST+=$_FILES;
		foreach ($_FILES as $k => $v) {
			// $_FILES rows structure is different than $POST rows. fix it into $POST
			if (is_array($v)) {
				$POST[$k] = array();
				foreach ($v as $k2 => $v2) {
					foreach ($v2 as $k3 => $v3) {
						if (!array_key_exists($k3, $POST[$k])) { // make sure array initialized
							$POST[$k][$k3] = array();
						}
						$POST[$k][$k3][$k2] = $v3;
					}
				}
			} else { // is_array($v)
				$POST[$k] = $v;
			}
		}

		//////////////// Update/insert data from POST because  ___up is set
		if (array_key_exists('___up', $POST)) {
			if (!is_array($POST['___up'])) {
				addToLog(_("Invalid Post Data!").' '._('Please copy the following data and paste to site admin'),LOGBAD,__LINE__);
				addToLog('<code>___up='.he(var_export($POST['___up'],1)).'</code>',LOGBAD,__LINE__,true);
			} else {
				foreach ($POST['___up'] as $rowc => $up) {  // for each row -- $rowc=row counter, $up=row action update
					if (!empty($up) && (empty($POST['___del']) || empty($POST['___del'][$rowc]))) {  // should update this row, and not deleting this same row

						///////////////// Prepare $updaterow[] array of values to updated

						if (array_key_exists('___id',$POST) && array_key_exists($rowc,$POST['___id'])) { // there's an id to this row? no id means add new
							$sqlv_id = sqlv($POST['___id'][$rowc]); // id for sql queries
							$html_id = he($POST['___id'][$rowc]); // id for logs

							if (!$t->userCan($u, 'edit')) {
								addToLog(_('You are not allowed to change').' <f>'.he($t->fetch['label']).'</f>',LOGBAD,__LINE__,false);
							} else {
								$updaterow = array();
								$skipped = array();
								if ($tf['tf.nosy']) { // check for changes against existing data and report them?
									$res = sqlRun("SELECT * FROM $tf[sqlf_tname] WHERE $tf[sqlf_pkey]=$sqlv_id");
									if (!$row = mysql_fetch_assoc($res)) {
										$skipped[]=array(_('Update item failed - item not found')." <t>id=$html_id</t>",LOGBAD,__LINE__,false);
									} else {
										// read current row data
										foreach ($row as $k => $v) {
											if (array_key_exists($k, $t->fields)) {
												$t->fields[$k]->fetch['_oldval'] = $v; // dont use set()
											} else {
												$skipped[]=array(_("Field not found in existing table. Please notify admin")." <t>$t->tname.$k</t>",LOGBAD,__LINE__,true);
											}
										}

										// update $t->fields from post:
										foreach ($t->fields as $f) { // per every field in this table
											$html_f=he($f->fetch['label']);
											if (array_key_exists($f->fname, $POST) && array_key_exists($rowc, $POST[$f->fname]) && !is_array($POST[$f->fname][$rowc])) { // value has been received in POST
												$postval = $POST[$f->fname][$rowc];
												if ($postval===$f->fetch['_oldval']) { // new post value is same as the one in db, no change
													$skipped[]=array(_('Skipping unchanged value at')." <t>$html_id</t> <f>$html_f</f>",LOGSAME,__LINE__,true);
												} else {
													if (!$f->userCan($tf['user'], 'edit')) { // user does't have permission to edit to field
														$skipped[]=array(_('User not allowed to edit')." <t>$html_id</t> <f>$html_f</f>",LOGBAD,__LINE__,true);
													} else {
														if (!$f->validate($postval)) {  // value not valid - do not update
															$skipped[]=array(_('Invalid value at')." <t>$html_id</t> <f>$fhtml_f</f> $f->error",LOGBAD,__LINE__,true);
														} else {
															// set the valid value
															$f->set($postval);
															if ($f->value===$f->fetch['_oldval']) { // new value is same as old value
																$skipped[]=array(_('Skipping unchanged set value at')." <t>$html_id</t> <f>$html_f</f>",LOGSAME,__LINE__,true);
															} else {
																$updaterow[$f->fname] = $f->value;
															}
														}
													}
												}
											} else { //field exist in post
												if (DEBUG && array_key_exists($f->fname, $POST) && array_key_exists($rowc, $POST[$f->fname]) && is_array($POST[$f->fname][$rowc]))
													$skipped[]=array(_('Skipping Array value received in POST for')." <t>$html_f=".he(var_export($POST[$f->fname][$rowc],true)).'</t>',LOGBAD,__LINE__,true);
											}
										}//foreach fields update
									}//item id not found in select

								} else { // not nosy - dont report every change, just update the record

									// update $t->fields from post:
									foreach ($t->fields as $f) { // per every field in this table
										$html_f=he($f->fetch['label']);
										if (array_key_exists($f->fname, $POST) && array_key_exists($rowc, $POST[$f->fname]) && !is_array($POST[$f->fname][$rowc])) { // value has been received in POST
											if (!$f->userCan($u, 'edit')) { // user does't have permission to edit this field
												$skipped[]=array(_('User is not allowed to edit')." <f>$html_f</f> <t>$html_id</t>",LOGBAD,__LINE__,true);
											} else {
												if (!$f->validate($POST[$f->fname][$rowc])) {  // value not validated, invalid value - do not update
													$skipped[]=array(_('Invalid value at')." <t>$html_id</t> <f>$html_f</f> $f->error",LOGBAD,__LINE__,true);
												} else {
													// set the valid value
													$f->set($POST[$f->fname][$rowc]);
													$updaterow[$f->fname] = $f->value;
												}
											}
										} else { //field exist in post
											if (DEBUG && array_key_exists($rowc, $POST[$f->fname]) && is_array($POST[$f->fname][$rowc]))
												$skipped[]=array(_('Skipping Array value received in POST for')." <t>$html_f=".he(var_export($POST[$f->fname][$rowc],true)).'</t>',LOGBAD,__LINE__,true);
										}
									}//foreach fields update

								} //not nosy

								///////////////////// Done preparing $updaterow array - now for some real DB action ///////////////

								// update the valid values
								if (!count($updaterow)) {
									addToLog(_('Update item cancelled - nothing to update')." <t>$html_id</t>",LOGSAME,__LINE__);
								} else {

									// Prepare the UPDATE SQL Query
									// set up SETs
									$sets = '';
									foreach ($updaterow as $k => $v) $sets.=",".sqlf($k)."=".sqlv($v);
									$sets = substr($sets, 1); // remove the first ,

									$sql = "UPDATE $tf[sqlf_tname] SET $sets WHERE $tf[sqlf_pkey]=$sqlv_id LIMIT 1";
									if (DEBUG || $tf['sql.printall']) echo '<!-- '.str_replace('-->','--≻',$sql).' -->';
									if (sqlRun($sql)) {
										$errors=0;
										foreach ($skipped as $v) if ($v[1]==LOGBAD) $errors++;
										if ($errors) {
											addToLog(_("Item Partially Updated") . " <t>$html_id</t>",LOGBAD,__LINE__);
										} else {
											addToLog(_("Item Updated") . " <t>$html_id</t>",LOGGOOD,__LINE__);
										}
										// display the updated fields and new values
										if ($tf['tf.nosy']) {
											foreach ($updaterow as $k => $v)
												addToLog('<f>'.$t->fields[$k]->fetch['label'].'</f> <n>'.fix4html4($v,50).'</n> <o>'.fix4html4($t->fields[$k]->fetch['_oldval'],50).'</o>',LOGGOOD,__LINE__,true);
										}
									} else {
										addToLog(_('Update item failed').' '._('Please copy the following data and paste to site admin')." <t>$html_id</t>",LOGBAD,__LINE__);
										addToLog('<sqlerr>'.sqlError().'</sqlerr> <sql>'.sqlLastQuery().'</sql>',LOGBAD,__LINE__,true);
									}//if sqlRun()


								}//else nothing to update in $updaterow
							}//$t->userCan edit the table

						} else { //empty id - no id means add new record

							// no id means add new
							if (!$t->userCan($u, 'new')) {
								addToLog(_('You are not allowed to add to').' <t>'.he($t->fetch['label']).'</t>',LOGBAD,__LINE__);
							} else {
								$updaterow = array();
								$skipped = array();
								// validate all values
								foreach ($t->fields as $f) {
									$html_f = he($f->fetch['label']);
									if ($f->userCan($u, 'new') && array_key_exists($f->fname, $POST) && array_key_exists($rowc, $POST[$f->fname])) { // value has been received in POST
										if (!$f->validate($POST[$f->fname][$rowc])) { // validated the value
											$skipped[]=array(_('Invalid value at')." <f>$html_f</f> $f->error",LOGBAD,__LINE__,true);
										} else {
											$f->set($POST[$f->fname][$rowc]);
											$updaterow[$f->fname] = $f->value;
										}
									} else { // const preset values - mainly for foreign keys. Should it work around the userCan(new)?
										// validate const values
										if (array_key_exists('const', $f->fetch) && $f->fetch['const'] !== null) {
											if ($f->userCan($u, 'new')) {
												$skipped[]=array(_('You cannot set this const value at')." <f>$html_f</f> $f->error",LOGBAD,__LINE__,true);
												if (!$f->validate($f->fetch['const'])) { // validated the value
													$skipped[]=array(_('Invalid const value at')." <f>$html_f</f> $f->error",LOGBAD,__LINE__,true);
												} else {
													$f->set($f->fetch['const']);
													$updaterow[$f->fname] = $f->value;
												}
											}
										}
									}
									// set default values for yet unset fields
									if (!array_key_exists($f->fname,$updaterow) && array_key_exists('default', $f->fetch) && $f->fetch['default']!==null) {
										$updaterow[$f->fname] = $f->fetch['default']; // set tf default value instead of DB default
									}
								}

								$errors=0;
								foreach($skipped as $v) if ($v[1]==LOGBAD) $errors++;
								if ($errors) { // do not allow partial adding
									addToLog(_("Add item failed"),LOGBAD,__LINE__);
								} else {
									// insert a blank row?
									if (count($updaterow) == 0) {
										// insert an empty row
										$sql = "INSERT INTO $tf[sqlf_tname]";
										if (DEBUG || $tf['sql.printall']) echo '<!-- '.$sql.' -->';
										if (sqlRun($sql)) {
											array_push($tf['id'],sqlLastInsert()); // use array_push to brind it to $tf['id'][0]
											addToLog(_('New blank item added').' <f>'.he($t->fields[$t->pkey]->fetch['label']).'</f>=<t>'.$tf['id'][0].'</t>',LOGGOOD,__LINE__);
										} else {
											addToLog(_('Add blank item failed.').' '._('Please copy the following data and paste to site admin'),LOGBAD,__LINE__);
											addToLog('<sqlerr>'.sqlError().'</sqlerr> <sql>'.sqlLastQuery().'</sql>',LOGBAD,__LINE__,true);
										}

									} else { //no $updaterow - add blank row

										// Prepare the UPDATE SQL Query
										// set up SETs
										$sets = '';
										foreach ($updaterow as $k => $v) if ($v!==null && $v!==$t->fields[$k]->fetch['default']) $sets.=",".sqlf($k)."=".sqlv($v);
										$sets = substr($sets, 1); // remove the first ,

										$sql = "INSERT INTO $tf[sqlf_tname] SET $sets";
										if (DEBUG || $tf['sql.printall']) echo '<!-- '.str_replace('-->','--≻',$sql).' -->';
										if (sqlRun($sql)) {
											array_push($tf['id'],sqlLastInsert()); // use array_push to brind it to $tf['id'][0]
											$errors=0;
											foreach($skipped as $v) if ($v[1]==LOGBAD) $errors++;
											if ($errors) {
												addToLog(_("New item added with problems").' <t>'.$tf['id'][0].'</t>',LOGBAD,__LINE__);
											} else {
												addToLog(_("New item added") . ' <t>'.$tf['id'][0].'</t>',LOGGOOD,__LINE__);
											}
											// display the updated fields and new values
											if ($tf['tf.nosy']) {
												foreach ($updaterow as $k => $v)
													if ($v!==null && $v!==$t->fields[$k]->fetch['default']) addToLog('<f>'.$t->fields[$k]->fetch['label'].'</f> <n>'.fix4html4($v,50).'</n>',LOGGOOD,__LINE__,true);
											}
										} else {
											addToLog(_("Add item failed").' '._('Please copy the following data and paste to site admin'),LOGBAD,__LINE__);
											addToLog('<sqlerr>'.sqlError().'</sqlerr> <sql>'.sqlLastQuery().'</sql>',LOGBAD,__LINE__,true);
										}//if sqlRun()

									}//else no $updaterow (insert blank row)
								}//there's something to update
							} //$t->userCan add new
						} // empty id
					}// eact exists and no del
					// report problems and errors items skipped (previously known as $errors array)
					if (isset($skipped)) foreach($skipped as $log) call_user_func_array('addToLog',$log);
				}//for each eaction
			}// ___up is array
		}// POST has ___up array - do update

		// do del
		if (array_key_exists('___del', $POST)) {
			if (!is_array($POST['___del'])) {
				addToLog(_("Invalid Post Data!").' '._('Please copy the following data and paste to site admin'),LOGBAD,__LINE__);
				addToLog('<code>___del='.he(var_export($POST['___del'],1)).'</code>',LOGBAD,__LINE__,true);
			} else {
				if (!$t->userCan($u, 'del')) {
					addToLog(_('You are not allowed to delete from').' <t>'.he($t->fetch['label']).'</t>',LOGBAD,__LINE__);
				} else {
					foreach ($POST['___del'] as $rowc => $act) {  // row counter and row action
						if (empty($POST['___id'][$rowc])) {
							addToLog(_("Invalid Post Data!").' '._('Please copy the following data and paste to site admin'),LOGBAD,__LINE__);
							addToLog("<code>___del is empty at $rowc=".he(var_export($POST['___del'],1)).'</code>',LOGBAD,__LINE__,true);
						} else {
							$sqlv_id = sqlv($POST['___id'][$rowc]);
							$html_id = he($t->fields[$t->pkey]->fetch['label'].'='.$POST['___id'][$rowc]);
							$res = sqlRun("SELECT * FROM $tf[sqlf_tname] WHERE $tf[sqlf_pkey]=$sqlv_id");
							if (!($row = mysql_fetch_assoc($res))) {
								addToLog(_('Nothing to remove, item not found')." <t>$html_id</t>",LOGSAME,__LINE__,true);
							}else{//id exists in db
								// deletetion of the field values (for deleting files)
								foreach ($t->fields as $f) {
									if (isset($row[$f->fname])) {
										$f->value = $row[$f->fname];
									}
									$f->del();  // only for deleting files, actually
								}
								$sql = "DELETE FROM $tf[sqlf_tname] WHERE $tf[sqlf_pkey]=$sqlv_id LIMIT 1";
								if (sqlRun($sql)) {
									if (mysql_affected_rows())
										addToLog(_('Item removed')." <t>$html_id</t>",LOGGOOD,__LINE__);
									else
										addToLog(_('Nothing to delete')." <t>$html_id</t>",LOGSAME,__LINE__,true);
								} else {
									addToLog(_('Error delete item').' '._('Please copy the following data and paste to site admin')." <t>$html_id</t>",LOGSAME,__LINE__,true);
									addToLog('<sqlerr>'.sqlError().'</sqlerr> <sql>'.sqlLastQuery().'</sql>',LOGBAD,__LINE__,true);
								}
							}//___id not found in post
						}//missing id from post
					}//foreach del
				}//$t->userCan delete from this table
			}// ___del is array
		}// POST has ___del array

		addToLog(_("Finished processing request."),LOGTITLE);
	}// POST
}

/////////////////////////////////////////////////////////////////////////////////// end of db updating - start of display

function displayMainMenu() {
	global $tf;
	$tables=TftFetchAllTables();
	echo '<div class="container-fluid well text-center">';
	foreach ($tables as $t) {
		// TftUserCan($user, $action, $tname, $fname = '', $row = null) {
		if ($t['tname']!='' && TftUserCan($tf['user'],'view',$t['tname'],'',$t)) {
			echo "<a href=\"./?t=$t[tname]\" class='btn maintablelink'>$t[label]</a>";
		}
	}
	echo '</div>';
}

function displayTable(&$t,&$tf,&$GET) { // pass by reference because there's no reason not to, and it save some memory/cpu load

	if (!$t->userCan($tf['user'], $tf['act']))
		fatal("Permission denied" . (DEBUG ? ": user <pre>" . $tf['user'] . "</pre>, table=<pre>" . $t->tname . "</pre>, action=<pre>" . $tf['act'] . "</pre>" : ""));

	///////////////////////////// INIT VARS ////////////////////////////////
	// disabled links
	$emptyhref = 'href="#" tabindex="-1" disabled';

	$htmlActions = '';
	$istr = '';
	$rowc=0; // html row counter

    ////////////////// get from GET: page, items per page, repeat title every
	if (isset($GET['p'])) {
		$page=1*$GET['p'];
		if ($page==0) $page=1;
	} else $page=1;

	if (isset($GET['pp'])) $perpage=1*$GET['pp'];
	elseif (count($tf['id']) && isset($GET['ppp'])) $perpage=1*$GET['ppp'];
	else $perpage=0;
	if ($perpage==0) @$perpage=1*$t->params['perpage'];
	if ($perpage==0) @$perpage=1*$t->params['pp'];
	if ($perpage==0) $perpage = 20;

	$titleevery=20; // default
	if (isset($GET['te']) && is_numeric($GET['te'])) $titleevery=1*$GET['te'];
	if ($titleevery===null && isset($t->params['titleevery']) && is_numeric($t->params['titleevery'])) $titleevery=$t->params['titleevery'];
	if ($titleevery===null && isset($t->params['te']) && is_numeric($t->params['te'])) $titleevery=$t->params['te'];

	////////////////// get from GET: order by, sort field
	$orderby=@$GET['o'];
	if (empty($orderby)) $orderby = @$t->params['order'];
	if (empty($orderby)) $orderby = @$t->params['orderby'];
	if (empty($orderby)) $orderby = @$t->params['order-by'];
	if (empty($orderby)) $orderby = @$t->params['o'];
	if (empty($orderby)) $orderby = @$t->params['sort'];
	if (empty($orderby)) $orderby = @$t->params['sortby'];
	if (empty($orderby)) $orderby = @$t->params['sort-by'];
	if (empty($orderby)) $orderby=false;
	$orderdir = 1; // asc by default
	if (substr($orderby, 0, 1) == '-') {
		$orderdir = 0; // desc
		$orderby = substr($orderby, 1); // remove the "-"
	}
	if (substr($orderby, strlen($orderby) - 1, 1) == '-') {
		$orderdir = 0; // desc
		$orderby = substr($orderby, 0, strlen($orderby) - 1); // remove the "-"
	}

	////////////////// get from GET: searches s1,s2,s3...
	$searches=array();
	$defaultsearch=array('f'=>'','q'=>'','not'=>false,'how'=>'has','chain'=>'','virgin'=>true);
	// s1=f.q.-how.chain
	for($i=1;$i<=count($GET);$i++) {
		if (array_key_exists("s$i",$GET)) {
			$g=explode('.',$GET["s$i"]);
			$s=$defaultsearch;
			if (@$g[0]!='') {
				$g[0]=str_replace('%2E','.',$g[0]);
				if (!array_key_exists($g[0], $t->fields)) {
					addToLog(_("Bad search key"). ' <t>'.he($g[0]).'</t>',LOGBAD,__LINE__);
				} else {
					$s['f']=$g[0];
					if (empty($g[2])) $g[2]='has'; // default
					if (strpos($g[2],'-')===0) {
						$s['not']=true;
						$g[2]=substr($g[2],1); // remove -
					}
					if (@$g[1]!=''||$g[2]=='b'||$g[2]=='n'||$g[2]=='e') {
						$g[1]=str_replace('%2E','.',$g[1]);
						unset($s['virgin']);
						$s['q']=$g[1];
						if ($g[2]=='has'||$g[2]=='a'||$g[2]=='z'||$g[2]=='ci'||$g[2]=='eq'||$g[2]=='lt'||$g[2]=='gt'||$g[2]=='lte'||$g[2]=='gte'||$g[2]=='rx'||$g[2]=='b'||$g[2]=='e'||$g[2]=='n') $s['how']=$g[2];
						if (!empty($g[3]) && $g[3]=='or') $s['chain']='or';
						else $s['chain']='and';
						$searches[]=$s;
					}
				}
			}
		}
	}
	if (count($searches)) $searches[count($searches)-1]['chain']=''; // remove last chain

	// set for xkey=xid xkey1=xid1 ...
	foreach ($t->fields as $k => $f) {
		$t->fields[$k]->fetch['const'] = false;
	}
	foreach ($GET as $k => $v) {
		$k = strtolower($k);
		if (substr($k, 0, 4) == 'xkey' && array_key_exists($v, $t->fields) && array_key_exists('xid' . substr($k, 4), $GET)) {
			$t->fields[$v]->fetch['const'] = $GET['xid' . substr($k, 4)];
			//if (DEBUG) addToLog("xid $k: $v=".$GET['xid'.substr($k,4)],LOGDEBUG,__LINE__);
		}
	}

	// fix id's for sql
	$tf['sqlv_id']=array();
	foreach($tf['id'] as $val) $tf['sqlv_id'][]=sqlv($val);
	$tf['sqlv_id']=implode(',',$tf['sqlv_id']);

	//////////////////////////// END INIT VARS ///////////////////////////

	$actionsTitle='';
	if ($tf['act']=='new') $actionsTitle=_('Add?');
	elseif ($tf['act']=='edit') $actionsTitle=_('Update?');

	// table comment
	if (!empty($t->fetch['comment' . $tf['act']]))
		echo '<div id=idTableComment class="container">'.$t->fetch['comment'.$tf['act']].'</div>';

	if (!$tf['nooptions']) {
		if (!$tf['nosearch']) {
			$search = array();
			foreach ($t->fields as $k => $f) {
				if ($f->fetch['searchable'] && $f->userCan($tf['user'], 'view'))
					$search[$k] = $f;
			}
			$tf['nosearch'] = count($search) == 0;
		}

		if ($tf['mini']) {
			$btn='btn btn-mini';
			echo '<div id="idCtrlBarMini" class="container-fluid nav nav-pills navbar-fixed-top">';
		} else {
			$btn='btn btn-small';
			echo '<div id="idCtrlBar" class="container-fluid nav nav-pills navbar-fixed-top">';
			echo '<span class="well" id="TableLabel">'.$t->fetch['label'].'</span> ';
		}

		// action edit / view / new
		if ($t->userCan($tf['user'], 'view'))
			echo '<a id=idCtrlView class="'.$btn.' " href="'.reGet(array('a'=>'v')).'"><i class="act icon-align-left"></i> '._('View').'</a>';
		if ($t->userCan($tf['user'], 'edit'))
			echo '<a id=idCtrlEdit class="'.$btn.' " href="'.reGet(array('a'=>'e')).'"><i class="act icon-pencil"></i> '._('Edit').'</a>';
		if ($t->userCan($tf['user'], 'new'))
			echo '<a id=idCtrlNew class="'.$btn.' " href="'.reGet(array('a'=>'n')).'"><i class="act icon-plus"></i> '._('Add').'</a>';

		// custom actions
		// todo: change to parse_str / paramsfromstring
		if ($t->fetch['actions' . $tf['act']]) {
			$aActs = split(';', $t->fetch['actions' . $tf['act']]);
			foreach ($aActs as $a) {
				$aAct = split('~', $a);
				if (count($aAct) >= 2) {
					if (count($aAct) == 2)
						$aAct[2] = '';
					if ((stripos($aAct[1], '.jpg') || stripos($aAct[1], '.gif') || stripos($aAct[1], '.png')) && (!stripos($aAct[1], '<img'))) {
						$aAct[1] = trim($aAct[1]);
						$aAct[1] = "<img src=\"$aAct[1]\">";
					}
					if (!strpos($aAct[0], '$id')) {
						echo '<a class="'.$btn.' csCtrlCustom" href="'.$aAct[0].'" title="'.$aAct[2].'">'.$aAct[1].'</a>';
					}
				} else {
					echo '<!-- error with action '.var_export($a,1).' -->';
				}
			}
		}

		// save button
		if ($tf['act'] == 'edit' || $tf['act'] == 'new') { // send form - save changes
			echo '<button id=idCtrlSave class="'.$btn.' btn-primary" type="submit" form="idForm"><i class="act icon-thumbs-up icon-white"></i> '._('Save changes').'</button>';
			if (DEBUG) echo '<label title="Debug form prepare"><i class="act icon-stethoscope"></i><input type=checkbox id="idCtrlSaveConfirm"></label>';
		}
		// search
		if (!$tf['nosearch']) {
			echo ' <button id=idCtrlSearch href="#idSearch" class="'.$btn.(count($tf['id']) || (count($searches) && !isset($searches[0]['virgin']))?' btn-success':'').'" data-toggle="modal"><i class="act icon-search"></i> '._('Search').'</button> ';
		}
		// log
		if (empty($tf['log'])) $cs='disabled';
		elseif (strpos($tf['log'],'logBad')) $cs='btn-danger';
		elseif (strpos($tf['log'],'logGood')) $cs='btn-success';
		else $cs='btn-info';
		echo '<button id=idCtrlLog '.(empty($tf['log'])? 'disabled':'').' href="#idLog" class="'.$btn.' '.$cs.'" data-toggle="modal"><i class="act icon-tasks'.(empty($tf['log'])? '':' icon-white').'"></i> '._('Log').'</button> ';

		if ($tf['mini']) {

			// close window
			//echo '<button id=idCtrlClose class="'.$btn.' pull-right" onclick="window.close()"><i class="act icon-remove-sign"></i> '._('Close Window').'</button>';

		} else { ////////////////////////

			// main menu
			echo '<a id=idCtrlMain href="./?" class="'.$btn.' "><i class="act icon-home"></i> '._('Main Menu').'</a> ';

		}//not mini

		// layout
		echo '<div class="'.$btn.' disabled" id=idCtrlLayout>'._('Layout').' '
			.'<a id=idCtrlBox  href="'.reGet(array('d'=>'b')).'" title="'._('Boxes Layout').'"      ><i class="act icon-th    '.($tf['d']=='b'?'icon-white':'').'"></i></a>'
			.'<a id=idCtrlList href="'.reGet(array('d'=>'l')).'" title="'._('List Layout').'"       ><i class="act icon-list  '.($tf['d']=='l'?'icon-white':'').'"></i></a>'
			.'<a id=idCtrlSS   href="'.reGet(array('d'=>'s')).'" title="'._('Spreadsheet Layout').'"><i class="act icon-table '.($tf['d']=='s'?'icon-white':'').'"></i></a>';
			if ($tf['mini'])
				echo '<a id=idCtrlMini href="'.reGet(array('i'=>0)).'" title="'._('Full Mode').'"><i class="act icon-resize-full"></i></a>';
			else
				echo '<a id=idCtrlMini href="'.reGet(array('i'=>1)).'" title="'._('Mini Mode').'"><i class="act icon-resize-small"></i></a>';
		echo '</div>';

		echo '</div>';//idCtrlBar

		// search modal
		if (!$tf['nosearch']) {
			echo '<div id=idSearch class="modal hide fade" tabindex="-1" role="dialog">'
					.'<div class="modal-header">'._('Search')
						.'<button type="button" class="close" data-dismiss="modal">×</button>'
					.'</div>'
					.'<div class="modal-body form-inline inline">';

					while (count($searches)<12) $searches[]=$defaultsearch;
					unset($searches[0]['virgin']); // always show at least one search line
					foreach($searches as $s) {
						echo '<div class="search-line '.(empty($s['virgin'])?'':'hidden').'">'
								.'<label class="search-not-label '.($s['not']?'true':'').'"><i class="add-on icon-minus-sign">'._('not').'</i><input class="search-not" type=checkbox '.($s['not']?'checked':'').' onchange="if (this.checked) $(this).parent().addClass(\'true\'); else $(this).parent().removeClass(\'true\');"></label>'
								.'<select size=1 class="search-field" onkeydown="if (event.keyCode==13) searchSubmit()"><option>';
								foreach ($search as $f) {
									echo "<option value='$f->fname' ".(($s['f']==$f->fname) ? 'selected' : '').'>'.$f->fetch['label'];
								}
								echo '</select>'
								.'<select size=1 class="search-how" onkeydown="if (event.keyCode==13) searchSubmit()">'
									.'<option '.($s['how']=='has'?'selected':'').' value="has">'._('has')
									.'<option '.($s['how']=='a'  ?'selected':'').' value="a" >' ._('begins')
									.'<option '.($s['how']=='z'  ?'selected':'').' value="z" >' ._('ends')
									.'<option '.($s['how']=='in' ?'selected':'').' value="in">' ._('in')
									.'<option '.($s['how']=='ci' ?'selected':'').' value="ci">' ._('is')
									.'<option '.($s['how']=='eq' ?'selected':'').' value="eq">' ._('≡')
									.'<option '.($s['how']=='gt' ?'selected':'').' value="gt">' ._('>')
									.'<option '.($s['how']=='lt' ?'selected':'').' value="lt">' ._('<')
									.'<option '.($s['how']=='gte'?'selected':'').' value="gte">'._('≥')
									.'<option '.($s['how']=='lte'?'selected':'').' value="lte">'._('≤')
									.'<option '.($s['how']=='rx' ?'selected':'').' value="rx">' ._('regexp')
									.'<option '.($s['how']=='e'  ?'selected':'').' value="e">'  ._('is empty')
									.'<option '.($s['how']=='b'  ?'selected':'').' value="b">'  ._('is true')
									.'<option '.($s['how']=='n'  ?'selected':'').' value="n">'  ._('is null')
								.'</select>'
								.'<input class="search-query" type="search" onkeydown="if (event.keyCode==13) searchSubmit();" value="'.he($s['q']).'">'
								.'<select size=1 class="search-chain" onchange="$(this).parent().next().slideDown(250).removeClass(\'hidden\');if (this.options[0].value==\'\') this.removeChild(this.options[0])"><option><option value="and" '
								.($s['chain']=='and' ?'selected':'').'>'._('and').'<option value="or" '.($s['chain']=='or' ?'selected':'').'>'._('or').'</select>'
								.' <i class="act icon-remove" onclick="$(this).parent().find(\'.search-field\').get(0).selectedIndex=0"></i>'
							.'</div>';
					}

						echo '<div id=idSearchIDBlock><label id=idSearchIDLabel>'._('Quickly go to id').' <input id=idSearchID type=text size=4 value="'
							.implode(',',$tf['id']).'" onchange="$(\'.search-query\').attr(\'disabled\',!!this.value)"></label> <i class="act icon-remove" onclick="document.getElementById(\'idSearchID\').value=\'\';document.getElementById(\'idSearchID\').onchange()"></i></div>'
					.'</div>'
					.'<div class="modal-footer">'
						.'<button class="btn" d1ata-dismiss="modal" onclick="searchSubmit(true)"><i class="act icon-remove"></i> '._('Clear Search').'</button>'
						.'<button class="btn btn-primary" d1ata-dismiss="modal" onclick="searchSubmit()">'._('Search!').'</button>'
					. '</div>'
				.'</div>';
		}//$tf['nosearch']

		// log modal
		echo '<div id=idLog class="modal hide" tabindex="-1" role="dialog">'
				//.'<div class="modal-header">'._('The Log')
				//	.'<button type="button" class="close" data-dismiss="modal">×</button>'
				//.'</div>'
				.'<div id=idLogText class="modal-body">'
				.$tf['log']
				.'</div>'
			.'</div>';

	}//$tf['nooptions']

	$tf['log'] = ''; // empty the log after displaying it.

	echo '<form id=idForm class="container-fluid" name="frm" method="post" enctype="multipart/form-data" onsubmit="return tfFormSubmit(this)"  '.($tf['nooptions']?'style="margin-top:0px"':'').' cancel_action="'.reGet().'">';

	////////////////////// process htmlFormStart() ////////////////////////
	foreach ($t->fields as $f) // per every field in this table
		echo $f->htmlFormStart();

	// on NEW mode there's no need to select
	if ($tf['act'] && $tf['act'] != 'new') {
		// default sql

		////////////////////////// main select: SELECT ///////////////////////////
		$sql = "SELECT SQL_CALC_FOUND_ROWS ";
		$arr=array('extant.*');
		foreach ($t->fields as $f) {
			$f->tname='extant';
			if ($s=$f->to_select_select())
				$arr[]=$s;
			$f->tname=$t->tname;
		}
		$sql.=implode(',',$arr);

		/////////////////////////// main select: FROM ////////////////////////
		$sql.=" FROM $tf[sqlf_tname] AS extant ";
		$arr=array();
		foreach ($t->fields as $f) {
			$f->tname='extant';
			if ($s=$f->to_select_from())
				$arr[]=$s;
			$f->tname=$t->tname;
		}
		$sql.=implode(' ',$arr);

		////////////////////// main select: WHERE //////////////////////

		// Single record mode:
		if (count($tf['id']) && ($perpage==1 || $perpage==count($tf['id']))) { // A single record by id mode

			if (count($tf['id'])==1)
				$where="(extant.$tf[sqlf_pkey]=$tf[sqlv_id])";
			else
				$where="(extant.$tf[sqlf_pkey] IN ($tf[sqlv_id]))";

		} else { // multi-where

			// search given by URL query GET
			$where = '';
			$nextchain='';
			// search
			if (count($tf['id'])==0) {
				foreach ($searches as $s) {
					if (empty($s['virgin']) && !empty($s['f'])) {
						$t->fields[$s['f']]->tname='extant';
						$q=$t->fields[$s['f']]->to_select_where($s['how'],$s['q'],$s['not']);
						if ($q) {
							if (substr($q,0,7)=='HAVING ')
								if (!empty($having)) $having.=' AND '.substr($q,7);
								else $having=substr($q,7);
							else {
								$where.="$nextchain ($q)";
								$nextchain=$s['chain']=='or'?' OR':' AND';
							}
						} else {
							if ($q===false) addToLog(_("Bad search method at")." <f>$s[f]</f>: <t>".he($s['how'].'.'.$s['q']).'</t>',LOGBAD,__LINE__);
						}
						$t->fields[$s['f']]->tname=$t->tname;
					}
				}
			}

			// const values given by URL query (note - this is not a search, this is a direct by-value caluse.
			// so DO NOT use to_select_where() here
			foreach ($t->fields as $f) {
				if ($f->fetch['const'] !== false) {
					if ($where != '')
						$where.=" AND ";
					$where.="(" . sqlf($f->fname) . "=" . sqlv($f->fetch['const']) . ")";
				}
			}
		}
		if ($where != '')
			$sql.=" WHERE $where ";

		if (!empty($having))
			$sql.=" HAVING $having";

		////////////////////// main select: ORDER BY //////////////////////
		$orderfields = array();
		// put given id first
		if (count($tf['id'])>1) {
			$orderfields[] = "(CASE WHEN extant.$tf[sqlf_pkey] IN ($tf[sqlv_id]) THEN 1 ELSE 0 END) DESC";
		} elseif (count($tf['id'])==1) // simple single id. not using IN for performance
			$orderfields[] = "(CASE WHEN extant.$tf[sqlf_pkey]=$tf[sqlv_id] THEN 1 ELSE 0 END) DESC";

		// order by from cmdline GET query
		if ($orderby && array_key_exists($orderby,$t->fields)) {
			$t->fields[$orderby]->tname='extant';
			$dir = $orderdir ? 'ASC' : 'DESC';
			$tmp = $t->fields[$orderby]->to_select_orderby($dir);
			if ($tmp) {
				$orderfields[] = $tmp;
			}
			$t->fields[$orderby]->tname=$t->tname;
		}
		// order by from tf info table
		$res = sqlRun("SELECT `orderby`,`odirasc`,`fname` FROM " . sqlf($tf['tbl.info']) . " WHERE `tname`=".sqlv($t->tname)." AND `fname`<>'' AND `orderby`<>0 ORDER BY `orderby` DESC");
		// process other sorting fields
		while ($row = mysql_fetch_row($res)) {
			$t->fields[$row[2]]->tname='extant';
			$dir = $row[1] ? 'ASC' : 'DESC';
			$tmp = $t->fields[$row[2]]->to_select_orderby($dir);
			if ($tmp) {
				$orderfields[] = $tmp;
			}
			$t->fields[$row[2]]->tname=$t->tname;
		}
		$order = implode(',', $orderfields);
		if ($order != '')
			$sql.=" ORDER BY $order ";

		///////////////////// main select: LIMIT /////////////////////////
		if ($perpage) {
			if (count($tf['id'])>$perpage) {
				$perpage=count($tf['id']); // dont hide given id's
				$page=1;
			}
			$sql.=" LIMIT " . (($page - 1) * $perpage) . ",$perpage ";
		}

		///////////////////////////// main select: GO GO GO //////////////////////

		if (DEBUG || true) print("<!-- $sql -->");

		if (!$masterq = sqlRun($sql))
			fatal(sqlError().' <sql>'.sqlLastQuery().'</sql> (at '.__LINE__.')');

		$total = sqlFoundRows();

		/* when SQL_CALC_FOUND_ROWS doesn't work - run the entire SQL again with COUNT(*)
		if ($total<0 || $total===false || $total===null) {
			$tmpsql = preg_replace("/SELECT .+ FROM `/", "SELECT COUNT(*) FROM `",preg_replace("/ LIMIT .+,.+$/", "", $sql));
			$row = sqlRun($tmpsql);
			$row = mysql_fetch_row($row);
			$total = $row[0];
		}*/

		if ($total) {
			$pages = ceil($total / $perpage);
		} else {
			$pages = 1;
			$page = 1;
			$total = 0;
		}

		// Actions action buttons
		if ($tf['act'] == 'view' && !$tf['noedit'] && $t->userCan($tf['user'], 'edit') )
			$htmlActions.='<i class="act icon-pencil csEditThisLink" title="'.fix4html2(_('Edit this record?')).'" onclick="'
							.'tfopenedit(\'?t=$tname&i=1&a=e&pp=1&d=b&ddd=$layout&te=0&id=$curid&no=1&sc=0&n=0&nn=1\',$curid,this,\'$layout\''.($tf['d']=='b'?'':",'".fix4js1(_('Edit item id').' $curid')."'").')"></i>';


		if ($tf['act'] == 'edit' && $t->userCan($tf['user'], 'edit'))
			$htmlActions.='<label class="upthis"><i class="act icon-lightbulb" title="'._('Update this record').'"></i><input name="___up[$rowc]"  type="checkbox" style="display:none;"></label>';
		if ($tf['act'] == 'edit' && $t->userCan($tf['user'], 'del'))
			$htmlActions.='<label class="delthis"><i class="act icon-trash"     title="'._('Delete this record').'" ></i><input name="___del[$rowc]" type="checkbox" style="display:none;"></label>';

		// var actions
		if (!empty($t->fetch['actions' . $tf['act']])) {
			$aActs = paramsfromstring($t->fetch['actions' . $tf['act']]);
			foreach ($aActs as $k => $v) {
				if ((stripos($k, '.jpg') || stripos($k, '.jpeg') || stripos($k, '.gif') || stripos($k, '.png')) && (!stripos($k, '<'))) {
					$k = trim($k);
					$k = "<img src=\"$k\">";
				}
				if (strpos($v, '$id')) {
					$htmlActions.="<a class=csActionLink href=\"$v\">$k</a>";
				}
			}
		}

		// Sub Tables Sub-Tables Subtables
		$htmlSubs = '';
		$keycount = 0;
		$subxkey='';
		if (!empty($t->subtables)) {
			foreach ($t->subtables as $subfetch) {
				$subname = trim($subfetch['tname']);
				if ($subname != '') {
					$sub = new TfTable($subfetch);
					if ($sub->userCan($tf['user'], 'view')) {
						foreach ($sub->fields as $f) {
							if (!empty($f->xtable) && $f->xtable == $t->tname) {
								$keycount++;
								$subxkey.="xkey{$keycount}=$f->fname&xid{$keycount}=\$curid";

								$subact = 'view';
								if ($tf['act'] == 'edit' && $sub->userCan($tf['user'], $tf['act'])) // if user can't edit, show 'view' mode
									$subact = $tf['act']{0};

								// open new <tr> with iframe below current <tr>
								$htmlSubs.='<i class="act icon-folder-open subtablelink" title="'.fix4html2($sub->fetch['label']).'" onclick="'
									."tfopensub('?t=$subname&d=l&i=1&nn=0&n=1&sc=0&no=1&a=$subact&$subxkey',\$curid,this,$keycount)".'">'."\n"
									.(($tf['d']=='b')? fix4html2($sub->fetch['label']):'').'</i>';
							}
						}
					}
				}
			}
		}
		foreach ($t->fields as $f) {
			if (!empty($f->xtable)) {
				$sub = new TfTable($f->xtable);
				if ($sub->userCan($tf['user'], 'view')) {
					$keycount++;
					$subxkey.="xkey{$keycount}=$f->xkey&xid{$keycount}=\$\$".$f->fname;

					$subact = 'view';
					if ($tf['act'] == 'edit' && $sub->userCan($tf['user'], $tf['act'])) // if user can't edit, show 'view' mode
						$subact = $tf['act']{0};

					// open new <tr> with iframe below current <tr>
					$htmlSubs.='<i class="act icon-folder-open subtablelink" title="'.fix4html2($sub->fetch['label']).'" onclick="'
						."tfopensub('?t=$f->xtable&d=l&i=1&nn=0&n=1&sc=0&no=1&a=$subact&$subxkey',\$curid,this,$keycount)".'">'."\n"
						.(($tf['d']=='b')? fix4html2($sub->fetch['label']):'').'</i>';

				}
			}
		}

		if ($tf['nooptions'] && ($tf['act'] == 'edit' || $tf['act'] == 'new')) { // send form - save changes
			$htmlActions.='<button class="btn btn-mini btn-primary" type="submit" form="idForm"><i class="act icon-thumbs-up icon-white"></i> '._('Save').'</button> ';
		}

		$title = "\n<TR class=trTitle>";
		//focus $title.="<td class=tdForFocus desc=for_focus width=1 height=1></td>";
		if (!empty($htmlActions))
			$title.="<td class='th thActions' desc=for_actions>$actionsTitle</td>";
		$countcols = 2;
		foreach ($t->fields as $f) {
			if (($tf['act'] == 'edit' && $f->userCan($tf['user'], 'edit'))
				   || ($tf['act'] == 'edit' && $tf['showview'] && $f->userCan($tf['user'], 'view'))
				   || ($tf['act'] == 'view' && $f->userCan($tf['user'], 'view'))) {
				if ($tf['showconst'] || $f->fetch['const'] === false) {
					$order = $f->fname;
					$cs = '';
					$cstd = '';
					$m = '';
					if ($orderby == $f->fname) {
						if ($orderdir)
							$order.='-';
						$cs = 'csTitleLinkOrdered';
						$cstd = 'csTitleTdOrdered';
						//$m=$orderdir? " &#x25BE;":" &#x25B4;";  // does not always work
						$m = $orderdir ? '<i class=icon-sort-down></i>' : '<i class=icon-sort-up></i>';
					}
					$title.="<td class='th $cstd'><a class='csTitleLink $cs' href=\"" . reGet(array('o' => $order)) . "\">" . ($f->fetch['label']) . "$m</a></td>";
					$countcols++;
				}
			}
		}
		if (!empty($htmlActions))
			$title.="<td class='th thActions' desc=for_actions>$actionsTitle</td>";

		$title.="\n";

		if ($tf['d']=='l') { // list
			echo '<table id="idListTable" class="listTable">';
		}

		// list
		$rowc=0;
		while ($row = mysql_fetch_assoc($masterq)) {
			if ($rowc==0 && !array_key_exists($t->pkey,$row))
				addToLog('<t>pkey='.$t->pkey.'</t> '._('Wrong value at').' <f>'.$t->fetch['label'].'</f>',LOGBAD,__LINE__);

			$rowc++;
			$curid = @$row[$t->pkey];
			if (!array_key_exists($t->pkey,$row) || $row[$t->pkey]===null) {
				addToLog(_('Primary key value is missing or null at').' <f>'.$t->fetch['label'].'</f> <t>rowc='.$rowc.' pkey='.$t->pkey.'</t> <t>curid='.var_export($curid,1).'</t>',LOGBAD,__LINE__);
				if (DEBUG) addToLog('<t>'.var_export($row,1).'</t>',LOGDEBUG,__LINE__,true);
			}
			$t->curid=$curid;
			$t->rowc=$rowc;
			$t->row=&$row;
			foreach ($t->fields as $k => $f) {
				if (isset($row[$f->fname])) $f->value=$row[$f->fname];
				elseif (isset($row['extant.'.$f->fname])) $f->value=$row['extant.'.$f->fname];
				else $f->value=null;
				$f->ename = $f->fname."[$rowc]";
				$f->eid = $f->fname.'_'.$curid;
			}

			$search=array('$curid','$rowc','$pkey','$tname','$layout');
			$replace=array(1*$curid,$rowc,$t->pkey,$t->tname,$tf['d']);
			foreach($t->fields as $f) {
				$search[]='$$'.$f->fname;
				$replace[]=$f->value;
			}
			$htmlActionsCur = str_replace($search,$replace,$htmlActions);
			$htmlSubsCur = str_replace($search,$replace,$htmlSubs);
			// repeat title in list mode
			if ($titleevery && $tf['d']=='l' && (($rowc-1) % $titleevery == 0)) // list
				echo $title;

			// <form>
			echo "<input type=hidden name='___id[$rowc]'  value='$curid'>";

			// row header for list or table header for boxes
			if ($tf['d']=='l') { // list
				echo "<tr id='cont_$rowc' class='tr tfRow'>";
				//focus echo "<td class=tdForFocus desc=for_focus><input class=inputForFocus type=text name='small_thing_for_focus[$rowc]' tabindex=-1 value=''/></td>";
			} else { // boxes
				echo "<table id='cont_$rowc' class='tableBox tfRow'>";
				//focus echo "<td class=tdForFocus desc=for_focus colspan=2><input class=inputForFocus type=text name='small_thing_for_focus[$rowc]' tabindex=-1 value='' /></td>";
			}

			// actions
			if ($htmlActions != '' || $htmlSubs!='') {
				if ($tf['d']=='l') { // list
					echo "$istr <td class='th actions' desc=for_actions>$htmlActionsCur $htmlSubsCur</td>";
				} else {
					echo "<tr class='tr trActions'><td class='th thActions'>$actionsTitle</td><td class='tdActions'>$htmlActionsCur $htmlSubsCur</td></tr>";
				}
			}

			foreach ($t->fields as $k => $f) {
				$istr = '';
				$commentview = str_ireplace(array( '$id','$val','$rowc','$c') , array($curid,$f->value,$rowc,$rowc), $f->fetch['commentview']);
				$commentedit = str_ireplace(array( '$id','$val','$rowc','$c') , array($curid,$f->value,$rowc,$rowc), $f->fetch['commentedit']);
				if ($tf['d']=='b') { // box
					$order = $f->fname;
					$cs = '';
					$cstd = '';
					$m = '';
					if ($orderby == $f->fname) {
						if ($orderdir)
							$order.='-';
						$cs = 'csTitleLinkOrdered';
						$cstd = 'csTitleTdOrdered';
						//$m=$orderdir? ' &#x25BE;':' &#x25B4;';
						$m = $orderdir ? '<i class=icon-sort-down></i>':'<i class=icon-sort-up></i>';
					}
					$istr = "<tr class='tr'><td class='th $cstd' ><a class='csTitleLink $cs' href=\"" . reGet(array('o' => $order)) . "\">" . ($f->fetch['label']) . "$m</a></td>";
				}

				// put fields - edit
				if ($tf['act'] == 'edit') {
					if ((!array_key_exists('const',$f->fetch) || $f->fetch['const'] === false) && $f->userCan($tf['user'], 'edit')) {
						echo $istr.'<td class="edit '.get_class($f).' '.$f->fname.'">'.$f->htmlInput().$commentedit.'</td>';
					} else if (($f->userCan($tf['user'], 'view') && $tf['showview'])
						   && ($tf['showconst'] || $f->fetch['const'] === false)) {
						echo $istr.'<td class="const '.get_class($f).' '.$f->fname.'">'.$f->htmlView().$commentedit.'</td>';
					}
				}

				// put fields - view
				if ($tf['act'] == 'view') {
					if (($f->userCan($tf['user'], 'view'))
						   && ($tf['showconst'] || $f->fetch['const'] === false)) {
						echo $istr.'<td class="view '.get_class($f).' '.$f->fname.'">'.$f->htmlView().$commentview.'</td>';
					}
				}
			}//foreach t

			// actions #2
			if ($htmlSubs!='') {
				if ($tf['d']=='l') { // list
					echo "$istr <td class='th actions2'>$htmlSubsCur</td>";
				} else {
					echo "<tr class='tr actions2'><td class='th'></td><td class='tdActions'>$htmlSubsCur</td></tr>";
				}
			}

			// end table for boxes
			if ($tf['d']=='b') // box
				echo "</table desc=tableBox>";

			// </form> end form
		}//while
	} // end list layout if ($tf['act']!='new')

	///////////////////////////////////////  ADD NEW /////////////////////////////////////////////////
	$htmlActions = '';
	if ($tf['act']=='new' || ($tf['act']=='edit' && $tf['news']>0)) {

		// set countcols and title if undefined
		if (!isset($countcols) || !isset($title) || !isset($istr)) {
			if ($tf['d']=='l') { // list
				echo '<table id="idListTable" class="listTable">';
				$title = '<TR class="tr tr1">';
				$title.='<td class="th thActions" desc=for_actions>'._('Add it?').'</td>';
				$countcols = 2;
				foreach ($t->fields as $f) {
					if ($f->userCan($tf['user'], 'new')) {
						//if ($f->fetch['const']===false || ($tf['showconst'] && $f->userCan($tf['user'],'view'))) {
						$title.="<td class=th>" . $f->fetch['label'] . "</td>";
						//}
						$countcols++;
					}
				}
				$title.="<td class='th thActions' desc=for_actions>" . _('Add it?').'</td>';
			} else {
				$title = '';
				$countcols = 2;
			}
		}

		if ($tf['d']=='l') // list
			echo "<tr id=idNewTitleTr class='tr csNewTitleTr'><td class=csNewTitle colspan='100%'>" . _($t->fetch['labelnew'] ? $t->fetch['labelnew'] : 'Add new items') . "</td></tr>\n";

		$htmlActions.='<label class="upthis"><i class="act icon-plus-sign" title="'._('Add this record').'"></i><input name="___up[$rowc]"  type="checkbox" style="display:none;"></label>';
		// var actions
		if (!empty($t->fetch['actions' . $tf['act']])) {
			$aActs = paramsfromstring($t->fetch['actions' . $tf['act']]);
			foreach ($aActs as $k=>$v) {
				if ((stripos($k,'.jpg')||stripos($k,'.jpeg')||stripos($k,'.gif')||stripos($k,'.png') || stripos($k,'.bmp')) && (!stripos($k,'<'))) {
					$k = trim($k);
					$k = "<img src=\"$k\">";
				}
				$htmlActions.="<a class=csActionLink href=\"$v\">$k</a>";
			}
		}

		if ($tf['nooptions'] && ($tf['act'] == 'edit' || $tf['act'] == 'new')) { // send form - save changes
			$htmlActions.='<button class="btn btn-mini btn-primary" type="submit" form="idForm"><i class="act icon-thumbs-up icon-white"></i> '._('Save').'</button> ';
		}

		for ($newscount = 0; $newscount < $tf['news']; $newscount++) {
			$rowc++;
			$curid = $rowc;

			$htmlActionsCur = str_replace(array('$curid','$id','$rowc','$c'),array($curid,$curid,$rowc,$rowc),$htmlActions);

			// <form>

			foreach ($t->fields as $k => $f) {
				// set ename
				$t->fields[$k]->eid=$f->fname.'_new'.$rowc;
				$t->fields[$k]->ename=$f->fname."[$rowc]";
				// set default values
				$t->fields[$k]->value=$f->fetch['default'];
				// set last entered values
				if (!empty($POST[$f->fname][$rowc])) {
					$t->fields[$k]->value = $POST[$f->fname][$rowc];
				}
				// set const values
				if ($t->fields[$k]->fetch['const'] !== false && array_key_exists('isconst', $t->fields[$k]->fetch)) {
					$t->fields[$k]->value = $t->fields[$k]->fetch['isconst'];
				}
			}

			// start form
			if ($tf['d']=='b') { // bpx
				echo "<table id='cont_$rowc' class='tableBox tfRow new'>";
				echo "<tr class='tr csNewTitleTr' id='cont_$rowc'><td class=csNewTitle colspan=$countcols>".($t->fetch['labelnew']?$t->fetch['labelnew']:$_('Add new')).'</td></tr>';
			}
			if ($titleevery && $tf['d']=='l' && ($newscount+$rowc) % $titleevery == 0) // list
				echo $title . "<tr>";
			// actions
			if ($tf['d']=='l') {
				echo "<tr id='cont_$rowc' class='tr tfRow'>";
				echo "<td class='tdActions' desc=for_actions>$htmlActionsCur</td>";
			} else if ($tf['d']=='b') {
				echo "<tr id='cont_$rowc' class='tr trActions trActions1'><td class='th thActions'>"._('Add?')."</td><td class='tdActions'>$htmlActionsCur</td></tr>";
			}

			// put fields
			foreach ($t->fields as $k => $f) {
				$istr = '';
				if ($tf['d']=='b')
					$istr = "<tr class='tr'><td class=th>" . $f->fetch['label'] . "</td>";
				$val = $f->value;
				if (is_array($val)) {
					addToLog(_('Array as value at').' <f>'.$f->fetch['label'].'</f>',LOGDEBUG,__LINE__);
					$val = var_export($val, 1); // avoid Array to String conversions
				}
				$commentview = str_ireplace('$id', $curid, str_ireplace('$val', $val, $f->fetch['commentview']));
				$commentnew = str_ireplace ('$id', $curid, str_ireplace('$val', $val, $f->fetch['commentnew']));
				if ($f->userCan($tf['user'], 'new')) {
					if ($f->fetch['const'] !== false) {
						$f->value = $f->fetch['const'];
						if ($tf['showconst'] && $f->userCan($tf['user'], 'view')) {
							echo $istr.'<td class="new const '.get_class($f).' '.$f->fname.'">'.$f->htmlView().$commentnew.'</td>';
						}
						echo $f->htmlQuietNew();
					} else {
						echo $istr.'<td class="new '.get_class($f).' '.$f->fname.' ">'.$f->htmlNew().$commentnew.'</td>';
					}
				} elseif (($f->userCan($tf['user'], 'view') || ($f->userCan($tf['user'], 'edit') && $tf['act'] == 'edit')) && $tf['d']=='l' && $tf['act'] != 'new') {
					echo "<td></td>";
				}
			}

			if (false && $htmlActionsCur != '') {
				if ($tf['d']=='l') {
					echo "$istr <td class='th actions' desc=for_actions>$htmlActionsCur</td>";
				} else {
					echo "<tr class='tr trActions trActions2'><td class='th thActions'>"._('Add?')."</td><td class='tdActions'>$htmlActionsCur</td></tr>";
				}
			}

			if ($tf['d']=='l') echo "</tr>";
			if ($tf['d']=='b') echo "</table>";
		}//for news
	}//if news

	// end big list table
	if ($tf['d']=='l')
		echo "</table>";

	// process htmlFormEnd()
	foreach ($t->fields as $f) // per every field in this table
		echo $f->htmlFormEnd();

?>
</form>
<?
	if (!$tf['nopage']) {

		echo '<div id=idPaging class="nav nav-pills navbar-fixed-bottom n1avbar-inner">';

		if ($pages == 1 && $page == 1) {

			//echo "<button id=idPgPage1 disabled>" . _('Page') . " 1 " . _('of') . " 1" . "</button>";

		} else {

			if (!$tf['mini']) {
				if ($page > 1) {
					echo "<div class='btn btn-mini disabled' id=idPgPrevTd><a id=idPgPrevLink    class=csPgLink href=\"" . reGet(array('p' => ($page - 1))) . "\"><i class='act icon-backward'></i></a></div>";
				} else {
					echo "<div class='btn btn-mini disabled' id=idPgPrevTd><a id=idPgPrevLinkDis class='csPgLink disabled' $emptyhref><i class='act disabled icon-backward icon-white'></i></a></div>";
				}
			}

			echo "<div class='btn btn-mini disabled' id=idPgPage><select id=idPgPageSelect name=page onChange=\"reget({p:this.options[this.selectedIndex].value})\">";
			if ($page > $pages)
				echo "<option></option>";
			for ($i = 1; $i <= $pages; $i++) {
				echo "<option value='$i' " . ($page == $i ? 'selected' : '') . ">$i</option>";
			}
			echo "</select>/$pages";
			echo "</div>";

			if (!$tf['mini']) {
				if ($page < $pages) {
					echo "<div class='btn btn-mini disabled' id=idPgNextTd><a id=idPgNextLink class=csPgLink href=\"" . reGet(array('p' => ($page + 1))) . "\"><i class='act icon-forward'></i></a></div> ";
				} else {
					echo "<div class='btn btn-mini disabled' id=idPgNextTd><a id=idPgNextLinkDis class='csPgLink disabled' $emptyhref'><i class='act disabled icon-forward icon-white'></i></a></div> ";
				}
			}
		}

		if (!$tf['mini']) {
			echo "<div class='btn btn-mini disabled' id=idPgTotal>" . _('Total') . " $total " . _('records') . "</div> ";
		}

		if (!$tf['mini']) {
			echo '<div class="btn btn-mini disabled" id=idCtrlPerpage>'
					._('Show')
					.'<select id=idCtrlPerpageSelect name=pp onChange="this.value=this.options[this.selectedIndex].value;reget({pp:this.value,'
					 // calculate new current page that will keep current first record on screen
					."p:1+Math.floor(($perpage/this.value)*($page-1))})\">";
				if ($perpage>5) echo '<option selected>'.$perpage;
				for ($i = 1;   $i <=   5; $i++   ) echo '<option'.($i==$perpage?' selected>':'>').$i;
				for ($i = 10;  $i <  100; $i+=10 ) echo "<option>$i";
				for ($i = 100; $i <= 500; $i+=100) echo "<option>$i";
			echo '</select>' . _('per page').'</div> ';
		}

		if (!$tf['mini']) {
			// reapeat title evey so often
			if ($tf['d']=='l' && $total > 5 && $perpage > 5) {
				echo "<div class='btn btn-mini disabled' id=idPgRepeatTitle>";
				$te_options = array(1, 5, 10, 20, 50, 100, 200, 300, 400, 500);

				echo _('Titles every') . "<select id=idPgRepeatTitleSelect name=te onChange=\"reget({te:this.options[this.selectedIndex].value})\">";
				if ($titleevery && !in_array($titleevery, $te_options)) {
					echo "<option value='$titleevery' selected>$titleevery</option>";
				}
				for ($i = 0; $i < count($te_options); $i++) {
					echo "<option value='" . $te_options[$i] . "' " . ($titleevery == $te_options[$i] ? 'selected' : '') . ">" . $te_options[$i] . "</option>";
				}
				echo "<option value='never' " . ($titleevery ? '' : 'selected') . "> X </option>";
				echo "</select>";
				echo "</div> ";
			}
		}
		echo '</div>';
	}//if $tf['nopage']

?>
<div id=idLaterLog style='display:none'><?=$tf['log']?></div>

<script type='text/javascript'>
	DEBUG=<?=1*DEBUG?>;
	ROWS=<?=$rowc?>;
	DISCARD_CHANGES=<?=$tf['act']=='view'?'null':'"'._('Discard Changes?').'"'?>;

	if (typeof(tfFormLoad)=='undefined' || typeof(tfFormSubmit)=='undefined') {
		alert('ERROR LOADING JAVASCRIPT tf.js');
		$('#idForm').hide();
	}
	tfFormLoad(document.frm);

	if (document.getElementById('idLaterLog').innerHTML) {
		addToLog(document.getElementById('idLaterLog'));
	}
</script>
<?
}//function DisplayTable
