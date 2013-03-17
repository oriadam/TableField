// oria.js
// Misc list of usefull javascript functions
// License: GPLv3
// http://tablefield.com
// http://code.google.com/p/oria-inc-php


// make sure we dont include this js twice:
if ((!document) || (!document.oriaRegisteredScripts)){
	document.oriaRegisteredScripts=1;


	//////////// String and Array prototype /////////////

	// Same as PHP urlencode()
	String.prototype.urlencode = function () {
		return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
		// http://phpjs.org/functions/urlencode
	};

	// Same as PHP urldecode()
	String.prototype.urldecode = function () {
		return decodeURIComponent((str + '').replace(/\+/g, '%20'));
		// http://phpjs.org/functions/urldecode
	};

    // Same as PHP str_rot13() - Perform the rot13 transform on a string
	String.prototype.rot13 = function () {
		return this.replace(/[a-z]/gi, function (s) {
			return String.fromCharCode(s.charCodeAt(0) + (s.toLowerCase() < 'n' ? 13 : -13));
		});
		// http://phpjs.org/functions/str_rot13
	};

	// XOR string - like PHP operator 'String1' ^ 'String2'
	String.prototype.xor = function (key) {
		var res="";
		if (typeof(key)!='string' || key=='') return null;
		while(key.length<this.length) {
			key+=key;
		}
		for(i=0;i<this.length;++i)
		{
			res+=String.fromCharCode(key.charCodeAt(i)^this.charCodeAt(i));
		}
		return res;
	}

    // wrapper for attachEvent because of IE non standard stuff
    function setEvent(obj,event,funct, bOnBubble) {
      event='on'+event.replace(/^on/,'');
      if (window.attachEvent) {
        return obj.attachEvent('on'+event,funct);
      } else {
        if (window.addEventListener) {
          obj.addEventListener(event,funct,bOnBubble);
          return true;
        } else {
          alert('setEvent error: browser does not support events. ('+(obj.id || obj.tagName || obj)+','+event+')');
          return false;
        }
      }
    }

    // check whether a form field has been changed. returns:
    //    true  when changed
    //    false when unchanged
    //    null  when uncertain
    function wasChanged(oField) {
      if (oField) {
        if (object_has(oField,'value') && object_has(oField,'defaultValue')) {
          return oField.value!=oField.defaultValue;
        }
        if (object_has(oField,'selected') && object_has(oField,'defaultSelected')) {
          return oField.value!=oField.defaultValue;
        }
        if (object_has(oField,'options')) {
          for (var i=0;i<oField.options.length;i++) {
            if (oField.options[i].selected!=oField.options[i].defaultSelected) {
              return true;
            }
          }
          if (i) return false;
        }
      }
      return null; // when uncertain returns null
    }

    // return a command-line (GET URL Location.search) parameter
    function getQueryParam(sName,sSearch) {
        if (typeof(sSearch    )=='undefined') sSearch=document.location.search;
        if (typeof(sSearch.search)=='string') sSearch=sSearch.search;  // in case we got a Location object
        sSearch=sSearch.toString().replace(/^\?/,''); // remove first ?
        sSearch='&'+sSearch+'&';

        var res=sSearch.match(new RegExp('&'+sName+'=([^&]*)','i'));
        if (res && res.length>1) {
            return res[1];
        }else{
            return null;
        }
    }

    // get an array symbolizing the location search string
    function getSearchArray(sSearch,sSeperator,sAssign,bDoUnescape)
    {
        // initialize variables
        if (typeof(sSeperator )=='undefined') sSeperator = '&';
        if (typeof(sAssign    )=='undefined') sAssign = '=';
        if (typeof(bDoUnescape)=='undefined') bDoUnescape = true;
        if (typeof(sSearch    )=='undefined') sSearch=document.location.search;
        if (typeof(sSearch.search)=='string') sSearch=sSearch.search;  // in case we got a Location object
        if (sSeperator == '&') sSearch=sSearch.toString().replace(/^\?/,''); // remove first ?

        // start diggin for the keys=values
        var aReturn = {};
        var aPairs = sSearch.split(sSeperator);
        var nEqPos,sKey,sVal;
        for(var i = 0; i < aPairs.length; i++) {
            nEqPos = aPairs[i].indexOf(sAssign);
            sKey = aPairs[i].substring(0,nEqPos);
            if (nEqPos == -1) {
                aReturn[sKey] = null;
            }else{
                sVal = aPairs[i].substring(nEqPos+1);
                aReturn[sKey] = bDoUnescape ? unescape(sVal) : sVal;
            }
        }
        return aReturn;
    }


    // return a $_GET style associative array from query string
    // optional bCaseLowercase will lower case the query keys. default true.
    function queryGET(sSearch,bCaseLowercase) {
        var i,aReturn,aPairs,aPair,s;
        if (typeof(sSearch    )=='undefined') sSearch=document.location.search;
        if (typeof(sSearch.search)=='string') sSearch=sSearch.search;  // in case we got a Location object
        if (typeof(bCaseLowercase)=='undefined' || bCaseLowercase===null) bCaseLowercase=true;
        var s=sSearch; // the return string
        s=s.toString().replace(/^[\?]+/,''); // remove first ?
        s=s.toString().replace(/^[\&]+/,''); // remove first &

        aReturn={}; // the associative array of GET
        aPairs=s.split('&'); // split pairs
        for (i=0;i<aPairs.length;i++) {
          aPair=aPairs[i].split('=');
          if (bCaseLowercase) aPair[0]=aPair[0].toLowerCase();
          aReturn[aPair[0]]=aPair[1];
        }
        return aReturn;
    }

    // return a query string based on a $_GET style associative array. skips null values.
    function queryFromGET(oGET) {
      var s='';
      var i;
      for (i in oGET) {
        if (oGET[i]!==null) {
          s+='&'+i+'='+oGET[i];
        }
      }
      s=s.replace(/^[\&]+/,''); // remove first &
      return s;
    }

    // query string manipulations
    // receives an object of parameters to change, optional string (default=location.search)
    // returns a string
    function queryChange(oParams,sSearch) {
        var a=queryGET(sSearch);
        var i;
        for (i in oParams) {
          a[i]=oParams[i];
        }
        var s=queryFromGET(a);
        return s;
    }

    // black out the entire document view (before popping up a message, on page load)
    function blackoutDocOn(imgsrc,doc) {
        if (!doc) doc=document;
        if (!imgsrc) imgsrc='blackout.gif';
        var div=doc.getElementById('idOriaBlackOut');
        if (!div) {
            div=doc.createElement("div");
            //    var dbody=doc.getElementsByTagName("body").item(0);
            //    dbody.appendChild(div);
            doc.appendChild(div);
            div.id='idOriaBlackOut';
            div.style.backgroundImage="url("+imgsrc+")";
            div.style.width='100%';
            div.style.height='100%';
            div.style.position='absolute';
            div.style.top=0;
            div.style.left=0;
            div.style.zIndex=100;
            div=doc.getElementById('idOriaBlackOut');
        }
        div.style.display='';
    }

    // finish the black out
    function blackoutDocOff(doc) {
        if (!doc) doc=document;
        var div=doc.getElementById('idOriaBlackOut');
        if (div) {
            div.style.display='none';
        }
    }


    // trim the string from specified characters
    function strTrim(str,trimchars){
        if (typeof(trimchars)=="undefined") trimchars=" \t\r\n\0"
        while ((str.length>0) && (trimchars.indexOf(str.charAt(0))>=0)) str=str.substr(1);
        while ((str.length>0) && (trimchars.indexOf(str.charAt(str.length-1))>=0)) str=str.substr(0,str.length-1);
        return str;
    }

    // opens a new window with the html content of [content]
    function previewHtml(content,title,params){
		// jqueryui modal widget
		if ($ && $().dialog) {
			if (typeof(content)=='string') {
				content='<div '+params+'>'+content+'</div>';
			} else {
				if (content.value) {
					if (!params) params='';
					if (params.indexOf('style')<0 && content.style && content.style.direction) {
						params+='style="direction:'+content.style.direction+'"';
					}
					content='<div '+params+'>'+content.value+'</div>';
				} else {
					content='<div '+params+'>'+content+'</div>';
				}
			}
			$(content).dialog({width:'70%',height:450,modal:true,resizable:true,draggable:true,closeText:'X',title:title,close:function(){$(this).dialog('destroy')}});
		} else {

			// legacy method
			var w;
			if (typeof(windowname)=="undefined") windowname=true;
			if (windowname==='1' || windowname===1 || windowname===true ) windowname=Math.random().toString().substr(3);
			if (windowname==='0' || windowname===0 || windowname===false) windowname='preview_window';
			w=window.open('',windowname,'width=400,height=400,location=0,menubar=0,resizable=1,scrollbars=1,status=0,titlebar=0,toolbar=0');
			w.title=title;
			w.document.open();
			w.document.write(content);
			w.document.close();
			w.focus();
		}
    }

    // copies an object
    function copyObjectProps(destination, source,aProps)
    {
      for (var i=0;i<aProps.length;i++) {
        try {
          destination[aProps[i]] = source[aProps[i]];
        }catch(e){
          destination[aProps[i]] = null;
        }
      }
      return destination;
    }

    // shuffle a select box options
    function selectShuffle(select) {
      var tmp={};
      var props=['innerHTML','value','text','serial','title'];
      var i,j; // bubble sort. i dont have any strength to think about a better thing
      for (i=1;i<select.options.length;i++) {
        for (j=1;j<select.options.length;j++) {
          if (Math.random()<0.4) {
            // swap
            copyObjectProps(tmp,select.options[j-1],props);
            copyObjectProps(select.options[j-1],select.options[j],props);
            copyObjectProps(select.options[j],tmp,props);
          }
        }
      }
    }

// -------------------------------------------------------------------
// sortSelect(select_object)
//   Pass this function a SELECT object and the options will be sorted
//   by their text (display) values
// -------------------------------------------------------------------
function sortSelect(obj)
{
  var o = new Array();
  for (var i=0; i<obj.options.length; i++) {
  	//o[o.length] = new Option( obj.options[i].text, obj.options[i].value, obj.options[i].defaultSelected, obj.options[i].selected) ;
  	//o[o.length] = obj.options[i];
  	o[o.length] = {text:obj.options[i].text, value:obj.options[i].value, selected:obj.options[i].selected, title:obj.options[i].title} ;
  }
  if (o.length==0) {return;}
  o = o.sort(
  	function(a,b) {
      if ((a.text+"") < (b.text+"")) {return -1;}
      if ((a.text+"") > (b.text+"")) {return 1;}
      return 0;
  	}
  );

  for (var i=0; i<o.length; i++) {
    //obj.options[i] = new Option(o[i].text, o[i].value, o[i].defaultSelected, o[i].selected);
    obj.options[i] = new Option(o[i].text, o[i].value, false, o[i].selected);
    obj.options[i].title = o[i].title;
  }
}

    // re-sort a select box by a specific attribute (innerHTML by default)
    function selectSortBy(select,attr,desc) {
      attr=attr || 'innerHTML';
      desc=desc || false;
      var props=['innerHTML','value','text','title','iserial','iorder'];
      props[props.length]=attr;
      var i,j,tmp,o;
      o=[];
      // copy all values to tmp array
      for (i=0;i<select.options.length;i++) {
        o[i]={};
        copyObjectProps(o[i],select.options[i],props);
      }
      // sort
      for (i=0;i<o.length;i++) {
        for (j=i+1;j<o.length;j++) {
          if (desc) {
            if (o[i][attr]<o[j][attr]) {
              // swap
              tmp={};
              copyObjectProps(tmp,o[i],props);
              copyObjectProps(o[i],o[j],props);
              copyObjectProps(o[j],tmp,props);
            }
          } else {
            if (o[i][attr]>o[j][attr]) {
              // swap
              tmp={};
              copyObjectProps(tmp,o[i],props);
              copyObjectProps(o[i],o[j],props);
              copyObjectProps(o[j],tmp,props);
            }
          }
        }
      }
      /* -- doesnt work!
      o.sort(function(a,b) {
        if (a[attr].value<b[attr].value) return -1*(-1*desc);
        if (a[attr].value>b[attr].value) return  1*(-1*desc);
        return 0;
      });
      */
      // copy back to select
      for (i=0;i<o.length;i++) {
        select.options[i]=new Option(o[i].text,o[i].value);
        copyObjectProps(select.options[i],o[i],props);
      }
    }

    // return the index for an option in <select>, where its text == val. if not found returns -1.
    function selectText(select,val,startat){
        var i,z;
        startat=1*startat;
        z=val.length;
        if (z==0) return -1;
        for (i=startat;i<select.options.length;i++) {
            if (select.options[i].text.toLowerCase()==val.toLowerCase()){
                select.selectedIndex=i;
                return i;
            }
        }
        return -1;
    }

    // return the index for an option in <select>, where its value == val
    function selectValue(select,value,startat){
        var z,i;
        startat=1*startat;
        z=select.options.length;
        for (i=0;i<z;i++){
            if (select.options[i].value==value){
                select.selectedIndex=i;
                return i;
            }
        }
        select.value=value;
        return -1;
    }

    // return all the selected options in <select> list as string of comma seperated values
    function listToCSV(list) {
        var s;
        s='';
        for (i=0;i<list.options.length;i++) {
            if (list.options.selected) s=s+list.options[i].value+',';
        }
        return s;
    }

    // remove a char at location
    function removeCharAt(s,i){
        return s.substring(0,i)+s.substring(i+1,s.length);
    }

    // replace a char at location
    function replaceCharAt(s,i,c){
        return s.substring(0,i)+c+s.substring(i+1,s.length);
    }

    // check single character for is it a 0-9 digit
    function isNum(c){
        return (c<='9')&&(c>='0');
    }

    // fix a number - remove all none-number digits
    // opt = optional options object.
    // opt.negative : allow negatives? default true
    // opt.plus     : allow + sign at start of number? true/false/'+'. when equals '+' always put + (or -) at start of number. default false.
    // opt.floating : allow floating point? default true.
    // opt.floatmax : display at most X digits after a floating point. default 100.
    // opt.floatmin : display at least X digits after a floating point. default 0.
    // opt.round    : round up to the number of digits after floating point? true/false. default true. @@TBD
    function fixnumber(v,opt){
        if (typeof(v)=="undefined") return null;
        if (v===null) return null;
        if (v.toString().length==0) return '';
        if (typeof(opt)=='undefined') {
          opt={};
        }
        if (typeof(opt.negative)=='undefined') {
          opt.negative=true;
        }
        if (typeof(opt.plus)=='undefined') {
          opt.plus=false;
        }
        if (typeof(opt.floating)=='undefined') {
          opt.floating=true;
        }
        if (typeof(opt.floatmax)=='undefined') {
          opt.floatmax=100;
        }
        if (typeof(opt.floatmin)=='undefined') {
          opt.floatmin=0;
        }
        if (typeof(opt.round)=='undefined') {
          opt.round=true;
        }

        // negative number?
        var neg=false; // negative sign?
        var plu=false; // plus sign?
        var left='';   // left part of number (before .)
        var right='';  // right part of number (after .)
        var tmp;
        if (v=='') return '';
        tmp=v.split('.'); // note: ignoring anything after a second dot
        // handle right part:
        if (opt.floating && tmp.length>1) right=tmp[1]; // hold right part
        right=right.replace(/[^0-9]/g,''); // remove non-legal chars
        right=right.substr(0,opt.floatmax); // truncate right
        while (right.length<opt.floatmin) right+='0'; // add 0 to the end of right
        // handle left part:
        left=tmp[0]; // hold left part
        left=left.replace(/[^0-9\-\+]/g,'');// remove non-legal chars
        if (left=='' && right=='') return '';
        neg=left.charAt(0)=='-' && opt.negative; // save negative status
        left=left.replace(/-/g,''); // remove all -
        plus=left.charAt(0)=='+' && opt.plus; // save plus status
        left=left.replace(/\+/g,''); // remove all +
        if (opt.plus=='+') plus=!neg; // always put + ?
        if (neg) {
          left='-'+(1*left);  // (1*left) to make '-.45' --> '-0.45'
        } else {
          if (plus) {
            left='+'+(1*left); // (1*left) to make '.45' --> '+0.45'
          }else{
            left=(1*left); // (1*left) to make '.45' --> '0.45'
          }
        }
        v=left;
        if (right.length) v+='.'+right;
        return v;
    }

    // fix an integer positive number - remove all none-number digits
    function fixintegerp(v){
        var a=fixnumber(v,{negative:false});
        if (a===null || a==='' || a===false){
          return a;
        } else {
          return Math.round(a);
        }
    }

    // fix an integer number (- or +)- remove all none-number digits
    // round number to the closest value
    function fixinteger(v){
        var a=fixnumber(v);
        if (a===null || a==='' || a===false){
          return a;
        } else {
          return Math.round(a);
        }
    }

    // fix year - also fix for 4 digit.
    function fixyear(v){
        var i;
        // remove non-numeric chars
        for (i=0;i<v.length;){
            if (v.charAt(i)>'9' || v.charAt(i)<'0'){
                v=removeCharAt(v,i);
            }else{
                i++;
            }
        }
        // truncate too big numbers
        if (v.length>4){
            v.length=4;
        }
        // fix 1 digit year:
        if (v.length==1){
            v='200'+v;
        }
        // fix 2 digit year:
        if (v.length==2){
            if (v.charAt(0)>'2'){
                v='19'+v;
            }else{
                v='20'+v;
            }
        }
        // fix 3 digit year:
        if (v.length==3){
            v='0'+v;
        }
        return v;
    }


    // fix a date - make month/day 2 chars, and year 4 chars
    function fixdate(v){
        // fix 1 digit year:
        if ((v.charAt(1)>'9' || v.charAt(1)<'0')
        && v.charAt(0)<='9' && v.charAt(0)>='0'){
            v='200'+v;
        }
        // fix 2 digit year:
        if ((v.charAt(2)>'9' || v.charAt(2)<'0')
        && v.charAt(0)<='9' && v.charAt(0)>='0'
        && v.charAt(1)<='9' && v.charAt(1)>='0'){
            if (v.charAt(0)>'2'){
                v='19'+v;
            }else{
                v='20'+v;
            }
        }
        // fix for year-only dates:
        if (v.length==4) v=v+'/00/00';

        // fix 1 digit month:
        if ((v.charAt(6)>'9' || v.charAt(6)<'0')
        && v.charAt(5)<='9' && v.charAt(5)>='0'){
            v=v.substr(0,4)+'/0'+v.substr(5,5);
        }
        // fix 1 digit day:
        if ((v.charAt(9)>'9' || v.charAt(9)<'0')
        && v.charAt(8)<='9' && v.charAt(8)>='0'){
            v=v.substr(0,7)+'/0'+v.substr(8,2);
        }

        // the date is not a fixes 10 digit date of YYYY/MM/DD :-D

        // fix / and numbers:
        if (v.charAt(0)>'3' || v.charAt(0)<'0') v=              'Y'+v.substr(1,9);
        if (v.charAt(1)>'9' || v.charAt(1)<'0') v=v.substr(0,1)+'Y'+v.substr(2,8);
        if (v.charAt(2)>'9' || v.charAt(2)<'0') v=v.substr(0,2)+'Y'+v.substr(3,7);
        if (v.charAt(3)>'9' || v.charAt(3)<'0') v=v.substr(0,3)+'Y'+v.substr(4,6);
        v=v.substr(0,4)+'/'+v.substr(5,7);
        if (v.charAt(5)>'1' || v.charAt(5)<'0') v=v.substr(0,5)+'M'+v.substr(6,4);
        if (v.charAt(6)>'9' || v.charAt(6)<'0') v=v.substr(0,6)+'M'+v.substr(7,3);
        v=v.substr(0,7)+'/'+v.substr(8,2);
        if (v.charAt(8)>'3' || v.charAt(8)<'0') v=v.substr(0,8)+'D'+v.substr(9,1);
        if (v.charAt(9)>'9' || v.charAt(9)<'0') v=v.substr(0,9)+'D';
        v.length=10;
        return v;
    }


    // fix a time string to hh:mm:ss
    function fixtime(v){ //@@ this function is not yet checked/debuged
        // fix 1 digit hour:
        if ((v.length<2 || v.charAt(1)>'9' || v.charAt(1)<'0')
        && v.charAt(0)<='9' && v.charAt(0)>='0'){
            v='0'+v;
        }
        if (v.length==2) return v+':00:00';
        // fix 1 digit minute:
        if ((v.length<5 || v.charAt(4)>'9' || v.charAt(4)<'0' )
        && v.charAt(3)<='9' && v.charAt(3)>='0'){
            v=v.substr(0,2)+':0'+v.substr(3);
        }

        // fix for no-seconds time:
        if (v.length==5) return v+':00';

        // fix 1 digit second:
        if ((v.length<8 || v.charAt(7)>'9' || v.charAt(7)<'0' )
        && v.charAt(6)<='9' && v.charAt(6)>='0'){
            v=v.substr(0,6)+':0'+v.substr(6);
        }

        // the time is now a fixes 8 digit (atleast) time of HH:MM:SS :-D

        // fix : and numbers:
        if (v.charAt(0)>'2' || v.charAt(0)<'0') v=replaceCharAt(v,0,'H');
        if (v.charAt(1)>'9' || v.charAt(1)<'0') v=replaceCharAt(v,1,'H');
        v=replaceCharAt(v,2,':');
        if (v.charAt(3)>'5' || v.charAt(5)<'0') v=replaceCharAt(v,3,'M');
        if (v.charAt(4)>'9' || v.charAt(6)<'0') v=replaceCharAt(v,4,'M');
        v=replaceCharAt(v,5,':');
        if (v.charAt(6)>'5' || v.charAt(8)<'0') v=replaceCharAt(v,6,'S');
        if (v.charAt(7)>'9' || v.charAt(9)<'0') v=replaceCharAt(v,7,'S');
        v.length=8;
        return v;
    }

    // fix a long date-time string
    function fixdatetime(v){ //@@ this function is not yet checked/debuged
        var cnt,i,valids,p,v1,v2;
        // find the third seperator position:
        valids='0123456789YyMmDdHhMmSs';  // valid chars - not seperators
        cnt=0;
        for(i=0;i<v.length;i++){
            if (valids.indexOf(v.charAt(i))<0){ // char not in valids
                cnt++; // count seperators
                if (cnt==3) p=i;
            }
        }
        if (p>=0){
            v1=fixdate(v.substr(0,p));
            v2=fixtime(v.substr(p+1));
            return ''+v1+' '+v2;
        }else{
            return fixdate(v)+' '+fixtime(' ');
        }
    }

	// fix a phone number string. add a single '-' after 2/3 digits and remove all other chars.
	function fixphone(v){
		var k,i;
		// remove non-digit chars
		v=v.replace(/[^0-9\-\+\(\)]/g,'');
		if (v=='') return '';
		if (v.replace(/[0-9]/g,'')!='') return v; // phone already has - or + or something.
		if (v.length==9 ) return v.substr(0,2)+'-'+v.substr(2); //  9 digit phone number, in the format of 03-4567890
          if (v.length==10) return v.substr(0,3)+'-'+v.substr(3); // 10 digit phone number, in the format of 052-3456789
		return v; // something else? hmmm...
	}

    // validate an israeli phone number. phone must include area code
    // phones which starts with 1-800 or 1-XXXX or * are invalid
    // only these types are ok:
    //   02 03 04 08 09 - 9 digits
    //   05X 06X 07X - 10 digits
    function validatePhone(v){
        // remove non-digit chars
        v=v.replace(/([^0-9])/g,'');
        if (v=='') return '';
        var k=v.substr(0,2);
        if (k=='02' || k=='03' || k=='04' || k=='08' || k=='09') return v.length==9;
        if (k=='05' || k=='06' || k=='07') return v.length==10;
        return false;
    }


	// select an option in select element by value
	function updSelect(select,value){
		var z;
		z=select.options.length;
		var i;
		for (i=0;i<z;i++){
			if (select.options[i].value==value){
				select.selectedIndex=i;
				return i;
			}
		}
		return -1;
	}

    // validate an email address
    function validateEmail(email){
        if (email=='') return true;
        v="0-9a-zA-Z.!#$%&*+-=?^_`{|}~'"; // valid email chars
        re=new RegExp("["+v+"]+\\@["+v+"]+\\.["+v+"]["+v+"]+","");
        return re.test(email);
        /*
        email=email.replace(/[ \t]/,'');
        if (email=='') return 1;
        if (email.indexOf(' ')>=0) return 0;  // space in middle
        if (email.indexOf('@')<1) return 0;  // no @
        if (email.indexOf('@')!=email.lastIndexOf('@')) return 0; // more than one @
        if (email.lastIndexOf('.')<email.indexOf('@')) return 0;  // no . after the @
        return 1;
        */
    }

    // switch display:none status. optionally set a display when visible (like 'block', 'inline', etc)
    // id can be either the object, or the object id
    // return true/false - the update display status of the object. null when object not found.
    function switchDisplay(id,sDisplay) {
      sDisplay=sDisplay || '';
      var o=id;
      if (typeof(o)!="object") o=document.getElementById(o);
      if (o) {
        if (o.style) {
          if (o.style.display=='none') {
            o.style.display=sDisplay;
            return true;
          } else {
            o.style.display='none';
            return false;
          }
        }
      }
      return null;
    }


} // the big if that makes sure we do not include this js twice
