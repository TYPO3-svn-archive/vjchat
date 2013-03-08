
var tx_vjchat_pi1_js_chat_instance = null;

function tx_vjchat_pi1_js_chat() {

	/* ==================================================== = = = = = = */
	/* SECTION I:		CONFIGURATION  								*/
	/* --------------------------------------------- - - - - - - */

	this.configuration = "";

	// default or example configuration -- is not enough to run this script - most values should be set by the php script and the configuration property!
	this.roomId					= 0;
	this.userId					= 0;
	this.scriptUrl 				= "";
	this.leaveUrl 				= "";
	this.newWindowUrl 			= "";
	this.initialId 				= 0;
	this.ulhash					= '';
	this.charset 				= "iso-8859-1";
	this.debugMode				= "1";	
	this.lang 					= "de";		
	this.userColors				= Array('#CCCCCC','#000000','#3636B2','#2A8C2A','#C33B3B','#C73232','#80267F','#66361F','#D9A641','#3DCC3D','#1A5555','#2F8C74','#4545E6','#B037B0','#4C4C4C','#959595');
	this.colorizeNicks 			= true;
	this.showTime 				= true;
	this.showEmoticons 			= true;
	this.showStyles 			= true;
	this.refreshMessagesTime 	= 5000;				
	this.refreshUserlistTime 	= 10000;					
	this.inputElement 			= $('txvjchatnewMessage');
	this.messagesElement 		= $('tx-vjchat-messages');
	this.userListElement		= $('tx-vjchat-userlist');
	this.emoticonsElement		= $('tx-vjchat-emoticons');	
	this.stylesElement			= $('tx-vjchat-style');	
	this.storedMessagesElement  = $('tx-vjchat-storedMessages');
	this.debugElementMessages 	= $('tx-vjchat-debug-messages');
	this.debugElementUserlist 	= $('tx-vjchat-debug-userlist');
	this.debugElementTimers 	= $('tx-vjchat-debug-timers');
	tx_vjchat_pi1_js_chat_instance 	= "chat_instance";
	this.maxActiveRequests		= 3;
	this.JSdebug				= false;
	this.popup					= false;
	this.userlistPMContent		= "PM";
	this.userlistPRContent		= "PR";
	this.userlistPMInfo 		= "Send a private message to \'%s\'";
	this.userlistPRInfo 		= "Open a new room and invite \'%s\'";
	this.allowPrivateMessages	= true;
	this.allowPrivateRooms		= true;
	this.checkFullTime			= 20000;
	this.checkFullStatusElement = $('tx-vjchat-full-jsstatus');
	this.useSnippets			= true;
	this.snippetsError			= "An error occured during the chat. You should reload the chat window. Do you want to do this now?";
	this.tooltipOffsetX			= 20;
	this.tooltipOffsetY			= 10;
	this.autoFocus				= false;
	this.chatbuttonson 			= new Array();
	this.chatbuttonsoff 		= new Array();
	this.chatbuttonsonkeys 		= new Array();
	this.chatWindow				= window;
	this.soundSupportName		= "soundmanager2";
	this.enableSound			= true;
	this.soundSupport			= null;
	this.soundSupportOptions    = new Object();
	this.soundMessage			= null;
	this.soundUserlist			= null;
	this.currentChatStyle		= 0;

	var globalInstanceName = tx_vjchat_pi1_js_chat_instance;

	var tools = new tx_vjchat_pi1_js_lib();

	this.messageStack = new Array(); // collection of messages that will be send to server
	this.receivedMessages = new Array(); // collection of ids
	this.oldMessage = ""; 	// saves message for avoiding duplicates entries
	
	var userList = new Array();
	var userColorList = new Array();

	var self = null;
	tx_vjchat_pi1_js_chat_instance = this;

	/* ==================================================== = = = = = = */
	/* SECTION II:		MAIN/COMMON									*/
	/* --------------------------------------------- - - - - - - */

	this.init = function() {
		// apply configuration
		Object.extend(this, this.configuration);

		globalInstanceName = tx_vjchat_pi1_js_chat_instance;

		for(var i = 0;i<this.chatbuttonskeys.length;i++) {
			var name = this.chatbuttonskeys[i];
			var containerName = name+'-container';
			this.chatbuttonsoff[i] = $(containerName) ? $(containerName).innerHTML : '';
		}
		
		
		if(Cookie.get('tx-vjchat-emoticons_visible') != null) {
			var show = (Cookie.get('tx-vjchat-emoticons_visible') == '1');
			this.setEmoticons(show);
		}
		else 
			this.setEmoticons(this.showEmoticons);

		if(Cookie.get('tx-vjchat-style_visible') != null) {
			var show = (Cookie.get('tx-vjchat-style_visible') == '1');
			this.setStyle(show);
		}
		else
			this.setStyle(this.showStyles);
		
		if(Cookie.get('tx_vjchat_showtime') != null) {
			var show = Cookie.get('tx_vjchat_showtime') == '1';
			this.setAllTime(show);
		}
		else
			this.setAllTime(this.showTime );
		
		if(Cookie.get('tx_vjchat_colorizeNicks') != null) {
			var show = Cookie.get('tx_vjchat_colorizeNicks') == '1';
			this.setUserColor(show);
		}
		else
			this.setUserColor(this.colorizeNicks);

			
		if(Cookie.get('tx_vjchat_autofocus') != null) {
			var show = Cookie.get('tx_vjchat_autofocus') == '1';
			this.setAutoFocus(show);
		}
		else
			this.setAutoFocus(this.autoFocus);			

		if(Cookie.get('tx_vjchat_enablesound') != null) {
			var on = Cookie.get('tx_vjchat_enablesound') == '1';
			this.setEnableSound(on);
		}
		else {
			this.setEnableSound(this.enableSound);			
		}
		
		self = this;
		
	}
	
	/**
	  * Run chat
	  */
	this.run = function() {
		
		console.debug("Run chat: room:"+this.roomId+" initialId:"+this.initialId);
		
		// set current id to initial id
		this.id = this.initialId;
	
		Event.observe(this.inputElement, 'keyup', this.performNewMessageKeyPress);
		Event.observe(this.inputElement, 'keydown', this.performNewMessageKeyPress);		
		this.inputElement.focus();
		
		// get messages
		this.getMessages();
		
		// get userlist
		this.getUserlist();
		
	}

	this.debug = function(message) {
		if(this.JSdebug) {
			this.debugElementMessages.innerHTML += message.escapeHTML() + "<br>";
			this.debugElementMessages.scrollTop = this.debugElementMessages.scrollHeight;
		}
	}

	this.debugUserlist = function(message) {
		if(this.JSdebug) {
			this.debugElementUserlist.innerHTML += message.escapeHTML() + "<br>";
			this.debugElementUserlist.scrollTop = this.debugElementUserlist.scrollHeight;
		}
	}

	/*
	this.debugTimers = function(message, node) {
		if(this.JSdebug && message) {

			var newTimerNode = document.createElement("div");
			var attr = document.createAttribute("style");
			attr.nodeValue = "float:left;";
			newTimerNode.setAttributeNode(attr);
			
			newTimerNode.innerHTML = message.escapeHTML();
			this.debugElementTimers.appendChild(newTimerNode);
		}
	}	
	*/
	
	var handleAjaxError = function(t) {
		alert('Error ' + t.status + ' -- ' + t.statusText);
	}

    var handleAjax404 = function(t) {
        alert('Error 404: location "' + t.statusText + '" was not found.');
    }

	this.performNewMessageKeyPress = function(evt) {

		var values = tools.getKeyValues(evt);

		if ( ( (values['keyCode'] == 13) || (values['keyCode'] == 10) )  &&  (values['ctrlPressed'] == false) ) {
			//window.event.keyCode = 0;
			return self.submitMessage();
		}	

		if ( ( (values['keyCode'] == 13) || (values['keyCode'] == 10) )  &&  (values['ctrlPressed'] == true) ) {
			tools.insertAtCursor(self.inputElement, "\n");
		}	
		
	}


	this.setValueToInput = function(value, addAtIfFirst) {
		
		if((this.inputElement.value == "") && (addAtIfFirst)) {
			tools.insertAtCursor(this.inputElement, "@"+value+": ");
			return;
		}

		tools.insertAtCursor(this.inputElement, value);
		
		// set focus
		// $("txvjchatnewMessage").focus();
	}

	this.newWindow = function() {
		tx_vjchat_openNewChatWindow(this.newWindowUrl, this.roomId);
	}

	this.openChatWindow = function(chatId) {
		tx_vjchat_openNewChatWindow(this.newWindowUrl, chatId);
	}
	
	this.helpInNewWindow = function() {
		var message = escape(tools.urlEncode("/help"));
		var url = this.scriptUrl+'?r='+this.roomId+'&a=sm&charset='+this.charset+'&l='+this.lang+'&m='+message;

		var vHWindow = window.open(url,"helpwindow", this.popupJSWindowParams);
		vHWindow.focus();
	}
	
	/* ==================================================== = = = = = = */
	/* SECTION III:		MESSAGES	  								*/
	/* --------------------------------------------- - - - - - - */
	
	this.getMessages = function(noSetTimeout) {
		if(Ajax.activeRequestCount < this.maxActiveRequests)
			new Ajax.Request(
				this.scriptUrl, 
				{
					method:'get',
					parameters:'r='+this.roomId+'&a=gm&t='+this.id+'&charset='+this.charset+'&d='+this.debugMode+'&l='+this.lang,
					onSuccess:getMessagesResponseHandler,
					onFailure:handleAjaxError,
					on404:handleAjax404
				}
			);
		//console.debug("Request: "+this.scriptUrl+'?r='+this.roomId+'&a=gm&t='+this.id+'&charset='+this.charset+'&d='+this.debugMode+'&l='+this.lang);

		if(!noSetTimeout)
			window.setTimeout("tx_vjchat_pi1_js_chat_instance.getMessages()", this.refreshMessagesTime);
	}

	var getMessagesResponseHandler = function(t) {	
		self.parseMessages(t.responseText);
	}
	
	this.extractDebug = function(responseObject) {
		if(responseObject.debug)
			for (i = 0; i<responseObject.debug.length; i++) {
				console.debug(responseObject.debug[i]);
			}
		if(responseObject.timers) {
			
			var newTimerNode = document.createElement("div");
			var attr = document.createAttribute("style");
			attr.nodeValue = "float:left;";
			newTimerNode.setAttributeNode(attr);				
			
			for (i = 0; i<responseObject.timers.length; i++) {
				var timer = responseObject.timers[i];
				var message = timer.label+':'+timer.time;
				newTimerNode.innerHTML += message.escapeHTML()+'<br />';
			}
			if(this.debugElementTimers)
				this.debugElementTimers.appendChild(newTimerNode);
		}
	}
	
	this.htmlspecialchars = function(str,typ) {
		if(typeof str=="undefined") str="";
		if(typeof typ!="number") typ=2;
		typ=Math.max(0,Math.min(3,parseInt(typ)));
		var from=new Array(/&/g,/</g,/>/g);
		var to=new Array("&amp;","&lt;","&gt;");
		if(typ==1 || typ==3) {from.push(/'/g); to.push("&#039;");}
		if(typ==2 || typ==3) {from.push(/"/g); to.push("&quot;");}
		for(var i in from) str=str.replace(from[i],to[i]);
		return str;
	}

	this.parseString = function(string) {
		if(!string || string.length == 0)
			return null;
		console.debug('parseString: '+string);
			var responseDecoded = Base64.decode(string);
		console.debug('decodedString: '+responseDecoded);
		var responseObject = json_parse(responseDecoded);
		this.extractDebug(responseObject.chatresponse);
		return responseObject.chatresponse;
	}

	this.parseMessages = function(string) {
		
		var responseObject = this.parseString(string);
		
		if(responseObject == null || responseObject.messages == null)
			return;
		
		
		if(responseObject.id) {
			if(responseObject.id > 0) {
				this.id = responseObject.id;
			}
		}

		console.debug("id: "+this.id);
		console.debug("messages: "+responseObject.messages.length);

		for (i = 0; i<responseObject.messages.length; i++) {
			var text = tools.trimString(responseObject.messages[i]);
			console.debug('message'+i+': '+text);
			if(text == "/quit") {
				window.setTimeout("tx_vjchat_pi1_js_chat_instance.quit()", 1500);
			}
			else {
				this.createNewMessageNode(text);
			}
		}

	}

	this.quit = function() {
		if(this.popup)
			window.close();
		else
			window.location.href = this.leaveUrl;
	}
	
	this.notifyNewMessage = function() {
		if(this.autoFocus && this.popup) {
			this.chatWindow.focus();
		}
		if(this.enableSound) {
			this.soundSupport.play('message');
		}
	}
	
	this.notifyUserListChange = function() {
		if(this.enableSound) {
			this.soundSupport.play('userlist');
		}
	}
		

	
	/**
	  * This function adds a node to the chat window (element: "messages")
	  * It adds a "<div>" and put the message as HTML into it
	  */	 
	this.createNewMessageNode = function(message) {

		console.debug("message node: "+message);

		if(message == null)
			return;		
		
		var idsearch = message.match(/<div id="([a-z0-9]*)\"/i);

		if(idsearch && idsearch[1]) {
		
			var id = idsearch[1];
			
			if(this.receivedMessages.inArray(id))
				return;
			else
				this.receivedMessages[this.receivedMessages.length] = id;
		}
		
		if(message == "" )		// skip empty values
			return;

		var newMessageNode = document.createElement("div");
		newMessageNode.innerHTML = message;

		var systemsearch = message.match(/<div class=\"(.*?tx-vjchat-system.*?)\"/i);
		var useridsearch = message.match(/tx-vjchat-user tx-vjchat-userid-([0-9]*?)\"\>/i);
		
			// notify if not a system message and message from another user
		if(!(systemsearch && systemsearch[1]) && (useridsearch && useridsearch[1] && useridsearch[1] != this.userId))
			this.notifyNewMessage();
		
		var className = document.createAttribute("class");
		className.nodeValue = "tx-vjchat-entry";
		newMessageNode.setAttributeNode(className);

		this.colorizeNicknames(newMessageNode);
		
//		this.colorizeNicknames(newMessageNode);
		this.showHideTime(newMessageNode, this.showTime);

		// add node
		this.messagesElement.appendChild(newMessageNode);

		// scroll down
		this.messagesElement.scrollTop = this.messagesElement.scrollHeight;
		
	}

	/**
	  * Submits an entered string by call sendMessageToServer()
	  */
	this.submitMessage = function() {

		// get entered message
		var newMessage = self.inputElement.value;

		// clear input field
		self.inputElement.value = "";
		Element.cleanWhitespace(self.inputElement);
		
		if(tools.trimString(newMessage) == "undefined" || tools.trimString(newMessage) == "") {
			return;
		}

		// send message to server
		self.sendMessageToServer(tools.trimString(newMessage));

		window.setTimeout("tx_vjchat_pi1_js_chat_instance.getMessages(true)", 500);
		
		return false;
	}
	
	/**
	  * Send a string to server 
	  * It is possible that some error-report will be returned, so the XMLHttpRequest uses the same callback function as above.
	  * Therefore results will be treated like simple chat messages
	  */
	this.sendMessageToServer = function(message) {

		console.debug("--- Putting message on stack:");
		console.debug(message);		

		if(message.length > 0)
			this.messageStack.push(message);

		// request of this type is still not ready
		if(Ajax.activeRequestCount >= this.maxActiveRequests) {
			window.setTimeout("tx_vjchat_pi1_js_chat_instance.sendMessageToServer(\"\") ", 500);		// try again later
			return;
		}
		
		// get first message of array
		message = this.messageStack[0];

		// remove first element
		this.messageStack.reverse();
		this.messageStack.pop();
		this.messageStack.reverse();
		
		if(message) {
			console.debug("Sending message:");
			console.debug(message);	
			message = Base64.encode(message);
		
			
			if(Ajax.activeRequestCount < this.maxActiveRequests)
				new Ajax.Request(
					this.scriptUrl, 
					{
						method:'post',
						parameters:"r="+this.roomId+"&a=sm&t="+this.id+"&d="+this.debugMode+"&l="+this.lang+"&m="+message+"&charset="+this.charset,
						onSuccess:getMessagesResponseHandler,
						onFailure:handleAjaxError,
						on404:handleAjax404
					}
				);
		}				
		
		// call function again if stack has at least one element
		if(this.messageStack.length > 0) {
			window.setTimeout("tx_vjchat_pi1_js_chat_instance.sendMessageToServer()", 500);
			return;
		}		

	}
	
	this.sendMessage = function(message) {
		this.sendMessageToServer(message);
	}
	
	this.commitEntry = function(uid) {

		new Ajax.Request(
			this.scriptUrl, 
			{
				method:'get',
				parameters:"r="+this.roomId+"&t="+this.id+"&a=commit&uid="+uid,
				onSuccess:getMessagesResponseHandler,
				onFailure:handleAjaxError,
				on404:handleAjax404
			}
		);
			
		var messageNode = $("tx-vjchat-entry-"+uid);
		messageNode.getAttributeNode("class").nodeValue = "tx-vjchat-committed";

		window.setTimeout("tx_vjchat_pi1_js_chat_instance.hideEntry(" + uid + ") ", 1000);

	}

	this.hideEntry = function(uid) {
		var node = $("tx-vjchat-entry-"+uid).parentNode;
		node.parentNode.removeChild(node);

		if(this.storedMessagesElement.childNodes.length == 0) {
			this.toogleStoredMessages(false);
		}
		
	}

	this.storeEntry = function(uid) {
		
		this.toogleStoredMessages(true);
	
		var node = $("tx-vjchat-entry-"+uid).parentNode;
		this.storedMessagesElement.style.display = "block";
		this.storedMessagesElement.appendChild(node);

		// remove link storemessage		
		node.childNodes[1].removeChild($("tx-vjchat-storelink-"+uid));

		// scroll down
		this.storedMessagesElement.scrollTop = this.storedMessagesElement.scrollHeight;

	}
	
	
	this.toogleStoredMessages = function(show) {

		var storedMessages = this.storedMessagesElement;
		var messages = this.messagesElement;

		var isVisible = (storedMessages.style.display == "block");

		if(isVisible == show)
			return;

		var heightStyle = messages.style.height;

		result = heightStyle.match(/([0-9]*?)([a-z]{2}|\%)/i);
		var height = result[1];
		var hunit = result[2];		

		if(!show) {
			
			var newHeight = Math.round(height * 2);
			
			storedMessages.style.display = "none";
			messages.style.height = newHeight + hunit;
			messages.style.top = 0;
		} 
		else {
	
			var newHeight = Math.round(height / 2);
			storedMessages.style.height = newHeight-1 + hunit;
			storedMessages.style.display = "block";

			messages.style.height = newHeight + hunit;
			messages.style.top = newHeight + hunit;			
			
		}

	}	
	
	/* ==================================================== = = = = = = */
	/* SECTION IV:		USERLIST	  								*/
	/* --------------------------------------------- - - - - - - */

	this.getUserlist = function() {
		if(Ajax.activeRequestCount < this.maxActiveRequests)
			new Ajax.Request(
				this.scriptUrl, 
				{
					method:'get',
					parameters:'r='+this.roomId+'&ulhash='+this.ulhash+'&a=gu&charset='+this.charset+'&d='+this.debugMode+'&l='+this.lang,
					onSuccess:getUserlistResponseHandler,
					onFailure:handleAjaxError,
					on404:handleAjax404
				}
			);
//		window.setTimeout(tx_vjchat_pi1_js_chat_instance+".getUserlist()", this.refreshUserlistTime);
		window.setTimeout("tx_vjchat_pi1_js_chat_instance.getUserlist()", this.refreshUserlistTime);
	}

	var getUserlistResponseHandler = function(t) {
		self.parseUserlist(t.responseText);
	}

	this.parseUserlist = function(string) {
		
		var responseObject = this.parseString(string);
		
		if(!responseObject || responseObject.useritems == null)
			return;

		console.debug('new ulhash:'+responseObject.ulhash);
			
        // update only if something has changed
        if(this.ulhash == responseObject.ulhash)
			return;

		this.ulhash = responseObject.ulhash;
		
		// remove all nodes
		this.clearUserList();

		console.debug('userlist items: '+responseObject.useritems.length);
		
		// go through all messages and add them to the chat window by calling createNewMessageNode()
		for (i = 0; i<responseObject.useritems.length; i++) {
			this.createNewUserNode(responseObject.useritems[i]);
		}
		
		this.colorizeNicknames(this.userListElement);
		this.notifyUserListChange();
		
	}

	this.clearUserList = function() {
		tools.clearNode(this.userListElement);
	}
	
	this.createNewUserNode = function(userObject) {

		if(!userObject)		// skip empty values
			return;

		var type = userObject.type;
		var id = userObject.uid;
		var style = userObject.style;
		var username = userObject.username;
		
		/*
		if(this.useSnippets) {
			var userlistsnippet = userObject.userlistsnippet;
			var tooltipsnippet = userObject.tooltipsnippet;
			
			
			if((this.userId == id) && ((userlistsnippet == "") || (tooltipsnippet == "")))
				if(window.confirm(self.snippetsError))
					window.location.reload();			
			
		}*/
		
		/*
			create a node like:
			<div class="tx-vjchat-userlist-item tx-vjchat-userlist-[moderator|user|expert|superuse]">
				<span id="user-[id]">[username]</span> <span class="tx-vjchat-pm-link">[PM]</span> <span class="tx-vjchat-pr-link">[PR]</span>
			</div>
		
		*/
	
		var newUserNode = document.createElement("div");
		var classAtt = document.createAttribute("class");
		classAtt.nodeValue = "tx-vjchat-userlist-item tx-vjchat-userlist-"+type;
		newUserNode.setAttributeNode(classAtt);
		this.userListElement.appendChild(newUserNode);

		var newUsernameNode = document.createElement("span");
		var idAtt = document.createAttribute("id");
		idAtt.nodeValue = "user-"+id;
		newUsernameNode.setAttributeNode(idAtt);

		var classAtt = document.createAttribute("class");
		classAtt.nodeValue = "tx-vjchat-userlist-username tx-vjchat-userid-"+id+" tx-vjchat-message-style-"+style;
		newUsernameNode.setAttributeNode(classAtt);
		
		var userlistsnippet = userObject.userlistsnippet;
		var tooltipsnippet = userObject.tooltipsnippet;
		var additonalinformation = userObject.additonalinformation;
	
		if(self.useSnippets && userlistsnippet && (userlistsnippet.length > 0))
			newUsernameNode.innerHTML = userlistsnippet;
		else
			newUsernameNode.innerHTML = username;

		newUserNode.appendChild(newUsernameNode);

		Event.observe(newUserNode, 'click', 
			function(evt) { 
				Event.stop(evt);
				self.setValueToInput(username, true); 
			} 
		);


		Event.observe(newUserNode, 'mouseover', 
			function(evt) { 
				Event.stop(evt);
				var node = $('tx-vjchat-user-detail');
			
					// new fashion with snippets
				if(self.useSnippets && tooltipsnippet && (tooltipsnippet.length > 0)) {
					node.innerHTML = tooltipsnippet;
				}
				else {
						// old fashion				
					var body = $('tx-vjchat-user-detail-body');
						
					$('tx-vjchat-user-detail-caption').innerHTML = username;

					tools.clearNode(body);

					var classAtt = document.createAttribute("class");
					classAtt.nodeValue = "tx-vjchat-user-detail-"+type;
					body.setAttributeNode(classAtt);

					if(additonalinformation) {
	
						for(var i=0;i<additonalinformation.length;i++) {
							var newNode = document.createElement("p");
							newNode.innerHTML = additonalinformation[i];
							body.appendChild(newNode);
						
						}
					}
				}
				
				
				tools.moveToMousePosition(evt, node, self.tooltipOffsetX, self.tooltipOffsetY);
				
				//if(Scriptaculous)
					//Effect.Appear(node);
				//else
					node.show();
					//node.style.display = "block";
				
			} 
		);

		/*
		Event.observe(newUsernameNode, 'onmousemove', 
			function(evt) { 
				var node = $('tx-vjchat-user-detail');
				tools.moveToMousePosition(evt, node, self.tooltipOffsetX, self.tooltipOffsetY);
			}
		);
		*/
		
		newUserNode.onmousemove = this.performMouseMoveUserList;		
		newUserNode.onmouseout = this.performMouseOutUserList;

		if(this.userId == id)
			return;


		if(this.allowPrivateMessages || this.allowPrivateRooms) {
			var newLinkPMPRNode = document.createElement("span");
			var classAtt = document.createAttribute("class");
			classAtt.nodeValue = "tx-vjchat-link-box";
			newLinkPMPRNode.setAttributeNode(classAtt);
			newUserNode.appendChild(newLinkPMPRNode);					
		}
			
		if(this.allowPrivateMessages) {

			var newLinkPMNode = document.createElement("span");
			var classAtt = document.createAttribute("class");
			classAtt.nodeValue = "tx-vjchat-pm-link";
			newLinkPMNode.setAttributeNode(classAtt);
			newLinkPMNode.innerHTML = this.userlistPMContent;
			newLinkPMPRNode.appendChild(newLinkPMNode);		
		

			Event.observe(newLinkPMNode, 'mouseover', 
				function(evt) { 
					Event.stop(evt);
					var node = $('tx-vjchat-user-detail');
					var body = $('tx-vjchat-user-detail-body');
					tools.clearNode(body);
	
					var content = self.userlistPMInfo.replace(/\%s/, username);
					$('tx-vjchat-user-detail-caption').innerHTML = content;
	
					tools.moveToMousePosition(evt, node, self.tooltipOffsetX, self.tooltipOffsetY);
					
					//if(Scriptaculous)
					//	Effect.Appear(node);
					//else
						node.show();
				} 
			);
			
	
			Event.observe(newLinkPMNode, 'click', 
				function(evt) { 
					Event.stop(evt);
					var command = "/msg "+username+" ";
					self.insertCommand(command);
				} 
			);

			newLinkPMNode.onmousemove = this.performMouseMoveUserList;		
			newLinkPMNode.onmouseout = this.performMouseOutUserList;

		}


		if(this.allowPrivateRooms) {

			var newLinkPRNode = document.createElement("span");
			var classAtt = document.createAttribute("class");
			classAtt.nodeValue = "tx-vjchat-pr-link";
			newLinkPRNode.setAttributeNode(classAtt);
			newLinkPRNode.innerHTML = this.userlistPRContent;
			newLinkPMPRNode.appendChild(newLinkPRNode);				

				
			Event.observe(newLinkPRNode, 'mouseover', 
				function(evt) { 
					Event.stop(evt);
					var node = $('tx-vjchat-user-detail');
					var body = $('tx-vjchat-user-detail-body');
					tools.clearNode(body);
	
					var content = self.userlistPRInfo.replace(/\%s/, username);					
					$('tx-vjchat-user-detail-caption').innerHTML = content;
	
					tools.moveToMousePosition(evt, node, self.tooltipOffsetX, self.tooltipOffsetY);
					
					//if(Scriptaculous)
					//	Effect.Appear(node);
					//else
						node.show();
				} 
			);				
	
			Event.observe(newLinkPRNode, 'click', 
				function(evt) { 
					Event.stop(evt);
					var name = self.talkToNewRoomName.replace(/\%s/, username);
					var command = "/newroom "+name+"\n"+"/recentinvite "+username+" ";
					self.sendMessage(command);
	//				self.inputElement.value = command + self.inputElement.value;
				} 
			);
		
			newLinkPRNode.onmousemove = this.performMouseMoveUserList;		
			newLinkPRNode.onmouseout = this.performMouseOutUserList;

		}		
	}

	this.insertCommand = function(command) {
		self.inputElement.value = command + self.inputElement.value;
		self.inputElement.focus();
	}	

	/* USERLIST EVENTS */
	
	this.performMouseMoveUserList = function(evt) { 
		var node = $('tx-vjchat-user-detail');
		tools.moveToMousePosition(evt, node, self.tooltipOffsetX, self.tooltipOffsetY);
	} 

	this.performMouseOutUserList = function(evt) {
		var node = $('tx-vjchat-user-detail');
		node.hide();
	}
	
	/* ==================================================== = = = = = = */
	/* SECTION V:		TOOLS										*/
	/* --------------------------------------------- - - - - - - */
	
	this.setMessageStyle = function(number, send) {
		
		var element = $("tx-vjchat-style-btn-"+number);
		
		if(!element)
			return;
		
		var container = $("tx-vjchat-style");
		
		for(var i=0; i<container.childNodes.length;i++) {
			container.childNodes[i].style.border = "none";
		}
		
		if(this.currentChatStyle == number)
			number = 0;
		else
			element.style.border = "1px solid black";
			
		this.currentChatStyle = number;
		
		if(typeof send=="undefined" || send == true)
			this.sendMessageToServer("/setstyle "+number);
		
	}

	this.getNickColor = function(id) {
		if(!userColorList[id]) {
			userColorList[id] = this.userColors[Math.round(Math.random()*(this.userColors.length-1))];
		}
		return userColorList[id];
	}
	
	this.colorizeNickElement = function(element, id) {
		if(this.colorizeNicks) {
			var color = this.getNickColor(id);
			element.style.color = color;
		}
		else
			element.style.color = "";
	}

	this.colorizeNicknames = function(element) {

		var elements = tools.getElementsByClassName(element, "tx-vjchat-userid-");
		for(var i = 0; i < elements.length; i++) {
			var matches = elements[i].className.match("tx-vjchat-userid-([0-9]*)");
			this.colorizeNickElement(elements[i], matches[1]);
		}

	}
	
	this.toggleEnableSound = function() {
		this.setEnableSound(!this.enableSound);
	}
	
	this.setEnableSound = function(on) {
		this.enableSound = on;
		
		//alert("sound "+on);
		
		Cookie.set("tx_vjchat_enablesound", on ? '1' : '0', 100);
		
		if(on) {
			this.soundSupport = eval('new tx_vjchat_pi1_js_soundsupport_'+this.soundSupportName+'()');
			this.soundSupport.init(this.soundSupportOptions);
			this.soundSupport.load('message',this.soundMessage);
			this.soundSupport.load('userlist',this.soundUserlist);
		}
		else 
			this.soundSupport = null;
		
		this.setChatButton('tx-vjchat-button-enablesound', on);
		
	}
	
	
	this.toggleUserColor = function() {
		this.setUserColor(!this.colorizeNicks);
	}
	
	this.setUserColor = function(on) {
		this.colorizeNicks = on;
		this.colorizeNicknames(this.userListElement);
		this.colorizeNicknames(this.messagesElement);
		Cookie.set("tx_vjchat_colorizeNicks", on ? '1' : '0', 100);
		this.setChatButton('tx-vjchat-button-usercolors', on);
	}

	this.toggleEmoticons = function() {
		//this.toggleElement(this.emoticonsElement);
		this.setEmoticons(!(this.emoticonsElement.visible()));
	}
	
	this.setEmoticons = function(on) {
		if(on && !this.emoticonsElement.visible()) {
				this.emoticonsElement.show();
		}
		
		if(!on && this.emoticonsElement.visible()) { 
			if(typeof Scriptaculous == 'object')
				Effect.SwitchOff(this.emoticonsElement);
			else
				this.emoticonsElement.hide();
		}
		Cookie.set("tx-vjchat-emoticons_visible", on ? '1' : '0', 100);
		this.setChatButton('tx-vjchat-button-emoticons', on);
	}
	
	this.toggleStyle = function() {
		this.setStyle(!(this.stylesElement.visible()));
	}

	this.setStyle = function(on) {
		if(on && !this.stylesElement.visible())
			this.stylesElement.show();
		
		if(!on && this.stylesElement.visible()) {
			if(typeof Scriptaculous == 'object')
				Effect.SwitchOff(this.stylesElement);
			else
				this.stylesElement.hide();
		}
		Cookie.set("tx-vjchat-style_visible", on ? '1' : '0', 100);
		this.setChatButton('tx-vjchat-button-styles', on);
	}	

	this.toggleAllTime = function() {
		this.setAllTime(!this.showTime);
	}
	
	this.setAllTime = function (on) {
		this.showTime = on;
		var element = this.messagesElement;
		this.showHideTime(element, this.showTime);
		Cookie.set('tx_vjchat_showtime', this.showTime ? '1' : '0', 100);
		this.setChatButton('tx-vjchat-button-clock', on);
	}

	this.toggleAutoFocus = function() {
		this.setAutoFocus(!this.autoFocus);
	}	
	
	this.setAutoFocus = function(on) {
		
		this.autoFocus = on;
		Cookie.set("tx_vjchat_autofocus", on ? '1' : '0', 100);
		this.setChatButton('tx-vjchat-button-autofocus', on);
		
	}		
	
	this.setChatButton = function(name, on) {
		if(on)
			this.setChatButtonOn(name);
		else
			this.setChatButtonOff(name);
	}
	
	this.setChatButtonOn = function(name) {
		for(var i = 0;i<this.chatbuttonskeys.length;i++) {
			//alert('on: '+this.chatbuttonskeys[i]+' : '+name+ ' : '+this.chatbuttonson[i]);
			if(this.chatbuttonskeys[i] == name) {
				var containerName = name+'-container';
				//this.chatbuttonsoff[i] = $(containerName).innerHTML;
				if($(containerName)) {
					$(containerName).innerHTML = this.chatbuttonson[i];
				}
				break;
			}
		}
	}

	this.setChatButtonOff = function(name) {
		for(var i = 0;i<this.chatbuttonskeys.length;i++) {
			//alert('off: '+this.chatbuttonskeys[i]+' : '+name+ ' : '+this.chatbuttonsoff[i]);
			if(this.chatbuttonskeys[i] == name) {
				var containerName = name+'-container';
				if($(containerName)) {
					$(containerName).innerHTML = this.chatbuttonsoff[i];
				}
				break;
			}
		}
	}	
	
	this.showHideTime = function(element, showtime) {
		var elements = tools.getElementsByClassName(element, "tx-vjchat-time");
		for(var i = 0; i < elements.length; i++) {
			this.showHideTime(elements[i], this.showTime);
			if(!showtime)
				elements[i].style.display = "none";
			else
				elements[i].style.display = "inline";
		}
	}
	
	this.addSelText = function(start, end) {
		tools.addSelText(this.inputElement, start, end);
	}
	
	
	/*
	
	this.toggleElement = function(element) {
		var name = element.readAttribute('id');
		element.toggle();
		Cookie.set(name+"_visible", element.visible() ? '1' : '0', 100);
	}

	this.toggleElementByCookie = function(element) {
		var name = element.readAttribute('id');
		if(Cookie.get(name+"_visible") == '1')
			element.show();
		if(Cookie.get(name+"_visible") == '0')
			element.hide();
	}		
	*/

	this.doCheckFull = function() {
//		alert(this.scriptUrl+'?r='+this.roomId+'&a=checkfull');
		new Ajax.Request(
			this.scriptUrl, 
			{
				method:'get',
				parameters:'r='+this.roomId+'&a=checkfull',
				onSuccess:checkFullResponse,
				onFailure:handleAjaxError,
				on404:handleAjax404
			}
		);
		
	}

	this.checkFull = function(newTry) {
	
		if(this.checkFullTimeLeft <= 0) {

			if(this.checkFullStatusElement)
				this.checkFullStatusElement.innerHTML = "Checking...";

			this.doCheckFull();

		}
		else {

			if(!this.checkFullTimeLeft)
				this.checkFullTimeLeft = this.checkFullTime;
				
			this.checkFullTimeLeft = this.checkFullTimeLeft - 1000;
			
			if(this.checkFullStatusElement)
				this.checkFullStatusElement.innerHTML = Math.round(this.checkFullTimeLeft / 1000) + " s";
	
			window.setTimeout("tx_vjchat_pi1_js_chat_instance.checkFull(false)", 1000 );
		}
	}

	var checkFullResponse = function(t) {
		if(tools.trimString(t.responseText) == "notfull") {		
			if(self.checkFullStatusElement)
				self.checkFullStatusElement.innerHTML = "Free - reloading...";
			window.location.reload();
		}
		else {
			if(self.checkFullStatusElement) {
				self.checkFullStatusElement.innerHTML = "Still full";
			}
			self.checkFullTimeLeft = tx_vjchat_pi1_js_chat_instance.checkFullTime;
			window.setTimeout("tx_vjchat_pi1_js_chat_instance.checkFull(true)", 1000 );
		}
	}
	
	
	this.checkSnippets = function() {
		if(this.useSnippets) {
		
		}
	}	
}


if(!console) {
	var console = { 
		debug : function(message) {
			tx_vjchat_pi1_js_chat_instance.debug(message);
		} 
	}
}