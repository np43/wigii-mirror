/*!
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
 *  @copyright  Copyright (c) 2016-2017  Wigii.org
 *  @author     <http://www.wigii.org/system/libs>      Wigii.org 
 *  @link       <http://www.wigii-system.net>     		<https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     		GNU General Public License
 */
 
 /**
  * Wigii Natural Code Development (NCD) core library
  * Created by Camille Weber (camille@wigii.org), 23.05.2016
  * Updated version 2.0 by Camille Weber (camille@wigii.org), 27.10.2017
  * @param Window window current browser window
  * @param JQuery $ depends on JQuery
  */
 (function (window, $){
	// Configuration options
	var wigiiNcdOptions = undefined;
	if(window.wigiiNcd && window.wigiiNcd.options) wigiiNcdOptions = window.wigiiNcd.options;
	if(!wigiiNcdOptions) wigiiNcdOptions = {};
	// Private members management
	if(!wigiiNcdOptions.privateNcdMembers) wigiiNcdOptions.privateNcdMembers = {};
	/**
	 * Marks a member as private. A private member is not published into the wncd symbol.
	 *@param String memberName the name of the variable or function to be marked as private
	 */
	var ncdprivate = function(memberName) {
		wigiiNcdOptions.privateNcdMembers[memberName] = true;
	};
	
	// Wigii NCD
	var WigiiNcd = function() {
		var wigiiNcd = this;
		wigiiNcd.instantiationTime = Date.now();ncdprivate('instantiationTime');
		/**
		 * Wigii NCD base key that can be used when generating unique context keys
		 */
		wigiiNcd.ctxKey = 'WigiiNcd_'+wigiiNcd.instantiationTime;
		/**
		 * Object which holds inner private mutable state variables.
		 */
		wigiiNcd.context = {};ncdprivate('context');
						
		// Error codes
		
		wigiiNcd.errorCodes = {
				
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
			
			// StringTokenizerException (error code range from 3800 to 3899)			
			SYNTAX_ERROR: 3800,
			
			// FuncExpEvalException (error code range from 4600 to 4699)
			
			SYMBOL_NOT_FOUND: 4600,
			INVALID_RETURN_VALUE: 4601,
			VARIABLE_NOT_DECLARED: 4602,
			DIVISION_BY_ZERO: 4603,
			ASSERTION_FAILED: 4604
			
		};
		// initializes error labels
		wigiiNcd.initializeErrorLabels = function() {
			if(!wigiiNcd.errorLabels) {
				wigiiNcd.errorLabels = {};
				for(var errName in wigiiNcd.errorCodes) {
					wigiiNcd.errorLabels[wigiiNcd.errorCodes[errName]] = errName;
				}
			}			
		};ncdprivate('initializeErrorLabels');
		wigiiNcd.initializeErrorLabels();
		
		// NCD Exceptions
		
		/**
		 * ServiceException class
		 * @param String message the error message
		 * @param Number code the error code
		 * @param Object previous if defined, the previous exception in the chain if wrapping.
		 */
		wigiiNcd.ServiceException = function(message,code,previous) {			
			var self = this;
			self.name = 'ServiceException';
			self.message = message;
			self.code = code || wigiiNcd.errorCodes.UNKNOWN_ERROR;
			self.previousException = previous; 
		};
		
		// NCD Services
		
		/**
		 * A String Stack object
		 */
		wigiiNcd.StringStack = function() {
			var self = this;
			self.className = 'StringStack';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className;
			
			self.stack = [];
			self.popSubscribers = [];
			
			/**
			 * Pushes a new element on top of the stack
			 *@return wigiiNcd.StringStack for chaining
			 */
			self.push = function(str) {
				self.stack.push({start:str,content:'',end:undefined});
				return self;
			};
			/**
			 * Appends a string to current element in stack
			 *@return wigiiNcd.StringStack for chaining
			 */
			self.append = function(str) {
				var top = undefined;
				if(self.stack.length>0) top = self.stack[self.stack.length-1];
				if(!top) {
					top = {start:'',content:''+str,end:undefined};
					self.stack.push(top);
				}
				else top.content += str;
				return self;
			};
			/**
			 * Returns current content in stack
			 *@return String current content
			 */
			self.current = function() {
				if(self.stack.length>0) return self.stack[self.stack.length-1].content;
				else return '';
			};
			/**
			 * Resets stack
			 *@return wigiiNcd.StringStack for chaining
			 */
			self.reset = function() {
				self.stack = [];
			};
			/**
			 * Pops top element from stack
			 *@param String str optional closing element string to append to top element before poping
			 *@return if stack is emptied then returns content, else returns wigiiNcd.StringStack for chaining
			 */
			self.pop = function(str) {
				var top = self.stack.pop();
				if(!top) top = {start:'',content:'',end:undefined};
				top.end = ''+str;
				// calls any registred eventHandlers
				if(self.popSubscribers.length>0) {
					for(var i=0;i<self.popSubscribers.length;i++) {
						self.popSubscribers[i](top.start, top.content, top.end);
					}
				}
				// if stack is not empty, then updates parent content
				if(self.stack.length>0) {
					self.stack[self.stack.length-1].content += top.start+top.content+top.end;
				}
				// else returns content
				else return top.start+top.content+top.end;
			};
			/**
			 * Registers an eventHandler which is called each time an element is poped
			 *@param Function eventHandler a function with signature eventHandler(startTag, content, endTag)
			 */
			self.onPop = function(eventHandler) {
				if(!$.isFunction(eventHandler)) throw wigiiNcd.createServiceException('pop event handler should be a function', wigiiNcd.errorCodes.INVALID_ARGUMENT);
				self.popSubscribers.push(eventHandler);
			};			
		}
		
		/**
		 * HTML Emitter object
		 *@param jQuery|DOM.Element output the element in which to emit HTML code, defaults to body if not specified.
		 */
		wigiiNcd.HtmlEmitter = function(output) {
			var self = this;
			self.className = 'HtmlEmitter';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className;
			self.options = {};
			self.context = {};
			self.impl = {};
			self.modelers = [];
			self.children = {};
			
			if(!output) output = $('body');
			else output = $(output);
			
			// Properties
			
			self.$ = function() {return output;};
			
			// Configuration
			
			self.emittedClass = function(cssClass) {
				if(cssClass) self.options.emittedClass = cssClass;
				if(!self.options.emittedClass) self.options.emittedClass = 'ncd';
				return self.options.emittedClass;
			}
						
			/**
			 * Adds a modeler to the list of modelers and calls it on this instance and all its children.
			 *@param Function modeler a function used to model this instance. Function signature is modeler(this).
			 */
			self.addModeler = function(modeler) {
				self.modelers.push(modeler);
				modeler(self);
				if(self.children) {
					for(var i=0;i<self.children.length;i++) {
						self.children[i].addModeler(modeler);
					}
				}
				return self;
			};
			
			/**
			 * Sets a cursor in the current output before which everything will be inserted. The cursor should be contained in the output.
			 *@param jQuery|DOM.Element node the element contained in the current output which will act as an insert pointer.
			 */
			self.setCursor = function(node) {				
				self.context.cursor = output.find(node);
			};
			/**
			 * Removes any cursor set in the current output
			 */
			self.removeCursor = function() {
				self.context.cursor = undefined;
			};
			
			// HTML emitting functions
			
			/**
			 * Outputs a string
			 */
			self.put = function(str){
				self.htmlTree.append(str);
				return self;
			};
			/**
			 * Emits a <h1> start tag
			 */
			self.startH1 = function(cssClass){
				self.htmlTree.push(wigiiNcd.getHtmlBuilder().putStartTag('h1','class',self.emittedClass()+(cssClass?' '+cssClass:'')).html());
				return self;
			};
			/**
			 * Emits a </h1> end tag
			 */
			self.endH1 = function(){
				self.impl.putHtml(self.htmlTree.pop('</h1>'));				
				return self;
			};
			/**
			 * Emits a <p> start tag
			 */
			self.startP = function(cssClass){
				self.htmlTree.push(wigiiNcd.getHtmlBuilder().putStartTag('p','class',self.emittedClass()+(cssClass?' '+cssClass:'')).html());
				return self;
			};
			/**
			 * Emits a </p> end tag
			 */
			self.endP = function(){
				self.impl.putHtml(self.htmlTree.pop('</p>'));				
				return self;
			};
			/**
			 * Emits a <span> start tag
			 */
			self.startSpan = function(cssClass){
				self.htmlTree.push(wigiiNcd.getHtmlBuilder().putStartTag('span','class',self.emittedClass()+(cssClass?' '+cssClass:'')).html());
				return self;
			};
			/**
			 * Emits a </span> end tag
			 */
			self.endSpan = function(){
				self.impl.putHtml(self.htmlTree.pop('</span>'));
				return self;
			};
			/**
			 * Emits a span with a given color for text and background.
			 * If background color is given, then border color is equal to text color.
			 */
			self.startColor = function(color,backgroundColor,cssClass) {
				self.htmlTree.push(wigiiNcd.getHtmlBuilder().putStartTag('span','class',self.emittedClass()+(cssClass?' '+cssClass:''),
				'style', 'color:'+color+';'+(backgroundColor?'margin:0.05em;padding:0.03em 0.1em;border-style:solid;border-radius:0.2em;background-color:'+backgroundColor+';border-color:'+color+';':'')).html());
				return self;
			};
			/**
			 * Emits end of colored span
			 */
			self.endColor = function() {
				self.impl.putHtml(self.htmlTree.pop('</span>'));	
				return self;
			};	
			/**
			 * Emits a div which has a delay before displaying
			 *@param Integer delay in seconds before displaying the div
			 */
			self.startDelay = function(delay,cssClass) {
				self.htmlTree.push(wigiiNcd.getHtmlBuilder().putStartTag('div','class',self.emittedClass()+(cssClass?' '+cssClass:''),
				'style', 'display:none;','data-ncd-delay',delay).html());
				return self;
			};
			/**
			 * Closes delayed div
			 */
			self.endDelay = function() {
				var delayedDiv = self.impl.putHtml(self.htmlTree.pop('</div>'), true);
				delayedDiv.delay(delayedDiv.attr('data-ncd-delay')*1000).fadeIn(700);	
				return self;
			};
			
			/**
			 * Emits a button
			 */
			self.putButton = function(label, onClick, cssClass){
				self.htmlTree.push(wigiiNcd.getHtmlBuilder().putStartTag('button','class',self.emittedClass()+(cssClass?' '+cssClass:'')).html());
				self.htmlTree.append(label);
				var b = self.impl.putHtml(self.htmlTree.pop('</button>'), true);
				if($.isFunction(onClick) && b) b.off().click(function(){
					if(window.wigiiNcdEtp && window.wigiiNcdEtp.program.context) window.wigiiNcdEtp.program.context.html(self);
					try {onClick();}
					catch(exc) {self.publishException(exc);}
				});				
				return self;
			};
			/**
			 * Creates and emits a Grid with the given number of rows and cols
			 */
			self.createGrid = function(nRows,nCols) {
				return new wigiiNcd.Grid(self, nRows, nCols);
			};
			/**
			 * Creates and emits a TextArea to capture a multiline user input
			 */
			self.createTextArea = function(cssClass) {
				return new wigiiNcd.TextArea(self, cssClass);
			};
			/**
			 * Creates and emits a TextInput to capture a single line user input
			 */
			self.createTextInput = function(cssClass) {
				return new wigiiNcd.TextInput(self, cssClass);
			};
			/**
			 * Creates and emits a PasswordInput to capture a secret input
			 */
			self.createPasswordInput = function(cssClass) {
				return new wigiiNcd.PasswordInput(self, cssClass);
			};
			/**
			 * Creates and emits a Checkbox
			 */
			self.createCheckbox = function(cssClass,index) {
				return new wigiiNcd.CheckBox(self, cssClass,index);
			};
			/**
			 * Creates and emits a UnorderedList
			 */
			self.createUnorderedList = function(itemGenerator,itemRenderer,options){ 
				return new wigiiNcd.UnorderedList(self,itemGenerator,itemRenderer,options);
			};
			/**
			 * Creates and emits a UnorderedList. Alias of createUnorderedList method
			 */
			self.list = self.createUnorderedList;
			
			/**
			 * Creates an HtmlBuilder which helps building HTML tags.
			 * After building the HTML chain, call the emit function to push the HTML code to this HtmlEmitter.
			 */
			self.htmlBuilder = function() {
				return wigiiNcd.getHtmlBuilder(self);
			};
			/**
			 * Creates or returns a div inside the current container.
			 *@param String id HTML ID for the created div or to select the inner div.
			 *@param String cssClass additional class name to add to the created div
			 *@return wigiiNcd.HtmlEmitter returns a wigiiNcd HtmlEmitter instance ready to interact with the specified div
			 */
			self.div = function(id,cssClass) {
				var returnValue = self.children[id];
				if(!returnValue) {
					self.putHtml(wigiiNcd.getHtmlBuilder()
						.putStartTag('div','class',self.emittedClass()+(cssClass?' '+cssClass:''), "id", id)
						.putEndTag('div')
						.html());
					returnValue = wigiiNcd.getHtmlEmitter(output.find('#'+id));
					// models child HTMLEmitter if some modelers exists
					if(self.modelers) {
						for(var i=0;i<self.modelers.length;i++) {
							returnValue.addModeler(self.modelers[i]);
						}
					}
					// stores child in children list
					self.children[id] = returnValue;
				}
				return returnValue;
			};
			
			/**
			 * Creates a clone of this HtmlEmitter linked to an output element. The clone inherits from all modelers.
			 *@param jQuery|DOM.Element output the new element in which to emit HTML code.
			 */
			self.clone = function(output) {
				if(output===undefined) return self;
				var returnValue = wigiiNcd.getHtmlEmitter(output);
				// models child HTMLEmitter if some modelers exists
				if(self.modelers) {
					for(var i=0;i<self.modelers.length;i++) {
						returnValue.addModeler(self.modelers[i]);
					}
				}
				return returnValue;
			};
			
			// Control functions
			
			/**
			 * Resets container
			 */
			self.reset = function() {
				self.children = {};
				self.context = {};
				output.empty();
				return self;
			};
			/**
			 * Clears all errors from container
			 */
			self.clearErrors = function() {				
				output.find('p.'+self.emittedClass()+'-error').remove();
				return self;
			};
			/**
			 * Ends current HTML emitting session and controls stack
			 */
			self.end = function() {
				self.impl.putHtml(self.htmlTree.pop(''));
				return self;
			};
			/**
			 * Publishes any catched exception
			 */
			self.publishException = function(exception) {
				var htmlb = wigiiNcd.getHtmlBuilder();
				htmlb.putStartTag('p','class',self.emittedClass()+'-error').put(exception.code).prepend(' ',wigiiNcd.errorLabels[exception.code]).putBr()
				.implode(' : ',exception.name,exception.message.replace(/</g,'&lt;').replace(/>/g,'&gt;'))
				.putEndTag('p');	
				self.impl.putHtml(htmlb.html());
				return self;
			};
			/**
			 * Emits some well formed HTML (should be used to link other components)
			 */
			self.putHtml = function(html) {
				self.impl.putHtml(html);
				return self;
			};
			
			// Low level implementation
			
			/**
			 * Inserts some html into the current output.
			 *@param HTML html well formed html string to insert
			 *@return jQuery if returnLastCreated returns the last created node, else returns the output
			 */
			self.impl.putHtml = function(html,returnLastCreated) {
				// if a cursor is defined, inserts before the cursor
				if(self.context.cursor) {
					self.context.cursor.before(html);
					if(returnLastCreated) return self.context.cursor.prev();
					else return output;
				}
				// else appends at the end
				else {
					output.append(html);
					if(returnLastCreated) return output.children().last();
					else return output;
				}
			}
			
			// HTML tree check
			
			self.htmlTree = wigiiNcd.createStringStackInstance();
			// checks that start and end tags are equal
			self.htmlTree.onPop(function(start,content,end) {				
				if(!start && !end) return;
				var i = end.indexOf('</');
				var j = end.indexOf('>');
				var endtag = end.substring(i+2,j).trim();
				i = start.indexOf('<');
				j = start.indexOf(' ');
				var starttag = start.substring(i+1,j).trim();
				if(endtag != starttag) throw wigiiNcd.createServiceException("invalid end tag '"+endtag+"' in context "+start+content.substr(0,64)+(content.length>64?'...':'')+end, wigiiNcd.errorCodes.SYNTAX_ERROR);
			});
		};
		
		/**
		 * HTML String builder
		 *@param wigiiNcd.HtmlEmitter optional HtmlEmitter instance that can be linked to html builder in which to emit the constructed html code.
		 */
		wigiiNcd.HtmlBuilder = function(htmlEmitter) {
			var self = this;
			self.className = 'HtmlBuilder';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now();
			
			self.context = {htmlEmitter: htmlEmitter};			
			self.buffer = '';
			self.jsBuffer = [];
			self.differedQueue = [];
			
			/**
			 * Returns built html string
			 * @return String
			 */
			self.html = function() {
				// executes differed queue if not empty
				if(self.differedQueue.length > 0) {
					var returnValue = '';
					for(var i=0; i < self.differedQueue.length; i++) {
						var f = self.differedQueue[i];						
						if($.isFunction(f)) {
							var r = f();
							if(r!==undefined) returnValue += r;
						} 
						else if($.isPlainObject(f)) {
							var r = f.fx.apply(null,f.args);
							if(r!==undefined) returnValue += r;
						}
						else returnValue += f;
					}
					// adds buffer content
					returnValue += self.buffer;
					return returnValue;
				}
				// else returns HTML buffer
				else return self.buffer;
			};
			/**
			 * Emits the html string into the given HtmlEmitter or the default one if defined
			 * @return wigiiNcd.HtmlEmitter for chaining
			 */
			self.emit = function(htmlEmitter) {
				if(!htmlEmitter) htmlEmitter = self.context.htmlEmitter;
				if(htmlEmitter) {
					var htmls = '';
					// executes differed queue if not empty
					if(self.differedQueue.length > 0) {
						var insertionTags = [];
						for(var i=0; i < self.differedQueue.length; i++) {
							var f = self.differedQueue[i];
							if($.isFunction(f)) f = {fx: f};							
							if($.isPlainObject(f)) {
								// creates an insertion tag
								f.id = self.ctxKey+"_"+i;
								htmls +='<span id="'+f.id+'"></span>';
								insertionTags.push(f);
							} 
							else htmls+= f;
						}
						// adds buffer content
						if(self.buffer != '') htmls += self.buffer;
						// emits html string
						htmlEmitter.putHtml(htmls);
						// resolves insertion tags
						if(insertionTags.length > 0) {
							for(var i=0; i < insertionTags.length; i++) {
								var insertionTag = htmlEmitter.$().find('#'+insertionTags[i].id);
								if(insertionTag) {
									htmlEmitter.setCursor(insertionTag);
									if(window.wigiiNcdEtp && window.wigiiNcdEtp.program.context) window.wigiiNcdEtp.program.context.html(htmlEmitter);
									var r = insertionTags[i];
									r = r.fx.apply(null, r.args);
									if(r!==undefined) htmlEmitter.putHtml(r);
									htmlEmitter.removeCursor();
									insertionTag.remove();
								}
							}
						}
					}
					// else emits HTML buffer
					else if(self.buffer != '') htmlEmitter.putHtml(self.buffer);
					return htmlEmitter;
				}							
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
				self.differedQueue = [];
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
				if(!multiplier) multiplier=1;
				for(var i=0;i<multiplier;i++) {
					self.buffer += '&nbsp;';
				}
				return self;
			};
			/**
			 * Repeats an br tag several times
			 * @return HtmlBuilder for chaining
			 */
			self.putBr = function(multiplier) {
				if(!multiplier) multiplier=1;
				for(var i=0;i<multiplier;i++) {
					self.buffer += '<br/>';
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
				if(!tagName) throw wigiiNcd.createServiceException('putStartTag takes a non null tagName', wigiiNcd.errorCodes.INVALID_ARGUMENT);
				self.buffer += '<'+tagName;
				if(arguments.length>1) {
					var i = 1;
					var key,value;
					while(i<arguments.length) {
						key = arguments[i];
						if(!key) throw wigiiNcd.createServiceException('html attribute name cannot be null', wigiiNcd.errorCodes.INVALID_ARGUMENT);
						i++;
						if(i<arguments.length) {
							value = arguments[i];
							i++;
						}
						else value = '';
						if(value !== '' && value !== undefined) self.buffer += ' '+key+'="'+value+'"';
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
				if(!tagName) throw wigiiNcd.createServiceException('putEndTag takes a non null tagName', wigiiNcd.errorCodes.INVALID_ARGUMENT);
				self.buffer += '</'+tagName+'>';
				return self;
			};
			/**
			 * Creates an html document header 
			 * @return HtmlBuilder for chaining
			 */
			self.putHtmlHeader = function() {
				self.buffer += '<!DOCTYPE html>';
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
					self.jsBuffer.push(actions);
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
			
			/**
			 * Inserts a piece of generated html. It can be a component or the result of a function.
			 *@param Function f the function used to generate the HTML to be inserted. 
			 * The function can be contextual and can take some parameters. In that case, pass the arguments to be injected following the function.
			 *@example self.insert(function(article){ program.form.createField("title", "Title").value(article.title);}, article)
			 */
			self.insert = function(f) {
				if(f) {
					var args;
					if(arguments.length > 1) args = Array.prototype.slice.call(arguments,1);
					else args = [];
					// flushes buffer in differed queue.
					if(self.buffer != '') {
						self.differedQueue.push(self.buffer);
						self.buffer = '';
					}
					// adds script to queue.
					self.differedQueue.push((args.length > 0 ? {fx: f, args: args}: f));					
				}
				return self;
			};
			
			// NCD Synonyms
			self.tag = self.putStartTag;
			self.$tag = self.putEndTag;
			self.nbsp = self.putNbsp;
			self.br = self.putBr;
			self.amp = self.putAmp;
			self.apos = self.putApos;
			self.quot = self.putQuot;
			self.hashTag = self.putHashTag;
			self.gt = self.putGt;
			self.lt = self.putLt;
			self.out = self.put;
		};
		
		/**
		 * NCD 2D fixed Grid
		 *@param wigiiNcd.HtmlEmitter htmlEmitter underlying open HTML emitter to which dump the 2D Grid
		 *@param int nRows number of rows in the Grid
		 *@param int nCols number of columns in the Grid
		 */
		wigiiNcd.Grid = function(htmlEmitter, nRows,nCols) {
			var self = this;
			self.className = 'Grid';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now();
			
			self.context = {};
			self.context.rows = [];
			var htmlB = wigiiNcd.getHtmlBuilder();
			htmlB.putStartTag('table','class',htmlEmitter.emittedClass(),"id",self.ctxKey);
			for(var i=0;i<nRows;i++) {
				htmlB.putStartTag('tr','class',htmlEmitter.emittedClass());
				self.context.rows.push([]);
				for(var j=0;j<nCols;j++) {
					var id = self.ctxKey+"_"+i+"_"+j;
					htmlB.putStartTag('td','class',htmlEmitter.emittedClass(),"id",id);
					self.context.rows[i].push(new wigiiNcd.GridCell(self,i,j,id));					
					htmlB.putNbsp(4);
					htmlB.putEndTag('td');
				}
				htmlB.putEndTag('tr');
			}
			htmlB.putEndTag('table');
			htmlEmitter.putHtml(htmlB.html());
			
			// Properties
			
			self.cell = function(x,y) {
				if(x<0||x>=nRows) return undefined;
				if(y<0||y>=nCols) return undefined;
				return self.context.rows[x][y];
			};
			self.nRows = function() {return nRows;}
			self.nCols = function() {return nCols;}
		};
		/**
		 * NCD 2D fixed Grid cell
		 *@param wigiiNcd.Grid grid reference to grid container in which lives the cell
		 *@param int x row index from 0..Grid.nRows-1
		 *@param int y col index from 0..Grid.nCols-1
		 *@apram string id HTML ID of the cell element in the DOM.
		 */
		wigiiNcd.GridCell = function(grid, x,y, id) {
			var self = this;
			self.className = 'GridCell';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className;
			
			// Inner state
			
			self.context = {};
			
			// Properties
			
			self.text = function(txt) {
				if(txt===undefined) return self.context.text;
				else {
					self.context.text = txt;
					$("#"+id).html(txt);
					return self;
				}
			};
			self.color = function(c) {
				if(c===undefined) return self.context.color;
				else  {
					self.context.color = c;
					$("#"+id).css('background-color',c);
					return self;
				}
			};
			self.left = function(wrap) {
				var neighbour = y-1;
				if(neighbour<0) {
					if(wrap) neighbour = grid.nCols()-1;
					else return undefined;
				}
				return grid.cell(x,neighbour);
			};
			self.right = function(wrap) {
				var neighbour = y+1;
				if(neighbour>=grid.nCols()) {
					if(wrap) neighbour = 0;
					else return undefined;
				}
				return grid.cell(x,neighbour);
			};
			self.up = function(wrap) {
				var neighbour = x-1;
				if(neighbour<0) {
					if(wrap) neighbour = grid.nRows()-1;
					else return undefined;
				}
				return grid.cell(neighbour,y);
			};
			self.down = function(wrap) {
				var neighbour = x+1;
				if(neighbour>=grid.nRows()) {
					if(wrap) neighbour = 0;
					else return undefined;
				}
				return grid.cell(neighbour,y);
			};
			self.offset = function(x,y) {
				var newX = (self.x() + x)%grid.nRows();
				var newY = (self.y() + y)%grid.nCols();
				return grid.cell(newX,newY);
			};
			self.grid = function() {
				return grid;
			};
			self.x = function() {
				return x;
			};
			self.y = function() {
				return y;
			};
			self.id = function() {
				return id;
			};
			self.click = function(onClick) {
				if($.isFunction(onClick)) $("#"+id).off('click').click(function(){onClick(self);});
				else if(onClick===undefined) $("#"+id).click();
				return self;
			};			
		};
		
		/**		
		 * NCD TextArea
		 *@param wigiiNcd.HtmlEmitter htmlEmitter underlying open HTML emitter to which dump the text area component
		 */
		wigiiNcd.TextArea = function(htmlEmitter, cssClass) {
			var self = this;
			self.className = 'TextArea';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now();
			
			self.context = {};

			var htmlB = wigiiNcd.getHtmlBuilder();
			htmlB.putStartTag('textarea','class',htmlEmitter.emittedClass()+(cssClass?' '+cssClass:''), "id", self.ctxKey);		
			htmlB.putEndTag('textarea');
			htmlEmitter.putHtml(htmlB.html());
			
			// Properties
			
			self.$ = function() {return $("#"+self.ctxKey);	};
			
			/**
			 * Sets background color and text color
			 */
			self.color = function(backgroundC,textC) {
				var elt = $("#"+self.ctxKey);			
				if(backgroundC) elt.css('background-color',backgroundC);
				if(textC) elt.css('color',textC);
				return self;
			};
			
			/**
			 * Sets or returns the text contained in this TextArea
			 */
			self.text = function(txt) {
				if(txt===undefined) return self.context.text;
				else {
					self.context.text = txt;
					$("#"+self.ctxKey).val(txt);
					return self;
				}
			};
			/**
			 * Registers a oninput event handler
			 */
			self.onInput = function(onInput) {
				if($.isFunction(onInput)) {
					if(!self.context.onInputSubscribers) {
						self.context.onInputSubscribers = [];
						// registers oninput event handler on text area
						$("#"+self.ctxKey).on('input', function(){self.onInput();})
					}
					self.context.onInputSubscribers.push(onInput);
				}
				else if(onInput===undefined) {
					if(self.context.onInputSubscribers) {
						for(var i=0;i<self.context.onInputSubscribers.length;i++) {
							var eh = self.context.onInputSubscribers[i];
							if($.isFunction(eh)) eh(self,$("#"+self.ctxKey).val());
						}
					}
				}
				return self;
			};
			/**
			 * Binds an autocompletion sense on this txt input
			 */
			self.autocomplete = function(propositionGenerator,options) {
				wigiiNcd.bindAutoCompletionSense(self,propositionGenerator,options);
				return self; 
			};
		};			
		
		/**		
		 * NCD TextInput
		 *@param wigiiNcd.HtmlEmitter htmlEmitter underlying open HTML emitter to which dump the text input component
		 */
		wigiiNcd.TextInput = function(htmlEmitter, cssClass) {
			var self = this;
			self.className = 'TextInput';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now();
			
			self.context = {};

			var htmlB = wigiiNcd.getHtmlBuilder();
			htmlB.putStartTag('input','type','text','class',htmlEmitter.emittedClass()+(cssClass?' '+cssClass:''), "id", self.ctxKey);		
			htmlB.putEndTag('input');
			htmlEmitter.putHtml(htmlB.html());
			
			// Properties
			
			self.$ = function() {return $("#"+self.ctxKey);	};
			
			/**
			 * Sets background color and text color
			 */
			self.color = function(backgroundC,textC) {
				var elt = $("#"+self.ctxKey);			
				if(backgroundC) elt.css('background-color',backgroundC);
				if(textC) elt.css('color',textC);
				return self;
			};
			/**
			 * Sets or returns the text contained in this TextArea
			 */
			self.text = function(txt) {
				if(txt===undefined) return self.context.text;
				else {
					self.context.text = txt;
					$("#"+self.ctxKey).val(txt);
					return self;
				}
			};
			/**
			 * Registers a oninput event handler
			 */
			self.onInput = function(onInput) {
				if($.isFunction(onInput)) {
					if(!self.context.onInputSubscribers) {
						self.context.onInputSubscribers = [];
						// registers oninput event handler on text area
						$("#"+self.ctxKey).on('input', function(){self.onInput();})
					}
					self.context.onInputSubscribers.push(onInput);
				}
				else if(onInput===undefined) {
					if(self.context.onInputSubscribers) {
						for(var i=0;i<self.context.onInputSubscribers.length;i++) {
							var eh = self.context.onInputSubscribers[i];
							if($.isFunction(eh)) eh(self,$("#"+self.ctxKey).val());
						}
					}
				}
				return self;
			};
			/**
			 * Binds an autocompletion sense on this txt input
			 */
			self.autocomplete = function(propositionGenerator,options) {
				wigiiNcd.bindAutoCompletionSense(self,propositionGenerator,options);
				return self; 
			};
		};
		
		/**		
		 * NCD Password Input
		 *@param wigiiNcd.HtmlEmitter htmlEmitter underlying open HTML emitter to which dump the password input component
		 */
		wigiiNcd.PasswordInput = function(htmlEmitter, cssClass) {
			var self = this;
			self.className = 'PasswordInput';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now();
			
			self.context = {};

			var htmlB = wigiiNcd.getHtmlBuilder();
			htmlB.putStartTag('input','type','password','class',htmlEmitter.emittedClass()+(cssClass?' '+cssClass:''), "id", self.ctxKey);		
			htmlB.putEndTag('input');
			htmlEmitter.putHtml(htmlB.html());
			
			// Properties
			
			self.$ = function() {return $("#"+self.ctxKey);	};
			
			/**
			 * Sets background color and text color
			 */
			self.color = function(backgroundC,textC) {
				var elt = $("#"+self.ctxKey);			
				if(backgroundC) elt.css('background-color',backgroundC);
				if(textC) elt.css('color',textC);
				return self;
			};
			/**
			 * Sets or returns the text contained in this PasswordInput
			 */
			self.text = function(txt) {
				if(txt===undefined) return self.context.text;
				else {
					self.context.text = txt;
					$("#"+self.ctxKey).val(txt);
					return self;
				}
			};
			/**
			 * Registers a oninput event handler
			 */
			self.onInput = function(onInput) {
				if($.isFunction(onInput)) {
					if(!self.context.onInputSubscribers) {
						self.context.onInputSubscribers = [];
						// registers oninput event handler on text area
						$("#"+self.ctxKey).on('input', function(){self.onInput();})
					}
					self.context.onInputSubscribers.push(onInput);
				}
				else if(onInput===undefined) {
					if(self.context.onInputSubscribers) {
						for(var i=0;i<self.context.onInputSubscribers.length;i++) {
							var eh = self.context.onInputSubscribers[i];
							if($.isFunction(eh)) eh(self,$("#"+self.ctxKey).val());
						}
					}
				}
				return self;
			};
			/**
			 * Binds an autocompletion sense on this txt input
			 */
			self.autocomplete = function(propositionGenerator,options) {
				wigiiNcd.bindAutoCompletionSense(self,propositionGenerator,options);
				return self; 
			};
		};
		
		/**		
		 * NCD CheckBox
		 *@param wigiiNcd.HtmlEmitter htmlEmitter underlying open HTML emitter to which dump the checkbox component
		 */
		wigiiNcd.CheckBox = function(htmlEmitter, cssClass,index) {
			var self = this;
			self.className = 'CheckBox';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now()+(index!==undefined?index:'');
			
			self.context = {index:index};
							
			// Properties
			
			self.$ = function() {return $("#"+self.ctxKey);	};
			
			/**
			 * Checks the box or returns if it is checked
			 */
			self.checked = function(bool) {
				if(bool===undefined) return self.context.checked;
				else {
					self.context.checked = (bool==true);
					$("#"+self.ctxKey).prop('checked',bool);
					return self;
				}
			};			
			/**
			 * Registers a onClick event handler
			 */
			self.onClick = function(onClick) {
				if($.isFunction(onClick)) {					
					self.context.onClickSubscribers.push(onClick);
				}
				else if(onClick===undefined) {
					if(self.context.onClickSubscribers) {
						for(var i=0;i<self.context.onClickSubscribers.length;i++) {
							var eh = self.context.onClickSubscribers[i];
							if($.isFunction(eh)) eh(self,self.context.checked==true);
						}
					}
				}
				return self;
			};
			/**
			 * Toggles the value of the checkbox
			 */
			self.toggle = function() {self.checked(!self.checked()); return self;}
			
			// Html emission
			var htmlB = wigiiNcd.getHtmlBuilder();
			htmlB.putStartTag('input','type','checkbox','class',htmlEmitter.emittedClass()+(cssClass?' '+cssClass:''), "id", self.ctxKey);		
			htmlB.putEndTag('input');
			htmlEmitter.putHtml(htmlB.html());
			
			if(!self.context.onClickSubscribers) {
				self.context.onClickSubscribers = [];
				// registers onclick event handler on checkbox
				$("#"+self.ctxKey).click(function(){self.toggle();self.onClick();})
			}
		};
		
		/**
		 * A selectable, browseable, unordered list, implemented as a ul>li set.
		 *@param wncd.HtmlEmitter htmlEmitter an open HtmlEmitter in which to render the unordered list.
		 *@param Function itemGenerator a function which generates some items to add to the list. 
		 * Function signature is : itemGenerator(n,list): Any; where
		 * - n: Integer. The number of already generated items. First call is 0.
		 * - list: wncd.UnorderedList. A reference to the current list for stateful operations.
		 * returns any kind of object, which can then be rendered by the itemRendere function. 
		 * This function is called as long as it returns something. To stop generating data, return nothing.
		 *@param Function itemRenderer a function which generates some html code for the current list item.
		 * Function signature is : itemRenderer(i,item,list): String|Object; where
		 * - i: Integer. Current item index (1 to n).
		 * - item: Any. Current item in the list as generated previously.
		 * - list: wncd.UnorderedList. A reference to the current list for stateful operations.
		 * returns some valid HTML string to be inserted into the ul>li tag,
		 * or a map of key/values where key 'html' holds some HTML string to be inserted into the ul>li tag, 
		 * and other keys defining some HTML attributes to be added to the ul>li node (for example class, style, etc).
		 *@param Object options an optional bag of options to configure the UnorderedList. It supports the following set of attributes :
		 * - selectedClass: String. Name of class added when an item is selected in the list. Defaults to 'selected'
		 * - unselectedClass: String. Name of class added when an item is unselected. Defaults to no class.
		 * - maxSelection: Integer. Maximum number of items that can be selected into the list. Defaults to no maximum.
		 * - highlightedClass: String. Name of class added when an item is highlighted (using the iterator). Defaults to 'highlighted'
		 */
		wigiiNcd.UnorderedList = function(htmlEmitter, itemGenerator, itemRenderer, options) {
			var self = this;
			self.className = 'UnorderedList';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now();
			self.options = options || {};
			self.impl = {};
			self.context = {
				items:[],
				selection:{},
				selectedCount:0,
				currentItem:undefined
			};
			
			// Defines default options
			if(self.options.selectedClass===undefined) self.options.selectedClass = 'selected';
			if(self.options.highlightedClass===undefined) self.options.highlightedClass = 'highlighted';
			
			// Implementation
			
			self.$ = function(){return $('ul#'+self.ctxKey);}
			/**
			 *@return Integer returns the number of items in the list.
			 */
			self.size = function() {return self.context.items.length;}
			/**
			 *@return Array returns an array with all items in the list
			 */
			self.items = function() {return self.context.items;}
			/**
			 *@return Object returns a map with the currently selected items in the list. Key is item index (1 to size). Value is list item.
			 */
			self.selection = function() {
				var returnValue = {};
				for(var i in self.context.selection) {
					if(self.context.selection[i]) returnValue[i] = self.context.items[i-1];
				}			
				return returnValue;
			}
			/**
			 * Tests if an item is selected into the list or selects it or unselects it.
			 * If no parameters are given, then returns the number of selected items in the list.
			 *@param Integer i the index of the item in the list (1 to size)
			 *@param Boolean bool if true, then selects the item, if false then unselects the item, if undefined then returns selection state.
			 *@throws wncd.ServiceException.OUT_OF_BOUND if index is not contained in the list.
			 *@throws wncd.ServiceException.ASSERTION_FAILED if the maximum number of selected elements is overpassed. 
			 * If maxSelection is one, then automatically unselects previous element.
			 */
			self.selected = function(i,bool) {
				if(i===undefined && bool===undefined) return self.context.selectedCount;
				if(i < 1 || self.size() < i) throw wncd.createServiceException("index "+i+" is out of list range.",wncd.errorCodes.OUT_OF_BOUND);
				if(bool===undefined) return (self.context.selection[i]==true);
				else {
					// if maxSelection is > 1 and overpassed then ASSERTION_FAILED is thrown
					if(bool && self.options.maxSelection > 1 && self.context.selectedCount >= self.options.maxSelection)  {
						throw wncd.createServiceException("A maximum of "+self.options.maxSelection+" items can be selected.",wncd.errorCodes.ASSERTION_FAILED);
					}
					// else if maxSelection is 1, then first unselects automatically previous item.
					else if(bool && self.options.maxSelection == 1 && self.context.selectedCount == 1) {
						self.selected(Object.keys(self.selection())[0],false);
					}
					self.context.selection[i] = (bool==true);
					var li = self.$().find("> li:nth-child("+i+")");
					if(self.context.selection[i]) {
						if(self.options.unselectedClass) li.removeClass(self.options.unselectedClass);
						li.addClass(self.options.selectedClass);
						self.context.selectedCount++;
					}
					else {
						li.removeClass(self.options.selectedClass);
						if(self.context.unselectedClass) li.addClass(self.options.unselectedClass);
						self.context.selectedCount--;
					}
					return self;
				}
			};
			/**
			 * Toggles the selection of the nth item in the list
			 *@param Integer i the index of the item to toggle the selection in the list. (1 to size)
			 *@return wncd.UnorderedList for chaining
			 */
			self.toggle = function(i) {
				self.selected(i,!self.selected(i)); 
				return self;
			};
			/**
			 * Registers a click handler on an item in the list or simulates a click on an item.
			 *@param Function onClick click handler of the form onClick(i,item,selected,list) where
			 * - i: Integer. The index of the item in the list (1 to size)
			 * - item: Any. The item object in the list as created by the itemGenerator function
			 * - selected: Boolean. True if item is currently selected, else false.
			 * - list: wncd.UnorderedList. A reference to the list of stateful operations.
			 *@return wncd.UnorderedList for chaining
			 */
			self.onItemClick = function(onClick) {
				// registers click handler on li
				if($.isFunction(onClick)) self.options.onItemClick = onClick;
				// simulates a click on the li
				else self.impl.onItemClick(onClick);
				return self;
			};
			
			// Mutation
			
			/**
			 * Adds an item at the end of the list
			 *@param Any item a piece of data of same nature as the other ones in the list and that can be rendered using the itemRenderer function
			 *@return wncd.UnorderedList for chaining
			 */
			self.add = function(item) {
				var n = self.size();
				// if exists, then stores and renders it
				if(item!==undefined) {
					var htmlB = self.$().wncd('html').htmlBuilder();
					self.context.items.push(item);
					n++;
					self.impl.buildLi(n,item,htmlB);
					htmlB.emit();
					// binds click event
					self.$().find('> li:nth-child('+n+')').click(self.impl.onLiClick);
				}
				return self;
			};
			
			// Iteration
			
			/**
			 * Highlights an item in the list given its index or returns the currently highlighted item.
			 *@param Integer i the index of the item to highlight (1 to size)
			 *@param String outputOption defines the output option. A string one of :
			 * - 'index': returns the index of the highlighted item (1 to size)
			 * - 'item': returns the list item 
			 * - 'all': returns an object of the form 
			 * {index: the index of the highlighted item (1 to size),
			 *  item: the highlighted item,
			 *	selected:Boolean indicating if the item is currently selected
			 * }
			 * Defaults to 'item'.
			 *@return Any|Integer|Object the chosen output format according to the outputOption.
			 *@throws wncd.ServiceException.OUT_OF_BOUND if index is not contained in the list.
			 */
			self.highLight = function(i, outputOption) {
				// sets current item
				if(i!==undefined) {
					if(i < 1 || self.size() < i) throw wncd.createServiceException("index "+i+" is out of list range.",wncd.errorCodes.OUT_OF_BOUND);
					if(self.context.currentItem) self.$().find("> li:nth-child("+self.context.currentItem+")").removeClass(self.options.highlightedClass);
					self.context.currentItem = i;
				}
				if(self.context.currentItem) {
					// add highlighted class
					self.$().find("> li:nth-child("+self.context.currentItem+")").addClass(self.options.highlightedClass);
					// returns output based on options
					switch(outputOption) {
						case 'index': return self.context.currentItem;
						case 'all': return {
							index:self.context.currentItem,
							item:self.context.items[self.context.currentItem-1],
							selected:self.selected(self.context.currentItem)
						}
						case 'item':
						default: return self.context.items[self.context.currentItem-1]
					}			
				}
			};
			/**
			 * Clears any highlighted item in the list
			 *@return wncd.UnorderedList for chaining
			 */
			self.clearHighLight = function() {
				if(self.context.currentItem) {
					self.$().find("> li:nth-child("+self.context.currentItem+")").removeClass(self.options.highlightedClass);
					self.context.currentItem = undefined;
				}
				return self;
			};
			/**
			 * Highlights next item in the list and returns it
			 *@param Boolean wrap if wrap then wraps again to first element when reaching the end.
			 *@param String outputOption defines the output option. A string one of :
			 * - 'index': returns the index of the highlighted item (1 to size)
			 * - 'item': returns the list item 
			 * - 'all': returns an object of the form 
			 * {index: the index of the highlighted item (1 to size),
			 *  item: the highlighted item,
			 *	selected:Boolean indicating if the item is currently selected
			 * }
			 * Defaults to 'item'.
			 *@return Any|Integer|Object the chosen output format according to the outputOption or undefined if the end of the list has been reached.
			 */
			self.next = function(wrap,outputOption) {
				// removes previous highlight
				if(self.context.currentItem) self.$().find("> li:nth-child("+self.context.currentItem+")").removeClass(self.options.highlightedClass);
				// increments current item
				if(self.context.currentItem === undefined) self.context.currentItem = 1;
				else if(self.context.currentItem >= self.size()) {
					if(wrap) self.context.currentItem = 1;
					else self.context.currentItem = undefined;
				}
				else self.context.currentItem++;
				
				if(self.context.currentItem) {
					// add highlighted class
					self.$().find("> li:nth-child("+self.context.currentItem+")").addClass(self.options.highlightedClass);
					// returns output based on options
					switch(outputOption) {
						case 'index': return self.context.currentItem;
						case 'all': return {
							index:self.context.currentItem,
							item:self.context.items[self.context.currentItem-1],
							selected:self.selected(self.context.currentItem)
						}
						case 'item':
						default: return self.context.items[self.context.currentItem-1]
					}	
				}
			};
			/**
			 * Highlights previous item in the list and returns it
			 *@param Boolean wrap if wrap then wraps again to last element when reaching the beginning.
			 *@param String outputOption defines the output option. A string one of :
			 * - 'index': returns the index of the highlighted item (1 to size)
			 * - 'item': returns the list item 
			 * - 'all': returns an object of the form 
			 * {index: the index of the highlighted item (1 to size),
			 *  item: the highlighted item,
			 *	selected:Boolean indicating if the item is currently selected
			 * }
			 * Defaults to 'item'.
			 *@return Any|Integer|Object the chosen output format according to the outputOption or undefined if start of list has been reached.
			 */
			self.previous = function(wrap,outputOption) {
				// removes previous highlight
				if(self.context.currentItem) self.$().find("> li:nth-child("+self.context.currentItem+")").removeClass(self.options.highlightedClass);
				
				// decrements current item
				if(self.context.currentItem === undefined) self.context.currentItem = self.size();
				else if(self.context.currentItem <= 1) {
					if(wrap) self.context.currentItem = self.size();
					else self.context.currentItem = undefined;
				}
				else self.context.currentItem--;
				
				if(self.context.currentItem) {
					// add highlighted class
					self.$().find("> li:nth-child("+self.context.currentItem+")").addClass(self.options.highlightedClass);
					// returns output based on options
					switch(outputOption) {
						case 'index': return self.context.currentItem;
						case 'all': return {
							index:self.context.currentItem,
							item:self.context.items[self.context.currentItem-1],
							selected:self.selected(self.context.currentItem)
						}
						case 'item':
						default: return self.context.items[self.context.currentItem-1]
					}	
				}
			};
			
			// Html emission helper
			self.impl.buildLi = function(n,item,htmlB) {
				var itemHtml = itemRenderer(n,item,self);
				if(itemHtml) {
					// if an object, then initializes li attributes with given values
					if($.type(itemHtml)==='object') {
						var tagArgs = ['li','class',htmlEmitter.emittedClass()+(self.context.unselectedClass?' '+self.context.unselectedClass:'')+(itemHtml['class']?' '+itemHtml['class']:'')];
						var plainHtml = '';
						itemHtml['class'] = undefined;
						for(var key in itemHtml) {
							if(key=='html') plainHtml = itemHtml[key];
							else if(itemHtml[key]) {
								tagArgs.push(key);
								tagArgs.push(itemHtml[key]);
							}
						}
						htmlB.tag.apply(undefined,tagArgs).put(plainHtml).$tag('li');
					}
					// else if plain HTML string, then puts it out into li tag
					else htmlB.tag('li','class',htmlEmitter.emittedClass()+(self.context.unselectedClass?' '+self.context.unselectedClass:'')).put(itemHtml).$tag('li');
				}
			}	
			// Html emission
			var htmlB = htmlEmitter.htmlBuilder();
			htmlB.tag('ul','id',self.ctxKey,'class',htmlEmitter.emittedClass()+(options.cssClass?' '+options.cssClass:''));
			var shouldContinue=true;
			var n = 0;			
			while(shouldContinue) {
				// generates item
				var item = itemGenerator(n,self);
				// if exists, then stores and renders it
				if(item!==undefined) {
					self.context.items.push(item);
					n++;
					self.impl.buildLi(n,item,htmlB);
				}
				else shouldContinue = false;
			}
			htmlB.$tag('ul').emit();			
			
			// Registers click events
			self.impl.onItemClick = function(i) {
				self.toggle(i);
				if($.isFunction(self.options.onItemClick)) self.options.onItemClick(i,self.context.items[i-1],self.selected(i),self); 
			};
			self.impl.onLiClick = function(e){
				try {self.impl.onItemClick($(this).index()+1);}
				catch(exc) {
					// Ignores ASSERTION_FAILED if maxSelection is active
					if(!(self.options.maxSelection > 1 && exc.code == wncd.errorCodes.ASSERTION_FAILED)) htmlEmitter.publishException(exc);
				}
				e.stopPropagation();
			};
			self.$().find('> li').click(self.impl.onLiClick);
		};
		
		/**
		 * NCD Grid Turtle
		 *@param wigiiNcd.Grid grid the underlying Grid on which the turtle lives
		 *@param String headColor HTML color code for turtle head (defaults to red)
		 *@param String tailColor HTML color code for turtle tail (defaults to blue)
		 */
		wigiiNcd.GridTurtle = function(grid,headColor,tailColor) {
			var self = this;
			headColor = headColor || 'red';
			tailColor = tailColor || 'blue';
			if(!grid) throw wigiiNcd.createServiceException('grid cannot be null',wigiiNcd.errorCodes.INVALID_ARGUMENT);
			
			self.context = {};
			self.start = function(x,y,direction) {
				direction = direction || 'N';
				switch(direction) {
					case 'N':
					case 'S':
					case 'E':
					case 'W': 
						self.context.direction = direction; 
						break;
					default: throw wigiiNcd.createServiceException("invalid direction '"+direction+"'",wigiiNcd.errorCodes.INVALID_ARGUMENT);
				}
				if(grid.cell(x,y)) {
					self.context.x = x; self.context.y = y;
				}
				else throw wigiiNcd.createServiceException("invalid start coordinates ("+x+","+y+")",wigiiNcd.errorCodes.INVALID_ARGUMENT);
				self.show();
			};
			self.moveForward = function() {
				// Calculates next cell
				var nextC;
				switch(self.context.direction) {
					case 'N':
						nextC = grid.cell(self.context.x,self.context.y).up(true);
						break;
					case 'S':
						nextC = grid.cell(self.context.x,self.context.y).down(true);
						break;
					case 'E':
						nextC = grid.cell(self.context.x,self.context.y).right(true);
						break;
					case 'W':
						nextC = grid.cell(self.context.x,self.context.y).left(true);
						break;
					default: throw wigiiNcd.createServiceException("Not started. Call start function first.",wigiiNcd.errorCodes.INVALID_STATE);
				}
				// Updates context and paints
				self.context.x = nextC.x();
				self.context.y = nextC.y();
				self.show();
			};
			self.moveForwardAndLeft = function() {
				// Calculates next cell
				var nextC;
				switch(self.context.direction) {
					case 'N':
						nextC = grid.cell(self.context.x,self.context.y).up(true).left(true);
						break;
					case 'S':
						nextC = grid.cell(self.context.x,self.context.y).down(true).right(true);
						break;
					case 'E':
						nextC = grid.cell(self.context.x,self.context.y).right(true).up(true);
						break;
					case 'W':
						nextC = grid.cell(self.context.x,self.context.y).left(true).down(true);
						break;
					default: throw wigiiNcd.createServiceException("Not started. Call start function first.",wigiiNcd.errorCodes.INVALID_STATE);
				}
				// Updates context and paints
				self.context.x = nextC.x();
				self.context.y = nextC.y();
				self.show();
			};
			self.moveForwardAndRight = function() {
				// Calculates next cell
				var nextC;
				switch(self.context.direction) {
					case 'N':
						nextC = grid.cell(self.context.x,self.context.y).up(true).right(true);
						break;
					case 'S':
						nextC = grid.cell(self.context.x,self.context.y).down(true).left(true);
						break;
					case 'E':
						nextC = grid.cell(self.context.x,self.context.y).right(true).down(true);
						break;
					case 'W':
						nextC = grid.cell(self.context.x,self.context.y).left(true).up(true);
						break;
					default: throw wigiiNcd.createServiceException("Not started. Call start function first.",wigiiNcd.errorCodes.INVALID_STATE);
				}
				// Updates context and paints
				self.context.x = nextC.x();
				self.context.y = nextC.y();
				self.show();
			};
			self.turnBack = function() {
				// returns itself
				switch(self.context.direction) {
					case 'N':
						self.context.direction = 'S';
						break;
					case 'S':
						self.context.direction = 'N';
						break;
					case 'E':
						self.context.direction = 'W';
						break;
					case 'W':
						self.context.direction = 'E';
						break;
					default: throw wigiiNcd.createServiceException("Not started. Call start function first.",wigiiNcd.errorCodes.INVALID_STATE);
				}
				self.show();
			};
			self.turnLeft = function() {
				// turn left
				switch(self.context.direction) {
					case 'N':
						self.context.direction = 'W';
						break;
					case 'S':
						self.context.direction = 'E';
						break;
					case 'E':
						self.context.direction = 'N';
						break;
					case 'W':
						self.context.direction = 'S';
						break;
					default: throw wigiiNcd.createServiceException("Not started. Call start function first.",wigiiNcd.errorCodes.INVALID_STATE);
				}
				self.show();
			};
			self.turnRight = function() {
				// turn right
				switch(self.context.direction) {
					case 'N':
						self.context.direction = 'E';
						break;
					case 'S':
						self.context.direction = 'W';
						break;
					case 'E':
						self.context.direction = 'S';
						break;
					case 'W':
						self.context.direction = 'N';
						break;
					default: throw wigiiNcd.createServiceException("Not started. Call start function first.",wigiiNcd.errorCodes.INVALID_STATE);
				}
				self.show();
			};
			
			self.previous = {x:undefined,y:undefined,text:undefined,color:undefined};
			self.show = function() {
				// Restores previous cell text and color
				var c;
				if(self.previous.x !== undefined && self.previous.y !== undefined) {
					c = grid.cell(self.previous.x, self.previous.y);
					/*
					if(self.previous.text !== undefined) c.text(self.previous.text); else c.text(' ');
					if(self.previous.color !== undefined) c.color(self.previous.color); else c.color('white');
					*/
					c.text(' ').color(tailColor);
				}
				c = grid.cell(self.context.x,self.context.y);
				// Saves previous cell text and color
				self.previous.x = self.context.x;
				self.previous.y = self.context.y;
				self.previous.text = c.text();
				self.previous.color = c.color();
				// Displays head
				var headTxt = '';
				switch(self.context.direction) {
				case 'N': headTxt = '&#9650;'; break;
				case 'S': headTxt = '&#9660;'; break;
				case 'W': headTxt = '&#9664;'; break;
				case 'E': headTxt = '&#9654;'; break;
				}
				c.text(headTxt).color(headColor);
			};	
			self.grid = function(){return grid;};
			self.currentCell = function() {return grid.cell(self.context.x,self.context.y);};
			self.cell = function(x,y) {
				var e1x=0,e1y=0,e2x=0,e2y=0;
				switch(self.context.direction) {
					case 'N':
						e1x=-1;e1y=0;e2x=0;e2y=1;
						break;
					case 'S':
						e1x=1;e1y=0;e2x=0;e2y=-1;
						break;
					case 'E':
						e1x=0;e1y=1;e2x=1;e2y=0;
						break;
					case 'W':
						e1x=0;e1y=-1;e2x=-1;e2y=0;
						break;
					default: throw wigiiNcd.createServiceException("Not started. Call start function first.",wigiiNcd.errorCodes.INVALID_STATE);
				}
				var rx = (self.context.x+x*e1x+y*e1y)%grid.nRows();
				if(rx < 0) rx = grid.nRows()+rx;
				var ry = (self.context.y+x*e2x+y*e2y)%grid.nCols();
				if(ry < 0) ry = grid.nCols()+ry;
				return grid.cell(rx, ry);
			};
		};				
		
		// Wigii Sense Services 
		
		/**
		 * Wigii Selection Sense
		 * A stateful object which reacts on click and selects an HTML element.
		 *@param Function onClick callback function triggered on click. Function signature is onClick(selectionSense)
		 *@param Object options an optional set of options to parametrize the Wigii Selection Sense
		 */
		wigiiNcd.SelectionSense = function(onClick, options) {
			var self = this;
			self.className = 'SelectionSense';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now();
			
			// Implementation
			
			self.context = {
				selected: false,
				selectedClass: 'selected',
				unselectedClass: undefined				
			};
			if(options) {
				if(options.selectedClass) self.context.selectedClass = options.selectedClass;
				if(options.unselectedClass) self.context.unselectedClass = options.unselectedClass;
			}
			
			self.onClick = function() {
				self.toggle();
				if($.isFunction(onClick)) onClick(self);
			};
			self.anchors = [];
					
			// Properties
			
			/**
			 * Selects the element or returns if it is selected
			 */
			self.selected = function(bool) {
				if(bool===undefined) return self.context.selected;
				else {
					self.context.selected = (bool==true);
					for(var i=0;i<self.anchors.length;i++) {
						var anchor = self.anchors[i];
						if(self.context.selected) {
							if(self.context.unselectedClass) anchor.removeClass(self.context.unselectedClass);
							anchor.addClass(self.context.selectedClass);
						}
						else {
							anchor.removeClass(self.context.selectedClass);
							if(self.context.unselectedClass) anchor.addClass(self.context.unselectedClass);
						}
					}
					return self;
				}
			};			
			
			// Methods
			
			/**
			 * Toggles the value of the selection
			 */
			self.toggle = function() {
				self.selected(!self.selected()); 
				return self;
			};
			/**
			 * Binds the Selection Sense to a specific clickable anchor
			 */
			self.bind = function(anchor) {
				anchor.click(self.onClick);
				anchor.css("cursor", "pointer");
				self.anchors.push(anchor);
				if(self.context.selected) {
					if(self.context.unselectedClass) anchor.removeClass(self.context.unselectedClass);
					anchor.addClass(self.context.selectedClass);
				}
				else {
					anchor.removeClass(self.context.selectedClass);
					if(self.context.unselectedClass) anchor.addClass(self.context.unselectedClass);
				}
				return self;
			};
		};
		
		/**
		 * Wigii Counting Sense
		 * A stateful object which reacts on click and counts up or down.
		 *@param Function onClick callback function triggered on click. Function signature is onClick(countingSense)
		 *@param Object options an optional set of options to parametrize the Wigii Counting Sense
		 */
		wigiiNcd.CountingSense = function(onClick, options) {
			var self = this;
			self.className = 'CountingSense';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now();
			
			// Implementation
			
			self.context = {
				counter: 0		
			};
			
			self.onClick = function() {
				// Runs specific callback
				if($.isFunction(onClick)) onClick(self);
				// By default increments the counter
				else self.inc();
			};
			self.anchors = [];
			
			// Properties
			
			/**
			 * Returns the value of the counter since last reset
			 */
			self.count = function() {
				return self.context.counter;				
			};		

			// Methods
									
			/**
			 * Resets the counter. An optional defaultValue is possible.
			 */
			self.reset = function(defaultValue) {
				if(defaultValue!==undefined) self.context.counter = defaultValue;
				else self.context.counter = 0;
				return self;
			};
			
			/**
			 * Increments the counter. An optional increment value can be given.
			 */
			self.inc = function(val) {
				if(val !== undefined) self.context.counter += val;
				else self.context.counter += 1;
				return self;
			};
			
			/**
			 * Decrements the counter. An optional decrement value can be given.
			 */
			self.dec = function(val) {
				if(val !== undefined) self.context.counter -= val;
				else self.context.counter -= 1;
				return self;
			};	

			/**
			 * Binds the Couting Sense to a specific clickable anchor
			 */
			self.bind = function(anchor) {
				anchor.click(self.onClick);
				anchor.css("cursor", "pointer");
				self.anchors.push(anchor);				
				return self;
			};			
		};
		
		/**
		 * Wigii Autocompletion Sense
		 * A stateful object which manages autocompletion of text.
		 *@param Function|Array propositionGenerator a function which generates some propositions or an array of propositions.
		 *@param Object options an optional map of options. It supports the following set of attributes :
		 * - panel: HtmlEmitter|JQuery. The panel used by the AutocompletionSense to interact with the user
		 * - hideOnSelection: Boolean. If true, then the panel is hidden once a proposition is selected.
		 * - cssClass: String. Optional CSS class associated to this AutocompletionSense panel
		 * - matchClass: String. CSS class associated to matching pattern in list items. Default to 'match'.
		 * - matchTag: String. Default tag associated to matching pattern in list items. Default to 'strong'.
		 * - actionOnChosenProposition: String. One of 'replace','append','prepend'. Defaults to 'replace'. 
		 */
		wigiiNcd.AutocompletionSense = function(propositionGenerator,options) {
			var self = this;
			self.className = 'AutocompletionSense';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now();
			self.options = options || {};
			self.impl = {};
			self.context = {interactivePanelReady:false};
			
			// Define default options
			if($.isFunction(propositionGenerator)) self.impl.propositionGenerator = propositionGenerator;
			else if($.isArray(propositionGenerator)) {
				self.impl.propositionGenerator = function(n,txt,list) {
					if(list.context.visited===undefined) list.context.visited=0;
					// finds next matching in array
					while(list.context.visited<propositionGenerator.length) {					
						var returnValue = propositionGenerator[list.context.visited++];
						if(returnValue.toLowerCase().indexOf(txt.toLowerCase())>=0) return returnValue;
					}
				}
			}
			if(!self.options.matchClass) self.options.matchClass = 'match';
			if(!self.options.matchTag) self.options.matchTag = 'strong';
			if(!self.options.renderProposition) self.options.renderProposition = function(i,data,list) {
				if(!list.context.txtLowerCase) {
					list.context.txtLowerCase = list.context.txt.toLowerCase();
					list.context.txtLength = list.context.txt.length;
				}
				// looks for txt pattern into data and highlights it
				var txtPos = data.toLowerCase().indexOf(list.context.txtLowerCase);
				var returnValue = data;
				if(txtPos>=0) {
					returnValue = wncd.getHtmlBuilder().out(data.substr(0,txtPos))
					.tag(self.options.matchTag,"class",self.options.matchClass).out(data.substr(txtPos,list.context.txtLength)).$tag(self.options.matchTag)
					.out(data.substr(txtPos+list.context.txtLength)).html();
				}
				return returnValue;
			}
			if(!self.options.actionOnChosenProposition) self.options.actionOnChosenProposition = 'replace';
			if(!self.options.updateTextWithProposition) self.options.updateTextWithProposition = function(txt,proposition) {
				if(self.options.actionOnChosenProposition == 'replace') return proposition;
				else if(self.options.actionOnChosenProposition == 'append') return txt+proposition;
				else if(self.options.actionOnChosenProposition == 'prepend') return proposition+txt;
			}
			
			// Properties
			
			self.$ = function() {return $('#'+self.ctxKey);}
			
			// Methods
							
			/**
			 * Given an input text, displays some propositions to user and callbacks with one choice.
			 *@param String txt the text used as an input to propose some matching
			 *@param Function choiceCallback function receiving the chosen text
			 */
			self.chooseOne = function(txt,choiceCallback) {
				if(!self.context.interactivePanelReady) self.impl.initializeInteractivePanel();
				/* resets panel */
				self.options.panel.reset();
				/* builds a list of propositions */
				self.context.list = self.options.panel.list(
					function(n,list){list.context.txt = txt; return self.impl.propositionGenerator(n,txt,list);}, 
					self.options.renderProposition,
					{
						/* on selection of item, callbacks with updated text */
						onItemClick: function(i,data,selected){
							if(selected) {
								choiceCallback(self.options.updateTextWithProposition(txt,data));
								self.options.panel.reset();
								self.context.list = undefined;
								if(self.options.hideOnSelection) self.options.panel.$().hide();
							}
						},
						maxSelection:1
					}
				);
				// binds hover on list highLight
				self.context.list.$().find('> li').on('hover',function(){
					self.context.list.highLight($(this).index()+1);
				});
				if(self.options.hideOnSelection) self.options.panel.$().show();
				self.options.panel.$().scrollTop(0);
				return self;
			};		

			/**
			 * Binds the Autocompletion Sense to a specific TextArea or TextInput
			 */
			self.bind = function(txtInput) {
				if(!self.context.interactivePanelReady) self.impl.initializeInteractivePanel(txtInput);
				var txtInputSetText = function(txt){txtInput.text(txt);txtInput.$().change().focus();}
				// on text input, executes a chooseOne process			
				txtInput.onInput(function(txtInput,txt){
					self.chooseOne(txt, txtInputSetText);				
				});
			};		

			// Implementation
			
			/**
			 * Initializes the interactive panel linked to this AutocompletionSense
			 *@param TextInput|TextArea txtInput an optional TextInput or TextArea instance to which is bound this AutocompletionSense
			 */ 
			self.impl.initializeInteractivePanel = function(txtInput) {
				// if no panel is defined, then creates a div below the txtInput
				if(!self.options.panel) {
					if(self.options.hideOnSelection===undefined) self.options.hideOnSelection=true;
					var anchor = txtInput.$();
					// ensures container displays panel				
					anchor.parent().css('overflow','visible');					
					// builds autcompletionsense container and inserts html code after the anchor
					anchor.after(wigiiNcd.getHtmlBuilder().tag("div",
						"id",self.ctxKey,
						"class","autocompletionsense"+(self.options.cssClass?' '+self.options.cssClass:''),
						"style","position:relative;"+(self.options.hideOnSelection?'display:none;':'')
						).$tag("div").html()
					);					
					self.options.panel = wncd.html(self.$());
				}
				else if($.type(self.options.panel)==='string' || self.options.panel.className != 'HtmlEmitter') {
					self.options.panel = wncd.html(self.options.panel).div(self.ctxKey,"autocompletionsense"+(self.options.cssClass?' '+self.options.cssClass:''));
				}				
				/* Binds keyboard :
				 * - to hide the panel: ESC or Backspace if empty.
				 * - to select a value: Enter.
				 * - to scroll up and down: Arrow up and down
				 */
				txtInput.$().on('keydown',function(e){					
					if(e.key == 'Up' || e.key == 'ArrowUp') {
						if(self.context.list) {
							var i = self.context.list.previous(true,'index');							
							var p = self.options.panel.$();
							var li = self.context.list.$().find("> li:nth-child("+i+")");
							if(i == self.context.list.size()) p.scrollTop(li.offset().top);
							else if(li.offset().top < p.offset().top) p.scrollTop(Math.max(p.scrollTop() - li.height(),0));
						}
					}
					else if(e.key == 'Down' || e.key == 'ArrowDown') {
						if(self.context.list) {
							var i = self.context.list.next(true,'index');							
							var p = self.options.panel.$();
							if(i == 1) p.scrollTop(0);
							else {
								var li = self.context.list.$().find("> li:nth-child("+i+")");
								if(li.offset().top + li.height() > p.offset().top + p.height()) p.scrollTop(p.scrollTop() + li.height());
							}
						}
					}
				}).on('keypress',function(e){
					if(self.options.hideOnSelection && 
					(e.key == 'Esc' || e.key == 'Escape'
					 || !$(this).val() && e.key == 'Backspace')) {
						 self.options.panel.$().hide();
						 if(self.context.list) self.context.list.clearHighLight();
					 }
					else if(e.key == 'Enter') {
						var i = (self.context.list ? self.context.list.highLight(undefined,'index'):undefined);
						if(i) self.context.list.onItemClick(i);
						else if(self.options.hideOnSelection) self.options.panel.$().hide();
					}
				});				
				self.options.panel.out("ready");
				self.context.interactivePanelReady=true;
			};		
		};
		
		/**
		 * JQuery collection event handlers
		 */
		wigiiNcd.JQueryService = function() {
			var self = this;
			self.className = 'JQueryService';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className;	

			/**
			 * Returns an HtmlEmitter instance linked to the selected DOM element
			 */
			self.HtmlEmitter = function(selection,options) {
				var returnValue=undefined;
				// checks we have only one element
				if(selection && selection.length==1) {
					// gets a ready to use HtmlEmitter with all inherited modelers
					returnValue = wncd.html(selection);
				}
				else if(selection && selection.length>1) throw wigiiNcd.createServiceException('Wigii NCD HtmlEmitter selector can only be activated on a JQuery collection containing one element and not '+selection.length, wigiiNcd.errorCodes.INVALID_ARGUMENT);
				return (!returnValue?{$:selection}:returnValue);
			};
			/**
			 * Returns an HtmlEmitter instance linked to the selected DOM element. Alias of HtmlEmitter handler.
			 */
			self.html = self.HtmlEmitter;
		};
		
		// Service providing
		
		/**
		 * Creates a new StringStack instance
		 */
		wigiiNcd.createStringStackInstance = function() {
			return new wigiiNcd.StringStack();
		};
		
		/**
		 * Creates a new HtmlEmitter object attached to the given DOM element.
		 *@param jQuery|DOM.Element output the element in which to emit HTML code, defaults to body if not specified.
		 */
		wigiiNcd.getHtmlEmitter = function(output) {
			return new wigiiNcd.HtmlEmitter(output);
		};
		
		/**
		 * Creates an HtmlBuilder instance
		 *@param wigiiNcd.HtmlEmitter optional HtmlEmitter instance that can be linked to html builder in which to emit the constructed html code.
		 */
		wigiiNcd.getHtmlBuilder = function(htmlEmitter) {			
			return new wigiiNcd.HtmlBuilder(htmlEmitter);
		};
		
		/**
		 * Creates an unbound Selection Sense
		 */
		wigiiNcd.createSelectionSense = function(onClick, options) {
			return new wigiiNcd.SelectionSense(onClick, options);
		};
		/**
		 * Creates and binds a Selection Sense to a given anchor
		 */
		wigiiNcd.bindSelectionSense = function(anchor, onClick, options) {
			return wigiiNcd.createSelectionSense(onClick, options).bind(anchor);
		};
		/**
		 * Creates an unbound Counting Sense
		 */
		wigiiNcd.createCountingSense = function(onClick, options) {
			return new wigiiNcd.CountingSense(onClick, options);
		};
		/**
		 * Creates and binds a Counting Sense to a given anchor
		 */
		wigiiNcd.bindCountingSense = function(anchor, onClick, options) {
			return wigiiNcd.createCountingSense(onClick, options).bind(anchor);
		};
		/**
		 * Creates an unbound Autocompletion Sense
		 */
		wigiiNcd.createAutoCompletionSense = function(propositionGenerator,options) {
			return new wigiiNcd.AutocompletionSense(propositionGenerator,options);
		};
		/**
		 * Creates and binds an Autocompletion Sense to a given TxtInput or TxtArea
		 */
		wigiiNcd.bindAutoCompletionSense = function(txtInput,propositionGenerator,options) {
			return wigiiNcd.createAutoCompletionSense(propositionGenerator,options).bind(txtInput);
		};
		
		/**
		 * Returns a JQueryService instance
		 */
		wigiiNcd.getJQueryService = function() {
			if(!wigiiNcd['jQueryServiceInstance']) {
				wigiiNcd.jQueryServiceInstance = new wigiiNcd.JQueryService();ncdprivate('jQueryServiceInstance');
			}
			return wigiiNcd.jQueryServiceInstance;
		};			
		
		// Functions
		
		/**
		 * throws a ServiceException::NOT_IMPLEMENTED exception
		 */
		wigiiNcd.throwNotImplemented = function() {
			throw new wigiiNcd.ServiceException("not implemented", wigiiNcd.errorCodes.NOT_IMPLEMENTED);
		};
		/**
		 * throws a ServiceException 
		 */
		wigiiNcd.createServiceException = function(message,code,previous) {
			return new wigiiNcd.ServiceException(message, code, previous);
		};
		/**
		 * @return String Converts anything to a compatible Fx string
		 */
		wigiiNcd.obj2FxString = function(obj) {			
			var objType = $.type(obj);
			if(objType==="function") {
				if($.isFunction(obj.toFxString)) return obj.toFxString();
				else return obj.toString();
			}
			else if(objType==="number") return obj;
			else if(objType==="boolean") return obj.toString();
			else if(objType==="string") return '"'+obj.replace(/"/g,'\\"')+'"';
			else if(objType==="object") return "JSON.parse('"+JSON.stringify(obj)+"')";
			else if(obj===undefined) return '""';
			else return '"'+obj.toString()+'"';
		};
		/**
		 * @return Object Converts an Fx string back to its object representation
		 */
		wigiiNcd.fxString2obj = function(str) {				
			if(str) {
				// initializes scope
				var wigiiNcd = window.wigiiNcd;
				// evaluates string
				var returnValue = eval(str);
				return returnValue;
			}
		};
		/**
		 * Chains an update of object fields if value changed.
		 * Allows to copy some field values from obj2 to obj1 if values are different. Can set a value on obj1 if changes exist.
		 *@example Updates author and description in existingCode.info object if changes exist compared to srcCode.info object,
		 * plus if some changes exist, then sets the existingCode.info.modificationDate field to now.
		 * wigiiNcd.updateObjIfChanged(existingCode.info,srcCode.info).field('author').field('description').set('modificationDate',new Date());
		 *@return chainable object
		 */
		wigiiNcd.updateObjIfChanged = function(obj1,obj2) {
			if(!obj1 || !obj2) throw wigiiNcd.createServiceException("obj1 and obj2 cannot be null.",wigiiNcd.errorCodes.INVALID_ARGUMENT);
			var updateChain = {
				context:{
					obj1: obj1,
					obj2: obj2,
					changes:false
				}				
			};
			updateChain.field = function(fieldName) {
				if(updateChain.context.obj2[fieldName]!=updateChain.context.obj1[fieldName]) {
					updateChain.context.obj1[fieldName] = updateChain.context.obj2[fieldName];
					updateChain.context.changes = true;
				}
				return updateChain;
			};
			updateChain.set = function(fieldName,value,subFieldName) {
				if(updateChain.context.changes) {
					if(subFieldName!==undefined) updateChain.context.obj1[fieldName][subFieldName] = value;
					else updateChain.context.obj1[fieldName] = value;
				}
				return updateChain;
			};
			updateChain.hasChanges = function() {return updateChain.context.changes;};
			return updateChain;
		};
		/**
		 * Returns a string representing a date in a Wigii compatible format (Y-m-d H:i:s).
		 */
		wigiiNcd.txtDate = function(timestamp) {
			var d = ($.type(timestamp)== 'date'? timestamp: new Date(timestamp));
			var returnValue = '';
			var v = 0;
			// Year
			returnValue += d.getFullYear();
			returnValue += '-';
			// Month
			v = d.getMonth()+1;
			if(v<10) returnValue += '0'+v;
			else returnValue += v;
			returnValue += '-';
			// Day
			v = d.getDate();
			if(v<10) returnValue += '0'+v;
			else returnValue += v;
			returnValue += ' ';
			// Hour
			v = d.getHours();
			if(v<10) returnValue += '0'+v;
			else returnValue += v;
			returnValue += ':';
			// Minute
			v = d.getMinutes();
			if(v<10) returnValue += '0'+v;
			else returnValue += v;
			returnValue += ':';
			// Seconds
			v = d.getSeconds();
			if(v<10) returnValue += '0'+v;
			else returnValue += v;
			
			return returnValue;
		};
		/**
		 * Returns a string representing a date in a French style (d.m.Y H:i:s).
		 *@param Integer timestamp timestamp to convert to date string
		 *@param String options a formating option string. One of : 
		 * noSeconds: display date and time up to minutes, 
		 * noTime: displays only date without time, 
		 * noDate: displays only time without date.
		 */
		wigiiNcd.txtFrenchDate = function(timestamp, options) {			
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
		};
		
		/**
		 * Creates a new HtmlEmitter object attached to the given DOM element. Alias of getHtmlEmitter function.
		 *@param jQuery|DOM.Element output the element in which to emit HTML code, defaults to body if not specified.
		 */
		wigiiNcd.html = function(output) {
			return wigiiNcd.getHtmlEmitter(output);
		};
		
		/**
		 * @return String returns the Wigii NCD version number
		 */
		wigiiNcd.version = function() {return "2.0";};
	};	
	// Default WigiiNCD instance
	var wigiiNcdInstance = new WigiiNcd();
	// WigiiNCD Functional facade 
	var wigiiNcdFacade = function(selector,options) {
		var wigiiNcd = wigiiNcdInstance;
		return wigiiNcd;
	};
	// Starting up and exporting symbols
	if(!$.isFunction(window.wigiiNcd) || window.wigiiNcd().version() < wigiiNcdFacade().version()) {
		window.wigiiNcd = wigiiNcdFacade;
		
		// publish NCD library members to wncd
		if(wigiiNcdOptions.publishNcdToWncd===undefined) wigiiNcdOptions.publishNcdToWncd = function(wigiiNcd,wncd) {
			// publishes everything, except members considered as private			
			for(var member in wigiiNcd) {
				if(!wigiiNcdOptions.privateNcdMembers[member]) wncd[member] = wigiiNcd[member];
			}
		};
		window.wncd = {};
		if(wigiiNcdOptions.publishNcdToWncd) wigiiNcdOptions.publishNcdToWncd(wigiiNcdInstance,window.wncd);
	}
 })(window, jQuery);
 
 /**
 * Wigii NCD JQuery plugin
 * Created by Camille Weber (camille@wigii.org), 18.10.2017
 */
(function($) {
	/**
	 * JQuery Wigii NCD plugin
	 * @param String cmd selects a service or a command in the Wigii NCD library which accepts a jQuery collection
	 * @param Object options a map of configuration options to be passed to the called service.
	 * @return JQuery|Any returns the service or command result if defined, or the JQuery collection if no specific result.
	 */
	$.fn.wncd = function(cmd, options) {
		var wncd = wigiiNcd();		
		var returnValue = undefined;
		try {
			if(!cmd) throw wncd.createServiceException("cmd cannot be null",wncd.errorCodes.INVALID_ARGUMENT);			
			var jQueryHandler = wncd.getJQueryService()[cmd];
			if(!jQueryHandler || $.type(jQueryHandler) !== 'function') throw wncd.createServiceException("Wigii NCD JQueryService does not support the '"+cmd+"' command.",wncd.errorCodes.UNSUPPORTED_OPERATION);
			returnValue = jQueryHandler(this,options);
		}
		catch(e) {
			if(window.wigii) wigii().publishException(e);
			else throw e;
		}
		if(returnValue) return returnValue;
		else return this;		
	};
})(jQuery);

 /*
 *	Tabby jQuery plugin version 0.12
 *
 *	Ted Devito - http://teddevito.com/demos/textarea.html
 *
 *	Copyright (c) 2009 Ted Devito
 *	 
 *	Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following 
 *	conditions are met:
 *	
 *		1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 *		2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer  
 *			in the documentation and/or other materials provided with the distribution.
 *		3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written 
 *			permission. 
 *	 
 *	THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
 *	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE 
 *	LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, 
 *	PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY 
 *	THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT 
 *	OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
 
// create closure

(function($) {
 
	// plugin definition

	$.fn.tabby = function(options) {
		//debug(this);
		// build main options before element iteration
		var opts = $.extend({}, $.fn.tabby.defaults, options);
		var pressed = $.fn.tabby.pressed; 
		
		// iterate and reformat each matched element
		return this.each(function() {
			$this = $(this);
			
			// build element specific options
			var options = $.meta ? $.extend({}, opts, $this.data()) : opts;
			
			$this.bind('keydown',function (e) {
				var kc = $.fn.tabby.catch_kc(e);
				if (16 == kc) pressed.shft = true;
				/*
				because both CTRL+TAB and ALT+TAB default to an event (changing tab/window) that 
				will prevent js from capturing the keyup event, we'll set a timer on releasing them.
				*/
				if (17 == kc) {pressed.ctrl = true;	setTimeout("$.fn.tabby.pressed.ctrl = false;",1000);}
				if (18 == kc) {pressed.alt = true; 	setTimeout("$.fn.tabby.pressed.alt = false;",1000);}
					
				if (9 == kc && !pressed.ctrl && !pressed.alt) {
					e.preventDefault; // does not work in O9.63 ??
					pressed.last = kc;	setTimeout("$.fn.tabby.pressed.last = null;",0);
					process_keypress ($(e.target).get(0), pressed.shft, options);
					return false;
				}
				
			}).bind('keyup',function (e) {
				if (16 == $.fn.tabby.catch_kc(e)) pressed.shft = false;
			}).bind('blur',function (e) { // workaround for Opera -- http://www.webdeveloper.com/forum/showthread.php?p=806588
				if (9 == pressed.last) $(e.target).one('focus',function (e) {pressed.last = null;}).get(0).focus();
			});
		
		});
	};
	
	// define and expose any extra methods
	$.fn.tabby.catch_kc = function(e) { return e.keyCode ? e.keyCode : e.charCode ? e.charCode : e.which; };
	$.fn.tabby.pressed = {shft : false, ctrl : false, alt : false, last: null};
	
	// private function for debugging
	function debug($obj) {
		if (window.console && window.console.log)
		window.console.log('textarea count: ' + $obj.size());
	};

	function process_keypress (o,shft,options) {
		var scrollTo = o.scrollTop;
		//var tabString = String.fromCharCode(9);
		
		// gecko; o.setSelectionRange is only available when the text box has focus
		if (o.setSelectionRange) gecko_tab (o, shft, options);
		
		// ie; document.selection is always available
		else if (document.selection) ie_tab (o, shft, options);
		
		o.scrollTop = scrollTo;
	}
	
	// plugin defaults
	$.fn.tabby.defaults = {tabString : String.fromCharCode(9)};
	
	function gecko_tab (o, shft, options) {
		var ss = o.selectionStart;
		var es = o.selectionEnd;	
				
		// when there's no selection and we're just working with the caret, we'll add/remove the tabs at the caret, providing more control
		if(ss == es) {
			// SHIFT+TAB
			if (shft) {
				// check to the left of the caret first
				if ("\t" == o.value.substring(ss-options.tabString.length, ss)) {
					o.value = o.value.substring(0, ss-options.tabString.length) + o.value.substring(ss); // put it back together omitting one character to the left
					o.focus();
					o.setSelectionRange(ss - options.tabString.length, ss - options.tabString.length);
				} 
				// then check to the right of the caret
				else if ("\t" == o.value.substring(ss, ss + options.tabString.length)) {
					o.value = o.value.substring(0, ss) + o.value.substring(ss + options.tabString.length); // put it back together omitting one character to the right
					o.focus();
					o.setSelectionRange(ss,ss);
				}
			}
			// TAB
			else {			
				o.value = o.value.substring(0, ss) + options.tabString + o.value.substring(ss);
				o.focus();
	    		o.setSelectionRange(ss + options.tabString.length, ss + options.tabString.length);
			}
		} 
		// selections will always add/remove tabs from the start of the line
		else {
			// split the textarea up into lines and figure out which lines are included in the selection
			var lines = o.value.split("\n");
			var indices = new Array();
			var sl = 0; // start of the line
			var el = 0; // end of the line
			var sel = false;
			for (var i in lines) {
				el = sl + lines[i].length;
				indices.push({start: sl, end: el, selected: (sl <= ss && el > ss) || (el >= es && sl < es) || (sl > ss && el < es)});
				sl = el + 1;// for "\n"
			}
			
			// walk through the array of lines (indices) and add tabs where appropriate						
			var modifier = 0;
			for (var i in indices) {
				if (indices[i].selected) {
					var pos = indices[i].start + modifier; // adjust for tabs already inserted/removed
					// SHIFT+TAB
					if (shft && options.tabString == o.value.substring(pos,pos+options.tabString.length)) { // only SHIFT+TAB if there's a tab at the start of the line
						o.value = o.value.substring(0,pos) + o.value.substring(pos + options.tabString.length); // omit the tabstring to the right
						modifier -= options.tabString.length;
					}
					// TAB
					else if (!shft) {
						o.value = o.value.substring(0,pos) + options.tabString + o.value.substring(pos); // insert the tabstring
						modifier += options.tabString.length;
					}
				}
			}
			o.focus();
			var ns = ss + ((modifier > 0) ? options.tabString.length : (modifier < 0) ? -options.tabString.length : 0);
			var ne = es + modifier;
			o.setSelectionRange(ns,ne);
		}
	}
	
	function ie_tab (o, shft, options) {
		var range = document.selection.createRange();
		
		if (o == range.parentElement()) {
			// when there's no selection and we're just working with the caret, we'll add/remove the tabs at the caret, providing more control
			if ('' == range.text) {
				// SHIFT+TAB
				if (shft) {
					var bookmark = range.getBookmark();
					//first try to the left by moving opening up our empty range to the left
				    range.moveStart('character', -options.tabString.length);
				    if (options.tabString == range.text) {
				    	range.text = '';
				    } else {
				    	// if that didn't work then reset the range and try opening it to the right
				    	range.moveToBookmark(bookmark);
				    	range.moveEnd('character', options.tabString.length);
				    	if (options.tabString == range.text) 
				    		range.text = '';
				    }
				    // move the pointer to the start of them empty range and select it
				    range.collapse(true);
					range.select();
				}
				
				else {
					// very simple here. just insert the tab into the range and put the pointer at the end
					range.text = options.tabString; 
					range.collapse(false);
					range.select();
				}
			}
			// selections will always add/remove tabs from the start of the line
			else {
			
				var selection_text = range.text;
				var selection_len = selection_text.length;
				var selection_arr = selection_text.split("\r\n");
				
				var before_range = document.body.createTextRange();
				before_range.moveToElementText(o);
				before_range.setEndPoint("EndToStart", range);
				var before_text = before_range.text;
				var before_arr = before_text.split("\r\n");
				var before_len = before_text.length; // - before_arr.length + 1;
				
				var after_range = document.body.createTextRange();
				after_range.moveToElementText(o);
				after_range.setEndPoint("StartToEnd", range);
				var after_text = after_range.text; // we can accurately calculate distance to the end because we're not worried about MSIE trimming a \r\n
				
				var end_range = document.body.createTextRange();
				end_range.moveToElementText(o);
				end_range.setEndPoint("StartToEnd", before_range);
				var end_text = end_range.text; // we can accurately calculate distance to the end because we're not worried about MSIE trimming a \r\n
								
				var check_html = $(o).html();
				$("#r3").text(before_len + " + " + selection_len + " + " + after_text.length + " = " + check_html.length);				
				if((before_len + end_text.length) < check_html.length) {
					before_arr.push("");
					before_len += 2; // for the \r\n that was trimmed	
					if (shft && options.tabString == selection_arr[0].substring(0,options.tabString.length))
						selection_arr[0] = selection_arr[0].substring(options.tabString.length);
					else if (!shft) selection_arr[0] = options.tabString + selection_arr[0];	
				} else {
					if (shft && options.tabString == before_arr[before_arr.length-1].substring(0,options.tabString.length)) 
						before_arr[before_arr.length-1] = before_arr[before_arr.length-1].substring(options.tabString.length);
					else if (!shft) before_arr[before_arr.length-1] = options.tabString + before_arr[before_arr.length-1];
				}
				
				for (var i = 1; i < selection_arr.length; i++) {
					if (shft && options.tabString == selection_arr[i].substring(0,options.tabString.length))
						selection_arr[i] = selection_arr[i].substring(options.tabString.length);
					else if (!shft) selection_arr[i] = options.tabString + selection_arr[i];
				}
				
				if (1 == before_arr.length && 0 == before_len) {
					if (shft && options.tabString == selection_arr[0].substring(0,options.tabString.length))
						selection_arr[0] = selection_arr[0].substring(options.tabString.length);
					else if (!shft) selection_arr[0] = options.tabString + selection_arr[0];
				}

				if ((before_len + selection_len + after_text.length) < check_html.length) {
					selection_arr.push("");
					selection_len += 2; // for the \r\n that was trimmed
				}
				
				before_range.text = before_arr.join("\r\n");
				range.text = selection_arr.join("\r\n");
				
				var new_range = document.body.createTextRange();
				new_range.moveToElementText(o);
				
				if (0 < before_len)	new_range.setEndPoint("StartToEnd", before_range);
				else new_range.setEndPoint("StartToStart", before_range);
				new_range.setEndPoint("EndToEnd", range);
				
				new_range.select();
				
			} 
		}
	}

