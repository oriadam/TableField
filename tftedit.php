<?php
global $tf;
require_once(__DIR__.'/inc/include.php');

$tblinfo = sqlf($tf['tbl.info']);
$tblmeta=sqlf($tf['tbl.meta']);

include(__DIR__.'/inc/header.php');

/*
  if (!tfCheckLogin()) {
  fatal('Please <a href=login.php>login</a>.',90000);
  exit;
  } */
$user = tfGetUserGroup();
if (empty($user)) {
	fatal("Please <A href='./'>Login</a>");
}

// check user permissions
if (!TftUserCan($user, 'edit', '', '')) {
	if ($tf['debug']) {
		fatal("User group $user have no super-admin edit permissions");
	} else {
		fatal("Error parsing tftedit.php file. Please try again later.", 90001);
	}
	exit;
}

echo "<div></div>
<style>
td,th {vertical-align:top; }
</style>
";

$tname = strtolower(Get('tname'));
if ($tname == '')
	$tname = strtolower(Get('table'));
$fname = strtolower(Get('fname'));
if ($fname == '')
	$fname = strtolower(Get('field'));
$table = TftFetchTable($tname);
if (empty($table)) {
	echo "<script type='text/javascript'>
    window.alert('table ".(DEBUG? $tname:'')." not found!');
  </script>";
	$tname = '';
}
$tnamev=sqlv($tname);

$Go = '  ' . _('GO') . '  ';
$Do = '  ' . _('DO') . '  ';

$mode = strtolower(Get('mode'));

echo "
  <form name=big method=get>
  what?
  <select name=mode>
  <option " . ($mode == '' ? 'selected' : '') . " value=''            >" . _('-- Select mode --') . "</option>
  <option " . ($mode == 'class' ? 'selected' : '') . " value='class'       >" . _('Field class and parameters') . "</option>
  <option " . ($mode == 'order' ? 'selected' : '') . " value='order'       >" . _('Display order of fields +indexed? +searchable?') . "</option>
  <option " . ($mode == 'sort' ? 'selected' : '') . " value='sort'     >" . _('Default sort in table') . "</option>
  <option " . ($mode == 'limits' ? 'selected' : '') . " value='limits'      >" . _('Limits +null? +empty?') . "</option>
  <option " . ($mode == 'permissions' ? 'selected' : '') . " value='permissions' >" . _('Permissions (can be used to hide fields)') . "</option>
  <option " . ($mode == 'labels' ? 'selected' : '') . " value='labels'      >" . _('Fields names') . "</option>
  <option " . ($mode == 'comments' ? 'selected' : '') . " value='comments'    >" . _('Comments and actions') . "</option>
  <option " . ($mode == 'edit' ? 'selected' : '') . " value='edit'        >" . _('Database changes - edit table and fields') . "</option>
  </select>
  &nbsp;&nbsp;&nbsp;
  where?
  <select name=tname placeholder=\""._('Select Table')."\">
  <option value=''>(general)</option>
  ";
$res = TftAllTables();
foreach ($res as $row) {
	echo "<option " . ($tname == $row ? 'selected' : '') . " value='" . fix4html1($row) . "' >$row</option>";
}
echo "
  </select>
  &nbsp;&nbsp;&nbsp;
  <input type=submit value='$Go' class=butgo>
  &nbsp;&nbsp;&nbsp;
  <a href='" . reGet() . "' style='font-size:90%' class=butgo>Undo (reload page)</a>

  <hr class=hr align=left width='36%' />
  </form name=big>
  ";

