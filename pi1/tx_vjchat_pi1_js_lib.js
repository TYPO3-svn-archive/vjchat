// JavaScript Document
function tx_vjchat_pi1_js_lib() {

	/******************************************************************************************************/
	/*** String Functions  ********************************************************************************/
	/******************************************************************************************************/

	this.urlEncode = function(str) {
		len = str.length;
		res = new String();
		charOrd = new Number();
		
		for (i = 0; i < len; i++) {
			charOrd = str.charCodeAt(i);
			if ((charOrd >= 65 && charOrd <= 90) || (charOrd >= 97 && charOrd <= 122) || (charOrd >= 48 && charOrd <= 57) || (charOrd == 33) || (charOrd == 36) || (charOrd == 95)) {
				// this is alphanumeric or $-_.+!*'(), which according to RFC1738 we don't escape
				res += str.charAt(i);
	
			}
			else {
				res += '%';
				if (charOrd > 255) res += 'u';
				hexValStr = charOrd.toString(16);
				if ((hexValStr.length) % 2 == 1) hexValStr = '0' + hexValStr;
				res += hexValStr;
			}
		}
	
		return res;
	} 
	
	this.trimString = function(sInString) {
	  sInString = sInString.replace( /^\s+/g, "" );// strip leading
	  return sInString.replace( /\s+$/g, "" );// strip trailing
	}
	
	/******************************************************************************************************/
	/*** Mouse- and KeyEvents *****************************************************************************/
	/******************************************************************************************************/

	/******************************************************************************************************/
	// For checking CTRL+ENTER
	/******************************************************************************************************/	
	this.getNamedKey = function(k){ 
	  if(typeof KeyEvent == "undefined") {
		return ""; 
	  } else{
		for( key in KeyEvent ){
		  if(KeyEvent[key] == k)
			return key.substr("DOM_VK_".length)
		}
	  }
	}

	/******************************************************************************************************/
	/*** DOM HELPER FUNCTIONS *****************************************************************************/
	/******************************************************************************************************/

	/*
	this.toggleElement = function(element, displayType) {

		var visible = !(element.style.display == "none");

		if(!visible) {
			element.style.display = displayType;
		}
		else {
			element.style.display = "none";
		}
		
	}


	this.toggleElementById = function(elementId, displayType) {
		if(!elementId || !document.getElementById(elementId))
			return;
		
		if(!displayType)
			displayType = "block";
		
		var element = document.getElementById(elementId);
		this.toggleElement(element, displayType);
	}
	*/
	
	this.getElementsByClassName = function( root, clsName, clsIgnore )  {
		var i, matches = new Array();
		var els = root.getElementsByTagName("*");
		var rx1 = new RegExp(".*"+clsName+".*");
		var rx2 = new RegExp(".*"+clsIgnore+".*");
		for(i=0; i<els.length; i++) {
		  if(els.item(i).className.match(rx1) && (clsIgnore == "" || !els.item(i).className.match(rx2)) ) {
				matches.push(els.item(i));
		  }
		}
		return matches;
	}	

	this.getTarget = function(e) {
		var targ;
		if (!e) 
			var e = window.event;

		if (e.target) 
			targ = e.target;
		else if 
			(e.srcElement) targ = e.srcElement;

		if (targ.nodeType == 3) // defeat Safari bug
			targ = targ.parentNode;
		
		return targ;
		
	}
	
	// perform submit if ENTER 
	this.getKeyValues = function(evt) {
		var e = evt || window.event; 
		var ch="";

		var keyCode = 0;
		var ctrlPressed = 0;
				
		if(document.layers){
			if(e.which>0) {
				ch = String.fromCharCode(e.which);
				keyCode = e.which;
			}
			ctrlPressed=(e.modifiers==Event.CONTROL_MASK);
		}
		else if(document.all){
			ctrlPressed =e.ctrlKey;
		if(e.keyCode>0) 
			ch=String.fromCharCode(e.keyCode);
			keyCode = e.keyCode;			
		}
		else if (document.getElementById){
			ctrlPressed =e.ctrlKey;
			if(e.charCode>0) {
				ch = String.fromCharCode(e.charCode);
				keyCode = e.charCode;
			}
			if(e.which>0) {
				ch = String.fromCharCode(e.which);
				keyCode = e.which;
			}
			if(e.keyCode>0) {
				ch = this.getNamedKey(e.keyCode);
				keyCode = e.keyCode;
			}
		} 

		var result = new Array();
		result['keyCode'] = keyCode;
		result['ctrlPressed'] = ctrlPressed;
		return result;
			
	}

	// http://aktuell.de.selfhtml.org/tippstricks/javascript/bbcode/
	this.addSelText = function(element, aTag, eTag) {
	  var input = element;
	  input.focus();
	  /* für Internet Explorer */
	  if(typeof document.selection != "undefined") {
		/* Einfügen des Formatierungscodes */
		var range = document.selection.createRange();
		var insText = range.text;
		range.text = aTag + insText + eTag;
		/* Anpassen der Cursorposition */
		range = document.selection.createRange();
		if (insText.length == 0) {
		  range.move("character", -eTag.length);
		} else {
		  range.moveStart("character", aTag.length + insText.length + eTag.length);      
		}
		range.select();
	  }
	  /* für neuere auf Gecko basierende Browser */
	  else if(typeof input.selectionStart != "undefined")
	  {
		/* Einfügen des Formatierungscodes */
		var start = input.selectionStart;
		var end = input.selectionEnd;
		var insText = input.value.substring(start, end);
		input.value = input.value.substr(0, start) + aTag + insText + eTag + input.value.substr(end);
		/* Anpassen der Cursorposition */
		var pos;
		if (insText.length == 0) {
		  pos = start + aTag.length;
		} else {
		  pos = start + aTag.length + insText.length + eTag.length;
		}
		input.selectionStart = pos;
		input.selectionEnd = pos;
	  }
	  /* für die übrigen Browser */
	  else
	  {
		/* Abfrage der Einfügeposition */
		var pos;
		var re = new RegExp("^[0-9]{0,3}$");
		while(!re.test(pos)) {
		  pos = prompt("Einfügen an Position (0.." + input.value.length + "):", "0");
		}
		if(pos > input.value.length) {
		  pos = input.value.length;
		}
		/* Einfügen des Formatierungscodes */
		var insText = prompt("Bitte geben Sie den zu formatierenden Text ein:");
		input.value = input.value.substr(0, pos) + aTag + insText + eTag + input.value.substr(pos);
	  }
	}


	this.insertAtCursor = function(myField, myValue) {
		//IE support
		if (document.selection) {
			myField.focus();
			sel = document.selection.createRange();
			sel.text = myValue;
			myField.focus();
		}
		//MOZILLA/NETSCAPE support
		else if (myField.selectionStart || myField.selectionStart == 0) {
			var startPos = myField.selectionStart;
			var endPos = myField.selectionEnd;
			myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
			myField.focus();
			var pos = startPos + myValue.length;
			myField.selectionStart = pos;
			myField.selectionEnd = pos;			
		} else {
			myField.value += myValue;
		}
	}

	this.clearNode = function(node) {

		if((node == null) || (node == "undefined"))
			return;
			
		while(node.hasChildNodes()) {
		  var childNode = node.firstChild;
		  node.removeChild(childNode);
		 }
		 
	}
	
	this.moveToMousePosition = function(evt, element, offsetX, offsetY) {
		Event.stop(evt);

		var posx = Event.pointerX(evt)+parseInt(offsetX);
		var posy = Event.pointerY(evt)+parseInt(offsetY);
		
		var dim = GetWindowSize();
		var width = element.getWidth();
		
		if((posx + width) + 40 > (dim.width))
			posx = dim.width - width - 40;
	
		element.style.left = posx + "px";
		element.style.top = posy + "px";

	}
	
}

