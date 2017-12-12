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
		// Appends comments
		srcCode = (comment?"\t\t"+comment+"\n":"")+"\t\t"+srcCode;
		// Normalize indentation
		srcCode = Prism.plugins.NormalizeWhitespace.normalize(srcCode);
		// Highlight syntax
		wncd.currentDiv().htmlBuilder()
			.tag('pre').tag('code','class','language-js')
				.put(Prism.highlight(srcCode,Prism.languages.js))
			.$tag('code').$tag('pre')
		.emit();
	}
	
	if(!self.options.renderClassSrc) self.options.renderClassSrc = function(srcCode,comment) {
		var Prism = wncd.externals.Prism;
		// Appends comments
		srcCode = (comment?"\t\t"+comment+"\n":"")+"\t\t"+srcCode;
		// Normalize indentation
		srcCode = Prism.plugins.NormalizeWhitespace.normalize(srcCode);
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