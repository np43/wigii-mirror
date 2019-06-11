/**
 *  This file is part of Wigii (R) software.
 *  Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
 *  
 *  Wigii is free software: you can redistribute it and/or modify it 
 *  under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, 
 *  or (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *  See the GNU General Public License for more details.
 *
 *  A copy of the GNU General Public License is available in the Readme folder of the source code.  
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2016  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

// helpers to embed javascript into xml documents without causing parsing errors due to reserved characters
window.sm = window.smaller = function(a,b){return a<b;};
window.gr = window.greater = function(a,b){return a>b;};
window.smeq = window.smallerOrEqual = function(a,b){return a<=b;};
window.greq = window.greaterOrEqual = function(a,b){return a>=b;};

/**
 * Wigii JS client
 * Created by CWE on October 19 2015
 * Modified by CWE on December 13 2018 to remove unused library WigiiStdFL and to add documentation tags on functions
 * @param Window window current browser window
 * @param JQuery $ depends on JQuery 1.8.x
 */
(function(window,$) {
	// Configuration options
	var wigiiApiOptions = undefined;
	if(window.wigiiApi && window.wigiiApi.options) wigiiApiOptions = window.wigiiApi.options;
	if(!wigiiApiOptions) wigiiApiOptions = {};
	
	/**
	 * Attaches a comment and some custom attributes to a given function.
	 * The comment or the attributes are stored in the wncdAttr object attached to the function 
	 * and can be used as meta information further down in the code.
	 * NCD attributes are not loaded by default. To load them wigiiApiOptions.loadNcdAttributes should be true.
	 *@param String|Function comment a comment describing the function, as a string or as a source code native comment wrapped into a function
	 *@param Function|Object f the function to which to add the NCD attributes. 
	 * Between first argument comment and last argument f, as many pairs key,value as needed can be inserted. These pairs key:value will be added to the attached wncdAttr object.
	 *@return Function returns f for chaining
	 */
	var wigiiNcdAttr = function(enable){var enabled=(enable==true); return function(comment,f) {
		if(enabled && f) {
			// extracts any list of pairs key,value
			var args = undefined;
			if(arguments.length > 2) {
				args = Array.prototype.slice.call(arguments);
				comment = args[0];
				f = args[args.length-1];
			}
			// extracts source code comment from wrapping function
			if($.isFunction(comment)) {
				comment = comment.toString().match(/(\/\*\*([^*]|[\r\n]|(\*+([^*\/]|[\r\n])))*\*+\/)/g);
				if(comment) comment = comment[0];				
			}
			// stores attributes
			var wncdAttr = f.wncdAttr || {};
			if(comment) wncdAttr.comment = comment;
			if(args && args.length>0) {
				var i=0; var key,value;
				while(i<args.length) {
					key = args[i]; i++;
					if(i<args.length) {
						value = args[i]; i++;
					}
					if(key && value!==undefined) wncdAttr[key] = value;
				}
			}
			f.wncdAttr = wncdAttr;			
		}
		return f;
	}};
	var ncddoc = wigiiNcdAttr(wigiiApiOptions.loadNcdAttributes);
	
	/**
	 * Wigii JS API
	 */
	var WigiiApi = function() {
		var wigiiApi = this;
		wigiiApi.instantiationTime = (new Date()).getTime();
		wigiiApi.ctxKey = 'Wigii_'+wigiiApi.instantiationTime;
		/**
		 * Object which holds inner private mutable state variables.
		 */
		wigiiApi.context = {};
		
		// Configuration
		
		wigiiApi.holdJsCodeAnswers = function(bool) {
			if(bool===undefined) return wigiiApi.context.holdJsCodeAnswers;
			else wigiiApi.context.holdJsCodeAnswers = bool;
		};
		
		// Error codes
		
		wigiiApi.errorCodes = {
				
			// ServiceException (from 1000 to 1099 + http error codes)
				
			/**
			 * unauthorized user, http equivalent
			 */
			UNAUTHORIZED: 401,
			/**
			 * access forbidden, http equivalent
			 */
			FORBIDDEN: 403,
			/**
			 * not found, http equivalent
			 */
			NOT_FOUND: 404,
			/**
			 * default error
			 */
			UNKNOWN_ERROR: 1000,
			/**
			 * development error, indicates that a method is still not implemented but will be in a short term
			 */
			NOT_IMPLEMENTED: 1001,
			/**
			 * indicates that nested (or previous) exception exists.
			 */
			WRAPPING: 1002,
			/**
			 * indicates that this operation is not supported by this implementation.
			 */
			UNSUPPORTED_OPERATION: 1003,
			/**
			 * indicates that a method argument is invalid
			 */
			INVALID_ARGUMENT: 1004,
			/**
			 * unexpected error, that should never happen.
			 */
			UNEXPECTED_ERROR: 1005,
			/**
			 * database is dirty with corrupted or invalid data
			 */
			DATA_INTEGRITY_ERROR: 1006,
			/**
			 * deprecated method
			 */
			DEPRECATED: 1007,
			/**
			 * invalid state of object
			 */
			INVALID_STATE: 1008,
			/**
			 * Indicates that there is a problem in the configuration of the system
			 */
			CONFIGURATION_ERROR: 1009,
			/**
			 * Indicates that the current operation has been explicitely canceled by the service
			 * The service can provide a retryAfterCancel method.
			 */
			OPERATION_CANCELED: 1010,
			
			
			// ListException (error code range from 3100 to 3199)
			
			OUT_OF_BOUND: 3100,
			ALREADY_EXISTS: 3101,
			DOES_NOT_EXIST: 3102,
			
			// FuncExpEvalException (error code range from 4600 to 4699)
			
			SYMBOL_NOT_FOUND: 4600,
			INVALID_RETURN_VALUE: 4601,
			VARIABLE_NOT_DECLARED: 4602,
			DIVISION_BY_ZERO: 4603,
			ASSERTION_FAILED: 4604
			
		};
		// initializes error labels
		wigiiApi.initializeErrorLabels = function() {
			if(!wigiiApi.errorLabels) {
				wigiiApi.errorLabels = {};
				for(var errName in wigiiApi.errorCodes) {
					wigiiApi.errorLabels[wigiiApi.errorCodes[errName]] = errName;
				}
			}			
		};
		wigiiApi.initializeErrorLabels();
		
		// Exceptions
		
		ncddoc(function(){/**
		 * ServiceException class
		 * @param String message the error message
		 * @param Number code the error code
		 * @param Object previous if defined, the previous exception in the chain if wrapping.
		*/},
		wigiiApi.ServiceException = function(message,code,previous) {			
			var self = this;
			self.name = 'ServiceException';
			self.message = message;
			self.code = code || wigiiApi.errorCodes.UNKNOWN_ERROR;
			self.previousException = previous; 
		});
		
		// Classes
		
		ncddoc(function(){/**
		 * Debugger core technical service
		*/},
		wigiiApi.DebugLogger = function(typeName) {
			var self = this;
			self.attachedClass = typeName;
			self.enabled = true;
			self.write = function(message) {
				if(self.enabled) wigiiApiConsole().debug("DEBUG "+self.attachedClass+" : "+message);
			};
			self.writeIf = function(condition,message) {
				if(self.enabled && condition) wigiiApiConsole().debug("DEBUG "+self.attachedClass+" : "+message);
			};
			self.logBeginOperation = function(operation) {self.write("BEGIN "+operation);};
			self.logEndOperation = function(operation) {self.write("END "+operation);};
		});
		
		ncddoc(function(){/**
		 * Exception sink core technical service
		*/},
		wigiiApi.ExceptionSink = function() {
			var self = this;
			self.publish = function(exception) {
				// goes up to root exception
				while($.type(exception) === 'object' && 
					exception.code == wigiiApi.errorCodes.WRAPPING && exception.previousException) {
					exception = exception.previousException;
				}
				var exceptionType = $.type(exception);
				if(exceptionType === 'object') {
					name = exception.name || 'ServiceException';
					code = exception.code || wigiiApi.errorCodes.UNKNOWN_ERROR;
					message = exception.message || 'No message';
				}				
				else if(exceptionType === 'array') {
					name = 'ServiceException';
					code = wigiiApi.errorCodes.UNKNOWN_ERROR;
					message = 'Array';
				}
				else if(exception) {
					name = 'ServiceException';
					code = wigiiApi.errorCodes.UNKNOWN_ERROR;
					message = exception;
				}
				else {
					name = 'ServiceException';
					code = wigiiApi.errorCodes.UNKNOWN_ERROR;
					message = 'UNKNOWN_ERROR';
				}
				var errorMessage = "EXCEPTION "+name+"   code:"+code+"\n"+message;
				try {wigiiApiConsole().error(errorMessage);}catch(e){alert(errorMessage+"\n\n(note: using browser alert because WigiiAPIConsole is not working)");}				
			};
		});
				
		
		ncddoc(function(){/**
		 * Creates a popup window attached to the given anchor element
		 * @param jQuery|DOM.Element anchor the element to which attach the popup window
		 * @param Object options a map of configuration options for the popup window:
		 * {
		 *  id: html ID to attach to the pop-up,
		 *  classId: class used as an html ID to attach to the pop-up,
		 *  title: String used as a pop-up title,
		 *  type: undefined or 'help','info','warning','error','notif'
		 *  relativeToAnchor: boolean (if true, then top and left are relative to anchor, else relative to window)
		 *  top: default y position,
		 *  left: default x position,  
		 *  width: default width in px, 
		 *  height: default height in px, 
		 *  resizable: boolean (if true, then resizable and movable popup, else fix popup),
		 *  closeable: boolean (if true, adds a small x button on top-right to hide the popup).
		 *  removeOnClose: boolean. If true and closeable, then closing the popup will remove it from the DOM, else only closes the popup. Defaults to false.
		 * }
		*/},
		wigiiApi.PopUp = function(anchor,options) {
			var self = this;
			if(!anchor) anchor = $('body');
			else anchor = $(anchor);
			if(!options) options = {};
			if(options['resizable'] !== false) options.resizable=true;
			if(options['closeable'] !== false) options.closeable=true;
			/**
			 * Shows the popup 
			 * or registers an event handler on show event
			 * @param Function eventHandler event handler to register for show event
			 * @return Popup for chaining
			 */
			self.show = function(eventHandler) {
				// eventHandler registration
				if($.isFunction(eventHandler)) {
					if(!self.showEventSubscribers) self.showEventSubscribers = [];
					self.showEventSubscribers.push(eventHandler);
				}
				// show popup
				else if(self['popupElt']) {
					$(self.popupElt).show();
					if(!self.isVisible) {
						self.isVisible = true;
						// if the popup is relative to anchor we add overflow to  ensure popup is displayed
						if(self.isRelativeToAnchor) anchor.parent().css('overflow','visible');
						// reset default position and dimension if resetOnShow
						if(self.resetOnShow && self.defaultOptions) {
							var w = self.window();
							w.css('top',self.defaultOptions.top).css('left',self.defaultOptions.left);
							if(self.defaultOptions.width && self.defaultOptions.height) {
								w.css('height',self.defaultOptions.height+20).css('width',self.defaultOptions.width).resize();
							}; 
							self.body().scrollTop(0).scrollLeft(0);
						}
						// notifies all event subscribers
						if(self.showEventSubscribers) {
							for(var i=0;i<self.showEventSubscribers.length;i++) {
								var handler = self.showEventSubscribers[i];
								if($.isFunction(handler)) handler(self);
							}
						}
					}					
				}
				return self;
			};
			/**
			 * Hides the popup (but still keeps it attached to the DOM)
			 * or registers an event handler on hide event
			 * @param Function eventHandler event handler to register for show event
			 * @return Popup for chaining
			 */
			self.hide = function(eventHandler) {
				// eventHandler registration
				if($.isFunction(eventHandler)) {
					if(!self.hideEventSubscribers) self.hideEventSubscribers = [];
					self.hideEventSubscribers.push(eventHandler);
				}
				// else hides popup
				else if(self['popupElt']) {
					$(self.popupElt).hide();
					if(self.isVisible) {
						self.isVisible=false;
						// if the popup is relative to anchor we remove overflow for fix a display bug
						if(self.isRelativeToAnchor) anchor.parent().css('overflow','');
						// notifies all event subscribers
						if(self.hideEventSubscribers) {
							for(var i=0;i<self.hideEventSubscribers.length;i++) {
								var handler = self.hideEventSubscribers[i];
								if($.isFunction(handler)) handler(self);
							}
						}
					}					
				}
				return self;
			};
			/**
			 * Toggles the visibility of the popup
			 */
			self.toggle = function() {
				if(self.isVisible) self.hide();
				else self.show();
			};
			/**
			 * Removes the popup window from the DOM
			 * or registers an event handler on remove event
			 * @param Function eventHandler event handler to register for show event
			 * @return Popup for chaining
			 */
			self.remove = function(eventHandler) {
				// eventHandler registration
				if($.isFunction(eventHandler)) {
					if(!self.removeEventSubscribers) self.removeEventSubscribers = [];
					self.removeEventSubscribers.push(eventHandler);
				}
				else if(self['popupElt']) {
					$(self.popupElt).remove();
					self.isVisible=false;
					delete self.popupElt;
					// notifies all event subscribers
					if(self.removeEventSubscribers) {
						for(var i=0;i<self.removeEventSubscribers.length;i++) {
							var handler = self.removeEventSubscribers[i];
							if($.isFunction(handler)) handler(self);
						}
					}
				}
				return self;
			};
			/**
			 * Sets the html of the popup body.
			 * @param String|Function htmlString the html string to use as html content for this popup 
			 */
			self.html = function(htmlString) {
				var b = self.body();
				if(b) {
					// if we have an html emitting function, call it on the popup body.
					if($.isFunction(htmlString)) htmlString(b,self)
					// else we already have a built in html string
					else b.html(htmlString);
					// fixes body height					
					if(b.children().size()>0) {
						b.css('height',b.height()+1);						
					}
				}
				// records default width and height if not yet calculated
				if(b.children().size()>0 && self.defaultOptions && !self.defaultOptions.width && !self.defaultOptions.height) {					
					var w = self.window();
					self.defaultOptions.width = w.width();
					self.defaultOptions.height = w.height();
				}
			};
			/**
			 * Returns the DIV jQuery selector containing the pop-up window
			 * @return jQuery 
			 */
			self.window = function() {
				if(self['popupElt']) return $(self.popupElt);
			};
			/**
			 * Returns the DIV jQuery selector containing the pop-up html body
			 * @return jQuery
			 */
			self.body = function() {
				if(self['popupElt']) return $(self.popupElt).find('div.popupBody');
			};
			
			var insertIntoAnchor = anchor.is('body')||(options['domInsertionMode']=='append');
			// if insertIntoAnchor then an ID should exist, generates one if needed.
			if(insertIntoAnchor && !options['id'] && !options['classId']) {
				options['id'] = 'popup_'+(new Date()).getTime();
			}

			if(!options.referenceWindow) options.referenceWindow = $(window);			
			var popupSelector = (options['id']?'#'+options.id:(options['classId']?'.'+options.classId:undefined));
			var title = options['title'];
			var width = options['width'] || Math.round(options.referenceWindow.width()/3);			
			var height = options['height'] || Math.round(options.referenceWindow.height()/3);
			var top = options['top'] ||  Math.max(0,(options.referenceWindow.height()-height)/2);
			var left = options['left'] || Math.max(0,(options.referenceWindow.width()-width)/2);		
			
			// if displays relative to anchor
			var popupPosition = 'fixed';
			if(options['relativeToAnchor'] && !anchor.is('body')) {
				// computes anchor position relative to parent
				var anchorOffset = anchor.offset();
				var parentOffset = anchor.parent().offset();
				// adapts top and left to handle parent coordinates
				top = top+(anchorOffset.top-parentOffset.top);
				left = left+(anchorOffset.left-parentOffset.left);
				// fixes anchor parent position
				anchor.parent().css('position','relative').css('overflow','visible');
				popupPosition='absolute';
				if(!self.isRelativeToAnchor) self.isRelativeToAnchor = true;
			}
			
			var popupHtml = '<div';
			if(options['id']) popupHtml+=' id="'+options['id']+'"';
			popupHtml += ' class="'+(options['classId']&&!options['id']?options['classId']:'')+' ui-corner-all ui-widget ui-dialog SBIB"';
			popupHtml += ' style="cursor:default;z-index:998;position:'+popupPosition+';background-color:#fff;border-style:solid;border-width:1px;top:'+top+'px;left:'+left+'px;padding:5px;width:'+width+'px;height:auto;max-height:'+height+'px;display:none;float:none;border-color:black;"';
			popupHtml +='>';
			if(title) popupHtml +='<div class="popupTitle ui-corner-all ui-widget-header" style="'+(options['resizable']?'cursor:move;':'')+'float:left;font-style:normal;font-weight:bold;font-size:small;text-align:left;padding-left:13px;padding-right:0;padding-top:5px;padding-bottom:5px;margin:0;color:black;min-height:14px;width:'+(width-13)+'px" >'+title+'</div>';
			else popupHtml +='<div class="popupTitle emptyTitle ui-corner-all" style="'+(options['resizable']?'cursor:move;':'')+'float:left;z-index:999;position:absolute;right:4px;top:-10px;font-style:normal;font-weight:bold;font-size:small;text-align:left;padding-left:13px;padding-right:0;padding-top:5px;padding-bottom:5px;margin:0;color:black;height:14px;width:'+(width-10)+'px" >&nbsp;</div>';
			if(options['closeable']) {
				if(title) popupHtml += '<div class="exit ui-corner-all" style="z-index:999;position:absolute;right:6px;top:8px;cursor:pointer;width:15px;height:17px;float:right;text-align:center;vertical-align:middle;color:black;font-weight:bold;font-style:normal;font-size:small;padding:0;margin:0">x</div>'; 
				else popupHtml += '<div class="exit ui-corner-all SBIB" style="z-index:999;position:absolute;right:-2px;top:-3px;cursor:pointer;width:15px;height:17px;float:right;background-color:#fff;border-style:solid;border-width:1px;text-align:center;vertical-align:middle;color:black;font-weight:bold;font-style:normal;font-size:small;padding:0;margin:0;border-color:black">x</div>';
			}
			popupHtml +='<div class="clear"></div>';
			popupHtml +='<div class="popupBody" style="z-index:998;cursor:default;float:left;font-weight:normal;font-style:normal;font-size:small;text-align:left;padding:0;margin:0;margin-top:1px;color:black;overflow-y:auto;height:auto;max-height:'+(title?height-40:height-8)+'px;width:'+(width-9)+'px;"><br/></div></div>';
						
			// if insert into anchor
			if(insertIntoAnchor) {
				anchor.find(popupSelector).remove();
				anchor.append(popupHtml);
				self.popupElt = anchor.find(popupSelector);
			}
			// else appends to anchor
			else {
				if(popupSelector) anchor.parent().find(popupSelector).remove();
				anchor.after(popupHtml);
				self.popupElt = anchor.next();
			}	
			// saves position and dimension options if resetOnShow
			if(options['resetOnShow']) {
				self.defaultOptions = {top:top,left:left};
				// only records size if popup is not resizable, 
				// because otherwise height is dynamically calculated by the browser when showing.
				if(!options['resizable']) {
					self.defaultOptions.width = width;
					self.defaultOptions.height = height;
				}
				self.resetOnShow=true;
			}
			// resizable and draggable
			if(options['resizable']) {
				self.popupElt.draggable({handle:'.popupTitle'}).resizable().resize(function(event){
					event.stopPropagation();
					$(this).css('min-width','0').css('min-height','0').css('max-width','none').css('max-height','none');
					var body = $(this).find('div.popupBody');
					body.css('min-width','0').css('min-height','0').css('max-width','none').css('max-height','none').width($(this).width()-5);
					var title = $(this).find('div.popupTitle');
					if(title.hasClass('emptyTitle')) {
						title.width($(this).width()-10);
						body.height($(this).height()-8);
					}
					else {
						title.width($(this).width()-13);
						body.height($(this).height()-28);
					}
				});				
			}			
			// close event handler			
			if(options['closeable']) {
				var closeHandler = undefined;
				if(options['removeOnClose']) closeHandler = function(event){self.remove();event.stopPropagation();};
				else closeHandler = function(event){self.hide();event.stopPropagation();};
				self.popupElt.find('div.exit').click(closeHandler);
			}
			// restores defaults mouseDown event on popupBody to allow scrolling
			self.body().mousedown(function(event){event.stopPropagation();});
		});
		
		ncddoc(function(){/**
		 * Wigii Help Service
		 * Shows some contextual help in popup windows or displays contextual messages according to current flow.
		*/},
		wigiiApi.HelpService = function() {
			var self = this;
			self.className = 'HelpService';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			self.debugLogger = wigiiApi.getDebugLogger(self.className);
			
			/**
			 * HTML event handler
			 */
			self.html = function(htmlString,context) {
				if(context && context.popup) {
					context.popup.html(htmlString);
					context.popup.show();
				}
			};
			
			/**
			 * JSON event handler
			 * @param Object object the JSON response already parsed into an object
			 */
			self.json = function(object,context) {
				// ignore scalar values
				if(!$.isPlainObject(object)) return;
				// if exception, then displays the error
				else if(object['exception']) {					
					self.html(wigiiApi.exception2html(object['exception'],object['context']),context);					
				}
				// if notification, then adds notification to target
				else if(object['notification']) {
					self.showNotification(object['notification']);
				}
				// else nothing to do.
			};
			
			/**
			 * Shows a help popup attached to a given anchor
			 * @param String content the help content (local content or remote url)
			 * @param jQuery anchor the jQuery selector on which to fix a popup
			 * @param options the help and popup options
			 */
			self.showHelp = function(content,anchor,options) {
				//We go back to the tree node 'scrollElement' if not found we are in html node
				options.referenceWindow = anchor.parentsUntil('#scrollElement').last().parent();
				//If we didn't find node 'scrollElement' we try to find 'elementDialog'
				if(options.referenceWindow.attr('id')!='scrollElement' || isWorkzoneViewDocked()) options.referenceWindow =  anchor.parentsUntil('#elementDialog').last().parent();
				var context = anchor.data(self.ctxKey);
				if(!context) {
					context = {};
					// popup positioning
					if(!options.top && !options.left) {						
						var anchorOffset = anchor.offset();
						var scrollLeft = options.referenceWindow.scrollLeft();
						var scrollTop = options.referenceWindow.scrollTop();
						// if reference window is a dialog, then the scroll is in fact the dialog position in the page
						var refWindowOffset = options.referenceWindow.offset();						
						if(refWindowOffset && (refWindowOffset.top > 0 || refWindowOffset.left > 0)) {
							scrollLeft = refWindowOffset.left;
							scrollTop = refWindowOffset.top;
						}						
						// takes max available window height if position is S,SE or SW, 1/3 window height if position is center.
						switch(options.position) {
						case 'SE':
						case 'S':
						case 'SW':							
							options.height = Math.max(options.height, options.referenceWindow.height()-(anchorOffset.top-scrollTop)-options.offset-15);
							break;
						case 'center':
							options.height = Math.max(options.height,Math.floor(options.referenceWindow.height()/2));
							break;
						}
						wigiiApi.positionBox({pageX:anchorOffset.left,pageY:anchorOffset.top}, options, options);
						// make position relative to anchor
						options.top = options.top-(anchorOffset.top-scrollTop);				
						options.left = options.left-(anchorOffset.left-scrollLeft);
						options.relativeToAnchor=true;						
					}
					else {
						options.relativeToAnchor=false;
					};
					// popup creation					
					context.popup = wigiiApi.createPopUpInstance(anchor,options);
					
					// if type == 'help' we would have only one popup  
					// (this initialization on show/hide events should be done before first call of show)
					if (options.type == 'help') {
						fh = anchor.parents('div.field').first().wigii('FieldHelper');
						if(fh.ctxKey) {
							fh = fh.formHelper();
							context.popup.show(fh.onHelpPopupShow);
							context.popup.hide(fh.onHelpPopupHide);
						}						
					};
					
					
					// shows popup first
					context.popup.show();
					// fills content after to resize height correctly based on content
					if(options.localContent) {
						context.popup.html(content);
					}
					else {
						$.ajax({url:wigiiApi.buildUpdateUrl(content),
							cache:false,
							success:wigiiApi.buildUpdateCallback(context)
						});
					}
					// reposition the window now we know its size.
					if(options.relativeToAnchor) {
						var w = context.popup.window();
						options.height = w.height();
						options.width = w.width();
						wigiiApi.positionBox({pageX:anchorOffset.left,pageY:anchorOffset.top}, options, options);
						options.top = options.top-(anchorOffset.top-scrollTop);								
						if(context.popup.defaultOptions) context.popup.defaultOptions.top = options.top;
						w.css('top',Math.ceil(options.top));
					}
					anchor.data(self.ctxKey,context);
					// removes anchor on close if needed
					if(options['removeOnClose']) context.popup.remove(function(){anchor.remove();});
					
				}	
				else context.popup.show();
			};
			
			/**
			 * Toogles visibility of a help popup attached to a given anchor.
			 * If previously displayed, then hides it, else displays it.
			 * @param String content the help content (local content or remote url)
			 * @param jQuery anchor the jQuery selector on which to fix a popup
			 * @param options the help and popup options
			 */
			self.toggleHelp = function(content,anchor,options) {
				var context = anchor.data(self.ctxKey);
				if(context) context.popup.toggle();
                else self.showHelp(content,anchor,options);
			};
			
			/**
			 * Shows a floating help popup displayed on the mouse event coordinates.
			 * @param jQuery container jQuery selector on the element which will contain the help popup
			 * @param jQuery.Event mouseEvent mouse event (click,mousedown, ...)
			 * @param String content the help content (local content or remote url)
			 * @param options the help and popup options
			 */
			self.showFloatingHelp = function(container,mouseEvent,content,options) {
				if(!container) container = $('body');
				if(!options) options = {};
				if(!options['type']) options.type = 'help';
				if(!options['width']) options.width=400;
				if(!options['height']) options.height=400;
				if(!options['position'] && !options.top && !options.left) options.position='SE';
				if(!options['offset'] && !options.top && !options.left) options.offset=0;
				options.removeOnClose=true; // removes popup when it is closed.
				options.domInsertionMode = 'append'; // inserts popup in the container
				// popup positioning
				if(!options.top && !options.left) {
					// takes max available window height if position is S,SE or SW, 1/3 window height if position is center.
					switch(options.position) {
					case 'SE':
					case 'S':
					case 'SW':	
						if(mouseEvent) options.height = Math.max(options.height, $(window).height()-(mouseEvent.pageY-$(window).scrollTop())-options.offset-15);
						else options.height = Math.max(options.height,Math.floor($(window).height()/2));
						break;
					case 'center':
						options.height = Math.max(options.height,Math.floor($(window).height()/2));	
						break;
					}				
					wigiiApi.positionBox(mouseEvent,options,options);
				}
				// popup creation
				var context = {};
				context.popup = wigiiApi.createPopUpInstance(container,options);
				// shows popup first
				context.popup.show();
				// fills content after to resize height correctly based on content
				if(options.localContent) {					
					context.popup.html(content);
				}
				else {
					$.ajax({url:wigiiApi.buildUpdateUrl(content),
						cache:false,
						success:wigiiApi.buildUpdateCallback(context)
					});
				}
			};
			
			/**
			 * Renders a help anchor to which a help popup is attached.
			 * @param jQuery container jQuery selector on the containing element to which append the help anchor.
			 * @param String content the help content
			 * @param Object options the help options
			 */
			self.renderHelpAnchor = function(container,content,options) {
				if(!container) return;
				if(!options) options = {};
				if(!options['type']) options.type = 'help';
				if(!options['domInsertionMode']) options.domInsertionMode = 'append';
				if(!options['width']) options.width=400;
				if(!options['height']) options.height=400;
				if(!options['position'] && !options.top && !options.left) options.position='SE';
				if(!options['offset'] && !options.top && !options.left) options.offset=15;
				options.resetOnShow = true; // resets the popup to default position and dimension each time it is shown.
				var ctxKey = self.ctxKey+'_'+options.type;
				// creates helpAnchor html
				var helpAnchor = '';
				switch(options.type) {
				case 'help':
					helpAnchor = 'helpAnchor';
					break;				
				case 'warning':
					helpAnchor = 'warningAnchor';
					break;	
				case 'error':
					helpAnchor = 'errorAnchor';
					break;
				case 'notif':
					helpAnchor = 'notifAnchor';
					break;
				case 'search':
					helpAnchor = 'searchAnchor';
					break;
				case 'info':
				default:
					helpAnchor = 'infoAnchor';
				}
				// creates helpSpan and inserts it into the DOM
				var helpSpan = '<a class="'+ctxKey+' HelpService '+helpAnchor+'"></a>';
				var neighbor = undefined;
				switch(options.domInsertionMode) {
				case 'append':
					container.append(helpSpan);
					helpSpan = container.find('a.'+ctxKey);
					neighbor = helpSpan.prev();
					if(neighbor.is('a')) neighbor = helpSpan.prevUntil('div,span,select').last().prev();
					break;
				case 'after':
					container.after(helpSpan);
					helpSpan = container.next('a.'+ctxKey);
					break;
				default:
					helpSpan = undefined;
				}
				
				if(helpSpan) {
					// ajusts neighbor width to allow displaying help span					
					if(neighbor) {						
						var w = neighbor.width();
						// special case for select2
						if(neighbor.hasClass("flex")||neighbor.hasClass("chosen")) {
							// checks if we have a fixed width
							var cssW = neighbor.css("width");
							if(cssW && cssW.indexOf("%")>=0) cssW=undefined;
							// else takes max-width
							if(!cssW) cssW = neighbor.css("max-width");
							// else takes parent max-width
							if(!cssW) cssW = neighbor.parent().css("max-width");
							if(cssW) {
								try{w = Number.parseInt(cssW.replace('px',''))-5;} catch(exc){/* nothing to change */}							
							}
						}
						if(w>=75) {
							// forces resize of element
							neighbor.width(w-25);
							neighbor.css("width",(w-25)+"px").resize();							
						}						
						neighbor.children('div,span,select').each(function(){
							var e = $(this);
							w = e.width();
							if(w>=75) {
								// forces resize of element
								e.width(w-25);
								e.css("width",(w-25)+"px").resize();								
							}
						});
					}
					// inserts popup after helpSpan
					options.domInsertionMode = 'after';
					// if position==blank then opens new tab with content as url
					if(options.position == 'blank') {
						helpSpan.attr('href',content).attr('target', '_blank');
					}
					// adds click event handler
					else {
						helpSpan.off().click(function(event){
							var e = $(this);						
							wigiiApi.getHelpService().toggleHelp(content,e,options);
							event.stopPropagation();
						});
					}
				}
			};
			/**
			 * Shows a notification in the GUI.
			 * @param Object notif a Wigii notification object of the form {
			 * target:String. HTML ID of notification container. One of elementDialog|searchBar
			 * type:String. Notification type. One of help|info|warning|error|notif
			 * url:String. Notification content callback.
			 * ... other options for showHelp and Popup
			 * }
			 */
			self.showNotification = function(notif) {
				if(!$.isPlainObject(notif)) return;
				if(!notif.url) return;
				if(notif.target != 'elementDialog') notif.target = 'searchBar';
				if(!notif.type) notif.type = 'info';				
				var wigiiNotif = undefined;
				// gets wigiiNotif div in searchBar
				if(notif.target == 'searchBar') {
					// if help, replaces moduleHelp div
					if(notif.type == 'help') {
						var wigiiHelp = $('#searchBar .toolbarBox .moduleHelp'); 
						if(wigiiHelp.length == 0) {
							$('#searchBar .toolbarBox').prepend('<div class="moduleHelp"/>');
							wigiiNotif = $('#searchBar .toolbarBox .moduleHelp');
						}
						else {
							wigiiHelp.empty();
							wigiiNotif = wigiiHelp;
						}
						// forces popup to appear in SW
						notif.position='SW';
						if(!notif.width) notif.width=800;
						if(!notif.height) notif.height=400;
					}
					// else puts in div wigiiNotif
					else {
						wigiiNotif = $('#searchBar .toolbarBox .wigiiNotif');
						// creates div if does not exist
						if(wigiiNotif.length == 0) {
							$('#searchBar .toolbarBox').append('<div class="wigiiNotif" style="padding-right:3px;padding-left:3px;padding-top:3px;padding-bottom:3px;margin-right:10px;max-width:100%;overflow:hidden;"/>');
							wigiiNotif = $('#searchBar .toolbarBox .wigiiNotif');
						}
					}
				}
				// gets wigiiNotif div in elementDialog
				else if(notif.target == 'elementDialog') {					
					wigiiNotif = $('#elementDialog').parent().find('.wigiiNotif');
					// creates div if does not exist
					if(wigiiNotif.length == 0) {
						if(isWorkzoneViewDocked()) {
							$('#validTop').children().last().after('<div class="wigiiNotif" style=""/>');
							wigiiNotif = $('#validTop').find('.wigiiNotif');
						} else {
							$('#elementDialog').parent().find('.ui-dialog-titlebar-close').before('<div class="wigiiNotif" style="float:right;padding-right:3px;padding-left:3px;padding-top:3px;padding-bottom:3px;margin-right:10px;max-width:100%;overflow:hidden;"/>');
							wigiiNotif = $('#elementDialog').parent().find('.wigiiNotif');
						}
					}
					// if notif is help, then gets special help placeholder
					if(notif.type == 'help') {						
						// creates elementHelp div if not exist
						var wigiiHelp = wigiiNotif.prev('.elementHelp'); 
						if(wigiiHelp.length == 0) {
							wigiiNotif.before('<div class="elementHelp" style="float:right;padding-right:3px;padding-left:0px;padding-top:3px;padding-bottom:3px;margin-right:10px;max-width:100%;overflow:hidden;"/>');
							if(isWorkzoneViewDocked()) {
								wigiiNotif = $('#validTop .elementHelp');
								wigiiNotif.css('margin-top', '25px');
							} else {
								wigiiNotif = $('#elementDialog').parent().find('.elementHelp');
							}
						}
						// else clears elementHelp placeholder
						else {
							wigiiHelp.empty();
							wigiiNotif = wigiiHelp;
						}
						// forces popup to appear in SW
						notif.position='SW';
						if(!notif.width) notif.width=600;
						if(!notif.height) notif.height=300;
					}
				}
				// appends notif in wigiiNotif div
				if(wigiiNotif) {
					self.renderHelpAnchor(wigiiNotif, notif.url, notif);
				}
			};
			
			/**
			 * Remove the * add with a JQuery prepend function.
			 * @param Object collection a JQuery object collection
			 */
			self.removePrependStar = function(collection) {
				collection.each(function(){ 
					$(this).contents().each(function(){
						if($(this)[0].nodeType == 3){
							if($(this)[0].nodeValue.indexOf('*')!=-1) {
								$(this).remove();
								return false;
							}
						}
					});			
				});
			};
		});
		
		ncddoc(function(){/**
		 * Wigii Field helper.
		 * A class which helps manage fields lifecycle.
		*/},
		wigiiApi.FieldHelper = function() {
			var self = this;
			self.className = 'FieldHelper';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			self.debugLogger = wigiiApi.getDebugLogger(self.className);
			
			
			// Object lifecycle
			
			/**
			 * Binds the FieldHelper instance to a Field or set of Fields selected with JQuery
			 * @param JQuery field the field DOM element to which attach the FieldHelper instance
			 * @param Object options a map of configuration options for the FieldHelper
			 */
			self.attach = function(field,options) {
				if(!field || field.length == 0) return;
				// stores jquery selection if more than one element
				if(field.length>1) {
					self.list = field;
					self.$ = field;
					self.options = options;
				}
				// if one element, then retrieves context
				else if(field.length == 1) {
					self.context = field.data(self.ctxKey);
					if(!self.context) {
						// creates context if not existing
						self.context = {}; /* here options could be used. */
						field.data(self.ctxKey,self.context);
						
						// extracts field info and puts into context
						var fieldInfo = field.attr('id');
						self.context.fieldId = fieldInfo;
						if(fieldInfo) {
							fieldInfo = fieldInfo.split('__');
							if(fieldInfo.length>0) self.context.formId = fieldInfo[0];
							if(fieldInfo.length>1) self.context.fieldName = fieldInfo[1];
						}
						self.context.dataType = field.attr('data-wigii-datatype');
					}
					self.$ = field;					
				}
			};
			
			// List iterator
			
			/**
			 * Returns an iterator that can be used to perform actions on each element of the list
			 */
			self.getIterator = function() {
				return wigiiApi.getFieldHelper();
			};
			/**
			 * Iterates through the list of elements
			 * @param iterator the iterator performing some actions
			 * @param Function actions a function grouping the actions to be done using the iterator. 
			 */
			self.iterate = function(iterator,actions) {
				if($.type(actions) === 'function' && self.list) {
					var options = self.options;
					self.list.each(function(i){
						var e = $(this);
						iterator.attach(e,options);						
						actions();
					});
				}
			};
			
			/**
			 * Iterates through the list of fields and calls doAction on each of them
			 * @param Function doAction callback of the form doAction(fieldHelper) where fieldHelper is a FieldHelper instance centered on the Field.
			 * @return FieldHelper for chaining
			 */
			self.forEachField = function(doAction) {
				if($.isFunction(doAction)) {
					if(self.list){
						var iterator = self.getIterator();
						self.iterate(iterator,function(){doAction(iterator);});
					} else {
						doAction(self);
					}
				}				
				return self;
			};
			
			// FieldHelper functional implementation
			
			/**
			 * Sets a value in the field or the list of fields
			 * @param String value value to set
			 * @param String subFieldName optional subfield name. If not defined, takes default 'value' subfield 
			 * @return FieldHelper for chaining
			 */
			self.setValue = function(value,subFieldName) {	
				// list iteration
				if(self.list) {
					var iterator = self.getIterator();
					var actions = function(){iterator.setValue(value,subFieldName);};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else {
					if(subFieldName!=undefined){
						$('#'+self.fieldId()+' :input[name='+self.fieldName()+'_'+subFieldName+']').val(value);
					} else {
						if($('#'+self.fieldId()+' :input').hasClass('htmlArea')){
							$('#'+self.fieldId()+' :input').ckeditor(function(textarea){ //create a function to ensure the value is set once the editor is ready
								$(textarea).ckeditor().val(value);
							});
						} else if($('#'+self.fieldId()+' :input').hasClass('flex') || $('#'+self.fieldId()+' :input').hasClass('chosen')) {
							// if field is a flex or chosen drop-down, then triggers change event to refresh UI with new value
							$('#'+self.fieldId()+' :input').val(value).change();
						} else if($('#'+self.fieldId()).attr('data-wigii-datatype')=='Booleans') {
							$('#'+self.fieldId()+' :input').prop('checked',value==true);
						} else {
							$('#'+self.fieldId()+' :input').val(value);
						}
					}
				}
				return self;
			};
			
			/**
			 * Sets a numeric value in the field or the list of fields and format it with thousand separator
			 * @param String value value to set
			 * @return FieldHelper for chaining
			 */
			self.setNumValue = function(value) {	
				// list iteration
				if(self.list) {
					var iterator = self.getIterator();
					var actions = function(){iterator.setNumValue(value);};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else {
					self.setValue(wigiiApi.txtNumeric(value));
				}
				return self;
			};
			
			/**
			 * Gets the value of the field
			 * @param String subFieldName optional subfield name. If not defined, takes default 'value' subfield 
			 * @returns String|Array the field value or an array of field value if FieldHelper is attached to a list
			 */
			self.getValue = function(subFieldName) {
				var returnValue = undefined;
				// list iteration
				if(self.list) {
					returnValue=[];
					var iterator = self.getIterator();
					var actions = function(){returnValue.push(iterator.getValue(subFieldName));};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else {
					if(subFieldName!=undefined){
						if(self.formId()=='detailElement_form'){
							returnValue = $('#'+self.fieldId()+' div.value').html();
						}
						else {
							returnValue = $('#'+self.fieldId()+' :input[name='+self.fieldName()+'_'+subFieldName+']').val();
						}
					} else {
						if($('#'+self.fieldId()+' :input').hasClass('htmlArea')){
							$('#'+self.fieldId()+' :input').ckeditor(function(textarea){
								returnValue = $(textarea).ckeditor().val();
							});							
						}
						else if($('#'+self.fieldId()).attr('data-wigii-datatype')=='Booleans') {
							if(self.formId()=='detailElement_form'){
								returnValue = ($('#'+self.fieldId()+' div.value').attr('data-wigii-dbvalue')=='1');
							}
							else {
								returnValue = ($('#'+self.fieldId()+' :input').prop('checked')==true);
							}							
						}
						else if(self.formId()=='detailElement_form'){
							returnValue = $('#'+self.fieldId()+' div.value').html();
						}
						else {
							returnValue = $('#'+self.fieldId()+' :input').val();
						}
					}
				}
				return returnValue;
			};
			/**
			 * Gets the numeric value of the field
			 * @returns Float|Array the numeric field value or an array of numeric field value if FieldHelper is attached to a list
			 */
			self.getNumValue = function() {
				var returnValue = undefined;
				// list iteration
				if(self.list) {
					returnValue=[];
					var iterator = self.getIterator();
					var actions = function(){returnValue.push(iterator.getNumValue());};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else {
					returnValue = wigiiApi.str2float(self.getValue());
				}
				return returnValue;
			};
			/**
			 * Gets the DB value of the field if known, else returns the translated value
			 * @returns String|Array the field db value or an array of field db value if FieldHelper is attached to a list
			 */
			self.getDbValue = function() {
				var returnValue = undefined;
				// list iteration
				if(self.list) {
					returnValue=[];
					var iterator = self.getIterator();
					var actions = function(){returnValue.push(iterator.getDbValue());};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else {
					returnValue = self.$.find('div.value').attr('data-wigii-dbvalue');
					if(self.dataType()=='MultipleAttributs' && returnValue!='' && returnValue!=null) {
						returnValue = returnValue.split(',');
					}
					else if(returnValue == '' || returnValue==null) returnValue = self.getValue();					
				}
				return returnValue;
			};
			/**
			 * sum the numeric value of the fields
			 * @returns Float the numeric field value or an array of numeric field value if FieldHelper is attached to a list
			 */
			self.sum = function() {
				var returnValue = 0;
				self.forEachField(function(fh){
					returnValue += fh.getNumValue();
				});
				return returnValue;
			};

			/**
			 * update the value of the numeric field to a ceiled value
			 * @param Float number up to the number. IE: ceilTo(0.05) returns 10.35 if field value is 10.34
			 * @return Float value of the field after ceiling
			 */
			self.ceilTo = function (number, fixed){
				var returnValue = undefined;
				// list iteration
				if(self.list) {
					returnValue=[];
					var iterator = self.getIterator();
					var actions = function(){returnValue.push(iterator.ceilTo(number, fixed));};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else {
					self.setNumValue(wigiiApi.ceilTo(self.getNumValue(), number, fixed));
					returnValue = self.getNumValue();
				}
				return returnValue;
			};
			
			/**
			 * Gets the value of a field
			 * @param String subFieldName optional subfield name. If not defined, takes default 'value' subfield 
			 * @returns String|Array the field value or an array of field value if FieldHelper is attached to a list
			 */
			self.value = function(subFieldName) {return self.getValue(subFieldName);};
			
			/**
			 * Returns the ID of the field
			 * @return String|Array the field ID or an array of field IDs if FieldHelper is attached to a list
			 */
			self.fieldId = function() {
				var returnValue = undefined;
				// list iteration
				if(self.list) {
					returnValue=[];
					var iterator = self.getIterator();
					var actions = function(){returnValue.push(iterator.fieldId());};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else {
					if(self.context) returnValue = self.context.fieldId;
				}
				return returnValue;
			};
			
			/**
			 * Returns the name of the field
			 * @return String|Array the field name or an array of field names if FieldHelper is attached to a list
			 */
			self.fieldName = function() {
				var returnValue = undefined;
				// list iteration
				if(self.list) {
					returnValue=[];
					var iterator = self.getIterator();
					var actions = function(){returnValue.push(iterator.fieldName());};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else {
					if(self.context) returnValue = self.context.fieldName;
				}
				return returnValue;
			};
			
			/**
			 * Returns the ID of the form containing the field
			 * @return String|Array the form ID or an array of form IDs if FieldHelper is attached to a list
			 */
			self.formId = function() {
				var returnValue = undefined;
				// list iteration
				if(self.list) {
					returnValue=[];
					var iterator = self.getIterator();
					var actions = function(){returnValue.push(iterator.formId());};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else {
					if(self.context) returnValue = self.context.formId;
				}
				return returnValue;
			};
			
			/**
			 * Returns the name of the Wigii data type of the field
			 * @return String|Array the data type name or an array of data type names if FieldHelper is attached to a list
			 */
			self.dataType = function() {
				var returnValue = undefined;
				// list iteration
				if(self.list) {
					returnValue=[];
					var iterator = self.getIterator();
					var actions = function(){returnValue.push(iterator.dataType());};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else {
					if(self.context) returnValue = self.context.dataType;
				}
				return returnValue;
			};					
			
			/**
			 * Returns the FormHelper containing this field
			 */
			self.formHelper = function() {
				var id = self.formId();
				// extracts form id from array if multiple selection
				if($.type(id) === 'array') {
					if(id.length>1) {
						id = wigii('ArrayHelper',id).filterDuplicates().array();
						if(id.length>1) throw wigiiApi.createServiceException('FieldHelper is currently attached to a set of fields not belonging to same Wigii Form, cannot create FormHelper', wigiiApi.errorCodes.INVALID_STATE);
					}
					else if(id.length== 0) id=undefined;
					else id = id[0];
				}
				// gets FormHelper
				if(id) {
					return $('#'+id).wigii('FormHelper');
				}
			};
			
			// Event handling
			
			/**
			 * Registers a handler for FormEvent
			 * @param Function eventHandler FormEvent event handler function
			 * @param Object options a map of filters options for the eventHandler
			 * {eventName=array,fieldName=array,subFieldName=array,dataType=array}
			 */
			self.onFormEvent = function(eventHandler,options) {
				// list iteration
				if(self.list) {
					var iterator = self.getIterator();
					var actions = function(){iterator.onFormEvent(eventHandler,options);};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else if(self.context) {
					if(!self.context.formEventSubscribers) {
						self.context.formEventSubscribers = [];
						// creates jQuery events handler
						var fieldId = self.fieldId();						
						var jQueryEventHandler = function(eventObject) {
							var e = $(this);
							var fh = $('#'+fieldId).wigii('FieldHelper');
							if(fh.context && fh.context.formEventSubscribers) {								
								var formId = fh.formId();
								var fieldName = fh.fieldName();
								// extracts eventName
								var eventName = eventObject.type;
								if(eventName == 'select2:open') eventName = 'focus';
								else if(eventName == 'select2:close') eventName = 'blur';
								// creates FormEvent
								var formEvent = wigiiApi.createFormEventInstance(eventName, formId, fieldId, fieldName);
								// extracts dataType
								var dataType = fh.dataType();
								formEvent.dataType(dataType);
								// extracts subFieldName
								var subFieldName = e.attr('id');
								subFieldName = subFieldName.replace(formId+'_'+fieldName+'_','');
								subFieldName = subFieldName.split('_');
								// specific Addresses::zip_code handling								
								subFieldName = subFieldName[0];
								if(dataType==='Addresses' && subFieldName == 'zip') subFieldName = 'zip_code';
								formEvent.subFieldName(subFieldName);
								// extracts value
								formEvent.value(e.val());
								// forwards event to all subscribers
								var subscribers = fh.context.formEventSubscribers;
								for(var i=0;i<subscribers.length;i++) {
									var formEventHandler = subscribers[i];
									if(formEventHandler) {										
										var trigger = true;
										var opts = formEventHandler.options;
										// filter event if needed
										if(opts) {
											if(opts.eventName && opts.eventName.indexOf(formEvent.eventName())===-1) trigger=false;
											if(opts.fieldName && opts.fieldName.indexOf(formEvent.fieldName())===-1) trigger=false;
											if(opts.subFieldName && opts.subFieldName.indexOf(formEvent.subFieldName())===-1) trigger=false;
											if(opts.dataType && opts.dataType.indexOf(formEvent.dataType())===-1) trigger=false;
										}
										formEventHandler = formEventHandler.eventHandler;
										if($.type(formEventHandler) === 'function' && trigger) formEventHandler(formEvent);
									}									
								}								
							}
						};
						// activates event listening
						$('#'+fieldId+' div.value').find('input,select,textarea').filter('[id^="'+self.formId()+'_'+self.fieldName()+'"]').on('change input focus blur select2:open select2:close',jQueryEventHandler);
					}
					self.context.formEventSubscribers.push({eventHandler:eventHandler,options:options});
				}
				return self;
			};	
			
			/**
			 * bind/trigger change on the field
			 */
			self.change = function(eventHandler){
				// list iteration
				if(self.list) {
					var iterator = self.getIterator();
					var actions = function(){ iterator.change(eventHandler);};
					self.iterate(iterator,actions);
				}
				// single element implementation
				else if(self.context) {
					if(eventHandler==undefined){
						return $('#'+self.fieldId()+' :input').change();
					}
					return $('#'+self.fieldId()+' :input').change(eventHandler);
				}
			}
			
		});
		
		ncddoc(function(){/**
		 * Wigii Form helper.
		 * A class which helps manage form lifecycle.
		*/},
		wigiiApi.FormHelper = function() {
			var self = this;
			self.className = 'FormHelper';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			//self.debugLogger = wigiiApi.getDebugLogger(self.className);
			
			
			// Object lifecycle
			
			/**
			 * Binds the FormHelper instance to a Wigii Form (detail,edit,copy,add)Field or set of Fields selected with JQuery
			 * @param JQuery wigiiForm the Wigii Form DOM element to which attach the FormHelper instance
			 * @param Object options a map of configuration options for the FormHelper
			 */
			self.attach = function(wigiiForm,options) {
				if(!wigiiForm || wigiiForm.length == 0) return;
				// does not support more than one element selected
				if(wigiiForm.length>1) throw wigiiApi.createServiceException('Wigii FormHelper selector can only be activated on a JQuery collection containing one element and not '+selection.length, wigiiApi.errorCodes.INVALID_ARGUMENT); 
				// retrieves context
				self.context = wigiiForm.data(self.ctxKey);
				if(!self.context) {
					// creates context if not existing
					self.context = {}; /* here options could be used. */
					wigiiForm.data(self.ctxKey,self.context);
					
					// extracts Wigii form info and puts into context
					self.context.formId = wigiiForm.attr('id');								
				}
				self.$ = wigiiForm;					
			};
			
			// FormHelper functional implementation
			
			/**
			 * Returns the ID of the form
			 * @return String the form ID
			 */
			self.formId = function() {
				if(self.context) return self.context.formId;
			};
			
			/**
			 * Returns the ID of the current element
			 * @return String the element ID
			 */
			self.elementId = function() {
				var sel = $('#elementDialog div.T');
				var elementId = '';
				if(sel.length>0) elementId=sel.attr('href').replace('#','');
				if(elementId=='') {
					sel = self.$.find('input[name=elementId]');
					if(sel.length>0) elementId = sel.val();
				}
				return elementId;
			};
			
			/**
			 * Returns if current element has the modify button active
			 * @return Boolean
			 */
			self.elementIsEditable = function() {
				var sel = $('#elementDialog div.T div.el_edit:visible');
				if(sel.length>0){
					if(!sel.attr('disabled')){
						return true;
					}
				}
				return false;
			};
			
			/**
			 * Returns the ID of the current group
			 * @return String the current group ID
			 */
			self.currentGroupId = function() {
				currentFolder = $('#groupPanel li.selected');
				if(currentFolder.length>0) currentFolder = currentFolder.attr('id').split('_')[1];
				else currentFolder = undefined;
				return currentFolder;
			}
			
			/**
			 * Returns the ID of the current group if group can be written
			 * @return String the current group ID
			 */
			self.writableCurrentGroupId = function() {
				currentFolder = $('#groupPanel li.selected.write');
				if(currentFolder.length>0) currentFolder = currentFolder.attr('id').split('_')[1];
				else currentFolder = undefined;
				return currentFolder;
			}
			
			/**
			 * Selects a field in the Wigii Form and returns a FieldHelper on it (or undefined if not found)
			 * @param String fieldName the field selector
			 * @return FieldHelper the FieldHelper instance attached to the selected field or undefined if not found
			 */
			self.field = function(fieldName) {
				var returnValue = undefined;
				if($.isArray(fieldName)){
					returnValue = self.fields(fieldName);
				} else if(fieldName) {
					returnValue = $('#'+self.formId()+'__'+fieldName).wigii('FieldHelper');
				}
				return returnValue;
			};
			
			/**
			 * Selects a field in the Wigii Form and returns a JQuery pointer on its input element
			 * @param String fieldName the field selector
			 * @return JQuery jquery collection on the selected fields input
			 */
			self.fieldInput = function(fieldName) {
				var returnValue = undefined;
				if($.isArray(fieldName)){
					var fieldSelector = '#'+self.formId()+'__';
					returnValue = $(fieldSelector+fieldName.join(' :input, '+fieldSelector)+' :input');
				} else if(fieldName) {
					returnValue = $('#'+self.formId()+'__'+fieldName+' :input');
				}
				return returnValue;
			};

			/**
			 * Returns a selection of fields in the Wigii Form			 
			 * @param String|Array selector JQuery or array of fieldNames, to filter the Fields of the form (for instance based on data-wigii-datatype or other attribute). If not defined, takes all the Fields.
			 * @return FieldHelper an instance of FieldHelper attached to the selected list of Fields.
			 */
			self.fields = function(selector) {
				if($.isArray(selector)){
					return self.fields('#'+self.formId()+'__'+selector.join(', #'+self.formId()+'__'));
				} else if(selector) return self.$.find('div.field[data-wigii-datatype]').filter(selector).wigii('FieldHelper');
				else return self.$.find('div.field[data-wigii-datatype]').wigii('FieldHelper');
			};
			
			/**
			 * Get/Set wigii field value
			 * @param String fieldName the field selector
			 * @param Mixed value for the field : optional, use '' to empty the field value
			 * @param String subFieldName optional the subFieldName
			 * @return Mixed value of the field
			 */
			self.val = function (fieldName, value, subFieldName){
				if(value!=undefined){
					self.field(fieldName).setValue(value, subFieldName);
				}
				return self.field(fieldName).getValue(subFieldName);
			};
			/**
			 * Get/Set wigii numeric field value
			 * @param String fieldName the field selector
			 * @param Float value for the field : optional
			 * @return Float value of the field
			 */
			self.numVal = function (fieldName, value){
				if(value!=undefined){
					self.field(fieldName).setNumValue(value);
				}
				return self.field(fieldName).getNumValue();
			};
			/**
			 * Get wigii field db value
			 * @param String fieldName the field selector
			 * @return String db value of the field
			 */
			self.dbVal = function (fieldName){
				return self.field(fieldName).getDbValue();
			};
			
			/**
			 * update the value of a wigii numeric field to a ceiled value
			 * @param String fieldName the field selector
			 * @param Float number up to the number. IE: ceilTo(fieldName, 0.05) returns 10.35 if field value is 10.34
			 * @return Float value of the field after ceiling
			 */
			self.ceilTo = function (fieldName, number, fixed){
				return self.field(fieldName).ceilTo(number, fixed);
			};
			
			/**
			 * Copies a set of fields, inserts them into the DOM and renames them.
			 * @param Array fromFields the array of field names to copy
			 * @param Array toFields the array of copied field names
			 * @param JQuery|String beforeTarget optional jQuery selector before which to insert the copied fields. 
			 * If not defined, the copied fields are appended to the end of the parent element.
			 * @example copyFields(['articleNumber_1,label_1,quantity_1'],['articleNumber_2,label_2,quantity_2'])
			 * @return FieldHelper a FieldHelper instance centered on the copied fields
			 */
			self.copyFields = function(fromFields,toFields,beforeTarget) {
				var i = 0;				
				if(!$.isArray(toFields)) toFields = [toFields];
				// iterates on the selected fields
				self.fields(fromFields).forEachField(function(field){
					var fieldHtml = field.$[0].outerHTML;
					// replaces current field name by new field name (in every reference
					fieldHtml = fieldHtml.replace(new RegExp(field.fieldName(),'g'),toFields[i]);
					// insert html
					if(beforeTarget==undefined) field.$.parent().append(fieldHtml);
					else field.formHelper().$.insertBefore(beforeTarget);
					// binds js events on new field
					var newField = field.formHelper().field(toFields[i])
					newField.$.find('.select2, .cke').remove();					
					addJsCodeAfterFormIsShown('#'+newField.fieldId());
					i++;
				});
				if(i>0) return self.fields(toFields);
			};
			
			/**
			 * Returns a wigiiApi.ButtonHelper on the selected button in form.
			 * @param String name button name. If selector is not given, then looks for button.name or div.name or span.name.
			 * @param String selector any valid JQuery selector used to select the button
			 * @return wigiiApi.ButtonHelper
			 */
			self.button = function(name,selector) {
				if(!self.context.buttons) self.context.buttons = {};
				if(!self.context.buttons[name]) {
					var btn = undefined;
					if(selector) btn = self.$.find(selector);
					else {
						btn = self.$.find('button.'+name);
						if(btn.length==0) btn = self.$.find('div.'+name);
						if(btn.length==0) btn = self.$.find('span.'+name);
					}
					self.context.buttons[name] = btn.wigii('ButtonHelper');
				}
				return self.context.buttons[name];
			};
			
			/**
			 * Returns a ButtonHelper centered on the form OK button
			 * @return wigiiApi.ButtonHelper
			 */
			self.ok = function() {
				if(!self.context.buttons) self.context.buttons = {};
				if(!self.context.buttons['ok']) {					
					var btn = undefined;
					if(isWorkzoneViewDocked()) btn = $('#elementDialog button.ok');
					else btn = $('#elementDialog').parent().find('button.ok');					
					self.context.buttons['ok'] = btn.wigii('ButtonHelper');
				}
				return self.context.buttons['ok'];
			};
			
			/**
			 * Returns a ButtonHelper centered on the form OK button
			 * @return wigiiApi.ButtonHelper
			 */
			self.cancel = function() {
				if(!self.context.buttons) self.context.buttons = {};
				if(!self.context.buttons['cancel']) {					
					var btn = undefined;
					if(self.formId()=='detailElement_form') {
						if(isWorkzoneViewDocked()) btn = $('#elementDialog div.T .el_closeDetails');
						else btn = $('#elementDialog').parent().find('button.ui-dialog-titlebar-close');
					}
					else {
						if(isWorkzoneViewDocked()) btn = $('#elementDialog button.cancel');
						else btn = $('#elementDialog').parent().find('button.cancel');
					}
					self.context.buttons['cancel'] = btn.wigii('ButtonHelper');
				}
				return self.context.buttons['cancel'];
			};
			
			/**
			 * Gets or sets element dialog title
			 */
			self.title = function(title) {
				if(title===undefined) {
					if(!isWorkzoneViewDocked()) return $('#elementDialog').parent().find('.ui-dialog-title').html();
				}
				else {
					if(!isWorkzoneViewDocked()) {
						$('#elementDialog').parent().find('.ui-dialog-title').html(title);
					}
					return self;
				}
			};
			
			/**
			 * Returns a ButtonHelper on a standard Wigii toolbar or creates a new custom button
			 * @param String name the name of the standard Wigii tool like 'edit','copy','status','organize','delete','feedback','link' or 'sendLink','print' or 'printDetails'
			 * or a new custom tool name (without any space)
			 * @example wigii().form().tool('createInvoice').label("Créer facture").click(function(){...}).$.css("background-color","green")
			 * will create a new custom tool "el_createInvoice" with label "Créer facture" and custom on click function and css
			 * @return wigiiApi.ButtonHelper only if current form is an element detail.
			 */
			self.tool = function(name) {
				if(self.formId()!='detailElement_form') throw wigiiApi.createServiceException("Standard Wigii toolbar is available only on element detail", wigiiApi.errorCodes.UNSUPPORTED_OPERATION);
				if(!self.context.buttons) self.context.buttons = {};
				if(!self.context.buttons[name]) {					
					var btn = undefined;
					var mappedName = name;
					if(name=='link') mappedName = 'sendLink';
					if(name=='print') mappedName = 'printDetails';
					if(isWorkzoneViewDocked()) btn = $('#elementDialog div.T .el_'+mappedName);
					else {
						btn = $('#elementDialog div.T .el_'+mappedName);
						if(btn.length==0) btn = $('#elementDialog').parent().find('div.ui-dialog-titlebar .el_'+mappedName);
					}
					// creates button if it does not exist
					if(btn.length==0) {
						$('#elementDialog div.T > div').last().after('<div class="H el_'+mappedName+'"></div>');
						btn = $('#elementDialog div.T .el_'+mappedName);
					}
					self.context.buttons[name] = btn.wigii('ButtonHelper');
				}
				return self.context.buttons[name];
			};
			
			/**
			 * Launches a custom copy process
			 * @param String dialogTitle custom organize dialog title (defaults to "Copy into folder")
			 * @param String dialogIntro custom instruction displayed before the group panel
			 * @param Object|Array|String urlArgs or an object with some (key,value) pairs, or an array of values, or a single string.
			 * @param int defaultGroupId a group ID to select by default in the organize dialog before launching the copy process 
			 */
			self.launchCopy = function(dialogTitle,dialogIntro,urlArgs,defaultGroupId) {
				var startUrl = 'elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName;
				var targetFolder = defaultGroupId;
				if(targetFolder===undefined) {
					targetFolder = $('#groupPanel li.selected.write');
					if(targetFolder.length>0) targetFolder = targetFolder.attr('id').split('_')[1];
					else targetFolder = undefined;
				}				
				var initialFolder = targetFolder;
				var itemID = self.elementId();
				if(urlArgs) urlArgs = '/'+wigiiApi.buildUrlFragment(urlArgs);
				prepareOrganizeDialog(
					//on click
					function(e){
						if(!$(this).hasClass('disabled')){
							$('#organizeDialog .selected').removeClass('selected');
							$(this).toggleClass('selected');
							targetFolder = $(this).parent().attr('id').split('_')[1];
						}
						return false;
					}, 
					//on Ok
					function(){
						if(!targetFolder) {
							jAlert(DIALOG_selectAtLeastOneGroup);
							return false;
						}
						if( $('#organizeDialog').is(':ui-dialog')) { $('#organizeDialog').dialog("destroy"); }			
						// opens copy dialog
						// if folder changed, then navigates to new folder
						invalidCache("moduleView");
						if(targetFolder != initialFolder) startUrl = 'NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/groupSelectorPanel/selectGroup/'+targetFolder+EXEC_requestSeparator+startUrl;
						update(startUrl+'/element/copy/'+itemID+'/'+targetFolder+urlArgs);
					});
				$('#organizeDialog').prev().find('.ui-dialog-title').text(dialogTitle||DIALOG_copyToFolder);
				$('#organizeDialog').prepend('<div class="introduction">'+(dialogIntro||DIALOG_copyToFolder_help)+'</div>');
				
				//disables read only groups
				$('#organizeDialog>.groupPanel li:not(.write)').addClass("disabled noRights");
				$('#organizeDialog>.groupPanel li:not(.write)>div').addClass("disabled noRights");
				if(targetFolder) $('#organizeDialog>.groupPanel #group_'+targetFolder+', #organizeDialog>.groupPanel #group_'+targetFolder+'>div').addClass('selected');
				unfoldToSelectedGroupButNotChildren('#organizeDialog>.groupPanel');
				positionSelectedGroup('#organizeDialog>.groupPanel');	
				if(targetFolder) {		
					// sets focus on OK button to manage pressing enter to copy directly in current folder
					$('#organizeDialog').next().find('button.ok').focus();
				}	
			};
			
			// Event handling
			
			/**
			 * Registers a handler for FormEvent
			 * @param Function eventHandler FormEvent event handler function
			 * @param Object options a map of filters options for the eventHandler
			 * {eventName=array,fieldName=array,subFieldName=array,dataType=array}
			 */
			self.onFormEvent = function(eventHandler,options) {
				if(self.context) {
					if(!self.context.formEventSubscribers) {
						self.context.formEventSubscribers = [];		
						// FormHelper subscribes to events from all contained FieldHelpers
						self.fields().onFormEvent(function(formEvent){
							var fh = $('#'+formEvent.formId()).wigii('FormHelper');
							// pushes event to all subscribers
							if(fh.context && fh.context.formEventSubscribers) {
								var subscribers = fh.context.formEventSubscribers;
								for(var i=0;i<subscribers.length;i++) {
									var formEventHandler = subscribers[i];
									if(formEventHandler) {										
										var trigger = true;
										var opts = formEventHandler.options;
										// filter event if needed
										if(opts) {
											if(opts.eventName && opts.eventName.indexOf(formEvent.eventName())===-1) trigger=false;
											if(opts.fieldName && opts.fieldName.indexOf(formEvent.fieldName())===-1) trigger=false;
											if(opts.subFieldName && opts.subFieldName.indexOf(formEvent.subFieldName())===-1) trigger=false;
											if(opts.dataType && opts.dataType.indexOf(formEvent.dataType())===-1) trigger=false;
										}
										formEventHandler = formEventHandler.eventHandler;
										if($.type(formEventHandler) === 'function' && trigger) formEventHandler(formEvent);
									}	
								}
							}
						});
					}
					self.context.formEventSubscribers.push({eventHandler:eventHandler,options:options});
				}
				return self;
			};		
			
			/**
			 * Check if an help popup is show, if a popup is display hide it and place the popup in context helpPopup 
			 * @param Event popup 
			 */
			self.onHelpPopupShow = function(popup) {
				// onHelpPopupShow(popup)
				// if(self.context.helpPopup) self.context.helpPopup.hide(); self.context.helpPopup=popup;
				if(self.context.helpPopup) {
					self.context.helpPopup.hide();
					if(self.context.helpPopup!==popup)
						self.context.helpPopup=popup;
				} else {
					self.context.helpPopup=popup;
				}
			};
			
			/**
			 * Check if the context helpPopup equal to popup event and unasign it
			 * @param Event popup
			 */
			self.onHelpPopupHide = function(popup) {
				// onHelpPopupHide(popup)
				// if(self.context.helpPopup===popup) self.context.helpPopup=undefined;
				if(self.context.helpPopup===popup) 
					self.context.helpPopup=undefined;
			};
		});
		
		ncddoc(function(){/**
		 * Wigii Button helper.
		 * A class which helps manage button lifecycle.
		 * This helper works with any elements acting logically as a button.
		*/},
		wigiiApi.ButtonHelper = function(btn,options) {
			var self = this;
			self.className = 'ButtonHelper';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			//self.debugLogger = wigiiApi.getDebugLogger(self.className);
			
			self.$ = btn;
			self.label = function(l) {
				if(l===undefined) return self.$.html();
				else {
					self.$.html(l);
					return self;
				}
			};
			self.click = function(eventHandler) {
				if($.isFunction(eventHandler)) self.$.click(eventHandler);
				else self.$.click();
				return self;
			};
			self.disabled = function(bool) {
				if(bool===undefined) return self.$.prop('disabled');
				else {
					self.$.prop('disabled',(bool==true));
					return self;
				}
			}
		});
		
		ncddoc(function(){/**
		 * WNCD container instance
		*/},
		wigiiApi.WncdContainer = function(wncd) {
			var self = this;
			self.className = 'WncdContainer';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;	
			//self.debugLogger = wigiiApi.getDebugLogger(self.className);
			self.impl = {
				onDataChangeSubscribers:undefined,
				onElementDeletedSubscribers:undefined
			};
			/**
			 * Resets the current WncdContainer
			 * This method is called each time the moduleView is repainted from scratch.
			 */
			self.reset = function() {
				self.impl.onDataChangeSubscribers=undefined;
				self.impl.onElementDeletedSubscribers=undefined;
			};
			
			/**
			 * Returns Wigii ElementPList JSON data model linked to current WNCD view
			 * @return Object module view object model as specified in https://resource.wigii.org/#Public/Documentation/item/2075
			 */
			self.getWigiiDataModel = function() {
				if(!wncd) throw wigiiApi.createServiceException("wncd libraries have not been correctly loaded, wncd symbol is not available.", wigiiApi.errorCodes.UNSUPPORTED_OPERATION);
				return wncd.program.context[wigiiApi.context.crtView];
			};
			
			/**
			 * Registers an event handler on the Wigii data model changes
			 * or triggers a dataChange event.
			 * The event handler signature is of the form eventHandler(wncdContainer,wigiiDataModel).
			 * @return wigiiApi.WncdContainer for chaining
			 */
			self.dataChange = function(onDataChange) {
				if($.isFunction(onDataChange)) {
					if(!self.impl.onDataChangeSubscribers) {
						self.impl.onDataChangeSubscribers = [];
					}
					self.impl.onDataChangeSubscribers.push(onDataChange);
				}
				else if(onDataChange===undefined) {
					if(self.impl.onDataChangeSubscribers) {
						var dataModel = self.getWigiiDataModel();
						for(var i=0;i<self.impl.onDataChangeSubscribers.length;i++) {
							var eh = self.impl.onDataChangeSubscribers[i];
							if($.isFunction(eh)) eh(self,dataModel);
						}
					}
				}
				return self;
			};
			
			/**
			 * Iterates through each element in current data model.
			 * On each element calls the given callback of the form callback(index,elementId, element)
			 * @return wigiiApi.WncdContainer for chaining
			 */
			self.forEachElement = function(callback) {
				self.iterateOnElementList(self.getWigiiDataModel(),callback);
				return self;
			};
			
			/**
			 * Iterates through each element in the given data model.
			 * On each element calls the given callback of the form callback(index,elementId, element)
			 * @return wigiiApi.WncdContainer for chaining
			 */
			self.iterateOnElementList = function(wigiiDataModel,callback) {
				if(!wigiiDataModel) throw wigiiApi.createServiceException('wigiiDataModel cannot be null',wigiiApi.errorCode.INVALID_ARGUMENT);
				if(!$.isFunction(callback)) throw wigiiApi.createServiceException('callback should be a function of the form callback(index,elementId, element)',wigiiApi.errorCode.INVALID_ARGUMENT);
				var elementList = wigiiDataModel.elementList;
				if(elementList) {
					var index = 1;
					for(var eltId in elementList) {
						var element = elementList[eltId];
						callback(index,element.__element.id,element);
						index++;
					}
				}
			};	
			
			/**
			 * Shows the details of the element
			 */
			self.showElement = function(elementId) {
				update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/detail/'+elementId);
			};
			
			/**
			 * Opens a Wigii Form to edit the element
			 */
			self.editElement = function(elementId) {
				update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/edit/'+elementId);
			};
			
			/**
			 * Asks Wigii to delete the element
			 */
			self.deleteElement = function(elementId) {
				update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/delete/'+elementId+'/elementDialog');
			};
			/**
			 * Registers an event handler when an element has been successfully deleted from Wigii
			 * The event handler signature is of the form eventHandler(wncdContainer,elementId).
			 * @return wigiiApi.WncdContainer for chaining
			 */
			self.elementDeleted = function(onElementDeleted) {
				if($.isFunction(onElementDeleted)) {
					if(!self.impl.onElementDeletedSubscribers) {
						self.impl.onElementDeletedSubscribers = [];
					}
					self.impl.onElementDeletedSubscribers.push(onElementDeleted);
				}
				else if(onElementDeleted) {						
					if(self.impl.onElementDeletedSubscribers) {
						var elementId = onElementDeleted;
						for(var i=0;i<self.impl.onElementDeletedSubscribers.length;i++) {
							var eh = self.impl.onElementDeletedSubscribers[i];
							if($.isFunction(eh)) eh(self,elementId);
						}
					}
				}
				return self;
			};
			
			/**
			 * Saves the value of an Element Field into the database.
			 * @param Int elementId id of the element to update
			 * @param String fieldName name of the field to update
			 * @param Object|String value value to be updated.
			 * To update subfields, an object with the subfields should be given.
			 * @param Object options an optional bag of options. The following options are supported:
			 * - onSuccessCallback: Function. A function to be called if save went well. Function signature is onSuccessCallback(fieldValue), where fieldValue is an object with all the Field subfields.
			 * - exceptionHandler: Function. A function which handles any thrown exception from server. Function signature is exceptionHandler(exception,context) where exception is Wigii API exception object of the form {name:string,code:int,message:string}
			 * and context is an object with some server context information of the form {request:string, wigiiNamespace:string, module:string, action:string, realUsername:string, username:string, principalNamespace:string, version:string}
			 * If exceptionHandler is not set, then exception is published through the wigii.publishException method.
			 * - silent: Boolean. If silent is true, then no exception handler is called if an error occurs.
			 * - noCalculation: Boolean. If true, then element calculated fields are not re-calculated. By default calculation is active.
			 * - noNotification: Boolean. If true, then Notifications are not sent out on field update. By default notifications are enabled following what is defined in configuration file.
			 */
			self.saveFieldValue = function(elementId,fieldName,value,options) {
				options = options || {};
				options.caller = 'saveFieldValue';
				// initializes autosave form
				var target = 'Wigii_'+self.className;					
				var autosaveForm = {
					'autoSaveFieldId':target,
					'autoSaveMesssageTargetId':target,
					'noCalculation':(options.noCalculation==true),
					'noNotification':(options.noNotification==true)
				};
				// puts field value
				if($.isPlainObject(value)) {
					for(var subField in value) {
						autosaveForm[fieldName+"_"+subField] = value[subField];
					}
				}
				else autosaveForm[fieldName+"_value"] = value;
				// call Wigii autosave method
				setVis('busyDiv', true);
				$.ajax({
					type:"POST",
					url: wigiiApi.buildUpdateUrl(target+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/autoSave/'+elementId+'/'+fieldName),
					data:autosaveForm,
					success: wigiiApi.buildUpdateCallback(options)
				});
			};
			/**
			 * JSON event handler
			 * @param Object object the JSON response already parsed into an object
			 */
			self.json = function(object,context) {
				// json callback with default error handling or on success callback
				if(context) {
					setVis('busyDiv', false);
					// extracts return data
					var exception = undefined;
					var exceptionContext = undefined;
					if($.isPlainObject(object)) {
						if(object.exception) {
							exception = object.exception;
							exceptionContext = object.context;
						}
					}
					// handles exceptions
					if(exception) {
						if(!context.silent) {
							if($.isFunction(context.exceptionHandler)) {
								context.exceptionHandler(exception,exceptionContext);
							}
							else wigiiApi.publishException(exception);
						}
					}
					// else handles success
					else if($.isFunction(context.onSuccessCallback)) {
						context.onSuccessCallback(object);
					}
				}					
				// else nothing to do.
			};
		});
		
		ncddoc(function(){/**
		 * HTML String builder
		*/},
		wigiiApi.HtmlBuilder = function() {
			var self = this;
			self.className = 'HtmlBuilder';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			
			self.buffer = '';
			self.jsBuffer = [];
			
			/**
			 * Returns built html string
			 * @return String
			 */
			self.html = function() {
				return self.buffer;
			};
			/**
			 * Executes all the JS code stored into the buffer
			 * @return HtmlBuilder for chaining
			 */
			self.runJsCode = function() {
				for(var i=0;i<self.jsBuffer.length;i++) {
					var jsCode = self.jsBuffer[i];
					if($.isFunction(jsCode)) jsCode();
				}
				return self;
			};
			/**
			 * Resets the html builder to an empty buffer
			 * @return HtmlBuilder for chaining
			 */
			self.reset = function() {
				self.buffer = '';
				self.jsBuffer = [];
				return self;
			};
			/**
			 * Appends a string to current buffer
			 * @param String str the string to put into the buffer
			 * @return HtmlBuilder for chaining
			 */
			self.put = function(str) {
				if(str) self.buffer += str;
				return self;
			};
			/**
			 * Append a string to current buffer but replace any new line with a br tag
			 */
			self.putNl2Br = function(str) {
				if (typeof str === 'undefined' || str === null) {
					//nothing to do
				} else {
					self.buffer += (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br />' + '$2');					
				}
				return self;
			}
			/**
			 * Implodes some arguments with a separator and adds the string to the given buffer
			 * @param String sep the separator to be added between each arguments
			 * @return HtmlBuilder for chaining
			 */
			self.implode = function(sep) {
				if(arguments.length>1) {
					var i = 1;
					var result = '', value;
					while(i<arguments.length) {						
						value = arguments[i];
						if(value) {
							if(result) result += sep;
							result += value;
						}
						i++;
					}
					self.buffer += result;
				}
				return self;
			};
			/**
			 * Prepends a prefix to some content only if content is not null.
			 * @param String prefix the string prefix
			 * @param String str the content to put into the buffer prefixed if not null
			 * @return HtmlBuilder for chaining
			 */
			self.prepend = function(prefix,str) {
				if(str) self.buffer += prefix+str;
				return self;
			};
			/**
			 * Repeats an nbsp entity several times
			 * @return HtmlBuilder for chaining
			 */
			self.putNbsp = function(multiplier) {
				if(!multiplier) multiplier=1;
				for(var i=0;i<multiplier;i++) {
					self.buffer += '&nbsp;';
				}
				return self;
			};
			/**
			 * Puts a double quote entity in the buffer or around the string parameter
			 * @return HtmlBuilder for chaining
			 */
			self.putQuot = function(str) {
				if(str){
					self.buffer += '&quot;'+str+'&quot;';
				} else {
					self.buffer += '&quot;';
				}
				return self;
			};
			/**
			 * Puts an Amp entity in the buffer
			 * @return HtmlBuilder for chaining
			 */
			self.putAmp = function() {
				self.buffer += '&amp;';
				return self;
			};
			/**
			 * Puts a Lt entity in the buffer
			 * @return HtmlBuilder for chaining
			 */
			self.putLt = function() {
				self.buffer += '&lt;';
				return self;
			};
			/**
			 * Puts a Gt entity in the buffer
			 * @return HtmlBuilder for chaining
			 */
			self.putGt = function() {
				self.buffer += '&gt;';
				return self;
			};
			/**
			 * Puts an Apos entity in the buffer
			 * @return HtmlBuilder for chaining
			 */
			self.putApos = function() {
				self.buffer += '&apos;';
				return self;
			};
			/**
			 * Puts an HashTag character in the buffer
			 * @return HtmlBuilder for chaining
			 */
			self.putHashTag = function() {
				self.buffer += '#';
				return self;
			};
			/**
			 * Repeats a br tag several times
			 * @return HtmlBuilder for chaining
			 */
			self.putBrTag = function(multiplier) {
				if(!multiplier) multiplier=1;
				for(var i=0;i<multiplier;i++) {
					self.buffer += '<br />';
				}
				return self;
			};/**
			 * Repeats a br tag several times
			 * @return HtmlBuilder for chaining
			 */
			self.putBr = self.putBrTag;
			
			/**
			 * Creates an html open tag
			 * @param String tagName the name of the html tag, for example "div" or "p"
			 * @param String key an html attribute name, for example "class"
			 * @param String value an html attribute value, for example "ui-dialog"
			 * This function supports a variable number of arguments, 
			 * meaning that you can pass as many key,value as you need to set all html attributes.
			 * @return HtmlBuilder for chaining
			 */
			self.putStartTag = function(tagName) {
				if(!tagName) throw wigiiApi.createServiceException('putStartTag takes a non null tagName', wigiiApi.errorCodes.INVALID_ARGUMENT);
				self.buffer += '<'+tagName;
				if(arguments.length>1) {
					var i = 1;
					var key,value;
					while(i<arguments.length) {
						key = arguments[i];
						if(!key) throw wigiiApi.createServiceException('html attribute name cannot be null', wigiiApi.errorCodes.INVALID_ARGUMENT);
						i++;
						if(i<arguments.length) {
							value = arguments[i];
							i++;
						}
						else value = '';
						self.buffer += ' '+key+'="'+value+'"';
					}
				}
				self.buffer += '>';
				return self;
			};
			/**
			 * Creates an html close tag
			 * @param String tagName the name of the html tag to close, for example "div" or "p"
			 * @return HtmlBuilder for chaining
			 */
			self.putEndTag = function(tagName) {
				if(!tagName) throw wigiiApi.createServiceException('putEndTag takes a non null tagName', wigiiApi.errorCodes.INVALID_ARGUMENT);
				self.buffer += '</'+tagName+'>';
				return self;
			};			
			/**
			 * Creates an html document header 
			 * @return HtmlBuilder for chaining
			 */
			self.putHtmlHeader = function() {
				self.buffer += '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
				return self;
			};
			/**
			 * Puts some JS code into the buffer waiting for execution
			 * @param Function|String actions the JS code to be executed. (a function with no arguments or a valid js code string).
			 * @returns HtmlBuilder for chaining
			 */
			self.putJsCode = function(actions) {
				var actionType = $.type(actions);
				if(actionType==='function') {
					self.jsBuffer.push(actionType);
				}
				else if(actionType==='string') {
					self.jsBuffer.push(function(){eval(actions);});
				}
				return self;
			};
			/**
			 * Adds the content of the given HtmlBuilder to the current HtmlBuilder
			 * @param HtmlBuilder htmlBuilder the HtmlBuilder instance from which to get the html string and waiting js code.
			 */
			self.putHtmlBuilder = function(htmlBuilder) {
				if(htmlBuilder) {
					// adds html
					self.put(htmlBuilder.html());
					// adds js code
					for(var i=0;i<htmlBuilder.jsBuffer.length;i++) {
						self.putJsCode(htmlBuilder.jsBuffer[i]);
					}
				}
				return self;
			};
		});
		
		ncddoc(function(){/**
		 * A helper on array of strings
		 * @param Array arr the array on which to perform some actions
		*/},
		wigiiApi.ArrayHelper = function(arr) {
			var self = this;
			self.className = 'ArrayHelper';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			
			if($.type(arr) !== 'array') throw wigiiApi.createServiceException('arr should be an array', wigiiApi.errorCodes.INVALID_ARGUMENT);
			self.arr = arr;
			
			/**
			 * Clones the array 
			 * @return ArrayHelper for chaining
			 */
			self.clone = function() {
				self.arr = self.arr.slice();
				return self;
			};
			/**
			 * Sorts the array
			 * @param Boolean asc sort order, true=ascending,false=descending, defaults to true.
			 * @return ArrayHelper for chaining
			 */
			self.sort = function(asc) {
				if(asc!==false) asc = true;
				// ascending
				self.arr.sort();
				// descending
				if(!asc) self.arr.reverse();
				return self;
			};
			/**
			 * Filter duplicates in the array of strings
			 * @return ArrayHelper for chaining
			 */
			self.filterDuplicates = function() {
				var d = self.distribution();
				self.arr = [];
				for(var val in d) {
					self.arr.push(val);
				}
				return self;
			};
			/**
			 * Returns the distribution of the values in the array (counts the frequency of each single values)
			 * @return Object the distribution object {arrayValue=>frequency counter}
			 */
			self.distribution = function() {
				var d = {};						
				for(var i=0;i<self.arr.length;i++) {
					var val = self.arr[i];
					if(!val) val='';
					var c = d[val];
					if(c) c += 1;
					else c = 1;
					d[val] = c;
				}
				return d;
			};
			/**
			 * Returns the array
			 * @return Array
			 */
			self.array = function() {
				return self.arr;
			};
		});
		
		ncddoc(function(){/**
		 * A helper on an array of rows. A row is an array of values or an object with field names
		 * @param Array matrix the array on which to perform some actions
		 * @param Array selectedRows an optional array with a subset of selected row indexes. If defined, then the matrix helper will act only on these selected rows.
		*/},
		wigiiApi.MatrixHelper = function(matrix,selectedRows) {
			var self = this;
			self.className = 'MatrixHelper';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;			
			if($.type(matrix) !== 'array') throw wigiiApi.createServiceException('matrix should be an array', wigiiApi.errorCodes.INVALID_ARGUMENT);
			self.matrix = matrix;
			self.selectedRows = selectedRows;
			self.currentFilter = [];
			self.next = undefined;
			self.previous = undefined;
			/**
			 * Selects rows matching a given value into a column and returns a MatrixHelper on the selection
			 * @param String|int col col index or name on which to apply the filter
			 * @param String|Number value value to filter on
			 * @example To filter more that one column at a time, just append (colI,valueI) pairs to the function arguments
			 * wigii().matrix([{project:"P1",grant:"G1",sector:"WASH"},{project:"P2",grant:"G1",sector:"NUT"},{project:"P1",grant:"G2",sector:"GEN"}])
			 * .filter("project","P1","sector","WASH").column("grant") = ["G1"]
			 * @return wigiiApi.MatrixHelper on filtered rows
			 */
			self.filter = function(col,value) {
				var matchingRows = [];
				if(self.selectedRows) {
					for(var i=0;i<self.selectedRows.length;i++) {
						if(value == self.matrix[self.selectedRows[i]][col]) matchingRows.push(self.selectedRows[i]);
					}
				}
				else {
					for(var i=0;i<self.matrix.length;i++) {
						if(value == self.matrix[i][col]) matchingRows.push(i);
					}
				}
				var returnValue = new wigiiApi.MatrixHelper(self.matrix,matchingRows);
				// saves current filter and links filters in chain
				returnValue.currentFilter = self.currentFilter.concat([col,value]);
				self.next = returnValue;
				returnValue.previous = self;
				
				// recursively call filter if more (col,value) pairs are defined
				var args;
				if(arguments.length > 2) args = Array.prototype.slice.call(arguments,2);
				else args = [];
				if(args.length>0) {
					returnValue = returnValue.filter.apply(returnValue,args);
				}
				
				return returnValue;
			};
			/**
			 * Returns an array with the content of a column filtered with the selected rows.
			 * @param String|int col col index or name from which to extract the values
			 * @param boolean unique if true, then filters duplicates and returns unique values, else returns all values
			 * @return Array
			 */
			self.column = function(col,unique) {
				var returnValue = (unique?{}:[]);
				if(self.selectedRows) {
					for(var i=0;i<self.selectedRows.length;i++) {
						var val = self.matrix[self.selectedRows[i]][col];
						if(unique) returnValue[val] = val;
						else returnValue.push(val);
					}
				}
				else {
					for(var i=0;i<self.matrix.length;i++) {
						var val = self.matrix[i][col];
						if(unique) returnValue[val] = val;
						else returnValue.push(val);
					}
				}
				return (unique?Object.keys(returnValue):returnValue);
			};
			/**
			 * Returns an array with the selected rows
			 * @return Array
			 */
			self.rows = function() {
				var returnValue = [];
				if(self.selectedRows) {
					for(var i=0;i<self.selectedRows.length;i++) {
						returnValue.push(self.matrix[self.selectedRows[i]]);
					}
				}
				else {
					for(var i=0;i<self.matrix.length;i++) {
						returnValue.push(self.matrix[i]);
					}
				}
				return returnValue;
			};	
			/**
			 * Clears the current selection and returns a MatrixHelper showing previous selection or all rows if no previous one
			 * @param String|int col optional column to define which column should be cleared. If not defined, takes current selection, which is last column.
			 * @return wigiiApi.MatrixHelper
			 */
			self.clear = function(col) {
				var returnValue = undefined;
				if(col!==undefined && self.currentFilter.length>2 && self.currentFilter[self.currentFilter.length-3]!=col) {
					var newSelection = [];
					var i=0;
					while(i<self.currentFilter.length) {
						if(self.currentFilter[i]!=col) {
							newSelection.push(self.currentFilter[i]);
							newSelection.push(self.currentFilter[i+1]);
						}						
						i+=2;
					}
					returnValue = new wigiiApi.MatrixHelper(self.matrix);
					if(newSelection.length>0) returnValue = returnValue.filter.apply(newSelection);
				}
				else if(self.previous) {
					self.previous.next = undefined;
					returnValue = self.previous;
					self.previous = undefined;
				}
				else returnValue = new wigiiApi.MatrixHelper(self.matrix);
				return returnValue;
			};
			/**
			 * Clears all selections and returns a MatrixHelper showing all rows
			 * @return wigiiApi.MatrixHelper
			 */
			self.clearAll = function() {
				return new wigiiApi.MatrixHelper(self.matrix);
			};
		});
		
		ncddoc(function(){/**
		 * A constraint on a selected set of drop-downs.
		 * The drop-downs can only show values which are compatible with the given matrix.
		 * The matrix is a set of rows, each row is a vector giving one possible combination of values for the drop-downs.
		 * The order of the selected drop-downs must match the order of the columns in the rows, 
		 * or the drop-down field name must match a field name in the row.
		*/},
		wigiiApi.DropDownConstraint = function(selector,matrix) {
			var self = this;
			self.className = 'DropDownConstraint';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			self.matrixHelper = wigiiApi.getMatrixHelper(matrix);
			self.lock=false;
			if(self.matrixHelper.matrix.length>0) {
				self.dropDowns = selector;
				// stores associated field names
				if($.type(self.matrixHelper.matrix[0])!=='array') {
					self.fieldNames = [];
					self.dropDowns.each(function(){
						self.fieldNames.push($(this).closest('div.field').wigii('FieldHelper').fieldName());
					});
				}
				// indexes current set of drop-downs values
				self.dropDownIndex = (self.fieldNames?{}:[]);
				self.dropDowns.each(function(){
					var dropDown = $(this);
					// retrieves matrix column associated to drop-down
					var col=self.dropDowns.index(dropDown);
					if(self.fieldNames) col = self.fieldNames[col];	
					// loops on all options and indexes them
					var options = {};					
					dropDown.find('option').each(function(){
						var option = $(this);
						var val = option.attr('value');
						if(val!=='' && val!==null) {
							options[val] = {
								'value':val,
								'title':option.attr('title'),
								'label':option.text()
							};
						}
					});
					self.dropDownIndex[col] = options;
				});
				// manages constraint on drop-down change
				self.dropDowns.change(function(){
					if(self.lock) return;
					self.lock=true;
					var dropDown = $(this);
					// retrieves matrix column associated to drop-down
					var col=self.dropDowns.index(dropDown);
					if(self.fieldNames) col = self.fieldNames[col];
					
					
					var newFilter = self.matrixHelper;
					var val = dropDown.val();
					// removes column from existing filter and adds column to new filter
					if(val!=='' && val!==null) {
						if(newFilter.currentFilter.length>0 && newFilter.currentFilter.indexOf(col)%2==0) newFilter = newFilter.clear(col);
						newFilter = newFilter.filter(col,val);
					}
					// if val is empty then clears all
					else newFilter = newFilter.clearAll();
					
					
					// builds each drop-down options based on new filter
					self.dropDowns.each(function(){
						var dropDown = $(this);
						var currentVal = dropDown.val();
						// retrieves matrix column associated to drop-down
						var col=self.dropDowns.index(dropDown);
						if(self.fieldNames) col = self.fieldNames[col];	
						// clears all current options
						dropDown.empty();
						// fills again the options based on new filtered column
						var colValues = newFilter.column(col,true);						
						var dropDownValues = self.dropDownIndex[col];
						var html = wigiiApi.getHtmlBuilder();
						dropDown.append(html.reset().putStartTag('option','value',"",'title',"").put("").putEndTag('option').html());
						var newVal = '';
						for(var i=0;i<colValues.length;i++) {
							var option = dropDownValues[colValues[i]];
							dropDown.append(html.reset().putStartTag('option','value',option.value,'title',option.title).put(option.label).putEndTag('option').html());
							if(currentVal==option.value) newVal = currentVal;
						}
						if(colValues.length==1) newVal = dropDownValues[colValues[0]].value;
						if(newFilter.currentFilter.length==0) newVal='';//resets everything if filter is reset
						dropDown.val(newVal).trigger('change');
					});
					self.matrixHelper = newFilter;
					self.lock=false;
				});
				// first load
				$(self.dropDowns[0]).trigger('change');
			}
		});
		
		ncddoc(function(){/**
		 * JQuery collection event handlers
		*/},
		wigiiApi.JQueryService = function() {
			var self = this;
			self.className = 'JQueryService';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;	
			//self.debugLogger = wigiiApi.getDebugLogger(self.className);
			
			self.bindHelpService = function(selection,options) {				
				//self.debugLogger.logBeginOperation('bindHelpService');				
				var helpService = wigiiApi.getHelpService();
				var optionsFx = ($.type(options) === 'function');
				// finds closes div.value and ensures overflow is visible
				var visibleOverflowOnEnclosingValueDiv = function(startElt) {
					var valueDiv = startElt.closest('div.value, div.field');
					if(valueDiv.length > 0 && valueDiv.hasClass('value')) valueDiv.css('overflow','visible');
				}
				if(options && (options.content || optionsFx)) {			
					var contentFx = !optionsFx && ($.type(options.content) === 'function');
					selection.each(function(i){
						var e = $(this);						
						var content, opts;
						if(optionsFx) {
							opts = options(e); content = opts.content;
						}
						else {
							content = (contentFx?options.content(e):options.content);
							// clones the options
							opts = {};
							for(var attr in options) {
								opts[attr] = options[attr];
							}
						}			
						//self.debugLogger.write("index:"+i+", optionsFx:"+optionsFx+", contentFx:"+contentFx+", options:"+JSON.stringify(options));
						if(content) {
							helpService.renderHelpAnchor(e, content, opts);							
						}
					});
				}
				else {
					selection.each(function(i){
						var e = $(this);
						var content = undefined;
						var opts = {width:(e.attr("data-popup-width")?e.attr("data-popup-width"):400),
							height:(e.attr("data-popup-height")?e.attr("data-popup-height"):400),
							type:(e.attr("data-popup-type")?e.attr("data-popup-type"):'help'),
							position:(e.attr("data-popup-position")?e.attr("data-popup-position"):'SE')
						};						
						// case: div with localContent
						if(e.is("div.localContent")) {
							content = e.html();							
							opts.localContent = true;							
							e = e.parent().find("div.value");							
							if(content) {
								helpService.renderHelpAnchor(e, content, opts);							
							}							
						}			
						// case: div with remoteContent
						else if(e.is("div.remoteContent")) {
							content = e.text();/* remote content is an url, so retrieves it as text to avoid double coding of amperstands */							
							opts.localContent = false;							
							e = e.parent().find("div.value");							
							if(content) {
								helpService.renderHelpAnchor(e, content, opts);							
							}							
						}
						// case: div with json data containing remote content
						else if(e.is("div.json")) {
							content = e.html();
							if(content) {
								content = JSON.parse(content);
								if(!content['width']) content.width=opts.width;
								if(!content['height']) content.height=opts.height;
								if(!content['type']) content.type=opts.type;
								opts=content;
								e = e.parent().find("div.value");								
								if(opts.content) {
									helpService.renderHelpAnchor(e, opts.content, opts);							
								}	
							}													
						}
						// case: a with href
						else if(e.is("a")) {
							content = e.attr('href');
							opts.localContent = false;
							opts.domInsertionMode = 'after';
							e.hide();
							// if e is into a div.value, ensures overflow is visible
							visibleOverflowOnEnclosingValueDiv(e);
							if(content) {
								helpService.renderHelpAnchor(e, content, opts);							
							}
						}
						// case: span with localContent
						else if(e.is("span")) {
							content = e.html();
							opts.localContent = true;
							opts.domInsertionMode = 'after';
							e.hide();
							// if e is into a div.value, ensures overflow is visible
							visibleOverflowOnEnclosingValueDiv(e);
							if(content) {
								helpService.renderHelpAnchor(e, content, opts);							
							}
						}
					});					
				}
				//self.debugLogger.logEndOperation('bindHelpService');
			};	
			
			self.FieldHelper = function(selection,options) {
				//var ctxKey = self.ctxKey+"_FieldHelper";
				var returnValue=undefined;
				// filters on Fields				
				if(selection && selection.length>0) {
					selection = selection.filter('div.field');
					if(selection && selection.length>0) {
						returnValue = wigiiApi.getFieldHelper();
						returnValue.attach(selection, options);
					}
				}								
				return (!returnValue?{$:selection}:returnValue);
			};
			
			self.FormHelper = function(selection,options) {
				//var ctxKey = self.ctxKey+"_FormHelper";
				var returnValue=undefined;
				// checks we have only one element (no strict checking on Wigii form identity)				
				if(selection && selection.length==1) {
					returnValue = wigiiApi.getFormHelper();
					returnValue.attach(selection, options);
				}
				else if(selection && selection.length>1) throw wigiiApi.createServiceException('Wigii FormHelper selector can only be activated on a JQuery collection containing one element and not '+selection.length, wigiiApi.errorCodes.INVALID_ARGUMENT);
				return (!returnValue?{$:selection}:returnValue);
			};
			
			self.DropDownConstraint = function(selection,matrix) {
				//var ctxKey = self.ctxKey+"_DropDownConstraint";
				var returnValue=undefined;
				// filters on select objects				
				if(selection && selection.length>0) {
					selection = selection.filter('select');
					if(selection && selection.length>0) {
						returnValue = wigiiApi.createDropDownConstraint(selection,matrix);
					}
				}								
				return (!returnValue?{$:selection}:returnValue);
			};
			
			self.ButtonHelper = function(selection,options) {
				var returnValue=undefined;
				// checks we have at least one selected button like element	
				if(selection && selection.length>0) {
					returnValue = wigiiApi.createButtonHelper(selection,options);
				}
				return (!returnValue?{$:selection}:returnValue);
			};
			
			/**
			 * Fills a set of drop-downs given some options
			 * @param Object|Array options a bag of parameters of the form
			 * - arr|array: Array containing the values or objects used to populate the drop-down
			 * - val|value: String|Function the name of a field in the object to be used as a drop-down value or a callback function computing the drop-down values.
			 * The callback function is of the form value(i,data,selector) where i=0..array.length, data = array[i], selector is the given JQuery selector to access the DOM context.
			 * The function should return a scalar value used a the drop-down value.
			 * - lab|label String|Function the name of a field in the object to be used as a drop-down label or a callback function computing the drop-down labels.
			 * The callback function is of the form label(i,data,selector) where i=0..array.length, data = array[i], selector is the given JQuery selector to access the DOM context.
			 * The function should return a scalar value used a the drop-down label.
			 * If options is an array, then considers it as an array of values used to fill the drop-down.
			 * @return JQuery selector for chaining
			 */
			self.fillDropDown = function(selection,options) {
				var returnValue=undefined;
				// filters on select objects				
				if(selection && selection.length>0) {
					selection = selection.filter('select');
					if(selection && selection.length>0) {
						if($.isArray(options)) options = {arr:options};
						else if(!options) options = {};
						var arr = options.arr || options.array;
						var val = options.val || options.value;
						var lab = options.lab || options.label;
						selection.each(function(){
							var dropdown = $(this);
							wigiiApi.fillDropDownFromArray(dropdown,arr,val,lab,options);
						});
						returnValue = selection;
					}
				}								
				return (!returnValue?{$:selection}:returnValue);
			};
		});
		
		ncddoc(function(){/**
		 * DataFlowService javascript implementation
		*/},
		wigiiApi.DataFlowService = function() {
			var self = this;			
			self.className = 'DataFlowService';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			
			/**
			 * Reads the data coming from the source and processes it through the selected flow activities
			 *@param Function source a data flow source function. 
			 * A data source function is a function which takes an open DataFlowContext in parameter and then calls processDataChunk as many times as needed.
			 * var rangeGen = function(from,to,step) { return function(dataFlowContext) {
			 *	for(var i=from;i<=to;i+=step) dataFlowContext.processDataChunk(i);
			 * }};
			 *@param Array activities an array of activity functions. An activity function is a function which receives the current chunk of data, 
			 * an activity context in which a processing state can be stored and the data flow context to get insight on the global state of the data flow beeing executed.
			 * var power = function(factor) { return function(data,activityCtx,dataFlowContext) {
			 * 		var returnValue = 1;
			 *		for(var i=0;i<factor;i++) returnValue *= data;
			 *		return returnValue;
			 * }};
			 * var sum = function(data,activityCtx,dataFlowContext) {
			 *	 switch(activityCtx.state) {
			 *	 	case dataFlowContext.DFA_STARTSTREAM: activityCtx.sum = 0; break;
			 *		case dataFlowContext.DFA_RUNNING: activityCtx.sum += data; break;
			 *		case dataFlowContext.DFA_ENDSTREAM: dataFlowContext.writeResultToOuput(activityCtx.sum,activityCtx); break;
			 *	 }	
			 * };
			 *@example wigii().getDataFlowService().processDataSource(rangeGen(-10,10,2),[power(3),sum])
			 *@return mixed the data flow result
			 */
			self.processDataSource = function(source,activities) {
				var dfCtx = self.startStream(activities);
				source(dfCtx);
				return self.endStream(dfCtx);
			};
			
			/**
			 * Starts a new data flow stream
			 *@param Array activities an array functions describing the pipe of activities
			 *@return wigiiApi.DataFlowService.DataFlowContext a DataFlowContext instance referencing the open stream
			 */
			self.startStream = function(activities) {
				return new self.DataFlowContext(self, activities);
			};
			/**
			 * Processes a data chunk in the context of an open stream
			 *@param mixed data chunk of data to be processed.
			 * Must be compatible with the first step of the pipe of activities
			 *@param wigiiApi.DataFlowService.DataFlowContext the reference to the current open stream
			 */
			self.processDataChunk = function(data,dataFlowContext) {
				if(!dataFlowContext.impl.dataFlow) throw wigiiApi.createServiceException('DataFlow is not running, call startStream to start it', wigiiApi.errorCodes.INVALID_STATE);
				// pushes data to first activity
				var dataFlowActivity = self.impl.getOpenedDataFlowActivityForStep(0,dataFlowContext);							
				dataFlowActivity.state = dataFlowContext.DFA_RUNNING;
				var result = dataFlowContext.activities[0](data,dataFlowActivity,dataFlowContext);
				if(result !== undefined) dataFlowContext.writeResultToOuput(result,dataFlowActivity);
			};
			/**
			 * Ends the current running data flow stream
			 * After the call of this method, the DataFlowContext is closed and any calls to processDataChunk will fail.			
			 *@param wigiiApi.DataFlowService.DataFlowContext the reference to the current open stream
			 *@return optionally returns some data if the last stage of the list of activities writes some output.
			 */
			self.endStream = function(dataFlowContext) {
				var returnValue = undefined;
				if(dataFlowContext.impl.dataFlow) {
					for(var stepId=0; stepId < dataFlowContext.activities.length; stepId++) {
						// if activity is open, 
						if(stepId < dataFlowContext.impl.dataFlow.length) {
							var dataFlowActivity = dataFlowContext.impl.dataFlow[stepId];
							// calls end stream on activity
							dataFlowActivity.state = dataFlowContext.DFA_ENDSTREAM;
							dataFlowContext.activities[stepId](undefined,dataFlowActivity,dataFlowContext);
							// flushes output buffer
							returnValue = self.impl.flushStepBufferIntoActivity(stepId,dataFlowContext);
						}
					}
					dataFlowContext.impl.dataFlow = undefined;
				}
				return returnValue;
			};
			
			/**
			 * An open data flow context for the given list of activities
			 */
			self.DataFlowContext = function(dataFlowService, activities) {
				var self = this;
				self.className = 'DataFlowContext';
				self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
				self.impl = {dfS:dataFlowService,dataFlow:[]};
				
				/**
				 * Data Flow Activity state START OF STREAM
				 */
				self.DFA_STARTSTREAM = 1;
				/**
				 * Data Flow Activity state RUNNING
				 */
				self.DFA_RUNNING = 2;
				/**
				 * Data Flow Activity state END OF STREAM
				 */
				self.DFA_ENDSTREAM = 3;							
				
				// Accessors
				
				self.activities = activities;
				
				/**
				 * Returns a reference to the underlying DataFlow Service
				 */
				self.getDataFlowService = function() {return self.impl.dfS;};
				
				// Methods 
				
				/**
				 * Writes some data to the output data flow
				 * The underlying DataFlowService will process the data chunk and
				 * call if needed the next steps in the data flow chain.
				 * This method can be called as many times a needed.
				 * Each call results in one data chunk to be passed to the DataFlowService for further processing.
				 * @param mixed resultData some result data, can be any kind of object
				 * @param wigiiApi.DataFlowService.DataFlowActivityContext the reference to the current executing dataflow activity
				 */
				self.writeResultToOuput = function(resultData,dataFlowActivityContext) {
					self.impl.dfS.impl.processResultFromActivity(resultData,dataFlowActivityContext);
				};		

				/**
				 * Shortcut on self.getDataFlowService().processDataChunk
				 * Pushes a data chunk at the beginning of the flow
				 */
				self.processDataChunk = function(data) {
					self.impl.dfS.processDataChunk(data,self);
				};
			};
			
			/**
			 * A running data flow activity context
			 */
			self.DataFlowActivityContext = function(stepId,dataFlowContext) {
				var self = this;
				self.className = 'DataFlowActivityContext';
				self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
				self.impl = {dfCtx:dataFlowContext, stepBuffer:[]};
				/**
				 * The current executing step ID in this DataFlow
				 */
				self.stepId = stepId;
				/**
				 * The current state of this activity in this DataFlow. 
				 * One of DataFlowContext.DFA_STARTSTREAM, DataFlowContext.DFA_RUNNING, DataFlowContext.DFA_ENDSTREAM.
				 */
				self.state = undefined;
				/**
				 * True if the current step is the last step of the dataflow
				 * Or equivalently if no more DataFlowActivity is coming after the one which is currently executing
				 */
				self.isCurrentStepTheLastStep = (stepId==dataFlowContext.activities.length-1);
			};
			
			// Implementation
			
			self.impl = {};
			self.impl.processResultFromActivity = function(resultData,dataFlowActivityContext) {
				if(resultData !== undefined) {
					// if buffer is not empty, flushes it
					if(dataFlowActivityContext.impl.stepBuffer.length>0) self.impl.flushStepBufferIntoActivity(dataFlowActivityContext.stepId, dataFlowActivityContext.impl.dfCtx);
					// stores data in buffer
					dataFlowActivityContext.impl.stepBuffer.push(resultData);
				}
			};
			self.impl.getOpenedDataFlowActivityForStep = function(stepId,dataFlowContext) {
				var returnValue = undefined;
				// checks if current step is already running
				if(!dataFlowContext.impl.dataFlow) dataFlowContext.impl.dataFlow = [];
				if(stepId < dataFlowContext.impl.dataFlow.length) returnValue = dataFlowContext.impl.dataFlow[stepId];
				// if not, then creates it and starts the stream on it
				else {
					returnValue = new self.DataFlowActivityContext(stepId,dataFlowContext);
					dataFlowContext.impl.dataFlow.push(returnValue);
					returnValue.state = dataFlowContext.DFA_STARTSTREAM;
					dataFlowContext.activities[stepId](undefined,returnValue,dataFlowContext);
				}
				return returnValue;
			};
			self.impl.flushStepBufferIntoActivity = function(stepId,dataFlowContext) {
				if(!dataFlowContext.impl.dataFlow) throw wigiiApi.createServiceException('DataFlow is not running, call startStream to start it', wigiiApi.errorCodes.INVALID_STATE);
				if(stepId < dataFlowContext.impl.dataFlow.length) {
					var dataFlowActivity = dataFlowContext.impl.dataFlow[stepId];
					// flushes buffer in next activity if not last one.
					if(!dataFlowActivity.isCurrentStepTheLastStep) {
						var nextStepId = stepId+1;
						while(dataFlowActivity.impl.stepBuffer.length>0) {
							var data = dataFlowActivity.impl.stepBuffer.shift();							
							var nextActivityContext = self.impl.getOpenedDataFlowActivityForStep(nextStepId,dataFlowContext);
							// pushes data to next activity
							nextActivityContext.state = dataFlowContext.DFA_RUNNING;
							var result = dataFlowContext.activities[nextStepId](data,nextActivityContext,dataFlowContext);
							if(result !== undefined) dataFlowContext.writeResultToOuput(result,nextActivityContext);
						}
					}
					// if last step, returns buffered value
					else if(dataFlowActivity.impl.stepBuffer.length>1) throw wigiiApi.createServiceException('On the DataFlow last step, only one result can be generated. You should add a new step to merge the multiple results into one.', wigiiApi.errorCodes.INVALID_STATE);
					else return dataFlowActivity.impl.stepBuffer.shift();
				}
			};
		});
		
		
		
		
		
		
		// Models
		
		ncddoc(function(){/**
		 * A Wigii FormEvent
		*/},
		wigiiApi.FormEvent = function(eventName,formId,fieldId,fieldName) {
			var self = this;
			self.className = 'FormEvent';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;	
			
			self.context = {
				'eventName':eventName,
				'formId':formId,
				'fieldId':fieldId,
				'fieldName':fieldName
			};
			
			self.eventName = function() {return self.context.eventName;};
			self.formId = function() {return self.context.formId;};
			self.fieldId = function() {return self.context.fieldId;};
			self.fieldName = function() {return self.context.fieldName;};
			self.subFieldName = function(subFieldName) {
				if(subFieldName) self.context.subFieldName = subFieldName;
				else return self.context.subFieldName;
			};
			self.value = function(value) {
				if(value) self.context.value = value;
				else return self.context.value;
			};
			self.dataType = function(dataType) {
				if(dataType) self.context.dataType = dataType;
				else return self.context.dataType;
			};
			self.toJson = function() {
				if(self.context) return JSON.stringify(self.context);
				else return '';
			};
		});
		
		ncddoc(function(){/**
		 * A Wigii Field
		 * 
		 * @param String fieldName the name of the field
		 * @param String dataType the Wigii DataType name of the field
		 * @param String label a label for the end user (already translated)
		 * @param Object attributes optional map of attributes
		*/},
		wigiiApi.Field = function(fieldName,dataType,label,attributes) {
			var self = this;
			self.className = 'Field';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;	
			
			self.context = {
				'fieldName':fieldName,
				'dataType':dataType,
				'label':label,
				'attributes':attributes
			};
			
			self.fieldName = function(){return self.context.fieldName;};
			self.dataType = function(){return self.context.dataType;};
			self.label = function(label) {
				if(label===undefined) return self.context.label;
				else self.context.label = label;
			};
			self.attribute = function(name,value) {
				if(!name) throw wigiiApi.createServiceException('attribute name cannot be null', wigiiApi.errorCodes.INVALID_ARGUMENT);
				if(!self.context.attributes) self.context.attributes = {};
				if(value===undefined) return self.context.attributes[name];
				else self.context.attributes[name] = value;
			};
		});
		
		ncddoc(function(){/**
		 * A Wigii FieldList
		*/},
		wigiiApi.FieldList = function() {
			var self = this;
			self.className = 'FieldList';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			
			self.context = {
				'indexByName':{},
				'indexByPos':[]
			};
			
			/**
			 * Adds a field to the FieldList
			 * @param Field field the field to add to the list
			 * @return FieldList for chaining
			 * @throws ServiceException::ALREADY_EXISTS if a field which same name is already in the list.
			 */
			self.addField = function(field) {
				if(!field) throw wigiiApi.createServiceException('field cannot be null', wigiiApi.errorCodes.INVALID_ARGUMENT);
				var fieldName = field.fieldName();
				if(self.doesFieldExist(fieldName)) wigiiApi.createServiceException("field '"+fieldName+"' already exists in the list", wigiiApi.errorCodes.ALREADY_EXISTS);
				self.context.indexByName[fieldName] = field;
				self.context.indexByPos.push(fieldName);
			};
			/**
			 * Checks if a Field with the given name exists in the list
			 * @param String fieldName the field name to check
			 * @return Field return Field if exists in the list else undefined
			 */
			self.doesFieldExist = function(fieldName) {
				if(!fieldName) return undefined;
				return self.context.indexByName[fieldName];
			};
			/**
			 * Return the Field in the list given its name.
			 * @param String fieldName the name of the field to retrieve
			 * @return Field the field
			 * @throws ServiceException::DOES_NOT_EXIST if no Field with this name exist in the list
			 */
			self.getField = function(fieldName) {
				if(!fieldName) throw wigiiApi.createServiceException('fieldName cannot be null', wigiiApi.errorCodes.INVALID_ARGUMENTfield.fieldName());
				var returnValue = self.context.indexByName[fieldName];
				if(!returnValue) throw wigiiApi.createServiceException("field '"+fieldName+"' does not exist in the list", wigiiApi.errorCodes.DOES_NOT_EXIST);
				return returnValue;
			};
			/**
			 * @return Boolean true if list is empty, else false
			 */
			self.isEmpty = function() {
				return (self.context.indexByPos.length==0);
			};
			/**
			 * @return Int the number of elements in the list, 0 if empty.
			 */
			self.count = function() {
				return self.context.indexByPos.length;
			};
			/**
			 * Iterates other the list and calls the given callback function
			 * @param Function callback function of the form callback(index,Field) where index=0..count-1.
			 */
			self.each = function(callback) {
				if(!$.isFunction(callback)) throw wigiiApi.createServiceException('callback should be a function of the form callback(index,Field)', wigiiApi.errorCodes.INVALID_ARGUMENT);
				var indexByPos = self.context.indexByPos;
				var indexByName = self.context.indexByName;
				for(var i=0;i<indexByPos.length;i++) {
					callback(i,indexByName[indexByPos[i]]);
				}
			};
		});
		
		ncddoc(function(){/**
		 * Wigii Bag
		*/},
		wigiiApi.WigiiBag = function() {
			var self = this;
			self.className = 'WigiiBag';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			
			self.context = {
				'bag':{}
			};
			
			/**
			 * Returns the value of the field given its name and optional subFieldName
			 * @param fieldName the name of the Field
			 * @param subFieldName the name of the subfield. If undefined, assumes 'value' subField.
			 * @return String|Number the field value or undefined if field has no value.
			 */
			self.getValue = function(fieldName,subFieldName) {
				if(!fieldName) throw wigiiApi.createServiceException('fieldName cannot be null', wigiiApi.errorCodes.INVALID_ARGUMENT);				
				var fieldValue = self.context.bag[fieldName];
				if(fieldValue) {
					if(!subFieldName) subFieldName = 'value';
					return fieldValue[subFieldName];
				}
				else return undefined;
			};
			/**
			 * Stores the value in the WigiiBag. Replaces any existing value for this fieldName and subFieldName
			 * @param String|Number value the value to store in the WigiiBag
			 * @param fieldName the name of the Field
			 * @param subFieldName the name of the subField. If undefined, assumes 'value' subField.
			 */
			self.setValue = function(value,fieldName,subFieldName) {
				if(!fieldName) throw wigiiApi.createServiceException('fieldName cannot be null', wigiiApi.errorCodes.INVALID_ARGUMENT);
				var fieldValue = self.context.bag[fieldName];
				if(!fieldValue) {
					fieldValue = {};
					self.context.bag[fieldName] = fieldValue;
				}
				if(!subFieldName) subFieldName = 'value';
				fieldValue[subFieldName] = value;
			};
		});
		
		ncddoc(function(){/**
		 * A Wigii Record
		 * @param FieldList fieldList optional predefined FieldList of the Record.
		 * @param WigiiBag wigiiBag optional predefined WigiiBag of the Record.
		*/},
		wigiiApi.Record = function(fieldList,wigiiBag) {
			var self = this;
			self.className = 'Record';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;
			
			self.context = {
				'fieldList':fieldList||wigiiApi.createFieldListInstance(),
				'wigiiBag':wigiiBag||wigiiApi.createWigiiBagInstance()
			};
			
			/**
			 * @return FieldList
			 */
			self.fieldList = function() {return self.context.fieldList;};
			/**			
			 * @return WigiiBag
			 */
			self.wigiiBag = function() {return self.context.wigiiBag;};
			
			/**
			 * Creates a new Field in the Record and adds it at the end of the list
			 * @param String fieldName the name of the field
			 * @param String dataType the Wigii DataType name of the field
			 * @param String label a label for the end user (already translated)
			 * @param Object attributes optional map of attributes
			 * @return Record for chaining
			 */
			self.createField = function(fieldName,dataType,label,attributes) {
				var field = wigiiApi.createFieldInstance(fieldName, dataType, label, attributes);
				self.fieldList().addField(field);
				return self;
			};
			/**
			 * Returns the value of the Field in the Record
			 * @param String fieldName the name of the field
			 * @param String subFieldName the name of the subfield. If undefined, assumes 'value' subfield.
			 * @return String|Number the field value or undefined if field does not exist.
			 */
			self.getFieldValue = function(fieldName,subFieldName) {
				return self.wigiiBag().getValue(fieldName,subFieldName);
			};
			/**
			 * Sets the value of a Field in the Record
			 * @param String|Number value the field value to store into the Record
			 * @param String fieldName the name of the Field
			 * @param subFieldName the name of the subfield. If undefined, assumes 'value' subfield.
			 * @return Record for chaining
			 */
			self.setFieldValue = function(value,fieldName,subFieldName) {
				self.wigiiBag().setValue(value,fieldName,subFieldName);
				return self;
			};
		});
		
		// ServiceProvider
		
		ncddoc(function(){/**
		 * Lookups a service instance given a service name
		 * returns undefined if not found
		*/},
		wigiiApi.lookupService = function(serviceName) {
			if(!serviceName) return;
			var srvGetter = wigiiApi['get'+serviceName];
			if($.type(srvGetter) === 'function') {
				return srvGetter();
			}
		});
		
		ncddoc(function(){/**
		 * Returns an instance of the debugger attached to the given class
		*/},
		wigiiApi.getDebugLogger = function(typeName) {
			return new wigiiApi.DebugLogger(typeName);
		});
		
		ncddoc(function(){/**
		 * Returns the ExceptionSink instance attached to the API
		*/},
		wigiiApi.getExceptionSink = function() {
			if(!wigiiApi['exceptionSinkInstance']) {
				wigiiApi.exceptionSinkInstance = new wigiiApi.ExceptionSink();
			}
			return wigiiApi.exceptionSinkInstance;
		});
		
		ncddoc(function(){/**
		 * Creates a new PopUp instance
		*/},
		wigiiApi.createPopUpInstance = function(anchor,options) {
			return new wigiiApi.PopUp(anchor,options);
		});
		
		ncddoc(function(){/**
		 * Returns a HelpService instance
		*/},
		wigiiApi.getHelpService = function() {
			if(!wigiiApi['helpServiceInstance']) {
				wigiiApi.helpServiceInstance = new wigiiApi.HelpService();
			}
			return wigiiApi.helpServiceInstance;
		});
		
		ncddoc(function(){/**
		 * Returns a JQueryService instance
		*/},
		wigiiApi.getJQueryService = function() {
			if(!wigiiApi['jQueryServiceInstance']) {
				wigiiApi.jQueryServiceInstance = new wigiiApi.JQueryService();
			}
			return wigiiApi.jQueryServiceInstance;
		});
		
		ncddoc(function(){/**
		 * Returns a WncdContainer instance
		 * @param Object wncd A Wigii NCD Lib instance (normally the defined wncd symbol)
		*/},
		wigiiApi.getWncdContainer = function(wncd) {
			if(!wigiiApi['wncdContainerInstance']) {
				wigiiApi.wncdContainerInstance = new wigiiApi.WncdContainer(wncd);
			}
			return wigiiApi.wncdContainerInstance;
		});
		
		ncddoc(function(){/**
		 * Creates a function that can be used to attach a comment and some custom attributes to a given function.
		 * The comment or the attributes are stored in the wncdAttr object attached to the function 
		 * and can be used as meta information further down in the code.
		 * The function has the following signature wncdAttr(comment,key1,val1,...,keyn,valn,f) where
		 * - Arg(0) comment. A comment describing the function, as a string or as a source code native comment wrapped into a function
		 * - Arg(1..n) keyI, valI: Between first argument comment and last argument f, as many pairs key,value as needed can be inserted. These pairs key:value will be added to the attached wncdAttr object.
		 * - Arg(last) f: Function|Object. The function to which to add the NCD attributes. 
		 * The wncdAttr function returns f for chaining.
		 *@param Boolean enable If true, the wncd attributes are actively loaded when the function is executed, else there are ignored. 
		 *@return Function a function to attach comments and custom attributes to functions
		*/},
		wigiiApi.getWncdAttrFx = function(enable) { return wigiiNcdAttr(enable);});
		
		ncddoc(function(){/**
		 * Returns DataFlowService instance
		*/},
		wigiiApi.getDataFlowService = function() {
			if(!wigiiApi['dataflowServiceInstance']) {
				wigiiApi.dataflowServiceInstance = new wigiiApi.DataFlowService();
			}
			return wigiiApi.dataflowServiceInstance;
		});
		
		ncddoc(function(){/**
		 * Creates an HtmlBuilder instance
		*/},
		wigiiApi.getHtmlBuilder = function() {			
			return new wigiiApi.HtmlBuilder();
		});
		
		ncddoc(function(){/**
		 * Creates a FieldHelper instance
		*/},
		wigiiApi.getFieldHelper = function() {
			return new wigiiApi.FieldHelper();
		});
		
		ncddoc(function(){/**
		 * Creates a FormHelper instance
		*/},
		wigiiApi.getFormHelper = function() {
			return new wigiiApi.FormHelper();
		});
		
		ncddoc(function(){/**
		 * Creates a ButtonHelper instance on a given button selector
		*/},
		wigiiApi.createButtonHelper = function(btn,options) {
			return new wigiiApi.ButtonHelper(btn,options);
		});
		
		ncddoc(function(){/**
		 * Creates an ArrayHelper instance
		*/},
		wigiiApi.getArrayHelper = function(arr) {
			return new wigiiApi.ArrayHelper(arr);
		});
		
		ncddoc(function(){/**
		 * Creates a MatrixHelper instance
		*/},
		wigiiApi.getMatrixHelper = function(matrix) {
			return new wigiiApi.MatrixHelper(matrix);
		});
		
		ncddoc(function(){/**
		 * Creates a DropDownConstraint and binds it to some selected drop-downs
		*/},
		wigiiApi.createDropDownConstraint = function(selector,matrix) {
			return new wigiiApi.DropDownConstraint(selector,matrix);
		});
		
		ncddoc(function(){/**
		 * Creates a FormEvent instance
		*/},
		wigiiApi.createFormEventInstance = function(eventName,formId,fieldId,fieldName) {
			return new wigiiApi.FormEvent(eventName,formId,fieldId,fieldName);
		});
		
		ncddoc(function(){/**
		 * Creates a Field instance
		*/},
		wigiiApi.createFieldInstance = function(fieldName,dataType,label,attributes) {
			return new wigiiApi.Field(fieldName,dataType,label,attributes);
		});
		
		ncddoc(function(){/**
		 * Creates a FieldList instance
		*/},
		wigiiApi.createFieldListInstance = function() {
			return new wigiiApi.FieldList();
		});
		
		ncddoc(function(){/**
		 * Creates a WigiiBag instance
		*/},
		wigiiApi.createWigiiBagInstance = function() {
			return new wigiiApi.WigiiBag();
		});
		
		ncddoc(function(){/**
		 * Creates a Record instance
		*/},
		wigiiApi.createRecordInstance = function(fieldList,wigiiBag) {
			return new wigiiApi.Record(fieldList,wigiiBag);
		});
		
		ncddoc(function(){/**
		 * @return wigiiApi.MatrixHelper
		*/},
		wigiiApi.matrix = function(matrix,selectedRows) {
			return new wigiiApi.MatrixHelper(matrix,selectedRows);
		});		
		
		// Wigii client
					
		ncddoc(function(){/**
		 * Logs a message in the console
		 * @param String message the message to log.
		*/},
		wigiiApi.log = function(message) {			
			wigiiApiConsole().log("INFO WigiiApi : "+message);
			return wigiiApi;
		});
		
		ncddoc(function(){/**
		 * Clears the WigiiAPI log console
		*/},
		wigiiApi.clearLog = function() {
			wigiiApiConsole().clear();
			return wigiiApi;
		});
		
		ncddoc(function(){/**
		 * Returns WigiiApi DebugLogger instance
		*/},
		wigiiApi.debugLogger = function() {
			if(!wigiiApi['debugLoggerInstance']) {
				wigiiApi.debugLoggerInstance = wigiiApi.getDebugLogger('WigiiApi');
			}
			return wigiiApi.debugLoggerInstance;
		});
		
		ncddoc(function(){/**
		 * Builds a complete Wigii Update url given a sub-url.
		 * @param String url a logical update url of the form idAnswer/WigiiNamespace/Module/action/parameter
		 * @return String complete encoded url ready to be passed to an AJAX query.
		*/},
		wigiiApi.buildUpdateUrl = function(url) {
			return encodeURI(wigiiApi.SITE_ROOT +"Update/"+window.crtContextId+wigiiApi.EXEC_requestSeparator+url);
		});
		
		ncddoc(function(){/**
		 * Builds a Wigii Update callback function which can be passed as a SUCCESS function to an AJAX query.
		 * @param Object context a map of [idAnswer=>context object] to be passed to the parseUpdateResult function.
		 * @return Function the callback function
		*/},
		wigiiApi.buildUpdateCallback = function(context) {
			return function(data,textStatus){wigiiApi.parseUpdateResult(data,textStatus,context);};
		});
		
		ncddoc(function(){/**
		 * Builds a Wigii url fragment by joining the keys and values given into the urlArgs object
		 * @param Object|Array|String urlArgs or an object with some (key,value) pairs, or an array of values, or a single string.
		 * @return String the built url fragment of the form key1=value1/key2=value2/... or value1/value2/value3
		*/},
		wigiiApi.buildUrlFragment = function(urlArgs) {
			var returnValue = '';
			if($.isArray(urlArgs)) {
				for(var i=0;i<urlArgs.length;i++) {
					if(i>0) returnValue += '/';
					returnValue += urlArgs[i];
				}				
			}
			else if($.isPlainObject(urlArgs)) {				
				for(var key in urlArgs) {
					if(returnValue!='') returnValue += '/';
					returnValue += key+'='+urlArgs[key];
				}
			}
			else returnValue = urlArgs;
			if(returnValue != '') returnValue = encodeURI(returnValue);
			return returnValue;
		});
		
		ncddoc(function(){/**
		 * Wigii Update protocol query answer parser
		 * @param String html string received from server following Wigii Update protocol format
		 * @param String textStatus JQuery AJAX text status
		 * @param Object context the optional map [idAnswer=>context object] used to retrieve contextual data according to idAnswer.
		*/},
		wigiiApi.parseUpdateResult = function(html,textStatus,context) {
			//wigiiApi.debugLogger().logBeginOperation('parseUpdateResult');
			// splits received html in several answers
			var tabReq = html.split(wigiiApi.EXEC_answerRequestSeparator);
			var tabLength = tabReq.length;
			var lookupPaths = {};
			var request,tempCode;
			for (var i=0; i<tabLength; i++){
				// splits request-answer in several parameters
				request = tabReq[i].split(wigiiApi.EXEC_answerParamSeparator);
				if(request != ""){
					// JSCode answer
					if (request[0] == "JSCode"){
						// if a cache key is present, puts js code in cache
						if(request.length > 2){
							// parses JS code
							tempCode = wigiiApi.decHTML(request[2]);
							// detects a PHP Fatal Error answer and cancels js execution
							if(tempCode.substr(0, 6)=="<br />"){
								tempCode = null;
							}
							// replaces actual js code in cache with new one received
							if(request[1] == "foundInCache") {
								if(lookupPaths["foundInCache"]) {
									wigiiApi.setJSCache(lookupPaths["foundInCache"][1], lookupPaths["foundInCache"][0], tempCode);
									wigiiApi.keepInCache(lookupPaths["foundInCache"][0]);
								}
							}
							// puts js code in cache
							else {
								wigiiApi.setJSCache(request[1], lookupPaths[request[1]], tempCode);
								wigiiApi.keepInCache(lookupPaths[request[1]]);
							}						
						} 
						// parses js code
						else {
							tempCode = decHTML(request[1]);
							// detects a PHP Fatal Error answer and cancels js execution
							if(tempCode.substr(0, 6)=="<br />"){
								tempCode = null;
							}
						}
						// executes javascript
						if(!wigiiApi.holdJsCodeAnswers()) eval(tempCode);
						else wigiiApi.log(tempCode);
					} 
					// Alert answer
					else if (request[0] == "Alert"){
						alert(request[1]);
					} 
					// Reload answer
					else if (request[0] == "Reload"){
						window.self.location = request[1];
					} 
					// NoAnswer
					else if (request[0] == "NoAnswer"){
						// detects a cache key
						if(request[1].indexOf('cacheKey_') == 0) {
							lookupPaths[request[0]] = request[1];
						}
						//should never occurs, but if there is an answer
						//then we alert it.
						else if(request[1] != ""){
							if(request.length>2){
								alert(request[1]+'\n'+request[2]);
							} else {
								alert(request[1]);
							}
						}
					} 
					// foundInCache answer
					else if (request[0] == "foundInCache"){
						// detects a cache key
						if(request[1].indexOf('cacheKey_') == 0) {
							lookupPaths[request[0]] = [request[1],request[2]];
						}
					} 
					// Wigii service handlers
					else if(request[0].indexOf('Wigii_') == 0) {
						tempCode = request[0].split('_');
						if(tempCode.length>1) {
							// lookups service
							var srv = wigiiApi.lookupService(tempCode[1]);
							if(srv) {								
								var answerType = 'html';
								var tempCode = request[1];
								// retrieves answer type
								if(request.length>2) {
									answerType = request[1];
									tempCode = request[2];									
								}
								// parses json answer
								if(answerType == 'json') {
									try {
										tempCode = $.parseJSON(tempCode);
									}
									catch(exc) {throw wigiiApi.createServiceException('json response parsing exception', wigiiApi.errorCodes.WRAPPING, exc);}
								}
								// retrieves right context
								var ctx = (context && context[request[0]]?context[request[0]]:context);
								try {
									// calls html handler
									if(answerType=='html' && $.type(srv['html']) === 'function') srv.html(tempCode,ctx);
									// calls json handler
									else if(answerType=='json' && $.type(srv['json']) === 'function' && tempCode) srv.json(tempCode,ctx);
								}
								catch(exc) {wigiiApi.publishException(exc);}
							}
						}
					}
					// DIV answer
					else {				
						// if 'keep' does nothing, keeps actual html content
						if(request[1]=='keep'){
							/* does nothing */
						} 
						// else replaces div content with answer
						else {							
							// very important to stop all the timers on elements that we will destroy.
							$("#"+request[0]).stopTime();
							$("#"+request[0]+" *").stopTime();
							// if cache key, then replaces html and puts in cache
							if(request.length > 2){
								// replaces div html with answer
								$("#"+request[0]).html(request[2]);
								// if empty answer, close open dialog if needed
								if(request[2]==''){
									if($("#"+request[0]).is(':ui-dialog')){
										$("#"+request[0]).dialog("destroy");
									}
								} 
								// puts answer in cache
								else {
									wigiiApi.setCache(request[0], request[1], request[2]);
									wigiiApi.keepInCache(request[1]);
									lookupPaths[request[0]] = request[1];
								}
							// no cache key, only replaces div html with answer
							} else {
								$("#"+request[0]).html(request[1]);
								// if empty answer, close open dialog if needed
								if(request[1]==''){
									if($("#"+request[0]).is(':ui-dialog')){
										$("#"+request[0]).dialog("destroy");
									}
								}
							}
						}
					}
				}
			}
			wigiiApi.clearKeepInCache();
			//wigiiApi.debugLogger().logEndOperation('parseUpdateResult');
		});
		
		ncddoc(function(){/**
		 * Shows the details of the element
		*/},
		wigiiApi.showElement = function(wigiiNamespaceUrl,moduleName,elementId,urlArgs) {
			if(urlArgs) urlArgs = '/'+wigiiApi.buildUrlFragment(urlArgs);
			update('elementDialog/'+wigiiNamespaceUrl+'/'+moduleName+'/element/detail/'+elementId+urlArgs);
		});
		
		ncddoc(function(){/**
		 * Print the details of the element
		*/},
		wigiiApi.printElement = function(wigiiNamespaceUrl,moduleName,elementId) {
			window.open(SITE_ROOT +"usecontext/"+crtContextId+"/__/"+wigiiNamespaceUrl+'/'+moduleName+'/element/print/'+elementId);
		});
		
		ncddoc(function(){/**
		 * Print the details of the element from a template
		*/},
		wigiiApi.printElementWithTemplate = function(wigiiNamespaceUrl,moduleName,elementId,template) {
			if(template) template = "/"+template;
			window.open(SITE_ROOT +"usecontext/"+crtContextId+"/__/"+wigiiNamespaceUrl+'/'+moduleName+'/element/template/'+elementId+template);
		});
		
		ncddoc(function(){/**
		 * Opens a Wigii Form to edit the element
		*/},
		wigiiApi.editElement = function(wigiiNamespaceUrl,moduleName,elementId,urlArgs) {
			if(urlArgs) urlArgs = '/'+wigiiApi.buildUrlFragment(urlArgs);
			update('elementDialog/'+wigiiNamespaceUrl+'/'+moduleName+'/element/edit/'+elementId+urlArgs);
		});
		
		ncddoc(function(){/**
		 * Opens a Wigii Form to add a new element
		*/},
		wigiiApi.addElement = function(wigiiNamespaceUrl,moduleName,groupId,urlArgs) {
			if(urlArgs) urlArgs = '/'+wigiiApi.buildUrlFragment(urlArgs);
			update('elementDialog/'+wigiiNamespaceUrl+'/'+moduleName+'/element/add/'+groupId+urlArgs);
		});
		
		ncddoc(function(){/**
		 * Opens a Wigii Form to copy a new element
		*/},
		wigiiApi.copyElement = function(wigiiNamespaceUrl,moduleName,elementId,groupId,urlArgs) {
			if(urlArgs) urlArgs = '/'+wigiiApi.buildUrlFragment(urlArgs);
			update('elementDialog/'+wigiiNamespaceUrl+'/'+moduleName+'/element/copy/'+elementId+'/'+groupId+urlArgs);
		});
		
		ncddoc(function(){/**
		 * Returns a FormHelper instance centered on current opened Wigii element form
		 * @return wigiiApi.FormHelper
		*/},
		wigiiApi.form = function() {
			var form = $('#elementDialog form');
			if(form.length == 0) form = $('#detailElement_form');
			return form.wigii('FormHelper');
		});
		
		ncddoc(function(){/**
		 * Calls asynchronously a FuncExp on server side through the Fx endpoint.
		 *@param String fx the FuncExp string to be called on server side
		 *@param Object options an optional bag of options. The following options are supported:
		 * - resultHandler: Function. A function to handle the FuncExp result. Function signature is resultHandler(data), where data is the received value of the JQuery ajax call.
		 * - exceptionHandler: Function. A function which handles any thrown exception from server. Function signature is exceptionHandler(exception,context) where exception is Wigii API exception object of the form {name:string,code:int,message:string}
		 * and context is an object with some server context information of the form {request:string, wigiiNamespace:string, module:string, action:string, realUsername:string, username:string, principalNamespace:string, version:string}
		 * If exceptionHandler is not set, then exception is published through the wigii.publishException method.
		 * - silent: Boolean. If silent is true, then no exception handler is called if an error occurs.
		 * - fxEndPoint: URL String. A url which points to a Wigii server Fx endpoint. If not defined calls wigii.SITE_ROOT/crtWigiiNamespace/crtModule/fx
		 * - postData: Object|Array. Some optional data to be posted to the server with the Fx call. The data is serialized as JSON.
		 * - postAsForm: Boolean. If true, the data is posted as an HTTP form, else posted as JSON.
		*/},
		wigiiApi.callFx = function(fx,options) {
			if(!fx) throw wigiiApi.createServiceException('fx cannot be null',wigiiApi.errorCodes.INVALID_ARGUMENT);
			
			// sets default options
			options = options || {};
			if(!options.fxEndPoint) {
				if(window.crtWigiiNamespaceUrl) options.fxEndPoint = wigiiApi.SITE_ROOT+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/fx';
				else options.fxEndPoint = wigiiApi.SITE_ROOT+'/NoWigiiNamespace/NoModule/fx';
			}
			if(!options.fxEndPoint.endsWith('/')) options.fxEndPoint += '/';
			if(!options.exceptionHandler && !options.silent) options.exceptionHandler = function(exception,context) { wigiiApi.publishException(exception); }
			
			// encodes fx call
			fx = $.base64EncodeUrl(fx)
			
			// prepares ajax options		
			var ajaxOptions = {type:"GET",
				url:options.fxEndPoint+fx,
				crossDomain: true,
				xhrFields: {withCredentials: true}
			}		
			if(options.resultHandler) ajaxOptions.success = options.resultHandler;
			if(!options.silent && options.exceptionHandler) ajaxOptions.error = function(xhr,textStatus) {
				// if HTTP error 500, assumes we have a server side exception sent as xml
				if(xhr.status == 500 && xhr.responseXML) {
					var serverError = $(xhr.responseXML);
					// extracts exception part
					var exception = serverError.find('exception');
					if(exception) {
						exception = {
							name: exception.find('name').text(),
							code: exception.find('code').text(),
							message: exception.find('message').text()
						};
					}
					// extracts context part
					var context = serverError.find('context');
					if(context) {
						context = {
							request: context.find('request').text(),
							wigiiNamespace: context.find('wigiiNamespace').text(),
							module: context.find('module').text(),
							action: context.find('action').text(),
							realUsername: context.find('realUsername').text(),
							username: context.find('username').text(),
							principalNamespace: context.find('principalNamespace').text()
						}
					}
					// calls exception handler
					options.exceptionHandler(exception,context);
				}
				// else converts HTTP error to exception
				else options.exceptionHandler({code:xhr.status||wigiiApi.errorCodes.UNKNOWN_ERROR,message:xhr.responseText||"Ajax status: "+textStatus});
			};
			
			// if data, then HTTP POST
			if(options.postData) {
				ajaxOptions.type = "POST";
				if(options.postAsForm) {
					ajaxOptions.data = options.postData;
				}
				else {
					ajaxOptions.contentType = 'text/plain';
					ajaxOptions.dataType = 'json';			
					ajaxOptions.data = JSON.stringify(options.postData);
					ajaxOptions.processData = false;
				}
			}
			
			// Fx Ajax call
			$.ajax(ajaxOptions);
		});
		
		ncddoc(function(){/**
		 * Default Fx error handler that can be plugged as an error callback into a jQuery ajax call.
		 * This error handler publishes the Wigii exception that occured on server side.
		*/},
		wigiiApi.defaultFxErrorHandler = function(xhr,textStatus) {
			var context = undefined;
			var exception = undefined;
			// tries to extract the error as XML
			var serverError = xhr.responseXML;
			if(!serverError) {						
				serverError = xhr.responseText;
				try {
					serverError = $.parseXML(serverError);
				}
				catch(e) { serverError = undefined;}
			}
			// if HTTP error 500, assumes we have a server side exception sent as xml			
			if(xhr.status == 500 && serverError) {									
				serverError = $(serverError);
				// extracts exception part
				exception = serverError.find('exception');
				if(exception) {
					exception = {
						name: exception.find('name').text(),
						code: exception.find('code').text(),
						message: exception.find('message').text()
					};
				}
				// extracts context part
				context = serverError.find('context');
				if(context) {
					context = {
						request: context.find('request').text(),
						wigiiNamespace: context.find('wigiiNamespace').text(),
						module: context.find('module').text(),
						action: context.find('action').text(),
						realUsername: context.find('realUsername').text(),
						username: context.find('username').text(),
						principalNamespace: context.find('principalNamespace').text()
					}
				}						
			}
			// else converts HTTP error to exception (ignores ajax abort) 
			else if(textStatus != 'abort') exception = {code:xhr.status||wigiiApi.errorCodes.UNKNOWN_ERROR,message:xhr.responseText||"Ajax status: "+textStatus};
			// shows exception as a centered popup
			if(exception) wigiiApi.getHelpService().showFloatingHelp(undefined, undefined, wigiiApi.exception2html(exception,context), {localContent:true,position:"center",removeOnClose:true});
		});
		
		ncddoc(function(){/**
		 * Returns current selected language code
		*/},
		wigiiApi.lang = function() {
			return crtLang;
		});
		
		// Functions
		
		ncddoc(function(){/**
		 * remove any ' , or spaces from a string and multiply it with 1.0 to cast it in a real number
		 * @param value : string representing a number
		 * @return float : a clean numercic value
		*/},
		wigiiApi.str2float = function (value){
			return 1.0*value.replace(/'/g,'').replace(/,/g,'').replace(/ /g,'');
		});
		
		ncddoc(function(){/**
		 * format a numeric value with thousand separatos
		 * @param numeric
		 * @return string : a formated numeric value
		*/},
		wigiiApi.txtNumeric = function (value){
			value += '';
		    var x = value.split('.');
		    var x1 = x[0];
		    var x2 = x.length > 1 ? '.' + x[1] : '';
		    var rgx = /(\d+)(\d{3})/;
		    while (rgx.test(x1)) {
		            x1 = x1.replace(rgx, '$1' + "'" + '$2');
		    }
		    return x1 + x2;
		});
		
		ncddoc(function(){/**
		 * add double quote around the value
		 * @param string
		 * @return string
		*/},
		wigiiApi.txtQuot = function (value){
			return '"'+value+'"';
		});
		
		ncddoc(function(){/**
		 * Returns a label in the current language
		 * @example Two syntaxes are possible:
		 * wigii().txtDico("l01","Label in english","l02","Label en français");
		 * wigii().txtDico({l01:"Label in english",l02:"Label en français"})
		 * @example Natural Code Develoment :
		 * var t = wigii().txtDico;
		 * wigii().form().tool('copy').label(t("l01","Create an invoice","l02","Créer une facture"));
		*/},
		wigiiApi.txtDico = function(lang,label) {
			var dico = undefined;
			if($.isPlainObject(lang)) dico = lang;
			else {
				dico = {};
				dico[lang] = label;
				if(arguments.length>2) {
					var i=2;
					while(i<arguments.length) {
						lang = arguments[i];
						i++;
						if(i<arguments.length) label = arguments[i];
						else label = '';
						i++;
						dico[lang] = label;
					}
				}
			}
			return dico[wigiiApi.lang()];
		});
		
		ncddoc(function(){/**
		 * apply && between all the args
		 * this is useful in configuration files as the character & is reserved
		 * @param mixed val1 left hand side
		 * @param mixed val2 right hand side
		 * @param mixed valI any other number of arguments to combine in the AND expression
		 * @return val1 && val2 && valI ...
		*/},
		wigiiApi.logAnd = function (val1, val2){
			if(arguments.length>2) {
				var returnValue = arguments[0];
				if(returnValue) {
					for(var i=1;i<arguments.length;i++) {
						returnValue = returnValue && arguments[i];
						if(!returnValue) break;
					}
				}
				return returnValue;
			}
			else return val1 && val2;
		});		

		ncddoc(function(){/** 
		 * ceil a value up to the number. IE: ceilTo(10.34, 0.05) returns 10.35
		 * the third parameter is optional and allows to fixe the number of decimals returned 
		 * this function is typically used for financial fields 
		*/},
		wigiiApi.ceilTo = function (value, number, fixed) {
			if (arguments.length<3) fixed = null;
			//due to rounding errors in float calculation in javascript
			//we force the usage with ints if fixed is defined
			//for example if you do: 701.05/0.05 you get 14020.999999999998 instead of 14021
			//this explain as well why 701.05%0.05 gives 0.049 which is far different than the 0 that it should return...
			var op = 1;
			//detect how many decimals the number have
			var floatDec = (number+"").indexOf(".");
			if(floatDec){
				floatDec = (number+"").length-floatDec-1; //-1 because of the .
				op = Math.pow(10,floatDec);
			}
			remain = Math.round(value*op) % Math.round(number*op);
			remain = remain / op;
			if (remain > 0) value = value - remain + number;
			if(fixed) return value.toFixed(2);
			return value;
		});

		ncddoc(function(){/** 
		 * Generates a random integer value between min and max included 
		*/},
		wigiiApi.rand = function (minV, maxV) {
			var nPos = (maxV-minV+1);
			return minV - 1 + Math.floor(1 + Math.random()*nPos);
		});
		
		ncddoc(function(){/**
		 * Generates a range of numbers, starting from one number, to another number, by a given step.
		 * @param Number from start number, can be integer, float, positive, null or negative.
		 * @param Number to stop number, can be integer, float, positive, null or negative.
		 * @param Number step increment number, can be integer, float, positive or negative. Not null.
		 * @param Function callback a callback function which receives the generated number		 
		*/},
		wigiiApi.genRange = function(from,to,step,callback) {
			if($.isFunction(callback)) {
				if(!($.isNumeric(from) && $.isNumeric(to) && $.isNumeric(step))) throw wigiiApi.createServiceException('from, to and step should all be numbers',wigiiApi.errorCodes.INVALID_ARGUMENT);			
				if(step>0) {
					while(from<to) {
						callback(from);
						from += step;
					}
				}
				else if(step<0) {
					while(from>to) {
						callback(from);
						from -= step;
					}
				}
				else throw wigiiApi.createServiceException('step cannot be 0',wigiiApi.errorCodes.INVALID_ARGUMENT);
			}
		});
		
		ncddoc(function(){/**
		 * Generates a password
		 * @param int length Password length, default to 12 characters.
		 * @return String generated password composed of letters, digits and ponctuation characters
		*/},
		wigiiApi.genPassword = function(length) {
			if(length===undefined) length=12;
		    var returnValue = [];
		    var randomSet = [
		        ['-',':','#','!'],
		        ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'],
		        ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'],
		        ['0','1','2','3','4','5','6','7','8','9']
		    ];
		    
		    var oneSymbol=false;
		    // first character is always a char
		    var symbolTreshold=-1;
            var digitTreshold=100;
		    for(var i=0;i<length;i++) {
		        var charType = wigiiApi.rand(0,100);		        
		        // if not first character, then 20% symbol, 30% digit, rest chars
		        if(i>0) {
		           symbolTreshold=20;
		           digitTreshold=70;
		        }
		        // selects char type based on some weights
		        var lowerCaseTreshold = symbolTreshold+(digitTreshold-symbolTreshold)/2;
		        if(0<=charType && charType<=symbolTreshold) charType=0;
		        else if(symbolTreshold+1<=charType && charType<=lowerCaseTreshold) charType=1;
		        else if(lowerCaseTreshold+1<=charType && charType<=digitTreshold) charType=2;
		        else if(digitTreshold+1<=charType && charType<=100) charType=3;
		        else charType=2;
		        
		        if(i>0) {
	    	       // if char type is not a symbol, then move treshold up
	    	       if(charType!=0) symbolTreshold+=5; 
		           // else moves treshold down
	    	       else {
	    	           symbolTreshold-=5;
	    	           oneSymbol=true;
	    	       }
		        }	        
		        // select random char in selected set
		        var charV = wigiiApi.rand(0,randomSet[charType].length-1);
		        charV = randomSet[charType][charV];
		        returnValue.push(charV);
		    }
		    // if no symbol, then adds one randomly
		    if(!oneSymbol) {
		    	charV = wigiiApi.rand(0,randomSet[0].length-1);
		    	charV = randomSet[0][charV];
		        returnValue[wigiiApi.rand(1,returnValue.length-1)] = charV;
		    }
		    
		    return returnValue.join('');
		});
		
		ncddoc(function(){/**
		 * Reads the data coming from the source and processes it through the selected flow activities
		 *@param Function source a data flow source function. 
		 * A data source function is a function which takes an open DataFlowContext in parameter and then calls processDataChunk as many times as needed.
		 * var rangeGen = function(from,to,step) { return function(dataFlowContext) {
		 *	for(var i=from;i<=to;i+=step) dataFlowContext.processDataChunk(i);
		 * }};
		 *@param Array activities an array of activity functions. An activity function is a function which receives the current chunk of data, 
		 * an activity context in which a processing state can be stored and the data flow context to get insight on the global state of the data flow beeing executed.
		 * var power = function(factor) { return function(data,activityCtx,dataFlowContext) {
		 * 		var returnValue = 1;
		 *		for(var i=0;i<factor;i++) returnValue *= data;
		 *		return returnValue;
		 * }};
		 * var sum = function(data,activityCtx,dataFlowContext) {
		 *	 switch(activityCtx.state) {
		 *	 	case dataFlowContext.DFA_STARTSTREAM: activityCtx.sum = 0; break;
		 *		case dataFlowContext.DFA_RUNNING: activityCtx.sum += data; break;
		 *		case dataFlowContext.DFA_ENDSTREAM: dataFlowContext.writeResultToOuput(activityCtx.sum,activityCtx); break;
		 *	 }	
		 * };
		 *@example wigii().sel(rangeGen(-10,10,2),[power(3),sum])
		 *@see wigiiApi.DataFlowService method processDataSource
		 *@return mixed the data flow result
		*/},
		wigiiApi.sel = function(source,activities) { return wigiiApi.getDataFlowService().processDataSource(source,activities); });
		
		ncddoc(function(){/**
		 * Converts a date to a string in format YYYY-MM-DD
		 * @param Date date the date instance to convert to string. If undefined takes current date.
		 * @return String the date formatted as a String
		*/},
		wigiiApi.day2string = function(date) {
			if(!date) date = new Date();
			var returnValue = '';
			// year
			var c = date.getFullYear();
			returnValue += c;
			// month
			c = date.getMonth()+1;
			if(c<10) returnValue += "-0"+c;
			else returnValue += "-"+c;
			// day
			c = date.getDate();
			if(c<10) returnValue += "-0"+c;
			else returnValue += "-"+c;
			
			return returnValue;
		});
		
		ncddoc(function(){/**
		 * Converts a date to a string in format YYYY-MM-DD hh:mm
		 * @param Date date the date instance to convert to string. If undefined takes current date.
		 * @return String the date formatted as a String
		*/},
		wigiiApi.minutes2string = function(date) {
			if(!date) date = new Date();
			var returnValue = wigiiApi.day2string(date);
			// hours
			var c = date.getHours();
			if(c<10) returnValue += " 0"+c;
			else returnValue += " "+c;
			// minutes
			c = date.getMinutes();
			if(c<10) returnValue += ":0"+c;
			else returnValue += ":"+c;
			
			return returnValue;
		});
		
		ncddoc(function(){/**
		 * Converts a date to a string in format YYYY-MM-DD hh:mm:ss
		 * @param Date date the date instance to convert to string. If undefined takes current date.
		 * @return String the date formatted as a String
		*/},
		wigiiApi.seconds2string = function(date) {
			if(!date) date = new Date();
			var returnValue = wigiiApi.minutes2string(date);
			// seconds
			var c = date.getSeconds();
			if(c<10) returnValue += ":0"+c;
			else returnValue += ":"+c;
			
			return returnValue;
		});
		
		ncddoc(function(){/**
		 * Converts a date to a string in format YYYY-MM-DD hh:mm:ss,milliseconds
		 * @param Date date the date instance to convert to string. If undefined takes current date.
		 * @return String the date formatted as a String
		*/},
		wigiiApi.milliseconds2string = function(date) {
			if(!date) date = new Date();
			var returnValue = wigiiApi.seconds2string(date);
			// milliseconds
			var c = date.getMilliseconds();
			if(c<10) returnValue += ",00"+c;
			if(c<100) returnValue += ",0"+c;
			else returnValue += ","+c;
			
			return returnValue;
		});
		
		ncddoc(function(){/**
		 * Formats the given number of seconds as a Time string in the format HH:mm
		 *@param Int s a given number of seconds (can be negative)
		 *@return String well formatted time string
		*/},
		wigiiApi.seconds2Time = function(s){
			var neg = s < 0;
			if(neg) s = -s; //if time is negative do all calculation as if positive + adds minus at the end
			var h = s / 3600;
			var min = h - Math.floor(h);
			h = Math.floor(h);
			min = min*60;
			min = Math.round(min);
			if(min==60){
				min = '00';
				h++;
			} else if(min<10){
				min = '0'+min;
			}
			if(neg) return "-"+h+':'+min;
			return h+':'+min;
		});
		
		ncddoc(function(){/**
		 * Formats a string as a Time HH:mm
		 *@param String s partially formated time string
		 *@return String well formatted time string
		*/},
		wigiiApi.format2Time = function(s){
			if(s=="") return "";
			var min = "";
			var h = "";
			var t = s.split(":");
			if(t.length>1){
				h = t[0];
				min = t[1];
				if(parseInt(min)<10){
					min = "0"+parseInt(min);
				}
			} else if((s+"").length>2){
				s = s+""; //convert to string
				min = s.substr(-2,2);
				h = s.substr(0,s.length-2);
			} else {
				h = s;
				min = '00';
			}
			if(h==""){
				h = "0";
			}
			if(min>"59"){
				min="??";
			}
			return h+':'+min;
		});
		
		ncddoc(function(){/**
		 * Transforms a duration represented as a time string HH:mm in the correspondant number of seconds.
		 *@param String duration time string in the format HH:mm
		 *@return Int the duration in seconds
		*/},
		wigiiApi.time2Seconds = function(duration){
			if(duration!=""){
				var time = duration.split(":");
				if(time[0]<0) return (time[0]*3600)-(time[1]*60);
				else return (time[0]*3600)+(time[1]*60);
			} else return 0;
		});
		
		ncddoc(function(){/**
		 * Returns a string representing a date in a French style (d.m.Y H:i:s).
		 *@param Integer|String timestamp timestamp to convert to date string or Wigii date string.
		 *@param String options a formating option string. One of : 
		 * noSeconds: display date and time up to minutes, 
		 * noTime: displays only date without time, 
		 * noDate: displays only time without date.
		*/},
		wigiiApi.txtFrenchDate = function(timestamp, options) {			
			var d = ($.type(timestamp)== 'date'? timestamp: new Date(timestamp));
			var returnValue = '';
			var v = 0;
			if(options!= 'noDate') {
				// Day
				v = d.getDate();
				if(v<10) returnValue += '0'+v;
				else returnValue += v;
				returnValue += '.';			
				// Month
				v = d.getMonth()+1;
				if(v<10) returnValue += '0'+v;
				else returnValue += v;
				returnValue += '.';
				// Year
				returnValue += d.getFullYear();
			}
			if(options != 'noTime') {
				if(options != 'noDate') returnValue += ' ';
				// Hour
				v = d.getHours();
				if(v<10) returnValue += '0'+v;
				else returnValue += v;
				returnValue += ':';
				// Minute
				v = d.getMinutes();
				if(v<10) returnValue += '0'+v;
				else returnValue += v;
				if(options != 'noSeconds') {
					returnValue += ':';
					// Seconds
					v = d.getSeconds();
					if(v<10) returnValue += '0'+v;
					else returnValue += v;
				}
			}
			
			return returnValue;
		});
		
		ncddoc(function(){/**
		 * Serializes an XML Dom object to string
		 * @param XMLDocument xmlDom an XML DOM document as created by calling jQuery.parseXML
		 * @return String XML serialized
		*/},
		wigiiApi.xml2string = function(xmlDom) {
			return (typeof XMLSerializer!=="undefined") ? 
					(new window.XMLSerializer()).serializeToString(xmlDom) : 
					xmlDom.xml;
		});
		
		ncddoc(function(){/**
		 * throws a ServiceException::NOT_IMPLEMENTED exception
		*/},
		wigiiApi.throwNotImplemented = function() {
			throw new wigiiApi.ServiceException("not implemented", wigiiApi.errorCodes.NOT_IMPLEMENTED);
		});
		
		ncddoc(function(){/**
		 * throws a ServiceException 
		*/},
		wigiiApi.createServiceException = function(message,code,previous) {
			return new wigiiApi.ServiceException(message, code, previous);
		});
		
		ncddoc(function(){/**
		 * Publishes an exception that cannot be handled
		*/},
		wigiiApi.publishException = function(exception) {
			wigiiApi.getExceptionSink().publish(exception);
		});
		
		ncddoc(function(){/**
		 * Converts an exception to HTML code that can be displayed
		 * @param Object|XML exception an exception object {name:string,code:int,message:string}
		 * @param Object|XML context if defined, then information about server context in the form 
		 * {request:string, wigiiNamespace:string, module:string, action:string, realUsername:string, username:string, principalNamespace:string, version:string}
		*/},
		wigiiApi.exception2html = function(exception,context) {
			var htmlb = wigiiApi.getHtmlBuilder();
			// if exception is not a plain object, then assumes its some xml
			if(!$.isPlainObject(exception)) {
				exception = $(exception);
				exception = {
					name: exception.find('name').text(),
					code: exception.find('code').text(),
					message: exception.find('message').text()
				};
			}
			
			htmlb.putStartTag('h2').put(exception.code).prepend(' ',wigiiApi.errorLabels[exception.code]).putEndTag('h2');
			htmlb.putStartTag('p').implode(' : ',exception.name,exception.message).putEndTag('p');
			if(context) {
				// if context is not a plain object, then assumes its some xml
				if(!$.isPlainObject(context)) {
					context = $(context);
					context = {
						request: context.find('request').text(),
						wigiiNamespace: context.find('wigiiNamespace').text(),
						module: context.find('module').text(),
						action: context.find('action').text(),
						realUsername: context.find('realUsername').text(),
						username: context.find('username').text(),
						principalNamespace: context.find('principalNamespace').text()
					}
				}
				
				htmlb.putStartTag('div','class','elementHistoric')
				.putStartTag('div', 'class', 'label SBB expanded','onclick',"$(this).toggleClass('expanded').toggleClass('collapsed');$(this).next().toggle();").put('Context').putEndTag('div')
				.putStartTag('table','style','display:table;').putStartTag('tbody');
				if(context.request) htmlb.putStartTag('tr','style','height:30px;').putStartTag('td','class','label').put('Request').putEndTag('td').putStartTag('td').put(context.request).putEndTag('td').putEndTag('tr');
				if(context.wigiiNamespace) htmlb.putStartTag('tr','style','height:35px;').putStartTag('td','class','label').put('Wigii Namespace').putEndTag('td').putStartTag('td').put(context.wigiiNamespace).putEndTag('td').putEndTag('tr');
				if(context.module) htmlb.putStartTag('tr','style','height:20px;').putStartTag('td','class','label').put('Module').putEndTag('td').putStartTag('td').put(context.module).putEndTag('td').putEndTag('tr');
				if(context.action) htmlb.putStartTag('tr','style','height:20px;').putStartTag('td','class','label').put('Action').putEndTag('td').putStartTag('td').put(context.action).putEndTag('td').putEndTag('tr');
				if(context.realUsername) htmlb.putStartTag('tr','style','height:20px;').putStartTag('td','class','label').put('Login').putEndTag('td').putStartTag('td').put(context.realUsername).putEndTag('td').putEndTag('tr');
				if(context.username) htmlb.putStartTag('tr','style','height:20px;').putStartTag('td','class','label').put('Role').putEndTag('td').putStartTag('td').put(context.username).putEndTag('td').putEndTag('tr');
				if(context.principalNamespace) htmlb.putStartTag('tr','style','height:35px;').putStartTag('td','class','label').put('User namespace').putEndTag('td').putStartTag('td').put(context.principalNamespace).putEndTag('td').putEndTag('tr');
				htmlb.putEndTag('tbody').putEndTag('table')
				.putEndTag('div');
			}			
			return htmlb.html();
		});
		
		ncddoc(function(){/**
		 * Calculates the left and top attributes of a box options according to some position options.
		 * Uses window (viewport) coordinate system and not document coordinates.
		 * @param jQuery.Event mouseEvent jQuery mouse event object (click, drag, etc.) 
		 * @param Object boxOptions an object containing {left,top,width,height} options.
		 * @param Object positionOptions the positioning options relative to mouse event of the form
		 *  {
		 *  position: String. One of 'N','NE','E','SE','S','SW','W','NW','center'.
		 *  offset: Int. Distance from mouse event coordinates to box limit.
		 *  preventCovering: Boolean. If true, then box doesn't overlap mouse event position, but can potentially overflow window limit.
		 *  By default, preventCovering is false, meaning that the box is always visible but can potentially cover mouse event position.
		 *  }
		 * @return Object box options updated
		*/},
		wigiiApi.positionBox = function(mouseEvent,boxOptions,positionOptions) {
			if(!boxOptions) boxOptions = {};
			var screenTop=undefined,screenLeft=undefined,boxTop=undefined,boxLeft=undefined;			
			if(!positionOptions) positionOptions = {};
			if(!positionOptions.referenceWindow) positionOptions.referenceWindow = $(window);
			var refWindowTop = 0, refWindowLeft = 0, refWindowOffset = positionOptions.referenceWindow.offset();
			if(refWindowOffset) {refWindowTop = refWindowOffset.top; refWindowLeft = refWindowOffset.left;}
			
			// if no mouseEvent then takes window center
			if(!mouseEvent) {			
				screenTop = Math.floor(positionOptions.referenceWindow.height()/2);
				screenLeft = Math.floor(positionOptions.referenceWindow.width()/2);	
				if(!positionOptions.position) positionOptions.position = 'center';
			}
			// else takes mouse position
			else {
				screenTop = mouseEvent.pageY-(refWindowTop > 0 ? refWindowTop:positionOptions.referenceWindow.scrollTop());
				screenLeft = mouseEvent.pageX-(refWindowLeft > 0 ? refWindowLeft:positionOptions.referenceWindow.scrollLeft());
				if(!positionOptions.position) positionOptions.position = 'SE';
				if(!positionOptions.position=='center' && !positionOptions.offset && positionOptions.offset!==0) positionOptions.offset=15;
			}
			// calculates boxTop and boxLeft
			switch(positionOptions.position) {
			case 'center': 
				boxTop = boxOptions.height/2;
				boxLeft = boxOptions.width/2;
				break;
			case 'N':
				boxTop = screenTop-positionOptions.offset-boxOptions.height;
				boxLeft = screenLeft-boxOptions.width/2;
				break;
			case 'NE':
				boxTop = screenTop-positionOptions.offset-boxOptions.height;
				boxLeft = screenLeft+positionOptions.offset;
				break;
			case 'E':
				boxTop = screenTop-boxOptions.height/2;
				boxLeft = screenLeft+positionOptions.offset;
				break;	
			case 'SE':
				boxTop = screenTop+positionOptions.offset;		
				boxLeft = screenLeft+positionOptions.offset;	
				break;
			case 'S':
				boxTop = screenTop+positionOptions.offset;
				boxLeft = screenLeft-boxOptions.width/2;
				break;
			case 'SW':
				boxTop = screenTop+positionOptions.offset;
				boxLeft = screenLeft-positionOptions.offset-boxOptions.width;
				break;	
			case 'W':
				boxTop = screenTop-boxOptions.height/2;
				boxLeft = screenLeft-positionOptions.offset-boxOptions.width;
				break;	
			case 'NW':
				boxTop = screenTop-positionOptions.offset-boxOptions.height;
				boxLeft = screenLeft-positionOptions.offset-boxOptions.width;
				break;
			}
			// keeps box fully visible except if preventCovering		
			if(!positionOptions.preventCovering) {				
				if(boxTop<0) boxTop = 0;
				if(boxLeft<0) boxLeft = 0;
				boxTop = Math.max(Math.min(boxTop,positionOptions.referenceWindow.height()-boxOptions.height-20),10);
				boxLeft = Math.max(Math.min(boxLeft,positionOptions.referenceWindow.width()-boxOptions.width-30),10);
			}
			boxOptions.left = Math.ceil(boxLeft);
			boxOptions.top = Math.ceil(boxTop);
			return boxOptions;
		});
		
		ncddoc(function(){/**
		 * Fills a drop-down (html select element) from an array. All existing options are flushed.
		 * @param JQuery selector JQuery selector on an existing select element
		 * @param Array array array of objects or values to fill the drop-down with
		 * @param String|Function value the name of a field in the object to be used as a drop-down value or a callback function computing the drop-down values.
		 * The callback function is of the form value(i,data,selector) where i=0..array.length, data = array[i], selector is the given JQuery selector to access the DOM context.
		 * The function should return a scalar value used a the drop-down value.
		 * @param String|Function label the name of a field in the object to be used as a drop-down label or a callback function computing the drop-down labels.
		 * The callback function is of the form label(i,data,selector) where i=0..array.length, data = array[i], selector is the given JQuery selector to access the DOM context.
		 * The function should return a scalar value used a the drop-down label.
		 * @param Object options a bag of options to customize the rendering process
		 * @return JQuery returns the updated select element
		*/},
		wigiiApi.fillDropDownFromArray = function(selector,array,value,label,options) {
			if(!selector) throw wigiiApi.createServiceException("selector cannot be empty", wigiiApi.errorCodes.INVALID_ARGUMENT);
			var currentVal = selector.val();
			var newVal='';
			// empties current drop-down
			selector.empty();
			if(array) {
				// checks arguments
				if(!$.isArray(array)) throw wigiiApi.createServiceException("fillDropDownFromArray needs an array", wigiiApi.errorCodes.INVALID_ARGUMENT);
				var isValFx=false;
				if($.isFunction(value)) isValFx=true;
				var isLabFx = false;
				if($.isFunction(label)) isLabFx=true;				
				// loops through array and creates html
				var html = wigiiApi.getHtmlBuilder();
				var v,l;				
				selector.append(html.reset().putStartTag('option','value',"").put("").putEndTag('option').html());
				for(var i=0;i<array.length;i++) {
					var data = array[i];
					var isObj = $.isPlainObject(data);
					// generates value
					if(isValFx) v = value(i,data,selector);
					else if(isObj) v = data[value];
					else v = data;
					if(currentVal==v) newVal=v;
					// generates label
					if(label) {
						if(isLabFx) l = label(i,data,selector);
						else if(isObj) l = data[label];
						else l = data;
					}					
					// no label, then takes value
					else l = v;
					// generates html
					selector.append(html.reset().putStartTag('option','value',v).put(l).putEndTag('option').html());
				}				
			}
			// if select2 drop-down, then triggers change to refresh the list
			selector.val(newVal).trigger('change');
			return selector;
		});
		
		// Link with WigiiExecutor.js
		
		wigiiApi.initContext = function() {
			wigiiApi.SITE_ROOT = window.SITE_ROOT;
			wigiiApi.EXEC_answerRequestSeparator = window.EXEC_answerRequestSeparator;
			wigiiApi.EXEC_answerParamSeparator = window.EXEC_answerParamSeparator;
			wigiiApi.EXEC_requestSeparator = window.EXEC_requestSeparator;			
		};
		wigiiApi.initContext();		
		wigiiApi.encHTML = window.encHTML;
		wigiiApi.decHTML = window.decHTML;
		wigiiApi.setCache = window.setCache;
		wigiiApi.setJSCache = window.setJSCache;
		wigiiApi.keepInCache = window.keepInCache;
		wigiiApi.clearKeepInCache = window.clearKeepInCache;
	}, 
	/*
	 * Default WigiiAPI instance
	 */
	wigiiApiInstance = new WigiiApi(), 
	/*
	 * WigiiAPI functional facade
	 */
	wigiiFacade = function(selector, options) {
		var wigiiApi = wigiiApiInstance;
		if(!selector) return wigiiApi;		
		var service = wigiiApi[selector];
		if(!service) throw new wigiiApi.ServiceException("service '"+selector+"' is not a valid Wigii API service", wigiiApi.errorCodes.INVALID_ARGUMENT);
		// if service is an object constructor, then returns a new instance
		if($.type(service)==='function') {
			if(options) return new service(options);
			else return new service();
		}
		// else returns singleton
		else return service;
	},
	/*
	 * WigiiAPI console attached to browser 
	 */
	wigiiApiConsoleInstance = undefined,
	wigiiApiConsole = function() {
		var wigiiApi = wigiiApiInstance;
		if(!wigiiApiConsoleInstance) {
			wigiiApiConsoleInstance = {
				popup : function() {
					if(!this['popupInstance']) {
						this.popupInstance = wigiiApi.createPopUpInstance($('body'), {
							id: 'wigiiApiConsole_'+wigiiApi.instantiationTime,
							title:'Wigii API Console'				
						});
					}
					return this.popupInstance;
				},
				clear : function() {
					if(this['popupInstance']) {
						this.popupInstance.remove();
						delete this['popupInstance'];
					}
				},
				debug : function(message) {
					this.popup().body().append("<p>"+wigiiApi.milliseconds2string()+" "+message+"</p>");
					this.popup().show();
				},	
				log : function(message) {
					this.popup().body().append("<p>"+wigiiApi.milliseconds2string()+" "+message+"</p>");
					this.popup().show();
				},
				error : function(message) {
					this.popup().body().append("<p>"+wigiiApi.milliseconds2string()+" "+message+"</p>");
					this.popup().show();
				}
			};
		}
		return wigiiApiConsoleInstance;
	};
	
	// Bootstrap
	window.wigii = wigiiFacade;
})(window,jQuery);


/**
 * Wigii JQuery plugin
 * Created by CWE on July 14th 2015
 */
(function($) {
	/**
	 * JQuery Wigii plugin
	 * @param String cmd selects a service or a command in the WigiiAPI which accepts a jQuery collection
	 * @param Object options a map of configuration options to be passed to the called service.
	 * @return JQuery|mixed returns the service or command result if defined, or the JQuery collection if no specific result.
	 */
	$.fn.wigii = function(cmd, options) {
		var wigiiApi = wigii();		
		var returnValue = undefined;
		try {
			if(!cmd) throw wigiiApi.createServiceException("cmd cannot be null",wigiiApi.errorCodes.INVALID_ARGUMENT);			
			var jQueryHandler = wigiiApi.getJQueryService()[cmd];
			if(!jQueryHandler || $.type(jQueryHandler) !== 'function') throw wigiiApi.createServiceException("Wigii API JQueryService does not support the '"+cmd+"' command.",wigiiApi.errorCodes.UNSUPPORTED_OPERATION);
			returnValue = jQueryHandler(this,options);
		}
		catch(e) {wigiiApi.publishException(e);}
		if(returnValue) return returnValue;
		else return this;		
	};
})(jQuery);
