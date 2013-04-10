<?php
global $tf;

////////////////////////////////////////
// the base TfType class.
class TfType {
	var $tname; // table database name
	var $fname; // field database name
	var $ename; // html form input element name, name that is sent to $_POST. MUST be: "$fname[$rowc]"
	var $eid;   // html form input element id
	var $table; // TfTable object
	var $value=null; // the field value
	var $isnum=true; // treat this field as use numeric in statistics

	var $fetch = array(); // array of all data from the tf info table (fetch_assoc)
	var $error = null; // validation error
	var $params = array(); // populated array of extra 'params' field from meta table tbl.meta tf_meta
	var $intags = array(); // populated values to go inside the main input tag, such as:
						   // class style dir pattern size rows cols heigth width onchange onclick onfocus onblur onmouseover onmouseout

	static $intag_replacek=array('$ename','$eid','$tname','$fname','$rowc','$curid','$form','$value','$pkey');
	static $intag_tocopy=array('rows','cols','width','height','dir','size','target','multiple','readonly','checked','selected','required');
	static $intag_boolean=array('multiple','readonly','checked','selected','required');

	function TfType() {
		$this->init();
	}
	function init() {
	}

	// Populate private vars from a tf_info row.
	// This should be called right after creating the object.
	function populate($fetch,&$table) {
		$this->table=$table;
		$this->fetch = $fetch;
		if (!array_key_exists('const', $fetch))
			$this->fetch['const'] = null;
		$this->tname = $fetch['tname'];   // table name
		$this->fname = $fetch['fname'];   // field name
		$this->value = $fetch['default']; // set the default value for this field
		/* no need...
		if (is_array($this->fetch['params'])) {
			&$this->params=$this->fetch['params'];
			unset($this->fetch['params']);
		}*/
	}

	// Return the sql decleration for this field type
	// * Note that in most cases you must overrun this method
	function sqlType() {
		return '';
	}

	// Return true if the $user has permission to do the $action.
	// $action can be 'view','edit','delete','new'.
	// Not using TftUserCan()
	// If $user is empty, using tfGetUserGroup()
	function userCan($user, $action) {
		global $tf;
		if (empty($user))
			$user = tfGetUserGroup();
		$action = strtolower($action);
		if (array_key_exists($action, $tf['permissionMap']))
			$action = $tf['permissionMap'][$action];
		$user = strtolower($user);
		if (!array_key_exists('users' . $action, $this->fetch))
			return null;
		return strpos(',' . strtolower($this->fetch['users' . $action]) . ',', ',' . $user . ',') !== false;
	}

	// Set $this->error and return false
	function valerror($error = null) {
		if ($error !== null)
			$this->error = $error; // $this->error = _($error);
		return false;
	}

	// Set $this->error and return false
	function searcherror($error = null) {
		global $tf;
		// TODO: use translation instead of this
		if (($error !== null) && array_key_exists($error, $tf['errors']))
			$error = $tf['errors'][$error];
		if ($error !== null)
			$this->error = $error;
		return false;
	}

	// Return false if current value is considered an empty or unset value; otherwise return true. By default, empty=''/null/0
	function notempty($value=0) {
		if (func_num_args()==0) $value = $this->value;
		return $value !== '' && $value !== null && $value !== 0;
	}

	// Return true for equal values, false for different values.
	// $value2 is optional. If omitted, current field value will be used.
	function equal($value1, $value2=0) {
		if (func_num_args()==1) $value2 = $this->value;
		return $value1 == $value2;
	}

	// Compare two values for which one is bigger.
	// $value2 is optional. If omitted, current field value will be used.
	// Return 0 for equal ; 1 for $value1 > $value2 (or current value) ; -1 for $value1 < $value2 (or current value)
	// Return null for undefined
	function diff($value1, $value2=0) {
		if (func_num_args()==1) $value2 = $this->value;
		if ($value1==$value2) return 0;
		if (is_numeric($value2))
			if ((1*$value1) > (1*$value2)) return 1;
			elseif ((1*$value1) < (1*$value2)) return -1;
		if ($value1 > $value2) return 1;
		if ($value1 < $value2) return -1;
		return null;
	}

	// Validate a potential value for this field.
	// Return true/false. When false, validation error info is at $this->error
	function validate($value) {
		if ($value===null && empty($this->fetch['oknull']))
			return $this->valerror(_('Null not allowed'));
		if ($value==='' && empty($this->fetch['okempty']))
			return $this->valerror(_('Cannot be empty'));
		$this->error = '';
		return true;
	}

	// handle statistics
	// anything stars with _ will not be displayed
	function to_statistics(&$array) {
		$v=$this->view();
		if (empty($array['Count'])) {
			$array['Count']=1;
			if ($this->isnum) { // is_numeric($v)) {
				$array['Sum']=0;
				$array['Total Positives']=0;
				$array['Total Negatives']=0;
				$array['Sum Positives']=0;
				$array['Sum Negatives']=0;
				$array['Total Empty']=0;
			}
		} else
			$array['Count']+=1;

		if (empty($v) || $v==='0000-00-00' || $v==='0000-00-00 00:00:00')
			@$array['Total Empty']+=1;

		if ($this->isnum) { // is_numeric($v)) {
			if ($array['Count']==1)
				$array['_all']=array($v);
			else
				array_push($array['_all'],$v);

			$array['Sum']+=$v;
			if ($v>0) {
				$array['Total Positives']+=1;
				$array['Sum Positives']+=$v;
			} elseif ($v<0) {
				$array['Total Negatives']+=1;
				$array['Sum Negatives']+=$v;
			}
		}
		return;
		// For translation...
		if (false) {
			//_('Count');
			_('Min');
			_('Max');
			_('Total Positives');
			_('Total Negatives');
			_('Sum');
			_('Sum Positives');
			_('Sum Negatives');
			_('Average');
			_('Mean Positives');
			_('Mean Negatives');
			_('Total Empty');
			_('Median');
		}
	}

	// finish up statistics at the end
	function to_statistics_end(&$array) {
		if (array_key_exists('Total Positives',$array) && ($array['Total Positives']===0 || $array['Total Positives']===$array['Count'])) {
			unset($array['Total Positives']);
			unset($array['Sum Positives']);
		}
		if (array_key_exists('Total Negatives',$array) && ($array['Total Negatives']===0 || $array['Total Negatives']===$array['Count'])) {
			unset($array['Total Negatives']);
			unset($array['Sum Negatives']);
		}
		if (array_key_exists('Total Empty',$array) && $array['Total Empty']===0) unset($array['Total Empty']);

		if ($this->isnum && array_key_exists('Count',$array) && $array['Count']>3) {
			sort($array['_all'],SORT_NUMERIC);
			$array['Min']=$array['_all'][0];
			$array['Max']=$array['_all'][$array['Count']-1];
			if ($array['Min']==$array['Max']) {
				unset($array['Min']);
				unset($array['Max']);
			} else {
				$array['Median']=$array['_all'][floor($array['Count'] / 2)];
				$array['Average']=$array['Sum']/$array['Count'];
				if (array_key_exists('Total Positives',$array))
					$array['Mean Positives']=$array['Sum Positives']/$array['Total Positives'];
				if (array_key_exists('Total Negatives',$array))
					$array['Mean Negatives']=$array['Sum Negatives']/$array['Total Negatives'];
			}
		}

		unset($array['_all']);
		unset($array['Count']);
	}

	// Additional expressions added after SELECT *
	function to_select_select() {
		return '';
	}

	// Additional expressions added after FROM `$tname`
	function to_select_from() {
		return '';
	}

	function search_methods() {
		return array(
			'has'=>'has',
			'a' =>'begins with',
			'z' =>'ends with',
			'ci'=>'is',
			'rx'=>'RegEx',
			'eq'=>'≡',
			'lt'=>'<',
			'gt'=>'>',
			'lte'=>'≤',
			'gte'=>'≥',
			'n'=>'is null',
			'b'=>'is true',
			'e'=>'is empty',
			'in'=>'in');
	}

	// Return sql expression to compare to something
	// $not - is negative search?
	// methods: see https://code.google.com/p/tablefield/wiki/Search_and_Sort_Options
	// Calculated values or Foreign keys can hack it
	function to_select_where($method,$query,$not=false) {
		if (empty($this->tname))
			$where=($not?'NOT ':'').' '.sqlf($this->fname);
		else
			$where=($not?'NOT ':'').' '.sqlf($this->tname).'.'.sqlf($this->fname);

			if ($method=='has') $where.=" LIKE '".'%'.mysql_real_escape_string(str_replace(array("\\", '_', '%'), array("\\\\", "\\_", "\\%"), $query)).'%'."'";
		elseif ($method=='a' )  $where.=" LIKE '".mysql_real_escape_string(str_replace(array("\\", '_', '%'), array("\\\\", "\\_", "\\%"), $query)).'%'."'";
		elseif ($method=='z' )  $where.=" LIKE '".'%'.mysql_real_escape_string(str_replace(array("\\", '_', '%'), array("\\\\", "\\_", "\\%"), $query))."'";
		elseif ($method=='ci')  $where.=" LIKE '".mysql_real_escape_string(str_replace(array("\\", '_', '%'), array("\\\\", "\\_", "\\%"), $query))."'";
		elseif ($method=='rx')  $where.=" RLIKE '".mysql_real_escape_string($query)."'";
		elseif ($method=='eq')  $where.='='      .(is_long($query)?$query:"'".mysql_real_escape_string($query)."'");
		elseif ($method=='lt')  $where.='<'      .(is_long($query)?$query:"'".mysql_real_escape_string($query)."'");
		elseif ($method=='gt')  $where.='>'      .(is_long($query)?$query:"'".mysql_real_escape_string($query)."'");
		elseif ($method=='lte') $where.='<='     .(is_long($query)?$query:"'".mysql_real_escape_string($query)."'");
		elseif ($method=='gte') $where.='>='     .(is_long($query)?$query:"'".mysql_real_escape_string($query)."'");
		elseif ($method=='n')   $where.=' IS NULL';
		elseif ($method=='b')   $where.=''; // boolean - leave as is
		elseif ($method=='e')   $where.="='' "; // empty
		elseif ($method=='in')  $where=($not?'NOT ':'')." CONCAT('%',".sqlf($this->fname).",'%') LIKE '".mysql_real_escape_string(str_replace(array("\\", '_', '%'), array("\\\\", "\\_", "\\%"), $query))."'";
		else return false; // unknown method
		return $where;
	}

	// SQL expression for order by clause (without the 'ORDER BY' itself)
	// $direction=DESC/ASC or empty
	// To cancel option of sorting by the field return null
	// Usually it's just escaped `fname`
	// Calculated values or Foreign keys can use it to sort by the visible value and not by key
	function to_select_orderby($direction) {
		if (empty($this->tname))
			return sqlf($this->fname) . ' ' . $direction;
		else
			return sqlf($this->tname).'.'.sqlf($this->fname) . ' ' . $direction;
	}

	// Fix the value before update
	function fix($value) {
		return $value;
	}

	// Set a value - using the fix($value) method
	function set($value) {
		$value = $this->fix($value);
		if ($this->validate($value))
			$this->value = $value;
		return $this->value;
	}

	// Prepare the field for deletion from table. Usually applicaple only to file fields.
	function del() {
		return true;
	}

	// Get or Set a value from the extra params field.
	// To remove a value set it to NULL
	// When value is missing NULL is returned
	function param($param,$set=0) {
		if (func_num_args()==2)
			if ($set === null) unset($this->params[$param]);
			else $this->params[$param] = $set;
		else
			if (array_key_exists($param, $this->params))
				return $this->params[$param];
			else
				return null;
	}

	// Return a boolean from a param. Default = false or given value.
	// Using oria.inc.php:chkBool() for values except 0/1/true/false
	function paramtrue($param, $default = false) {
		if (array_key_exists($param,$this->params))
			if ($this->params[$param]===0 || $this->params[$param]==='0' || $this->params[$param]===false)
				return false;
			elseif ($this->params[$param]===1 || $this->params[$param]==='1' || $this->params[$param]===true)
				return true;
			else
				return chkBool($this->params[$param], $default);
		else
			return $default;
	}

	// Display value to view current field (not html) (xkey will be translated)
	function view() {
		return $this->value;
	}

	// Return html string for displaying this field.
	function htmlView($override=array()) {
		return '<span '.$this->intag($override).'>'.$this->view().'</span>';
	}

	// Populate $this->intag var that will be added to the main input tag
	function populate_intag($override=array()) {

		/////// get stuff from params /////////
		foreach (TfType::$intag_tocopy as $k)
			if (array_key_exists($k,$this->params))
				$override[$k]=$this->params[$k];

		////// populate pattern /////
		if (!array_key_exists('pattern',$override) && (!$this->fetch['okempty'])) $override['pattern']='.+';

		//////////// populate onchange //////////////
		if (array_key_exists('onchange',$this->params)) $override['onchange']=$this->params['onchange'].';'.(@$override['onchange']);

		//////////// populate style ////////////////
		if (array_key_exists('style',$this->params)) $override['style']=$this->params['style'].';'.(@$override['style']);

		/////////// populate name,id //////////////
		if (!array_key_exists('name',$override)) $override['name']='$ename';
		if (!array_key_exists('id',$override)) $override['id']='$eid';

		//////// populate class ////////
		$class=array(get_called_class());
		// covert to arrays for easy processing
		if (!array_key_exists('class',$this->params)) $this->params['class']=array();
		elseif (!is_array($this->params['class'])) $this->params['class']=explode(' ',$this->params['class']);
		if (!array_key_exists('class',$override)) $override['class']=array();
		elseif (!is_array($override['class'])) $override['class']=explode(' ',$override['class']);
		$override=array_merge($this->params['class'],$override);
		// add or remove class names
		foreach($override['class'] as $k=>$v)
			if ($v===false)  // remove class (from assoc array)
				$class=explode(' ',str_replace(" $k ",' ',' '.implode(' ',$class).' '));
			elseif ($v===true) // add class (from assoc array)
				$class[]=$k;
			elseif ($v===null) // do nothing
				;
			else $class[]=$v;  // add class (from regular array)

		// fin
		$this->intags=$override;
	}

	// Called per each and every element... Efficiency is mandatory
	// make sure eid, ename, rowc, curid exists - before calling this or any html* method
	// When value is 0 it adds value0 class to element. To disable this use $override['no value0']=true
	function intag($override=array()) {

		// handle class
		if (!empty($override['class'])) {
			if (!is_array($override['class'])) $override['class']=explode(' ',$override['class']);
			$override['class']=array_merge($this->intags['class'],$override['class']);
		}

		// merge pre-populated and current
		if (empty($override)) $override=$this->intags;
		else $override=array_merge($this->intags,$override);

		// handle value0 class - when value is 0 add value0 class to element. To disable this use $override['no value0']=true
		if (($this->value===0 || $this->value==='0000-00-00') && empty($override['no value0'])) {
			if (!array_key_exists('class',$override)) $override['class']=array();
			$override['class'][]='value0';
			$override['onchange']='$(this).removeClass(\'value0\');'.(@$override['onchange']);
		}
		unset($override['no value0']);

		// add then remove class names
		$class='';
		if (isset($override['class']))
			foreach($override['class'] as $k=>$v)
				if (is_string($v))
					$class.=" $v ";
		if (isset($override['class']))
			foreach($override['class'] as $k=>$v)
				if ($v===false)
					$class=str_replace(" $k ",' ',$class);
		$override['class']=$class;

		// fix populated booleans
		foreach (TfType::$intag_boolean as $k)
			if (array_key_exists($k,$override))
				if ($override[$k]) $override[$k]=$k;
				else unset($override[$k]);

		//static $intag_replacek=array('$ename','$eid','$tname','$fname','$rowc','$curid','$form','$value','$pkey');
		$replacev=array($this->ename,$this->eid,$this->tname,$this->fname,@$this->table->rowc,@$this->table->curid,@$this->table->htmlform,$this->value,@$this->table->pkey);

		$out='';
		foreach ($override as $k=>$v)
			if ($v)
				if (strpos($v,'$')===false)
					//$out.=$k.'="'.fix4html2($v).'"';
					$out.=$k."=\"$v\"";
				else
					$out.=$k.'="'.(str_replace(TfType::$intag_replacek,$replacev,$v)).'" ';
		return $out;
	}

	// Return html string for inputing this field
	function htmlInput($override=array()) {
		return '<input '.$this->intag($override).' value="'.fix4html2($this->value) . '" />';
	}

	// Return html string for inputing this field as new
	function htmlNew($override=array()) {
		if ($this->value === null)
			$this->set($this->fetch['default']);
		return $this->htmlInput($override);
	}

	function htmlQuietInput($override=array()) {   // return a hidden input
		$override['type']='hidden';
		$override['style']='';
		return '<input '.$this->intag($override).' value="'.fix4html2($this->value) . '" />';
	}

	function htmlQuietNew($override=array()) {   // return a hidden field input for new record
		if ($this->value === null)
			$this->set($this->fetch['default']);
		return $this->htmlQuietInput($override);
	}

	// add some html code just before form close tag
	function htmlFormEnd() {
		return '';
	}
	// add some html code just below form start tag
	function htmlFormStart() {
		return '';
	}
}

//class TfType
////////////////////////////////////////
// fictive - base class for virtual and calculated fields --
// values that does not actually exist on the db
class TfTypefictive extends TfType {

	function sqlType() {
		return '';
	}

	function view() {
		// This is the most important function of a fictive type,
		// because a fictive type doesn't have a value.
		// For example:
		// $res=sqlRun("SELECT COUNT(*) FROM foo WHERE id=".sqlv($this->table->curid));
		// $row=mysql_fetch_row($res);
		// if ($row) return $row[0];
		// else return 'N/A';
		return '';
	}

