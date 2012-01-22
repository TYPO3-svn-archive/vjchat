

/**
  * This sound proxy works with soundmanager2 from Scott Schiller (schillmania.com)
  */

tx_vjchat_pi1_js_soundsupport_soundmanager2 = function() {
		
		var _enabled = true;
		
		var _sound = null;
		
		var self = this;

		var toLoad = new Array();
		
		this.init = function(options) {
			
			_sound = soundManager;
			
			_sound.onload = function() {
				//self.load(aId, aFile);
				for(var i = 0; i<toLoad.length; i++) {
					soundManager.createSound({id: toLoad[i].id,  url: toLoad[i].file,  autoLoad: true,  autoPlay: false});
				}
			}
			
			
		}
		
		this.load = function(aId, aFile) {
	
			if(!_sound._didInit) {
				var obj = new Object();
				obj.id = aId;
				obj.file = aFile;
				toLoad.push(obj);
				return;
			}
	
			_sound.createSound({id: aId,  url: aFile,  autoLoad: true,  autoPlay: false});
			
		}
		
		this.play = function(aId) {
			
			if(!_enabled)
				return;
			
			_sound.play(aId);
			
		}

		this.stop = function(aId) {
			
			_sound.stop(aId);
			
		}
		
		this.setEnabled = function(enabled) {
		
			_enabled = enabled;
			
		}
		
	}