if ($mode == 'order') {
	if (Post('datasent') == 'yeah baby') {
		if ($tname == '') {
			foreach ($_POST['fields'] as $k => $fname) {
				sqlRun("UPDATE $tblinfo SET `order`=".sqlv(1 * $_POST['orders'][$k])." WHERE `tname`=".sqlv($fname)." AND `fname`=''");
			}
		} else {
			foreach ($_POST['fields'] as $k => $fname) {
				// index may not always be valid, because of the way POST handles unchecked boxes.
				// that's why i have added @ on these lines:
				@$index = ($_POST['indexs'][$k]) ? '1' : '0';
				@$search = ($_POST['searchs'][$k]) ? '1' : '0';
				sqlRun("UPDATE $tblinfo SET `order`=".sqlv(1 * $_POST['orders'][$k]).",`indexed`=$index,`searchable`=$search WHERE `tname`=$tnamev AND `fname`=".sqlv($fname));
			}
		}
	}

	echo "
  <form accept-charset='iso-8859-1,utf-8' enctype='multipart/form-data' name=frm method=post action='" . reGet() . "'>
  <input type=hidden name=datasent value='yeah baby' />
  <table class=tftedit border=0><th></th><th class=th align=center>" . _('order') . "</th><th class=th align=center>" . _('index') . "?</th><th class=th align=center>" . _('search') . "?</th>";
	if ($tname == '') {
		$res = sqlRun("SELECT `tname`,`order` FROM $tblinfo WHERE `fname`='' ORDER BY `order` DESC,`label`,`tname`");
	} else {
		$res = sqlRun("SELECT `fname`,`order`,`indexed`,`searchable` FROM $tblinfo WHERE `tname`=$tnamev AND `fname`<>'' ORDER BY (`fname`='') DESC,`order` DESC,`fname`");
	}
	$cnt = 0;
	while ($row = mysql_fetch_row($res)) {
		$tr = 'class=tr' . ((($cnt % 2) == 0) ? '1' : '2');
		echo "<tr $tr><th class=th align=left>$row[0]</th><td class=td align=right>
    <input type=hidden name='fields[$cnt]' value='" . fix4html1($row[0]) . "'>
    <select name='orders_select[$cnt]' onChange=\"document.frm['orders[$cnt]'].value=this.value;\">";
		if ($row[1] != 0)
			echo "<option value='" . fix4html1($row[1]) . "'>$row[1]</option>";
		echo "<option value='0'>Default (0)</option>";
		echo "<option value='1000'>1000</option>";
		for ($i = 100; $i >= -100; $i-=10) {
			echo "<option value='$i'>$i</option>";
		}
		echo "<option value='-1000'>-1000</option>";
		echo "</select> <input name='orders[$cnt]' type=number size=3 value='" . fix4html1($row[1]) . "' /></td>";
		if ($tname != '') {
			echo "<td class=td align=center><input name='indexs[$cnt]'  type=checkbox value='" . fix4html1($row[2]) . "' " . ($row[2] ? 'checked  ' : 'unchecked') . " onChange='this.value=this.checked;' /></td>";
			echo "<td class=td align=center><input name='searchs[$cnt]' type=checkbox value='" . fix4html1($row[3]) . "' " . ($row[3] ? 'checked  ' : 'unchecked') . " onChange='this.value=this.checked;' /></td>";
		}
		$cnt++;
	}
	echo "
  </table><br>
  <input type=submit value='$Do' class=butgo>
  </form name=frm>
  ";
}

if ($mode == 'sort') {
	if (Post('datasent') == 'yeah baby') {
		foreach ($_POST['fields'] as $k => $fname) {
			@$odir = sqlv(chkbox($_POST['odirs'][$k]) ? '1' : '0');
			sqlRun("UPDATE $tblinfo SET `orderby`=".sqlv(1 * $_POST['orderbys'][$k]).",`odirasc`=$odir WHERE `tname`=$tnamev AND `fname`=".sqlv($fname));
		}
	}

	echo "
  <form accept-charset='iso-8859-1,utf-8' enctype='multipart/form-data' name=frm method=post action='" . reGet() . "'>
  <script type='text/javascript'>
    function odirSwitch(field,img){
      if (field.value=='') field.value=0;
      field.value=1-field.value;
      odirImg(img,field.value);
    }
    function odirImg(img,val){
      if (val==1){
        img.className='icon-sort-up';
        img.title='" . _('Ascending') . "';
      }else{
        img.className='icon-sort-down';
        img.title='" . _('Descending') . "';
      }
    }
  </script>
  <input type=hidden name=datasent value='yeah baby' />
  <table class=tftedit border=0><th></th><th class=th align=center>" . _('Order by') . "</th><th class=th align=center>" . _('Direction') . "</th>";
	$res = sqlRun("SELECT `fname`,`orderby`,`odirasc`,(`fname`='') as `isfname` FROM $tblinfo WHERE `tname`=$tnamev ORDER BY isfname DESC,`orderby` DESC");
	$cnt = 0;
	while ($row = mysql_fetch_row($res)) {
		echo "<tr><th align=left class=th>" . ($row[0] == '' ? '<i>Table</i>' : fix4html1($row[0])) . "</th><td align=right class=td>
    <input type=hidden name='fields[$cnt]' value='" . fix4html1($row[0]) . "'>
    <select name='orderbys_select[$cnt]' onChange=\"document.frm['orderbys[$cnt]'].value=this.value;\">";
		if ($row[1] != 0)
			echo "<option value='" . fix4html1($row[1]) . "'>$row[1]</option>";
		echo "<option value='0'>dont orderby (0)</option>";
		for ($i = 100; $i >= -100; $i-=10) {
			echo "<option value='$i'>$i</option>";
		}
		echo "</select> <input name='orderbys[$cnt]' type=number size=3 value='" . fix4html1($row[1]) . "' /></td><td class=td align=center>";
		echo "<input type=hidden name='odirs[$cnt]' value='" . fix4html1($row[2]) . "' /><i id='odirs_img[$cnt]' onClick=\"document.frm['odirs[$cnt]'].value=1-document.frm['odirs[$cnt]'].value;odirImg(this,document.frm['odirs[$cnt]'].value);\" class=\"".($row[2]?'icon-chevron-up' : 'icon-chevron-down').'" title="'._($row[2] ? 'asc' : 'desc') . '"></i>';
		echo "</td></tr>";
		$cnt++;
	}
	echo "
  </table><br>
  <input type=submit value='$Do' class=butgo>
  </form name=frm>
  ";
}

