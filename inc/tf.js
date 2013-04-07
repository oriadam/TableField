// append an entry into the log
// first set global classes
logGood='logGood icon-ok-sign';
logBad='logBad icon-warning-sign';
logDebug='logDebug icon-wrench';
logSame='logSame icon-info-sign';
logTitle='logTitle';

function addToLog(msg,cssClass) {
	if (console && console.log) console.log(msg);
	var o;
	if (typeof(msg)=='string' || typeof(msg)=='number') {
		o=document.createElement('SPAN');
		o.className=cssClass || '';
		o.innerHTML=msg;
	} else {
		o=msg;
		o.style.display='';
		if (!cssClass) {
			if (o.innerHTML.indexOf(logBad)) {
				cssClass=logBad;
			} else if (o.innerHTML.indexOf(logGood)) {
				cssClass=logGood;
			}
		}
	}
	if (!document.getElementById('idLogText')) $('body').append($('<div id="idLogText">'));
	document.getElementById('idLogText').appendChild(o);
	var cs;
	if (cssClass==logBad){
		cs='btn-danger';
	} else if (cssClass==logGood){
		cs='btn-success';
	} else {
		cs='btn-info';
	}
	$('#idCtrlLog').attr('disabled',false).removeClass('disabled').removeClass('btn-success').removeClass('btn-info').addClass(cs); // btn-danger overcomes the other two
	$('#idCtrlLog i').addClass('icon-white');
}

// change the current location.href according to the given associative array of new arguments values
function reget(oParams) {
	var s=queryChange(oParams);
	location.href='?'+s;
}

// fill zeros for a number
function zeros(n,len) {
	var s=''+n;
	while (s.length<len) s='0'+s;
	return s;
}

// were there any changes in the document? check by checking the ___act[] values
function AnyChanges(frm) {
	frm = frm || document.forms['frm'];
	var i,e;
	for (i=0;i<=frm.elements.length;i++) {
		e=frm.elements[i];
		if (e && e.name && (1*e.value) && (e.name.indexOf('___del[')===0 || e.name.indexOf('___up[')===0)) {
			if (DEBUG) addToLog('Changed='+e.name+'='+e.value,logDebug);
			return true;
		}
	}
	return false;
}

// submit the search query using reget()
//   optional arguments Value and Key.
//   to clear the search use searchSubmit(true)
// search structre: s1,s2,s3 and up to s12
//    fname.searchquery.method.or
//       fname = field in which to search
//       search query = what to search. dots needs to be replaced by %2E before encoding (double encoded)
//       or = Optional. How to chain to next line? 'or' means OR, AND in any other case.
//       method: for negative search add '-' before the method name
//               in = Has: (LIKE '%Q%')
//               -in = Doesn't have: NOT (LIKE '%Q%')
//               a = Starts with: (LIKE 'Q%')
//               -a = Doesn't start with: NOT (LIKE 'Q%')
//               z = Ends with: (LIKE '%Q')
//               eq = Exact match case sensitive: (f='Q')
//               ci = Exact match Case Insensitive: (f LIKE 'Q')
//               rx = Regular expression query: (f RLIKE Q)
//               b = boolean. Works only with numeric (=non zero) and boolean fields: (f)
//               n = Null values: (f IS NULL)
//               e = Empty values: (f='')
//               gt = Greater Than. Note search-query is little than field: (f<Q)
//               gte = Greater Then or Equal to: (f<=Q)
//               lt = Little Than: (f>Q)
//               lte = Little Then or Equal to: (f>=Q)
search={p:'1',id:null,s1:null,s2:null,s3:null,s4:null,s5:null,s6:null,s7:null,s8:null,s9:null,s10:null,s11:null,s12:null};
function searchSubmit(clearSearch) {

	if (clearSearch) {
		if (getQueryParam('pp')=='1') {
			search.pp=getQueryParam('ppp') || '20';
			search.ppp=null; // Previous Per-Page
		}
	} else {
		var id=document.getElementById('idSearchID').value;
		if (id) {
			search.id=id;
			search.ppp=getQueryParam('ppp') || getQueryParam('pp');  // save Previous Per-Page value
			search.pp='1';
		}
		var count=1;
		$('.search-line:not(.hidden)').each(function(index,e) {
			e=$(e);
			var f=e.find('.search-field').get(0).value;
			if (f) {
				var how=e.find('.search-how').get(0).value;
				var q=e.find('.search-query').get(0).value;
				if (q || how=='b' || how=='n' || how=='e') {
					if (how=='b'||how=='n'||how=='e') q=''; // for Boolean, is Null, is Empty - query is irrelevant
					var not='';
					if (e.find('.search-not').get(0).checked) not='-';
					var chain='';
					var c=e.find('.search-chain').get(0);
					if (c.options[c.selectedIndex].value=='or') chain='or';
					search['s'+count]=encodeURI(f.replace(/\./,'%2E') +'.'+not+how+'.'+q.replace(/\./,'%2E')+'.'+chain); // double encode dots
					count++;
				}
			}
		});
	}
	reget(search);
}

