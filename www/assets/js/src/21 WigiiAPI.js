/**
 *  This file is part of Wigii.
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
 * 
 * @param Window window current browser window
 * @param JQuery $ depends on JQuery 1.8.x
 */
(function(window,$) {
	
	
	// FuncExp Libraries 
	
	/**
	 * Wigii Std FuncExp library
	 * @see FuncExpVMStdFL and WigiiFL PHP classes
	 */
	var WigiiStdFL = function(api) {
		
		// Dependency injection
		
		/**
		 * Reference to the WigiiAPI instance
		 */
		var wigiiApi = api || wigii();
		var wigiiStdFL = this;		
		
		/**
		 * The current evaluated FuncExp. (transcient value).
		 */
		wigiiStdFL.currentFx;
		
		// FuncExp Evaluation
		
		/**
		 * Evaluates an argument in the context of FuncExps
		 * @param Function arg the argument to evaluate
		 * @return Any
		 */
		wigiiStdFL.evaluateArg = function(arg) {
			if($.type(arg) === 'function') return arg();
			else return arg;
		};
		/**
		 * FuncExpEvalException class
		 * @param String message the error message
		 * @param Number code the error code
		 * @param Object previous if defined, the previous exception in the chain if wrapping.
		 */
		wigiiStdFL.FuncExpEvalException = function(message,code,previous) {
			var self = this;
			self.name = 'FuncExpEvalException';
			self.message = message;
			self.code = code || wigiiApi.errorCodes.UNKNOWN_ERROR;
			self.previousException = previous; 
		};
		/**
		 * Creates a new instance of a FuncExpEvalException
		 */
		wigiiStdFL.createFuncExpEvalException = function(message,code,previous) {
			return new wigiiStdFL.FuncExpEvalException(message,code,previous);
		};
		
		// FuncExp Std library
		
		/*
		 * Examples :
		 * wigii().log(wigii().evalfx(function(){return this.ctlIf(false, "toto", "titi");}));
		 * wigii().log(wigii().evalfx(wigii().fl.ctlIf(false, "toto", "titi")));
		 * wigii().log(wigii().evalfx(function(){return this.ctlSeq("A","B",this.ctlIf(true,this.ctlIf(true,"OK","NO2"),"NO1"));}));
		 * wigii().log(wigii().evalfx(function(){return this.gr(8);}));
		 * 
		 */
		
		/**
		 * ctlIf FuncExp.
		 * @see PHP FuncExpVMStdFL::ctlIf
		 */
		wigiiStdFL.ctlIf = function(condition, ifTrue, ifFalse) {var fl=wigiiStdFL; return function() {
			if(fl.evaluateArg(condition)) return fl.evaluateArg(ifTrue);
			else return fl.evaluateArg(ifFalse);
		};};
		/**
		 * ctlSeq FuncExp.
		 * @see PHP FuncExpVMStdFL::ctlSeq
		 */
		wigiiStdFL.ctlSeq = function() {var fl=wigiiStdFL,args=$.makeArray(arguments); return function() {
			if(args) {
				var returnValue = undefined;
				for(var i=0;i<args.length;i++) {
					returnValue = fl.evaluateArg(args[i]);
				}
				return returnValue;
			}
		};};
		/**
		 * logAnd FuncExp.
		 * @see PHP FuncExpVMStdFL::logAnd
		 */
		wigiiStdFL.logAnd = function() {var fl=wigiiStdFL,args=$.makeArray(arguments); return function() {
			if(!args || args.length < 2) throw fl.createFuncExpEvalException("For logical AND, the number of arguments should be at least 2", wigiiApi.errorCodes.INVALID_ARGUMENT);
			for(var i=0;i<args.length;i++) {
				if(!fl.evaluateArg(args[i])) return false;
			}
			return true;
		};};
		/**
		 * logOr FuncExp.
		 * @see PHP FuncExpVMStdFL::logOr
		 */
		wigiiStdFL.logOr = function() {var fl=wigiiStdFL,args=$.makeArray(arguments); return function() {
			if(!args || args.length < 2) throw fl.createFuncExpEvalException("For logical OR, the number of arguments should be at least 2", wigiiApi.errorCodes.INVALID_ARGUMENT);
			for(var i=0;i<args.length;i++) {
				if(fl.evaluateArg(args[i])) return true;
			}
			return false;
		};};
		/**
		 * logNot FuncExp.
		 * @see PHP FuncExpVMStdFL::logNot
		 */
		wigiiStdFL.logNot = function(arg) {var fl=wigiiStdFL; return function() {
			return !fl.evaluateArg(arg);
		};};
		/**
		 * eq FuncExp.
		 * @see PHP FuncExpVMStdFL::eq
		 */
		wigiiStdFL.eq = function() {var fl=wigiiStdFL,args=$.makeArray(arguments); return function() {
			if(!args || args.length < 2) throw fl.createFuncExpEvalException("For equality, the number of arguments should be at least 2", wigiiApi.errorCodes.INVALID_ARGUMENT);
			var first = true, firstVal = undefined;
			for(var i=0;i<args.length;i++) {
				if(first) {
					firstVal = fl.evaluateArg(args[i]);
					first = false;
				}
				else if(fl.evaluateArg(args[i]) != firstVal) return false;
			}
			return true;
		};};
		/**
		 * sm FuncExp.
		 * @see PHP FuncExpVMStdFL::sm
		 */
		wigiiStdFL.sm = function() {var fl=wigiiStdFL,args=$.makeArray(arguments); return function() {
			if(!args || args.length < 2) throw fl.createFuncExpEvalException("For comparison, the number of arguments should be at least 2", wigiiApi.errorCodes.INVALID_ARGUMENT);
			var first = true, firstVal = undefined;
			for(var i=0;i<args.length;i++) {
				if(first) {
					firstVal = fl.evaluateArg(args[i]);
					first = false;
				}
				else if(firstVal >= fl.evaluateArg(args[i])) return false;
			}
			return true;
		};};
		/**
		 * gr FuncExp.
		 * @see PHP FuncExpVMStdFL::gr
		 */
		wigiiStdFL.gr = function() {var fl=wigiiStdFL,args=$.makeArray(arguments); return function() {
			if(!args || args.length < 2) throw fl.createFuncExpEvalException("For comparison, the number of arguments should be at least 2", wigiiApi.errorCodes.INVALID_ARGUMENT);
			var first = true, firstVal = undefined;
			for(var i=0;i<args.length;i++) {
				if(first) {
					firstVal = fl.evaluateArg(args[i]);
					first = false;
				}
				else if(firstVal <= fl.evaluateArg(args[i])) return false;
			}
			return true;
		};};
		/**
		 * smeq FuncExp.
		 * @see PHP FuncExpVMStdFL::smeq
		 */
		wigiiStdFL.smeq = function() {var fl=wigiiStdFL,args=$.makeArray(arguments); return function() {
			if(!args || args.length < 2) throw fl.createFuncExpEvalException("For comparison, the number of arguments should be at least 2", wigiiApi.errorCodes.INVALID_ARGUMENT);
			var first = true, firstVal = undefined;
			for(var i=0;i<args.length;i++) {
				if(first) {
					firstVal = fl.evaluateArg(args[i]);
					first = false;
				}
				else if(firstVal > fl.evaluateArg(args[i])) return false;
			}
			return true;
		};};
		/**
		 * greq FuncExp.
		 * @see PHP FuncExpVMStdFL::greq
		 */
		wigiiStdFL.greq = function() {var fl=wigiiStdFL,args=$.makeArray(arguments); return function() {
			if(!args || args.length < 2) throw fl.createFuncExpEvalException("For comparison, the number of arguments should be at least 2", wigiiApi.errorCodes.INVALID_ARGUMENT);
			var first = true, firstVal = undefined;
			for(var i=0;i<args.length;i++) {
				if(first) {
					firstVal = fl.evaluateArg(args[i]);
					first = false;
				}
				else if(firstVal < fl.evaluateArg(args[i])) return false;
			}
			return true;
		};};
	};
	
	
	
	
	
	/*
	 * WigiiAPI
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
		
		/**
		 * ServiceException class
		 * @param String message the error message
		 * @param Number code the error code
		 * @param Object previous if defined, the previous exception in the chain if wrapping.
		 */
		wigiiApi.ServiceException = function(message,code,previous) {			
			var self = this;
			self.name = 'ServiceException';
			self.message = message;
			self.code = code || wigiiApi.errorCodes.UNKNOWN_ERROR;
			self.previousException = previous; 
		};
		
		// Classes
		
		/**
		 * Debugger core technical service
		 */
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
		};
		
		/**
		 * Exception sink core technical service
		 */
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
		};
				
		
		/**
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
		 */
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
						// if the popup is relative to anchor we add overflow for fix a display bug
						if(self.isRelativeToAnchor) anchor.parent().css('overflow','visible');
						// reset default position and dimension if resetOnShow
						if(self.resetOnShow && self.defaultOptions) {
							var w = self.window();
							w.css('top',self.defaultOptions.top).css('left',self.defaultOptions.left);
							if(self.defaultOptions.width && self.defaultOptions.height) {
								w.css('height',self.defaultOptions.height+10).css('width',self.defaultOptions.width).resize();
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
			 * @param String htmlString the html string to use as html content for this popup 
			 */
			self.html = function(htmlString) {
				var b = self.body();
				if(b) b.html(htmlString);
				// records default width and height if not yet calculated
				if(self.defaultOptions && !self.defaultOptions.width && !self.defaultOptions.height) {
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
				anchor.parent().css('position','relative').css('overflow','visible'); //.removeAttr('overflow');
				popupPosition='absolute';
				if(!self.isRelativeToAnchor) self.isRelativeToAnchor = true;
			}
			
			var popupHtml = '<div';
			if(options['id']) popupHtml+=' id="'+options['id']+'"';
			popupHtml += ' class="'+(options['classId']&&!options['id']?options['classId']:'')+' ui-corner-all ui-widget ui-dialog SBIB"';
			popupHtml += ' style="cursor:default;z-index:998;position:'+popupPosition+';background-color:#fff;border-style:solid;border-width:1px;top:'+top+'px;left:'+left+'px;padding:5px;width:'+width+'px;max-height:'+height+'px;display:none;float:none;border-color:black;"';
			popupHtml +='>';
			if(title) popupHtml +='<div class="popupTitle ui-corner-all ui-widget-header" style="'+(options['resizable']?'cursor:move;':'')+'float:left;font-style:normal;font-weight:bold;font-size:small;text-align:left;padding-left:13px;padding-right:0;padding-top:5px;padding-bottom:5px;margin:0;color:black;height:14px;width:'+(width-15)+'px" >'+title+'</div>';
			else popupHtml +='<div class="popupTitle emptyTitle ui-corner-all" style="'+(options['resizable']?'cursor:move;':'')+'float:left;z-index:999;position:absolute;right:4px;top:-10px;font-style:normal;font-weight:bold;font-size:small;text-align:left;padding-left:13px;padding-right:0;padding-top:5px;padding-bottom:5px;margin:0;color:black;height:14px;width:'+(width-10)+'px" >&nbsp;</div>';
			//if(options['closeable']) popupHtml += '<div class="exit ui-corner-all SBIB" style="z-index:999;position:absolute;right:-8px;top:-8px;cursor:pointer;width:15px;height:17px;float:right;background-color:#fff;text-align:center;vertical-align:middle;color:black;font-weight:bold;font-style:normal;font-size:small;padding:0;margin:0">x</div>';			
			if(options['closeable']) {
				if(title) popupHtml += '<div class="exit ui-corner-all" style="z-index:999;position:absolute;right:10px;top:7px;cursor:pointer;width:15px;height:17px;float:right;text-align:center;vertical-align:middle;color:black;font-weight:bold;font-style:normal;font-size:small;padding:0;margin:0">x</div>'; 
				else popupHtml += '<div class="exit ui-corner-all SBIB" style="z-index:999;position:absolute;right:-2px;top:-3px;cursor:pointer;width:15px;height:17px;float:right;background-color:#fff;border-style:solid;border-width:1px;text-align:center;vertical-align:middle;color:black;font-weight:bold;font-style:normal;font-size:small;padding:0;margin:0;border-color:black">x</div>';
			}
			popupHtml +='<div class="clear"></div>';
			popupHtml +='<div class="popupBody" style="z-index:998;cursor:normal;float:left;font-weight:normal;font-style:normal;font-size:small;text-align:left;padding:0;margin:0;margin-top:1px;color:black;overflow-y:auto;height:auto;max-height:'+(height-28)+'px;width:'+(width-5)+'px;"><br/></div></div>';
						
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
				self.popupElt.draggable({handle:'.popupTitle'}).resizable().resize(function(){
					$(this).css('min-width','0').css('min-height','0').css('max-width','none').css('max-height','none');
					var body = $(this).find('div.popupBody');
					body.css('min-width','0').css('min-height','0').css('max-width','none').css('max-height','none').width($(this).width()-5);
					var title = $(this).find('div.popupTitle');
					if(title.hasClass('emptyTitle')) {
						title.width($(this).width()-10);
						body.height($(this).height()-8);
					}
					else {
						title.width($(this).width()-15);
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
		};
		
		/**
		 * Wigii Help Service
		 * Shows some contextual help in popup windows or displays contextual messages according to current flow.
		 */
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
				options.referenceWindow =  anchor.parentsUntil('#scrollElement').last().parent();
				//If we didn't find node 'scrollElement' we try to find 'elementDialog'
				if(options.referenceWindow.attr('id')!='scrollElement' || isWorkzoneViewMode()) options.referenceWindow =  anchor.parentsUntil('#elementDialog').last().parent(); //addElement_form
				var context = anchor.data(self.ctxKey);
				if(!context) {
					context = {};
					// popup positioning
					if(!options.top && !options.left) {
						//if option.referenceWindow first child has 'html' it was the window object and use offset else use position
						var anchorOffset = (options.referenceWindow.children().first().is('html')) ? anchor.offset() : anchor.position();
						var scrollLeft = options.referenceWindow.scrollLeft(); //changer cette valeur si on est dans une fenêtre
						var scrollTop = options.referenceWindow.scrollTop();
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
						options.top = options.top+scrollTop-anchorOffset.top;				
						options.left = options.left+scrollLeft-anchorOffset.left;
						options.relativeToAnchor=true;
						
						//We must add 20px to the left in case where you have a scroolbar
						options.left = options.left-20;
					}
					else {
						options.relativeToAnchor=false;
					};
					// popup creation					
					context.popup = wigiiApi.createPopUpInstance(anchor,options);
					if(options.localContent) {
						context.popup.html(content);
					}
					else {
						$.ajax({url:wigiiApi.buildUpdateUrl(content),
							cache:false,
							success:wigiiApi.buildUpdateCallback(context)
						});
					}
					anchor.data(self.ctxKey,context);
					// removes anchor on close if needed
					if(options['removeOnClose']) context.popup.remove(function(){anchor.remove();});
					// if type == 'help' we would have only one popup
					if (options.type == 'help') {
						fh = anchor.parents('div.field').first().wigii('FieldHelper');
						if(fh.ctxKey) {
							fh = fh.formHelper();
							context.popup.show(fh.onHelpPopupShow);
							context.popup.hide(fh.onHelpPopupHide);
						}						
					};
				}
				context.popup.show();
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
				context = {};
				context.popup = wigiiApi.createPopUpInstance(container,options);
				if(options.localContent) {
					context.popup.html(content);
				}
				else {
					$.ajax({url:wigiiApi.buildUpdateUrl(content),
						cache:false,
						success:wigiiApi.buildUpdateCallback(context)
					});
				}
				// shows popup
				context.popup.show();
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
						if(w>=75) neighbor.width(w-25);
						neighbor.children('div,span,select').each(function(){
							var e = $(this);
							w = e.width();
							if(w>=75) e.width(w-25);
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
						if(isWorkzoneViewMode()) {
							$('#searchBar .middleBox div.T').children().last().after('<div class="wigiiNotif" style=""/>');
							wigiiNotif = $('#searchBar .middleBox div.T').find('.wigiiNotif');
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
							if(isWorkzoneViewMode()) {
								wigiiNotif = $('#searchBar').find('.elementHelp');
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
		};
		
		/**
		 * Wigii Field helper.
		 * A class which helps manage fields lifecycle.
		 */
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
					subFieldName = subFieldName || 'value';
					wigiiApi.throwNotImplemented();
					self.debugLogger.write(self.fieldName+'.'+subFieldName+' setValue '+value);
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
					subFieldName = subFieldName || 'value';
					wigiiApi.throwNotImplemented();
					self.debugLogger.write(self.fieldName+'.'+subFieldName+' getValue '+value);
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
		};
		
		/**
		 * Wigii Form helper.
		 * A class which helps manage form lifecycle.
		 */
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
			 * Selects a field in the Wigii Form and returns a FieldHelper on it (or undefined if not found)
			 * @param String fieldName the field selector
			 * @return FieldHelper the FieldHelper instance attached to the selected field or undefined if not found
			 */
			self.field = function(fieldName) {
				var returnValue = undefined;
				if(fieldName) {
					returnValue = $('#'+self.formId()+'__'+fieldName).wigii('FieldHelper');
				}
				return returnValue;
			};
			
			/**
			 * Returns a selection of fields in the Wigii Form			 
			 * @param String selector JQuery selector to filter the Fields of the form (for instance based on data-wigii-datatype or other attribute). If not defined, takes all the Fields.
			 * @return FieldHelper an instance of FieldHelper attached to the selected list of Fields.
			 */
			self.fields = function(selector) {
				if(selector) return self.$.find('div.field[data-wigii-datatype]').filter(selector).wigii('FieldHelper');
				else return self.$.find('div.field[data-wigii-datatype]').wigii('FieldHelper');
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
		};
		
		/**
		 * HTML String builder
		 */
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
				if(!multipler) multiplier=1;
				for(var i=0;i<multipler;i++) {
					self.buffer += '&nbsp;';
				}
				return self;
			};
			/**
			 * Puts a double quote entity in the buffer
			 * @return HtmlBuilder for chaining
			 */
			self.putQuot = function() {
				self.buffer += '&quot;';
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
		};
		
		/**
		 * A helper on array of strings
		 * @param Array arr the array on which to perform some actions
		 */
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
		};
		
		/**
		 * JQuery collection event handlers
		 */
		wigiiApi.JQueryService = function() {
			var self = this;
			self.className = 'JQueryService';
			self.ctxKey = wigiiApi.ctxKey+'_'+self.className;	
			//self.debugLogger = wigiiApi.getDebugLogger(self.className);
			
			self.bindHelpService = function(selection,options) {				
				//self.debugLogger.logBeginOperation('bindHelpService');				
				var helpService = wigiiApi.getHelpService();
				var optionsFx = ($.type(options) === 'function');
				
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
		};
		
		
		
		// Models
		
		/**
		 * A Wigii FormEvent
		 */
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
		};
		
		/**
		 * A Wigii Field
		 * 
		 * @param String fieldName the name of the field
		 * @param String dataType the Wigii DataType name of the field
		 * @param String label a label for the end user (already translated)
		 * @param Object attributes optional map of attributes
		 */
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
		};
		
		/**
		 * A Wigii FieldList
		 */
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
		};
		
		/**
		 * Wigii Bag
		 */
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
			 * @return Scalar the field value or undefined if field has no value.
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
			 * @param Scalar value the value to store in the WigiiBag
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
		};
		
		/**
		 * A Wigii Record
		 * @param FieldList fieldList optional predefined FieldList of the Record.
		 * @param WigiiBag wigiiBag optional predefined WigiiBag of the Record.
		 */
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
			 * @return Scalar the field value or undefined if field does not exist.
			 */
			self.getFieldValue = function(fieldName,subFieldName) {
				return self.wigiiBag().getValue(fieldName,subFieldName);
			};
			/**
			 * Sets the value of a Field in the Record
			 * @param Scalar value the field value to store into the Record
			 * @param String fieldName the name of the Field
			 * @param subFieldName the name of the subfield. If undefined, assumes 'value' subfield.
			 * @return Record for chaining
			 */
			self.setFieldValue = function(value,fieldName,subFieldName) {
				self.wigiiBag().setValue(value,fieldName,subFieldName);
				return self;
			};
		};
		
		// ServiceProvider
		
		/**
		 * Lookups a service instance given a service name
		 * returns undefined if not found
		 */
		wigiiApi.lookupService = function(serviceName) {
			if(!serviceName) return;
			var srvGetter = wigiiApi['get'+serviceName];
			if($.type(srvGetter) === 'function') {
				return srvGetter();
			}
		};
		/**
		 * Returns an instance of the debugger attached to the given class
		 */
		wigiiApi.getDebugLogger = function(typeName) {
			return new wigiiApi.DebugLogger(typeName);
		};
		/**
		 * Returns the ExceptionSink instance attached to the API
		 */
		wigiiApi.getExceptionSink = function() {
			if(!wigiiApi['exceptionSinkInstance']) {
				wigiiApi.exceptionSinkInstance = new wigiiApi.ExceptionSink();
			}
			return wigiiApi.exceptionSinkInstance;
		};
		/**
		 * Creates a new PopUp instance
		 */
		wigiiApi.createPopUpInstance = function(anchor,options) {
			return new wigiiApi.PopUp(anchor,options);
		};
		/**
		 * Returns a HelpService instance
		 */
		wigiiApi.getHelpService = function() {
			if(!wigiiApi['helpServiceInstance']) {
				wigiiApi.helpServiceInstance = new wigiiApi.HelpService();
			}
			return wigiiApi.helpServiceInstance;
		};
		/**
		 * Returns a JQueryService instance
		 */
		wigiiApi.getJQueryService = function() {
			if(!wigiiApi['jQueryServiceInstance']) {
				wigiiApi.jQueryServiceInstance = new wigiiApi.JQueryService();
			}
			return wigiiApi.jQueryServiceInstance;
		};
		/**
		 * Creates an HtmlBuilder instance
		 */
		wigiiApi.getHtmlBuilder = function() {			
			return new wigiiApi.HtmlBuilder();
		};
		/**
		 * Creates a FieldHelper instance
		 */
		wigiiApi.getFieldHelper = function() {
			return new wigiiApi.FieldHelper();
		};
		/**
		 * Creates a FormHelper instance
		 */
		wigiiApi.getFormHelper = function() {
			return new wigiiApi.FormHelper();
		};
		/**
		 * Creates an ArrayHelper instance
		 */
		wigiiApi.getArrayHelper = function() {
			return new wigiiApi.ArrayHelper();
		};
		/**
		 * Creates a FormEvent instance
		 */
		wigiiApi.createFormEventInstance = function(eventName,formId,fieldId,fieldName) {
			return new wigiiApi.FormEvent(eventName,formId,fieldId,fieldName);
		};
		/**
		 * Creates a Field instance
		 */
		wigiiApi.createFieldInstance = function(fieldName,dataType,label,attributes) {
			return new wigiiApi.Field(fieldName,dataType,label,attributes);
		};
		/**
		 * Creates a FieldList instance
		 */
		wigiiApi.createFieldListInstance = function() {
			return new wigiiApi.FieldList();
		};
		/**
		 * Creates a WigiiBag instance
		 */
		wigiiApi.createWigiiBagInstance = function() {
			return new wigiiApi.WigiiBag();
		};
		/**
		 * Creates a Record instance
		 */
		wigiiApi.createRecordInstance = function(fieldList,wigiiBag) {
			return new wigiiApi.Record(fieldList,wigiiBag);
		};
		
		// Wigii client
					
		/**
		 * Logs a message in the console
		 * @param String message the message to log.
		 */
		wigiiApi.log = function(message) {			
			wigiiApiConsole().log("INFO WigiiApi : "+message);
		};
		/**
		 * Clears the WigiiAPI log console
		 */
		wigiiApi.clearLog = function() {
			wigiiApiConsole().clear();
		};
		/**
		 * Returns WigiiApi DebugLogger instance
		 */
		wigiiApi.debugLogger = function() {
			if(!wigiiApi['debugLoggerInstance']) {
				wigiiApi.debugLoggerInstance = wigiiApi.getDebugLogger('WigiiApi');
			}
			return wigiiApi.debugLoggerInstance;
		};
		/**
		 * Builds a complete Wigii Update url given a sub-url.
		 * @param String url a logical update url of the form idAnswer/WigiiNamespace/Module/action/parameter
		 * @return String complete encoded url ready to be passed to an AJAX query.
		 */
		wigiiApi.buildUpdateUrl = function(url) {
			return encodeURI(wigiiApi.SITE_ROOT +"Update/"+wigiiApi.crtContextId+wigiiApi.EXEC_requestSeparator+url);
		};
		/**
		 * Builds a Wigii Update callback function which can be passed as a SUCCESS function to an AJAX query.
		 * @param Object context a map of [idAnswer=>context object] to be passed to the parseUpdateResult function.
		 * @return Function the callback function
		 */
		wigiiApi.buildUpdateCallback = function(context) {
			return function(data,textStatus){wigiiApi.parseUpdateResult(data,textStatus,context);};
		};
		/**
		 * Wigii Update protocol query answer parser
		 * @param String html string received from server following Wigii Update protocol format
		 * @param String textStatus JQuery AJAX text status
		 * @param Object context the optional map [idAnswer=>context object] used to retrieve contextual data according to idAnswer.
		 */
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
		};
		
		// Functions
		
		/**
		 * Converts a date to a string in format YYYY-MM-DD
		 * @param Date date the date instance to convert to string. If undefined takes current date.
		 * @return String the date formatted as a String
		 */
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
		};
		/**
		 * Converts a date to a string in format YYYY-MM-DD hh:mm
		 * @param Date date the date instance to convert to string. If undefined takes current date.
		 * @return String the date formatted as a String
		 */
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
		};
		/**
		 * Converts a date to a string in format YYYY-MM-DD hh:mm:ss
		 * @param Date date the date instance to convert to string. If undefined takes current date.
		 * @return String the date formatted as a String
		 */
		wigiiApi.seconds2string = function(date) {
			if(!date) date = new Date();
			var returnValue = wigiiApi.minutes2string(date);
			// seconds
			var c = date.getSeconds();
			if(c<10) returnValue += ":0"+c;
			else returnValue += ":"+c;
			
			return returnValue;
		};
		/**
		 * Converts a date to a string in format YYYY-MM-DD hh:mm:ss,milliseconds
		 * @param Date date the date instance to convert to string. If undefined takes current date.
		 * @return String the date formatted as a String
		 */
		wigiiApi.milliseconds2string = function(date) {
			if(!date) date = new Date();
			var returnValue = wigiiApi.seconds2string(date);
			// milliseconds
			var c = date.getMilliseconds();
			if(c<10) returnValue += ",00"+c;
			if(c<100) returnValue += ",0"+c;
			else returnValue += ","+c;
			
			return returnValue;
		};			
		/**
		 * Serializes an XML Dom object to string
		 * @param XMLDocument xmlDom an XML DOM document as created by calling jQuery.parseXML
		 * @return String XML serialized
		 */
		wigiiApi.xml2string = function(xmlDom) {
			return (typeof XMLSerializer!=="undefined") ? 
					(new window.XMLSerializer()).serializeToString(xmlDom) : 
					xmlDom.xml;
		};
		/**
		 * throws a ServiceException::NOT_IMPLEMENTED exception
		 */
		wigiiApi.throwNotImplemented = function() {
			throw new wigiiApi.ServiceException("not implemented", wigiiApi.errorCodes.NOT_IMPLEMENTED);
		};
		/**
		 * throws a ServiceException 
		 */
		wigiiApi.createServiceException = function(message,code,previous) {
			return new wigiiApi.ServiceException(message, code, previous);
		};
		/**
		 * Publishes an exception that cannot be handled
		 */
		wigiiApi.publishException = function(exception) {
			wigiiApi.getExceptionSink().publish(exception);
		};
		/**
		 * Converts an exception to HTML code that can be displayed
		 * @param Object exception an exception object {name:string,code:int,message:string}
		 * @param Object context if defined, then information about server context in the form 
		 * {request:string, wigiiNamespace:string, module:string, action:string, realUsername:string, username:string, principalNamespace:string, version:string}
		 */
		wigiiApi.exception2html = function(exception,context) {
			htmlb = wigiiApi.getHtmlBuilder();
			
			htmlb.putStartTag('h2').put(exception.code).prepend(' ',wigiiApi.errorLabels[exception.code]).putEndTag('h2');
			htmlb.putStartTag('p').implode(' : ',exception.name,exception.message).putEndTag('p');
			if(context) {
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
		};
		/**
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
		 */
		wigiiApi.positionBox = function(mouseEvent,boxOptions,positionOptions) {
			if(!boxOptions) boxOptions = {};
			var screenTop=undefined,screenLeft=undefined,boxTop=undefined,boxLeft=undefined;			
			if(!positionOptions) positionOptions = {};
			if(!positionOptions.referenceWindow) positionOptions.referenceWindow = $(window);		
			// if no mouseEvent then takes window center
			if(!mouseEvent) {			
				screenTop = Math.floor(positionOptions.referenceWindow.height()/2);
				screenLeft = Math.floor(positionOptions.referenceWindow.width()/2);	
				if(!positionOptions.position) positionOptions.position = 'center';
			}
			// else takes mouse position
			else {
				screenTop = mouseEvent.pageY-positionOptions.referenceWindow.scrollTop();
				screenLeft = mouseEvent.pageX-positionOptions.referenceWindow.scrollLeft();
				if(!positionOptions.position) positionOptions.position = 'SE';
				if(!positionOptions.position=='center' && !positionOptions.offset && positionOptions.offset!==0) positionOptions.offset=15;
			}
			// calculates boxTop and boxLeft
			switch(positionOptions.position) {
			case 'center': 
				boxTop = screenTop-boxOptions.height/2;
				boxLeft = screenLeft-boxOptions.width/2;
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
				boxTop = Math.min(boxTop,positionOptions.referenceWindow.height()-boxOptions.height-15);
				boxLeft = Math.min(boxLeft,positionOptions.referenceWindow.width()-boxOptions.width-15);
			}
			boxOptions.left = Math.ceil(boxLeft);
			boxOptions.top = Math.ceil(boxTop);
			return boxOptions;
		};
		// Link with WigiiExecutor.js
		
		wigiiApi.initContext = function() {
			wigiiApi.SITE_ROOT = window.SITE_ROOT;
			wigiiApi.crtContextId = window.crtContextId;
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
		
		// FuncExp libraries and evaluation
		
		/**
		 * Pointer to the current FuncExp Library. Defaults to an instance of WigiiStdFL
		 */
		wigiiApi.fl;
		/**
		 * Initializes the current FuncExp Library by loading an instance of WigiiStdFL 
		 * and merging additional func exp libraries.
		 * @param Array|Object array or list of additional FuncExp libraries to load.
		 */
		wigiiApi.initFL = function(libs) {
			
			// default FL
			wigiiApi.fl = new WigiiStdFL(wigiiApi);
			// merge additional FLs
			for(var i=0;i<arguments.length;i++) {
				var lib = arguments[i];
				if($.isArray(lib)) {
					for(var j=0;j<lib.length;j++) {
						var lib2 = lib[j];
						if($.type(lib2) === 'object') {
							for(var fxName in lib2) {
								this.fl[fxName] = lib2[fxName];
							}
						}
					}
				}
				else if($.type(lib) === 'object') {
					for(var fxName in lib) {
						this.fl[fxName] = lib[fxName];
					}
				}				
			}
		};
		/**
		 * Evaluates the given FuncExp in the scope of the current FuncExp library
		 * @param Function fx the func exp to evaluate.
		 * @return Any
		 */
		wigiiApi.evalfx = function(fx) {
			var returnValue = undefined;
			try {
				if($.type(fx) === 'function') {
					if(!this.fl) this.initFL();
					this.fl.currentFx = fx;
					var res = this.fl.currentFx();
					if($.type(res) === 'function') returnValue = res();
					else returnValue = res;
				}
				else returnValue = fx;
			}
			catch(e) {this.publishException(e);}
			return returnValue;
		};
		
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
	wigiiApiInstance.initFL();
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
	 * @return JQuery|Any returns the service or command result if defined, or the JQuery collection if no specific result.
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