// end of closure
})(jQuery);

/**
 * jQuery MD5 hash algorithm function
 * 
 * 	<code>
 * 		Calculate the md5 hash of a String 
 * 		String $.md5 ( String str )
 * 	</code>
 * 
 * Calculates the MD5 hash of str using the Â» RSA Data Security, Inc. MD5 Message-Digest Algorithm, and returns that hash. 
 * MD5 (Message-Digest algorithm 5) is a widely-used cryptographic hash function with a 128-bit hash value. MD5 has been employed in a wide variety of security applications, and is also commonly used to check the integrity of data. The generated hash is also non-reversable. Data cannot be retrieved from the message digest, the digest uniquely identifies the data.
 * MD5 was developed by Professor Ronald L. Rivest in 1994. Its 128 bit (16 byte) message digest makes it a faster implementation than SHA-1.
 * This script is used to process a variable length message into a fixed-length output of 128 bits using the MD5 algorithm. It is fully compatible with UTF-8 encoding. It is very useful when u want to transfer encrypted passwords over the internet. If you plan using UTF-8 encoding in your project don't forget to set the page encoding to UTF-8 (Content-Type meta tag). 
 * This function orginally get from the WebToolkit and rewrite for using as the jQuery plugin.
 * 
 * Example
 * 	Code
 * 		<code>
 * 			$.md5("I'm Persian."); 
 * 		</code>
 * 	Result
 * 		<code>
 * 			"b8c901d0f02223f9761016cfff9d68df"
 * 		</code>
 * 
 * @alias Muhammad Hussein Fattahizadeh < muhammad [AT] semnanweb [DOT] com >
 * @link http://www.semnanweb.com/jquery-plugin/md5.html
 * @see http://www.webtoolkit.info/
 * @license http://www.gnu.org/licenses/gpl.html [GNU General Public License]
 * @param {jQuery} {md5:function(string))
 * @return string
 */