// update the appearance of a given container id, according to predicted action - update,new,del
function updateClass(id,frm) {
	var act=frm.elements['___up['+id+']'];
	var del=frm.elements['___del['+id+']'];
	var ide=frm.elements['___id['+id+']'];
	var o=$(document.getElementById('cont_'+id));
	if (DEBUG) console.log('updateClass(',id,')','act',act,'del',del,'ide',ide,'o',o);

	if (typeof(del)=='object' && 1*del.value) { // del
		o.addClass('actDel')
		.removeClass('actNew')
		.removeClass('actUpdate');
	} else {
		if (typeof(ide)=='object') { // update
			o.removeClass('actDel');
			if (typeof(act)=='object' && 1*act.value) {
				o.addClass('actUpdate');
			} else {
				o.removeClass('actUpdate');
			}
		} else { // new
			if (typeof(act)=='object' && 1*act.value) {
				o.addClass('actNew');
			} else {
				o.removeClass('actNew');
			}
		}
	}
}

// tf form onsubmit - execute just before submitting the form
// remove unchanged fields to make the form request as small as possible
function tfFormSubmit(frm,confirmSend) {
	$(window).unbind('beforeunload'); // cancel 'leave page' warning
	addToLog('Preparing the form for submit...');
	var i,e,id;
	var ar=frm.elements;
	for (i=0;i<ar.length;i++) {
		e=ar[i];
		if (e.name) { // no name - no gain
			//if (DEBUG) if (e.type=='hidden') e.type='text';
			id=1*e.name.replace(/^[^\[]+\[([0-9]+)\].*$/,'$1');
			if (isNaN(id))
				addToLog('TF Submit Error: could not find id inside "'+e.name,logBad);
			else
				if (e.name.indexOf('___')===0) // special - keep ___up[] ___del[] ___id[]
					e.tf_remove_me=!e.value; // keep only when true/not empty
				else
					if (!ar['___up['+id+']'] || !ar['___up['+id+']'].value) // remove when not updating current id
						e.name=e.value=null;
		} else {
			e.name=e.value=null;
		}
		// do not remove here, so that ___up and ___del won't be missing
	}//for elements
	// remove unnecessary elements from form

	//for (i=0;i<ar.length;i++)
	//	if (ar[i].tf_remove_me)
	//		ar[i].name=ar[i].value=ar[i].type='';
	//$('.tf_remove_me').remove();
	addToLog('Finished. Sending Form...');

	if (confirmSend || (document.getElementById('idCtrlSaveConfirm') && document.getElementById('idCtrlSaveConfirm').checked)) {
		if (!confirm("Form prepare complete. Submit?\nHit cancel to review form in place")) {
			w=window.open();
			w.document.open();
			w.document.write('<code>');
			for (i=0;i<frm.elements.length;i++) w.document.write(frm.elements[i].outerHTML);
			w.document.write('</code>');
			w.document.write(idLogText.outerHTML);
			return false;
		}
	}
	return true;
}

// tf form load - execute when form finish loading
function tfFormLoad(frm) {
	var i,e;
	for (i=0;i<frm.elements.length;i++) {
		e=frm.elements[i];
		// attach onchange that set the 'please update' checkbox
		//if (e.name.indexOf('___')!==0)  // important to also bind ___del/___up
			$(e).on('change', tffechg);
	}
}

// tf form element - set 'update' on every change of
function tffechg(event) {
	if (!event && window.event) event=window.event;
	var e=false; // source element
	if (event.srcElement) e=event.srcElement;
	else if (event.target) e=event.target;
	//if (DEBUG) console.log(event,e);
	if (e) {
		if (e.type && e.type=='checkbox')
			e.value=1*e.checked;
		var id=1*e.name.replace(/^[^\[]+\[([0-9]+)\].*/,'$1');
		if (id && e.name.indexOf('___')!==0) { // dont update actions when changed value is  ___del/___up
			var act=e.form.elements['___up['+id+']'];
			if (act) {
				$(e).addClass('tfChg'); // used to be csEChanged
				act.checked=true;
				act.value=1;
			}
		}//e.name not ___ special and id exist
		if (!isNaN(id)) updateClass(id,e.form);
	}//e
}

function updParamsElement(key,value,element) {
	if (typeof(element)=='string') element=document.getElementById(element);
	if (!element) {
		alert('updParamsElement: element not found: '+element);
		return false;
	}
	var p=paramsfromstring(element.value);
	if (value===null)
		delete p[key];
	else
		p[key]=value;
	element.value=stringfromparams(p);
	p=null;
	return true;
}

function paramsfromstring(str) {
	var ret = {};
	var seg = str.replace(/^\?/,'').split('&');
	var len = seg.length;
	for (var i=0;i<len;i++) {
		if (!seg[i]) { continue; }
		var x=seg[i].indexOf('=');
		if (x>0) {
			ret[seg[i].substr(0,x)]=seg[i].substr(x+1);
		} else {
			$ret[seg[i]]=null;
		}
	}
	return ret;
}

function stringfromparams(params) {
	var ret=[];
	for (var k in params) {
		ret.push(escape(k)+'='+escape(params[k]));
	}
	return ret.join('&');
}

// set a new value to star fields
function starset(input,val,imgid,maxstars){
	var i;
	val=1*val;
	if ((val<0) || (''+val=='NaN')) val=0;
	input.value=val;

	// filled stars
	for(i=1;i<=val;i++) {
		$('#'+imgid+i).attr('class','icon-star');
	}
	// empty stars
	for(i=val+1;i<=maxstars;i++) {
		$('#'+imgid+i).attr('class','icon-star-empty');
	}
	$('#'+imgid).attr('class','tfStars star'+val);
}

// update xkeys input from the select
function tfksUS(select,input){
	var v='';
	for (var i=0;i<select.options.length;i++){
		if (select.options[i].selected || select.options[i].checked){
			v+=','+select.options[i].value;
		}
	}
	input.value=v;
}

// update xkeys input from the checkboxes
function tfxsCB(input,CBname){
	var v='';
	var ar=document.getElementsByName(CBname);
	for (var i=0;i<ar.length;i++){
		if (ar[i].checked){
			v+=','+ar[i].value;
			$('#'+CBname+' '+ar[i].value).removeClass('false').addClass('true');
		} else {
			$('#'+CBname+' '+ar[i].value).removeClass('true').addClass('false');
		}
	}
	input.value=v;
}

// updates xkeys and the boxes from value.
function updKeysAndBoxes(frm,fname,value){
	if (frm=='' || fname==''){
		alert('updKeysAndBoxes Error: no frm/fname ('+frm+'.'+fname+')');
		return ; // exit on error
	}
	var f,o,nam,val,elem,i;
	f=document.forms[frm].elements[fname];
	f.value=value;
	value=','+value+',';
	elem=document.forms[frm].elements;
	for (i=0;i<elem.length;i++){
		nam=elem[i].name;
		o=elem[i];
		if (nam.substr(0,fname.length+5)==fname+'_box_') { // this is one of our checkboxes
			val=o.value;
			if (val=='') val=nam.substr(fname.length+5);
			o.checked=value.indexOf(','+val+',')>=0;
		}
	}
}

// toggle options in a multi select (not yet working)
function toggleMultiSelect(frm,fname,value){
	if (frm=='' || fname==''){
		alert('toggleMultiSelect Error: no frm/fname ('+frm+'.'+fname+')');
		return ; // exit on error
	}
	var f,z,i;
	f=document.forms[frm].elements[fname];
	z=f.options.length;
	for (i=0;i<z;i++){
		if (f.options[i].value==value){
			f.options[i].selected=!f.options[i].selected;
		}
	}
}

// check that a password was entered the same way twice
function matchpasswords(p1,p2,bundle,allowempty){
	if ( (allowempty && p1.value=='')
	  || ((allowempty || p1.value!='') && p1.value==p2.value))
	{
		$(bundle).removeClass('mismatched');
		return true;
	} else {
		$(bundle).addClass('mismatched');
		if (p2.value!='') {
			p2.value='';
			p2.focus();
		}
		return false;
	}
}

// update fname as y-m-d accoarding to fname_sel_year,fname_sel_month,fname_sel_day
function updateDate(frm,fname){
	if (frm=='' || fname==''){
		alert('updateDate Error: no frm/fname ('+frm+'.'+fname+')');
		return ; // exit on error
	}
	var val;
	document.forms[frm].elements[fname+'_sel_year' ].value=fixyear(document.forms[frm].elements[fname+'_sel_year' ].value);
	val =zeros(document.forms[frm].elements[fname+'_sel_year' ].value,4)+'-' +
	zeros(document.forms[frm].elements[fname+'_sel_month'].value,2)+'-' +
	zeros(document.forms[frm].elements[fname+'_sel_day'  ].value,2);
	document.forms[frm].elements[fname].value=val;
}

// update a time field as h:m:s accoarding to fname_sel_hour,fname_sel_minute,fname_sel_second
function updateTimes(frm,fname){ //@@ this function is not yet checked/debuged
	if (frm=='' || fname==''){
		alert('updateTimes Error: no frm/fname ('+frm+'.'+fname+')');
		return ; // exit on error
	}
	var val;
	val =document.forms[frm].elements[fname+'_sel_hour'  ].value+':' +
	document.forms[frm].elements[fname+'_sel_minute'].value+':' +
	document.forms[frm].elements[fname+'_sel_second'].value;
	document.forms[frm].elements[fname].value=val;
}

// update a time field as h:m accoarding to fname_hour,fname_minute
function updateTime(frm,fname){ //@@ this function is not yet checked/debuged
	if (frm=='' || fname==''){
		alert('updateTime Error: no frm/fname ('+frm+'.'+fname+')');
		return ; // exit on error
	}
	var val;
	val =document.forms[frm].elements[fname+'_sel_hour'  ].value+':' +
	document.forms[frm].elements[fname+'_sel_minute'].value;
	document.forms[frm].elements[fname].value=val;
}

// update a time value from select boxes
function tfutim(frm,fname){
	if (frm=='' || fname==''){
		alert('updateTime Error: no frm/fname ('+frm+'.'+fname+')');
		return ; // exit on error
	}
	var v='';
	alert('TODO!! tfutim() - update a time value from select boxes');
	//@@@TODO: tfutim() - update a time value from select boxes
}

// open an iframe for editing one row
function tfopenedit(href,curid,obj,layout,dialogTitle) {
	var iframeid='inline-edit-'+curid.toString();
	var iframejq=$('#'+iframeid);
	if (DEBUG) dbg_iframejq=iframejq;

	if (iframejq.length) { // if already open, center it so use would see

		if (dialogTitle) iframejq.dialog('option','position','center');

	} else { // only if iframe isn't already open

		// create iframe object
		iframejq=$('<iframe>').attr({src:href,id:iframeid,scrolling:'no',class:'inlineEdit'}).css({padding:0,margin:0})

		if (dialogTitle) { // open dialog

			$(obj).addClass('clicked');
			iframejq.dialog({
				resize:'auto',
				title: dialogTitle,
				close: function(event, ui) {
					$(this).dialog('destroy').remove();
				},
				draggable:true,
				resizable:true,
				show: {effect: 'fadeIn', duration: 400},
				hide: {effect: 'fadeOut', duration: 250}
			});
			iframejq.one('load',function(){ // when changing this to load() or on() everything is messed up :-/
				iframejq.thewrapper=iframejq.contents().find('#idForm table');
				iframejq.thewrapper.css({padding:0,margin:0}).parent().css({padding:0,margin:0});
				var h=iframejq.thewrapper.height();
				var w=iframejq.thewrapper.width()+2;
				iframejq.dialog('option','height',h);
				iframejq.dialog('option','width',w);
				iframejq.dialog('option','position','center');
				iframejq.css({width:w,height:h});
			});
			iframejq.load(function(){
				iframejq.thewrapper=iframejq.contents().find('#idForm table');
				iframejq.thewrapper.css({padding:0,margin:0}).parent().css({padding:0,margin:0});
			});

		} else { // edit inline

			iframejq.one('load',function(){ // when changing this to load() or on() everything is messed up :-/
				iframejq.thewrapper=iframejq.contents().find('#idForm table');
				iframejq.thewrapper.css({padding:0,margin:0});
				var h=iframejq.thewrapper.height()+2;
				var w=iframejq.thewrapper.width()+4;
				if (w>2000) w=1200;
				iframejq.css({width:w,height:h});
			});
			var parent=$(obj).parents('.tfRow');
			if (layout=='l') {
				var add=$('<tr>')
					.append($('<td colspan="100%">').append(iframejq))
					.attr({/*id:parent.attr('id'),*/class:'tfRow inlineEdit'})
					.insertAfter(parent);
				parent.hide();//.delay(4000,function(){$(this).remove()})
			} else {
				var add=$('<div>').hide().append(iframejq)
				.attr({/*id:parent.attr('id'),*/class:'tfRow inlineEdit'})
				.insertAfter(parent)
				.delay(350).slideDown(350,function(){this.style.position='';});
				parent.slideUp(350);//,function(){$(this).hide().remove()});
			}
		}
	}
}


// open a sub-table row subtables sub tables
function tfopensub(href,curid,obj,keycount) {
	var divid='sub_'+curid+'_'+keycount;
	var divj=$('#'+divid);
	if (divj.length) { // already exists, show/hide it
		divj.toggle();
		tfsubfixsize(divid);
	} else {
		// create iframe object and wrapper div object
		iframej=$('<iframe>').attr({'src':href,'divid':divid,'scrolling':'no','width':'100%','height':'100%'}).addClass('subtableiframe')
			.load(function() {
				//alert($(this).attr('divid'));
				tfsubfixsize($(this).attr('divid'));
				$(this).contents().get(0).divid=$(this).attr('divid');
			});
		var divj=$('<div>').attr('id',divid).addClass('subdiv').append(iframej);
		$('<tr class=subtr>').append($('<td colspan="100%">').append(divj)).insertAfter($(obj).parents('tr'));
	}
	if (divj.filter(':hidden').length) { // hidden
		$(obj).removeClass('icon-folder-open-alt').addClass('icon-folder-open');
	} else { // visible
		$(obj).removeClass('icon-folder-open').addClass('icon-folder-open-alt');
	}
}
function tfsubfixsize(divid) {
	var divj=$('#'+divid);
	var iframej=divj.find('iframe');
	divj.height(iframej.contents().find('body').height()+5);
	var w=iframej.contents().width()+5;
	if (w>2000 || w<60) w=iframej.contents().find('#idForm').width()+5;
	if (w>2000 || w<60) w=iframej.contents().find('#idForm').width()+5;
	if (w>2000 || w<60) w=1200;
	divj.width(w);
	if (window.parent && window.parent!==window) {
		if (window.document.divid) {
			window.parent.tfsubfixsize(window.document.divid);
		}
	}
}

// handle file delete
function tfFd(delinput,eid) {
	delinput.value=1*delinput.checked;
	if (delinput.value) {
		if (document.getElementById(eid).value!='')	document.getElementById(eid).oldvalue=document.getElementById(eid).value;
		document.getElementById(eid).value='';
		$('#'+eid+' del').addClass('true');
	} else {
		$('#'+eid+' del').removeClass('true');
	}
}

// copy changed values from first line
function tfCopyChangedFromFirst() {
	var objs=$('[name$=\\[1\\]]');
	for (var i=0;i<objs.length;i++) {
		var objj=$(objs[i]);
		if (objj.hasClass('tfChg')) {
			var f=objs[i].name.toString().replace(/\[[0-9]+\]$/,'');
			var tochange = $('[name^='+f+'\\[]').not(objj).not('.tfNew *')
				.val(objj.val());
			if (objj.hasClass('chzn-done'))
				tochange.trigger('liszt:updated');
			if (objs[i].checked===true)
				for (var k=0;k<tochange.length;k++)
					tochange[k].checked=true;
			if (objs[i].checked===false)
				for (var k=0;k<tochange.length;k++)
					tochange[k].checked=false;
			tochange.trigger('change');
		}
	};
}


// 'discard changes' confirmation
$(window).bind('beforeunload', function() {
		if (AnyChanges())
			return DISCARD_CHANGES;
		else
			return null;
	});