if ($mode == 'class') {
	if (!empty($_POST['datasent'])) {
		foreach ($_POST['fields'] as $k => $fname) {
			sqlRun("UPDATE $tblinfo SET `class`=". sqlv($_POST['classs'][$k]) . " WHERE `tname`=$tnamev AND `fname`=" . sqlv($fname));
			foreach ($_POST["metakey$k"] as $kk => $meta)
				if (!empty($meta))
					if (empty($_POST["metadelete$k"][$kk]))
						sqlRun("REPLACE INTO $tblmeta (`tname`,`fname`,`key`,`value`) VALUES ($tnamev,".sqlv($fname).",".sqlv($meta).",".sqlv($_POST["metavalue$k"][$kk]).")");
					else
						sqlRun("DELETE FROM $tblmeta WHERE `tname`=$tnamev AND `fname`=".sqlv($fname)." AND `key`=".sqlv($meta));
		}
	}

	echo "
  <form accept-charset='iso-8859-1,utf-8' enctype='multipart/form-data' name=frm method=post action='" . reGet() . "'>
  <input type=hidden name=datasent value='yeah baby' />
  <table class=tftedit border=0><th class=none></th><th class=th align=center>Class</th><th class=th align=center>Meta Params</th><tr>";
	/*
	$known = array();
	$res = sqlRun("SELECT DISTINCT `class` FROM $tblinfo ORDER BY `class` ASC");
	if (!$res) {
		echo "<div class=text-info>mysql error reading classes: ".mysql_error()."</div>";
	} else {
		while ($row = mysql_fetch_row($res)) {
			if (!empty($row[0]))
				$known[] = $row[0];
		}
	}*/
	$known=array();
	foreach (get_declared_classes() as $k=>$v)
		if (strpos($v,'TfType')===0)
			$known[]=substr($v,strlen('TfType'));
	sort($known);
	$res = sqlRun("SELECT `fname`,`class` FROM $tblinfo WHERE `tname`=$tnamev ORDER BY (`fname`='') DESC,`order` DESC,`fname`");
	$cnt = 0;
	while ($row = mysql_fetch_row($res)) {
		$tr = 'class=tr' . ((($cnt % 2) == 0) ? '1' : '2');
		echo
		"<tr $tr><th class=th align=left>" . ($row[0] == '' ? '<i>Table</i>' : $row[0]) . "</th><td class=td align=right>"
			."<input type=hidden name='fields[$cnt]' value='" . fix4html1($row[0]) . "'>"
			."<select name='classs[$cnt]'>"
				."<option selected>$row[1]";
				foreach ($known as $cl)
					echo "<option>$cl";
		echo "</select> </td><td class=td>";
		// params
		$res2 = sqlRun("SELECT * FROM $tblmeta WHERE `tname`=$tnamev AND `fname`=".sqlv($row[0]));
		$cnt2=0;
		while ($row2 = mysql_fetch_assoc($res2)) {
			echo "<div class=metarow>"
					."<input name='metakey$cnt"."[$cnt2]' value='".fix4html1($row2['key'])."'> = "
					."<input name='metavalue$cnt"."[$cnt2]' size=100 value='".fix4html1($row2['value'])."'> "
					."<label><input name='metadelete$cnt"."[$cnt2]' type='checkbox'>"._('Delete')."</label>"
				."</div>";
			$cnt2++;
		}
		echo "<div class=metarow>"
				."<input name='metakey$cnt"."[$cnt2]' value='".fix4html1($row2['key'])."'> = "
				."<input name='metavalue$cnt"."[$cnt2]' size=100 value='".fix4html1($row2['value'])."'> "
				."<label>"._('Add')."</label>"
			."</div>";
		$cnt2++;
		echo "<div class=metarow>"
				."<input name='metakey$cnt"."[$cnt2]' value='".fix4html1($row2['key'])."'> = "
				."<input name='metavalue$cnt"."[$cnt2]' size=100 value='".fix4html1($row2['value'])."'> "
				."<label>"._('Add')."</label>"
			."</div>";

		echo "</td></tr>";
		$cnt++;
	}
	echo "
  </table><br>
  <input type=submit value='$Do' class=butgo>
  </form name=frm>
  ";
}