	function orderby($direction) {
		// This is very important too because there is no db field for this one.
		// You need to set an mysql orderby clause that will sort this.
		// For example:
		// return "(SELECT COUNT(*) FROM pita WHERE pita.id=bla.id) $direction";
		// return "CONCAT(pita,humus) $direction";
		return '';
	}

	// Return sql WHERE search query (inside brackets) for the appropriate given value
	// If value is not set, current value will be used.
	function to_select_where($method,$query,$not=false) {
		return '';
	}

	function to_select_orderby($direction) {
		return '';
	}

	function validate($value) {
		return true;
	}

	function htmlView($override=array()) {
		return '<span '.$this->intag($override).'>'.$this->view().'</span>';
	}

	function htmlInput($override=array()) {
		return $this->htmlView($override);
	}

	function htmlQuietInput($override=array()) {
		return '';
	}

	function htmlQuietNew($override=array()) {
		return '';
	}

	function htmlNew($override=array()) {
		return '';
	}

} //class TfTypefictive


////////////////////////////////////////
// enum
// select values from a list
// meta params:
//   values - comma separated available values
//   label-X - optional visible label for the value X
class TfTypeenum extends TfType {
	var $values=array(); // available values

	function sqlType() {
		$vals=$this->values;
		for ($i=0;$i<count($vals);$i++) $vals[$i]=sqlv($vals[$i]);
	 	return 'ENUM('.implode(',',$vals).')';
	}

