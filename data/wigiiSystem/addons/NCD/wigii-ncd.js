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
 
 /**
  * Wigii Natural Code Development (NCD) core library
  * Created by Camille Weber (camille@wigii.org), 23.05.2016
  * @param Window window current browser window
  * @param JQuery $ depends on JQuery 1.8.x
  */
 (function (window, $){
	// Wigii NCD
	var WigiiNcd = function() {
		var wigiiNcd = this;
		wigiiNcd.instantiationTime = Date.now();
		wigiiNcd.ctxKey = 'WigiiNcd_'+wigiiNcd.instantiationTime;
		/**
		 * Object which holds inner private mutable state variables.
		 */
		wigiiNcd.context = {};		
						
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
		};
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
					if(window.programme) programme.context.html(self);
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
			self.createCheckbox = function(cssClass) {
				return new wigiiNcd.CheckBox(self, cssClass);
			};
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
									if(window.wigiiNcdEtp) wigiiNcdEtp.programme.context.html(htmlEmitter);
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
			 *@example self.insert(function(article){ programme.formulaire.creerChamp("title", "Title").value(article.title);}, article)
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
			};
		};
		
		/**		
		 * NCD CheckBox
		 *@param wigiiNcd.HtmlEmitter htmlEmitter underlying open HTML emitter to which dump the checkbox component
		 */
		wigiiNcd.CheckBox = function(htmlEmitter, cssClass) {
			var self = this;
			self.className = 'CheckBox';
			self.ctxKey = wigiiNcd.ctxKey+'_'+self.className+Date.now();
			
			self.context = {};

			var htmlB = wigiiNcd.getHtmlBuilder();
			htmlB.putStartTag('input','type','checkbox','class',htmlEmitter.emittedClass()+(cssClass?' '+cssClass:''), "id", self.ctxKey);		
			htmlB.putEndTag('input');
			htmlEmitter.putHtml(htmlB.html());
			
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
					if(!self.context.onClickSubscribers) {
						self.context.onClickSubscribers = [];
						// registers onclick event handler on checkbox
						$("#"+self.ctxKey).click(function(){self.toggle();self.onClick();})
					}
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
			};
			/**
			 * Toggles the value of the checkbox
			 */
			self.toggle = function() {self.checked(!self.checked()); return self;}
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
		
		// Service providing
		
		/**
		 * Creates a new StringStack instance
		 */
		wigiiNcd.createStringStackInstance = function() {
			return new wigiiNcd.StringStack();
		};
		
		/**
		 * Creates a new HtmlEmitter object
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
		wigiiNcd.bindSelectionSense = function(anchor, onClick, options) {
			return wigiiNcd.createSelectionSense(onClick, options).bind(anchor);
		};
		
		wigiiNcd.createCountingSense = function(onClick, options) {
			return new wigiiNcd.CountingSense(onClick, options);
		};
		wigiiNcd.bindCountingSense = function(anchor, onClick, options) {
			return wigiiNcd.createCountingSense(onClick, options).bind(anchor);
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
		 * @return String returns the Wigii NCD version number
		 */
		wigiiNcd.version = function() {return "1.1";};
	},	
	// Default WigiiNCD instance
	wigiiNcdInstance = new WigiiNcd(),
	// WigiiNCD Functional facade 
	wigiiNcdFacade = function(selector,options) {
		var wigiiNcd = wigiiNcdInstance;
		return wigiiNcd;
	};
	// Bootstrap
	if(!window.wigiiNcd || window.wigiiNcd().version() < wigiiNcdFacade().version()) window.wigiiNcd = wigiiNcdFacade;
 })(window, jQuery);
 
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