(function($){
	
	var rotateLeft = function(lValue, iShiftBits) {
		return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
	}
	
	var addUnsigned = function(lX, lY) {
		var lX4, lY4, lX8, lY8, lResult;
		lX8 = (lX & 0x80000000);
		lY8 = (lY & 0x80000000);
		lX4 = (lX & 0x40000000);
		lY4 = (lY & 0x40000000);
		lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
		if (lX4 & lY4) return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
		if (lX4 | lY4) {
			if (lResult & 0x40000000) return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
			else return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
		} else {
			return (lResult ^ lX8 ^ lY8);
		}
	}
	
	var F = function(x, y, z) {
		return (x & y) | ((~ x) & z);
	}
	
	var G = function(x, y, z) {
		return (x & z) | (y & (~ z));
	}
	
	var H = function(x, y, z) {
		return (x ^ y ^ z);
	}
	
	var I = function(x, y, z) {
		return (y ^ (x | (~ z)));
	}
	
	var FF = function(a, b, c, d, x, s, ac) {
		a = addUnsigned(a, addUnsigned(addUnsigned(F(b, c, d), x), ac));
		return addUnsigned(rotateLeft(a, s), b);
	};
	
	var GG = function(a, b, c, d, x, s, ac) {
		a = addUnsigned(a, addUnsigned(addUnsigned(G(b, c, d), x), ac));
		return addUnsigned(rotateLeft(a, s), b);
	};
	
	var HH = function(a, b, c, d, x, s, ac) {
		a = addUnsigned(a, addUnsigned(addUnsigned(H(b, c, d), x), ac));
		return addUnsigned(rotateLeft(a, s), b);
	};
	
	var II = function(a, b, c, d, x, s, ac) {
		a = addUnsigned(a, addUnsigned(addUnsigned(I(b, c, d), x), ac));
		return addUnsigned(rotateLeft(a, s), b);
	};
	
	var convertToWordArray = function(string) {
		var lWordCount;
		var lMessageLength = string.length;
		var lNumberOfWordsTempOne = lMessageLength + 8;
		var lNumberOfWordsTempTwo = (lNumberOfWordsTempOne - (lNumberOfWordsTempOne % 64)) / 64;
		var lNumberOfWords = (lNumberOfWordsTempTwo + 1) * 16;
		var lWordArray = Array(lNumberOfWords - 1);
		var lBytePosition = 0;
		var lByteCount = 0;
		while (lByteCount < lMessageLength) {
			lWordCount = (lByteCount - (lByteCount % 4)) / 4;
			lBytePosition = (lByteCount % 4) * 8;
			lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
			lByteCount++;
		}
		lWordCount = (lByteCount - (lByteCount % 4)) / 4;
		lBytePosition = (lByteCount % 4) * 8;
		lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
		lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
		lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
		return lWordArray;
	};
	
	var wordToHex = function(lValue) {
		var WordToHexValue = "", WordToHexValueTemp = "", lByte, lCount;
		for (lCount = 0; lCount <= 3; lCount++) {
			lByte = (lValue >>> (lCount * 8)) & 255;
			WordToHexValueTemp = "0" + lByte.toString(16);
			WordToHexValue = WordToHexValue + WordToHexValueTemp.substr(WordToHexValueTemp.length - 2, 2);
		}
		return WordToHexValue;
	};
	
	var uTF8Encode = function(string) {
		string = string.replace(/\x0d\x0a/g, "\x0a");
		var output = "";
		for (var n = 0; n < string.length; n++) {
			var c = string.charCodeAt(n);
			if (c < 128) {
				output += String.fromCharCode(c);
			} else if ((c > 127) && (c < 2048)) {
				output += String.fromCharCode((c >> 6) | 192);
				output += String.fromCharCode((c & 63) | 128);
			} else {
				output += String.fromCharCode((c >> 12) | 224);
				output += String.fromCharCode(((c >> 6) & 63) | 128);
				output += String.fromCharCode((c & 63) | 128);
			}
		}
		return output;
	};
	
	$.extend({
		md5: function(string) {
			var x = Array();
			var k, AA, BB, CC, DD, a, b, c, d;
			var S11=7, S12=12, S13=17, S14=22;
			var S21=5, S22=9 , S23=14, S24=20;
			var S31=4, S32=11, S33=16, S34=23;
			var S41=6, S42=10, S43=15, S44=21;
			string = uTF8Encode(string);
			x = convertToWordArray(string);
			a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
			for (k = 0; k < x.length; k += 16) {
				AA = a; BB = b; CC = c; DD = d;
				a = FF(a, b, c, d, x[k+0],  S11, 0xD76AA478);
				d = FF(d, a, b, c, x[k+1],  S12, 0xE8C7B756);
				c = FF(c, d, a, b, x[k+2],  S13, 0x242070DB);
				b = FF(b, c, d, a, x[k+3],  S14, 0xC1BDCEEE);
				a = FF(a, b, c, d, x[k+4],  S11, 0xF57C0FAF);
				d = FF(d, a, b, c, x[k+5],  S12, 0x4787C62A);
				c = FF(c, d, a, b, x[k+6],  S13, 0xA8304613);
				b = FF(b, c, d, a, x[k+7],  S14, 0xFD469501);
				a = FF(a, b, c, d, x[k+8],  S11, 0x698098D8);
				d = FF(d, a, b, c, x[k+9],  S12, 0x8B44F7AF);
				c = FF(c, d, a, b, x[k+10], S13, 0xFFFF5BB1);
				b = FF(b, c, d, a, x[k+11], S14, 0x895CD7BE);
				a = FF(a, b, c, d, x[k+12], S11, 0x6B901122);
				d = FF(d, a, b, c, x[k+13], S12, 0xFD987193);
				c = FF(c, d, a, b, x[k+14], S13, 0xA679438E);
				b = FF(b, c, d, a, x[k+15], S14, 0x49B40821);
				a = GG(a, b, c, d, x[k+1],  S21, 0xF61E2562);
				d = GG(d, a, b, c, x[k+6],  S22, 0xC040B340);
				c = GG(c, d, a, b, x[k+11], S23, 0x265E5A51);
				b = GG(b, c, d, a, x[k+0],  S24, 0xE9B6C7AA);
				a = GG(a, b, c, d, x[k+5],  S21, 0xD62F105D);
				d = GG(d, a, b, c, x[k+10], S22, 0x2441453);
				c = GG(c, d, a, b, x[k+15], S23, 0xD8A1E681);
				b = GG(b, c, d, a, x[k+4],  S24, 0xE7D3FBC8);
				a = GG(a, b, c, d, x[k+9],  S21, 0x21E1CDE6);
				d = GG(d, a, b, c, x[k+14], S22, 0xC33707D6);
				c = GG(c, d, a, b, x[k+3],  S23, 0xF4D50D87);
				b = GG(b, c, d, a, x[k+8],  S24, 0x455A14ED);
				a = GG(a, b, c, d, x[k+13], S21, 0xA9E3E905);
				d = GG(d, a, b, c, x[k+2],  S22, 0xFCEFA3F8);
				c = GG(c, d, a, b, x[k+7],  S23, 0x676F02D9);
				b = GG(b, c, d, a, x[k+12], S24, 0x8D2A4C8A);
				a = HH(a, b, c, d, x[k+5],  S31, 0xFFFA3942);
				d = HH(d, a, b, c, x[k+8],  S32, 0x8771F681);
				c = HH(c, d, a, b, x[k+11], S33, 0x6D9D6122);
				b = HH(b, c, d, a, x[k+14], S34, 0xFDE5380C);
				a = HH(a, b, c, d, x[k+1],  S31, 0xA4BEEA44);
				d = HH(d, a, b, c, x[k+4],  S32, 0x4BDECFA9);
				c = HH(c, d, a, b, x[k+7],  S33, 0xF6BB4B60);
				b = HH(b, c, d, a, x[k+10], S34, 0xBEBFBC70);
				a = HH(a, b, c, d, x[k+13], S31, 0x289B7EC6);
				d = HH(d, a, b, c, x[k+0],  S32, 0xEAA127FA);
				c = HH(c, d, a, b, x[k+3],  S33, 0xD4EF3085);
				b = HH(b, c, d, a, x[k+6],  S34, 0x4881D05);
				a = HH(a, b, c, d, x[k+9],  S31, 0xD9D4D039);
				d = HH(d, a, b, c, x[k+12], S32, 0xE6DB99E5);
				c = HH(c, d, a, b, x[k+15], S33, 0x1FA27CF8);
				b = HH(b, c, d, a, x[k+2],  S34, 0xC4AC5665);
				a = II(a, b, c, d, x[k+0],  S41, 0xF4292244);
				d = II(d, a, b, c, x[k+7],  S42, 0x432AFF97);
				c = II(c, d, a, b, x[k+14], S43, 0xAB9423A7);
				b = II(b, c, d, a, x[k+5],  S44, 0xFC93A039);
				a = II(a, b, c, d, x[k+12], S41, 0x655B59C3);
				d = II(d, a, b, c, x[k+3],  S42, 0x8F0CCC92);
				c = II(c, d, a, b, x[k+10], S43, 0xFFEFF47D);
				b = II(b, c, d, a, x[k+1],  S44, 0x85845DD1);
				a = II(a, b, c, d, x[k+8],  S41, 0x6FA87E4F);
				d = II(d, a, b, c, x[k+15], S42, 0xFE2CE6E0);
				c = II(c, d, a, b, x[k+6],  S43, 0xA3014314);
				b = II(b, c, d, a, x[k+13], S44, 0x4E0811A1);
				a = II(a, b, c, d, x[k+4],  S41, 0xF7537E82);
				d = II(d, a, b, c, x[k+11], S42, 0xBD3AF235);
				c = II(c, d, a, b, x[k+2],  S43, 0x2AD7D2BB);
				b = II(b, c, d, a, x[k+9],  S44, 0xEB86D391);
				a = addUnsigned(a, AA);
				b = addUnsigned(b, BB);
				c = addUnsigned(c, CC);
				d = addUnsigned(d, DD);
			}
			var tempValue = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);
			return tempValue.toLowerCase();
		}
	});
})(jQuery);
 
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
 *  @copyright  Copyright (c) 2016-2017  Wigii.org
 *  @author     <http://www.wigii.org/system/libs>      Wigii.org 
 *  @link       <http://www.wigii-system.net>           <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>          GNU General Public License
 */

