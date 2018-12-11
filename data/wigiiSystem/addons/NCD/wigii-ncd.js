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
			 *@param String label the label of the button
			 *@param Function onClick the on click callback, compatible with JQuery onClick signature
			 *@param String cssClass some classes to be added to the button
			 *@param String id optional HTML ID
			 *@param Any context an optional context to be passed to the click callback (as last parameter)
			 */
			self.putButton = function(label, onClick, cssClass, id, context){
				self.htmlTree.push(wigiiNcd.getHtmlBuilder().putStartTag('button','id',(id?id:''),'class',self.emittedClass()+(cssClass?' '+cssClass:'')).html());
				self.htmlTree.append(label);
				var b = self.impl.putHtml(self.htmlTree.pop('</button>'), true);
				if($.isFunction(onClick) && b) b.off().click(function(e){
					if(window.wigiiNcdEtp && window.wigiiNcdEtp.program.context) window.wigiiNcdEtp.program.context.html(self);
					try {onClick.apply(this,[e,context]);}
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
			self.createTextArea = function(cssClass, index) {
				return new wigiiNcd.TextArea(self, cssClass, index);
			};
			/**
			 * Creates and emits a TextInput to capture a single line user input
			 */
			self.createTextInput = function(cssClass, index) {
				return new wigiiNcd.TextInput(self, cssClass, index);
			};
			/**
			 * Creates and emits a PasswordInput to capture a secret input
			 */
			self.createPasswordInput = function(cssClass, index) {
				return new wigiiNcd.PasswordInput(self, cssClass, index);
			};
			/**
			 * Creates and emits a Checkbox
			 */
			self.createCheckbox = function(cssClass, index) {
				return new wigiiNcd.CheckBox(self, cssClass, index);
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
				if(exception.fileName && exception.lineNumber) {
					htmlb.putStartTag('p','class',self.emittedClass()+'-error').put('in file ').put(exception.fileName).put(' on line ').put(exception.lineNumber).putEndTag('p');
				}
				if(exception.stack) {
					htmlb.putStartTag('pre','class',self.emittedClass()+'-error').put(exception.stack).putEndTag('pre');
				}
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
		wigiiNcd.TextArea = function(htmlEmitter, cssClass, index) {
			var self = this;
			self.className = 'TextArea';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now()+(index!==undefined?index:'');
			
			self.context = {index:index};

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
		wigiiNcd.TextInput = function(htmlEmitter, cssClass, index) {
			var self = this;
			self.className = 'TextInput';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now()+(index!==undefined?index:'');
			
			self.context = {index:index};

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
		wigiiNcd.PasswordInput = function(htmlEmitter, cssClass, index) {
			var self = this;
			self.className = 'PasswordInput';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now()+(index!==undefined?index:'');
			
			self.context = {index:index};

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
		 * NCD Text input wrapper
		 *@param jQuery|DOM.Element txtInput a text input or text area DOM element to wrap as NCD
		 */
		wigiiNcd.TextInputWrapper = function(txtInput, cssClass, index) {
			var self = this;
			self.className = 'TextInputWrapper';
			txtInput = $(txtInput);
			if(txtInput.attr('id')) self.ctxKey = txtInput.attr('id');
			else {
				self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now()+(index!==undefined?index:'');
				txtInput.attr('id',self.ctxKey);
			}
			if(cssClass) {
				txtInput.addClass(cssClass);			
			}
			self.context = {index:index};
			
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
		
		/**
		 * A Wigii Graph
		 */
		wigiiNcd.Graph = function() {
			var self = this;
			self.className = 'Graph';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className;
			self.context = {graphNodes:[]};
			
			// Properties
			
			/**
			 * Gets or sets a reference on the GraphNode of the parent graph containing this graph			 
			 */
			self.parentGraphNode = function(graphNode) {
				if(graphNode===undefined) return self.context.parentGraphNode;							
				else {
					// unlinks old parent graph node
					if(self.context.parentGraphNode) {
						self.context.parentGraphNode.context.subGraph = undefined;
						self.context.parentGraphNode = undefined;
					}
					// links new parent graph node only if not null
					if(graphNode!==null) {
						self.context.parentGraphNode = graphNode;
						graphNode.context.subGraph = self;
					}
				}
				return self;
			};
			/**
			 * Iterates on the list of GraphNodes and calls the given callback
			 * @param Function callback of the form callback(graphNode)
			 */
			self.forEachGraphNode = function(callback) {
				for(var i=0;i<self.context.graphNodes.length;i++) { 
					callback(self.context.graphNodes[i]);
				}
			};
			/**
			 * Returns true if this graph is empty (has no GraphNode)
			 */
			self.isEmpty = function()  {return self.context.graphNodes.length==0;};
			/**
			 * Returns the number of GraphNodes contained in this graph
			 */
			self.countGraphNodes = function() {return self.context.graphNodes.length;};
			
			// Methods			
			
			/**
			 * Creates a new unlinked GraphNode instance living in this graph
			 * @return wigiiNcd.GraphNode an instance of a GraphNode
			 */
			self.createGraphNode = function() {
				var returnValue = new wigiiNcd.GraphNode(self);
				self.context.graphNodes.push(returnValue);
				return returnValue;
			};
		};
		
		/**
		 * A Wigii Graph Node
		 *@param wigiiNcd.Graph graph the graph in which lives the node
		 */
		wigiiNcd.GraphNode = function(graph) {
			var self = this;
			self.className = 'GraphNode';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className;
			self.context = {graph:graph};
			
			// Properties
			
			/**
			 * Sets or gets the value of the graph node
			 */
			self.value = function(val) {
				if(val===undefined) return self.context.value;
				else if(val===null) self.context.value=undefined;
				else self.context.value = val;
				return self;
			};
			
			/**
			 * Gets or sets the value of an attribute
			 * If no key is given, returns the map of all attributes
			 */
			self.attribute = function(key,val) {
				if(self.context.attributes) self.context.attributes = {};
				if(!key) return self.context.attributes;
				else if(val===undefined) return self.context.attributes[key];
				else if(val===null) delete self.context.attributes[key];
				else self.context.attributes[key] = val;
				return self;
			};
			
			/**
			 * Gets or sets a departing link with a given name to a given graph node
			 * If no name is given, returns the map of all departing nodes
			 */
			self.depLink = function(name,graphNode) {
				if(!self.context.depLinks) self.context.depLinks = {};
				if(!name) return self.context.depLinks;
				else if(graphNode===undefined) return self.context.depLinks[name];
				else {
					// removes old link
					if(self.context.depLinks[name]) {
						delete self.context.depLinks[name].context.arrLinks[name];
						delete self.context.depLinks[name];
					}
					// adds new link only if not null
					if(graphNode!==null) {
						self.context.depLinks[name] = graphNode;
						if(!graphNode.context.arrLinks) graphNode.context.arrLinks = {};
						graphNode.context.arrLinks[name] = self;
					}
				}
				return self;
			};
			/**
			 * Gets or sets an attribute of a departing link
			 * If no key is given, returns the map of all attributes of the given departing node
			 */
			self.depLinkAttr = function(name,key,val) {
				if(!name) throw wigiiNcd.createServiceException("link name cannot be null",wigiiNcd.errorCodes.INVALID_ARGUMENT);
				if(!self.context.depLinkAttr) self.context.depLinkAttr = {};
				if(!self.context.depLinkAttr[name]) self.context.depLinkAttr[name] = {};
				if(key===undefined) return self.context.depLinkAttr[name];
				else if(key===null) self.context.depLinkAttr[name] = {};
				else if(val===undefined) return self.context.depLinkAttr[name][key];
				else if(val===null) delete self.context.depLinkAttr[name][key];
				else self.context.depLinkAttr[name][key] = val;
				return self;
			};			
			/**
			 * Gets or sets an arriving link with a given name from a given graph node
			 * If no name is given, returns the map of all arriving nodes
			 */
			self.arrLink = function(name,graphNode) {
				if(!self.context.arrLinks) self.context.arrLinks = {};
				if(!name) return self.context.arrLinks;
				else if(graphNode===undefined) return self.context.arrLinks[name];
				else {
					// removes old link
					if(self.context.arrLinks[name]) {
						delete self.context.arrLinks[name].context.depLinks[name];
						delete self.context.arrLinks[name]
					}
					// adds new link only if not null
					if(graphNode!==null) {
						if(!graphNode.context.depLinks) graphNode.context.depLinks = {};
						graphNode.context.depLinks[name] = self;
						self.context.arrLinks[name] = graphNode;
					}
				}
				return self;
			};
			/**
			 * Gets or sets an attribute of an arriving link
			 * If no key is given, returns the map of all attributes of the given arriving node
			 */
			self.arrLinkAttr = function(name,key,val) {
				var depNode = self.arrLink(name);
				if(!depNode) throw wigiiNcd.createServiceException("no arriving link with name "+name,wigiiNcd.errorCodes.INVALID_ARGUMENT);
				var returnValue = depNode.depLinkAttr(name,key,val);
				if(key===undefined || val===undefined) return returnValue;
				else return self;
			};
			/**
			 * Gets or sets a reference to the sub graph attached to this graph node
			 */
			self.subGraph = function(graph) {
				if(graph===undefined) return self.context.subGraph;							
				else {
					// unlinks old sub graph
					if(self.context.subGraph) {
						self.context.subGraph.context.parentGraphNode = undefined;
						self.context.subGraph = undefined;
					}
					// links new sub graph if not null
					if(graph!==null) {
						self.context.subGraph = graph;
						graph.context.parentGraphNode = self;
					}
				}
				return self;
			};
			/**
			 * Returns a reference to the graph containing this graph node
			 */
			self.graph = function() {return self.context.graph;};
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
		
		// Connectors
		
		/**
		 * Shows a popup on the screen with a message
		 *@param String|Function message the message to display in the popup. Can be some HTML, a simple string or a function which returns some HTML or write into the currentDiv.
		 * If message is a function, it receives the wigiiApi.Popup instance as first argument to enable interacting with the popup object (for instance to hide or close it).
		 *@param Object options an optional bag of options to configure the popup. The bag of options should be compatible with the wigiiApi.Popup options (it supports for instance the closeable or resizable options)
		 */
		wigiiNcd.popup = function(message,options) {
			if(!window.wigii) throw wigiiNcd.createServiceException('wigii Api is not loaded, popup fonction is not supported.', wigiiNcd.errorCodes.UNSUPPORTED_OPERATION);
			// sets fixed options
			options = options || {};
			options.localContent = true;
			options.position = "center"
			options.removeOnClose = true;
			
			var wrappedMessage = undefined;
			if($.isFunction(message)) {			
				wrappedMessage = function(popupBody,popup) {
					// creates an html emitter in the popup
					var html = wncd.html(popupBody);						
					// resets current div on close
					var currentDiv = wncd.currentDiv();
					popup.remove(function(){wncd.program.context.html(currentDiv);});
					// sets the html emitter as current div and builds custom html
					wncd.program.context.html(html);
					message(popup);
					wncd.program.context.html(currentDiv);
				};
			}
			else wrappedMessage = message;		
			wigii('HelpService').showFloatingHelp(undefined, undefined, wrappedMessage, options);
		}
		
		/**
		 * Publishes a server side Wigii Exception into a popup
		 *@param Object exception server side Wigii Exception received through a json ajax call.
		 *@param Object context server side execution context details, packaged into the json ajax response.
		 */
		wigiiNcd.publishWigiiException = function(exception,context) {
			if(!window.wigii) throw wigiiNcd.createServiceException('wigii Api is not loaded, publishWigiiException fonction is not supported.', wigiiNcd.errorCodes.UNSUPPORTED_OPERATION);			
			wigiiNcd.popup(wigii().exception2html(exception,context));
		};
		
		/**
		 *@return WigiiApi.WncdContainer returns a Wigii Api WNCD container to host NCD components into a Wigii Module View
		 */
		wigiiNcd.wigiiContainer = function() {
			if(!window.wigii) throw wigiiNcd.createServiceException('wigii Api is not loaded, wigiiContainer fonction is not supported.', wigiiNcd.errorCodes.UNSUPPORTED_OPERATION);
			return wigii().getWncdContainer(wncd);
		};
		/**		 
		 *@return WigiiApi.WncdContainer returns a Wigii Api WNCD container to host NCD components into a Wigii Module View
		 */
		wigiiNcd.getWigiiContainer = wigiiNcd.wigiiContainer;
		
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
		 *@example wigii().sel(rangeGen(-10,10,2),[power(3),sum])
		 *@see WigiiApi.sel method
		 *@return Any the data flow result
		 */
		wigiiNcd.sel = function(source,activities) { 
			if(!window.wigii) throw wigiiNcd.createServiceException('wigii Api is not loaded, sel fonction is not supported.', wigiiNcd.errorCodes.UNSUPPORTED_OPERATION);
			return wigii().sel(source,activities); 
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
			/**
			 * Returns an TextInputWrapper instance linked to the selected DOM element
			 */
			self.wrap = function(selection,options) {
				var returnValue=undefined;
				// checks we have only one element
				if(selection && selection.length==1) {
					// wraps the given selection as a NCD Text Input
					returnValue = wigiiNcd.wrapTextInput(selection,options);
				}
				else if(selection && selection.length>1) throw wigiiNcd.createServiceException('Wigii NCD wrap selector can only be activated on a JQuery collection containing one element and not '+selection.length, wigiiNcd.errorCodes.INVALID_ARGUMENT);
				return (!returnValue?{$:selection}:returnValue);
			};
		};
		
		/**
		 * Wigii NCD data flow sources library
		 */
		wigiiNcd.source = {};
		
		/**
		 * Returns a DataFlow source which generates a range of numbers, starting from one number, to another number (not included), by a given step.
		 * @param Number from start number, can be integer, float, positive, null or negative.
		 * @param Number to stop number, can be integer, float, positive, null or negative.
		 * @param Number step increment number, can be integer, float, positive or negative. Not null.
		 */
		wigiiNcd.source.genRange = function(from,to,step) { return function(dataFlowContext) {
			if(!($.isNumeric(from) && $.isNumeric(to) && $.isNumeric(step))) throw wigiiNcd.createServiceException('from, to and step should all be numbers',wigiiNcd.errorCodes.INVALID_ARGUMENT);			
			if(step>0) {
				while(from<to) {
					dataFlowContext.processDataChunk(from);
					from += step;
				}
			}
			else if(step<0) {
				while(from>to) {
					dataFlowContext.processDataChunk(from);
					from -= step;
				}
			}
			else throw wigiiNcd.createServiceException('step cannot be 0',wigiiNcd.errorCodes.INVALID_ARGUMENT);
		}};
		
		/**
		 * Returns a DataFlow source which generates a finite quantity of numbers, starting from one number, going to another number included.
		 * @param Number from start number, can be integer, float, positive, null or negative.
		 * @param Number to stop number, can be integer, float, positive, null or negative.
		 * @param Number nbSlices Quantity of slices to generate. Numbers a equally distributed between from and to limits.
		 */
		wigiiNcd.source.genInterval = function(from,to,nbSlices) { return function(dataFlowContext) {
			if(!($.isNumeric(from) && $.isNumeric(to) && $.isNumeric(nbSlices))) throw wigiiNcd.createServiceException('from, to and nbSlices should all be numbers',wigiiNcd.errorCodes.INVALID_ARGUMENT);	
			var factor = (to-from)/nbSlices;
			for(var i=0; i <= nbSlices; i++) {
				dataFlowContext.processDataChunk(from+factor*i);
			}
		}};
		
		/**
		 * Returns a DataFlow source which generates a linear sequence of numbers
		 * @param Number length a positive integer which is the length of the sequence. Will compute the sequence for integers in range 1..length.
		 * @param Number factor a number which will be used as a multiplier factor
		 * @param Number shift a number which will be used as a shift value.
		 * @param Boolean alternateSign If true then the signs of the numbers in the sequence alternate. Once positive, once negative. Else always positive.
		 */
		wigiiNcd.source.linearSequence = function(length,factor,shift,alternateSign) { return function(dataFlowContext) {
			if(!($.isNumeric(length) && $.isNumeric(factor) && $.isNumeric(shift))) throw wigiiNcd.createServiceException('length should be a positive integer, factor and shift should be numbers',wigiiNcd.errorCodes.INVALID_ARGUMENT);			
			var negative = false;
			for(var i=1; i <= length; i++) {
				dataFlowContext.processDataChunk((negative ? -(factor*i+shift): factor*i+shift));
				if(alternateSign) negative = !negative;
			}
		}};
		
		/**
		 * Wigii NCD data flow activities library
		 */
		wigiiNcd.dfa = {};
		
		/**
		 * An Array Buffer data flow activity
		 *@param Object options the following configuring options are supported:
		 * - unpair: Boolean. If true, indicates that the flow is a flow of pairs (key,value) represented as objects
		 * 	 These pairs are unpaired and stored into the array as key=>value.
		 * 	 Else, queues the data in the array as it arrives without handling keys.
		 * - keyField: Defines the name of the field to be used as a key. If unpair and not set, then defaults to 'key'.
		 * - valueField: If unpairing, then defines the name of the field to be used as a value, defaults to 'value'
		 *@return Function a function compatible with data flow activities
		 */
		wigiiNcd.dfa.arrayBuffer = function(options) { var options = options || {}; return function(data,activityCtx,dataFlowContext) {
			if(activityCtx.state==dataFlowContext.DFA_STARTSTREAM) {
				if(options.unpair) {
					activityCtx.buffer = {};
					if(!options.keyField) options.keyField = 'key';
					if(!options.valueField) options.valueField = 'value';
				}
				else if(options.keyField) activityCtx.buffer = {};
				else activityCtx.buffer = [];
			}
			else if(activityCtx.state==dataFlowContext.DFA_RUNNING) {
				if(options.unpair) {
					if($.isPlainObject(data)) {
						activityCtx.buffer[data[options.keyField]] = (data.hasOwnProperty(options.valueField)? data[options.valueField]:data);
					}
					else activityCtx.buffer[data] = data;
				}
				else if(options.keyField) {
					if($.isPlainObject(data)) activityCtx.buffer[data[options.keyField]] = data;
					else activityCtx.buffer[data] = data;
				}
				else activityCtx.buffer.push(data);
			}
			else if(activityCtx.state==dataFlowContext.DFA_ENDSTREAM) dataFlowContext.writeResultToOuput(activityCtx.buffer,activityCtx);
		}};
		
		/**
		 * A data flow activity which transforms a flow of points {x,y,weight,color} to an SVG path element.
		 * x,y coordinates should already be in SVG coordinate system. If path and points are plotted, then everything is grouped into an SVG g element.
		 *@param Object options the following configuring options are supported:
		 * - stroke: String. Stroke color code (as described in SVG path),
		 * - strokeWidth: int. Stroke width (as described in SVG path),
		 * - strokeLineCap: String. Stroke line cap (as described in SVG path),
		 * - fill: String. Fill color code if path is closed,
		 * - close: Boolean. If true, then path is forced to close (adds a path Z command),
		 * - pointRadius: int|Function|Boolean. If defined, then the path points are plotted using this value as a radius (or edge length if shape is not circle). A function of point can be given to compute a radius. 
		 * By default, points are not plotted. If true is given, then pointRadius=strokeWidth
		 * - pointDefaultRadius: int. If given then this radius will be taken as unit for weight = 1. Then point radius will be equal to weight*defaultPointRadius.
		 * - pointStroke: String|Function|Boolean. Point stroke color if points are plotted. A function of point can be given. If true is given, then point color is the one given in the point itself (point color attribute).
		 * - pointStrokeWidth: int. Point stroke width. Defaults to strokeWidth.
		 * - pointFill: String|Function|Boolean. Point fill color if points are plotted. A function of point can be given. If true is given, then point fill color is the one given in the point itself (point color attribute).
		 * - pointShape: String. One of circle|square|triangle|diamond. The edge length of the shape is given by the radius parameter.
		 * - id: String. HTML/SVG id of the element
		 * - style: String. CSS style string
		 * - cssClass: String. CSS class string
		 * - outputPathString: Boolean. If true, only outputs path description (value of d attribute), skipping whole SVG construction. False by default.
		 *@return Function a function compatible with data flow activities
		 */
		wigiiNcd.dfa.points2SVG = function(options) { var options = options || {}; return function(data,activityCtx,dataFlowContext) {
			var svgTag;
			if(activityCtx.state==dataFlowContext.DFA_STARTSTREAM) {
				// saves options in context
				activityCtx.options = options;
				// defines default options
				if(options.stroke===false) options.stroke = 'none';
				else if(options.stroke===undefined) options.stroke='#3333ff';
				if(options.strokeLineCap===undefined) options.strokeLineCap="round";
				if(options.pointDefaultRadius && !options.pointRadius) options.pointRadius=true;
				if(options.pointRadius===true) options.pointRadius = options.strokeWidth||2;
				if(options.pointRadius) {
					if(options.pointStroke===undefined && options.stroke!='none') options.pointStroke = options.stroke;
					if(options.pointStrokeWidth===undefined) options.pointStrokeWidth = options.strokeWidth;
					if(options.pointFill===undefined && options.stroke!='none') options.pointFill = options.pointStroke;
					if(options.pointShape===undefined) options.pointShape = 'circle';
					// points SVG buffer
					activityCtx.pointsSVG = wncd.getHtmlBuilder();
				}				
			}
			else if(activityCtx.state==dataFlowContext.DFA_RUNNING) {
				// draws path
				if(!activityCtx.path) activityCtx.path = "M"+data.x+" "+data.y;
				else activityCtx.path += " L"+data.x+" "+data.y;
				
				// draws point
				if(options.pointRadius && !options.outputPathString) {				
					// computes point radius
					var radius = options.pointRadius;
					if(options.pointDefaultRadius) radius = Number(data.weight||0)*options.pointDefaultRadius;
					else if($.isFunction(options.pointRadius)) radius = options.pointRadius(data,activityCtx);
					
					// computes point svg shape using provided function
					if($.isFunction(options.pointShape)) {
						activityCtx.pointsSVG.put(options.pointShape(data,activityCtx));
					}
					// or default shape
					else {
						svgTag = [];
						if(options.pointShape==='circle') {
							svgTag.push('circle');
							svgTag.push('cx');
							svgTag.push(data.x);
							svgTag.push('cy');
							svgTag.push(data.y);
							svgTag.push('r');
							svgTag.push(radius);
						}
						else if(options.pointShape==='square') {
							svgTag.push('rect');
							svgTag.push('x');
							svgTag.push(data.x-radius);
							svgTag.push('y');
							svgTag.push(data.y-radius);
							svgTag.push('width');
							svgTag.push(radius*2);
							svgTag.push('height');
							svgTag.push(radius*2);
						}
						else if(options.pointShape==='triangle') {
							// cos(60) = 0.5 = demiBase / radius
							var demiBase = 0.5*radius;
							// sin(60) = sqrt(3)/2 = hauteur / radius
							var hauteur = Math.sqrt(3)/2*radius;
							// tan(30) = dHauteur / demiBase
							var dHauteur = Math.tan(Math.PI/6)*demiBase;
							var points = '';
							// sommet
							points += data.x+","+(data.y-hauteur+dHauteur);
							// base gauche
							points += " "+(data.x-demiBase)+","+(data.y+dHauteur);
							// base droite
							points += " "+(data.x+demiBase)+","+(data.y+dHauteur);
							// close
							points += " Z";
							svgTag.push('polygon');
							svgTag.push('points');
							svgTag.push(points);
						}
						else if(options.pointShape==='diamond') {						
							var demiBase = radius/2;
							// tan(60) = hauteur / demiBase
							var hauteur = Math.tan(Math.PI/3)*demiBase;						
							var points = '';
							// sommet
							points += data.x+","+(data.y-hauteur);
							// milieu gauche
							points += " "+(data.x-demiBase)+","+data.y;
							// base
							points += " "+data.x+","+(data.y+hauteur);
							// milieu droite
							points += " "+(data.x+demiBase)+","+data.y;
							// close
							points += " Z";
							svgTag.push('polygon');
							svgTag.push('points');
							svgTag.push(points);
						}
						if(options.pointStroke) {
							svgTag.push('stroke');
							svgTag.push(($.isFunction(options.pointStroke) ? options.pointStroke(data,activityCtx) : options.pointStroke));
						}
						if(options.pointStrokeWidth) {
							svgTag.push('stroke-width');
							svgTag.push(options.pointStrokeWidth);
						}
						if(options.pointFill) {
							svgTag.push('fill');
							svgTag.push(($.isFunction(options.pointFill) ? options.pointFill(data,activityCtx) : options.pointFill));
						}
						activityCtx.pointsSVG.tag.apply(undefined,svgTag).$tag(svgTag[0]);
					}
				}
			}
			else if(activityCtx.state==dataFlowContext.DFA_ENDSTREAM) {
				// closes path if needed
				if(options.close) activityCtx.path += " Z";
				
				// if outputPathString then only dumps path description
				if(options.outputPathString) {
					dataFlowContext.writeResultToOuput(activityCtx.path,activityCtx);
				}
				// else constructs svg code
				else {
					var svgBuilder = wncd.getHtmlBuilder();
					var initPathOptions = function(pathTag) {
						// fill only if closed
						if(options.close) {
							if(options.fill && options.fill !== 'css') {
								pathTag.push('fill');
								pathTag.push(options.fill);
							}
						}
						else {
							pathTag.push('fill');
							pathTag.push('none');
						}
						// puts stroke if not taken over by css
						if(options.stroke && options.stroke!=='css') {
							pathTag.push('stroke');
							pathTag.push(options.stroke);
						}
						if(options.strokeWidth) {
							pathTag.push('stroke-width');
							pathTag.push(options.strokeWidth);
						}
						// puts line cap if not taken over by css
						if(options.strokeLineCap && options.strokeLineCap!=='css') {
							pathTag.push('stroke-linecap');
							pathTag.push(options.strokeLineCap);
						}
					};
					svgTag = [];
					// if draw points, then creates an svg group
					if(options.pointRadius) {
						svgTag.push('g');
					}
					// else only creates a path
					else {
						svgTag.push('path');
					}
					// puts html attributes
					if(options.id) {
						svgTag.push('id');
						svgTag.push(options.id);
					}
					if(options.cssClass) {
						svgTag.push('class');
						svgTag.push(options.cssClass);
					}
					if(options.style) {
						svgTag.push('style');
						svgTag.push(options.style);
					}
					// puts path and points in the group
					if(options.pointRadius) {
						var pathTag=['path','d',activityCtx.path];
						initPathOptions(pathTag);
						svgBuilder.tag.apply(undefined,svgTag)
						.tag.apply(undefined,pathTag).$tag(pathTag[0])
						.putHtmlBuilder(activityCtx.pointsSVG)
						.$tag(svgTag[0]);
					}
					// else puts path only
					else {
						svgTag.push('d');
						svgTag.push(activityCtx.path);
						initPathOptions(svgTag);					
						svgBuilder.tag.apply(undefined,svgTag).$tag(svgTag[0]);
					}
					dataFlowContext.writeResultToOuput(svgBuilder.html(),activityCtx);
				}
			}
		}};
		
		/**
		 * A data flow activity which translates and autosizes a flow of points {x,y} to have all coordinates contained in range coordMin..coordMax 
		 * and compatible with screen coordinate system (y is flipped to grow towards bottom instead of top).
		 *@param Object options the following configuring options are supported:
		 * - coordMin: int. Min value allowed for any x or y coordinate of any point in the flow. Defaults to 0.
		 * - coordMax: int. Max value allowed for any x or y coordinate of any point in the flow. Defaults to 1024.
		 *@return Function a function compatible with data flow activities
		 */
		wigiiNcd.dfa.autoSizePoints = function(options) { var options = options || {}; return function(data,activityCtx,dataFlowContext) {
			if(activityCtx.state==dataFlowContext.DFA_STARTSTREAM) {
				if(options.coordMin===undefined) options.coordMin = 0;
				if(options.coordMax===undefined) options.coordMax = 1024;
				activityCtx.buffer = [];
			}
			else if(activityCtx.state==dataFlowContext.DFA_RUNNING) {
				// computes min and max of each coordinate x and y
				if(activityCtx.minX===undefined) activityCtx.minX = data.x;
				else if(data.x < activityCtx.minX) activityCtx.minX = data.x;
				if(activityCtx.maxX===undefined) activityCtx.maxX = data.x;
				else if(data.x > activityCtx.maxX) activityCtx.maxX = data.x;
				if(activityCtx.minY===undefined) activityCtx.minY = data.y;
				else if(data.y < activityCtx.minY) activityCtx.minY = data.y;
				if(activityCtx.maxY===undefined) activityCtx.maxY = data.y;
				else if(data.y > activityCtx.maxY) activityCtx.maxY = data.y;
				// bufferizes points
				activityCtx.buffer.push(data);
			}
			else if(activityCtx.state==dataFlowContext.DFA_ENDSTREAM) {
				// autosizing factor
				var factor = Math.max(Math.abs(activityCtx.maxX-activityCtx.minX), Math.abs(activityCtx.maxY-activityCtx.minY));
				factor = Math.abs(options.coordMax-options.coordMin)/factor;
				// translates and transforms every points
				var p;
				for(var i=0;i<activityCtx.buffer.length;i++) {
					p = activityCtx.buffer[i];
					p.x = (p.x - activityCtx.minX) * factor + options.coordMin;
					p.y = options.coordMax - ((p.y - activityCtx.minY) * factor + options.coordMin); // flips y coordinate to have it growing towards the top of the screen
					dataFlowContext.writeResultToOuput(p,activityCtx);
				}				
			}
		}};
		
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
		 * Wraps a standard DOM input text, or text area to be compatible with NCD features
		 *@return wigiiNcd.TextInputWrapper
		 */
		wigiiNcd.wrapTextInput = function(txtInput, cssClass) {
			return new wigiiNcd.TextInputWrapper(txtInput, cssClass);
		};
		/**
		 * Creates a new Wigii Graph instance
		 */
		wigiiNcd.createGraph = function() {
			return new wigiiNcd.Graph();
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
		wigiiNcd.version = function() {return "2.11";};
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