// http://textsnippets.com/posts/show/835
GetWindowSize = function(w) {
	var width, height;
        w = w ? w : window;
        this.width = w.innerWidth || (w.document.documentElement.clientWidth || w.document.body.clientWidth);
        this.height = w.innerHeight || (w.document.documentElement.clientHeight || w.document.body.clientHeight);
        
        return this;
}

// http://gorondowtl.sourceforge.net/wiki/Cookie
var Cookie = {
  set: function(name, value, daysToExpire) {
    var expire = '';
    if (daysToExpire != undefined) {
      var d = new Date();
      d.setTime(d.getTime() + (86400000 * parseFloat(daysToExpire)));
      expire = '; expires=' + d.toGMTString();
    }
    return (document.cookie = escape(name) + '=' + escape(value || '') + expire);
  },
  get: function(name) {
    var cookie = document.cookie.match(new RegExp('(^|;)\\s*' + escape(name) + '=([^;\\s]*)'));
    return (cookie ? unescape(cookie[2]) : null);
  },
  erase: function(name) {
    var cookie = Cookie.get(name) || true;
    Cookie.set(name, '', -1);
    return cookie;
  },
  accept: function() {
    if (typeof navigator.cookieEnabled == 'boolean') {
      return navigator.cookieEnabled;
    }
    Cookie.set('_test', '1');
    return (Cookie.erase('_test') === '1');
  }
};

Array.prototype.inArray = function (value)
// Returns true if the passed value is found in the
// array.  Returns false if it is not.
{
    var i;
    for (i=0; i < this.length; i++) {
        // Matches identical (===), not just similar (==).
        if (this[i] === value) {
            return true;
        }
    }
    return false;
};


function tx_vjchat_addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      oldonload();
      func();
    }
  }
}