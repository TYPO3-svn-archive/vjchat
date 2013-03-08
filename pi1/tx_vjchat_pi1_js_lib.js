// JavaScript Document
function tx_vjchat_pi1_js_lib() {
	
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
	  if(!sInString || sInString == null)
		return sInString;
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

/*
http://www.JSON.org/json_parse.js
2011-03-06

Public Domain.

NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.

This file creates a json_parse function.

json_parse(text, reviver)
This method parses a JSON text to produce an object or array.
It can throw a SyntaxError exception.

The optional reviver parameter is a function that can filter and
transform the results. It receives each of the keys and values,
and its return value is used instead of the original value.
If it returns what it received, then the structure is not modified.
If it returns undefined then the member is deleted.

Example:

// Parse the text. Values that look like ISO date strings will
// be converted to Date objects.

myData = json_parse(text, function (key, value) {
var a;
if (typeof value === 'string') {
a =
/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2}(?:\.\d*)?)Z$/.exec(value);
if (a) {
return new Date(Date.UTC(+a[1], +a[2] - 1, +a[3], +a[4],
+a[5], +a[6]));
}
}
return value;
});

This is a reference implementation. You are free to copy, modify, or
redistribute.

This code should be minified before deployment.
See http://javascript.crockford.com/jsmin.html

USE YOUR OWN COPY. IT IS EXTREMELY UNWISE TO LOAD CODE FROM SERVERS YOU DO
NOT CONTROL.
*/

/*members "", "\"", "\/", "\\", at, b, call, charAt, f, fromCharCode,
hasOwnProperty, message, n, name, prototype, push, r, t, text
*/

var json_parse = (function () {
    "use strict";

// This is a function that can parse a JSON text, producing a JavaScript
// data structure. It is a simple, recursive descent parser. It does not use
// eval or regular expressions, so it can be used as a model for implementing
// a JSON parser in other languages.

// We are defining the function inside of another function to avoid creating
// global variables.

    var at, // The index of the current character
        ch, // The current character
        escapee = {
            '"': '"',
            '\\': '\\',
            '/': '/',
            b: '\b',
            f: '\f',
            n: '\n',
            r: '\r',
            t: '\t'
        },
        text,

        error = function (m) {

// Call error when something is wrong.

            throw {
                name: 'SyntaxError',
                message: m,
                at: at,
                text: text
            };
        },

        next = function (c) {

// If a c parameter is provided, verify that it matches the current character.

            if (c && c !== ch) {
                error("Expected '" + c + "' instead of '" + ch + "'");
            }

// Get the next character. When there are no more characters,
// return the empty string.

            ch = text.charAt(at);
            at += 1;
            return ch;
        },

        number = function () {

// Parse a number value.

            var number,
                string = '';

            if (ch === '-') {
                string = '-';
                next('-');
            }
            while (ch >= '0' && ch <= '9') {
                string += ch;
                next();
            }
            if (ch === '.') {
                string += '.';
                while (next() && ch >= '0' && ch <= '9') {
                    string += ch;
                }
            }
            if (ch === 'e' || ch === 'E') {
                string += ch;
                next();
                if (ch === '-' || ch === '+') {
                    string += ch;
                    next();
                }
                while (ch >= '0' && ch <= '9') {
                    string += ch;
                    next();
                }
            }
            number = +string;
            if (!isFinite(number)) {
                error("Bad number");
            } else {
                return number;
            }
        },

        string = function () {

// Parse a string value.

            var hex,
                i,
                string = '',
                uffff;

// When parsing for string values, we must look for " and \ characters.

            if (ch === '"') {
                while (next()) {
                    if (ch === '"') {
                        next();
                        return string;
                    } else if (ch === '\\') {
                        next();
                        if (ch === 'u') {
                            uffff = 0;
                            for (i = 0; i < 4; i += 1) {
                                hex = parseInt(next(), 16);
                                if (!isFinite(hex)) {
                                    break;
                                }
                                uffff = uffff * 16 + hex;
                            }
                            string += String.fromCharCode(uffff);
                        } else if (typeof escapee[ch] === 'string') {
                            string += escapee[ch];
                        } else {
                            break;
                        }
                    } else {
                        string += ch;
                    }
                }
            }
            error("Bad string");
        },

        white = function () {

// Skip whitespace.

            while (ch && ch <= ' ') {
                next();
            }
        },

        word = function () {

// true, false, or null.

            switch (ch) {
            case 't':
                next('t');
                next('r');
                next('u');
                next('e');
                return true;
            case 'f':
                next('f');
                next('a');
                next('l');
                next('s');
                next('e');
                return false;
            case 'n':
                next('n');
                next('u');
                next('l');
                next('l');
                return null;
            }
            error("Unexpected '" + ch + "'");
        },

        value, // Place holder for the value function.

        array = function () {

// Parse an array value.

            var array = [];

            if (ch === '[') {
                next('[');
                white();
                if (ch === ']') {
                    next(']');
                    return array; // empty array
                }
                while (ch) {
                    array.push(value());
                    white();
                    if (ch === ']') {
                        next(']');
                        return array;
                    }
                    next(',');
                    white();
                }
            }
            error("Bad array");
        },

        object = function () {

// Parse an object value.

            var key,
                object = {};

            if (ch === '{') {
                next('{');
                white();
                if (ch === '}') {
                    next('}');
                    return object; // empty object
                }
                while (ch) {
                    key = string();
                    white();
                    next(':');
                    if (Object.hasOwnProperty.call(object, key)) {
                        error('Duplicate key "' + key + '"');
                    }
                    object[key] = value();
                    white();
                    if (ch === '}') {
                        next('}');
                        return object;
                    }
                    next(',');
                    white();
                }
            }
            error("Bad object");
        };

    value = function () {

// Parse a JSON value. It could be an object, an array, a string, a number,
// or a word.

        white();
        switch (ch) {
        case '{':
            return object();
        case '[':
            return array();
        case '"':
            return string();
        case '-':
            return number();
        default:
            return ch >= '0' && ch <= '9' ? number() : word();
        }
    };

// Return the json_parse function. It will have access to all of the above
// functions and variables.

    return function (source, reviver) {
        var result;

        text = source;
        at = 0;
        ch = ' ';
        result = value();
        white();
        if (ch) {
            error("Syntax error");
        }

// If there is a reviver function, we recursively walk the new structure,
// passing each name/value pair to the reviver function for possible
// transformation, starting with a temporary root object that holds the result
// in an empty key. If there is not a reviver function, we simply return the
// result.

        return typeof reviver === 'function' ? (function walk(holder, key) {
            var k, v, value = holder[key];
            if (value && typeof value === 'object') {
                for (k in value) {
                    if (Object.prototype.hasOwnProperty.call(value, k)) {
                        v = walk(value, k);
                        if (v !== undefined) {
                            value[k] = v;
                        } else {
                            delete value[k];
                        }
                    }
                }
            }
            return reviver.call(holder, key, value);
        }({'': result}, '')) : result;
    };
}());



/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/
 
var Base64 = {
 
	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
 
	// public method for encoding
	encode : function (input) {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = Base64._utf8_encode(input);
 
		while (i < input.length) {
 
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);
 
			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;
 
			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}
 
			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
 
		}
 
		return output;
	},
 
	// public method for decoding
	decode : function (input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
 
		while (i < input.length) {
 
			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));
 
			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;
 
			output = output + String.fromCharCode(chr1);
 
			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}
 
		}
 
		//alert(output);
 
		output = Base64._utf8_decode(output);
 
		return output;
 
	},
 
	// private method for UTF-8 encoding
	_utf8_encode : function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";
 
		for (var n = 0; n < string.length; n++) {
 
			var c = string.charCodeAt(n);
 
			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
 
		}
 
		return utftext;
	},
 
	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;
 
		while ( i < utftext.length ) {
 
			c = utftext.charCodeAt(i);
 
			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
 
		}
 
		return string;
	}
 
}