if ($mode == 'permissions') {
	if (Post('datasent') == 'yeah baby') {
		foreach ($_POST['fields'] as $k => $fname) {
			sqlRun("UPDATE $tblinfo SET `usersview`=".sqlv($_POST['views'][$k]).",`usersedit`=".sqlv($_POST['edits'][$k]).",`usersnew`=".sqlv($_POST['news'][$k]).",`usersdel`=".sqlv($_POST['dels'][$k])." WHERE `tname`=$tnamev AND `fname`=".sqlv($fname));
		}
	}
	echo "
  <a href='?t=" . $tf['tbl.users'] . "&a=e' target=_blank>Edit tf users table</a> -- <b>`group`</b> is the field relevant to table permissions<br>
  <form accept-charset='iso-8859-1,utf-8' enctype='multipart/form-data' name=frm method=post action='" . reGet() . "'>
  <input type=hidden name=datasent value='yeah baby' />
  <table class=tftedit>
  <th class=none></th><th class=th align=center>" . _('view') . "</th><th class=th align=center>" . _('edit') . "</th><th class=th align=center>" . _('new') . "</th><th class=th align=center>" . _('del') . "</th><th align=left style='font-size:74%'><b><i>Mass<br>-Fill</i></b></th>";

	$known = array(); // suggestions
	$res = sqlRun("
      SELECT DISTINCT `usersview` as `names` FROM $tblinfo
      UNION
      SELECT DISTINCT `usersedit` as `names` FROM $tblinfo
      UNION
      SELECT DISTINCT `usersnew`  as `names` FROM $tblinfo
      UNION
      SELECT DISTINCT `usersdel`  as `names` FROM $tblinfo
      ORDER BY `names` ASC");
	while ($row = mysql_fetch_row($res)) {
		if (!in_array($row[0], $known))
			$known[] = $row[0];
	}
	$res = sqlRun("SELECT DISTINCT `group` FROM `" . $tf['tbl.users'] . "` ORDER BY `group` ASC");
	while ($row = mysql_fetch_row($res)) {
		if (!in_array($row[0], $known))
			$known[] = $row[0];
	}

	$res = sqlRun("SELECT `fname`,`usersview`,`usersedit`,`usersnew`,`usersdel` FROM $tblinfo WHERE `tname`=$tnamev ORDER BY (`fname`='') DESC,`order` DESC,`fname`");
	$cnt = 0;
	while ($row = mysql_fetch_row($res)) {
		$tr = 'class=tr' . ((($cnt % 2) == 0) ? '1' : '2');
		$name = $row[0];
		if ($name == '')
			$name = '<i>' . _('Table') . '</i>';
		echo "
    <tr $tr><th class=th style='text-align:left;'>$name</td><td align=right>";
		echo "<input type=hidden name='fields[$cnt]' value='" . fix4html1($row[0]) . "'>";

		echo "<select name='views_select[$cnt]' style='width:61px;' onChange=\"document.frm['views[$cnt]'].value=this.value;\">";
		echo "<option value='" . fix4html1($row[1]) . "'>$row[1]</option>";
		foreach ($known as $cl) {
			if ($cl != $row[1])
				echo "<option value='" . fix4html1($cl) . "'>$cl</option>";
		}
		echo "</select> <input name='views[$cnt]' type=text size=18 value='" . fix4html1($row[1]) . "' />&nbsp;";

		echo "</td><td>";
		echo "<select name='edits_select[$cnt]' style='width:61px;' onChange=\"document.frm['edits[$cnt]'].value=this.value;\">";
		echo "<option value='" . fix4html1($row[2]) . "'>$row[2]</option>";
		foreach ($known as $cl) {
			if ($cl != $row[2])
				echo "<option value='" . fix4html1($cl) . "'>$cl</option>";
		}
		echo "</select> <input name='edits[$cnt]' type=text size=18 value='" . fix4html1($row[2]) . "' />&nbsp;";

		echo "</td><td>";
		echo "<select name='news_select[$cnt]' style='width:61px;' onChange=\"document.frm['news[$cnt]'].value=this.value;\">";
		echo "<option value='" . fix4html1($row[3]) . "'>$row[3]</option>";
		foreach ($known as $cl) {
			if ($cl != $row[3])
				echo "<option value='" . fix4html1($cl) . "'>$cl</option>";
		}
		echo "</select> <input name='news[$cnt]' type=text size=18 value='" . fix4html1($row[3]) . "' />&nbsp;";

		echo "</td><td>";
		echo "<select name='dels_select[$cnt]' style='width:61px;' onChange=\"document.frm['dels[$cnt]'].value=this.value;\">";
		echo "<option value='" . fix4html1($row[4]) . "'>$row[4]</option>";
		foreach ($known as $cl) {
			if ($cl != $row[4])
				echo "<option value='" . fix4html1($cl) . "'>$cl</option>";
		}
		echo "</select> <input name='dels[$cnt]' type=text size=18 value='" . fix4html1($row[4]) . "' />&nbsp;";

		echo "</td>";

		echo "<td align=left><a class=csMassFill onclick=\"massFillN($cnt)\" href='#'>&#x21DA;</a></td>";

		echo "</tr>";
		$cnt++;
	}

	echo "<tr><td><b><i>Mass-Fill:</i></b></td>";
	echo "<td align=center><a class=csMassFill onclick=\"massFill('views')\" href='#'>&#x21D1;</a></td>";
	echo "<td align=center><a class=csMassFill onclick=\"massFill('edits')\" href='#'>&#x21D1;</a></td>";
	echo "<td align=center><a class=csMassFill onclick=\"massFill('news' )\" href='#'>&#x21D1;</a></td>";
	echo "<td align=center><a class=csMassFill onclick=\"massFill('dels' )\" href='#'>&#x21D1;</a></td>";

	echo "</tr></table><br>";

	echo "<b><i>Mass-Fill</i> &nbsp;with this user group:</b> ";
	echo "<select onChange=\"document.getElementById('idMassFill').value=this.value;\">";
	echo "<option value=''></option>";
	foreach ($known as $cl) {
		if ($cl != $row[1])
			echo "<option value='" . fix4html1($cl) . "'>$cl</option>";
	}
	echo "</select> <input id='idMassFill' type=text size=18 value='' />&nbsp;";
	echo "<script type='text/javascript'>
    function massFill(group) {
      var v=document.getElementById('idMassFill').value;
      for (var i=0;i<$cnt;i++) {
        document.frm[group+'['+i+']'].value=v;
      }
    }
    function massFillN(i) {
      var v=document.getElementById('idMassFill').value;
      document.frm['views['+i+']'].value=v;
      document.frm['edits['+i+']'].value=v;
      document.frm['news[' +i+']'].value=v;
      document.frm['dels[' +i+']'].value=v;
    }
    </script>";

	echo "
  <br>
  <input type=submit value='$Do' class=butgo>
  </form name=frm>
  ";
}


if ($mode == 'labels') {
	if (Post('datasent') == 'yeah baby') {
		foreach ($_POST['fields'] as $k => $fname) {
			sqlRun("UPDATE $tblinfo SET `label`=".sqlv($_POST['labels'][$k])." WHERE `tname`=$tnamev AND `fname`=".sqlv($fname));
		}
	}

	echo "
  <form accept-charset='iso-8859-1,utf-8' enctype='multipart/form-data' name=frm method=post action='" . reGet() . "'>
  <input type=hidden name=datasent value='yeah baby' />
  <table class=tftedit>
  <th class=none></th><th class=th align=center>" . _('label') . "</th>";
	$res = sqlRun("SELECT `fname`,`label` FROM $tblinfo WHERE `tname`=$tnamev ORDER BY (`fname`='') DESC,`order` DESC,`fname`");
	$cnt = 0;
	while ($row = mysql_fetch_row($res)) {
		$tr = 'class=tr' . ((($cnt % 2) == 0) ? '1' : '2');
		$name = $row[0];
		if ($name == '')
			$name = '<i>' . _('Table') . '</i>';
		echo "
    <tr $tr><th class=th style='text-align:left;'>$name</th>";
		echo "<input type=hidden name='fields[$cnt]' value='" . fix4html1($row[0]) . "'>";
		echo "<td class=td><input name='labels[$cnt]'  type=text size=18 value='" . fix4html1($row[1]) . "' /></td>";
		echo "</tr>";
		$cnt++;
	}
	echo "
  </table><br>
  <input type=submit value='$Do' class=butgo>
  </form name=frm>
  ";
}


if ($mode == 'comments') {
	if (Post('datasent') == 'yeah baby') {
		foreach ($_POST['fields'] as $k => $fname) {
			sqlRun("UPDATE $tblinfo SET
			   `commentedit`=".sqlv($_POST['commentedit'][$k])
			.',`commentview`='.sqlv($_POST['commentview'][$k])
			.',`commentnew`='.sqlv($_POST['commentnew'][$k])
			.',`commentdel`='.sqlv($_POST['commentdel'][$k])
			.',`actionsedit`='.sqlv($_POST['actionsedit'][$k])
			.',`actionsview`='.sqlv($_POST['actionsview'][$k])
			.',`actionsnew`='.sqlv($_POST['actionsnew'][$k])
			." WHERE `tname`=$tnamev AND `fname`=".sqlv($fname));
		}
	}

	echo "
  <form accept-charset='iso-8859-1,utf-8' enctype='multipart/form-data' name=frm method=post action='" . reGet() . "'>
  <input type=hidden name=datasent value='yeah baby' />
  <table class=tftedit>
   <th class=none></th>"
	. "<th class=th align=center>" . _('view') . "</th>"
	. "<th class=th align=center>" . _('edit') . "</th>"
	. "<th class=th align=center>" . _('new') . "</th>"
	. "<th class=th align=center>" . _('del') . "</th>"
	. "<th class=th align=center>" . _('action') . ' ' . _('view') . "</th>"
	. "<th class=th align=center>" . _('action') . ' ' . _('edit') . "</th>"
	. "<th class=th align=center>" . _('action') . ' ' . _('new') . "</th>"
	;
	$res = sqlRun("SELECT `fname`,`commentview`,`commentedit`,`commentnew`,`commentdel`,`actionsview`,`actionsedit`,`actionsnew` FROM $tblinfo WHERE `tname`=$tnamev ORDER BY (`fname`='') DESC,`order` DESC,`fname`");
	$cnt = 0;
	while ($row = mysql_fetch_row($res)) {
		$tr = 'class=tr' . ((($cnt % 2) == 0) ? '1' : '2');
		$name = $row[0];
		if ($name == '')
			$name = '<i>' . _('Table') . '</i>';
		echo "
    <tr $tr><th class=th style='text-align:left;'>$name</th>";
		echo "<input type=hidden name='fields[$cnt]' value='" . fix4html1($row[0]) . "'>";
		echo "<td class=td><input name='commentview[$cnt]' type=text size=18 value='" . fix4html1($row[1]) . "' /></td>";
		echo "<td class=td><input name='commentedit[$cnt]' type=text size=18 value='" . fix4html1($row[2]) . "' /></td>";
		echo "<td class=td><input name='commentnew[$cnt]'  type=text size=18 value='" . fix4html1($row[3]) . "' /></td>";
		echo "<td class=td><input name='commentdel[$cnt]'  type=text size=18 value='" . fix4html1($row[4]) . "' /></td>";
		echo "<td class=td><input name='actionsview[$cnt]' type=text size=18 value='" . fix4html1($row[5]) . "' /></td>";
		echo "<td class=td><input name='actionsedit[$cnt]' type=text size=18 value='" . fix4html1($row[6]) . "' /></td>";
		echo "<td class=td><input name='actionsnew[$cnt]'  type=text size=18 value='" . fix4html1($row[7]) . "' /></td>";
		echo "</tr>";
		$cnt++;
	}
	echo "
  </table><br>
  <input type=submit value='$Do' class=butgo>
  </form name=frm>
  ";
}



if ($mode == 'limits') {
	if (Post('datasent') == 'yeah baby') {
		foreach ($_POST['fields'] as $k => $fname) {
			$okmax = $_POST['okmaxs'][$k];
			if (!is_numeric($okmax) && $okmax == '') {
				$okmax = 'NULL';
			} else {
				$okmax = sqlv($okmax);
			}
			$okmin = $_POST['okmins'][$k];
			if (!is_numeric($okmin) && $okmin == '') {
				$okmin = 'NULL';
			} else {
				$okmin = sqlv($okmin);
			}
			@$okempty = sqlv(chkbox($_POST['okemptys'][$k]) ? '1' : '0');
			@$oknull = sqlv(chkbox($_POST['oknulls'][$k]) ? '1' : '0');
			sqlRun("UPDATE $tblinfo SET `okmax`=$okmax,`okmin`=$okmin,`okempty`=$okempty,`oknull`=$oknull,`default`=".sqlv($_POST['defaults'][$k])." WHERE `tname`=$tnamev AND `fname`=".sqlv($fname));
		}
	}

	echo "
  <form accept-charset='iso-8859-1,utf-8' enctype='multipart/form-data' name=frm method=post action='" . reGet() . "'>
  <input type=hidden name=datasent value='yeah baby' />
  <table class=tftedit>
  <th class=none></th><th class=th align=center>" . _('Minimum') . "</th><th class=th align=center>" . _('Maximum') . "</th><th class=th align=center>" . _('Empty') . '?' . "</th><th class=th align=center>" . _('NULL') . '?' . "</th><th class=th align=center>Default value <small>(<i>null</i> for null)</small></th>";
	$res = sqlRun("SELECT `fname`,`okmin`,`okmax`,`okempty`,`oknull`,`default` FROM $tblinfo WHERE `tname`=$tnamev ORDER BY (`fname`='') DESC,`order` DESC,`fname`");
	$cnt = 0;
	while ($row = mysql_fetch_row($res)) {
		$tr = 'class=tr' . ((($cnt % 2) == 0) ? '1' : '2');
		$name = $row[0];
		if ($name == '')
			$name = '<i>' . _('Table') . '</i>';
		echo "
    <tr $tr><th class=th style='text-align:left;'>$name</th>";
		echo "<input type=hidden name='fields[$cnt]' value='" . fix4html1($row[0]) . "'>";
		echo "<td class=td><input name='okmins[$cnt]'    type=number size=6 value='" . fix4html1($row[1]) . "' /></td>";
		echo "<td class=td><input name='okmaxs[$cnt]'    type=number size=6 value='" . fix4html1($row[2]) . "' /></td>";
		echo "<td class=td align=center><input name='okemptys[$cnt]' type=checkbox value='" . fix4html1($row[3]) . "' " . ($row[3] ? 'checked  ' : 'unchecked') . " onChange='this.value=this.checked;' /></td>";
		echo "<td class=td align=center><input name='oknulls[$cnt]'  type=checkbox value='" . fix4html1($row[4]) . "' " . ($row[4] ? 'checked  ' : 'unchecked') . " onChange='this.value=this.checked;' /></td>";
		echo "<td class=td><input name='defaults[$cnt]'    type=text size=36 value='" . fix4html1($row[5]) . "' /></td>";
		echo "</tr>";
		$cnt++;
	}
	echo "
  </table><br>
  <input type=submit value='$Do' class=butgo>
  </form name=frm>
  ";
}



if ($mode == 'edit') {
	if (Post('datasent') == 'yeah baby') {

		// delete this table and all it's content
		if (Post('delthistable') == 'DelTable') {
			$ok = sqlRun("DELETE FROM $tblinfo WHERE `tname`=$tnamev");
			if ($ok) {
				sqlRun("DELETE FROM $tblmeta WHERE `tname`=$tnamev");
				echo "<div style='color:green'>Table removed completely $tname ** reload page to see changes</div>";
			} else {
				echo "<div style='color:red'>Failed to remove Table $tname</div>";
			}
		} else {

			// add a new table
			@$newtable = strtolower(trim($_POST['newtable']));
			if ($newtable != '') {
				if ("'$newtable'" != sqlv($newtable) || "`$newtable`" != sqlf($newtable)) {
					echo "<div style='color:red'>illegal table name</div>";
				} else {
					$ok = sqlRun("INSERT INTO $tblinfo (`tname`,`fname`,`label`) VALUES(".sqlv($newtable).",''," .sqlv(ucwords($newtable)). ")");
					if ($ok) {
						echo "<div style='color:green'>Table added $newtable ** reload page to see changes </div>";
					} else {
						echo "<div style='color:red'>Failed to add Table $newtable</div>";
					}
				}
			}

			// add new fields
			if (!empty($_POST['newfields'])) {
				foreach ($_POST['newfields'] as $k => $newfield) {
					$newfield = trim($_POST['newfields'][$k]);
					if ($newfield != '') {
						// get users from table general info
						$sql="SELECT `usersview`,`usersedit`,`usersdel`,`usersnew` FROM $tblinfo WHERE tname=$tnamev AND fname=''";
						$res=mysql_query($sql);
						$row=mysql_fetch_row($res);
						$sql="INSERT INTO $tblinfo (`tname`,`fname`,`label`,`oknull`,`usersview`,`usersedit`,`usersdel`,`usersnew`) VALUES ($tnamev,".sqlv($newfield).",".sqlv(ucwords($newfield)).',1,'.sqlv($row[0]).','.sqlv($row[1]).','.sqlv($row[2]).','.sqlv($row[3]).')';
						echo "<!-- $sql -->";
						if (sqlRun($sql))
							echo "<div style='color:green'>Field added $newfield</div>";
						else
							echo "<div style='color:red'>Failed to add Field $newfield</div>";
					}
				}
			}

			// change table name
			$renametable = strtolower(trim(Post('renametable_' . $tname)));
			if ($renametable != '' && $tname != '' && $renametable != $tname) {
				if ("'$renametable'" != sqlv($renametable) || "`$renametable`" != sqlf($renametable)) {
					echo "<div style='color:red'>illegal table name</div>";
				} else {
					$ok = sqlRun("UPDATE $tblinfo SET `tname`=".sqlv($renametable)." WHERE `tname`=$tnamev");
					if ($ok) {
						echo "<div style='color:green'>Table $tname renamed to $renametable ** reload page to see changes </div>";
						$tname = $renametable;
						$_GET['tname'] = $renametable;
					} else {
						echo "<div style='color:red'>Failed to rename $tname to $renametable</div>";
					}
				}
			}

			// change field names
			if (!empty($_POST['fields'])) {
				foreach ($_POST['fields'] as $k => $fname) {
					$newname = trim($_POST['newnames'][$k]);
					if ($newname!='' && $newname != $fname) {
						$ok = sqlRun("UPDATE $tblinfo SET `fname`=".sqlv($newname)." WHERE `tname`=$tnamev AND `fname`=".sqlv($fname)." LIMIT 1");
						if ($ok) {
							echo "<div style='color:green'>Rename $fname to $newname</div>";
						} else {
							echo "<div style='color:red'>Failed to Rename $fname to $newname</div>";
						}
					} else {
						// remove fields
						if ($_POST['delete'][$k] == "D") {
							$ok = sqlRun("DELETE FROM $tblinfo WHERE `tname`=$tnamev AND `fname`=".sqlv($fname));
							if ($ok) {
								sqlRun("DELETE FROM $tblmeta WHERE `tname`=$tnamev AND `fname`=".sqlv($fname));
								echo "<div style='color:green'>Deleted $fname</div>";
							} else {
								echo "<div style='color:red'>Failed to Delete $fname</div>";
							}
						}
					}
				}
			}

			// remove then create the table
			if (Post('resettable') == 'ResetTable' && $tname != '') {
				// Drop Tables
				$ok = sqlRun("DROP TABLE IF EXISTS `" . sqlf($tname) . "`;");
				if ($ok) {
					echo "<div style='color:green'>Table $tname dropped</div>";
				} else {
					echo "<div style='color:red'>Failed to drop $tname</div>";
				}

				// re-create the tables
				$ok = TftCreateTable($tname);
				if ($ok) {
					echo "<div style='color:green'>Table $tname creation completed</div>";
				} else {
					echo "<div style='color:red'>Failed to create $tname from $tblinfo</div>";
				}
			}

			// remove then create all tables from tf info table
			if (Post('resetdb') == 'ResetDB' && $tname == '') {
				// Drop Tables
				$res = sqlRun("SELECT `tname` FROM $tblinfo WHERE `fname`='' AND `tname`<>''");
				while ($row = mysql_fetch_row($res)) {
					$ok = sqlRun("DROP TABLE IF EXISTS `" . sqlf($row[0]) . "`;");
					if ($ok) {
						echo "<div style='color:green'>Table $row[0] dropped</div>";
					} else {
						echo "<div style='color:red'>Failed to drop $row[0]</div>";
					}
				}

				// re-create the tables
				$ok = TftCreateTables();
				if ($ok) {
					echo "<div style='color:green'>All Tables+Fields creation completed based on $tblinfo</div>";
				} else {
					echo "<div style='color:red'>Failed to create all Tables+Fields from $tblinfo</div>";
				}
			}

			// create all missing tables from tf info table
			if (Post('createdb') == 'Create') {
				TftCreateTables();
			}
		}
	}

	echo "
  <form accept-charset='iso-8859-1,utf-8' enctype='multipart/form-data' name=frm method=post action='" . reGet() . "'>
  <input type=hidden name=datasent value='yeah baby' />
  ";
	if ($tname != '') {
		echo "
  <table class=tftedit>
  <th class=none></th><th class=th align=center>" . _('rename') . "</th><th class=th align=center>" . _('Delete') . "</th>";
		$res = sqlRun("SELECT `fname` FROM $tblinfo WHERE `tname`=$tnamev AND `fname`<>'' ORDER BY `order` DESC,`fname`");
		$cnt = 0;
		while ($row = mysql_fetch_row($res)) {
			$tr = 'class=tr' . ((($cnt % 2) == 0) ? '1' : '2');
			$name = $row[0];
			echo "
    <tr $tr><th class=th style='text-align:left;'>$name</th>";
			echo "<input type=hidden name='fields[$cnt]' value='" . fix4html1($row[0]) . "'>";
			echo "<td class=td><input name='newnames[$cnt]'  type=text size=22 value='" . fix4html1($row[0]) . "' /></td>";
			echo "<td class=td><input name='delete[$cnt]'    type=text size=3  value='' /></td>";
			echo "</tr>";
			$cnt++;
		}
		echo "<tr><td></td><td></td><td>^ To remove field(s) type 'D'</td></tr>
        </table><br>";
		echo "
          Add new fields to [<b>$tname</b>]:<br>
          <input name='newfields[1]' type=text size=22 value='' />
          <input name='newfields[2]' type=text size=22 value='' />
          <input name='newfields[3]' type=text size=22 value='' />
          <input name='newfields[4]' type=text size=22 value='' />
          <input name='newfields[5]' type=text size=22 value='' />
          <input name='newfields[6]' type=text size=22 value='' />
          <input name='newfields[7]' type=text size=22 value='' />
          <br>
          ";
	}
	if ($tname == '') {
		echo "
        Add new table: <input name='newtable' type=text value='' size=36 />
        <br>To Create non-existing tables, type 'create' wc <input name='createdb' type=text> non-destructive <br>
        <br>To Drop all known tables and Re-Create them, type 'resetdb' wc <input name='resetdb' type=text> <font color=red>Destructive!</font><br>
        ";
	} else {
		echo "
        <br>Rename table: <input name='renametable_{$tname}' type=text> <font color=red>be carefull</font><br>
        <br>To Drop the table and Re-Create it, type 'resettable' wc <input name='resettable' type=text> <font color=red>Destructive!</font><br>
        <br>To remove table [$tname] and all its fields, type 'deltable' wc <input name='delthistable' type=text> <font color=red>Destructive!</font><br>
        ";
	}
	echo "
  <br>
  <input type=submit value='$Do' class=butgo>
  <br><br>
  </form name=frm>
  ";
}


include(__DIR__.'/inc/footer.php');