	function populate_values()
	{
		if (empty($this->params['values'])) {
			addToLog('<t>values</t> '._('Value is missing at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
		} else {
			$this->values=explode(',',$this->params['values']);
			if (count($this->values)==0)
				addToLog('<t>values</t> '._('Value is missing at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
			else
				foreach ($this->values as $k=>$v)
					$this->values[$k]=urldecode($v);
		}
	}

	function populate($fetch,&$table) {
		$this->isnum=false;
		parent::populate($fetch, $table);
		$this->populate_values();
	}

	function validate($value) {
		$ok=parent::validate($value);
		if ($ok && !in_array($value,$this->values)) return $this->valerror(_('Value not in list'));
		return $ok;
	}

	function view() {
		if (empty($this->params['label-'.$this->value]))
			return $this->value;
		else
			return $this->params['label-'.$this->value];
	}

	function htmlInput($override=array()) {
		$inp='<SELECT '.$this->intag($override).'>';
		for ($i=0;$i<count($this->values);$i++)
			if (empty($this->params['label-'.$this->values[$i]]))
				$inp.='<OPTION'.($this->value==$this->values[$i]?' selected':'').'>'.fix4html3($this->values[$i]);
			else
				$inp.='<OPTION'.($this->value==$this->values[$i]?' selected':'').' value="'.fix4html2($this->values[$i]).'">'.fix4html3($this->params['label-'.$this->values[$i]]);
		$inp.='</SELECT>';
		return $inp;
	}

	function to_statistics(&$array) {
		$v=$this->view();
		@$array['Count']+=1;
		if ($this->value==='' || $this->value===null)
			@$array['Total Empty']+=1;
		else
			@$array[_('Total ').$v]+=1;
	}
} // class TfTypeenum

////////////////////////////////////////
// enums
// select set of values from a list
// meta params:
//   values - comma separated available values
class TfTypeenums extends TfTypeenum {

	function sqlType() {
		$vals=$this->values;
		for ($i=0;$i<count($vals);$i++) $vals[$i]=sqlv($vals[$i]);
	 	return 'SET('.implode(',',$vals).')';
	}

	function validate($value) {
		$value=explode(',',$value);
		$cnt=count($value);
		for ($i=0;$i<$cnt;$i++) {
			$value[$i]=urldecode($value[$i]);
			if (!in_array($value[$i],$this->values))
				return $this->valerror(_('Value not in list'));
		}
		if ($this->fetch['okmin']) {
			if ($cnt<$this->fetch['okmin'])
				return $this->valerror('Select at least '.$this->fetch['okmin'].' items');
		}
		if ($this->fetch['okmax']) {
			if ($cnt>$this->fetch['okmax'])
				return $this->valerror('Select at most '.$this->fetch['okmax'].' items');
		}
		if ($cnt==0 && $this->fetch['okempty'])
			return $this->valerror(_('Select at least one item'));
		return true;
	}

	function view() {
		$vals=explode(',',$this->value);
		$inp='';
		foreach($vals as $v)
			if (empty($this->params["label-$v"]))
				$inp.=$this->value.',';
			else
				$inp.=$this->params["label-$v"].',';
		return substr($inp,0,strlen($inp)-1);
	}

	function htmlInput($override=array()) {
		return parent::htmlInput(array_merge($override,array('multiple'=>true)));
	}

	function to_statistics(&$array) {
		@$array['Count']+=1;
		if ($this->value==='' || $this->value===null)
			@$array['Total Empty']+=1;
		else {
			$vals=explode(',',$this->value);
			foreach($vals as $v)
				if (empty($this->params["label-$v"]))
					@$array[_('Total ').$v]+=1;
				else
					@$array[_('Total ').$this->params["label-$v"]]+=1;
		}
	}
} // class TfTypeenums


////////////////////////////////////////
// Primary Key type
// not editable, not null, auto_incremented
class TfTypepkey extends TfTypenumber {

	function sqlType() {
		return "BIGINT UNSIGNED NOT NULL AUTO_INCREMENT";
	}

	function populate($fetch,&$table) {
		$this->isnum=false;
		parent::populate($fetch, $table);
	}

	function validate($value) {
		if ($value)
			return $this->valerror(_('Primary key is not editable'));
		return true;
	}

	function htmlInput($override=array()) {
		return $this->htmlView($override);   // update a pkey is not allowed
	}

	function htmlQuietInput($override=array()) {
		return '';  // update a pkey is not allowed
	}

}

//class TfTypepkey
////////////////////////////////////////
// Regular string type
// A general text field (up to [okmax] chars, 255 by default)
// meta params:
// for search:
//   search-cs = yes/no. case sensitive? default no.
//   search-wildcards = yes/no. support the * wildcard (translated to % in sql) default no.
//   search-entirevalue = yes/no. match only entire value? no=also match part of string. default no.
class TfTypestring extends TfType {

	function sqlType() {
		if (!empty($this->fetch['okmax']))
			if (!empty($this->fetch['okmin']) && $this->fetch['okmin'] == $this->fetch['okmax'] && $this->fetch['okmin']<256)
				return 'CHAR(' . $this->fetch['okmin'] . ')';
			else
				return "VARCHAR(" . $this->fetch['okmax'] . ")";
		else
			return "VARCHAR(255)";
	}

	function populate($fetch,&$table) {
		$this->isnum=false;
		parent::populate($fetch, $table);
	}

	function search_methods() {
		return array(
			'has'=>'has',
			'a' =>'begins with',
			'z' =>'ends with',
			'ci'=>'is (ci)',
			'rx'=>'RegEx',
			'eq'=>'is (cs)',
			'n'=>'is null',
			'e'=>'is empty',
			'in'=>'in');
	}

	function validate($value) {
		if ((@$this->fetch['okmax'] > 0) && ($this->fetch['okmax'] < strlen($value)))
			return $this->valerror(_('Text too long'));
		if ((@$this->fetch['okmin'] > 0) && ($this->fetch['okmin'] > strlen($value)))
			return $this->valerror(_('Text too short'));
		return parent::validate($value);
	}

}

//class TfTypestring
////////////////////////////////////////
// MD5 Hash Type
// A text field which is going to be hashed on update.
// A search is not really valid on md5, but basically it is a regular %LIKE% search
class TfTypemd5 extends TfTypestring {

	function sqlType() {
		return "CHAR(32) CHARSET ascii";
	}

	// return true for a value that is already an md5 hash, false otherwise.
	function ishashed($value=0) {
		if (func_num_args()==0) $value = $this->value;
		if (strlen($value) != 32 || preg_replace("/[0-9a-f]/i", '', $value) != '') {
			return false;
		} else {
			return true;
		}
	}

	function validate($value) {
		if ($this->ishashed($value))
			return true;
		if ((@$this->fetch['okmax'] > 0) && ($this->fetch['okmax'] < strlen($value)))
			return $this->valerror(_('Text too long'));
		if ((@$this->fetch['okmin'] > 0) && ($this->fetch['okmin'] > strlen($value)))
			return $this->valerror(_('Text too short'));
		return parent::validate($value);
	}

	function fix($value) {
		if ($this->ishashed($value)) {
			return $value;
		} else {
			return md5($value);
		}
	}

	// Search is not possible on MD5 fields
	function to_select_where($method,$query,$not=false) {
		return '';
	}

}

//class TfTypemd5
////////////////////////////////////////
// Email address
// Todo: anti email-fishing-robots mechanism
// meta params:
//       nolink   - When set to '1' or 'true' does not display a link, only the email itself on htmlView()
//       linktext - The html text inside the <a> tag that leads to the hyperlink.
//                  Use "$value" to use the email address in this field.
//                  When missing, the actual email address will be used.
//       validate-domain - When set to '1' or 'true' the validation function also checks that the email domain name actually exists.
//                         This makes the validation process MUCH slower! So do not use if not necessary.
//       no-example - Validate that the email is not in the domain "@example.com/org/..."
//
class TfTypeemail extends TfTypestring {

	var $validchars = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~.%+-_$=';
	var $validcharsRE = '0-9a-zA-Z~\\.\\%\\+\\-\\_\\$=';

	function populate($fetch,&$table) {
		$this->intags['pattern']='\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b';
		parent::populate($fetch, $table);
	}

	function fix($value) {
		$value = preg_replace("/[^{$this->validcharsRE}@<>]/", '', $value); // remove any unrecognized character
		// fix the "Oria <oria@example.com>" format by taking only the text inside <>
		if (strpos($value, '<') !== false) {
			while (strpos($value, '<') !== false) { // if there is more than one <<, take only the last one (like "oria<br>adam<<oria@example.com>"
				$value = preg_replace('/^[^<]*</', '', $value); // remove anything before the last <
			}
			$value = preg_replace('/>[^>]*$/', '', $value); // remove anything after the last >
			$value = preg_replace('/[<>]/', '', $value); // remove any <> which may left behind
		}
		return $value;
	}

	function validateDomain($value = null) {
		if ($value === null)
			$value = $this->value;
		if ($value == '')
			return $this->valerror(_('Email is empty'));
		$a = explode('@', $value);
		$domain = $a[1];
		if (gethostbyname($domain) == $domain)
			return $this->valerror(_('Email address domain is not reachable'));
		return true;
	}

	function validate($value) {
		$value = $this->fix($value);
		if ($value == '')
			return parent::validate($value);
		$r = $this->validcharsRE;
		if (!preg_match("/[$r]+\\@[$r]+\\.[$r][$r]+/", $value))
			return $this->valerror(_('Invalid email address'));
		// Dont always validate domain exists because it takes some time when domain is invalid (0.5-3 seconds)
		// and it always fail when there's no internet connection
		if ($this->paramtrue('validatedomain') || $this->paramtrue('validate-domain')) {
			if (!$this->validateDomain($value)) {
				return $this->valerror(_('Email address domain is not reachable'));
			}
		}
		if ($this->paramtrue('noexample') || $this->paramtrue('no-example')) {
			if (preg_match('/@example\.[a-zA-Z][a-zA-Z][a-zA-Z]?\.?[a-zA-Z]?[a-zA-Z]?/', $value))
				return $this->valerror(_('Email address domain is not reachable'));
		}
		return true;
	}

	function htmlView($override=array()) {
		if ($this->paramtrue('nolink') || $this->paramtrue('no-link'))
			return parent::htmlView($override);

		// else: display as a link to email the guy
		$inp = $this->view();
		$inp = '<a '.$this->intag($override).' href="mailto:'.fix4html2($this->value).'">'.$inp.'</a>';
		return $inp;
	}

}

////////////////////////////////////////
// hyper Link field
// meta params:
//       no-link   - when true dont display an <a> link on htmlView(), but the link as text
//       allowed   - CSV of allowed protocols. Default: http,https. Other protocols for example: mailto,javascript
//                   Note that if the link is missing a protocol, 'http://' will be added automatically on fix().
//       linktext - The html text inside the <a> tag that leads to the hyperlink.
//                  Use "$value" to use the email address in this field.
//                  When missing, the actual email address will be used.
//       validate-domain - When set to '1' or 'true' the validation function also checks that the email domain name actually exists.
//                         This makes the validation process MUCH slower! So do not use if not necessary.
//       target   - target that will be added to the link
class TfTypelink extends TfTypestring {

	function populate($fetch,&$table) {
		parent::populate($fetch,$table);
		if (!isset($this->params['target']))
			$this->params['target']='_blank';
	}

	/* disallow relative links?
	function fix($value) {
		if (!empty($value)) {
			if (strpos($value, ':') === FALSE) {
				return "http://$value";
			}
		}
		return $value;
	}*/

	function htmlView($override=array()) {
		if ($this->paramtrue('no-link'))
			return $this->view();
		else
			return '<a href="'.fix4html2($this->value).'" '.$this->intag($override).'>'.$this->view().'</a>';
	}

	function htmlInput($override=array()) {
		$override['onchange']="document.getElementById('$this->eid link').href=this.value;";
		return parent::htmlInput($override)."<a class='icon-share tfLink' id='$this->eid link' href=\"$this->value\" target='".$this->param('target')."'></a>";
	}

}

// class TfTypelink
////////////////////////////////////////
// general Number
// the sqlType is based on the okmax and okmin fields.
// search query supports the following forms:
//    5   =5
//    >5   more than 5   greater than 5   gt 5   larger than 5
//    <5   less than 5   little than 5    lt 5   smaller than 5
//    >=5   =>5   <=5   =<5
//    between 4 and 6     >4<6
//    not between 4 and 6
//    other than 5
//    !5 ~5
//    has 51 -> look for number with 51, as a string
//    %5     mod 5    modulu 5   div 5    div by 5    diveded by 5    divide by 5   -> any number divedes by 5
class TfTypenumber extends TfType {

	function populate($fetch,&$table) {
		parent::populate($fetch,$table);
		if (!isset($this->params['size'])) {
			$size = max(3, strlen($this->fetch['okmax']), strlen($this->fetch['okmin']), strlen($this->value));
			$this->params['size']=$size;
		}
	}

	function sqlType() {
		if (!isset($this->fetch['okmax']))
			$this->fetch['okmax'] = null;
		if (!isset($this->fetch['okmin']))
			$this->fetch['okmin'] = null;
		if ($this->fetch['okmax'] === null && $this->fetch['okmin'] === null)
			return "INT";
		if ($this->fetch['okmin'] > 0 || $this->fetch['okmin'] === 0) { // unsigned number. ===0 to avoid null.
			if ($this->fetch['okmax'] === null) {
				if ($this->fetch['okmin'] > 4294967295)
					return "UNSIGNED BIGINT";    // 8bytes 0-16 mega tera
				if ($this->fetch['okmin'] > 16777215)
					return "UNSIGNED INT";	  // 4bytes 0-4 giga
				return "UNSIGNED MEDIUMINT"; // default for positive minimum - upto 65535
			}
			if ($this->fetch['okmax'] > 4294967295)
				return "UNSIGNED BIGINT";    // 8bytes 0-16 mega tera
			if ($this->fetch['okmax'] > 16777215)
				return "UNSIGNED INT";	  // 4bytes 0-4 giga
			if ($this->fetch['okmax'] > 65535)
				return "UNSIGNED MEDIUMINT"; // 3bytes 0-16 mega
			if ($this->fetch['okmax'] > 255)
				return "UNSIGNED SMALLINT";  // 2bytes 0-65535
			if ($this->fetch['okmax'] > 0)
				return "UNSIGNED TINYINT";   // 1byte  0-255
			return "UNSIGNED INT";   // default
		} else {
			if ($this->fetch['okmax'] > 4294967295 || $this->fetch['okmin'] < -4294967296)
				return "BIGINT";    // 8bytes ~� 8 mega tera
			if ($this->fetch['okmax'] > 8388607 || $this->fetch['okmin'] < -8388608)
				return "INT";	  // 4bytes ~� 2 giga
			if ($this->fetch['okmax'] > 32767 || $this->fetch['okmin'] < -32768)
				return "MEDIUMINT"; // 3bytes ~� 8 mega
			if ($this->fetch['okmax'] > 127 || $this->fetch['okmin'] < -128)
				return "SMALLINT";  // 2bytes ~� 32767
			if ($this->fetch['okmax'] > 0 || $this->fetch['okmin'] < 0)
				return "TINYINT";   // 1byte  ~� 127
			return "INT"; // default
		}
	}

	function fix($value) {
		// remove addable characters which are not numbers
		$value = trim($value);
		if ($value == '')
			return $value;
		// remove unnecessary +
		if ($value{0} == '+')
			$value = substr($value, 1);
		// fix '.046' to '0.046'
		if ($value{0} == '.')
			$value = '0' . $value;
		// fix '-.046' to '-0.46'
		if (strlen($value) > 1 && $value{0} == '-' && $value{1} == '.')
			$value = '-0' . substr($value, 1);
		// fix 100,000 and remove spaces
		$value = str_replace(array(',', ' ', "\r", "\n", "\t"), '', $value);
		if (is_numeric($value))
			$value = 1*$value;
		return $value;
	}

	function validate($value) {
		$value = $this->fix($value);
		if ($value === '') {
			if ($this->fetch['okempty'] || $this->fetch['oknull']) {
				return true;
			} else {
				return $this->valerror(_('Cannot be left empty'));
			}
		}
		if (!is_numeric($value))
			return $this->valerror(_('Not a number'));
		if (strpos($value, '.') !== false)
			return $this->valerror(_('Not supporting decimal point'));
		if (($this->fetch['okmax'] !== null && $this->fetch['okmax'] !== '') && ($this->fetch['okmax'] < $value))
			return $this->valerror(_('Number too big'));
		if (($this->fetch['okmin'] !== null && $this->fetch['okmin'] !== '') && ($this->fetch['okmin'] > $value))
			return $this->valerror(_('Number too small'));
		return parent::validate($value);
	}

	function htmlInput($override=array()) {
		$override['type']='number';
		return parent::htmlInput($override);
	}

	function search_methods() {
		return array(
			'eq'=>'≡',
			'lt'=>'<',
			'gt'=>'>',
			'lte'=>'≤',
			'gte'=>'≥',
			'n'=>'never entered',
			'in'=>'in');
	}

	function to_select_where($method,$query,$not=false) {
		if ($method!='in') return parent::to_select_where($method,$query,$not);
		// method == in
		if (empty($this->tname))
			$f=sqlf($this->fname);
		else
			$f=sqlf($this->tname).'.'.sqlf($this->fname);
		if (strpos($query,' ')!==false) $query=str_replace(' ',',',$query);
		$query=explode(',',$query);
		foreach($query as $k=>$v)
			if (!is_numeric($v) || $v==='') unset($query[$k]);
		if (count($query)==0) return false;
		$query=implode(',',$query);
		return ($not?'NOT ':'')." $f IN ($query)";
	}

}

// class TfTypenumber
////////////////////////////////////////
// floating point number
class TfTypefloat extends TfTypenumber {

	function populate($fetch,&$table) {
		parent::populate($fetch, $table);
		if ($this->fetch['default'] == '' || $this->fetch['default'] == '0')
			$this->fetch['default'] = '0.0';
	}

	function sqlType() {
		return "FLOAT";
	}

	function fix($value) {
		$value = $this->fix_from_number($value);
		$value = floatval($value);
		if (strpos($value, ".") === false)
			$value.='.0';
		return $value;
	}

	function fix_from_number($value) {
		// fix 100,000 and remove spaces
		$value = str_replace(array(',', ' ', "\r", "\n", "\t"), '', $value);
		// trim spaces
		$value = trim($value);
		// if empty - stop now
		if ($value == '')
			return $value;
		// remove unnecessary +
		if ($value{0} == '+')
			$value = substr($value, 1);
		if ($value == '')
			$value = '0';
		// fix '.046' to '0.046'
		if ($value{0} == '.')
			$value = '0' . $value;
		// fix '-.046' to '-0.46'
		if (strlen($value) > 1 && $value{0} == '-' && $value{1} == '.')
			$value = '-0' . substr($value, 1);
		return $value;
	}

	function validate($value) {
		$value = $this->fix($value);
		if ($value == '' && (!$this->fetch['okempty']) && (!$this->fetch['oknull']))
			return $this->valerror(_('Cannot be left empty'));
		if ($value != '' && !is_numeric($value))
			return $this->valerror(_('Not a number'));
		if ($value != '' && ($this->fetch['okmax'] !== null && $this->fetch['okmax'] !== '') && (floatval($this->fetch['okmax']) < $value))
			return $this->valerror(_('Number too big'));
		if ($value != '' && ($this->fetch['okmin'] !== null && $this->fetch['okmin'] !== '') && (floatval($this->fetch['okmin']) > $value))
			return $this->valerror(_('Number too small'));
		return parent::validate($value);
	}

	function htmlInput($override=array()) {
		if (!isset($this->params['size'])) {
			$size = max(4, strlen($this->fetch['okmax']), strlen($this->fetch['okmin']), strlen($this->value));
			$this->params['size']=$size;
		}
		return parent::htmlInput($override);
	}

	function htmlView($override=array()) {
		if (strpos($this->value, ".") === false)
			$this->value.='.0';
		return parent::htmlView($override);
	}

}

// class TfTypefloat
////////////////////////////////////////
// Password field
// meta params:
//    echo - Default false. When set to False/0 doesn't return saved password back to html input, for security reasons.
class TfTypepassword extends TfTypestring {

	function htmlInput($override=array()) {
		if ($this->paramtrue('echo',true)) {
			$pass = $this->value;
		} else {
			$pass = '';
		}
		return '<label><span class="add-on icon-key"></span><input '.$this->intag($override).' value="'.fix4html2($pass).'" type="password" /></label>';
	}
}

// class TfTypepassword
////////////////////////////////////////
// Password +Validation
// Requires validation of the password by entering it twice.
// meta params:
//     echo - Default false. When set to False/0 doesn't return saved password back to html input, for security reasons.
//     enterpass1 - label of first password. default 'Enter new password'
//     enterpass2 - label of validation field. default 'again:'
//     mismatched - text of 'passwords mismatch'
// css classes:
//		.tfPassBundle , .tfPassBundle.mismatched , .tfPassMismatched , .tfPass1 input , .tfPass2 input
class TfTypepass2 extends TfTypestring {

	function validate($value) {
		if (isset($_POST[$this->eid . ' match']) && ($_POST[$this->eid . ' match'] != $value))
			return $this->valerror(_('Password validation mismatched'));
		return parent::validate($value);
	}

	function htmlInput($override=array()) {
		if ($this->paramtrue('echo',true)) {
			$pass = $this->value;
		} else {
			$pass = '';
		}
		if (isset($this->params['enterpass1']))
			$enterpass1 = $this->params['enterpass1'];
		else
			$enterpass1 = _('Enter new password');
		if (isset($this->params['enterpass2']))
			$enterpass2 = $this->params['enterpass2'];
		else
			$enterpass2 = _('...and again');
		if (isset($this->params['mismatched']))
			$mismatched = $this->params['mismatched'];
		else
			$mismatched = _('password mismatched');

		$override['onblue']="matchpasswords(this,document.getElementById('$this->eid match'),this,document.getElementById('$this->eid bundle'),".(1*$this->fetch['okempty']).")";
		return "<div id='$this->eid bundle' class='tfPassBundle ".$this->fname."'>"
					."<label class=tfPass1><span class='add-on icon-key'></span><input value=\"".fix4html2($pass)."\" type=password placeholder=\"".fix4html2($enterpass1)."\" ".$this->intag($override)
					."></label><label class=tfPass2><span class='add-on icon-double-angle-right'></span><input id='$this->eid match' name='$this->eid match' value='' type=password placeholder=\"".fix4html2($enterpass2)."\" "
					."onblur=\"matchpasswords(document.getElementById('$this->eid'),this,document.getElementById('$this->eid bundle'),".(1*$this->fetch['okempty']).")\">"
					.'</label><span class=tfPassMismatched>'.$mismatched.'</span></div>';
	}
}// class TfTypepass2

////////////////////////////////////////
// Password +Validation + Validate current password before allow changing
// Requires validation of the password by entering it twice
// AND enter current password (if set)
// meta params:
//     echo - Default false. When set to False/0 doesn't return saved password back to html input, for security reasons.
//     enterpass1 - label (placeholder) of first password. default 'Enter new password'
//     enterpass2 - label (placeholder) of validation field. default '...and again'
//     enterpass3 - label (placeholder) of current password field. default 'Current password:'
//     mismatched - text of 'passwords mismatch'
// css classes:
//		.tfPassBundle , .tfPassBundle.mismatched , .tfPassMismatched , .tfPass1 input , .tfPass2 input, .tfPass3 input
class TfTypepass3 extends TfTypestring {

	function validate($value) {
		$ok=parent::validate($value);
		if ($ok)
			if (empty($_POST[$this->eid.' current']))
				return $this->valerror(_('Please type current password'));
			elseif (($_POST[$this->eid.' current'] != $this->value) && md5($_POST[$this->eid.' current'])!=$this->value)
				return $this->valerror(_('Wrong current password'));
		return $ok;
	}

	function htmlInput($override=array()) {
		if ($this->paramtrue('echo',true)) {
			$pass = $this->value;
		} else {
			$pass = '';
		}
		if (isset($this->params['enterpass1']))
			$enterpass1 = $this->params['enterpass1'];
		else
			$enterpass1 = _('Enter new password');
		if (isset($this->params['enterpass2']))
			$enterpass2 = $this->params['enterpass2'];
		else
			$enterpass2 = _('...and again');
		if (isset($this->params['enterpass3']))
			$enterpass3 = $this->params['enterpass3'];
		else
			$enterpass3 = _('Current password');
		if (isset($this->params['mismatched']))
			$mismatched = $this->params['mismatched'];
		else
			$mismatched = _('password mismatched');
		$override['onblue']="matchpasswords(this,document.getElementById('$this->eid match'),this,document.getElementById('$this->eid bundle'),".(1*$this->fetch['okempty']).")";
		return "<div id='$this->eid bundle' class='tfPassBundle ".$this->fname."'>"
					."<label class=tfPass1><span class='add-on icon-key'></span><input value=\"".fix4html2($pass)."\" type=password placeholder=\"".fix4html2($enterpass1)."\" ".$this->intag($override)
					."></label><label class=tfPass2><span class='add-on icon-double-angle-right'></span><input id='$this->eid match' name='$this->eid match' value='' type=password placeholder=\"".fix4html2($enterpass2)."\" "
					."onblur=\"matchpasswords(document.getElementById('$this->eid'),this,document.getElementById('$this->eid bundle'),".(1*$this->fetch['okempty']).")\">"
					.'</label><span class=tfPassMismatched>'.$mismatched.'</span>'
					."<label class=tfPass3><span class='add-on icon-lock'></span><input id='$this->eid current' name='$this->eid current' value='' type=password placeholder=\"".fix4html2($enterpass3)."\"></label></div>";
	}
}// class TfTypepass3

////////////////////////////////////////
// large text field
class TfTypetext extends TfTypestring {

	function sqlType() {
		return "TEXT";
	}

	function htmlInput($override=array()) {
		return '<textarea '.$this->intag($override).' >'
				. $this->value  //  fix4html3($this->value)
				. '</textarea>'
				.'<div class=tfhtmlicons>'
				.'<i class="icon-share" onclick="'."\$('#$this->eid').dialog({width:'70%',height:450,modal:true,resizable:true,draggable:true,closeText:'X',title:'".fix4js2($this->fetch['label'])."',close:function(){\$(this).dialog('destroy')}});".'" title="'._('Open full screen').'"></i>'
				.'<i class="icon-exchange" onclick="'."if (document.getElementById('$this->eid').style.direction=='rtl') document.getElementById('$this->eid').style.direction='ltr'; '
				.'else document.getElementById('$this->eid').style.direction='rtl';".'" title="'._('Change direction (RTL/LTR)').'"></i></div>';
	}

}

// class TfTypetext
////////////////////////////////////////
// html text field
class TfTypehtml extends TfTypetext {

	function htmlInput($override=array()) {
		return '<textarea '.$this->intag($override).'>'
				.$this->value  //  fix4html3($this->value)
				.'</textarea>'
				.'<div class=tfhtmlicons>'
					.'<i class="icon-share" onclick="'."previewHtml(document.getElementById('$this->eid'),'".fix4js1($this->fetch['label'])."');".'" title="'._('preview').'"></i>'
					.'<i class="icon-edit" onclick="CKEDITOR.replace(\''.$this->eid.'\');" title="'._('edit HTML').'"></i>'
					.'<i class="icon-exchange" onclick="'."if (document.getElementById('$this->eid').style.direction=='rtl') document.getElementById('$this->eid').style.direction=CKEDITOR.config.contentsLangDirection='ltr'; else document.getElementById('$this->eid').style.direction=CKEDITOR.config.contentsLangDirection='rtl';".'" title="'._('Change direction (RTL/LTR)').'"></i>'
				.'</div>';
}

	function htmlView($override=array()) {
		return '<span class="add-on icon-share" title="preview" onclick="previewHtml(document.getelementById(\''.$this->eid.'\').innerHTML)"></span><code id="'.$this->eid.' cide">'.htmlentities(substr($this->value,0,20),ENT_QUOTES,'UTF-8').'</code>'
			.'<div class=popover id='.$this->eid.'>'.$this->value.'</div>';
	}

}// class TfTypehtml


////////////////////////////////////////
// File
// validate($_FILES[key]) - validates:
//             allowed mime types from meta param 'mimes' ; separated,
//             allowed extensions from meta param 'extensions' ; separated,
//             for file min/max size, if okmax / okmin are set
//             for upload errors
//
// fix('name') - returns the filename to be used, as if the file has been saved, accoarding to meta param:
//             if overwrite=auto:      the file is auto named only when already exists.
//             if overwrite=autoname:  the file is always auto named.
//             if overwrite=yes:       the file is never auto named, and when the file already exists it's being overwritten.
//             if overwrite=no:        the file is never auto named. when file already exists - error is set, and null is returned.
//             zeros: when the file is auto named, the length of the addition is meta param 'zeros'.
//             if autoname='' or 'zeros' or 'number' or 'numbers': the auto-name addition is numbers
//             if autoname='chars': the auto-name addition is char based (aaa aab aac ... aba abb .. zzz)
//             if autoname='hex': the auto-name addition counts hexa numbers
//             if autoname='RandChars': the auto-name addition is randomized chars
//             if autoname='RandNumbers' or 'RandDigits' or 'RandNums': the auto-name addition is randomized decimal digits
//             if autoname='RandHex': the auto-name addition is randomized hex-digits
//             when autoname system failes - error is set, and null is returned.
//
// set($_FILES[key]) -
//             delete the current file (if current value is set and the file exists)
//             saves the new uploaded file to the name params['path.rel'].params['basename']
//             the file name is fixed using fix(params['basename']).
//             the new file name is returned and saved in the current field value.
//
// set(''),
// set(null)   - delete the current file (if current value is set and file exists).
//
// set('name') - if the current value file exists - it is renamed to the new 'name' (using fix('name') for auto-name).
//               if the current value is '' or file doesnt exists - the value is blindly changed.
//               the new file name value is returned (null indicates an error).
//               if you want the name to be empty, use set('.');
//
// meta params:
//   basename - the file name to be used when uploading new files.
//  extension - the file extension, added with a .dot to the basename, after autonaming. when '' - the extension is saved from the original file name.
//  overwrite - overwrite method. default 'auto'. values: auto/autoname/yes/no.
//   autoname - auto name system to be used.
//      zeros - set length of auto name addition.
//        dir - local directory to save files. Must include / at end, or it will be added to the filename as prefix.
//    url.rel - prefix for file link href. Must include / at end, or it will be added to the filename as prefix.
//      mimes - allowed MIME types, ; separated. it is only set the 'accept' parameter in the file input.
// extensions - allowed source file extensions
//
// remember <form METHOD=POST ENCTYPE='multipart/form-data' >

class TfTypefile extends TfType {

	function sqlType() {
		return "VARCHAR(255)";
	}

	function populate($fetch,&$table) {
		$this->isnum=false;
		parent::populate($fetch, $table);
	}

	function validate($value) {
		global $tf;
		if (is_array($value)) {
			if ($value['name'] == '')
				$value = '';
		}
		// check empty file
		if (empty($value['name'])) {
			if ($this->fetch['okempty']) {
				return true;
			} else {
				return $this->valerror(_('Cannot be left empty'));
			}
		}

		clearstatcache();  // must do it so the info would be current and not cached
		if (is_array($value)) { // file uploaded just now - we receive a $_FILES['fname'] style array
			if ($value['error'] != UPLOAD_ERR_OK) { // php upload error
				switch ($value['error']) {
					case UPLOAD_ERR_INI_SIZE:
						return $this->valerror(_('File exceeds upload_max_filesize'));

					case UPLOAD_ERR_FORM_SIZE:
						return $this->valerror(_('File exceeds MAX_FILE_SIZE'));

					case UPLOAD_ERR_PARTIAL:
						return $this->valerror(_('File was only partially uploaded'));

					case UPLOAD_ERR_NO_FILE:
						return $this->valerror(_('No file was uploaded'));

					case UPLOAD_ERR_NO_TMP_DIR:
						error_log("PHP Missing a temporary folder. $this->tname.$this->fname=$value[tmp_name]");
						return $this->valerror(_('PHP Missing a temporary folder'));
				}
				return $this->valerror("Unknown file upload error $value[error]");
			}

			if (!file_exists($value['tmp_name']))
				return $this->valerror(_('File does not exist in temp folder'));
			$fz = filesize($value['tmp_name']);
			if ($fz < 0)
				return $this->valerror(_('Error reading file size'));
			if (($this->fetch['okmax'] > 0) && ($this->fetch['okmax'] < $fz))
				return $this->valerror(_('File too big'));
			if (($this->fetch['okmin'] > 0) && ($this->fetch['okmin'] > $fz))
				return $this->valerror(_('File too small'));
			if ((!$this->fetch['okempty']) && ($fz == 0))
				return $this->valerror(_('File is empty'));

			if (!empty($this->params['extensions'])) {
				$ext = getExtension($value['name']);
				if (stripos(';'.$this->params['extensions'].';', ";$ext;") === false)
					return $this->valerror(_('File extension not allowed'));
			}

			// if we got here it means everything's ok
			// i hope someone would this->set to move the file out of temp folder
		}else {
			if (file_exists($tf['path.rel'] . $this->params['path.rel'] . $value)) {
				$fz = filesize($tf['path.rel'] . $this->params['path.rel'] . $value);
				if ($fz < 0)
					return $this->valerror(_('Error reading file size'));
				if (($this->fetch['okmax'] > 0) && ($this->fetch['okmax'] < $fz))
					return $this->valerror(_('File too big'));
				if (($this->fetch['okmin'] > 0) && ($this->fetch['okmin'] > $fz))
					return $this->valerror(_('File too small'));
				if ((!$this->fetch['okempty']) && ($fz == 0))
					return $this->valerror(_('File is empty'));
			} else {
				return $this->valerror(_('File does not exist'));
			}

			if (!empty($this->params['extensions'])) {
				$ext = getExtension($value['name']);
				if (stripos(';'.$this->params['extensions'].';', ";$ext;") === false)
					return $this->valerror(_('File extension not allowed'));
			}

		}
		return true;
	}

	function htmlInput($override=array()) {
		// new upload?
		if ($this->value == '' || $this->value == $this->fetch['default'])
			return $this->htmlNew($override);

		if ($this->fetch['okempty'])
			return $this->htmlView($override) // delete option
				."<label id='$this->eid del' class=tfFileDel><i class=icon-trash></i><input type=CHECKBOX name='$this->eid del' value=0 onchange=\"tfFd(this,'$this->eid')\" /></label>";
		else
			return $this->htmlView($override);
	}

	function htmlView($override=array()) {
		global $tf;
		if ($this->value != '') {
			return "<a href=\"" . fix4html2($tf['url.rel'] . $this->param('url.rel') . $this->value) . "\" title=\"" . fix4html2($this->value) . "\" ".$this->intag($override).">$this->value</a>";
		} else {
			return '';
		}
	}

	function htmlNew($override=array()) {
		if ($this->fetch['okmax'] > 0)
			$inp.="<input type=hidden name='MAX_FILE_SIZE' value='" . fix4html1($this->fetch['okmax']) . "' />";
		$accept = $this->param('mimes');
		if (!empty($accept))
			$accept = " accept='$accept' ";
		$override['type']='file';
		$inp = "<input ".$this->intag($override)." $accept />";
		return $inp;
	}

	function fix($newvalue = '') {
		global $tf;
		if (is_array($newvalue)) {
			$newname = $newvalue['name'];
		} else {
			$newname = $newvalue;
		}

		// overwrite and autoname
		$ovr = strtolower($this->param('overwrite'));
		if ($ovr == '')
			$ovr = 'auto';
		$fnewname = $tf['path.rel'] . $this->param('path.rel') . $newname;
		if ($ovr == 'yes') { // overwrite, dont autoname
			if ($newname == '')
				return $this->valerror(_('No filename given'));
			if (!file_exists($fnewname)) {
				return $newname;
			} else {
				if (is_writable($fnewname)) {
					return $newname;
				} else {
					return $this->valerror(_('No overwrite permission for this filename'));
				}
			}
		}
		if ($ovr == 'no') { // dont overwrite dont autoname
			if ($newname == '')
				return $this->valerror(_('No filename given'));
			if (!file_exists($fnewname)) {
				return $newname;
			} else {
				return $this->valerror(_('filename already exists'));
			}
		}
		// overwrite is not yes/no
		$ovr = strtolower($ovr);
		$ovr = str_replace(' ', '', $ovr);
		$ovr = str_replace('-', '', $ovr);
		$ovr = str_replace('_', '', $ovr);
		$ovr = str_replace('.', '', $ovr);
		if ($ovr == '')
			$ovr = 'auto'; // default for overwrite

		if ($ovr == 'auto' || $ovr == 'autoname') {  // autoname when filename exists OR when overwrite=autoname
			if ($newname != '' && $ovr != 'autoname' && !file_exists($fnewname)) {
				return $newname;
			} else {
				// autoname! weee :)
				return $this->autoname($newname);
			}
		}

		return $this->valerror("unknown overwrite mode ($ovr)", 'dbg');
	}

	function autoname($newvalue) {
		global $tf;
		// defautls
		$newname = '';
		$basename = '';
		$ext = '';
		$dir = $tf['path.rel'] . $this->param('path.rel');
		$zeros = $this->param('zeros');
		$method = $this->param('autoname');
		$startfrom = $this->param('startfrom');
		if ($startfrom == '')
			$startfrom = 1;

		// set data from a regular filename
		if (is_array($newvalue)) {
			if (array_key_exists('name', $newvalue))
				$newname = $newvalue['name'];
		}else {
			$newname = $newvalue;
		}
		if ($newname != '') {
			$path = pathinfo($newname);
			$basename = substr($path['basename'], 0, strlen($path['basename']) - strlen($path['extension']));
			if (substr($basename, strlen($basename) - 1, 1) == '.')
				$basename = substr($basename, 0, strlen($basename) - 1);
			$ext = $path['extension'];
		}
		$basename = $this->param('basename');


		// set data from array of parameters
		if (is_array($newvalue)) {
			if (array_key_exists('basename', $newvalue))
				$basename = $newvalue['basename'];
			if (array_key_exists('prefix', $newvalue))
				$basename = $newvalue['prefix'];
			if (array_key_exists('ext', $newvalue))
				$ext = $newvalue['ext'];
			if (array_key_exists('extension', $newvalue))
				$ext = $newvalue['extension'];
			if (array_key_exists('zeros', $newvalue))
				$zeros = $newvalue['zeros'];
			if (array_key_exists('folder', $newvalue))
				$dir = $newvalue['folder'];
			if (array_key_exists('directory', $newvalue))
				$dir = $newvalue['directory'];
			if (array_key_exists('dir', $newvalue))
				$dir = $newvalue['dir'];
			if (array_key_exists('method', $newvalue))
				$method = $newvalue['method'];
			if (array_key_exists('autoname', $newvalue))
				$method = $newvalue['autoname'];
			if (array_key_exists('startfrom', $newvalue))
				$startfrom = $newvalue['startfrom'];
		}

		// get method
		$method = strtolower($method);
		// cleanup
		$method = str_replace(' ', '', $method);
		$method = str_replace('`', '', $method);
		$method = str_replace('"', '', $method);
		$method = str_replace("'", '', $method);
		$method = str_replace('.', '', $method);
		$method = str_replace('-', '', $method);
		$method = str_replace('_', '', $method);
		$method = str_replace('/', '', $method);
		$method = str_replace("\\", '', $method);
		// fix code names
		$method = str_replace('randomize', 'rand', $method);
		$method = str_replace('random', 'rand', $method);
		$method = str_replace('rnd', 'rand', $method);
		$method = str_replace('hexa', 'hex', $method);
		$method = str_replace('hexdecimal', 'hex', $method);
		$method = str_replace('hexdeci', 'hex', $method);
		$method = str_replace('hexdec', 'hex', $method);
		$method = str_replace('hexs', 'hex', $method);
		$method = str_replace('zero', 'num', $method);
		$method = str_replace('digit', 'num', $method);
		$method = str_replace('number', 'num', $method);
		$method = str_replace('numb', 'num', $method);
		$method = str_replace('nomber', 'num', $method);
		$method = str_replace('nums', 'num', $method);
		$method = str_replace('character', 'char', $method);
		$method = str_replace('chr', 'char', $method);
		$method = str_replace('chars', 'char', $method);
		if (substr($method, strlen($method) - 1, 1) == 's')
			$method = substr($method, 0, strlen($method) - 1); // remove plural s

		if ($method == '')
			$method = 'num'; // default

		$footer = $ext;
		if ($footer != '')
			$footer = ".$footer";
		$name = $basename;

		// count
		if ($method == 'num' || $method == '#' || $method = '0' || $method == '10') {
			clearstatcache();  // must do it so the info would be current and not cached
			$i = $startfrom;
			while (file_exists($dir . $name . zeros($i, $zeros) . $footer))
				$i++;
			return $name . zeros($i, $zeros) . $footer;
		}
		if ($method == 'hex' || $method == 'x' || $method == '16') {
			clearstatcache();  // must do it so the info would be current and not cached
			$i = $startfrom;
			while (file_exists($dir . $name . str_pad(dechex($i), $zeros, '0', STR_PAD_LEFT) . $footer))
				$i++;
			return $name . str_pad(dechex($i), $zeros, '0', STR_PAD_LEFT) . $footer;
		}
		if ($method == 'both' || $method == '36') {
			clearstatcache();  // must do it so the info would be current and not cached
			$i = $startfrom;
			while (file_exists($dir . $name . str_pad(base_convert($i, 10, 36), $zeros, '0', STR_PAD_LEFT) . $footer))
				$i++;
			return $name . str_pad(base_convert($i, 10, 36), $zeros, '0', STR_PAD_LEFT) . $footer;
		}
		if ($method == 'char' || $method == 'a' || $method == '26') {
			clearstatcache();  // must do it so the info would be current and not cached
			$i = $startfrom;
			while (file_exists($dir . $name . str_pad(decabc($i), $zeros, '0', STR_PAD_LEFT) . $footer))
				$i++;
			return $name . str_pad(decabc($i), $zeros, '0', STR_PAD_LEFT) . $footer;
		}

		// random
		if ($method == 'randnum' || $method == 'rand#' || $method = 'rand0' || $method == 'rand10') {
			clearstatcache();  // must do it so the info would be current and not cached
			$i = randstring($zeros, '0123456789');
			while (file_exists($dir . $name . $i . $footer))
				$i = randstring($zeros, '0123456789');
			return $name . $i . $footer;
		}
		if ($method == 'randhex' || $method == 'randx' || $method == 'rand16') {
			clearstatcache();  // must do it so the info would be current and not cached
			$i = randstring($zeros, '0123456789abcdef');
			while (file_exists($dir . $name . $i . $footer))
				$i = randstring($zeros, '0123456789abcdef');
			return $name . $i . $footer;
		}
		if ($method == 'randboth' || $method == 'rand36') {
			clearstatcache();  // must do it so the info would be current and not cached
			$i = randstring($zeros, '0123456789abcdefghijklmnopqrstuvwxyz');
			while (file_exists($dir . $name . $i . $footer))
				$i = randstring($zeros, '0123456789abcdefghijklmnopqrstuvwxyz');
			return $name . $i . $footer;
		}
		if ($method == 'randchar' || $method == 'randa' || $method == 'rand26') {
			clearstatcache();  // must do it so the info would be current and not cached
			$i = randstring($zeros, 'abcdefghijklmnopqrstuvwxyz');
			while (file_exists($dir . $name . $i . $footer))
				$i = randstring($zeros, 'abcdefghijklmnopqrstuvwxyz');
			return $name . $i . $footer;
		}

		return $this->valerror("Autoname: unknown method '$method'");
	}

	function save($newvalue) { // backwards compatibility
		return $this->set($newvalue);
	}

	function set($newvalue) {
		global $tf;
		if (!is_array($newvalue) && array_key_exists($this->fname, $_FILES)) {
			$_FILES[$this->fname]['name'] = $newvalue;
			$newvalue = $_FILES[$this->fname];
		}
		if (is_array($newvalue)) { // file upload! yeee! :)
			$name = $this->fix($newvalue['name']);
			if (move_uploaded_file($newvalue['tmp_name'], $tf['path.rel'] . $this->param('path.rel') . $name)) {
				if ($this->value != '') {
					if (file_exists($tf['path.rel'] . $this->param('path.rel') . $this->value)) {
						unlink($tf['path.rel'] . $this->param('path.rel') . $this->value);
					}
				}
				$this->value = $name;
			} else {
				return $this->valerror(_('Error uploading file'));
			}
		} else {
			if ($newvalue === null || $newvalue == '')
				return $this->del();
			if ($newvalue == '.')
				$newvalue = '';
			if ($this->value == '') {
				$this->value = $newvalue;
			} else {
				if (rename($tf['path.rel'] . $this->param('path.rel') . $this->value, $tf['path.rel'] . $this->param('path.rel') . $newvalue)) {
					$this->value = $newvalue;
				}
			}
		}
		return $this->value;
	}

	function del() {
		global $tf;
		if ($this->value == '')
			return true;
		if (unlink($tf['path.rel'] . $this->param('path.rel') . $this->value)) {
			$this->value = '';
			$this->error = '';
			return true;
		} else {
			error_log("error deleting `$this->tname.$this->fname`: " . $tf['path.rel'] . $this->param('path.rel') . $this->value);
			$this->valerror(_('Delete failed'));
			$this->value = '';
			return false;
		}
	}


}// class TfTypefile

////////////////////////////////////////
// picture file upload
// params: noimg - optional. src of 'no image' image.
//       url.rel - optional. prefix for src. must end with "/". added to $tf['url.rel']
//      path.rel - optional. prefix for file local dir on server. must end with "/". added to $tf['path.rel']
//       width and height - width and height limits. proportions are saved.
// see TfTypefile for params info.
class TfTypepicture extends TfTypefile {

	//function validate($value){
	//}

	function htmlView($override=array()) {
		global $tf;
		// when height/width specifically given in $override, dont try to fix it myself
		if (isset($override['height']) || isset($override['width'])) {
			$fixwh=false;
		} else {
			$fixwh=true;
			// get fix w/h limits
			if ($fixwh) {
				$maxh = $this->param('height');
				if ($maxh == '')
					$maxh = $this->param('maxheight');
				if ($maxh == '')
					$maxh = $this->param('maxh');
				if ($maxh == '')
					$maxh = $this->param('limitheight');
				if ($maxh == '')
					$maxh = $this->param('limith');
				$maxw = $this->param('width');
				if ($maxw == '')
					$maxw = $this->param('maxwidth');
				if ($maxw == '')
					$maxw = $this->param('maxw');
				if ($maxw == '')
					$maxw = $this->param('limitwidth');
				if ($maxw == '')
					$maxw = $this->param('limitw');
				$fixwh = $maxh != '' || $maxw != '';  // if no maxw and no maxh dont try to fix anything
			}
		}

		// set no-image when value==''
		if ($this->value != '') {
			$img = $tf['path.rel'] . $this->param('path.rel') . $this->value;
			// fix w/h of picture without distorting limit w/h
			if ($fixwh) {
				if ($maxh == '')
					$maxh = 9999; // no limit
				if ($maxw == '')
					$maxw = 9999; // no limit
				$size = getimagesize($img);
				if ($size) {
					$rw = $size[0];  // real_width
					$rh = $size[1];  // real_height
					$hw = $rh / $rw;   // height/width
					$h = $rh;		// height
					$w = $rw;		// width
					if ($h > $maxh) { // limit height?
						$h = $maxh;
						$w = $h / $hw;
					}
					if ($w > $maxw) { // limit width?
						$w = $maxw;
						$h = $w * $hw;
					}
					// the smaller limitation would be taken naturally.
				}
			}
			if (isset($h))
				$override['height']=$h;
			if (isset($w))
				$override['width']=$w;
		}

		// set a link to the big picture?
		$link = chkBool($this->param('link'), true);
		// set no-image when value==''
		if ($this->value != '') {
			$img = $tf['url.rel'] . $this->param('url.rel') . $this->value;
		} else {
			$link = false;
			$img = $this->param('noimg');
			if ($img == '')
				$img = $tf['url.rel'] . $this->param('no-img');
			if ($img == '')
				$img = $tf['url.rel'] . $this->param('no img');
			if ($img == '')
				$img = $tf['url.rel'] . $this->param('noimage');
			if ($img == '')
				$img = $tf['url.rel'] . $this->param('no image');
			if ($img == '')
				$img = $tf['url.rel'] . $this->param('no-image');
		}

		if ($img == '')
			return '';
		$override['title']=$this->value;
		if ($link)
			return "<a href=\"$img\" ".$this->intag($override)."><img border=0 src=\"$img\"  /></a>";
		else
			return "<img border=0 src=\"$img\" ".$this->intag($override)." />";
	}

}// class TfTypepicture

////////////////////////////////////////
// Blob File
// TODO: allow a way to download the file
// validate($_FILES[key]) - validates:
//             //allowed mime types from param('mimes') ; separated,
//             allowed extensions from param('exts' / extensions) ; separated,
//             for file min/max size, if okmax / okmin are set
//             for upload errors
// params:
//    displaylength - How much to show from the file beginning, if at all. Default 0
//
// remember <form METHOD=POST ENCTYPE='multipart/form-data' >

class TfTypeblob extends TfTypefile {

	function sqlType() {
		return "BLOB";
	}

	function validate($value) {
		return parent::validate($value);
		if (is_array($value)) {
			if ($value['tmp_name'] == '')
				$value = '';
		}
		// check empty file
		if (!$this->notempty($value)) {
			if (!$this->fetch['okempty']) {
				return true;
			} else {
				if (empty($this->value)) {
					return $this->valerror(_('Cannot be left empty'));
				} else {
					return true;
				}
			}
		}

		clearstatcache();  // must do it so the info would be current and not cached
		if (is_array($value)) { // file uploaded just now - we receive a $_FILES['fname'] style array
			if ($value['error'] != UPLOAD_ERR_OK) { // php upload error
				switch ($value['error']) {
					case UPLOAD_ERR_INI_SIZE:
						return $this->valerror(_('File exceeds upload_max_filesize'));

					case UPLOAD_ERR_FORM_SIZE:
						return $this->valerror(_('File exceeds MAX_FILE_SIZE'));

					case UPLOAD_ERR_PARTIAL:
						return $this->valerror(_('File was only partially uploaded'));

					case UPLOAD_ERR_NO_FILE:
						return $this->valerror(_('No file was uploaded'));

					case UPLOAD_ERR_NO_TMP_DIR:
						error_log("PHP Missing a temporary folder. $this->fname;$this->fname;$value[tmp_name]");
						return $this->valerror(_('PHP Missing a temporary folder'));
				}
				return $this->valerror("Unknown file upload error $value[error]");
			}

			if (!file_exists($value['tmp_name']))
				return $this->valerror(_('File does not exist in temp folder'));
			$fz = filesize($value['tmp_name']);
			if ($fz < 0)
				return $this->valerror(_('Error reading file size'));
			if (($this->fetch['okmax'] > 0) && ($this->fetch['okmax'] < $fz))
				return $this->valerror(_('File too big'));
			if (($this->fetch['okmin'] > 0) && ($this->fetch['okmin'] > $fz))
				return $this->valerror(_('File too small'));
			if ((!$this->fetch['okempty']) && ($fz == 0))
				return $this->valerror(_('File is empty'));

			$exts = $this->param('exts');
			if ($exts) {
				$ext = getExtension($value['name']);
				if (stripos(";$exts;", ";$ext;") === false)
					return $this->valerror(_('File extension not allowed'));
			}

			// if we got here it means everything's ok
			// i hope someone would this->set to move the file out of temp folder
		}else {
			return parent::validate($value);
		}
	}

	function htmlInput($override=array()) {
		// new upload?
		if (true || $this->value == '' || $this->value == $this->fetch['default'])
			return $this->htmlNew($override);

		// update - first put a link to the file
		$inp = $this->htmlView($override);
		// delete option
		if ($this->fetch['okempty']) {
			$inp.= "<label class='tfFileDel icon-remove'><input type=CHECKBOX name='$this->eid del' id='$this->eid del' title=\"".fix4html2(_('delete'))
				."\" onchange=\"if (this.checked) { \$(this.parent).removeClass('del0').addClass('del1'); \$('#$this->eid').addClass('delete');} else { \$(this.parent).removeClass('del1').addClass('del0'); \$('#$this->eid').removeClass('delete');}\" />"
				."</label>";
			// when deleting set value=''
		}

		return $inp; // let go reupload for now... no time. we can delete then upload.
	}

	function htmlView($override=array()) {
		if ($this->value != '') {
			$len=1*$this->param('displaylength');
			if ($len) $preview=" start=" . htmlentities(substr($this->value, 0, 20),ENT_QUOTES,'UTF-8');
			else $preview='';
			return "<span ".$this->intag($override).">[BLOB length=" . strlen($this->value) . $preview . "]</span>";
		} else {
			return '';
		}
	}

	function htmlNew($override=array()) {
		$inp = '<input type=file '.$override.' />';
		if ($this->fetch['okmax'] > 0) return '<input type=hidden name="MAX_FILE_SIZE" value='.$this->fetch['okmax'].'">'.$inp;
		return $inp;
	}

	function fix($newvalue = '') {
		return $newvalue;
	}

	function set($newvalue) {
		if (!is_array($newvalue) && array_key_exists($this->fname, $_FILES)) {
			$_FILES[$this->fname]['name'] = $newvalue;
			$newvalue = $_FILES[$this->fname];
		}
		if (is_array($newvalue)) { // file upload! yeee! :)
			if ($newvalue['tmp_name'] != '') {
				$this->value = file_get_contents($newvalue['tmp_name']);
			}
		} else {
			$this->value = $newvalue;
		}
		return $this->value;
	}

	function del() {

	}

}// class TfTypeblob

////////////////////////////////////////
// picture file blob
// params: noimg - src of 'no image' image.
//       width and height - width and height limits. proportions are saved.
class TfTypepictureblob extends TfTypeblob {

	function validate($value) {
		// read image header and validate it
		if (is_array($value) && isset($value['tmp_name'])) {
			if (function_exists('exif_imagetype')) {
				return exif_imagetype($value['tmp_name']) != false;
			}
		}
		return parent::validate($value);
	}

	function fix($value='') {
		return $value;
	}

	function htmlView($override=array()) {
		global $tf;
		$fixwh = true; // limit w/h of picture?
		if (!empty($override['width']) || !empty($override['height'])) $fixwh = false;

		// get fix w/h limits
		if ($fixwh) {
			$maxh = $this->intag['height'];
			if ($maxh == '')
				$maxh = $this->param('maxheight');
			if ($maxh == '')
				$maxh = $this->param('maxh');
			if ($maxh == '')
				$maxh = $this->param('limitheight');
			if ($maxh == '')
				$maxh = $this->param('limith');
			$maxw = $this->intag['width'];
			if ($maxw == '')
				$maxw = $this->param('maxwidth');
			if ($maxw == '')
				$maxw = $this->param('maxw');
			if ($maxw == '')
				$maxw = $this->param('limitwidth');
			if ($maxw == '')
				$maxw = $this->param('limitw');
			$fixwh = $maxh != '' || $maxw != '';  // if no maxw and no maxh dont try to fix anything
		}

		// set no-image when value==''
		if ($this->value != '') {
			$img = $tf['path.rel'] . $this->param('path.rel') . $this->value;
			// fix w/h of picture without distorting limit w/h
			if ($fixwh) {
				if ($maxh == '')
					$maxh = 9999; // no limit
				if ($maxw == '')
					$maxw = 9999; // no limit
				$size = getimagesize($img);
				if ($size) {
					$rw = $size[0];  // real_width
					$rh = $size[1];  // real_height
					$hw = $rh / $rw;   // height/width
					$h = $rh;		// height
					$w = $rw;		// width
					if ($h > $maxh) { // limit height?
						$h = $maxh;
						$w = $h / $hw;
					}
					if ($w > $maxw) { // limit width?
						$w = $maxw;
						$h = $w * $hw;
					}
					// the smaller limitation would be taken naturally.
				}
			}
			if (isset($h))
				$override['height']=$h;
			if (isset($w))
				$override['width']=$w;
		}

		// set a link to the big picture?
		$link = chkBool($this->param('link'), true);
		// set no-image when value==''
		if ($this->value != '') {
			$base64 = chunk_split(base64_encode($this->value));
		} else {
			$link = 0;
			$img = $this->param('noimg');
			if ($img == '')
				$img = $tf['url.rel'] . $this->param('no-img');
			if ($img == '')
				$img = $tf['url.rel'] . $this->param('no img');
			if ($img == '')
				$img = $tf['url.rel'] . $this->param('noimage');
			if ($img == '')
				$img = $tf['url.rel'] . $this->param('no image');
			if ($img == '')
				$img = $tf['url.rel'] . $this->param('no-image');
			if (!file_exists($img) && file_exists($tf['path.rel'] . $img))
				$img = $tf['path.rel'] . $img;
			$base64 = chunk_split(base64_encode(file_get_contents($img)));
		}

		if ($img == '')
			return '';
		return '<img src="data:image/gif;base64,$base64" '.$this->intag($override).' />';
	}

	function del() {
	}

}// class TfTypepictureblob

////////////////////////////////////////
// Boolean value
// displayed as checkbox
// params:
//    yes,no,null - Text to be written on view mode. Default 'True','False','-'
class TfTypeboolean extends TfType {

	var $yes=null,$no=null,$null=null;

	function sqlType() {
		return "BOOL";
	}

	function populate($fetch,&$table) {
		$this->isnum=false;
		parent::populate($fetch,$table);
		if (isset($this->params['yes']))  $this->yes=$this->params['yes'];
		else $this->yes=_('True');
		if (isset($this->params['no']))   $this->no=$this->params['no'];
		else $this->no=_('False');
		if (isset($this->params['null'])) $this->null=$this->params['null'];
		else $this->null=_('-');
	}

	function fix($value) {
		if ($value===1 || $value===0 || $value==null) return $value;
		if ($value==='1' || $value==='0') return 1*$value;
		$value=chkBool($value, null); // get null for unknown values
		if ($value===null && !$this->fetch['oknull']) return $this->fetch['default']; // set default parameter when null
		return $value;
	}

	function validate($value) {
		if ($value===1 || $value===0 || $value==='1' || $value==='0') return true;
		if ($value===null)
			if ($this->fetch['oknull'])
				return true;

		return $this->valerror(_('Must be 1/0'));
	}

	function htmlInput($override=array()) {
		if (array_key_exists('class',$override)) {
			if (!is_array($override['class'])) $override['class']=explode(' ',$override['class']);
		} else $override['class']=array();
		$override['class'][]='checkbox';
		if ($this->value) $override['class'][]='checked';
		$override['onchange']=@$override['onchange']."this.value=1*this.checked;if (this.value) \$(this).addClass('checked'); else \$(this).removeClass('checked');".(@$override['onchange']);
		return '<input type=CHECKBOX '.$this->intag($override).' value='.($this->value?'1 checked':'0').' />';
	}

	function view() {
		if ($this->value === null) return $this->null;
		if ($this->value) return $this->yes;
		return $this->no;
	}

	function htmlView($override=array()) {
		$override['readonly']='readonly';
		return $this->htmlInput($override);
	}

	function search_methods() {
		if ($this->fetch['oknull'])
			return array(
				'n'=>_('Is Null'),
				'b'=>_('Is True'));
		else
			return array(
				'b'=>_('Is True'));
	}

	function to_statistics(&$array) {
		@$array['Count']+=1;
		if ($this->value==='' || $this->value===null)
			@$array['Total Empty']+=1;
		else
			if ($this->value)
				@$array['Total Yes']+=1;
			else
				@$array['Total No']+=1;

		// for translations...
		return;
		_('Total Yes');
		_('Total No');
		_('Total Empty');
	}

	function to_statistics_end(&$array) {
		unset($array['Count']);
	}

}// class TfTypeboolean

////////////////////////////////////////
// Boolean value displayed as yes and no
// params:
//    yes,no - The way yes and no are written. you can put something like <img src=/yes.gif>
// css classes:
//   input:
//      div.tfYesNo , div.tfYesNo.yes , div.tfYesNo.no , .tfYesNo.yes input.yes , .tfYesNo.no input.yes , .tfYesNo.yes input.no , tfYesNo.no input.no
//   view:
//      span.tfYesNo.no , span.tfYesNo.yes

class TfTypeyesno extends TfTypeboolean {

	function htmlInput($override=array()) {
		if ($this->value) $class='yes';
		else $class='no';
		$override['onchange']="if (this.checked) if (this.value) \$('#$this->eid bundle').addClass('yes').removeClass('no'); else \$('#$this->eid bundle').addClass('no').removeClass('yes');".(@$override['onchange']);
		return "<div id='$this->eid bundle' class='tfYesNo $class'>"
				.'<label class=true ><input type=radio '.$this->intag(array_merge($override,array('checked'=>!!$this->value)))." class='icon-ok-sign yes' value=1>$this->yes</label>"
				.'<label class=false><input type=radio '.$this->intag(array_merge($override,array('checked'=>!$this->value)))." class='icon-remove-sign no' value=0>$this->no</label>"
			.'</div>';
	}

	function htmlView($override=array()) {
		if ($this->value) {
			return "<span class='tfYesNo yes'>" . $this->yes . "</span>";
		} else {
			return "<span class='tfYesNo no' >" . $this->no . "</span>";
		}
	}

}//class TfTypeyesno

/////////////////////////////////////
// Date - yy-mm-dd
// Using jQueryUI picker
// params:
//    okmin,okmax are limiting Years only
//    format: date() function format, used for view()/htmlView()
class TfTypedate extends TfType {
	var $format;

	function sqlType() {
		return "DATE";
	}

	function populate($fetch,&$table) {
		$this->isnum=false;
		parent::populate($fetch, $table);
		if (empty($this->params['format'])) {
			$this->format='Y-M-d';
		} else {
			$this->format=$this->params['format'];
		}
		$this->params['pattern']='^(19[0-9][0-9]|20[0-9][0-9])-(0[0-9]|1[0-2])-([0-2][0-9]|3[0-1])$';
		if ($this->fetch['okempty']) $this->params['pattern'].='|^$';
		if ($this->fetch['okmin']<1970) $this->fetch['okmin']=null;
		if ($this->fetch['okmax']<1970) $this->fetch['okmax']=null;
	}

	function view() {
		if (empty($this->value) || $this->value=='0000-00-00') return '';
		elseif ($this->value=='1970-01-01') return '-';
		else return date($this->format,strtotime($this->value));
	}

	function htmlInput($override=array()) {
		global $tf;
		$override['size']=10;
		return parent::htmlInput($override);
	}

	function htmlFormEnd() {
		$datepickeroptions='';
		if (!empty($this->fetch['okmin'])) $datepickeroptions.=",minDate:'".$this->fetch['okmin']."-01-01'";
		if (!empty($this->fetch['okmax'])) $datepickeroptions.=",maxDate:'".$this->fetch['okmax']."-12-31'";
		return "<script>$('.".get_called_class().".$this->fname input').datepicker({dateFormat:'yy-mm-dd',changeMonth: true,changeYear: true $datepickeroptions});</script>";
	}
}

/////////////////////////////////////
// Datetime - yy-mm-dd hh:mm:Ss
// Using jQueryUI picker
// params:
//    okmin,okmax in yy-mm-dd format
//    format: date() function format, used for view()/htmlView()
class TfTypedatetime extends TfType {
	var $format;

	function sqlType() {
		return "DATE";
	}

	function populate($fetch,&$table) {
		$this->isnum=false;
		parent::populate($fetch, $table);
		if (empty($this->params['format'])) {
			$this->format='Y-M-d';
		} else {
			$this->format=$this->params['format'];
		}
		$this->params['pattern']='^(19[0-9][0-9]|20[0-9][0-9])-(0[0-9]|1[0-2])-([0-2][0-9]|3[0-1])$';
		if ($this->fetch['okempty']) $this->params['pattern'].='|^$';
	}

	function view() {
		if (empty($this->value)) return '';
		else return date($this->format,strtotime($this->value));
	}

	function htmlInput($override=array()) {
		$override['size']=20;
		return parent::htmlInput($override);
	}
}

////////////////////////////////////////
// timer - count seconds and show it as h:m:s
// params: format - default 4
//          1 = show only second.     examples: 0         6         124       12345     12345678
//          2 = show minutes:seconds. examples: 0:00      0:06      2:04      205:45    20761:18
//          3 = show hours:min:sec.   examples: 0:00:00   0:00:06   0:02:04   3:25:45   346:01:18
//          4 = show days h:mm:ss.    examples: 0:00:00   0:00:06   0:02:04   3:25:34   14D 10:01:18
//       days - default 'D '. its the text to represent Days. see format=4.
class TfTypetimer extends TfTypenumber {

	function sqlType() {
		return "BIGINT";
	}

	function fix($value) {
		// replace all sorts of 'days' strings to 'D'
		$value = str_ireplace(array('Days', 'DD', 'Day'), 'D', $value);
		$days = $this->param('days');
		if ($days)
			$value = str_ireplace($days, 'D', $value);
		$value = str_ireplace(' ', '', $value);
		$value = trim($value);

		$v = 0; // init return value
		// start with parsing the days annoying format, so it would be easier later
		$daypos = strpos($value, 'D');
		if ($daypos > 0) {
			$v = 24 * 60 * 60 * substr($value, 0, $daypos);
			$value = substr($value, $daypos);
		}

		// ignore values with something else than : and numbers
		if (preg_match('/[^0-9:]/', $value)) {
			return null;
		} else {
			$v = 0;
			$multiplies = array(1, 60, 3600, 86400); // multiplications seconds,hours,minutes,days
			$matches = array();
			preg_match_all("/([0-9]+)/", $value, $matches, PREG_PATTERN_ORDER);
			$matches = $matches[1];
			for ($z = count($matches), $i = 0; $z; $z--, $i++) {
				if ($i > 4) {
					return null;
				} else {
					$v+=$multiplies[$i] * $matches[$z - 1];
				}
			}
		}
		return $v;
	}

	function validate($value) {
		// replace all sorts of 'days' strings to 'D'
		$value = str_ireplace(array('Days', 'Day', 'DD'), 'D', $value);
		$days = $this->param('days');
		if ($days)
			$value = str_ireplace($days, 'D', $value);
		$value = str_ireplace(' ', '', $value);
		$value = trim($value);
		// look for more than one occurance of days string
		if (strpos($value, 'D') != strrpos($value, 'D'))
			return $this->valerror("Not a valid timer value");
		// look for anything that is not :
		$value = str_replace('D', ':', $value);
		if (preg_match("/[^0-9:]/", $value))
			return $this->valerror("Not a valid timer value");
		return parent::validate($this->fix($value));
	}

	function view() {
		$format = 1 * $this->param('format');
		if ($format < 1 || $format > 4)
			$format = 4;
		// seconds
		if ($format == 1)
			return $this->value;
		$v = $this->value;
		// minutes
		if ($format == 2) {
			$m = floor($v / 60);
			$s = ($v % 60 < 10 ? '0' : '') . ($v % 60);
			return "$m:$s";
		}
		// hours
		if ($format == 3) {
			$h = floor($v / 3600);
			$v-=$h * 3600;
			$m = (floor($v / 60) < 10 ? '0' : '') . floor($v / 60);
			$s = ($v % 60 < 10 ? '0' : '') . ($v % 60);
			return "$h:$m:$s";
		}
		// days
		if ($format == 4) {
			$days = $this->param('days');
			if ($days == '')
				$days = 'D ';
			$d = floor($v / 86400);
			$v-=$d * 86400;
			$h = floor($v / 3600);
			$v-=$h * 3600;
			$m = (floor($v / 60) < 10 ? '0' : '') . floor($v / 60);
			$s = ($v % 60 < 10 ? '0' : '') . ($v % 60);
			if ($d) {
				return $d . $days . "$h:$m:$s";
			} else {
				return "$h:$m:$s";
			}
		}
		return "<error with timer class>";
	}

	function htmlInput($override=array()) {
		return parent::htmlInput($override);
		//TBD?
	}

}// class TfTypetimer

////////////////////////////////////////
// Year
// Fixes '30' to '1930', '29' to '2029', etc.
class TfTypeyear extends TfTypenumber {

	function sqlType() {
		return "YEAR(4)";
	}

	function fix($value) {
		if ($value < 1)
			return 0;
		if ($value < 30)
			return 2000 + $value;
		if ($value < 100)
			return 1900 + $value;
		return $value;
	}

	function jsOnChange() {
		return "this.value=fixyear(this.value);" . parent::jsOnChange();
	}

	function htmlInput($override=array()) {
		if ($this->param('size') == '')
			$this->param('size', 4);
		return parent::htmlInput($override);
	}
}

////////////////////////////////////////
// timestamp - last update time of current record
// ALWAYS read only
// value saved as timestamp, which is NOT date - so please do not inherit from date
class TfTypetimestamp extends TfType {
	var $format;

	function populate($fetch,&$table) {
		$this->isnum=false;
		parent::populate($fetch, $table);
		if (empty($this->params['format'])) {
			$this->format='Y-M-d H:i:s';
		} else {
			$this->format=$this->params['format'];
		}
		//$this->params['pattern']='^(19[0-9][0-9]|20[0-9][0-9])-(0[0-9]|1[0-2])-([0-2][0-9]|3[0-1])$';
		//if ($this->fetch['okempty']) $this->params['pattern'].='|^$';
	}

	function view() {
		if (empty($this->value)) return '';
		else return date($this->format,strtotime($this->value));
	}

	function sqlType() {
		return "TIMESTAMP DEFAULT NULL";
	}

	function validate($value) {
		return true;
	}

	function htmlInput($override=array()) {
		return $this->htmlView($override);
	}

	function htmlQuietInput($override=array()) {
		return '';
	}

}// class TfTypetimestamp

// lastupdate = alias to timestamp
class TfTypelastupdate extends TfTypetimestamp {
}// class TfTypelastupdate

////////////////////////////////////////
// statistical information counter
class TfTypestat extends TfTypenumber {

	function populate($fetch,&$table) {
		parent::populate($fetch,$table);
		$this->fetch['default'] = '0';
	}

	function sqlType() {
		return "BIGINT UNSIGNED";
	}

}// class TfTypestat

////////////////////////////////////////
// Order of appearance by importance - higher=higher, highest=first
// -32000 to 32000 OR okmin to okmax
class TfTypeorder extends TfTypenumber {

	function populate($fetch,&$table) {
		parent::populate($fetch, $table);
		if (empty($this->fetch['default']))
			$this->fetch['default'] = '0';
		if ($this->fetch['okmin']==$this->fetch['okmax']) {
			$this->fetch['okmin'] = -32000;
			$this->fetch['okmax'] = 32000;
		}
	}

	function sqlType() {
		return "SMALLINT";
	}

	function htmlInput($override=array()) {
		return parent::htmlInput($override)
			.'<div class=tfOrderBundle>'
				.'<i class="icon-circle-arrow-up"   onclick="document.getElementById(\''.$this->eid.'\').value='.$this->fetch['okmax'].';" title="'._('First').'"></i>'
				.'<i class="icon-minus-sign"        onclick="document.getElementById(\''.$this->eid.'\').value=0;" title="'._('Zero').'"></i>'
				.'<i class="icon-circle-arrow-down" onclick="document.getElementById(\''.$this->eid.'\').value='.$this->fetch['okmin'].';"  title="'._('Last').'"></i>'
				.'<i class="icon-remove-sign"       onclick="document.getElementById(\''.$this->eid.'\').value='.$this->value.';" title="'._('Reset').'"></i>'
			.'</div>';
	}
}

// class TfTypeorder
////////////////////////////////////////
// Stars rank,rate,rating
// okmax must be set. default is 5
// css styles:
//    .tfStars.tfStars0 , .tfStars.tfStars1 , .tfStars.tfStars2 , etc...
//    .tfStars i.icon-star , .tfStars i.icon-star-empty
class TfTypestars extends TfTypenumber {

	function populate($fetch,&$table) {
		parent::populate($fetch, $table);
		if (empty($this->fetch['okmax']))
			$this->fetch['okmax'] = 5;
	}

	function htmlInput($override=array()) {
		$imgid = 'img'.$this->eid;
		$max = $this->fetch['okmax'];
		$min = $this->fetch['okmin'];
		if ($max === null)
			$max = 5;
		if ($min === null || $min<0)
			$min = 0;

		$inp = "<div id=$imgid class='tfStars star".$this->value."'>";
		$click=!empty($override['readonly']);
		$onclick='';
		for ($i = 1; $i <= $max; $i++) {
			if ($click && $i>=$min) // not read only + min allowed rank
				if ($i==1 && $min<1) // allow zero stars
					$onclick = " onclick=\"if ($('#$this->eid').value==1) starset(document.getElementById('$this->eid'),0,'$imgid',$max); else starset('".$this->table->htmlform."','$this->fname',1,'$imgid',$max);" . $this->jsOnChange() . '" title="'.$i._(" Stars").'"';
				else
					$onclick = " onclick=\"starset('".$this->table->htmlform."','$this->fname',$i,'$imgid',$max);" . $this->jsOnChange() . '" title="'.$i._(" Stars").'"';
			$inp.='<i id='.$imgid.$i.' class="'.(i <= $this->value ? 'icon-star' : 'icon-star-empty').'" $onclick></i>';
		}
		$inp.='</div>';

		return $inp;
	}

	function htmlView($override=array()) {
		$override['readonly']=true;
		return htmlInput($override);
	}
}// class TfTypestars

////////////////////////////////////////
// eXternal Key = Foreign Key , an id that links to another table
//
// meta params:
//   xtable   - mandatory. The external table name which holds the data
//   xkey     - mandatory. The external table primary key to search. default to xtable->pkey
//   xname    - mandatory. The external table field name or sql-expression that will be used for display. example: CONCAT('[',`id`,'] ',first_name,' ',last_name)
//   xclass   - recommended. The external table field class. trying to default to original field type
//   xwhere   - optional. SQL WHERE clause to add to external table query, to limit the results (including GROUP BY and HAVING when necessary)
//   xorderby - optional. SQL ORDER BY clause
//   nothing  - optional. when okempty is on, you can select nothing. this is the text for nothing.
//   strict   - optional boolean. Default true. When true only allow values from external table. When false allows any value and only suggests from external table.
//   type     - select/radio/combo - method of input
//
// search: todo: clarify
//   using the xtable xname field class when possible, or string %LIKE%
class TfTypexkey extends TfType {

	var $xtable, $xname, $xkey, $xwhere, $xorderby, $xlimit, $xclass, $xtf, $xfetch;

	function sqlType() {
		return "MEDIUMINT UNSIGNED "; //DEFAULT 0";
	}

	function populate($fetch,&$table) {
		$this->isnum=false;
		parent::populate($fetch, $table);
		global $tf;

		// external table tname
		if (empty($this->params['xtable'])) {
				addToLog('<t>xtable</t> '._('value missing at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
		}else{
			$this->xtable = $this->params['xtable'];
			if (!sqlvalidate($this->xtable)) {
				addToLog('<t>xtable</t> '._('expression not balanced at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
				if (DEBUG) addToLog(he($this->xtable),LOGDEBUG,__LINE__,true);
				$this->xtable = '';
			}
		}

		// external key pkey id field name
		if (empty($this->params['xkey'])) {
			// no key defined - look for pkey from xtable
			if (!empty($this->xtable)) {
				$res = mysql_query("SELECT `fname` FROM " . sqlf($tf['tbl.info']) . " WHERE `tname`=" . sqlv($this->xtable) . " AND `class`='pkey'");
				if ($res)
					if ($row = mysql_fetch_row($res))
						$this->xkey = $row[0];
			}
			if (empty($this->xkey)) // still empty - bad news
				addToLog('<t>xkey</t> '._('value missing at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
			else // automatically filled - medium news
				addToLog('<t>xkey</t> '._('value missing at').' <f>'.$this->fetch['label'].'</f>',LOGSAME,__LINE__);
		} else {
			if (!sqlvalidate($this->params['xkey'])) {
				addToLog('<t>xkey</t> '._('expression not balanced at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
				if (DEBUG) addToLog(he($this->xkey),LOGDEBUG,__LINE__,true);
				$this->xkey = '';
			} else {
				if (strpos("`", $this->params['xkey']) === false)
					$this->xkey='`'.$this->params['xkey'].'`';
				else
					$this->xkey=$this->params['xkey'];
			}
		}

		// external display value field name, or expression such as "CONCAT(`firstname`,' ',`lastname`)"
		if (empty($this->params['xname'])) {
			addToLog('<t>xname</t> '._('value missing at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
		} else {
			$this->xname = str_replace('$xtable',sqlf($this->fname.'_xtable'),$this->params['xname']);
			if (!sqlvalidate($this->xname)) {
				addToLog('<t>xname</t> '._('expression not balanced at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
				if (DEBUG) addToLog(he($this->xname),LOGDEBUG,__LINE__,true);
				$this->xname = '';
			}
		}

		// external order by clause
		if (!empty($this->params['xorderby'])) {
			$this->xorderby=$this->params['xorderby'];
			if (strtolower($this->xorderby) == 'desc')
				$this->xorderby = "$this->xname DESC";
			elseif (strtolower($this->xorderby) == 'asc')
				$this->xorderby = "$this->xname ASC";
			elseif (!sqlvalidate($this->xorderby)) {
				addToLog('<t>xorderby</t> '._('expression not balanced at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
				if (DEBUG) addToLog(he($this->xorderby),LOGDEBUG,__LINE__,true);
				$this->xorderby = null;
			}
		}

		// external sql where clause
		if (!empty($this->params['xwhere'])) {
			if (!sqlvalidate($this->params['xwhere'])) {
				addToLog('<t>xwhere</t> '._('expression not balanced at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
				if (DEBUG) addToLog(he($this->params['xwhere']),LOGDEBUG,__LINE__,true);
				$this->xwhere = "'error with xwhere param at'='$this->fname'"; // same as WHERE FALSE
			} else {
				$this->xwhere=$this->params['xwhere'];
			}
		}

		// external query limit
		if (!empty($this->params['xlimit'])) {
			if (!preg_match('/^[0-9]+$/',$this->params['xlimit'])) {
				addToLog('<t>xlimit</t> '._('not a number at').' <f>'.$this->fetch['label'].'</f> (defaults to string)',LOGBAD,__LINE__);
				if (DEBUG) addToLog(he($this->params['xlimit']),LOGDEBUG,__LINE__,true);
			} else {
				$this->xlimit=1*$this->params['xlimit'];
			}
		}

		// external tftype class
		if (empty($this->params['xclass'])) {
			if (sqlname($this->xname)) $this->xfetch=TftFetch($this->xtable,$this->xname);
			if (!empty($this->xfetch) && is_array($this->xfetch)) {
				$this->xclass=$this->xfetch['class'];
				addToLog('<t>xclass</t> '._('value missing at').' <f>'.$this->fetch['label'].'</f>. Detected <t>'.$this->xclass.'</t>',LOGSAME,__LINE__);
			} else {
				$this->xclass='string';
				addToLog('<t>xclass</t> '._('value missing at').' <f>'.$this->fetch['label'].'</f>. Unable to detect - using string.',LOGBAD,__LINE__);
			}
		} else {
			$this->xclass = $this->params['xclass'];
			if (!preg_match('/^[a-zA-Z0-9_]+$/',$this->xclass)) {
				addToLog('<t>xclass</t> '._('expression not balanced at').' <f>'.$this->fetch['label'].'</f> (defaults to string)',LOGBAD,__LINE__);
				if (DEBUG) addToLog(he($this->xclass),LOGDEBUG,__LINE__,true);
				$this->xclass = 'string';
			}
		}
		// set a new external tftype class
		$this->xclass='TfType'.preg_replace('/^TfType/','',$this->xclass);
		if (class_exists($this->xclass)) {
			$this->xtf = new $this->xclass();
		} else {
			addToLog("<t>$this->xclass</t> "._('Bad class at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
			$this->xtf = new TfTypestring();
		}

		if (empty($this->params['xtype']))
			if ($this->paramtrue('strict'))
				$this->param('type','select');
			else $this->param('type','combo');

	}//function populate

	function validate($value) {
		global $tf;
		if (($value === '') && (!$this->fetch['okempty']))
			return $this->valerror(_('Cannot be left empty'));
		if (($value === null) && (!$this->fetch['oknull']))
			return $this->valerror(_('Cannot be null'));
		$value = $this->fix($value);
		if ($value !== '' && $value !== null) {
			// make sure the key exists in the xtable
			$res = sqlRun('SELECT COUNT(*) FROM ' . sqlf($this->xtable) . ' WHERE ' . sqlf($this->xkey) . '=' . sqlv($value));
			if (!$res) {
				addToLog(_('Error reading data at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
				if (DEBUG) addToLog('<sqlerr>'.sqlError().'</sqlerr> <sql>'.sqlLastQuery().'</sql>',LOGDEBUG,__LINE__,true);
				return $this->valerror(_('Error reading xtable'));
			}
			$row = mysql_fetch_row($res);
			if ($row[0] == 0)
				return $this->valerror('XKey not found in xtable'.(DEBUG? " $this->xtable.$this->xkey":''));
		}
		return true;
	}

	function sqlList() {
		// Prepare vars.
		// Assume all validations took place at populate()
		// .. as well as removing unnecessary 'ORDER BY' or 'WHERE'
		if (empty($this->xkey) || empty($this->xtable) || empty($this->xname)) {
			addToLog('<t>xkey/xtable/xname</t> '._('value missing at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
			return false;
		}
		// build the query
		$sql='SELECT DISTINCT ';
		if (sqlname($this->xkey)) $sql.= sqlf($this->xkey);
		else $sql.= $this->xkey;
		if (sqlname($this->xname)) $sql.=','.sqlf($this->xname);
		else $sql.=','.str_replace('$xtable',sqlf($this->fname.'_xtable'),$this->xname);
		if (sqlname($this->xtable)) $sql.=' FROM '.sqlf($this->xtable).' AS '.sqlf($this->fname.'_xtable');
		else $sql.=' FROM '.$this->xtable.' AS '.sqlf($this->fname.'_xtable');
		if (!empty($this->xwhere)) {
			$sql.=' WHERE '.$this->xwhere;
		}
		if (!empty($this->xorderby)) {
			$sql.=' ORDER BY '.$this->xorderby;
		}
		if (!empty($this->xlimit)) {
			$sql.=' LIMIT '.$this->xlimit;
		}
		return $sql;
	}

	function htmlInputRadio($override) {
		$res = sqlRun($this->sqlList());
		if (!$res) {
			addToLog(_('Error reading data at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
			if (DEBUG) addToLog('<sqlerr>'.sqlError().'</sqlerr> <sql>'.sqlLastQuery().'</sql>',LOGDEBUG,__LINE__,true);
			return false;
		}
		$inp='';
		while ($row = mysql_fetch_row($res)) {
			$inp.="<label><input type=radio value=\"$row[0]\" ".$this->intag($override).($this->value==$row[0]?' checked ':'').">$row[1]</label>";
		}
		return '<div class='.get_called_class().'>'.$inp.'</div>';
	}

	function htmlInput($override=array()) {
		$res = sqlRun($this->sqlList());
		if (!$res) {
			addToLog(_('Error reading data at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
			if (DEBUG) addToLog('<sqlerr>'.sqlError().'</sqlerr> <sql>'.sqlLastQuery().'</sql>',LOGDEBUG,__LINE__,true);
			return false;
		}
		$inp='';
		$empty=false;
		while ($row = mysql_fetch_row($res)) {
			$inp.='<OPTION value="'.fix4html2($row[0]).'"'.($this->value==$row[0]?' selected':'').'>'.fix4html3($row[1]);
			if ($row[0]==='') $empty=true;
		}
		if ($this->fetch['okempty'] && !$empty)
			if (empty($this->params['nothing']))
				$inp='<OPTION value="">'._('nothing').$inp;
			else
				$inp='<OPTION value="">'.$this->param('nothing').$inp;
		return '<SELECT '.$this->intag($override).">$inp</SELECT>";
	}

	function sqlView($id = null) {
		if ($id === null)
			$id = $this->value;

		if ($id===null) // still?
			return "SELECT 0,''";

		// Prepare vars.
		// Assume all validations took place at populate()
		// .. as well as removing unnecessary 'ORDER BY' or 'WHERE'
		if (empty($this->xkey) || empty($this->xtable) || empty($this->xname)) {
			addToLog('<t>xkey/xtable/xname</t> '._('value missing at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
			return false;
		}
		// build the query
		$sql='SELECT ';
		if (sqlname($this->xkey)) $sql.= sqlf($this->xkey);
		else $sql.= $this->xkey;
		if (sqlname($this->xname)) $sql.=','.sqlf($this->xname);
		else $sql.=','.str_replace('$xtable',sqlf($this->fname.'_xtable'),$this->xname);
		if (sqlname($this->xtable)) $sql.=' FROM '.sqlf($this->xtable);
		else $sql.=' FROM '.$this->xtable;
		$sql.=' WHERE ';
		if (sqlname($this->xkey)) $sql.= sqlf($this->xkey);
		else $sql.= $this->xkey;
		$sql.='='.sqlv($id);
		return $sql;
	}

	function htmlView($override=array()) {
		$override['title']=$this->value;
		if (isset($this->table->row) && isset($this->table->row[$this->fname.'_xdisplayname']))
			return '<span '.$this->intag($override).'>'.$this->table->row[$this->fname.'_xdisplayname'].'</span>';;
		return '['.$this->value.']';
	}

	function view() {
		if (isset($this->table->row) && isset($this->table->row[$this->fname.'_xdisplayname']))
			return $this->table->row[$this->fname.'_xdisplayname'];
		return $this->value;

		/* very wasteful method - requires an sql query for each and every view. instead - use join.
		$res = sqlRun($this->sqlView($this->value));
		if (!$res) {
			addToLog(_('Error reading data at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
			if (DEBUG) addToLog($this->sqlView($this->value),LOGBAD,__LINE__,true);
			if (DEBUG) addToLog('<sqlerr>'.sqlError().'</sqlerr> <sql>'.sqlLastQuery().'</sql>',LOGBAD,__LINE__,true);
			return null;
		}
		$row = mysql_fetch_row($res);
		if ($row) {
			return $row[1];
		} else {
			return null;
		}*/
	}

	function to_select_select() {
		if (empty($this->xname)) return null;
		if (sqlname($this->xname))
			return sqlf($this->fname.'_xtable').'.'.sqlf($this->xname).' as '.sqlf($this->fname.'_xdisplayname');
		else
			return str_replace('$xtable',sqlf($this->fname.'_xtable'),$this->xname).' as '.sqlf($this->fname.'_xdisplayname');

	}

	function to_select_from() {
		if (empty($this->xname)) return null;
		//return '(seLECT '.sqlf($this->xtable).'.'.sqlf($this->fname).' frOM '.sqlf($this->xtable).' whERE '.sqlf($this->tname).'.'.sqlf($this->fname).'='.sqlf($this->fname.'_xtable').'.'.sqlf($this->xkey).') as '.sqlf($this->fname.'_xtable');
		return ' inner join '.sqlf($this->xtable).' as '.sqlf($this->fname.'_xtable').' on ('.sqlf($this->tname).'.'.sqlf($this->fname).'='.sqlf($this->fname.'_xtable').'.'.sqlf($this->xkey).')';
	}

	function to_select_where($method,$query,$not=false) {
		if (is_numeric($query) && $method!='has' && $method!='in' && $method!='a' && $method!='z' && $method!='ci' && $method!='rx')
			return parent::to_select_where($method,$query,$not); // search by id
		// otherwise search by display name
		$fname=$this->fname;
		$this->fname=$fname.'_xdisplayname';
		$tname=$this->tname;
		$this->tname='';
		$return = parent::to_select_where($method,$query,$not);
		$return = "HAVING $return";
		$this->tname=$tname;
		$this->fname=$fname;
		return $return;
	}

	function to_select_orderby($direction) {
		$fname=$this->fname;
		$this->fname=$fname.'_xdisplayname';
		$tname=$this->tname;
		$this->tname='';
		$return = parent::to_select_orderby($direction);
		$this->tname=$tname;
		$this->fname=$fname;
		return $return;
	}

}// class TfTypexkey


////////////////////////////////////////
// eXternal Key from a single big Lists table
//
// For example let's say I have 1 big table for all available values lists.
// The external table (a table of lists) has these fields: id, tname, fname, value, order
// The TfTypexlist will default the xwhere parameter to ('Current_Table_Name' LIKE `tname` AND 'Current_Field_Name' LIKE `fname`)
// The use of LIKE allows wildcard lists that fits several fields.
// For example if you set the `tname`,`fname` on the external table to '%','question%'
// This will allow using the list for any field named question* in any table.
//
// meta params:
//    See TfTypexkey! Note the changed defaults!
//   xtable  - mandatory.
//   xname   - recommended. Defaults to `value` or `name` or `display` if they exist on the external table.
//   xkey    - recommended. Defaults to external table pkey (as in TfTypexkey)
//   xwhere  - SQL WHERE clause for fetching available keys. Defaults to ('$this->tname' LIKE `tname` AND '$this->fname' LIKE `fname`)
//   orderby - SQL ORDER BY clause. Defaults to (`order` DESC) or (`sort` DESC) or (`weight` ASC) if they exist on the external lists table.
class TfTypexlist extends TfTypexkey {

	function populate($fetch,&$table) {
		parent::populate($fetch, $table);

		if (empty($this->xwhere)) {
			if (TftFetch($this->xtable,'tname') && TftFetch($this->xtable,'fname')) {
				$this->xwhere="('".$this->tname."' LIKE `tname` AND '".$this->fname."' LIKE `fname`)";
			}
			addToLog('<t>xwhere</t> '._('value missing at').' <f>'.$this->fetch['label'].'</f>',empty($this->xwhere)?LOGBAD:LOGSAME,__LINE__);
		}

		if (empty($this->xname)) {
			    if ($fetch=TftFetch($this->xtable,'value')) $this->xname='value';
			elseif ($fetch=TftFetch($this->xtable,'name')) $this->xname='name';
			elseif ($fetch=TftFetch($this->xtable,'display')) $this->xname='display';
			addToLog('<t>xname</t> '._('value missing at').' <f>'.$this->fetch['label'].'</f>',empty($this->xname)?LOGBAD:LOGSAME,__LINE__);
		}

		if ($this->xorderby=='') {
			    if ($fetch=TftFetch($this->xtable,'order')) $this->xorderby='`order` DESC';
			elseif ($fetch=TftFetch($this->xtable,'sort')) $this->xorderby='`sort` DESC';
			elseif ($fetch=TftFetch($this->xtable,'weight')) $this->xorderby='`weight` ASC';
		}

	}

}// class TfTypexlist

////////////////////////////////////////
// List of boolean values from an outside list - multiple select box/checkboxes
// comma separated (must be comma for various reasons)
// See TfTypexkey
// additinal params:
//    pre,post - optional. When displaying values surround each value with $pre.$displayvalue.$post. i.e pre='<i class=v>' post='</i>'
//    type - select/checkbox/tagmanager/manifest. How should the html input work.
//
// css classes:
//   tfCheckboxes , tfCheckbox.false , tfCheckbox.true , tfCheckbox input
//
class TfTypexkeys extends TfTypexkey {

	function sqlType() {
		return "VARCHAR(1024)";
	}

	function set($value) {
		$this->value = $this->fix($value);
	}

	// id exists
	function isOn($id) {
		return strpos(",$this->value,", ",$id,") !== false;
	}

	function view() {
		$xkeys = explode(',', $this->value);
		$pre=$this->params['pre'];
		$post=$this->params['post'];
		$inp = '';
		foreach ($xkeys as $id) {
			if ($id !== '') {
				if (sqlname($this->xname)) $this->xname=sqlf($this->xname);
				$res = sqlRun('SeLeCT '.$this->xname.' FROM '.sqlf($this->xtable).' WHERE '.sqlf($this->xkey).'='.sqlv($id));
				if ($res) {
					if ($row=mysql_fetch_row($res)) {
						if ($this->xtf === false) {
							$inp.=$pre.$row[0].$post;
						} else {
							$this->xtf->set($row[0]);
							$inp.=$pre.$this->xtf->view().$post;
						}
					}else{
						addToLog("<t>$this->xkey=$id</t> "._('XKey not found at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
					}
				} else {
					addToLog(_('Error reading data at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
					if (DEBUG) addToLog('<sqlerr>'.sqlError().'</sqlerr> <sql>'.sqlLastQuery().'</sql>',LOGDEBUG,__LINE__,true);
				}
			}
		}
		return $inp;
	}

	function htmlView($override=array()) {
		$xkeys = explode(',', $this->value);
		$pre=$this->param('pre');
		$post=$this->param('post');
		$inp='<span '.$this->intag($override).'>';
		foreach ($xkeys as $id) {
			if ($id !== '') {
				if (sqlname($this->xname)) $this->xname=sqlf($this->xname);
				$res = sqlRun("SeLeCT $this->xname FROM ".sqlf($this->xtable).' WHERE '.sqlf($this->xkey).'='.sqlv($id));
				if ($res) {
					if ($row=mysql_fetch_row($res)) {
						if ($this->xtf === false) {
							$inp.=$pre.$row[0].$post;
						} else {
							$this->xtf->set($row[0]);
							$inp.=$pre.$this->xtf->htmlView($override).$post;
						}
					}else{
						addToLog("<t>$this->xkey=$id</t> "._('XKey not found at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
					}
				} else {
					addToLog(_('Error reading data at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
					if (DEBUG) addToLog('<sqlerr>'.sqlError().'</sqlerr> <sql>'.sqlLastQuery().'</sql>',LOGDEBUG,__LINE__,true);
				}
			}
		}
		$inp.='</span>';
		return $inp;
	}

	function htmlInput($override=array()) {
		if (empty($this->params['type']) || $this->params['type']=='txt' || $this->params['type']=='text')
			return $this->htmlInputTxt($override);
		if ($this->params['type']=='select' || $this->params['type']=='chosen')
			return $this->htmlInputSelect($override);
		if ($this->params['type']=='radio')
			return $this->htmlInputRadio($override);
		if ($this->params['type']=='cb' || $this->params['type']=='checkbox' || $this->params['type']=='checkboxes' || $this->params['type']=='chkbox' || $this->params['type']=='chkboxes')
			return $this->htmlInputCheckbox($override);

		addToLog('<t>type</t> '._('value is wrong at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
		if (DEBUG) addToLog(he($type),LOGDEBUG,__LINE__,true);
		return $this->htmlInputTxt($override);
	}

	// used for debug and fall back
	function htmlInputTxt($override) {
		return '<input '.$this->intag($override).' value="'.fix4html2($this->value).'" />';
	}

	// multiple select box
	function htmlInputSelect($override) {
		$override['multiple']='multiple';
		return parent::htmlInput($override);
	}

	// list of checkboxes - suitable for short lists
	function htmlInputCheckbox($override) {
		global $tf;
		// real input
		if (DEBUG) $inp = $this->htmlInputTxt($override); // show real input to user
		else $inp = $this->htmlQuietInput($override); // hide real input from user

		$res = sqlRun($this->sqlList());

		$inp.='<div class="'.get_class().' tfCheckboxes">';

		$group = false;
		while ($row = mysql_fetch_row($res)) {
			// divide to sub groups sub sections fieldsets
			if (isset($row[2]) && $row[2] != $group) {
				if ($group !== false) $inp.="</fieldset>";
				$group = $row[2];
				$inp.='<fieldset title="'.fix4html2($group).'">';
				if (!is_numeric($group))
					$inp.='<legend>'.fix4html3($group).'</legend>';
			}
			$on = $this->isOn($row[0]);

			// note: specific set/unset of a key is not used in order to keep the keys in same order
			$inp.='<label class="tfCheckbox '.($on?'true':'false').'" title="'.fix4html2($row[0])."\" id='$this->eid box ".fix4html1($row[0])."'><input ".$this->intag(array('name',"$this->eid box",'onchange'=>"tfxsCB('$this->eid','$this->eid box')",'checked'=>$on,'class'=>'remove_me'))." value='".fix4html1($row[0])."' type=CHECKBOX />$row[1]</label>";
		}
		if ($group !== false) $inp.="</fieldset>";
		$inp.='</div>';
		return $inp;
	}//htmlInputCheckbox


	function to_select_select() {
		return '';
	}

	function to_select_from() {
		return '';
	}

	function to_select_where($method,$query,$not=false) {
		return '';
	}

} // class TfTypexkeys


////////////////////////////////////////
// enter a phone number
class TfTypephone extends TfTypestring {

	function intag($override=array()) {
		$override['onchange']='this.value=fixphone(this.value);'.(@$override['onchange']);
		return parent::intgag($override);
	}

} // class TfTypephone


////////////////////////////////////////
// Associative array of values in url query format
// meta params:
//      mandatory - CSV (or simple array) of parameters that must be included in the parameters list when editing.
//      X-class   - Optional. The TfType of the parameter X. default string. (currently not implemented)
//      X-params  - Optional. The 'params' value for the meta parameters, but in associative array format (not in meta table). (currently not implemented)
class TfTypequery_need_rewrite extends TfTypestring {
	var $fields=array(); // An associative array representing current fields with their values and other parameters

	function sqlType() {
		return 'VARCHAR(1000)';
	}

	function populate_values() {
		$this->fields=paramsfromstring($this->value);
		if (!empty($this->params['mandatory'])) {
			if (!is_array($this->params['mandatory']))
				$this->params['mandatory']=explode(',',$this->params['mandatory']);
			foreach ($this->params['mandatory'] as $v)
				if (!array_key_exists($v,$this->fields))
					$this->fields[$v]=null;
		}
		foreach ($this->fields as $k=>$v) {
			if (isset($this->params["$k-class"]))
				$class='TfType'.$this->params["$k-class"];
			else
				$class='TfTypestring';
			$fetch=array('fname'=>$k,'tname'=>$this->tname.'.'.$this->fname);
			if (isset($this->params["$k-params"]))
				$fetch['params']=paramsfromstring($this->params["$k-params"]);
			if (class_exists($class)) {
				$f=new $class();
			} else {
				addToLog("<t>$class</t> "._('Bad class at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
				$f=new TfTypestring();
			}
			$f->populate($fetch,$this->table);
			$f->value=$this->fields[$k];
			$this->fields[$k]=$f;
		}//foreach fields
	}

	function populate($fetch,&$table) {
		parent::populate($fetch, $table);
		$this->populate_values();
	}

	// Return false if current value is considered an empty or unset value; otherwise return true
	function notempty($value=0) {
		if (func_num_args()==0) $value = $this->value;
		if (!parent::nonempty($value)) return false;
		if (str_replace(array('&','=',' '),'',$value)=='') return false;
		return true;
	}

	// Return true for equal values, false for different values.
	// $value2 is optional. If omitted, current field value will be used.
	function equal($value1, $value2=0) {
		if (func_num_args()==1) $value2 = $this->value;
		if (parent::equal($value1,$value2)) return true;
		if (!is_array($value1)) $value1=paramsfromstring($value1);
		if (!is_array($value2)) $value2=paramsfromstring($value2);
		if (count($value1)!=count($value2)) return false;
		if (count(array_diff_assoc($value1, $value2))) return false;
		return true;
	}

	function diff($value1, $value2=0) {
		if (func_num_args()==1) $value2 = $this->value;
		if (!is_array($value1)) $value1=paramsfromstring($value1);
		if (!is_array($value2)) $value2=paramsfromstring($value2);
		return parent::diff(count($value1),count($value2));
	}

	// Validate a potential value for this field.
	// Return true/false. When false, validation error info is at $this->error
	function validate($value) {
		$value = $this->fix($value);
		if (is_array($value)) {
			if (count($value)==0 && !$this->fetch['okempty'])
				return $this->valerror(_('Null not allowed'));
			return true;
		}
		if (!$this->fetch['oknull'] && ( ($value === null)))
			return $this->valerror(_('Null not allowed'));
		if ($value == '' && (!$this->fetch['okempty']) && (!$this->fetch['oknull']))
			return $this->valerror(_('Cannot be left empty'));

		$this->error = '';
		return true;
	}

	// Return sql order by clause (without the 'ORDER BY' itself) $direction=DESC/ASC or empty
	function orderby($direction) {
		return '';
	}

	// Fix the value before update
	function fix($value) {
		return $value;
	}

	// Set a value - using the fix($value) method
	function set($value) {
		$value = $this->fix($value);
		if ($this->validate($value)) {
			if ($value==null) {
				$this->value=null;
				$this->array=null;
			} elseif (is_array($value)) {
				$this->array=$value;
				$this->value=stringfromparams($value);
			} else {
				$this->value=$value;
				parse_str($value,$this->array);
			}
		}
		return $this->value;
	}

	// Prepare the field for deletion from table. Usually applicaple only to file fields.
	function del() {
		$this->array=null;
		return true;
	}

	// Display value to view current field (not html) (xkey will be translated)
	function view() {
		////////// TODO - rewrite this whole function //////////////
		$head=$this->param('head');
		$pre=$this->param('pre');
		$equal=$this->param('equal') || '=';
		$post=$this->param('post');
		if ($post===null) $post=', ';
		$tail=$this->param('tail');
		$inp="$head";
		$tbl=new TfTable();
		foreach ($this->array as $k=>$v) {
			if ($this->param("$k-class")) {
				$class="TfType".$this->param("$k-class");
				if (class_exists($class)) {
					$f=new $class();
				} else {
					addToLog("<t>$class</t> "._('Bad class at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
					$f=new TfTypestring();
				}
				$fetch=array('tname'=>$this->tname.'_'.$this->fname,'fname'=>$k,'label'=>$k);
				$params=array();
				foreach ($this->params as $pk=>$pv) {
					if (strpos($pk,$k)===0) {
						$params[substr($pk,strlen($k))]=$pv;
					}
				}
				$f->params=$params;
				$f->populate($fetch,$tbl);
				$f->populate_intag();
				$f->set($v);
				$view=$f->view();
				unset($f);
				unset($fetch);
				unset($params);
			} else {
				$view=$v;
			}
			$inp.=$pre.$k.$equal.$view.$post;
		}
		$inp.=$tail;
		return $inp;
	}

	// Return html string for displaying this field.
	// $params is a string to add in the <input> tag, if any.
	// This method may use $this->table->htmlform (<form> name) and $this->fname (form element name, <input> or another)
	function htmlView($override=array()) {
		////////// TODO - rewrite this whole function //////////////
		$head=$this->param('htmlhead');
		$pre=$this->param('htmlpre');
		$equal=$this->param('htmlequal');
		$post=$this->param('htmlpost');
		$tail=$this->param('htmltail');

		if ($pre===null && $equal===null && $post===null) {
			$head.="<table class=tfparams>";
			$pre='<tr><th>';
			$equal='</th><td>';
			$post='</td></tr>\n';
			$tail='</tr></table>'.$tail;
		}

		$tbl=new TfTable();
		$inp=$head;
		if (!empty($this->array)) {
			foreach ($this->array as $k=>$v) {
				if ($this->param("$k-class")) {
					$class="TfType".$this->param("$k-class");
					if (class_exists($class)) {
						$f=new $class();
					} else {
						addToLog("<t>$class</t> "._('Bad class at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
						$f=new TfTypestring();
					}
					$fetch=array('tname'=>$this->tname.'_'.$this->fname,'fname'=>$k,'label'=>$k);
					$params=array();
					foreach ($this->params as $pk=>$pv) {
						if (strpos($pk,$k)===0) {
							$params[substr($pk,strlen($k))]=$pv;
						}
					}
					$fetch['params']=stringfromparams($params);
					$f->populate($fetch,$tbl);
					$f->populate_intag();
					$f->set($v);
					$view=$f->htmlView();
					unset($f);
					unset($fetch);
					unset($params);
				} else {
					$view=$v;
				}
				$inp.=$pre.$k.$equal.$view.$post;
			}
		}
		$inp.=$tail;
		return $inp;
	}

	// Return html string for inputing this field
	// This method may use $this->table->htmlform and $this->fname
	function htmlInput($override=array()) {
		global $tf;
		$mandatory=explode(',',$this->param('mandatory'));
		if ($this->array==null)
			$this->array=array();
		foreach($mandatory as $k) {
			if (!array_key_exists($k, $this->array))
				$this->array[$k]='';
		}
		$this->set($this->array); // update $this->value

		$head=$this->param('htmlhead');
		$pre=$this->param('htmlpre');
		$equal=$this->param('htmlequal');
		$post=$this->param('htmlpost');
		$tail=$this->param('htmltail');

		if ($pre===null && $equal===null && $post===null) {
			$head.="<table class=tfparams>";
			$pre='<tr><th>';
			$equal='</th><td>';
			$post='</td></tr>\n';
			$tail='</tr></table>'.$tail;
		}

		$tbl=new TfTable();

		$inp=$head;
		foreach ($this->array as $k=>$v) {
			if ($k!='') {
				if ($this->param("$k-class")) {
					$class="TfType".$this->param("$k-class");
				} else {
					$class="TfTypestring";
				}
				if (class_exists($class)) {
					$f=new $class();
				} else {
					addToLog("<t>$class</t> "._('Bad class at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
					$f=new TfTypestring();
				}
				$fetch=array('tname'=>$this->tname.'_'.$this->fname,'fname'=>$k,'label'=>$k);
				$params=array();
				foreach ($this->params as $pk=>$pv) {
					if (strpos($pk,$k)===0) {
						$params[substr($pk,strlen($k))]=$pv;
					}
				}
				$fetch['params']=stringfromparams($params);
				$f->populate($fetch,$tbl);
				$f->populate_intag();
				$f->set($v);
				$view=$f->htmlInput("onchange=\"updParamsElement('$k',this.value,'$this->eid')\"")
					.'<i class=icon-trash title="'.fix4html2(_('Delete this row')).'" '
					.    "onclick=\"if (confirm('".fix4js2(_('Delete this row?'))."')) {updParamsElement('$k',null,'$this->eid');}\"></i>";
				unset($f);
				unset($fetch);
				unset($params);
				$inp.=$pre.$k.$equal.$view.$post;
			}
		}
		$inp.=$tail;
		$inp.="<div class=tfparamsadded id='$this->eid added'></div>
			<div class=tfparamsadd>"
			   ." <input size=4 onchange=\"this.value=this.value.replace(/[^a-zA-Z0-9-_]/g,'_')\" id='$this->eid newname'>"
			   ." <span style='cursor:pointer' onclick=\"if(document.getElementById('$this->eid newname').value.replace('_','')!=''){ updParamsElement(document.getElementById('$this->eid newname').value,'','$this->eid'); alert(\""._('Done. Save changes to edit the new parameter.')."\");}\">"._('Add param')."</span>";

		return $inp.'<input value="'.fix4html2($this->value).'" '.$this->intag(array_merge($override,array('type'=>(DEBUG? '':'hidden')))).' />';
	}//htmlInput

} // class TfTypeparameters

////////////////////////////////////////
// TF field class selection
class TfTypeTFclass extends TfType
{
	var $list=array();

	function sqltype() {
		return 'varchar(60)';
	}

	function populate($fetch,&$table) {
		$this->isnum=false;
		parent::populate($fetch,$table);
		$ar=get_declared_classes();
		foreach ($ar as $v) {
			if (strpos($v,'TfType')===0)
				$this->list[]=substr($v,6);
		}
		$this->param('okempty',true);
	}

	function validate($val) {
		$ok=parent::validate($val);
		if ($ok && in_array($val,$this->list))
			return true;
		else
			return $ok;
	}

	function htmlInput($override=array()) {
		$inp='<SELECT '.$this->intag($override).'>';
		//if ($this->fetch['okempty'] || $this->fetch['oknull']) $inp.='<option>'; // --> empty is ALWAYS an option, because it is simply TfType
		foreach ($this->list as $v) {
			$inp.='<option'.($v==$this->value?' selected':'').">$v";
		}
		$inp.='</SELECT>';
		return $inp;
	}

} // class TfTypeTFclass


/////////////////////////////////////////////
// For calculated values
// meta params:
//     q = SQL SELECT query that returns a single value
//     Use the following vars:
//        $tname, $pkey, $curid, $rowc and any $$fieldname (real ones, not calculated) from the table
// examples for table orders and items:
//    items --- q=SELECT COUNT(*) FROM items WHERE order_id=$curid
//    sum   --- q=SELECT SUM(price) FROM items WHERE order_id=$curid
//	  debt  --- q=((SELECT SUM(price)-$$paid FROM items WHERE order_id=$curid))

class TfTypecalculated extends TfTypefictive {

	var $lastrowc; // last cached at this row in table
	var $sql; // cache the query

	function populate($fetch,&$table) {
		parent::populate($fetch,$table);
		if (empty($this->params['q']))
			addToLog('<t>q</t> '._('Value is missing at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
		else
			$this->q=$this->params['q'];
	}

	function get_query() {
		$search=array('$tname','$pkey','$curid','$rowc');
		if ($this->table && is_array($this->table->row)) {
			$replace=array(sqlf($this->tname),sqlf($this->table->pkey),$this->table->curid,$this->table->rowc);
			foreach ($this->table->row as $k=>$v) {
				$search[]='$$'.$k;
				$replace[]=is_numeric($v)?$v:sqlv($v);
			}
		} else { // no preset data, try to get it on the fly in query
			$replace=array(sqlf($this->tname),sqlf($this->tname).'.'.sqlf($this->table->pkey),sqlf($this->tname).'.'.sqlf($this->table->pkey),0);
			foreach ($this->table->fields as $k=>$v) {
				$search[]='$$'.$k;
				$replace[]=sqlf($this->tname).'.'.sqlf($k);
			}
		}

		$q=str_replace($search,$replace,$this->params['q']);
		if (!preg_match('/^[\\s\\(]*SELECT /i',$q)) {
			addToLog(_('Only SELECT queries allowed at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
			if (DEBUG) addToLog('<sql>'.he($q).'</sql>',LOGBAD,__LINE__,true);
			return false;
		} else {
			return $q;
		}
	}

	function view() {
		// cache value and query
		if ($this->lastrowc!==$this->table->rowc) {
			$this->lastrowc=$this->table->rowc;
			$this->q=$this->get_query();
			if ($this->q)
				if ($res=mysql_query($this->q))
					if ($row=mysql_fetch_row($res))
						if (array_key_exists(0,$row))
							return $this->value=$row[0];

			addToLog(_('Error with calculated query at').' <f>'.$this->fetch['label'].'</f>',LOGBAD,__LINE__);
			if (DEBUG) addToLog('<sqlerr>'.mysql_error().'</sqlerr> <sql>'.he($this->q).'</sql>',LOGBAD,__LINE__,true);
			return _('QUERY ERROR');
		} else {
			return $this->value;
		}
	}

	function to_select_orderby($direction) {
		// query is cached?
		if (empty($this->table) || empty($this->table->rowc) || $this->lastrowc!==$this->table->rowc)
			$this->q=$this->get_query();
		return "($this->q) $direction";
	}

	function to_select_where($method,$query,$not=false) {
		// query is cached?
		if (empty($this->table) || empty($this->table->rowc) || $this->lastrowc!==$this->table->rowc)
			$this->q=$this->get_query();
		if (empty($this->q)) return '';

		$where=($not?'NOT ':'')."($this->q)";
			if ($method=='has') $where.=" LIKE '".'%'.mysql_real_escape_string(str_replace(array("\\", '_', '%'), array("\\\\", "\\_", "\\%"), $query)).'%'."'";
		elseif ($method=='a' )  $where.=" LIKE '".mysql_real_escape_string(str_replace(array("\\", '_', '%'), array("\\\\", "\\_", "\\%"), $query)).'%'."'";
		elseif ($method=='z' )  $where.=" LIKE '".'%'.mysql_real_escape_string(str_replace(array("\\", '_', '%'), array("\\\\", "\\_", "\\%"), $query))."'";
		elseif ($method=='ci')  $where.=" LIKE '".mysql_real_escape_string(str_replace(array("\\", '_', '%'), array("\\\\", "\\_", "\\%"), $query))."'";
		elseif ($method=='rx')  $where.=" RLIKE '".mysql_real_escape_string($query)."'";
		elseif ($method=='eq')  $where.='='      .(is_long($query)?$query:"'".mysql_real_escape_string($query)."'");
		elseif ($method=='lt')  $where.='<'      .(is_long($query)?$query:"'".mysql_real_escape_string($query)."'");
		elseif ($method=='gt')  $where.='>'      .(is_long($query)?$query:"'".mysql_real_escape_string($query)."'");
		elseif ($method=='lte') $where.='<='     .(is_long($query)?$query:"'".mysql_real_escape_string($query)."'");
		elseif ($method=='gte') $where.='>='     .(is_long($query)?$query:"'".mysql_real_escape_string($query)."'");
		elseif ($method=='n')   $where.=' IS NULL';
		elseif ($method=='b')   $where.=''; // boolean - leave as is
		elseif ($method=='e')   $where.="='' "; // empty
		elseif ($method=='in')  $where=($not?'NOT ':'')." CONCAT('%',($q),'%') LIKE '".mysql_real_escape_string(str_replace(array("\\", '_', '%'), array("\\\\", "\\_", "\\%"), $query))."'";
		else return false; // unknown method
		return $where;
	}

}// class TfTypecalculated

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////                       THE TABLE CLASS TfTable                                   ///////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////
// the base TfTable class
class TfTable {

	var $tname;		// table name
	var $fetch = array();  // array of all data fetch_assoc from the tf info table
	var $params = array(); // populated array of extra 'params' field
	var $fields = array(); // array of all table fields
	var $subtables = array(); // array of all sub tables names
	var $pkey = '';	   // primary key field
	var $htmlform = ''; // html form name ('frm' is tfadmin default)
	var $row;   // current row in database. good for computed types
	var $rowc;  // html form element row count (TODO move it to TfTable object)
	var $curid; // database current row id (TODO move it to TfTable object)

	function TfTable($fetch_row_or_tname = null, $skip_fields_and_subtables = false) {
		if ($fetch_row_or_tname!==null) {
			$this->populate($fetch_row_or_tname, $skip_fields_and_subtables);
		}
	}

	function populate($fetch_row_or_tname, $skip_fields_and_subtables = false) { // populate class vars from the tf info row
		global $tf;
		if (!is_array($fetch_row_or_tname)) {
			$fetch_row_or_tname = TftFetchTable($fetch_row_or_tname);
		}
		$this->tname = $fetch_row_or_tname['tname'];
		$this->fetch = $fetch_row_or_tname;

		$this->fetch['engine'] = ''; //'MyISAM';
		$this->fetch['charset'] = 'utf8';
		$this->fetch['auto_increment'] = '1';  // first auto_increment value
		if (!empty($this->params['auto_increment']))
			$this->fetch['auto_increment'] = $this->params['auto_increment'];
		if (!empty($this->params['charset']))
			$this->fetch['charset'] = $this->params['charset'];
		if (!empty($this->params['engine']))
			$this->fetch['engine'] = $this->params['engine'];

		if (!$skip_fields_and_subtables) {
			if (empty($this->params)) {
				$res = mysql_query('SELeCT `key`,`value` FROM '.sqlf($tf['tbl.meta']).' WHERE (`tname`='.sqlv($this->tname).') AND (`fname`=\'\')');
				while($row=mysql_fetch_array($res))
					$this->params[$row[0]]=$row[1];
			}

			$res = mysql_query('SELeCT * FROM '.sqlf($tf['tbl.info']).' WHERE (`tname`='.sqlv($this->tname).') AND (`fname`<>\'\') ORDER BY `order` DESC');
			while ($row = mysql_fetch_assoc($res)) {
				$class = "TfType".$row['class'];
				if (class_exists($class)) {
					$this->fields[$row['fname']] = new $class();
				} else {
					addToLog("<t>$class</t> "._('Bad class at')." <f>$this->tname.$row[fname]</f>=<f>".$this->fetch['label'].".$row[label]</f>",'bad',__LINE__);
					$this->fields[$row['fname']] = new TfType();
				}
				$resmeta = mysql_query('SELecT * FROM '.sqlf($tf['tbl.meta']).' WHERE (`tname`='.sqlv($row['tname']).' AND `fname`='.sqlv($row['fname']).')');
				if ($resmeta) {
					while ($rowmeta=mysql_fetch_assoc($resmeta))
						$this->fields[$row['fname']]->params[$rowmeta['key']]=$rowmeta['value'];

					// subtables connected FROM this table
					if (!empty($this->fields[$row['fname']]->params['xtable']) && !empty($this->fields[$row['fname']]->params['xkey'])) {
						$this->subtables[]=array('tname'=>$this->fields[$row['fname']]->params['xtable'],'fname'=>$this->fields[$row['fname']]->params['xkey'],'xkey'=>$row['fname'],'from');
					}
				}

				$this->fields[$row['fname']]->populate($row,$this);
				$this->fields[$row['fname']]->populate_intag();

				if ($row['class']=='pkey')
					$this->pkey=$row['fname'];
			}// while $row

			// subtables connected TO this table
			$resmeta = mysql_query('SELEcT `tname`,`fname` FROM '.sqlf($tf['tbl.meta'])." WHERE (`tname`<>".sqlv($this->tname)." AND `key`='xtable' AND `value`=".sqlv($this->tname).')');
			while ($rowmeta = mysql_fetch_assoc($resmeta)) {
				$res = mysql_query('SELEcT `value` FROM '.sqlf($tf['tbl.meta']).' WHERE (`tname`='.sqlv($rowmeta['tname']).' AND `fname`='.sqlv($rowmeta['fname'])." AND `key`='xkey' AND `value`=".sqlv($this->pkey).")");
				if ($res) if ($row=mysql_fetch_array($res))
					$this->subtables[]=array('tname'=>$rowmeta['tname'],'fname'=>$rowmeta['fname'],'xkey'=>$row[0],'to');
					//$this->subtables["$rowmeta[tname]..$row[0]"]=array('tname'=>$rowmeta['tname'],'fname'=>$rowmeta['fname'],'xkey'=>$row[0]);
			}
		}//if !$skip_fields_and_subtables

		if (!empty($this->params['pkey']))
			$this->pkey = $this->params['pkey'];
	}

	function userCan($user, $action) {   // This user can make this action? (like TftUserCan)
		global $tf;
		if (empty($user))
			$user = tfGetUserGroup();
		$action = strtolower($action);
		$user = strtolower($user);
		if (array_key_exists($action, $tf['permissionMap']))
			$action = $tf['permissionMap'][$action];
		if (!array_key_exists('users' . $action, $this->fetch))
			return null;
		return strpos(',' . strtolower($this->fetch['users' . $action]) . ',', ',' . $user . ',') !== false;
	}

	// Get or Set a value from the extra params field.
	// To remove a value set it to NULL
	// When value is missing NULL is returned
	function param($param,$set=0) {
		if (func_num_args()==2)
			if ($set === null) unset($this->params[$param]);
			else $this->params[$param] = $set;
		else
			if (array_key_exists($param, $this->params))
				return $this->params[$param];
			else
				return null;
	}

	// Return a boolean from a param. Default = false or given value.
	// Using oria.inc.php:chkBool()
	function paramtrue($param, $default = false) {
		if (array_key_exists($param,$this->params))
			if ($this->params[$param]===0 || $this->params[$param]==='0' || $this->params[$param]===false)
				return false;
			elseif ($this->params[$param]===1 || $this->params[$param]==='1' || $this->params[$param]===true)
				return true;
			else
				return chkBool($this->params[$param], $default);
		else
			return $default;
	}

	function htmlInHead() {
		return '';
	}

	function htmlInForm() {
		return '';
	}

}// class TfTable

///////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////// Load Custom TfTypes Custom Classes /////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
if (file_exists(__DIR__.'/../custom/tftypes.php')) {
	try {
		include_once(__DIR__.'/../custom/tftypes.php');
	} catch (Exception $e) {
		addToLog('Error including <t>custom/tftypes.php</t>',LOGBAD,__LINE__);
		addToLog('<t>'.$e->getMessage().'</t>',LOGBAD,__LINE__,true);
	}
}

$list = glob(__DIR__.'/../custom/tftypes/*.php');
foreach ($list as $file) {
	try {
		include_once($file);
	} catch (Exception $e) {
		addToLog("Error including <t>$file</t>",LOGBAD,__LINE__);
		addToLog('<t>'.$e->getMessage().'</t>',LOGBAD,__LINE__,true);
	}
}