/**
 * Wigii NCD Language for ETPs : 
 * Expand, Translate, Program - Encode, Transmit, Power.
 * This language can be naturally used within the etp-start.html for your own creations.
 * Created by Wigii.org (camille@wigii.org), 11.02.2017
 * Modified by Wigii.org (camille@wigii.org and lionel@wigii.org), 10.05.2017
 * Updated version 2.0 by Camille Weber (camille@wigii.org), 27.10.2017
 */ 
(function (window, $, wigiiNcd){ 
	// Configuration options
	var wigiiNcdEtpOptions = undefined;
	if(window.wigiiNcdEtp && window.wigiiNcdEtp.options) wigiiNcdEtpOptions = window.wigiiNcdEtp.options;
	if(!wigiiNcdEtpOptions) wigiiNcdEtpOptions = {};
	// Private members management
	if(!wigiiNcdEtpOptions.privateNcdEtpMembers) wigiiNcdEtpOptions.privateNcdEtpMembers = {};
	/**
	 * Marks a member as private. A private member is not published into the wncd symbol.
	 *@param String memberName the name of the variable or function to be marked as private
	 */
	var ncdprivate = function(memberName) {
		wigiiNcdEtpOptions.privateNcdEtpMembers[memberName] = true;
	};


	// Wigii NCD ETP

	/**
	 * Language holder
	 */
	var wigiiNcdEtp = {};
	wigiiNcdEtp.instantiationTime = Date.now();ncdprivate('instantiationTime');
	wigiiNcdEtp.ctxKey = 'WigiiNcdEtp_'+wigiiNcdEtp.instantiationTime;ncdprivate('ctxKey');
	wigiiNcdEtp.version = function() {return "2.0";};
	
	// Execution environment

	if(!wigiiNcdEtpOptions.programOutput) wigiiNcdEtpOptions.programOutput = "#programOutput";
	
	/**
	 * Main HTML Emitter
	 */
	var html = wigiiNcd().getHtmlEmitter(wigiiNcdEtpOptions.programOutput);
	wigiiNcdEtp.html = html;ncdprivate('html');
	
	/**
	 * Main Program
	 */
	var programme = function(f) {
		html.clearErrors();
		try {f();html.end();}
		catch(exc) {html.publishException(exc);}
	};
	
	/**
	 * ETP Debugger
	 */
	wigiiNcdEtp.debug = {};
	wigiiNcdEtp.debug.initialize = function() {
		if($("#programWatcherContainer").length==0) {
			html.$().css('float','left').css('width','71%').after('<div id="programWatcherContainer"><div id="programWatcher"></div></div><div style="clear:both;"></div>');
		}
		$("#programWatcherContainer").show();
		return wigiiNcdEtp.debug;
	};
	wigiiNcdEtp.debug.$ = function() {return $("#programWatcherContainer");};
	
	/**
	 * Server logical proxy
	 */
	var serveur = {
		stockerDonnee : function(elementId, keyField, objet){
			if(objet===undefined) wigii().getRiseNcd().storeData(elementId, undefined, keyField);
			else wigii().getRiseNcd().storeData(elementId, keyField, objet);
		},
		obtenirDonnee : function(elementId){
			return wigii().getRiseNcd().getData(elementId);
		},
		creerEspaceStockage : function(groupId, description){
			return wigii().getRiseNcd().createDataStorage(groupId, description);
		},
		sysUsername : function() {
			return wigii().getRiseNcd().sysUsername();
		},
		rise : function() {
			return wigii().getRiseNcd();
		}
	};

	// NCD basic programming language modeler
	html.addModeler(function(html) {
		// Implementation
		html.impl.out = function(str,cssClass){html.startSpan(cssClass);html.put(str);html.endSpan();return html;}; 
		html.impl.h1 = function(str,cssClass){html.startH1(cssClass);html.put(str);html.endH1(); return html;}; 
		html.impl.p = function(cssClass) {html.startP(cssClass); return html;};
		html.impl.$p = function() {html.endP(); return html;};
		html.impl.b = function(str){html.startSpan('b');html.put(str);html.endSpan(); return html;};
		html.impl.i = function(str){html.startSpan('i');html.put(str);html.endSpan(); return html;}; 
		html.impl.u = function(str){html.startSpan('u');html.put(str);html.endSpan(); return html;}; 
		html.impl.a = function(url){html.put(wigiiNcd().getHtmlBuilder().putStartTag('a','href',url,'target','_blank','class',html.emittedClass()).put(url).putEndTag('a').html());return html;};
		html.impl.color = function(c,backgroundC,cssClass){html.startColor(c,backgroundC, cssClass);return html;};
		html.impl.$color = function(){html.endColor(); return html;};
		html.impl.bouton = function(label, onClick, cssClass){html.putButton(label, onClick, cssClass); return html;};
		html.impl.input = function(cssClass){return html.createTextArea(cssClass);};
		html.impl.display = function(backgroundC,textC,cssClass) {return html.createTextInput(cssClass).color(backgroundC,textC);};
		html.impl.insert = function(f) {
			html.htmlBuilder().insert(f).emit();
			return html;
		};
		// English translation
		html.impl.button = html.impl.bouton;
		
		// Exposes interface
		html.out = html.impl.out; 
		html.h1 = html.impl.h1; 
		html.p = html.impl.p;
		html.$p = html.impl.$p;
		html.b = html.impl.b;
		html.i = html.impl.i; 
		html.u = html.impl.u; 
		html.a = html.impl.a;
		html.color = html.impl.color;
		html.$color = html.impl.$color;
		html.bouton = html.impl.bouton;
		html.input = html.impl.input;
		html.display = html.impl.display;
		html.insert = html.impl.insert;
		// English symbols
		html.button = html.impl.button;
	});
	
	// HTML Grid modeler
	html.addModeler(function(html) {
		// Context
		html.context.gridInstance = undefined;
		// Implementation
		html.impl.grille = function(nLignes,nCols) {
			if(nLignes===undefined && nCols===undefined) return html.context.gridInstance;
			if(html.context.gridInstance) $("#"+html.context.gridInstance.ctxKey).remove();
			html.context.gridInstance = html.createGrid(nLignes,nCols);
			// Models grid instance
			html.context.gridInstance.cellule = html.impl.grille.cellule;
			html.context.gridInstance.couleur = html.impl.grille.couleur;
			html.context.gridInstance.texte = html.impl.grille.texte;
			html.context.gridInstance.onclick = html.impl.grille.onclick;
			html.context.gridInstance.nLignes = html.context.gridInstance.nRows;
			html.context.gridInstance.nColonnes = html.context.gridInstance.nCols;
			// Models a cell for ETP
			html.context.gridInstance.initializeCellForEtp = function(gridCell) {
				if(!gridCell.context.etpReady) {
					gridCell.texte = gridCell.text;
					gridCell.couleur = gridCell.color;
					gridCell.gauche = function(){return html.context.gridInstance.initializeCellForEtp(gridCell.left(true));};
					gridCell.droite = function(){return html.context.gridInstance.initializeCellForEtp(gridCell.right(true));};
					gridCell.bas = function(){return html.context.gridInstance.initializeCellForEtp(gridCell.down(true));};
					gridCell.haut = function(){return html.context.gridInstance.initializeCellForEtp(gridCell.up(true));};
					gridCell.bouton = function(label,onClick) {$("#"+gridCell.id()).addClass('button');return gridCell.text(label).click(onClick);};
					gridCell.cellule = function(ligne,colonne) {return html.impl.grille.cellule(ligne,colonne);};
					var offsetImpl = gridCell.offset;
					gridCell.offset = function(ligne,colonne){return html.context.gridInstance.initializeCellForEtp(offsetImpl(ligne,colonne));};
					gridCell.espace = {};
					gridCell.context.etpReady = true;
				}
				return gridCell;
			}
			return html.context.gridInstance; 
		}; 
		html.impl.grille.cellule = function(ligne,colonne) {return html.context.gridInstance.initializeCellForEtp(html.context.gridInstance.cell(ligne-1,colonne-1));};
		html.impl.grille.couleur = function(ligne,colonne,couleur) {
			if(couleur===undefined) return html.context.gridInstance.cell(ligne-1,colonne-1).color();
			else {
				html.context.gridInstance.cell(ligne-1,colonne-1).color(couleur);
				return html.context.gridInstance;
			};
		}; 
		html.impl.grille.texte = function(ligne,colonne,texte) {
			if(texte===undefined) return html.context.gridInstance.cell(ligne-1,colonne-1).text();
			else {
				html.context.gridInstance.cell(ligne-1,colonne-1).text(texte);
				return html.context.gridInstance;
			}
		};
		html.impl.grille.onclick = function(f) {
			for(var gi = 0; gi < html.context.gridInstance.nRows(); gi++) {
				for(var gj = 0; gj < html.context.gridInstance.nCols(); gj++) {
					html.context.gridInstance.cell(gi,gj).click(function(cell){
						if(wigiiNcdEtp.programme.context) wigiiNcdEtp.programme.context.html(html);
						f(cell.x()+1,cell.y()+1);
					});
				}
			}
			return html.context.gridInstance;
		}; 	
		html.impl.grille.nLignes = function() {return html.context.gridInstance.nLignes();};
		html.impl.grille.nColonnes = function() {return html.context.gridInstance.nColonnes();};
		html.impl.grille.nRows = function() {return html.context.gridInstance.nRows();};
		html.impl.grille.nCols = function() {return html.context.gridInstance.nCols();};
		
		// Exposes interface
		html.grille = html.impl.grille;		
	});


	// Panier
	var panier = {
		impl:{ 
			collection: {},
			instance: function(index) {
				var self = this;
				self.index = index;
				self.list = [];
				// Méthodes
				self.lire = function(index) {
					if(index-1 < 0 || index-1 >= self.list.length) throw wigiiNcd().createServiceException("L'index "+index+" est invalide, il doit être compris entre 1 et "+self.list.length,wigiiNcd().errorCodes.INVALID_ARGUMENT);
					return self.list[index-1];
				};
				self.lirePremier = function() {return self.lire(1);};
				self.lireDernier = function() {return self.lire(self.taille());}
				self.remplacer = function(index,objet) {
					if(index-1 < 0 || index-1 >= self.list.length) throw wigiiNcd().createServiceException("L'index "+index+" est invalide, il doit être compris entre 1 et "+self.list.length,wigiiNcd().errorCodes.INVALID_ARGUMENT);
					self.list[index-1] = objet;
					return self;
				};
				self.ajouter = function(objet) { self.list.push(objet); return self;};
				self.sortirDernier = function() { return self.list.pop(); };
				self.taille = function() { return self.list.length; };
				self.montrer = function() {
					wigiiNcdEtp.debug.initialize().$().find("#programWatcher").html("<u>"+self.index+"</u> "+"Taille: "+self.taille()+"<br/>"+JSON.stringify(self.list));
					//wigii().log("Panier: "+self.index+" Taille: "+self.taille()+"\n"+JSON.stringify(self.list));
				};
				self.exporter = function() { return self.list; };
				self.importer = function(data) {
					if(data===undefined || data===null) return self;
					if($.isArray(data)) self.list = data;
					else self.list.push(data);
					return self;
				};
				self.vider = function() {
					self.list = [];
					return self;
				};
				self.trier = function(fonctionTri) {
					self.list.sort(fonctionTri);
					return self;
				};
				
				// English translation
				self.read = self.lire;
				self.readFirst = self.lirePremier;
				self.readLast = self.lireDernier;
				self.replace = self.remplacer;
				self.add = self.ajouter;
				self.removeLast = self.sortirDernier;
				self.size = self.taille;
				self.show = self.montrer;
				self.exportContent = self.exporter;
				self.importContent = self.importer;
				self.empty = self.vider;
				self.sort = self.trier;
			}
		},
		// Selecteur
		no: function(index) {
			var returnValue = panier.impl.collection['P_'+index];
			if(!returnValue) {
				returnValue = new (panier.impl.instance)(index);
				panier.impl.collection['P_'+index] = returnValue;
			}
			return returnValue;
		},
		reset: function() {panier.impl.collection = {};},
		// Méthodes
		lire : function(index) {return panier.no('default').lire(index);},
		lirePremier : function() {return panier.no('default').lirePremier();},
		lireDernier : function() {return panier.no('default').lireDernier();},
		remplacer : function(index,objet) {return panier.no('default').remplacer(index,objet);},
		ajouter : function(objet) {return panier.no('default').ajouter(objet);},
		sortirDernier : function() {return panier.no('default').sortirDernier();},
		taille : function() {return panier.no('default').taille();},
		montrer : function() {return panier.no('default').montrer();},
		exporter : function() {return panier.no('default').exporter();},
		importer : function(data) {return panier.no('default').importer(data);},
		vider : function() {return panier.no('default').vider();},
		trier : function(fonctionTri) {return panier.no('default').trier(fonctionTri);}
	};	
	
	
	// Formulaire
	var formulaire = {
		impl:{
			collection: {},
			instance: function(index) {
				var self = this;
				self.index = index;
				// Inner state
				self.context = {currentFieldName:undefined};
				// Champ
				self.fields = {};		
				self.Field = function(formulaire, nom, label, couleurTexte, couleurFond, type, options) {
					var self = this;
					// Inner state
					self.context = {};
					self.context.formulaire = formulaire;
					self.context.nom = nom;
					self.context.id = 'formulaire_'+formulaire.index+'__'+nom;
					self.context.display = undefined;
					self.context.label = label;
					self.context.changed = false;
					
					// Propriétés
					self.id = function() {return self.context.id;};
					self.$ = function() {return $('#'+self.context.id);};
					self.nom = function() {return self.context.nom;};					
					self.input = function() {return self.context.display;};					
					
					// Méthodes
					self.valeur = function(v) {
						if(v===undefined) return self.context.display.text();
						else {
							self.context.display.text(v);
							self.context.changed=false;
							return self;
						}
					};					
					self.label = function(v) {
						if(v===undefined) return self.context.label;
						else {
							self.context.label = v;
							$('#'+self.context.id+' div.etp-label span:first-child').html(v);
							return self;
						}
					};
					self.label.couleur = function(couleurTexte) {
						if(couleurTexte===undefined) return $('#'+self.context.id+' div.etp-label span:first-child').css('color');
						else {
							$('#'+self.context.id+' div.etp-label span:first-child').css('color',couleurTexte);
							return self;
						}
					};
					self.couleur = function(couleurFond) {
						if(couleurFond===undefined) return $('#'+self.context.id).css('background-color');
						else {
							$('#'+self.context.id).css('background-color',couleurFond);
							return self;
						}				
					};
					self.vider = function() {
						self.context.display.text('');
						return self;
					};
					self.focus = function() {
						$('#'+self.context.display.ctxKey).focus();
						self.context.formulaire.context.currentFieldName = self.context.nom;
						return self;
					};
					self.disabled = function(bool) {
						if(bool===undefined) return $('#'+self.context.display.ctxKey).prop('disabled');
						else {
							$('#'+self.context.display.ctxKey).prop('disabled',bool);
							return self;
						}
					};
					self.readonly = function(bool) {
						if(bool===undefined) return $('#'+self.context.display.ctxKey).prop('readonly');
						else {
							$('#'+self.context.display.ctxKey).prop('readonly',bool);
							return self;
						}
					};
					self.changed = function(bool) {
						if(bool===undefined) return self.context.changed;
						else {
							self.context.changed = bool;
							return self;
						}
					};					
					// Chaînage
					self.champ = function(nom) {return self.context.formulaire.champ(nom);};
					
					// English translation
					self.name = self.nom;
					self.value = self.valeur;
					self.label.color = self.label.couleur;
					self.color = self.couleur;
					self.empty = self.vider;
					self.field = self.champ;					
					
					// HTML rendering
					wigiiNcdEtp.programme.currentDiv().putHtml(wigiiNcd().getHtmlBuilder()
						.putStartTag('div','id',self.context.id,'class','etp-field '+html.emittedClass())
						.putStartTag('div','class','etp-label '+html.emittedClass())
						.putStartTag('span','class',html.emittedClass())
						.put(self.context.label)
						.putEndTag('span')
						.putEndTag('div')
						.putStartTag('div','class','etp-value '+html.emittedClass())
						.putEndTag('div')
						.putEndTag('div')
						.html()
					);
					
					// Creates display according to type. Defaults to TextInput
					if(type) {
						switch(type) {
							case "TextArea":
								 self.context.display = wigiiNcd().getHtmlEmitter('#'+self.context.id+' div.etp-value').createTextArea().color(couleurFond, couleurTexte);
								 break;
							case "PasswordInput":
								 self.context.display = wigiiNcd().getHtmlEmitter('#'+self.context.id+' div.etp-value').createPasswordInput().color(couleurFond, couleurTexte);
								 break;
							case "Empty":
								self.context.display = undefined;
								break;
							default: throw wigiiNcd().createServiceException("Le type de champ '"+type+"' n'est pas supporté.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
						}
					}
					else self.context.display = wigiiNcd().getHtmlEmitter('#'+self.context.id+' div.etp-value').createTextInput().color(couleurFond, couleurTexte);
					if(self.context.display) {
						self.context.display.id = function() {return self.context.display.ctxKey;};
						self.context.display.$ = function() {return $('#'+self.context.display.ctxKey);};
						self.context.display.couleur = self.context.display.color;
						
						// Behavior: 
						// - when typing, saves text
						// - on click, changes current form field to this one
						self.context.display.onInput(function(d,txt){d.context.text=txt;self.context.changed = true;});
						self.context.display.$().click(function(){self.context.formulaire.context.currentFieldName=self.context.nom;});
					}
					return self;
				}; 
				// Méthodes
				self.creerChamp = function(nom, label, couleurTexte, couleurFond) {
					var field = self.fields[nom];
					if(field) throw wigiiNcd().createServiceException("Le champ "+nom+" existe déjà dans le formulaire, il ne peut pas être créé une deuxième fois. Choisissez un autre nom.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
					self.fields[nom] = new self.Field(self, nom, label, couleurTexte, couleurFond);
					return self;
				};
				self.creerChampTexte = function(nom, label, couleurTexte, couleurFond) {
					var field = self.fields[nom];
					if(field) throw wigiiNcd().createServiceException("Le champ "+nom+" existe déjà dans le formulaire, il ne peut pas être créé une deuxième fois. Choisissez un autre nom.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
					self.fields[nom] = new self.Field(self, nom, label, couleurTexte, couleurFond,"TextArea");
					return self;
				};
				self.creerChampPassword = function(nom, label, couleurTexte, couleurFond) {
					var field = self.fields[nom];
					if(field) throw wigiiNcd().createServiceException("Le champ "+nom+" existe déjà dans le formulaire, il ne peut pas être créé une deuxième fois. Choisissez un autre nom.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
					self.fields[nom] = new self.Field(self, nom, label, couleurTexte, couleurFond,"PasswordInput");
					return self;
				};
				self.creerChampCustom = function(nom, label, valueRenderer, couleurTexte, couleurFond) {
					var field = self.fields[nom];
					if(field) throw wigiiNcd().createServiceException("Le champ "+nom+" existe déjà dans le formulaire, il ne peut pas être créé une deuxième fois. Choisissez un autre nom.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
					// creates an empty field
					self.fields[nom] = new self.Field(self, nom, label, couleurTexte, couleurFond,"Empty");
					// extracts valueRenderer additional arguments
					var args;
					if(arguments.length > 5) args = Array.prototype.slice.call(arguments,5);
					else args = [];
					// runs the valueRenderer into the context of the field value div					
					var valueEmitter = self.fields[nom].$().find('div.etp-value').wncd('html');
					if($.isFunction(valueRenderer)) {
						var currentDiv = wncd.currentDiv();
						wncd.programme.context.html(valueEmitter);
						var valueHtml = valueRenderer.apply(null,args);
						if(valueHtml!==undefined) valueEmitter.putHtml(valueHtml);
						wncd.programme.context.html(currentDiv);
					}
					else valueEmitter.putHtml(valueRenderer);
					return self;
				};
				self.champ = function(nom) {
					var field = self.fields[nom];
					if(!field) throw wigiiNcd().createServiceException("Le champ "+nom+" n'existe pas dans le formulaire.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
					return field;
				};
				self.champExiste = function(nom) {
					var field = self.fields[nom];
					return (field?true:false);
				};
				self.champCourant = function(nom) {
					if(nom!==undefined) self.context.currentFieldName = nom;
					if(!self.context.currentFieldName) throw wigiiNcd().createServiceException("Aucun champ n'est actuellement sélectionné dans le formulaire. Cliquez sur un champ.",wigiiNcd().errorCodes.INVALID_STATE);
					return self.champ(self.context.currentFieldName);
				};
				self.vider = function() {
					for(var fieldName in self.fields) {
						self.fields[fieldName].vider();
					}
					return self;
				};
				self.supprimer = function() {formulaire.remove(self.index);};

				// English translation
				self.createField = self.creerChamp;
				self.createTextField = self.creerChampTexte;
				self.createPasswordField = self.creerChampPassword;
				self.createCustomField = self.creerChampCustom;
				self.field = self.champ;
				self.fieldExist = self.champExiste;
				self.currentField = self.champCourant;
				self.empty = self.vider;
				self.remove = self.supprimer;
				self.reset = function() {return formulaire.reset(self.index);};
			}
		},
		// Selecteur
		no: function(index) {
			var returnValue = formulaire.impl.collection['F_'+index];
			if(!returnValue) {
				returnValue = new (formulaire.impl.instance)(index);
				formulaire.impl.collection['F_'+index] = returnValue;
			}
			return returnValue;
		},
		remove: function(index) {
			var returnValue = formulaire.impl.collection['F_'+index];
			if(returnValue) {
				// removes from collection
				formulaire.impl.collection['F_'+index] = null;
				// deletes every field
				for(fieldName in returnValue.fields) {					
					var field = returnValue.fields[fieldName];
					$("#"+field.id()).remove();
					field.context.formulaire = undefined;
					field.context.display = undefined;
					field.context = undefined;
				}
			}
		},
		// Méthodes
		creerChamp : function(nom, label, couleurTexte, couleurFond) {return formulaire.no('default').creerChamp(nom, label, couleurTexte, couleurFond);},
		creerChampTexte : function(nom, label, couleurTexte, couleurFond) {return formulaire.no('default').creerChampTexte(nom, label, couleurTexte, couleurFond);},
		creerChampPassword : function(nom, label, couleurTexte, couleurFond) {return formulaire.no('default').creerChampPassword(nom, label, couleurTexte, couleurFond);},
		creerChampCustom : function(nom, label, valueRenderer, couleurTexte, couleurFond) {return formulaire.no('default').creerChampCustom(nom, label, valueRenderer, couleurTexte, couleurFond);},
		champ : function(nom) {return formulaire.no('default').champ(nom);},
		champExiste : function(nom) {return formulaire.no('default').champExiste(nom);},
		champCourant : function(nom) {return formulaire.no('default').champCourant(nom);},
		vider : function() {return formulaire.no('default').vider();},
		supprimer : function() {return formulaire.no('default').supprimer();},
		reset : function(index) {
			if(index===undefined) index = 'default';
			formulaire.no(index).supprimer();
			return formulaire.no(index);
		}
	};
	
	// Publish language
	wigiiNcdEtp.programme = programme;
	wigiiNcdEtp.out = html.out;
	wigiiNcdEtp.h1 = html.h1;
	wigiiNcdEtp.p = html.p;
	wigiiNcdEtp.$p = html.$p;
	wigiiNcdEtp.b = html.b;
	wigiiNcdEtp.i = html.i;
	wigiiNcdEtp.u = html.u;
	wigiiNcdEtp.a = html.a;
	wigiiNcdEtp.color = html.color; 
	wigiiNcdEtp.$color = html.$color;
	wigiiNcdEtp.bouton = html.bouton;
	wigiiNcdEtp.input = html.input;
	wigiiNcdEtp.display = html.display;
	wigiiNcdEtp.grille = html.grille;
	wigiiNcdEtp.panier = panier; 
	wigiiNcdEtp.formulaire = formulaire;
	wigiiNcdEtp.serveur = serveur; 

	// English translation
	wigiiNcdEtp.program = wigiiNcdEtp.programme; 
	wigiiNcdEtp.button = wigiiNcdEtp.bouton; 
	wigiiNcdEtp.grid = wigiiNcdEtp.grille; 

	wigiiNcdEtp.basket = wigiiNcdEtp.panier;
	wigiiNcdEtp.basket.read = wigiiNcdEtp.panier.lire;
	wigiiNcdEtp.basket.readFirst = wigiiNcdEtp.panier.lirePremier;
	wigiiNcdEtp.basket.readLast =wigiiNcdEtp.panier.lireDernier;
	wigiiNcdEtp.basket.replace = wigiiNcdEtp.panier.remplacer;
	wigiiNcdEtp.basket.add = wigiiNcdEtp.panier.ajouter; 
	wigiiNcdEtp.basket.removeLast = wigiiNcdEtp.panier.sortirDernier;
	wigiiNcdEtp.basket.size = wigiiNcdEtp.panier.taille;
	wigiiNcdEtp.basket.show = wigiiNcdEtp.panier.montrer;
	wigiiNcdEtp.basket.exportContent = wigiiNcdEtp.panier.exporter;
	wigiiNcdEtp.basket.importContent = wigiiNcdEtp.panier.importer;
	wigiiNcdEtp.basket.empty = wigiiNcdEtp.panier.vider;
	wigiiNcdEtp.basket.sort = wigiiNcdEtp.panier.trier;
	
	wigiiNcdEtp.form = wigiiNcdEtp.formulaire;
	wigiiNcdEtp.form.createField = wigiiNcdEtp.formulaire.creerChamp;
	wigiiNcdEtp.form.createTextField = wigiiNcdEtp.formulaire.creerChampTexte;
	wigiiNcdEtp.form.createPasswordField = wigiiNcdEtp.formulaire.creerChampPassword;
	wigiiNcdEtp.form.createCustomField = wigiiNcdEtp.formulaire.creerChampCustom;
	wigiiNcdEtp.form.field = wigiiNcdEtp.formulaire.champ;
	wigiiNcdEtp.form.currentField = wigiiNcdEtp.formulaire.champCourant;
	wigiiNcdEtp.form.empty = wigiiNcdEtp.formulaire.vider;
	//remove is already defined. Do not translate again. wigiiNcdEtp.form.remove = wigiiNcdEtp.formulaire.supprimer;
	
	wigiiNcdEtp.server = wigiiNcdEtp.serveur;
	wigiiNcdEtp.server.getData = wigiiNcdEtp.serveur.obtenirDonnee;
	wigiiNcdEtp.server.storeData = wigiiNcdEtp.serveur.stockerDonnee;
	
	// Starting up and exporting symbols
	if(!window.wigiiNcdEtp || !window.wigiiNcdEtp.version || window.wigiiNcdEtp.version() < wigiiNcdEtp.version()) {
		window.wigiiNcdEtp = wigiiNcdEtp;
		
		// publish NCD ETP language symbols to wncd
		if(wigiiNcdEtpOptions.publishNcdEtpToWncd===undefined) wigiiNcdEtpOptions.publishNcdEtpToWncd = function(wigiiNcdEtp,wncd) {
			// publishes everything, except members considered as private			
			for(var member in wigiiNcdEtp) {
				if(!wigiiNcdEtpOptions.privateNcdEtpMembers[member]) wncd[member] = wigiiNcdEtp[member];
			}
			// creates shortcuts
			wncd.html = wigiiNcdEtp.html.clone;
		};
		if(!window.wncd) window.wncd = {};
		if(wigiiNcdEtpOptions.publishNcdEtpToWncd) wigiiNcdEtpOptions.publishNcdEtpToWncd(wigiiNcdEtp,window.wncd);
		
		// Publish symbols to window if flag is not explicitely set to false
		if(wigiiNcdEtpOptions.publishNcdEtpToWindow===undefined) wigiiNcdEtpOptions.publishNcdEtpToWindow = function(wigiiNcdEtp,window) {
			// publishes everything, except members considered as private			
			for(var member in wigiiNcdEtp) {
				if(!wigiiNcdEtpOptions.privateNcdEtpMembers[member]) window[member] = wigiiNcdEtp[member];
			}
		};
		if(wigiiNcdEtpOptions.publishNcdEtpToWindow) wigiiNcdEtpOptions.publishNcdEtpToWindow(wigiiNcdEtp,window);		
	}
	// Ready callback
	if(wigiiNcdEtpOptions.ncdEtpReady===undefined) wigiiNcdEtpOptions.ncdEtpReady = function(wigiiNcdEtp) {
		var footer = $("#footer");
		if(footer.length>0) footer.append('<span><i>&nbsp;Wigii NCD core v.'+wigiiNcdEtp.version()+' loaded</i></span>');
	}
	if(wigiiNcdEtpOptions.ncdEtpReady) wigiiNcdEtpOptions.ncdEtpReady(wigiiNcdEtp);
	// keeps options for Fx layer loading 
	wigiiNcdEtp.options = wigiiNcdEtpOptions;ncdprivate('options');
 })(window, jQuery, wigiiNcd); 
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
 *  @copyright  Copyright (c) 2016-2017  Wigii.org
 *  @author     <http://www.wigii.org/system/libs>      Wigii.org 
 *  @link       <http://www.wigii-system.net>           <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>          GNU General Public License
 */

/**
 * Wigii NCD Fx Layer for ETPs : 
 * Expand, Translate, Program - Encode, Transmit, Power.
 * This language can be naturally used within the etp-start.html for your own creations.
 * Created by Wigii.org (camille@wigii.org), 11.02.2017
 * Updated version 2.0 by Camille Weber (camille@wigii.org), 27.10.2017
 */ 
(function (window, $, wigiiNcd, wigiiNcdEtp){ 
	// Configuration options
	var wigiiNcdEtpOptions = wigiiNcdEtp.options;
	/**
	 * Marks a member as private. A private member is not published into the wncd symbol.
	 *@param String memberName the name of the variable or function to be marked as private
	 */
	var ncdprivate = function(memberName) {
		wigiiNcdEtpOptions.privateNcdEtpMembers[memberName] = true;
	};


	// Wigii NCD ETP FX 
	
	
	// Execution environment

	/**
	 * Main HTML Emitter
	 */
	var html = wigiiNcdEtp.html;
	
	/**
	 * Main Program as a list of Func Exp to execute
	 */
	var programme = function() {
		var args = Array.prototype.slice.call(arguments);
		var newFxCtx = createFxContext();
		var returnValue = undefined;
		newFxCtx.pause = function() {
			if(!newFxCtx.isPaused) {
				newFxCtx.isPaused = true;
			}
		};
		newFxCtx.resume = function(retVal) {
			returnValue = retVal;
			if(newFxCtx.isPaused) {
				newFxCtx.isPaused = false;
				newFxCtx.argsI++;
				try {
					while(newFxCtx.argsI<args.length) {
						if($.isFunction(args[newFxCtx.argsI])) {
							returnValue = args[newFxCtx.argsI](newFxCtx);
							if($.isFunction(returnValue)) returnValue = returnValue(newFxCtx);
						}
						else throw wigiiNcd().createServiceException('programme instruction number '+newFxCtx.argsI+' is not a function.',wigiiNcd().errorCodes.INVALID_ARGUMENT);
						if(newFxCtx.isPaused) return;
						newFxCtx.argsI++;
					}
					html.end();
				}
				catch(exc) {html.publishException(exc);}
			}
			/* returnValue is ignored */
		};
		// runs the program
		try {
			html.clearErrors();
			while(newFxCtx.argsI<args.length) {
				if($.isFunction(args[newFxCtx.argsI])) {
					returnValue = args[newFxCtx.argsI](newFxCtx);
					if($.isFunction(returnValue)) returnValue = returnValue(newFxCtx);
				}
				else throw wigiiNcd().createServiceException('programme instruction number '+newFxCtx.argsI+' is not a function.',wigiiNcd().errorCodes.INVALID_ARGUMENT);
				if(newFxCtx.isPaused) return;
				newFxCtx.argsI++;
			}
			/* returnValue is ignored */
			html.end();
		}
		catch(exc) {html.publishException(exc);}
	};
	
	/**
	 * Creates a JavaScript function ready to invoke a list of FuncExp
	 *@return Function call the javascript function to invoke the list of FuncExp
	 */
	var sousProgramme = function() {
		var args = Array.prototype.slice.call(arguments);
		var returnValue = function() {programme.apply(null,args);};
		returnValue.toFxString = function() {
			var fxs = 'sousProgramme(';
			for(var i=0;i<args.length;i++) {
				if(i>0) fxs += ',';
				fxs += wigiiNcd().obj2FxString(args[i]);			
			}
			fxs += ')';
			return fxs;
		};
		return returnValue;
	};
	
	
	// FuncExp infrastructure

	/**
	 * Creates a new Fx Context object
	 */
	var createFxContext = function() {
		var fxCtx = {argsI:0, isPaused:false, impl:{}};
		fxCtx.html = function(html) {
			if(html===undefined) {
				return fxCtx.impl.html || wigiiNcdEtp.html;
			}
			else {
				fxCtx.impl.html = html;
				return fxCtx;
			}
		}
		fxCtx.html(programme.currentDiv());
		return fxCtx;
	};
	
	/**
	 * Runs a normal JavaScript function in the context of a Func Exp
	 */
	var fxRunF = function(f,fxCtx,args) {
		programme.context.fxCtx = fxCtx;
		var returnValue = f.apply(null,args);
		programme.context.fxCtx = undefined;
		return returnValue;
	};
	
	/**
	 * Converts a javascript function call to a FuncExp
	 *@return Function a FuncExp ready to be invoked
	 */
	var fx = function(f) {
		var args;
		if(arguments.length > 1) args = Array.prototype.slice.call(arguments,1);
		else args = [];
		return function(fxCtx){
			// evaluates args
			var evaluatedArgs = [];
			for(var i=0;i<args.length;i++) {
				if($.isFunction(args[i])) evaluatedArgs[i] = args[i](fxCtx);
				else evaluatedArgs[i] = args[i];
			}
			// calls wrapped function
			return fxRunF(f,fxCtx,evaluatedArgs)
		};
	};
	
	/**
	 * Converts a javascript function call to a serializable FuncExp
	 *@param String symbol the FuncExp name
	 *@return a FuncExp ready to be invoked or serialized
	 */
	var fx_s = function(symbol,f) {
		var args;
		if(arguments.length > 2) args = Array.prototype.slice.call(arguments,2);
		else args = [];
		var returnValue = function(fxCtx){
			// evaluates args
			var evaluatedArgs = [];
			for(var i=0;i<args.length;i++) {
				if($.isFunction(args[i])) evaluatedArgs[i] = args[i](fxCtx);
				else evaluatedArgs[i] = args[i];
			}
			// calls wrapped function
			return fxRunF(f,fxCtx,evaluatedArgs)
		};
		returnValue.toFxString = function() {
			var fxs = symbol+'(';
			for(var i=0;i<args.length;i++) {
				if(i>0) fxs += ',';
				fxs += wigiiNcd().obj2FxString(args[i]);		
			}
			fxs += ')';
			return fxs;
		};
		return returnValue;
	};
	
	/**
	 * Converts a javascript function call to a serializable FuncExp for which implementation is dynamically chosen.
	 *@param String symbol the FuncExp name
	 *@param Function implChooser a function which dynamically decides which implementation to run based on the Func Exp context.
	 *@example dynImpl_fx_s("out", function(fxCtx){ return fxCtx.html().out; }, str); invokes the out function on the contextual html emitter.
	 *@return a FuncExp ready to be invoked or serialized
	 */
	var dynImpl_fx_s = function(symbol,implChooser) {
		var args;
		if(arguments.length > 2) args = Array.prototype.slice.call(arguments,2);
		else args = [];
		var returnValue = function(fxCtx){
			// evaluates args
			var evaluatedArgs = [];
			for(var i=0;i<args.length;i++) {
				if($.isFunction(args[i])) evaluatedArgs[i] = args[i](fxCtx);
				else evaluatedArgs[i] = args[i];
			}
			// chooses function implementation to be invoked
			var f = implChooser(fxCtx);
			// calls wrapped function
			return fxRunF(f,fxCtx,evaluatedArgs)
		};
		returnValue.toFxString = function() {
			var fxs = symbol+'(';
			for(var i=0;i<args.length;i++) {
				if(i>0) fxs += ',';
				fxs += wigiiNcd().obj2FxString(args[i]);		
			}
			fxs += ')';
			return fxs;
		};
		return returnValue;
	};
	
	/**
	 * Builds a FuncExp as a sequence of FuncExp
	 *@return Function a FuncExp ready to be invoked
	 */
	var ctlSeq = function() {
		var args = [];
		var newFxCtx = createFxContext();
		var seqBuilder = {
			addFx : function(fx) {args.push(fx);},
			toFx : function() {
				return function(fxCtx) {
					newFxCtx.html(fxCtx.html());
					var returnValue = undefined;
					newFxCtx.pause = function() {
						if(!newFxCtx.isPaused) {
							newFxCtx.isPaused = true;
							if(fxCtx) fxCtx.pause();
						}
					};
					newFxCtx.resume = function(retVal) {
						returnValue = retVal;
						if(newFxCtx.isPaused) {
							newFxCtx.isPaused = false;
							newFxCtx.argsI++;
							/* finishes to execute Functional Expression sequence once */
							while(newFxCtx.argsI<args.length) {
								if($.isFunction(args[newFxCtx.argsI])) {
									returnValue = args[newFxCtx.argsI](newFxCtx);
									if($.isFunction(returnValue)) returnValue = returnValue(newFxCtx);
								}
								else returnValue = args[newFxCtx.argsI];
								if(newFxCtx.isPaused) return;
								newFxCtx.argsI++;
							}
							/* enable to replay same Functional Expression */
							newFxCtx.argsI = 0;
							// resumes calling stack and returns last value
							if(fxCtx) returnValue = fxCtx.resume(returnValue);
						}
						return returnValue;
					};
					/* executes Functional Expression sequence once */
					while(newFxCtx.argsI<args.length) {
						if($.isFunction(args[newFxCtx.argsI])) {
							returnValue = args[newFxCtx.argsI](newFxCtx);
							if($.isFunction(returnValue)) returnValue = returnValue(newFxCtx);
						}
						else returnValue = args[newFxCtx.argsI];
						if(newFxCtx.isPaused) return;
						newFxCtx.argsI++;
					}
					/* enable to replay same Functional Expression */
					newFxCtx.argsI = 0;
					// returns
					return returnValue;
				};
			}
		};
		if(arguments.length>0) {
			args = Array.prototype.slice.call(arguments);
			var returnValue = seqBuilder.toFx();
			returnValue.toFxString = function() {
				var fxs = 'ctlSeq(';
				for(var i=0;i<args.length;i++) {
					if(i>0) fxs += ',';
					fxs += wigiiNcd().obj2FxString(args[i]);		
				}
				fxs += ')';
				return fxs;
			};
			return returnValue;
		}
		else return seqBuilder;
	};
	
	/**
	 * Builds a FuncExp which executes a script in JavaScript
	 *@return Function a FuncExp ready to be invoked
	 */
	var scripte = function(f) { 
		var returnValue = function(fxCtx){
			return fxRunF(f,fxCtx,undefined);
		};
		returnValue.toFxString = function() {
			var fxs = 'scripte(';
			fxs += wigiiNcd().obj2FxString(f);		
			fxs += ')';
			return fxs;
		};
		return returnValue;
	};
	
	/**
	 * Generates a sequence of FuncExp using a span function and a generator
	 *@param Integer|Function span number of generations or a control function which, given a step i=1..n and a context, returns true to continue generation, false to stop. Function signature is span(i,context): Boolean
	 *@param Function generator a function which generates some FuncExp given a step i=1..n and a context. Function signature is generator(i,context): FuncExp
	 *@param Object context stateful context used by generator for his work
	 */
	var ctlGen = function(span, generator, context) {
		var newFxCtx = createFxContext();
		newFxCtx.stepI=1;
		newFxCtx.isSpanFunction = $.isFunction(span);
		if(!$.isFunction(generator)) throw wigiiNcd().createServiceException("generator should be a function.",wigiiNcd().errorCodes.INVALID_ARGUMENT);		
		var returnValue = function(fxCtx) {		
			newFxCtx.html(fxCtx.html());
			var returnValue = undefined;
			newFxCtx.pause = function() {
				if(!newFxCtx.isPaused) {
					newFxCtx.isPaused = true;
					if(fxCtx) fxCtx.pause();
				}
			};
			newFxCtx.resume = function(retVal) {
				returnValue = retVal;
				if(newFxCtx.isPaused) {
					newFxCtx.isPaused = false;
					newFxCtx.stepI++;
					/* finishes to build and execute Functional Expression */
					var cont = (newFxCtx.isSpanFunction ? span(newFxCtx.stepI,context): newFxCtx.stepI<=span);
					while(cont) {
						var genFx = generator(newFxCtx.stepI,context);
						if($.isFunction(genFx)) returnValue = genFx(newFxCtx);
						else returnValue = genFx;
						if(newFxCtx.isPaused) return;
						newFxCtx.stepI++;
						cont = (newFxCtx.isSpanFunction ? span(newFxCtx.stepI,context): newFxCtx.stepI<=span);
					}
					/* enable to replay same Functional Expression */
					newFxCtx.stepI = 1;
					// resumes calling stack and returns last value
					if(fxCtx) returnValue = fxCtx.resume(returnValue);
				}
				return returnValue;
			};
			/* builds and executes Functional Expression once */
			var cont = (newFxCtx.isSpanFunction ? span(newFxCtx.stepI,context): newFxCtx.stepI<=span);				
			while(cont) {			
				var genFx = generator(newFxCtx.stepI,context);			
				if($.isFunction(genFx)) returnValue = genFx(newFxCtx);
				else returnValue = genFx;
				if(newFxCtx.isPaused) return;
				newFxCtx.stepI++;
				cont = (newFxCtx.isSpanFunction ? span(newFxCtx.stepI,context): newFxCtx.stepI<=span);
			}
			/* enable to replay same Functional Expression */
			newFxCtx.stepI = 1;
			// returns
			return returnValue;
		};	
		
		returnValue.toFxString = function() {
			var fxs = 'ctlGen(';
			fxs += wigiiNcd().obj2FxString(span);
			fxs += ',';
			fxs += wigiiNcd().obj2FxString(generator);
			fxs += ',';
			if(context===undefined) fxs += 'undefined';			
			else fxs += wigiiNcd().obj2FxString(context);
			fxs += ')';
			return fxs;
		};
		return returnValue;
	};
	
	// Source code and NCD components

	/* 
	 Code object model
	 
	 Source code is a javascript object with the following structure :
	 {
		 label: String. A label to be used in the invocation button
		 program: sousProgramme Fx. An Fx main program
		 info: Object. Source code meta information object
	 }
	 
	 Source code meta information object has the following structure
	 {
		 id: String. Unique ID of source code component.
		 catalog: String. Catalog ID in which the source code is published.
		 author: String. Name of author which has written the code
		 description: String. A description of the piece of code
		 publish: Boolean. Flag enabling code source plublication in the catalog.
		 key: String. Optional. MD5 hash of a key used to allow code source modification and publication.
		 date: Date. Creation date.
		 modificationDate: Date. Last modification date
	 }

	 */

	var codeListIndex = {};
	var codeIndex = {};
	var codeList = function(catalogId) {
		if(!catalogId) throw wigiiNcd().createServiceException("Catalog ID cannot be null",wigiiNcd().errorCodes.INVALID_ARGUMENT);
		var returnValue = codeListIndex[catalogId];
		if(!returnValue) {
			returnValue = programme.panier.no(wigiiNcdEtp.ctxKey+"_"+catalogId);
			// fetches catalog from server
			returnValue.importer(wigiiNcdEtp.serveur.obtenirDonnee(catalogId));
			// indexes code list
			var n = returnValue.taille();
			if(n > 0) {
				for(var i=1;i<=n;i++) {
					var srcCode = returnValue.lire(i);
					if(srcCode && srcCode.info) {
						codeIndex[catalogId+"_"+srcCode.info.id] = srcCode;
					}
				}
			}
			codeListIndex[catalogId] = returnValue;
			//returnValue.montrer();
		}
		return returnValue;
	};
	var findCode = function(catalogId, codeId) {
		var returnValue = codeIndex[catalogId+"_"+codeId];
		if(!returnValue) {
			if(codeList(catalogId).taille() > 0) returnValue = codeIndex[catalogId+"_"+codeId];			
		}
		return returnValue;
	};
	
	/**
	 * Fetches a piece of public code published into the given catalog.
	 *@param String label Label of button used to invoke the code
	 *@param String catalogId Catalog ID from which to fetch the source code
	 *@param String codeId unique ID identifying the piece of code
	 *@return Object. Returns a Source Code object
	 */
	var codePublic = function(label, catalogueId, codeId, cssClass) {
		var program = sousProgramme(
			p(),out("Du code publique."),$p(),
			p(),out("Définir le catalogue ID et le code ID pour le charger."),$p()
		);
		var srcCode={info:undefined};
		if(catalogueId && codeId) {
			srcCode = findCode(catalogueId, codeId);
			if(!srcCode) throw wigiiNcd().createServiceException("No source code found in catalog '"+catalogueId+"' with ID '"+codeId+"'",wigiiNcd().errorCodes.INVALID_ARGUMENT);
			program = wigiiNcd().fxString2obj($.base64Decode(srcCode.program));
		}		
		return {
			label:label,
			cssClass:cssClass,
			program: program,
			info: srcCode.info
		};
	};
	
	/**
	 * Creates or updates a source code object
	 *@param String label Label of button used to invoke the code
	 *@param Function program Source code as an Fx program.
	 *@param Object info Optional Source code meta information object for publication 
	 *@return Object. Returns a Source Code object
	 */
	var codeSource = function(label,program,info,cssClass) {
		try {			
			var returnValue = {
				label:label,
				cssClass:cssClass,
				program:program,
				info: {}
			};		
			if(info) {
				returnValue.info.author = info.author;
				returnValue.info.description = info.description;
				if(info.key) returnValue.info.key = $.md5(info.key);
				if(info.publish) {
					var timestamp = Date.now();
					var srcCode = {						
						info: returnValue.info,
						program:$.base64Encode(wigiiNcd().obj2FxString(program))
					};
					var srcCodeChanged = false;
					var newPublication = false;
					// if existing code ID, fetches it in catalog				
					if(info.id) {
						var existingCode = findCode(info.catalog, info.id);
						// if existing code is not in catalog, adds it
						if(!existingCode) {
							returnValue.info.id = info.id;
							returnValue.info.catalog = info.catalog;
							returnValue.info.date = timestamp;
							returnValue.info.modificationDate = timestamp;
							// adds source code to codeList					
							codeList(returnValue.info.catalog).ajouter(srcCode);
							// indexes source code
							codeIndex[returnValue.info.catalog+"_"+returnValue.info.id] = srcCode;
							srcCodeChanged = true;
						}
						// else updates it
						else {
							if(existingCode.info.key && existingCode.info.key != srcCode.info.key) throw wigiiNcd().createServiceException("Invalid modification key. No rights to modify code in catalog '"+info.catalog+"' with ID '"+info.id+"'",wigiiNcd().errorCodes.FORBIDDEN);
							// updates existing code if some changes
							srcCodeChanged = wigiiNcd().updateObjIfChanged(existingCode.info,srcCode.info).field('author').field('description').set('modificationDate',timestamp).hasChanges();
							srcCodeChanged = wigiiNcd().updateObjIfChanged(existingCode,srcCode).field('program').set('info',timestamp,'modificationDate').hasChanges() | srcCodeChanged;
							if(srcCodeChanged) srcCode = existingCode;
						}
					}
					// else generates ID
					else {
						returnValue.info.id = timestamp+"_"+srcCode.program.substr(3,8);
						returnValue.info.catalog = info.catalog;
						returnValue.info.date = timestamp;
						returnValue.info.modificationDate = timestamp;
						// adds source code to codeList					
						codeList(returnValue.info.catalog).ajouter(srcCode);
						// indexes source code
						codeIndex[returnValue.info.catalog+"_"+returnValue.info.id] = srcCode;
						srcCodeChanged = true;
						newPublication = true;
					}
					// pushes code to server if some changes
					if(srcCodeChanged) {						
						srcCode.id = srcCode.info.id;
						wigiiNcdEtp.serveur.stockerDonnee(info.catalog,'id',srcCode);
					}
					// if new publication then shows ID to user
					if(newPublication) {
						returnValue.program = sousProgramme(
							p(),out('Le code source "'),i(info.description),out('" a été publié dans le catalogue '+info.catalog+" sous l'ID: "),b(srcCode.id),$p(),
							p(),color("orange"),out("Ajoutez l'ID dans les infos de publications du code source avant de recharger une nouvelle fois la page."),$color(),$p()
						);
					}
				}
			}
		}
		catch(exc) {programme.html().publishException(exc);}
		return returnValue;
	};
	
	/**
	 * Creates a menu which handles a list of active NCD plugins.
	 * Each selected plugin should be invoked by using the codeSource or codePublic functions.
	 *@example menu(codeSource("A",sousProgramme(p(),out("something"),$p())),codePublic("B","123","1234567"))
	 */
	var menu = function() {
		var timestamp = Date.now();
		var args = Array.prototype.slice.call(arguments);
		var divMenu = html.div('menu'+timestamp);
		var divCodeOutput = html.div('codeOutput'+timestamp);
		// Menu object
		var menuObj = {};
		menuObj.modules = [];
		// Code Button
		var codeButton = function(code) {
			// Registers code into loaded modules
			code.menu = menuObj;
			menuObj.modules.push(code);
			// Creates invocation button
			divMenu.button(code.label,function(){			
				divCodeOutput.reset();
				programme.context.codeSource = code;
				programme.context.html(divCodeOutput);
				code.program();
				programme.context.html(html);
			}, code.cssClass);
		};
		for(var i=0;i<args.length;i++) { codeButton(args[i]); }		
	};

	/**
	 * Loads and updates a library source code
	 *@param Function program Library source code as an Fx program.
	 *@param Object info Optional Source code meta information object for publication 
	 */
	var libSource = function(program,info) {
		// creates a code object
		var code = codeSource(undefined,program,info);
		// runs the library code
		try {
			code.program();
		}
		catch(exc) {programme.html().publishException(exc);}
	};
	/**
	 * Fetches a public library published into the given catalog.
	 *@param String catalogId Catalog ID from which to fetch the source code
	 *@param String codeId unique ID identifying the piece of code
	 */
	var libPublic = function(catalogueId, codeId) {
		try {
			// fetches the code object in the given catalogue
			var code = codePublic(undefined,catalogueId,codeId);
			// runs the library code
			code.program();
		}
		catch(exc) {programme.html().publishException(exc);}
	};
	
	/**
	 * Code source editor component. Allows to edit and test some source code given as a function.
	 *@param Function f the source code to edit given as an anonymous function without any arguments.
	 *@param Object options a set of options to configure the behavior of the Source Code Editor component. It supports the following attributes :
	 * - programOutput: HtmlEmitter|JQuery. A reference of where to redirect the output when running code. Can be an open HtmlEmitter or a JQuery selector.
	 * - runOnLoad: Boolean. If true, then given code is executed when loaded into the editor. False by default.
	 * - resetOutput: Boolean. If true, then before the code is tested, the linked output is reset. 
	 * By default, reset occurs only if output has been redirected to a specific location and not kept on current div.
	 * - testBtnLabel: String. Label to put on the "Test" button. Defaults to "Test".
	 * - testBtnClass: String. Optional CSS class to attach to the Test button.
	 * - afterTest: Function. Callback each time the code has been tested. The callback receives the CodeSourceEditor instance.
	 * - textAreaClass: String. Optional CSS class to attach to the source code text area.
	 */
	wigiiNcdEtp.CodeSourceEditor = function(f,options) {
		var self = this;
		self.className = 'CodeSourceEditor';
		self.instantiationTime = Date.now();
		self.ctxKey = wigiiNcdEtp.ctxKey+'_'+self.className+self.instantiationTime;
		self.options = options || {};
		self.context = {};
		self.impl = {};
		
		// Defines default options
		if(!self.options.testBtnLabel) self.options.testBtnLabel = 'Test';
		if(!self.options.programOutput) {
			self.options.programOutput = wncd.currentDiv();
			if(self.options.resetOutput === undefined) self.options.resetOutput = false;
		}
		else if(self.options.programOutput === 'above' || 
				self.options.programOutput === 'below' ||
				self.options.programOutput === 'between') {
			if(self.options.resetOutput === undefined) self.options.resetOutput = true;			
		}
		else if($.type(self.options.programOutput)==='string' || self.options.programOutput.className != 'HtmlEmitter') {
			self.options.programOutput = wncd.html(self.options.programOutput);
			if(self.options.resetOutput === undefined) self.options.resetOutput = true;
		}
		else if(self.options.resetOutput === undefined) self.options.resetOutput = true;
		
		/**
		 * The source code wrapped as a scripte ready to be executed.
		 */
		self.context.codeScript = scripte(f);
		/**
		 * The source code as a valid string
		 */
		self.context.srcCode = wigiiNcd().obj2FxString(self.context.codeScript);
		
		/**
		 * Runs the source code
		 *@param FuncExp the source code wrapped as a script FuncExp. (cf. scripte function)
		 */
		self.impl.runCode = function(scripte) {
			var currentDiv = programme.currentDiv();
			programme.context.html(self.options.programOutput);
			if(self.options.resetOutput) self.options.programOutput.reset();
			scripte();
			programme.context.html(currentDiv);
		};
		
		if(self.options.programOutput === 'above') self.options.programOutput = programme.currentDiv().div(self.ctxKey+"_out");
		
		/**
		 * The TextArea object to edit the source code
		 */
		self.context.textArea = programme.currentDiv().createTextArea(self.options.textAreaClass);
		self.context.textArea.text(self.context.srcCode)
		// Behavior: when typing, saves text 
		.onInput(function(txtArea,txt){txtArea.text(txt);})
		// Configures Jquery plugins
		.$().tabby();
		
		if(self.options.programOutput === 'between') self.options.programOutput = programme.currentDiv().div(self.ctxKey+"_out");
		
		// Adds a button to test the code
		programme.currentDiv().button(self.options.testBtnLabel,function(){
			var srcCode = self.context.textArea.text();
			// Creates back a script from the source code
			var scripte = wigiiNcd().fxString2obj(srcCode);
			// Runs the script
			self.impl.runCode(scripte);
			// Saves new scripte in context
			self.context.codeScript = scripte;
			if(self.options.afterTest) self.options.afterTest(self);
		}, self.options.testBtnClass);
		
		if(self.options.programOutput === 'below') self.options.programOutput = programme.currentDiv().div(self.ctxKey+"_out");
		
		// Runs on load
		if(self.options.runOnLoad) {
			self.impl.runCode(self.context.codeScript);
			if(self.options.afterTest) self.options.afterTest(self);
		}
	};
	/**
	 * Creates a CodeSourceEditor instance to edit and test the given function.
	 *@param Function f some source code wrapped into an anonymous function without any parameters
	 *@param Object options some options to configure the CodeSourceEditor.
	 *@return wigiiNcdEtp.CodeSourceEditor 
	 */
	wigiiNcdEtp.createCodeSourceEditor = function(f,options) { return new wigiiNcdEtp.CodeSourceEditor(f,options);}
	
	/**
	 * Opens a CodeSourceEditor to allow the user to see and test a given piece of code
	 *@param Function f a scope holding some code to be tested by the user
	 *@param Object options some options to configure the CodeSourceEditor. See CodeSourceEditor constructor for more details on available options.
	 *@return Function a FuncExp ready to be invoked
	 */
	var exemple = function(f,options) { 
		var returnValue = function(fxCtx){
			return wigiiNcdEtp.createCodeSourceEditor(f,options);	
		};
		returnValue.toFxString = function() {
			var fxs = 'exemple(';
			fxs += wigiiNcd().obj2FxString(f);
			if(options) {
				fxs += ',';
				fxs += wigiiNcd().obj2FxString(options);
			}
			fxs += ')';
			return fxs;
		};
		return returnValue;
	};
	
	// Interaction

	var pause = function(delay) {
		var returnValue = function(fxCtx) {
			if(fxCtx) {
				fxCtx.pause();
				//var w = $(window);
				//w.scrollTop(w.height());
				setTimeout(function(){
					try {					
						fxCtx.resume();
					}			
					catch(exc) {html.publishException(exc);}
				},1000*delay);
			}
		};
		returnValue.toFxString = function() {
			var fxs = 'pause(';
			fxs += wigiiNcd().obj2FxString(delay);		
			fxs += ')';
			return fxs;
		};
		return returnValue;
	};
	
	var boutonDePause = function(label,onclick,cssClass) {
		var returnValue = function(fxCtx){			
			fxCtx.html().button(label,function(){
				if($.isFunction(onclick)) fxRunF(onclick,fxCtx,undefined);
				if(fxCtx) fxCtx.resume();
			},cssClass);
			if(fxCtx) fxCtx.pause();
			//var w = $(window);
			//w.scrollTop(w.height());
		};
		returnValue.toFxString = function() {
			var fxs = 'boutonDePause(';
			fxs += wigiiNcd().obj2FxString(label);
			if(onclick) {
				fxs += ','
				fxs += wigiiNcd().obj2FxString(onclick);
			}
			if(cssClass) {
				if(!onclick) fxs += ',undefined'
				fxs += ','
				fxs += wigiiNcd().obj2FxString(onclick);
			}
			fxs += ')';
			return fxs;
		};
		return returnValue;
	};	
	var bouton = function(label,onClick,cssClass) {return dynImpl_fx_s("bouton",function(fxCtx){return fxCtx.html().button;},label,function(){return onClick;},cssClass);};
	

	
	// Turtle
	
	html.addModeler(function(html) {
		html.tortue = {
			impl:{
				collection: {},
				instance: function(index) {
					var self = this;
					self.context = {tortue:undefined,key:index};
					// Implementation
					self.impl = {};
					self.impl.initialize = function(tailColor) {
						if(!html.context.gridInstance) throw wigiiNcd().createServiceException("Aucune grille n'existe. Appelez d'abord la fonction grille(nLignes,nCols).",wigiiNcd().errorCodes.INVALID_STATE);
						if(!self.context.tortue || tailColor) {
							self.context.tortue = new (wigiiNcd().GridTurtle)(html.context.gridInstance,undefined,tailColor);
						}						
						return self.context.tortue;
					};
					self.impl.start = function(x,y,direction,tailColor){self.impl.initialize(tailColor).start(x-1,y-1,direction);};
					self.impl.avance = function() {self.impl.initialize().moveForward();};
					self.impl.avanceDiagGauche = function() {self.impl.initialize().moveForwardAndLeft();};
					self.impl.avanceDiagDroite = function() {self.impl.initialize().moveForwardAndRight();};
					self.impl.demiTour = function() {self.impl.initialize().turnBack();};
					self.impl.tourneAGauche = function() {self.impl.initialize().turnLeft();};
					self.impl.tourneADroite = function() {self.impl.initialize().turnRight();};
					// Actions
					self.start = function(x,y,direction,tailColor) {return fx(self.impl.start,x,y,direction,tailColor);};
					self.avance = function() {return ctlSeq(pause(0.4),fx(self.impl.avance));};
					self.avanceDiagGauche = function() {return ctlSeq(pause(0.4),fx(self.impl.avanceDiagGauche));};
					self.avanceDiagDroite = function() {return ctlSeq(pause(0.4),fx(self.impl.avanceDiagDroite));};
					self.demiTour = function() {return ctlSeq(pause(0.4),fx(self.impl.demiTour));};
					self.tourneAGauche = function() {return ctlSeq(pause(0.4),fx(self.impl.tourneAGauche));};
					self.tourneADroite = function() {return ctlSeq(pause(0.4),fx(self.impl.tourneADroite));};
					self.marche = function(nSteps,plan) {
						if(!plan) plan = function(i,tortue) {return tortue.avance();};
						else if(!$.isFunction(plan)) throw wigiiNcd().createServiceException("plan doit être une fonction.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
						return ctlGen(nSteps,plan,self.chainable(true));
					};		
					// Senseur
					self.celluleCourante = function() {if(self.context.tortue) return self.context.tortue.grid().initializeCellForEtp(self.context.tortue.currentCell());};	
					self.cellule = function(x,y) {if(self.context.tortue) return self.context.tortue.grid().initializeCellForEtp(self.context.tortue.cell(x,y));};
					// Mémoire
					self.memoire = function() {if(self.context.tortue) return self.context.tortue.context;};
					// Chainable
					self.chainable = function(disposable) {
						var tortueFxChain = ctlSeq();
						var tortueInstance = self;
						tortueFxChain.addFx(function(fxCtx){fxCtx.returnValue = tortueInstance; return fxCtx.returnValue;});
						var tortueFx = function(fxCtx) {
							var returnValue = tortueFxChain.toFx()(fxCtx);
							if(disposable) tortueFxChain = ctlSeq();
							return returnValue;
						};
						tortueFx.start = function(x,y,direction,tailColor) {
							tortueFxChain.addFx(tortueInstance.start(x,y,direction,tailColor));
							return tortueFx;
						},
						tortueFx.avance = function() {
							tortueFxChain.addFx(tortueInstance.avance());
							return tortueFx;
						},
						tortueFx.avanceDiagGauche = function() {
							tortueFxChain.addFx(tortueInstance.avanceDiagGauche());
							return tortueFx;
						},
						tortueFx.avanceDiagDroite = function() {
							tortueFxChain.addFx(tortueInstance.avanceDiagDroite());
							return tortueFx;
						},
						tortueFx.demiTour = function() {
							tortueFxChain.addFx(tortueInstance.demiTour());
							return tortueFx;
						},
						tortueFx.tourneAGauche = function() {
							tortueFxChain.addFx(tortueInstance.tourneAGauche());
							return tortueFx;
						},
						tortueFx.tourneADroite = function() {
							tortueFxChain.addFx(tortueInstance.tourneADroite());
							return tortueFx;
						}
						tortueFx.pause = function(delay) {
							tortueFxChain.addFx(pause(delay));
							return tortueFx;
						}	
						tortueFx.marche = function(nSteps,plan) {
							tortueFxChain.addFx(tortueInstance.marche(nSteps,plan));
							return tortueFx;
						}
						tortueFx.celluleCourante = function() {return tortueInstance.celluleCourante();}
						tortueFx.cellule = function(x,y) {return tortueInstance.cellule(x,y);}
						tortueFx.memoire = function() {return tortueInstance.memoire();}
						return tortueFx;
					};
				}
			},
			// Selecteur
			no: function(index) {
				var tortueInstance = html.tortue.impl.collection['T_'+index];
				if(!tortueInstance) {
					tortueInstance = new (html.tortue.impl.instance)(index);
					html.tortue.impl.collection['T_'+index] = tortueInstance;
				}		
				return tortueInstance.chainable();
			},
			reset: function() {html.tortue.impl.collection = {};},
			// Actions
			start: function(x,y,direction,couleurQueue) {return html.tortue.no('default').start(x,y,direction,couleurQueue||'blue');},
			avance: function() {return html.tortue.no('default').avance();},
			avanceDiagGauche: function() {return html.tortue.no('default').avanceDiagGauche();},
			avanceDiagDroite: function() {return html.tortue.no('default').avanceDiagDroite();},
			demiTour: function() {return html.tortue.no('default').demiTour();},
			tourneAGauche: function() {return html.tortue.no('default').tourneAGauche();},
			tourneADroite: function() {return html.tortue.no('default').tourneADroite();},
			pause: function(delay) {return html.tortue.no('default').pause(delay);},
			marche: function(nSteps,plan) {return html.tortue.no('default').marche(nSteps,plan);},
			// Senseur
			celluleCourante: function() {return html.tortue.no('default').celluleCourante();},
			cellule: function(x,y) {return html.tortue.no('default').cellule(x,y);},
			// Memoire
			memoire: function() {return html.tortue.no('default').memoire();}
		};
	});
	var tortue = {
		// Selecteur
		no: function(index) {return programme.currentDiv().tortue.no(index);},
		reset: function() {programme.currentDiv().tortue.reset();},
		// Actions
		start: function(x,y,direction,couleurQueue) {return tortue.no('default').start(x,y,direction,couleurQueue||'blue');},
		avance: function() {return tortue.no('default').avance();},
		avanceDiagGauche: function() {return tortue.no('default').avanceDiagGauche();},
		avanceDiagDroite: function() {return tortue.no('default').avanceDiagDroite();},
		demiTour: function() {return tortue.no('default').demiTour();},
		tourneAGauche: function() {return tortue.no('default').tourneAGauche();},
		tourneADroite: function() {return tortue.no('default').tourneADroite();},
		pause: function(delay) {return tortue.no('default').pause(delay);},
		marche: function(nSteps,plan) {return tortue.no('default').marche(nSteps,plan);},
		// Senseur
		celluleCourante: function() {return tortue.no('default').celluleCourante();},
		cellule: function(x,y) {return tortue.no('default').cellule(x,y);},
		// Memoire
		memoire: function() {return tortue.no('default').memoire();}
	}; 
	
	
	// Grid
	
	var grille = function(nLignes,nCols) {return fx_s("grille",function(nLignes_,nCols_){programme.currentDiv().tortue.reset();programme.currentDiv().grille(nLignes_,nCols_);},nLignes,nCols);};
	grille.cellule = function(ligne,colonne) {
		var gridCellFxChain = ctlSeq();
		gridCellFxChain.addFx(function(fxCtx){fxCtx.returnValue = fxCtx.html().grille.cellule(ligne,colonne); return fxCtx.returnValue;});
		var gridCellFx = function(fxCtx) {return gridCellFxChain.toFx()(fxCtx);};
		gridCellFx.couleur = function(c) {
			gridCellFxChain.addFx(function(fxCtx){fxCtx.returnValue = fxCtx.returnValue.couleur(c); return fxCtx.returnValue;});
			return gridCellFx;
		};
		gridCellFx.texte = function(txt) {
			gridCellFxChain.addFx(function(fxCtx){fxCtx.returnValue = fxCtx.returnValue.texte(txt); return fxCtx.returnValue;});
			return gridCellFx;
		};
		gridCellFx.pause = function(delay) {
			gridCellFxChain.addFx(pause(delay));
			return gridCellFx;
		};
		gridCellFx.bouton = function(label,onClick) {
			gridCellFxChain.addFx(function(fxCtx){fxCtx.returnValue = fxCtx.returnValue.bouton(label,onClick); return fxCtx.returnValue;});
			return gridCellFx;
		};
		gridCellFx.gauche = function(){
			gridCellFxChain.addFx(function(fxCtx){fxCtx.returnValue = fxCtx.returnValue.gauche(); return fxCtx.returnValue;});
			return gridCellFx;
		};
		gridCellFx.droite = function(){
			gridCellFxChain.addFx(function(fxCtx){fxCtx.returnValue = fxCtx.returnValue.droite(); return fxCtx.returnValue;});
			return gridCellFx;
		};
		gridCellFx.bas = function(){
			gridCellFxChain.addFx(function(fxCtx){fxCtx.returnValue = fxCtx.returnValue.bas(); return fxCtx.returnValue;});
			return gridCellFx;
		};
		gridCellFx.haut = function(){
			gridCellFxChain.addFx(function(fxCtx){fxCtx.returnValue = fxCtx.returnValue.haut(); return fxCtx.returnValue;});
			return gridCellFx;
		};
		gridCellFx.cellule = function(ligne,colonne){
			gridCellFxChain.addFx(function(fxCtx){fxCtx.returnValue = fxCtx.returnValue.cellule(ligne,colonne); return fxCtx.returnValue;});
			return gridCellFx;
		};
		gridCellFx.offset = function(ligne,colonne){
			gridCellFxChain.addFx(function(fxCtx){fxCtx.returnValue = fxCtx.returnValue.offset(ligne,colonne); return fxCtx.returnValue;});
			return gridCellFx;
		};
		return gridCellFx;	
	};
	grille.couleur = function(ligne,colonne,c) {return dynImpl_fx_s("grille.couleur",function(fxCtx){return fxCtx.html().grille.couleur;},ligne,colonne,c);}; 
	grille.texte = function(ligne,colonne,txt) {return dynImpl_fx_s("grille.texte",function(fxCtx){return fxCtx.html().grille.texte;},ligne,colonne,txt);};
	grille.onclick = function(f) {return dynImpl_fx_s("grille.onclick",function(fxCtx){return fxCtx.html().grille.onclick;},function(){return f;});};
	grille.fonctionSurClique = grille.onclick;
	
	// Re-definition of symbols for NCD basic programming language
	wigiiNcdEtp.out = function(str,cssClass){ return dynImpl_fx_s("out",function(fxCtx){return fxCtx.html().out;},str,cssClass);};
	wigiiNcdEtp.h1 = function(str,cssClass){return dynImpl_fx_s("h1",function(fxCtx){return fxCtx.html().h1;},str,cssClass);};
	wigiiNcdEtp.p = function(cssClass) {return dynImpl_fx_s("p",function(fxCtx){return fxCtx.html().p;},cssClass);};
	wigiiNcdEtp.$p = function() {return dynImpl_fx_s("$p",function(fxCtx){return fxCtx.html().$p;});};
	wigiiNcdEtp.b = function(str){return dynImpl_fx_s("b",function(fxCtx){return fxCtx.html().b;},str);};
	wigiiNcdEtp.i = function(str){return dynImpl_fx_s("i",function(fxCtx){return fxCtx.html().i;},str);};
	wigiiNcdEtp.u = function(str){return dynImpl_fx_s("u",function(fxCtx){return fxCtx.html().u;},str);};
	wigiiNcdEtp.a = function(url){return dynImpl_fx_s("a",function(fxCtx){return fxCtx.html().a;},url);};
	wigiiNcdEtp.color = function(c,backgroundC,cssClass) {return dynImpl_fx_s("color",function(fxCtx){return fxCtx.html().color;},c,backgroundC,cssClass);};
	wigiiNcdEtp.$color = function() {return dynImpl_fx_s("$color",function(fxCtx){return fxCtx.html().$color;});};
	
	
	// Layout system
	html.addModeler(function(html){
		/**
		 * Emits an HTML layout based on composition of horizontal (h) and vertical (v) containers and proportional boxes (x1,...,x10).
		 *@param Function layoutBuilder a function which composes a layout using the layout symbols. 
		 * The functions signature has the form: layoutBuilder(h,v,x1,x2,x3,x4,x5,x6,x7,x8,x9,x10,args,...)
		 */
		html.layout = function(layoutBuilder) {
			// extracts optional arguments
			var optionalArgs;
			if(arguments.length > 1) optionalArgs = Array.prototype.slice.call(arguments,1);
			else optionalArgs = [];	
			
			// Layout object
			var layout = {};
			
			// rendering functions
			
			/**
			 * Renders a proportional box
			 */
			layout.x = function(fxCtx,args,options) {
				// if options.h then renders an horizontal container
				if(options.h && !args) layout.h(fxCtx,options.h,options);
				// if options.v then renders a vertical container
				else if(options.v && !args) layout.v(fxCtx,options.v,options);
				// else renders a proportional box and fills it with content
				else fxCtx.html().htmlBuilder().tag('div',
						'id',options.id,
						'class',options.name+(options.cssClass?' '+options.cssClass:''),
						'style','float:left;width:'+options.width+'%;height:'+options.height+'%;'
					)
					.insert(args[0])
					.$tag('div').emit();
			}
			layout.x1 = layout.x;
			layout.x2 = layout.x;
			layout.x3 = layout.x;
			layout.x4 = layout.x;
			layout.x5 = layout.x;
			layout.x6 = layout.x;
			layout.x7 = layout.x;
			layout.x8 = layout.x;
			layout.x9 = layout.x;
			layout.x10 = layout.x;
			/**
			 * Returns the relative weight of a proportional box
			 */
			layout.weightOf = function(symbol) {
				if(symbol && symbol.startsWith('x')) return Number(symbol.substr(1));
				else return 0;
			};
			
			/**
			 * Renders an horizontal container
			 */
			layout.h = function(fxCtx,args,options) {			
				var htmlb = fxCtx.html().htmlBuilder();
				// extracts width and height
				var width = options.width;
				if(width) width = width+'%';
				else width = options.cssWidth;
				var height = options.height;
				if(height) height = height+'%';
				else height = options.cssHeight;
				// computes total weight and extracts unit weight
				var unitWeight = 0; var totalProportion = 0;
				for(var i=0;i<args.length;i++) {
					unitWeight += args[i].options.weight;
				}
				unitWeight = 100.0/unitWeight;
				// renders container
				htmlb.tag('div',
					'id',options.id,
					'class','h'+(options.cssClass?' '+options.cssClass:''),
					'style','float:left;width:'+width+';height:'+height+';'
				);
				// renders all components with relative width
				for(var i=0;i<args.length;i++) {
					var componentFx = args[i];				
					if(i<args.length-1) componentFx.options.width = Math.floor(componentFx.options.weight*unitWeight);
					else componentFx.options.width = 100-totalProportion;
					totalProportion += componentFx.options.width;
					componentFx.options.height = 100;
					htmlb.insert(function(componentFx){componentFx(wncd.createFxContext());},componentFx);
				}
				htmlb.$tag('div').emit();
			};
			/**
			 * Renders a vertical container
			 */
			layout.v = function(fxCtx,args,options) {						
				var htmlb = fxCtx.html().htmlBuilder();
				// extracts width and height
				var width = options.width;
				if(width) width = width+'%';
				else width = options.cssWidth;
				var height = options.height;
				if(height) height = height+'%';
				else height = options.cssHeight;
				// computes total weight and extracts unit weight
				var unitWeight = 0; var totalProportion = 0;
				for(var i=0;i<args.length;i++) {
					unitWeight += args[i].options.weight;
				}
				unitWeight = 100.0/unitWeight;
				// renders container
				htmlb.tag('div',
					'id',options.id,
					'class','v'+(options.cssClass?' '+options.cssClass:''),
					'style','float:left;width:'+width+';height:'+height+';'
				);
				// renders all components with relative height
				for(var i=0;i<args.length;i++) {
					var componentFx = args[i];
					componentFx.options.width = 100;
					if(i<args.length-1) componentFx.options.height = Math.floor(componentFx.options.weight*unitWeight);
					else componentFx.options.height = 100-totalProportion;
					totalProportion += componentFx.options.height;
					htmlb.insert(function(componentFx){componentFx(wncd.createFxContext());},componentFx);
				}
				htmlb.$tag('div').emit();
			}		
			/**
			 * Creates a FuncExp which wraps the underlying rendering function
			 *@param String name the name of the underlying rendering function (one of h,v,x1,...,x10)
			 *@param Array args the array of arguments passed to the FuncExp. These map to children nodes in the layout expression tree.
			 *@param Object options some rendering options, like the id, cssClass, width or height.
			 */
			layout.fx = function(name,args,options) {
				options = {name:name,id:'',cssClass:'',weight:layout.weightOf(name),h:(options?options.h:undefined),v:(options?options.v:undefined)};
				
				// Func Exp mapping a layout function
				var returnValue = function(fxCtx) {return layout[name](fxCtx,args,options);};
				returnValue.options = options;
				
				// Adds chaining capabilities to allow user to configure the boxes
				/**
				 * Sets an html id to the given box
				 */
				returnValue.id = function(id) {options.id = id;return returnValue;};
				/**
				 * Sets a CSS class to the given box
				 */
				returnValue.cssClass = function(cssClass) {options.cssClass = cssClass;return returnValue;};
				
				// For h and v container, allows the user to define initial height and weight
				if(!layout.weightOf(name)) {
					/**
					 * Sets the height of the initial container
					 *@param String cssHeight the height of the container in CSS syntax (200px or 50%, etc)
					 */
					returnValue.height = function(cssHeight) {options.cssHeight = cssHeight;return returnValue;}
					/**
					 * Sets the width of the initial container
					 *@param String cssWidth the width of the container in CSS syntax (200px or 50%, etc)
					 */
					returnValue.width = function(cssWidth) {options.cssWidth = cssWidth;return returnValue;}
				}
				
				/**
				 * Fx serializer
				 */
				returnValue.toFxString = function() {
					var fxs = name;
					// Serializes arguments
					var fxsArgs = args;
					if(options.h) {
						fxs += '.h';
						fxsArgs = options.h;
					}
					else if(options.v) {
						fxs += '.v';
						fxsArgs = options.v;
					}
					fxs +='(';
					for(var i=0;i<fxsArgs.length;i++) {
						if(i>0) fxs += ',';
						fxs += wncd.obj2FxString(fxsArgs[i]);			
					}
					fxs += ')';
					if(options.id) fxs += '.id("'+options.id+'")';
					if(options.cssClass) fxs += '.cssClass("'+options.cssClass+'")';
					if(name == 'h' || name=='v') {
						if(options.cssWidth) fxs += '.width("'+options.cssWidth+'")';
						if(options.cssHeight) fxs += '.height("'+options.cssHeight+'")';
					}
					return fxs;
				};			
				return returnValue;
			}
			
			// declares layout building language symbols
			
			/**
			 * Fx constructor for the h container
			 */
			layout.hFx = function() {return layout.fx('h',Array.prototype.slice.call(arguments));}
			/**
			 * Fx constructor for the v container
			 */
			layout.vFx = function() {return layout.fx('v',Array.prototype.slice.call(arguments));}
			
			/**
			 * Generates an Fx constructor for the given proportional box x1,...,x10
			 *@return Function an Fx constructor
			 */
			layout.xFx = function(name) {
				var returnValue = function() {return layout.fx(name,Array.prototype.slice.call(arguments));}
				var options = {v:undefined,h:undefined};
				/**
				 * Injects a vertical container into the current proportional box.
				 */
				returnValue.v = function() {options.v = Array.prototype.slice.call(arguments);return layout.fx(name,undefined,options);};
				/**
				 * Injects an horizontal container into the current proportional box.
				 */
				returnValue.h = function() {options.h = Array.prototype.slice.call(arguments);return layout.fx(name,undefined,options);};
				return returnValue;
			}
			/**
			 * Fx constructor for the x1 proportional box
			 */
			layout.x1Fx = layout.xFx('x1');
			/**
			 * Fx constructor for the x2 proportional box
			 */
			layout.x2Fx = layout.xFx('x2');
			/**
			 * Fx constructor for the x3 proportional box
			 */
			layout.x3Fx = layout.xFx('x3');
			/**
			 * Fx constructor for the x4 proportional box
			 */
			layout.x4Fx = layout.xFx('x4');
			/**
			 * Fx constructor for the x5 proportional box
			 */
			layout.x5Fx = layout.xFx('x5');
			/**
			 * Fx constructor for the x6 proportional box
			 */
			layout.x6Fx = layout.xFx('x6');
			/**
			 * Fx constructor for the x7 proportional box
			 */
			layout.x7Fx = layout.xFx('x7');
			/**
			 * Fx constructor for the x8 proportional box
			 */
			layout.x8Fx = layout.xFx('x8');
			/**
			 * Fx constructor for the x9 proportional box
			 */
			layout.x9Fx = layout.xFx('x9');
			/**
			 * Fx constructor for the x10 proportional box
			 */
			layout.x10Fx = layout.xFx('x10');
			
			// fills language array
			var layoutLanguage = [layout.hFx, layout.vFx, layout.x1Fx, layout.x2Fx, layout.x3Fx, layout.x4Fx, layout.x5Fx, layout.x6Fx, layout.x7Fx, layout.x8Fx, layout.x9Fx, layout.x10Fx];
			Array.prototype.push.apply(layoutLanguage,optionalArgs);
			// builds layout Fx using the layoutBuilder function provided by the user
			var layoutFx = layoutBuilder.apply(null,layoutLanguage);
			// executes layout Fx
			if(layoutFx) wncd.program(layoutFx);
			// returns HtmlEmitter for chaining
			return html;
		};
	});
	
	// Scripting environment
	programme.context = { impl:{},
		html: function(html) {
			if(html===undefined) {
				if(programme.context.fxCtx && $.isFunction(programme.context.fxCtx.html)) return programme.context.fxCtx.html();
				else if(programme.context.impl.html) return programme.context.impl.html;
				else return wigiiNcdEtp.html;
			}
			else {
				if(programme.context.fxCtx && $.isFunction(programme.context.fxCtx.html)) programme.context.fxCtx.html(html);
				programme.context.impl.html = html;
				return programme.context;
			}
		}
	};
	programme.grille = html.grille;
	programme.tortue = html.tortue;
	programme.panier = wigiiNcdEtp.panier; 
	programme.formulaire = wigiiNcdEtp.formulaire; 
	programme.serveur = wigiiNcdEtp.serveur; 
	programme.div = function(id, cssClass) {return html.div(id,cssClass);}; 
	programme.html = function(){ return html;};
	programme.mainDiv = programme.html; 
	programme.currentDiv = function() {return programme.context.html();}; 
	programme.out = function(str,cssClass){programme.currentDiv().out(str,cssClass);return programme;}; 
	programme.h1 = function(str,cssClass){programme.currentDiv().h1(str,cssClass);return programme;}; 
	programme.p = function(cssClass) {programme.currentDiv().p(cssClass);return programme;};
	programme.$p = function() {programme.currentDiv().$p();return programme;}; 
	programme.b = function(str){programme.currentDiv().b(str);return programme;};
	programme.i = function(str){programme.currentDiv().i(str);return programme;}; 
	programme.u = function(str){programme.currentDiv().u(str);return programme;};
	programme.a = function(url){programme.currentDiv().a(url);return programme;}; 
	programme.color = function(c,backgroundC,cssClass) {programme.currentDiv().color(c,backgroundC,cssClass);return programme;};
	programme.$color = function() {programme.currentDiv().$color();return programme;}; 
	programme.bouton = function(label,onClick,cssClass) {programme.currentDiv().bouton(label,onClick,cssClass);return programme;};
	programme.codeSource = function() {return programme.context.codeSource;}; 
	programme.libSource = libSource; 
	programme.libPublic = libPublic;
	programme.txtDate = wigiiNcd().txtDate; 
	programme.txtFrenchDate = wigiiNcd().txtFrenchDate; 
	programme.createSelectionSense = wigiiNcd().createSelectionSense; 
	programme.bindSelectionSense = wigiiNcd().bindSelectionSense; 
	programme.createCountingSense = wigiiNcd().createCountingSense;
	programme.bindCountingSense = wigiiNcd().bindCountingSense;
	
	
	// Publish language
	wigiiNcdEtp.programme = programme;
	wigiiNcdEtp.sousProgramme = sousProgramme; 
	wigiiNcdEtp.fx = fx;
	wigiiNcdEtp.fx_s = fx_s;
	wigiiNcdEtp.createFxContext = createFxContext;
	wigiiNcdEtp.ctlSeq = ctlSeq;
	wigiiNcdEtp.sequence = ctlSeq; 
	wigiiNcdEtp.sequence.ajouter = wigiiNcdEtp.sequence.addFx; 
	wigiiNcdEtp.sequence.fin = wigiiNcdEtp.sequence.toFx; 
	wigiiNcdEtp.scripte = scripte; 
	wigiiNcdEtp.exemple = exemple; 
	wigiiNcdEtp.ctlGen = ctlGen; 
	wigiiNcdEtp.codePublic = codePublic; 
	wigiiNcdEtp.codeSource = codeSource;
	wigiiNcdEtp.libPublic = libPublic;
	wigiiNcdEtp.libSource = libSource; 
	wigiiNcdEtp.menu = menu; 
	wigiiNcdEtp.pause = pause; 
	wigiiNcdEtp.boutonDePause = boutonDePause; 
	wigiiNcdEtp.bouton = bouton;
	wigiiNcdEtp.tortue = tortue; 
	wigiiNcdEtp.grille = grille;
	
	// English translation
	wigiiNcdEtp.program = wigiiNcdEtp.programme;
	wigiiNcdEtp.subProgram = wigiiNcdEtp.sousProgramme; 
	wigiiNcdEtp.sequence.add = wigiiNcdEtp.sequence.addFx;
	wigiiNcdEtp.sequence.end = wigiiNcdEtp.sequence.toFx;
	wigiiNcdEtp.script = wigiiNcdEtp.scripte;
	wigiiNcdEtp.example = wigiiNcdEtp.exemple;
	wigiiNcdEtp.buttonPause = wigiiNcdEtp.boutonDePause;
	wigiiNcdEtp.button = wigiiNcdEtp.bouton;
	wigiiNcdEtp.turtle = wigiiNcdEtp.tortue;
	wigiiNcdEtp.grid = wigiiNcdEtp.grille;	
	
	// Exporting symbols
	if(wigiiNcdEtpOptions.publishNcdEtpFxToWncd===undefined) wigiiNcdEtpOptions.publishNcdEtpFxToWncd = function(wigiiNcdEtp,wncd) {
		// refreshes NCD ETP symbols			
		if(wigiiNcdEtpOptions.publishNcdEtpToWncd) wigiiNcdEtpOptions.publishNcdEtpToWncd(wigiiNcdEtp,wncd);
		// creates shortcuts
		wncd.div = wigiiNcdEtp.program.div;
		wncd.currentDiv = wigiiNcdEtp.program.currentDiv;
	};
	if(!window.wncd) window.wncd = {};
	if(wigiiNcdEtpOptions.publishNcdEtpFxToWncd) wigiiNcdEtpOptions.publishNcdEtpFxToWncd(wigiiNcdEtp,window.wncd);
	if(wigiiNcdEtpOptions.publishNcdEtpToWindow) wigiiNcdEtpOptions.publishNcdEtpToWindow(wigiiNcdEtp,window);
	
	// Ready callback
	if(wigiiNcdEtpOptions.ncdEtpFxReady===undefined) wigiiNcdEtpOptions.ncdEtpFxReady = function(wigiiNcdEtp) {
		var footer = $("#footer");
		if(footer.length>0) footer.append('<span><i>, Wigii NCD Fx loaded</i></span>');
	}
	if(wigiiNcdEtpOptions.ncdEtpFxReady) wigiiNcdEtpOptions.ncdEtpFxReady(wigiiNcdEtp);
})(window, jQuery, wigiiNcd, wigiiNcdEtp); 
/**
 * Wigii Natural Code Development (NCD) external libraries package
 * Packaged by Camille Weber (camille@wigii.org), 12.12.2017
 */
if(!wncd.externals) wncd.externals = {};

/*!
 * Prism 1.9.0: Lightweight, robust, elegant syntax highlighting (http://prismjs.com)
 * @author Lea Verou http://lea.verou.me
 * @license MIT license http://www.opensource.org/licenses/mit-license.php/
 * Packaged Prism library as a wncd external library by camille@wigii.org on 12.12.2017
 */
wncd.externals.Prism = (function(_self){

// Private helper vars
var lang = /\blang(?:uage)?-(\w+)\b/i;
var uniqueId = 0;

var _ = _self.Prism = {
	manual: true /* camille@wigii.org: sets mode to be always manual.*/ /*_self.Prism && _self.Prism.manual*/,
	disableWorkerMessageHandler: _self.Prism && _self.Prism.disableWorkerMessageHandler,
	util: {
		encode: function (tokens) {
			if (tokens instanceof Token) {
				return new Token(tokens.type, _.util.encode(tokens.content), tokens.alias);
			} else if (_.util.type(tokens) === 'Array') {
				return tokens.map(_.util.encode);
			} else {
				return tokens.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/\u00a0/g, ' ');
			}
		},

		type: function (o) {
			return Object.prototype.toString.call(o).match(/\[object (\w+)\]/)[1];
		},

		objId: function (obj) {
			if (!obj['__id']) {
				Object.defineProperty(obj, '__id', { value: ++uniqueId });
			}
			return obj['__id'];
		},

		// Deep clone a language definition (e.g. to extend it)
		clone: function (o) {
			var type = _.util.type(o);

			switch (type) {
				case 'Object':
					var clone = {};

					for (var key in o) {
						if (o.hasOwnProperty(key)) {
							clone[key] = _.util.clone(o[key]);
						}
					}

					return clone;

				case 'Array':
					return o.map(function(v) { return _.util.clone(v); });
			}

			return o;
		}
	},

	languages: {
		extend: function (id, redef) {
			var lang = _.util.clone(_.languages[id]);

			for (var key in redef) {
				lang[key] = redef[key];
			}

			return lang;
		},

		/**
		 * Insert a token before another token in a language literal
		 * As this needs to recreate the object (we cannot actually insert before keys in object literals),
		 * we cannot just provide an object, we need anobject and a key.
		 * @param inside The key (or language id) of the parent
		 * @param before The key to insert before. If not provided, the function appends instead.
		 * @param insert Object with the key/value pairs to insert
		 * @param root The object that contains `inside`. If equal to Prism.languages, it can be omitted.
		 */
		insertBefore: function (inside, before, insert, root) {
			root = root || _.languages;
			var grammar = root[inside];

			if (arguments.length == 2) {
				insert = arguments[1];

				for (var newToken in insert) {
					if (insert.hasOwnProperty(newToken)) {
						grammar[newToken] = insert[newToken];
					}
				}

				return grammar;
			}

			var ret = {};

			for (var token in grammar) {

				if (grammar.hasOwnProperty(token)) {

					if (token == before) {

						for (var newToken in insert) {

							if (insert.hasOwnProperty(newToken)) {
								ret[newToken] = insert[newToken];
							}
						}
					}

					ret[token] = grammar[token];
				}
			}

			// Update references in other language definitions
			_.languages.DFS(_.languages, function(key, value) {
				if (value === root[inside] && key != inside) {
					this[key] = ret;
				}
			});

			return root[inside] = ret;
		},

		// Traverse a language definition with Depth First Search
		DFS: function(o, callback, type, visited) {
			visited = visited || {};
			for (var i in o) {
				if (o.hasOwnProperty(i)) {
					callback.call(o, i, o[i], type || i);

					if (_.util.type(o[i]) === 'Object' && !visited[_.util.objId(o[i])]) {
						visited[_.util.objId(o[i])] = true;
						_.languages.DFS(o[i], callback, null, visited);
					}
					else if (_.util.type(o[i]) === 'Array' && !visited[_.util.objId(o[i])]) {
						visited[_.util.objId(o[i])] = true;
						_.languages.DFS(o[i], callback, i, visited);
					}
				}
			}
		}
	},
	plugins: {},

	highlightAll: function(async, callback) {
		_.highlightAllUnder(document, async, callback);
	},

	highlightAllUnder: function(container, async, callback) {
		var env = {
			callback: callback,
			selector: 'code[class*="language-"], [class*="language-"] code, code[class*="lang-"], [class*="lang-"] code'
		};

		_.hooks.run("before-highlightall", env);

		var elements = env.elements || container.querySelectorAll(env.selector);

		for (var i=0, element; element = elements[i++];) {
			_.highlightElement(element, async === true, env.callback);
		}
	},

	highlightElement: function(element, async, callback) {
		// Find language
		var language, grammar, parent = element;

		while (parent && !lang.test(parent.className)) {
			parent = parent.parentNode;
		}

		if (parent) {
			language = (parent.className.match(lang) || [,''])[1].toLowerCase();
			grammar = _.languages[language];
		}

		// Set language on the element, if not present
		element.className = element.className.replace(lang, '').replace(/\s+/g, ' ') + ' language-' + language;

		if (element.parentNode) {
			// Set language on the parent, for styling
			parent = element.parentNode;

			if (/pre/i.test(parent.nodeName)) {
				parent.className = parent.className.replace(lang, '').replace(/\s+/g, ' ') + ' language-' + language;
			}
		}

		var code = element.textContent;

		var env = {
			element: element,
			language: language,
			grammar: grammar,
			code: code
		};

		_.hooks.run('before-sanity-check', env);

		if (!env.code || !env.grammar) {
			if (env.code) {
				_.hooks.run('before-highlight', env);
				env.element.textContent = env.code;
				_.hooks.run('after-highlight', env);
			}
			_.hooks.run('complete', env);
			return;
		}

		_.hooks.run('before-highlight', env);

		if (async && _self.Worker) {
			var worker = new Worker(_.filename);

			worker.onmessage = function(evt) {
				env.highlightedCode = evt.data;

				_.hooks.run('before-insert', env);

				env.element.innerHTML = env.highlightedCode;

				callback && callback.call(env.element);
				_.hooks.run('after-highlight', env);
				_.hooks.run('complete', env);
			};

			worker.postMessage(JSON.stringify({
				language: env.language,
				code: env.code,
				immediateClose: true
			}));
		}
		else {
			env.highlightedCode = _.highlight(env.code, env.grammar, env.language);

			_.hooks.run('before-insert', env);

			env.element.innerHTML = env.highlightedCode;

			callback && callback.call(element);

			_.hooks.run('after-highlight', env);
			_.hooks.run('complete', env);
		}
	},

	highlight: function (text, grammar, language) {
		var tokens = _.tokenize(text, grammar);
		return Token.stringify(_.util.encode(tokens), language);
	},

	matchGrammar: function (text, strarr, grammar, index, startPos, oneshot, target) {
		var Token = _.Token;

		for (var token in grammar) {
			if(!grammar.hasOwnProperty(token) || !grammar[token]) {
				continue;
			}

			if (token == target) {
				return;
			}

			var patterns = grammar[token];
			patterns = (_.util.type(patterns) === "Array") ? patterns : [patterns];

			for (var j = 0; j < patterns.length; ++j) {
				var pattern = patterns[j],
					inside = pattern.inside,
					lookbehind = !!pattern.lookbehind,
					greedy = !!pattern.greedy,
					lookbehindLength = 0,
					alias = pattern.alias;

				if (greedy && !pattern.pattern.global) {
					// Without the global flag, lastIndex won't work
					var flags = pattern.pattern.toString().match(/[imuy]*$/)[0];
					pattern.pattern = RegExp(pattern.pattern.source, flags + "g");
				}

				pattern = pattern.pattern || pattern;

				// Don’t cache length as it changes during the loop
				for (var i = index, pos = startPos; i < strarr.length; pos += strarr[i].length, ++i) {

					var str = strarr[i];

					if (strarr.length > text.length) {
						// Something went terribly wrong, ABORT, ABORT!
						return;
					}

					if (str instanceof Token) {
						continue;
					}

					pattern.lastIndex = 0;

					var match = pattern.exec(str),
					    delNum = 1;

					// Greedy patterns can override/remove up to two previously matched tokens
					if (!match && greedy && i != strarr.length - 1) {
						pattern.lastIndex = pos;
						match = pattern.exec(text);
						if (!match) {
							break;
						}

						var from = match.index + (lookbehind ? match[1].length : 0),
						    to = match.index + match[0].length,
						    k = i,
						    p = pos;

						for (var len = strarr.length; k < len && (p < to || (!strarr[k].type && !strarr[k - 1].greedy)); ++k) {
							p += strarr[k].length;
							// Move the index i to the element in strarr that is closest to from
							if (from >= p) {
								++i;
								pos = p;
							}
						}

						/*
						 * If strarr[i] is a Token, then the match starts inside another Token, which is invalid
						 * If strarr[k - 1] is greedy we are in conflict with another greedy pattern
						 */
						if (strarr[i] instanceof Token || strarr[k - 1].greedy) {
							continue;
						}

						// Number of tokens to delete and replace with the new match
						delNum = k - i;
						str = text.slice(pos, p);
						match.index -= pos;
					}

					if (!match) {
						if (oneshot) {
							break;
						}

						continue;
					}

					if(lookbehind) {
						lookbehindLength = match[1].length;
					}

					var from = match.index + lookbehindLength,
					    match = match[0].slice(lookbehindLength),
					    to = from + match.length,
					    before = str.slice(0, from),
					    after = str.slice(to);

					var args = [i, delNum];

					if (before) {
						++i;
						pos += before.length;
						args.push(before);
					}

					var wrapped = new Token(token, inside? _.tokenize(match, inside) : match, alias, match, greedy);

					args.push(wrapped);

					if (after) {
						args.push(after);
					}

					Array.prototype.splice.apply(strarr, args);

					if (delNum != 1)
						_.matchGrammar(text, strarr, grammar, i, pos, true, token);

					if (oneshot)
						break;
				}
			}
		}
	},

	tokenize: function(text, grammar, language) {
		var strarr = [text];

		var rest = grammar.rest;

		if (rest) {
			for (var token in rest) {
				grammar[token] = rest[token];
			}

			delete grammar.rest;
		}

		_.matchGrammar(text, strarr, grammar, 0, 0, false);

		return strarr;
	},

	hooks: {
		all: {},

		add: function (name, callback) {
			var hooks = _.hooks.all;

			hooks[name] = hooks[name] || [];

			hooks[name].push(callback);
		},

		run: function (name, env) {
			var callbacks = _.hooks.all[name];

			if (!callbacks || !callbacks.length) {
				return;
			}

			for (var i=0, callback; callback = callbacks[i++];) {
				callback(env);
			}
		}
	}
};

var Token = _.Token = function(type, content, alias, matchedStr, greedy) {
	this.type = type;
	this.content = content;
	this.alias = alias;
	// Copy of the full string this token was created from
	this.length = (matchedStr || "").length|0;
	this.greedy = !!greedy;
};

Token.stringify = function(o, language, parent) {
	if (typeof o == 'string') {
		return o;
	}

	if (_.util.type(o) === 'Array') {
		return o.map(function(element) {
			return Token.stringify(element, language, o);
		}).join('');
	}

	var env = {
		type: o.type,
		content: Token.stringify(o.content, language, parent),
		tag: 'span',
		classes: ['token', o.type],
		attributes: {},
		language: language,
		parent: parent
	};

	if (o.alias) {
		var aliases = _.util.type(o.alias) === 'Array' ? o.alias : [o.alias];
		Array.prototype.push.apply(env.classes, aliases);
	}

	_.hooks.run('wrap', env);

	var attributes = Object.keys(env.attributes).map(function(name) {
		return name + '="' + (env.attributes[name] || '').replace(/"/g, '&quot;') + '"';
	}).join(' ');

	return '<' + env.tag + ' class="' + env.classes.join(' ') + '"' + (attributes ? ' ' + attributes : '') + '>' + env.content + '</' + env.tag + '>';

};

if (!_self.document) {
	if (!_self.addEventListener) {
		// in Node.js
		return _self.Prism;
	}

	if (!_.disableWorkerMessageHandler) {
		// In worker
		_self.addEventListener('message', function (evt) {
			var message = JSON.parse(evt.data),
				lang = message.language,
				code = message.code,
				immediateClose = message.immediateClose;

			_self.postMessage(_.highlight(code, _.languages[lang], lang));
			if (immediateClose) {
				_self.close();
			}
		}, false);
	}

	return _self.Prism;
}

//Get current script and highlight
var script = document.currentScript || [].slice.call(document.getElementsByTagName("script")).pop();

if (script) {
	_.filename = script.src;

	if (!_.manual && !script.hasAttribute('data-manual')) {
		if(document.readyState !== "loading") {
			if (window.requestAnimationFrame) {
				window.requestAnimationFrame(_.highlightAll);
			} else {
				window.setTimeout(_.highlightAll, 16);
			}
		}
		else {
			document.addEventListener('DOMContentLoaded', _.highlightAll);
		}
	}
}

return _self.Prism;

})(wncd.externals);

wncd.externals.Prism.languages.markup = {
	'comment': /<!--[\s\S]*?-->/,
	'prolog': /<\?[\s\S]+?\?>/,
	'doctype': /<!DOCTYPE[\s\S]+?>/i,
	'cdata': /<!\[CDATA\[[\s\S]*?]]>/i,
	'tag': {
		pattern: /<\/?(?!\d)[^\s>\/=$<]+(?:\s+[^\s>\/=]+(?:=(?:("|')(?:\\[\s\S]|(?!\1)[^\\])*\1|[^\s'">=]+))?)*\s*\/?>/i,
		inside: {
			'tag': {
				pattern: /^<\/?[^\s>\/]+/i,
				inside: {
					'punctuation': /^<\/?/,
					'namespace': /^[^\s>\/:]+:/
				}
			},
			'attr-value': {
				pattern: /=(?:("|')(?:\\[\s\S]|(?!\1)[^\\])*\1|[^\s'">=]+)/i,
				inside: {
					'punctuation': [
						/^=/,
						{
							pattern: /(^|[^\\])["']/,
							lookbehind: true
						}
					]
				}
			},
			'punctuation': /\/?>/,
			'attr-name': {
				pattern: /[^\s>\/]+/,
				inside: {
					'namespace': /^[^\s>\/:]+:/
				}
			}

		}
	},
	'entity': /&#?[\da-z]{1,8};/i
};

wncd.externals.Prism.languages.markup['tag'].inside['attr-value'].inside['entity'] =
	wncd.externals.Prism.languages.markup['entity'];

// Plugin to make entity title show the real entity, idea by Roman Komarov
wncd.externals.Prism.hooks.add('wrap', function(env) {

	if (env.type === 'entity') {
		env.attributes['title'] = env.content.replace(/&amp;/, '&');
	}
});

wncd.externals.Prism.languages.xml = wncd.externals.Prism.languages.markup;
wncd.externals.Prism.languages.html = wncd.externals.Prism.languages.markup;
wncd.externals.Prism.languages.mathml = wncd.externals.Prism.languages.markup;
wncd.externals.Prism.languages.svg = wncd.externals.Prism.languages.markup;

wncd.externals.Prism.languages.css = {
	'comment': /\/\*[\s\S]*?\*\//,
	'atrule': {
		pattern: /@[\w-]+?.*?(?:;|(?=\s*\{))/i,
		inside: {
			'rule': /@[\w-]+/
			// See rest below
		}
	},
	'url': /url\((?:(["'])(?:\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1|.*?)\)/i,
	'selector': /[^{}\s][^{};]*?(?=\s*\{)/,
	'string': {
		pattern: /("|')(?:\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1/,
		greedy: true
	},
	'property': /[-_a-z\xA0-\uFFFF][-\w\xA0-\uFFFF]*(?=\s*:)/i,
	'important': /\B!important\b/i,
	'function': /[-a-z0-9]+(?=\()/i,
	'punctuation': /[(){};:]/
};

wncd.externals.Prism.languages.css['atrule'].inside.rest = wncd.externals.Prism.util.clone(wncd.externals.Prism.languages.css);

if (wncd.externals.Prism.languages.markup) {
	wncd.externals.Prism.languages.insertBefore('markup', 'tag', {
		'style': {
			pattern: /(<style[\s\S]*?>)[\s\S]*?(?=<\/style>)/i,
			lookbehind: true,
			inside: wncd.externals.Prism.languages.css,
			alias: 'language-css',
			greedy: true
		}
	});

	wncd.externals.Prism.languages.insertBefore('inside', 'attr-value', {
		'style-attr': {
			pattern: /\s*style=("|')(?:\\[\s\S]|(?!\1)[^\\])*\1/i,
			inside: {
				'attr-name': {
					pattern: /^\s*style/i,
					inside: wncd.externals.Prism.languages.markup.tag.inside
				},
				'punctuation': /^\s*=\s*['"]|['"]\s*$/,
				'attr-value': {
					pattern: /.+/i,
					inside: wncd.externals.Prism.languages.css
				}
			},
			alias: 'language-css'
		}
	}, wncd.externals.Prism.languages.markup.tag);
};
wncd.externals.Prism.languages.clike = {
	'comment': [
		{
			pattern: /(^|[^\\])\/\*[\s\S]*?(?:\*\/|$)/,
			lookbehind: true
		},
		{
			pattern: /(^|[^\\:])\/\/.*/,
			lookbehind: true
		}
	],
	'string': {
		pattern: /(["'])(?:\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1/,
		greedy: true
	},
	'class-name': {
		pattern: /((?:\b(?:class|interface|extends|implements|trait|instanceof|new)\s+)|(?:catch\s+\())[\w.\\]+/i,
		lookbehind: true,
		inside: {
			punctuation: /[.\\]/
		}
	},
	'keyword': /\b(?:if|else|while|do|for|return|in|instanceof|function|new|try|throw|catch|finally|null|break|continue)\b/,
	'boolean': /\b(?:true|false)\b/,
	'function': /[a-z0-9_]+(?=\()/i,
	'number': /\b-?(?:0x[\da-f]+|\d*\.?\d+(?:e[+-]?\d+)?)\b/i,
	'operator': /--?|\+\+?|!=?=?|<=?|>=?|==?=?|&&?|\|\|?|\?|\*|\/|~|\^|%/,
	'punctuation': /[{}[\];(),.:]/
};

wncd.externals.Prism.languages.javascript = wncd.externals.Prism.languages.extend('clike', {
	'keyword': /\b(?:as|async|await|break|case|catch|class|const|continue|debugger|default|delete|do|else|enum|export|extends|finally|for|from|function|get|if|implements|import|in|instanceof|interface|let|new|null|of|package|private|protected|public|return|set|static|super|switch|this|throw|try|typeof|var|void|while|with|yield)\b/,
	'number': /\b-?(?:0[xX][\dA-Fa-f]+|0[bB][01]+|0[oO][0-7]+|\d*\.?\d+(?:[Ee][+-]?\d+)?|NaN|Infinity)\b/,
	// Allow for all non-ASCII characters (See http://stackoverflow.com/a/2008444)
	'function': /[_$a-z\xA0-\uFFFF][$\w\xA0-\uFFFF]*(?=\s*\()/i,
	'operator': /-[-=]?|\+[+=]?|!=?=?|<<?=?|>>?>?=?|=(?:==?|>)?|&[&=]?|\|[|=]?|\*\*?=?|\/=?|~|\^=?|%=?|\?|\.{3}/
});

wncd.externals.Prism.languages.insertBefore('javascript', 'keyword', {
	'regex': {
		pattern: /(^|[^/])\/(?!\/)(\[[^\]\r\n]+]|\\.|[^/\\\[\r\n])+\/[gimyu]{0,5}(?=\s*($|[\r\n,.;})]))/,
		lookbehind: true,
		greedy: true
	},
	// This must be declared before keyword because we use "function" inside the look-forward
	'function-variable': {
		pattern: /[_$a-z\xA0-\uFFFF][$\w\xA0-\uFFFF]*(?=\s*=\s*(?:function\b|(?:\([^()]*\)|[_$a-z\xA0-\uFFFF][$\w\xA0-\uFFFF]*)\s*=>))/i,
		alias: 'function'
	}
});

wncd.externals.Prism.languages.insertBefore('javascript', 'string', {
	'template-string': {
		pattern: /`(?:\\[\s\S]|[^\\`])*`/,
		greedy: true,
		inside: {
			'interpolation': {
				pattern: /\$\{[^}]+\}/,
				inside: {
					'interpolation-punctuation': {
						pattern: /^\$\{|\}$/,
						alias: 'punctuation'
					},
					rest: wncd.externals.Prism.languages.javascript
				}
			},
			'string': /[\s\S]+/
		}
	}
});

if (wncd.externals.Prism.languages.markup) {
	wncd.externals.Prism.languages.insertBefore('markup', 'tag', {
		'script': {
			pattern: /(<script[\s\S]*?>)[\s\S]*?(?=<\/script>)/i,
			lookbehind: true,
			inside: wncd.externals.Prism.languages.javascript,
			alias: 'language-javascript',
			greedy: true
		}
	});
}

wncd.externals.Prism.languages.js = wncd.externals.Prism.languages.javascript;

/*! END OF PRISM http://prismjs.com */ 
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
 *  @copyright  Copyright (c) 2016-2017  Wigii.org
 *  @author     <http://www.wigii.org/system/libs>      Wigii.org 
 *  @link       <http://www.wigii-system.net>     		<https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     		GNU General Public License
 */
 
 /*!
  * Wigii Natural Code Development (NCD) standard library
  * Created by Camille Weber (camille@wigii.org), 15.11.2017
  */
 
/**
 * Object doc component. Builds the documentation model of a given object and optionally renders it into a given HtmlEmitter
 *@param Object obj a javascript object for which to introspect the documentation model
 *@param Object options a set of options to configure the behavior of the ObjectDoc component. It supports the following attributes :
 * - docOutput: HtmlEmitter|JQuery. A reference of where to output the documentation. Can be an open HtmlEmitter or a JQuery selector. 
 * - If not defined, outputs to current div. To prevent rendering HTML, set the noOutput option to true.
 * - noOutput: Boolean. If true, no HTML is rendered, only the internal documentation model is built.
 * - noSorting: Boolean. If true, the object members are not alphabetically sorted. Order is kept as in the source code.
 * - expandLevel: Integer. Max level of automatic expansion. For deeper members, expands only on demand, one level at a time. Default to 2 (lib->class->members)
 * - namespace: String. Optional library name which represents the object or to which the object belongs.
 * - className: String. Optional class name describing the object instance.
 * - nullEmitter: HtmlEmitter. An HtmlEmitter which is invisible and used to simulated object constructions. By default points to div with id 'nullEmitter'.
 * - privateValues: Map. A list of member names for which its value is considered as private and cannot be viewed or expanded. Only member name can be visualized.
 *
 * Type system language model :
 *
 * Variable = Name Type+
 * Function = Name Variable* Type+
 * Class = Name Variable* Function* Classes*
 * Type = [Namespace.]Name
 *
 * nb. Variables can have several types and function return type can be multiple.
 * Multiple type is represented in the source comments as a disjunction of types (using the vertical bar |).
 * 
 * Type system object model :
 *
 * Variable {
 *	 name: String. Variable name.
 *	 type: String|Map. Type qualified name or map of type names.
 *   value: Any. Value of the variable or constant.
 *	 attributes: Map. A map of attributes for chaining.
 *	 className: String. Optional class name to which this variable belongs.
 * 	 namespace: String. Optional library name to which this variable belongs.
 *	 qualifiedName = [namespace.][className.]name
 *   modelType = Variable
 * }
 *
 * Function {
 * 	 name: String. Function name.
 *	 args: Array. Array of variables.
 *	 returnType: String|Map. Return type qualified name or map of return types.
 *	 attributes: Map. A map of attributes for chaining.
 *	 className: String. Optional class name to which this function belongs.
 * 	 namespace: String. Optional library name to which this function belongs.
 *	 qualifiedName = [namespace.][className.]name
 * 	 modelType = Function
 * }
 *
 * Class {
 *	 name: String. The class name.
 *	 vars: Map. Map of instance variables.
 *	 methods: Map. Map of Functions representing instance methods.
 *	 innerClasses: Map. Map of inner classes.
 *	 namespace: String. Optional library name to which this class belongs.
 *   qualifiedName = [namespace.]name
 *	 modelType = Class
 * }
 *
 * Lib {
 * 	 namespace: String. The library name.
 *	 members: Map. Map of library members (classes, functions, variables)
 *	 modelType = Lib
 * }
 *
 * DocModel {
 * 	 libs: Map. Map of libraries
 * 	 members: Map. Map of qualified classes, functions and variables.
 * }
 */
wncd.ObjectDoc = function(obj,options) {
	var self = this;
	self.className = 'ObjectDoc';
	self.instantiationTime = Date.now();
	self.ctxKey = wncd.ctxKey+'_'+self.className+self.instantiationTime;
	self.options = options || {};
	self.context = {
		stack:[],
		docModel:{libs:{},members:{}}
	};
	self.impl = {};
			
	// Defines output options
	if(!self.options.noOutput) {
		if(!self.options.docOutput) self.options.docOutput = wncd.currentDiv();
		else if($.type(self.options.docOutput)==='string' || self.options.docOutput.className != 'HtmlEmitter') {
			self.options.docOutput = wncd.html(self.options.docOutput);
		}
		self.$ = function() {return self.options.docOutput.$();};
	}
	// Defines default options
	if(self.options.expandLevel === undefined) self.options.expandLevel = 1;
	if(!self.options.nullEmitter) self.options.nullEmitter = wncd.div("nullEmitter");
	if(!self.options.privateValues) self.options.privateValues = {}
	// Private values management
	self.options.privateValues.id = true;
	self.options.privateValues.ctxKey = true;
	self.options.privateValues.instantiationTime = true;
	self.options.privateValues.className = true;
	self.options.privateValues.$ = true;
	if(self.options.privateValues.context===undefined) self.options.privateValues.context = true;
	if(self.options.privateValues.impl===undefined) self.options.privateValues.impl = true;
	if(self.options.privateValues.options===undefined) self.options.privateValues.options = true;
	
	
	if(!self.options.onMemberCreation) {
		/**
		 * Callback when a member documentation model is created
		 *@param Object member member doc model. One of Class, Function, Variable.
		 *@param Object objModel doc model of object containing the member. One of Lib or Class.
		 *@param Object options map of options
		 *@param Object context current runtime context
		 *@param int nestingLevel current level of nesting. Start is 1.
		 */
		self.options.onMemberCreation = function(member,objModel,options,context,nestingLevel) {
			if(!options.noOutput) options.renderMember(member,objModel,options,context,nestingLevel);
		}
	}
	if(!self.options.renderMember) {
		/**
		 * Renders a member documentation model
		 *@param Object member member doc model. One of Class, Function, Variable.
		 *@param Object objModel doc model of object containing the member. One of Lib or Class.
		 *@param Object options map of options
		 *@param Object context current runtime context
		 *@param int nestingLevel current level of nesting. Start is 1.
		 */
		self.options.renderMember = function(member,objModel,options,context,nestingLevel) {
			var memberDiv = objModel.context.docOutput;
			if(!memberDiv) memberDiv = options.docOutput;
			memberDiv = memberDiv.div(member.uri, "method");						
			
			// display expand button			
			if(member.context.expandable) {
				memberDiv.out("+","methodExpand");
			}
			// adds class keyword
			if(member.modelType === 'Class') {
				memberDiv.out("class","classKeyword keyword");
			}
			// adds function keyword
			else if(member.modelType === 'Function') {
				memberDiv.out("function","functionKeyword keyword");
			}
			
			// displays member name
			memberDiv.out(member.name,"methodName");
			
			// follows with parameters	
			if(member.context.args) {
				memberDiv.out(" ").out(member.context.args);			
				// if src code then display an expand src button
				if(member.context.srcCode && !member.context.expandable) {
					memberDiv.out("+","methodSrcExpand");
					wncd.bindSelectionSense(memberDiv.$().find('span.methodSrcExpand'),function(selectionSense){
						if(selectionSense.selected()) memberDiv.$().find('div.methodSrc').show();
						else memberDiv.$().find('div.methodSrc').hide();
					});	
				}
				// if class then display complete class src
				else if(member.context.srcCode && member.modelType === 'Class') {
					memberDiv.out("+","classSrcExpand");
					wncd.bindSelectionSense(memberDiv.$().find('span.classSrcExpand'),function(selectionSense){
						if(selectionSense.selected()) memberDiv.$().find('div.classSrc').show();
						else memberDiv.$().find('div.classSrc').hide();
					});	
				}
			}
			// displays value type
			else {
				// if string or number and not empty and not private, then displays value
				if((member.context.objectType === 'number' || member.context.objectType === 'string') 
					&& !options.privateValues[member.name] 
					&& member.context.object) {
					memberDiv.out(":","typeAssignement").out(member.context.object, "scalarValue");
				}
				else if(member.context.object === options.nullEmitter) memberDiv.out(":","typeAssignement").out('undefined', "keyword");
				else memberDiv.out(":","typeAssignement").out(member.context.objectType, "keyword");
			}
			memberDiv.out(" ","methodEnd");
			
			// displays class src code
			if(member.context.srcCode && member.modelType === 'Class') {				
				memberDiv.htmlBuilder().tag("div","class","classSrc").insert(options.renderClassSrc,member.context.srcCode).$tag("div").emit();				
			}
			
			// expands recursively until expandLevel, then only on click				
			if(member.context.expandable) {
				var selectionSense = wncd.bindSelectionSense(memberDiv.$().find('span.methodExpand'),function(selectionSense){
					if(selectionSense.selected()) {
						// if expand token exists, then expands into method members container.
						if(member.context.expand) member.context.expand();
						// shows method members
						memberDiv.$().find('div.methodMembers').show();
					}
					else {
						memberDiv.$().find('div.methodMembers').hide();
					}
				});
				// prepares method members container and prepares HtmlEmitter on it
				memberDiv.htmlBuilder().tag("div","class","methodMembers").$tag("div").emit();
				member.context.docOutput = memberDiv.clone(memberDiv.$().find('div.methodMembers'));
				
				// already marks as expanded
				if(nestingLevel < options.expandLevel) selectionSense.selected(true);
				// keeps selection sense into context to allow piloting the tree view
				member.context.selectionSense = selectionSense;
			}			
			// else displays member src code
			else if(member.context.srcCode) {		
				memberDiv.htmlBuilder().tag("div","class","methodSrc").insert(options.renderMemberSrc,member.context.srcCode,member.context.comment).$tag("div").emit();				
			}		
		}
	}
	
	if(!self.options.renderMemberSrc) self.options.renderMemberSrc = function(srcCode,comment) {
		var Prism = wncd.externals.Prism;		
		// Normalize indentation
		srcCode = self.impl.normalizeSrcIndentation(srcCode);
		// Prepends comments
		srcCode = (comment?self.impl.normalizeCommentIndentation(comment)+"\n":"")+srcCode;
		// Highlight syntax
		wncd.currentDiv().htmlBuilder()
			.tag('pre').tag('code','class','language-js')
				.put(Prism.highlight(srcCode,Prism.languages.js))
			.$tag('code').$tag('pre')
		.emit();
	}
	
	if(!self.options.renderClassSrc) self.options.renderClassSrc = function(srcCode,comment) {
		var Prism = wncd.externals.Prism;
		// Normalize indentation
		srcCode = self.impl.normalizeSrcIndentation(srcCode);
		// Prepends comments
		srcCode = (comment?self.impl.normalizeCommentIndentation(comment)+"\n":"")+srcCode;
		// Highlight syntax
		wncd.currentDiv().htmlBuilder()
			.tag('pre').tag('code','class','language-js')
				.put(Prism.highlight(srcCode,Prism.languages.js))
			.$tag('code').$tag('pre')
		.emit();
	}
	
	// Implementation helpers
	
	self.impl.qualifier2uri = function(namespace,className,methodName) {
		var returnValue = '';
		if(namespace) returnValue += namespace.replace('.','__');
		if(className) {
			if(returnValue) returnValue += '__';
			returnValue += className;
		}
		if(methodName) {
			if(returnValue) returnValue += '__';
			returnValue += methodName;
		}
		return returnValue.replace('$','S');
	}
	self.impl.buildQualifiedName = function(namespace,className,methodName) {
		var returnValue = '';
		if(namespace) returnValue += namespace;
		if(className) {
			if(returnValue) returnValue += '.';
			returnValue += className;
		}
		if(methodName) {
			if(returnValue) returnValue += '.';
			returnValue += methodName;
		}
		return returnValue;
	}
	self.impl.extractClassMemberComments = function(classSrcCode) {
		var returnValue = {};
		var commentsRegExp = /(\/\*\*([^*]|[\r\n]|(\*+([^*\/]|[\r\n])))*\*+\/)\s*([\w]+)[.]([\w.]+)\s*=/g;
		var matches = undefined;
		while((matches = commentsRegExp.exec(classSrcCode))!==null){
			//group 1 is comment, group 5 is class name or 'self', group 6 is method name
			returnValue[matches[6]] = matches[1];
			returnValue.className = matches[5];
		}
		return returnValue;
	};
	self.impl.createObjModel = function(namespace,className,name) {
		var returnValue = {};
		if(namespace) returnValue.namespace = namespace;
		if(className) returnValue.className = className;
		if(name) returnValue.name = name;
		returnValue.uri = self.impl.qualifier2uri(namespace,className,name);
		returnValue.qualifiedName = self.impl.buildQualifiedName(namespace,className,name);
		returnValue.context = {};
		return returnValue;
	};
	self.impl.initClassModel = function(objModel) {
		objModel.vars = {};
		objModel.methods = {};
		objModel.innerClasses = {};
		objModel.modelType = 'Class';
		objModel.parent = undefined;
	};
	self.impl.initFunctionModel = function(objModel) {
		objModel.args = [];
		objModel.returnType = undefined;
		objModel.attributes = {};
		objModel.modelType = 'Function';
		objModel.parent = undefined;
	};
	self.impl.initVariableModel = function(objModel) {
		objModel.type = undefined;
		objModel.value = undefined;
		objModel.attributes = {};
		objModel.modelType = 'Variable';
		objModel.parent = undefined;
	};
	self.impl.initLibModel = function(objModel) {
		objModel.members = {};			
		objModel.modelType = 'Lib';
	};
	self.impl.normalizeSrcIndentation = function(srcCode) {
		// searches for indentation block (takes last pre indentation before closing bracket).
		var indentationRegExp = /[\n\r]([\t ]*)}$/g;
		var matches = indentationRegExp.exec(srcCode);
		var indentation = (matches? matches[1]:'');
		// adds indentation before first line of code
		var returnValue = indentation+srcCode;
		// removes extra indentation on all lines
		if(indentation.length>0) {
			indentationRegExp = new RegExp('^\\s{0,'+(indentation.length+1)+'}','gm');
			returnValue = returnValue.replace(indentationRegExp,'');
		}
		return returnValue;
	};
	self.impl.normalizeCommentIndentation = function(comment) {
		// searches for indentation block (takes last pre indentation before closing comment).
		var indentationRegExp = /[\n\r]([\t ]*)\*\/$/g;
		var matches = indentationRegExp.exec(comment);
		var indentation = (matches? matches[1]:'');
		// adds indentation before first line of comment (one char shorter to correctly align comment)
		var returnValue = indentation.substr(1)+comment;
		// removes extra indentation on all lines
		if(indentation.length > 0) {
			indentationRegExp = new RegExp('^\\s{0,'+(indentation.length)+'}','gm');
			returnValue = returnValue.replace(indentationRegExp,'');
		}
		return returnValue;
	};
	
	// Visitors
	
	self.impl.visitObj = function(obj,options,context,nestingLevel) {
		var objModel = context.stack[nestingLevel-1];
		
		// fills an array with all members
		var members = [];
		for(var memberName in obj) {
			var member = self.impl.createObjModel(objModel.namespace,(objModel.modelType === 'Class'?objModel.name:objModel.className),memberName);
			member.context.object = obj[memberName];
			members.push(member);
		}
		
		// sorts by name
		if(!options.noSorting) {
			members.sort(function(m1,m2){
				if(m1.name < m2.name) return -1;
				else if(m1.name > m2.name) return 1;
				else return 0;
			});
		}
		
		// visits members
		if(options.expandLevel > 0) {
			for(var i=0;i<members.length;i++) {
				var member = members[i];					
				self.impl.visitObjMember(member,options,context,nestingLevel);			
			}
		}					
		
		// removes model from stack
		self.context.stack.pop();
	};
	self.impl.visitObjMember = function(member,options,context,nestingLevel) {
		var objModel = context.stack[nestingLevel-1];
		
		// discovers member information and sets local variables
		var objectType = $.type(member.context.object);	
		var srcCode = undefined;
		var args = undefined;
		var expandable = false;
		var isObjectConstructor = false;
		var classMemberComments = undefined;
		
		if(objectType === 'function') {				
			srcCode = wncd.obj2FxString(member.context.object);
			args = srcCode.match(/^function\s*(\([^\(\)]*\))/);
			if(args) args = args[1];				
			
			// checks if member is an object constructor 
			// - is a Function 
			// - has first line equal to var self = this;
			isObjectConstructor = /^function\s*\([^\(\)]*\)\s*\{\s*var\s*self\s*=\s*this;/.test(srcCode);
			// extracts members comments
			classMemberComments = self.impl.extractClassMemberComments(srcCode);
			// runs constructor (safely)
			if(isObjectConstructor) {					
				var newObj = {};
				var constructorArgs = Array(member.context.object.length).fill(options.nullEmitter);
				var currentDiv = wncd.currentDiv();
				try {
					wncd.program.context.html(options.nullEmitter);
					member.context.object.apply(newObj, constructorArgs);
					wncd.program.context.html(currentDiv);
					options.nullEmitter.reset();
				}
				catch(exc) {wncd.program.context.html(currentDiv);}					
				if(!newObj.className) newObj.className = member.name;
				member.context.object = newObj;
			}
			expandable = (Object.keys(member.context.object).length > 0);				
		}
		else if(objectType === 'object' && member.context.object !== options.nullEmitter) {
			expandable = (Object.keys(member.context.object).length > 0);
		}
		
		// Is member a class
		if(isObjectConstructor) {
			self.impl.initClassModel(member);
			member.context.srcCode = srcCode;
			member.context.args = args;				
			member.context.classMemberComments = classMemberComments;
		}
		// Is member a function
		else if(objectType === 'function') {
			self.impl.initFunctionModel(member);
			member.context.srcCode = srcCode;
			member.context.args = args;
		}
		// Is member a variable
		else {
			self.impl.initVariableModel(member);
			member.context.objectType = objectType;
		}
		member.context.expandable = expandable;
		// Links associated comment to member
		if(objModel.context.classMemberComments) member.context.comment = objModel.context.classMemberComments[member.name];
		
		// blocks recursion for private values
		if(options.privateValues[member.name]) member.context.expandable = false;
		
		// Registers member into objModel
		if(objModel.modelType === 'Lib') objModel.members[member.name] = member;
		else if(objModel.modelType === 'Class') {
			if(member.modelType === 'Variable') objModel.vars[member.name] = member;
			else if(member.modelType === 'Function') objModel.methods[member.name] = member;
			else if(member.modelType === 'Class') {
				// if an inner class, then updates namespace value with parent qualifier
				member.namespace = objModel.qualifiedName;
				member.qualifiedName = self.impl.buildQualifiedName(member.namespace,member.name);
				member.uri = self.impl.qualifier2uri(objModel.uri,member.name);
				objModel.innerClasses[member.name] = member;
			}
		}
		else if(objModel.modelType === 'Function') {
			// if chaining attribute, then updates namespace value with parent qualifier
			member.namespace = objModel.qualifiedName;
			member.qualifiedName = self.impl.buildQualifiedName(member.namespace,member.name);
			member.uri = self.impl.qualifier2uri(objModel.uri,member.name);
			objModel.attributes[member.name] = member;
		}
		else if(objModel.modelType === 'Variable') {
			// if chaining attribute, then updates namespace value with parent qualifier
			member.namespace = objModel.qualifiedName;
			member.qualifiedName = self.impl.buildQualifiedName(member.namespace,member.name);
			member.uri = self.impl.qualifier2uri(objModel.uri,member.name);
			objModel.attributes[member.name] = member;
		}
		// indexes into docModel.members map
		context.docModel.members[member.qualifiedName] = member;
		// links member back to its parent
		member.parent = objModel;
		
		// creates a consumable 'expand' token which launches the recursion on demand				
		if(member.context.expandable) {
			member.context.expand = function() {
				member.context.expand = undefined;
				context.stack.push(member);
				self.impl.visitObj(member.context.object,options,context,context.stack.length);
			};				
		}		
	
		// onMemberCreation callback
		if(options.onMemberCreation) options.onMemberCreation(member,objModel,options,context,nestingLevel);
		
		// expands recursively until expandLevel
		if(nestingLevel < options.expandLevel && member.context.expand) member.context.expand();
	};
	
	/**
	 * Builds the documentation model of the given object and adds it to the current model
	 *@param Object obj the object for which to build the documentation model
	 *@param String namespace optional library name which represents the object or to which the object belongs.
	 *@param String className optional class name describing the object instance
	 *@return Object returns the object documentation model
	 */
	self.buildDocModel = function(obj, namespace, className) {
		if(obj) {
			// creates objModel and initializes it as a Class or a Lib
			var objModel = self.impl.createObjModel(namespace,undefined,className);
			if(className) self.impl.initClassModel(objModel);
			else self.impl.initLibModel(objModel);
			// pushes objModel on stack and visits object.
			self.context.stack.push(objModel);
			self.impl.visitObj(obj,self.options,self.context,1);				
			// updates docModel with built model	
			if(objModel.modelType === 'Lib') self.context.docModel.libs[objModel.namespace] = objModel;
			self.context.docModel.members[objModel.qualifiedName] = objModel;
		}
		return self.context.docModel;
	};
	
	// Builds the object documentation model
	self.buildDocModel(obj,self.options.namespace, self.options.className);
};	
/**
 * Creates an object documentation model on the given object
 */
wncd.createObjectDoc = function(obj,options) { 
	return new wncd.ObjectDoc(obj,options);
};	
 
/**
 * Builds a contextual menu and attaches it to a given anchor
 *@param jQuery|DOM.Element anchor the element to which attach the contextual menu
 *@param Array|Function compose an array of menu items or a function which composes the menu items.
 * The compose function has the following signature compose(n,subMenu,list):String|Object|wncd.ContextualMenu.SubMenu where
 * - n: Int. The number of already created items (the first time, n equals 0)
 * - subMenu: Function. A symbol helping creating sub-menus. 
 * - list: wncd.UnorderedList. The underlying list of items composing the menu.
 * returns a String|Object representing the clickable menu item or an instance of wncd.ContextualMenu.SubMenu for a sub-menu.
 *@param Object options a map of options
 * - cssClass: a CSS class name to attach to this contextual menu
 * - labelField: String. If compose is an array of objects, then defines the object field containing the label of the menu item. Defaults to 'label'.
 * - subMenuField: String. If compose is an array of objects, then defines the object field containing the sub menu. Defaults to 'subMenu'.
 * - rightMenuLabel: String. The character opening a submenu on the right. Defaults to right triangle.
 *@style to style the menu define for example
 * div.contextualMenu ul {
 * 	background-color:#ccccff;
 * 	border: 1px solid #3333ff;
 * 	border-radius: 5px;
 * }
 * div.contextualMenu ul li:hover {
 * 	background-color: #3333ff;
 * }
 * div.contextualMenu span.rightMenu, div.contextualMenu span.noMenu {
 *	color:#000099;
 *}
 */
wncd.ContextualMenu = function(anchor, compose, options) {
	var self = this;
	self.className = 'ContextualMenu';
	self.instantiationTime = Date.now();
	self.ctxKey = wncd.ctxKey+'_'+self.className+self.instantiationTime;
	self.options = options || {};
	self.context = {
		isVisible:false,
		rootMenu: self,
		openMenu: undefined
	};
	self.impl = {};
	
	/**
	 * Sub menu object
	 *@param wncd.ContextualMenu|wncd.ContextualMenu.SubMenu parent parent menu to which this sub-menu belongs
	 *@param String label the label of the item launching the sub menu
	 *@param Array|Function compose an array of menu items or a function which composes the sub menu items
	 *@param Object options a map of options
	 */
	self.impl.SubMenu = function(parent,label,compose,options) {
		var self = this;
		self.className = 'SubMenu';
		self.instantiationTime = Date.now();
		self.ctxKey = wncd.ctxKey+'_'+self.className+self.instantiationTime;
		self.options = Object.assign({},parent.options,options);
		self.context = {
			isVisible:false,
			rootMenu: parent.context.rootMenu,
			openMenu:undefined,
			parent: parent,
			label: label
		};
		self.impl = {};
					
		// Implementation
		
		self.impl.createSubMenu = function(label,compose,options) {
			return new self.context.rootMenu.impl.SubMenu(self,label,compose,options);
		};
		if($.isFunction(compose)) self.impl.compose = function(n,list){
			var returnValue = compose(n,self.impl.createSubMenu,list);
			// if SubMenu, then ensures ctxKey is unique.
			if($.type(returnValue) === 'object' && returnValue.className == 'SubMenu') {
				returnValue.ctxKey += n;
				returnValue.context.itemNumber = n+1;
			}
			return returnValue;
		}
		else if($.isArray(compose)) {
			self.impl.compose = function(n,list) {
				if(n<compose.length) {
					var returnValue = compose[n];
					// if array item encodes a sub menu then returns a sub menu instance
					if($.type(returnValue) === 'object' && returnValue[self.options.subMenuField]) {
						returnValue = self.impl.createSubMenu(returnValue[self.options.labelField],returnValue[self.options.subMenuField],self.options);
						// ensures ctxKey is unique.
						returnValue.ctxKey += n;
						returnValue.context.itemNumber = n+1;
					}
					return returnValue;
				}
			}
		}
		else throw wncd.createServiceException("compose should be an array or a function",wncd.errorCodes.INVALID_ARGUMENT);

		// Properties
	
		self.$ = function() {return $('#'+self.ctxKey);}
		
		// Methods
		
		/**
		 * Shows the contextual menu
		 */
		self.show = function() {
			// builds menu if not done yet
			if(!self.context.list) {
				self.context.list = wncd.html(self.$()).list(self.impl.compose, self.options.render, self.options);
				self.context.list.context.menu = self;		
				// positions the menu
				self.options.top = self.$().prev().position().top;
				self.options.positionMenu(self.context.parent,self.options);
				self.$().css('top',self.options.top).css('left',self.options.left);
			}
			// then shows it
			if(!self.context.isVisible) {
				self.$().show();
				self.context.isVisible = true;
			}
		};
		/**
		 * Hides the contextual menu
		 *@param Boolean cascadeUp if true then hiding this subMenu will also hide all parent sub-menus and also root menu.
		 */
		self.hide = function(cascadeUp) {				
			if(self.context.isVisible) {
				// hides any open menu
				if(self.context.openMenu) {
					self.context.openMenu.hide();
					self.context.openMenu = undefined;
				}
				// hides menu
				self.$().hide();
				self.context.isVisible = false;
				if(cascadeUp && self.context.parent) self.context.parent.hide(cascadeUp);
			}
		};
		/**
		 * Toggles the visibility of the contextual menu
		 */
		self.toggle = function() {
			if(self.context.isVisible) self.hide();
			else self.show();
		};
	};
	self.impl.createSubMenu = function(label,compose,options) {
		return new self.impl.SubMenu(self,label,compose,options);
	};
	
	// Define default options
	
	if(!self.options.labelField) self.options.labelField = 'label';
	if(!self.options.subMenuField) self.options.subMenuField = 'subMenu';
	if($.isFunction(compose)) self.impl.compose = function(n,list){
		var returnValue = compose(n,self.impl.createSubMenu,list);
		// if SubMenu, then ensures ctxKey is unique.
		if($.type(returnValue) === 'object' && returnValue.className == 'SubMenu') {
			returnValue.ctxKey += n;
			returnValue.context.itemNumber = n+1;
		}
		return returnValue;
	};
	else if($.isArray(compose)) {
		self.impl.compose = function(n,list) {
			if(n<compose.length) {
				var returnValue = compose[n];
				// if array item encodes a sub menu then returns a sub menu instance
				if($.type(returnValue) === 'object' && returnValue[self.options.subMenuField]) {
					returnValue = self.impl.createSubMenu(returnValue[self.options.labelField],returnValue[self.options.subMenuField],self.options);
					// ensures ctxKey is unique.
					returnValue.ctxKey += n;
					returnValue.context.itemNumber = n+1;
				}
				return returnValue;
			}
		}
	}
	else throw wncd.createServiceException("compose should be an array or a function",wncd.errorCodes.INVALID_ARGUMENT);
	self.options.maxSelection = 1;
	if(!self.options.rightMenuLabel) self.options.rightMenuLabel = "&#9654;"
	
	/**
	 * Positions the contextual menu relative to its anchor
	 *@param Object options a bag of options in which to add the top, left attributes.
	 */
	if(!self.options.positionMenu) self.options.positionMenu = function(parentAnchor,options) {
		if(parentAnchor.className == 'ContextualMenu' || parentAnchor.className == 'SubMenu') {
			var div = parentAnchor.$();
			options.left = div.width();
		}
		else {		
			var anchorPos = parentAnchor.position();
			options.top = anchorPos.top + (options.top ? options.top : 0);
			options.left = anchorPos.left+ (options.left ? options.left : 0);
		}			
	};
	/**
	 * Builds the html of the contextual menu panel
	 *@return String valid html representing the contextual menu panel.
	 */
	if(!self.options.buildMenuPanel) self.options.buildMenuPanel = function(id, options) {
		var style = 'position:absolute;display:none;';
		if(options.top) style += 'top:'+Math.ceil(options.top)+'px;';
		if(options.left) style += 'left:'+Math.ceil(options.left)+'px;';
		return wncd.getHtmlBuilder().tag('div',
			'id',id,
			'class',"contextualMenu"+(options.cssClass?' '+options.cssClass:''),
			'style',style
		).$tag('div').html();
	};
	/**
	 * Renders a menu item
	 *@return String|Object plain HTML or an object with some html code and html attributes
	 */
	if(!self.options.render) self.options.render = function(i,menuItem,list) {
		var options = list.options;
		// if a SubMenu, renders label and creates sub menu panel
		if($.type(menuItem) === 'object' && menuItem.className == 'SubMenu') {
			var style = 'position:absolute;display:none;';
			if(options.top) style += 'top:'+Math.ceil(options.top)+'px;';
			if(options.left) style += 'left:'+Math.ceil(options.left)+'px;';
			return wncd.getHtmlBuilder().tag('span','class','label').out(menuItem.context.label).$tag('span')
			.tag('span','class','rightMenu').out(self.options.rightMenuLabel).$tag('span')
			.tag('div','id',menuItem.ctxKey,'style',style).$tag('div')
			.html();
		}
		// else if a label/value pair, displays the label
		else if($.type(menuItem) === 'object' && menuItem[options.labelField]) {
			return wncd.getHtmlBuilder()
				.tag('span','class','label').out(menuItem[options.labelField]).$tag('span')
				.tag('span','class','noMenu').out("&nbsp;").$tag('span')
				.html();
		}
		// else assumes its a label
		else return wncd.getHtmlBuilder()
			.tag('span','class','label').out(menuItem).$tag('span')
			.tag('span','class','noMenu').out("&nbsp;").$tag('span')
			.html();
	};

	// keeps customer onItemClick for chaining
	if(self.options.onItemClick) self.impl.onItemClick = self.options.onItemClick;
	/**
	 * Creates the onItemClick function for the menu
	 */
	self.options.onItemClick = function(i,menuItem,selected,list) {
		// if sub menu, then shows sub menu content
		if($.type(menuItem) === 'object' && menuItem.className == 'SubMenu') {
			if(menuItem.context.isVisible) {
				menuItem.hide();
				list.context.menu.context.openMenu = undefined;
			}
			else {
				if(list.context.menu.context.openMenu) list.context.menu.context.openMenu.hide();
				menuItem.show();
				list.context.menu.context.openMenu = menuItem;
			}
		}
		// else, hides menu and calls custom onItemClick
		else {
			list.context.menu.hide(true);
			var customOnItemClick = list.context.menu.context.rootMenu.impl.onItemClick;
			if($.isFunction(customOnItemClick)) customOnItemClick(i,menuItem,selected,list);
		}
	};
	
	// Properties
	
	self.$ = function() {return $('#'+self.ctxKey);}
	
	// Methods
	
	/**
	 * Shows the contextual menu
	 */
	self.show = function() {
		if(!self.context.isVisible) {
			self.$().show();
			self.context.isVisible = true;
		}
	};
	/**
	 * Hides the contextual menu
	 */
	self.hide = function() {			
		if(self.context.isVisible) {
			// hides any open menu
			if(self.context.openMenu) {					
				self.context.openMenu.hide();
				self.context.openMenu = undefined;
			}
			// hides menu
			self.$().hide();
			self.context.isVisible = false;
		}
	};
	/**
	 * Toggles the visibility of the contextual menu
	 */
	self.toggle = function() {
		if(self.context.isVisible) self.hide();
		else self.show();
	};
	
	// Builds contextual menu
	if(!anchor) throw wncd.createServiceException("anchor should point to an existing DOM element",wncd.errorCodes.INVALID_ARGUMENT);
	anchor = $(anchor);
	// fixes anchor position
	anchor.css('position','relative');
	anchor.parent().css('position','relative').css('overflow','visible');
	// positions the menu
	self.options.positionMenu(anchor,self.options);
	// builds contextual menu container and inserts html code after the anchor
	anchor.after(self.options.buildMenuPanel(self.ctxKey,self.options));
	// creates a list and fills it with the menu
	self.context.list = wncd.html(self.$()).list(self.impl.compose,self.options.render, self.options);
	self.context.list.context.menu = self;
	// registers toggle on click
	anchor.click(self.toggle);
};

/**
 * JQuery NCD plugin binding a contextual menu to a given anchor
 *@return wncd.ContextualMenu
 */
wncd.getJQueryService().menu = function(selection,options) {
	var returnValue=undefined;
	// checks we have only one element
	if(selection && selection.length==1) {
		// extracts composition function or array and bag of options
		var compose = undefined;
		var optionsType = $.type(options);
		if(optionsType==='array' || optionsType==='function') {
			compose = options;
			options = undefined;
		}
		else if(optionsType === 'object') {
			compose = options.compose;				
		}
		// creates a ContextualMenu
		returnValue = new wncd.ContextualMenu(selection,compose,options);
	}
	else if(selection && selection.length>1) throw wncd.createServiceException('Wigii NCD menu selector can only be activated on a JQuery collection containing one element and not '+selection.length, wncd.errorCodes.INVALID_ARGUMENT);
	return (!returnValue?{$:selection}:returnValue);
};

/**
 * A desktop user interface which displays a user menu, a header bar, a workzone and a footer bar.
 * It accepts  to display desktop components which should display a header bar, a workzone and a footer bar.
 * It supports an activate component event and a close event. The activate event is fired when a component is brought to the screen,
 * the close event is fired when a component is brought off the screen.
 *@param Object options a set of options to configure the desktop component. It supports the following attributes :
 * - htmlEmitter: HtmlEmitter|JQuery. A reference of where to render the desktop user interface. Can be an open HtmlEmitter or a JQuery selector.
 * - label: Label used to display the desktop
 * - title: Title displayed in the title bar
 * - logo: HTML img to display a logo
 * - height: height of the desktop in his container. Defaults to 100%
 * - width: width of the desktop in his container. Defaults to 100%
 */
wncd.Desktop = function(options) {
	var self = this;
	self.className = 'Desktop';
	self.instantiationTime = Date.now();
	self.ctxKey = wncd.ctxKey+'_'+self.className;
	self.options = options || {};
	self.context = {
		components: {},
		currentComponent: undefined
	};
	self.impl = {
		onCloseSubscribers:[],
		onActivateSubscribers:[]
	};
	
	// Define default options
	if(!self.options.userMenuLabel) self.options.userMenuLabel = "&#9776;"; //trigram of heaven
	if(!self.options.label) self.options.label = "&#127968;"; //house building
	if(!self.options.title) self.options.title = "&nbsp;";
	self.options.obj = self;
	if(!self.options.height) self.options.height = "100%";
	if(!self.options.width) self.options.width = "100%";
	if(!self.options.logo) self.options.logo = wncd.getHtmlBuilder()
		.tag('img','class','logo','src','https://rise.wigii.org/NCD/CMS/www/etp/logo_wigii_48.gif').$tag('img')
		.html();

	/**
	 *Defines default HtmlEmitter
	 */
	if(!self.options.htmlEmitter) self.options.htmlEmitter = wncd.currentDiv();
	else if($.type(self.options.htmlEmitter)==='string' || self.options.htmlEmitter.className != 'HtmlEmitter') {
		self.options.htmlEmitter = wncd.html(self.options.htmlEmitter);
	}
	
	/**
	 * Defines desktop displayHeaderBar function
	 */
	if(!self.options.displayHeaderBar) self.options.displayHeaderBar = function(desktop) {
		wncd.currentDiv().reset();
	};
	/**
	 * Defines desktop displayWorkzone function
	 */
	if(!self.options.displayWorkzone) self.options.displayWorkzone = function(desktop) {
		wncd.currentDiv().reset();
	};
	/**
	 * Defines desktop displayFooterBar function
	 */
	if(!self.options.displayFooterBar) self.options.displayFooterBar = function(desktop) {			
		var startupLog = '';
		if(program.context.startupLog) {
			if(program.context.startupLog.ncdEtpReady) startupLog += "Wigii NCD core v."+program.context.startupLog.version;
			if(program.context.startupLog.ncdEtpFxReady) startupLog += (startupLog?", ":"")+"Fx layer ready "+wncd.txtDate(new Date());				
		}
		self.context.startupLog = startupLog;
		wncd.currentDiv().reset().out(self.context.startupLog);
	};
	/**
	 * Defines desktop displayUserMenu function
	 */
	if(!self.options.displayUserMenu) self.options.displayUserMenu = function(desktop) {
		// creates the list of available components in a displayable order
		self.context.userMenu = [];
		for(var key in self.context.components) {
			self.context.userMenu.push(key);
		}
		wncd.currentDiv().reset().out(self.options.userMenuLabel,'userMenuButton')
		.$().find('span.userMenuButton').wncd('menu',{
			compose:function(n,subMenu,list) {
				// returns each component in the user menu
				if(n<self.context.userMenu.length) {
					var component = self.context.components[self.context.userMenu[n]];
					// sets the label
					if(component.options && component.options.label) component.label = component.options.label;
					else component.label = component.key;
					return component;
				}
			},
			onItemClick: function(i,component) {self.activate(component.key);},
			top:5,left:20,
			cssClass:'userMenu'
		});			
	};
	/**
	 * Defines desktop layout function
	 */
	if(!self.options.layoutDesktop) self.options.layoutDesktop = function(h,v,x1,x2,x3,x4,x5,x6,x7,x8,x9,x10,desktop) {
		return v(
			x3.h(
				/* user menu */
				x1.h(
					x1(self.options.logo||"&nbsp;"),
					x1().id(desktop.ctxKey+"_userMenu").cssClass("userMenuContainer")
				),
				/* header bar */
				x10.v(
					x1().id(desktop.ctxKey+"_titleBar").cssClass("titleBar"),
					x3().id(desktop.ctxKey+"_headerBar").cssClass("headerBar")
				)
			),
			/* workzone */
			x10().id(desktop.ctxKey+"_workzone").cssClass("workzone"),
			/* footer bar */
			x1().id(desktop.ctxKey+"_footerBar").cssClass("footerBar")
		).cssClass("desktop").width(desktop.options.width).height(desktop.options.height);
	};
	
	/**
	 * Registers a new component that can be displayed on the desktop
	 *@param String key the key under which the component is registered
	 *@param Function displayHeaderBar a function which display the component header bar using wncd.currentDiv() context. It receives the desktop as parameter.
	 *@param Function displayWorkzone a function which display the component workzone using wncd.currentDiv() context. It receives the desktop as parameter.
	 *@param Function displayFooterBar a function which display the component footer bar using wncd.currentDiv() context. It receives the desktop as parameter.
	 *@param Object options some options to configure the component registration. It supports the following attributes :
	 * - label: A label to put into a menu or button to activate the component
	 * - obj: An underlying object instance representing the component itself.
	 * - title: An optional title to display on the top of the desktop
	 *@return wncd.Desktop for chaining
	 */
	self.registerComponent = function(key,
		displayHeaderBar,
		displayWorkzone,
		displayFooterBar, 
		options) {
		
		self.context.components[key] = {
			key: key,
			displayHeaderBar: displayHeaderBar,
			displayWorkzone: displayWorkzone,
			displayFooterBar: displayFooterBar,
			options: options
		};
		
		// displays user menu
		wncd.program.context.html(self.options.userMenuEmitter);
		self.options.displayUserMenu(self);
		
		return self;
	};
	/**
	 * Creates an instance of a desktop component given its class name and registers it into the desktop
	 *@param String className a wncd class which can be registred as a desktop component
	 *@param Objects options an optional map of options to be passed to the component constructor
	 *@return wncd.Desktop for chaining
	 */
	self.add = function(className,options) {
		var componentConstructor = wncd[className];
		if(!$.isFunction(componentConstructor)) throw wncd.createServiceException("Unsupported class "+className,wncd.errorCodes.UNSUPPORTED_OPERATION);
		new componentConstructor(self,options);
		return self;
	};
	
	/**
	 * Activates and brings to the screen a registered component given its key
	 *@return Object returns underlying component object if defined
	 */ 
	self.activate = function(key) {
		if(!key) throw wncd.createServiceException("key cannot be null",wncd.errorCodes.INVALID_ARGUMENT);
		var component = self.context.components[key];
		if(!component) throw wncd.createServiceException("no component registered under key "+key,wncd.errorCodes.INVALID_ARGUMENT);
		// closes previous component
		if(self.context.currentComponent) self.onClose();
		// changes current component key
		self.context.currentComponent = key;
		// activates the component			
		self.onActivate();			
		// Renders component header bar
		self.options.titleBarEmitter.reset().out(component.options.title||'&nbsp;');
		if($.isFunction(component.displayHeaderBar)) {
			wncd.program.context.html(self.options.headerBarEmitter);
			component.displayHeaderBar(self);
		}
		// Renders component workzone			
		if($.isFunction(component.displayWorkzone)) {
			wncd.program.context.html(self.options.workzoneEmitter);
			component.displayWorkzone(self);
		}
		// Renders component footer bar			
		if($.isFunction(component.displayFooterBar)) {
			wncd.program.context.html(self.options.footerBarEmitter);
			component.displayFooterBar(self);			
		}
		// keeps current div on workzone.
		wncd.program.context.html(self.options.workzoneEmitter);
		
		return self.getComponentObject(key);
	};
	/**
	 * If the component has an underlying object, then returns it
	 */
	self.getComponentObject = function(key) {
		if(!key) throw wncd.createServiceException("key cannot be null",wncd.errorCodes.INVALID_ARGUMENT);
		var component = self.context.components[key];
		if(!component) throw wncd.createServiceException("no component registered under key "+key,wncd.errorCodes.INVALID_ARGUMENT);
		if(component.options) return component.options.obj;
	};
	/**
	 * Removes the current component off the screen and displays again the desktop
	 */
	self.closeCurrentComponent = function() {
		self.activate(self.ctxKey);
	};
	/**
	 * Registers an eventHandler which is called when a component is closed and took off the screen.
	 *@param Function closeEventHandler a function which receives the component key which is closed and the desktop
	 */
	self.onClose = function(closeEventHandler) {
		if($.isFunction(closeEventHandler)) {
			self.impl.onCloseSubscribers.push(closeEventHandler);
		}
		else if(closeEventHandler===undefined) {
			for(var i=0;i<self.impl.onCloseSubscribers.length;i++) {
				var eh = self.impl.onCloseSubscribers[i];
				if($.isFunction(eh)) eh(self.context.currentComponent, self);
			}
		}
		return self;
	};
	/**
	 * Registers an eventHandler which is called when a component is activated and took on the screen.
	 * This event is called before the component is asked to be rendered so that it can optionally prepare itself.
	 *@param Function activateEventHandler a function which receives the component key which is activated and the desktop
	 */
	self.onActivate = function(activateEventHandler) {
		if($.isFunction(activateEventHandler)) {
			self.impl.onActivateSubscribers.push(activateEventHandler);
		}
		else if(activateEventHandler===undefined) {
			for(var i=0;i<self.impl.onActivateSubscribers.length;i++) {
				var eh = self.impl.onActivateSubscribers[i];
				if($.isFunction(eh)) eh(self.context.currentComponent, self);
			}
		}
		return self;
	};
	
	// Lays out the desktop		
	self.options.htmlEmitter.layout(self.options.layoutDesktop,self);
	// Keeps the open emitters into memory
	self.options.userMenuEmitter = self.options.htmlEmitter.clone(self.options.htmlEmitter.$().find("div.desktop div#"+self.ctxKey+"_userMenu"));
	self.options.titleBarEmitter = self.options.htmlEmitter.clone(self.options.htmlEmitter.$().find("div.desktop div#"+self.ctxKey+"_titleBar"));
	self.options.headerBarEmitter = self.options.htmlEmitter.clone(self.options.htmlEmitter.$().find("div.desktop div#"+self.ctxKey+"_headerBar"));
	self.options.workzoneEmitter = self.options.htmlEmitter.clone(self.options.htmlEmitter.$().find("div.desktop div#"+self.ctxKey+"_workzone"));
	self.options.footerBarEmitter = self.options.htmlEmitter.clone(self.options.htmlEmitter.$().find("div.desktop div#"+self.ctxKey+"_footerBar"));
	
	// Registers the default desktop component 
	self.registerComponent(self.ctxKey,
		self.options.displayHeaderBar,
		self.options.displayWorkzone,
		self.options.displayFooterBar,
		self.options);			
	// Activates the desktop
	self.activate(self.ctxKey);
};
wncd.createDesktop = function(options) {return new wncd.Desktop(options);}