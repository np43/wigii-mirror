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
 
wncd.comment(function(){/**
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
 */},
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
	self.options.privateValues.wncdAttr = true;
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
			memberDiv.htmlBuilder().tag("a","class","methodName selfLink","href","#"+member.uri).out(member.name,"methodName").$tag("a").emit();
			
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
				memberDiv.htmlBuilder().tag("div","class","classSrc").insert(options.renderClassSrc,member.context.srcCode,member.context.comment).$tag("div").emit();				
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
		var memberComment = undefined;
		
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
			if(member.context.object.wncdAttr && member.context.object.wncdAttr.comment) {
				memberComment = member.context.object.wncdAttr.comment;
			}
			
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
				expandable = (Object.keys(member.context.object).length > 0);
			}
			else expandable = (Object.keys(member.context.object).length > (memberComment?1:0));
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
		if(memberComment) member.context.comment = memberComment;		
		else if(objModel.context.classMemberComments) member.context.comment = objModel.context.classMemberComments[member.name];		
		
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
});	
wncd.comment(function(){/**
 * Creates an object documentation model on the given object
 */},
wncd.createObjectDoc = function(obj,options) { 
	return new wncd.ObjectDoc(obj,options);
});	

wncd.comment(function(){/**
 * Documentation browser which displays a tree view of the documentation, 
 * a search bar and a view with the detailed documentation and interactive examples
 * @param wncd.Desktop container the container in which to deploy the ObjectDocBrowser. Today supports only wncd.Desktop.
 * @param Object options a set of options to configure the desktop component. It supports the following attributes :
*/},
wncd.ObjectDocBrowser = function(container, options) {
	var self = this;
	self.className = 'ObjectDocBrowser';
	self.instantiationTime = Date.now();
	self.ctxKey = wncd.ctxKey+'_'+self.className+self.instantiationTime;
	self.options = options || {};
	self.context = {
		articles: undefined,
		articlesIndex: {},
		currentArticle: undefined
	};
	self.impl = {
		onCloseArticleSubscribers:[],
		onActivateArticleSubscribers:[]
	};
	
	// Properties

	self.$ = function() {return $('#'+self.ctxKey);}
	
	// Define default options		
	if(!self.options.title) self.options.title = wncd.getHtmlBuilder()
		.tag('p','class','title').out("Wigii ").tag('span','style','color:#3333ff').out("NCD").$tag('span').out(" Documentation reference").$tag('p')
		.html();
	if(!self.options.searchButtonLabel) self.options.searchButtonLabel = "&#128270;"; // lense
	if(!self.options.libInstance) {
		self.options.libInstance = wncd;
		self.options.libNamespace = 'wncd';
	}
	if(!self.options.libNamespace) self.options.libNamespace = 'Library Doc';
	if(!self.options.label) self.options.label = "&#128214; "+self.options.libNamespace; // open book
	
	/**
	 * Renders search bar
	 */
	if(!self.options.renderSearchBar) self.options.renderSearchBar = function(container) {
		self.context.searchBar = wncd.currentDiv().reset().display(undefined,undefined,"searchBar");
		self.context.searchBar.$().on('change',function(){self.expandToMember($(this).val());});
		//wncd.currentDiv().button(self.options.searchButtonLabel,undefined,"searchBarButton");
	};
	/**
	 * Renders browser panel
	 */
	if(!self.options.renderBrowserPanel) self.options.renderBrowserPanel = function(container) {
		wncd.currentDiv().reset().layout(function(h,v,x1,x2,x3,x4,x5,x6,x7,x8,x9,x10){
			return h(
				x1(function(){
					// resets the list of current articles
					if(self.context.articles) {
						self.context.articlesIndex = {};
						self.context.currentArticle = undefined;
						self.context.articles = undefined;
					};
					/*
					self.addArticle({
						key: 'test1',
						label: 'Test 1',
						content: wncd.ctlSeq(p(),out("Hello 1"),out('&nbsp;'),out(wncd.txtDate(Date.now())),$p())
					})
					.addArticle({
						key: 'test12',
						label: 'Test 2',
						content: wncd.ctlSeq(p(),out("Hello 2"),out('&nbsp;'),out(wncd.txtDate(Date.now())),$p())
					});
					*/
				}).cssClass("docBrowserToolbar"),
				x10(function(){						
					wncd.currentDiv().div(self.options.libNamespace, "method").color("#3333FF").out(self.options.libNamespace).$color();
					self.context.wncdDoc = wncd.createObjectDoc(self.options.libInstance,
						{docOutput:wncd.currentDiv().div(self.options.libNamespace),
							expandLevel:5,
							noSorting:true,
							namespace:self.options.libNamespace,
							privateValues:{}
						});
					// collapse all
					self.collapseAll();
					// initializes search bar
					self.context.searchBar.autocomplete(Object.keys(self.context.wncdDoc.context.docModel.members)).$().focus();
				}).cssClass("docBrowser")					
			).width("100%").height("100%").id(self.ctxKey);
		});
	};
	/**
	 * Renders footer bar
	 */
	if(!self.options.renderFooterBar) self.options.renderFooterBar = function(container) {
		wncd.currentDiv().reset().out("Natural Code Development, Wigii.org (CWE), 10.01.2018");
		if(container.context.startupLog) wncd.currentDiv().out(", ").out(container.context.startupLog);
	}
	
	// Implementation
	
	/**
	 * Collapse all doc browser nodes, except first level
	 */
	self.collapseAll = function() {
		if(self.context.wncdDoc) {
			var wncdMembers = self.context.wncdDoc.context.docModel.libs[self.options.libNamespace].members;
			for(var memberName in wncdMembers) {
				var ss = wncdMembers[memberName].context.selectionSense;
				if(ss && ss.selected()) ss.onClick();
			}
			self.$().find('> div.docBrowser').scrollTop(0);
		}
	};

	/**
	 * Expands tree to display given member and scrolls down to it.
	 *@param String qualifiedName the qualified name of the member to display
	 */
	self.expandToMember = function(qualifiedName) {
		if(!self.context.wncdDoc) return;
		// searches for member
		var member = self.context.wncdDoc.context.docModel.members[qualifiedName];
		if(!member) return;
		// expands member and goes up the tree
		var m = member;
		while(m) {
			if(m.context.selectionSense && !m.context.selectionSense.selected()) m.context.selectionSense.onClick();
			m = m.parent;
		}
		// scrolls to member
		var docBrowser = self.$().find('> div.docBrowser');
		docBrowser.scrollTop(0).scrollTop($("#"+member.uri).offset().top-docBrowser.offset().top);
	};
	
	/**
	 * Adds an article into the doc browser
	 *@param wncd.ObjectDocArticle an article instance
	 *@return wncd.ObjectDocBrowser for chaining
	 */
	self.addArticle = function(article) {
		// creates the list of articles if not yet done
		if(!self.context.articles) {
			self.context.articles = self.$().find('> div.docBrowserToolbar').wncd('html').list(
				function(n,list){/* nothing to generate. empty list */},
				/* renders the article label in the toolbar */
				function(i,article,list) {
					return article.label || article.key;
				},
				{
					maxSelection:1,
					/* activates article on click in the toolbar */
					onItemClick:function(i,article,selected,list){
						if(selected) self.activateArticle(article.key);
						else self.closeCurrentArticle();
					}
				}
			);
		}
		// adds the article to the list
		self.context.articles.add(article);
		// registers article in index
		self.context.articlesIndex[article.key] = self.context.articles.size();
		return self;
	};		
	
	/**
	 * Activates an article givens its key
	 *@param String key Key of the article to be activated
	 *@return wncd.ObjectDocArticle returns the activated article instance
	 */
	self.activateArticle = function(key) {
		if(!key) throw wncd.createServiceException("key cannot be null",wncd.errorCodes.INVALID_ARGUMENT);
		var articleIndex = self.context.articlesIndex[key];
		if(!articleIndex) throw wncd.createServiceException("no article found under key "+key,wncd.errorCodes.INVALID_ARGUMENT);			
		// selects article if not already selected
		if(!self.context.articles.selected(articleIndex)) self.context.articles.selected(articleIndex,true);
		// gets article
		var article = self.context.articles.items()[articleIndex-1];		
		// closes previous article
		if(self.context.currentArticle) self.onCloseArticle();
		// changes current article
		self.context.currentArticle = article;
		// activates the article			
		self.onActivateArticle();			
		// Renders article
		self.impl.renderArticle(article);
		return article;
	};
	
	/**
	 * Renders article in docBrowser space
	 */
	self.impl.renderArticle = function(article) {
		// switches html emitter to docBrowser space
		wncd.program.context.html(self.$().find('> div.docBrowser').wncd('html').reset());
		// runs article content function
		wncd.program(article.content);
	};
	
	/**
	 * Removes the current article off the screen and displays again the doc browser
	 */
	self.closeCurrentArticle = function() {
		self.activateArticle(self.ctxKey);
	};
	/**
	 * Registers an eventHandler which is called when an article is closed and took off the screen.
	 *@param Function closeEventHandler a function which receives the article which is closed and the doc browser
	 */
	self.onCloseArticle = function(closeEventHandler) {
		if($.isFunction(closeEventHandler)) {
			self.impl.onCloseArticleSubscribers.push(closeEventHandler);
		}
		else if(closeEventHandler===undefined) {
			for(var i=0;i<self.impl.onCloseArticleSubscribers.length;i++) {
				var eh = self.impl.onCloseArticleSubscribers[i];
				if($.isFunction(eh)) eh(self.context.currentArticle, self);
			}
		}
		return self;
	};
	/**
	 * Registers an eventHandler which is called when an article is activated and took on the screen.
	 * This event is called before the article is asked to be rendered so that it can optionally prepare itself.
	 *@param Function activateEventHandler a function which receives the article which is activated and the doc browser
	 */
	self.onActivateArticle = function(activateEventHandler) {
		if($.isFunction(activateEventHandler)) {
			self.impl.onActivateArticleSubscribers.push(activateEventHandler);
		}
		else if(activateEventHandler===undefined) {
			for(var i=0;i<self.impl.onActivateArticleSubscribers.length;i++) {
				var eh = self.impl.onActivateArticleSubscribers[i];
				if($.isFunction(eh)) eh(self.context.currentArticle, self);
			}
		}
		return self;
	};
	
	// Deploys into container		
	// Only supports wncd.Desktop container
	if($.type(container)!=='object' || container.className != 'Desktop') throw wncd.createServiceException("container should be a non null instance of wncd.Desktop",wncd.errorCodes.INVALID_ARGUMENT);
	self.options.obj = self; // passes the ObjectDocBrowser to the Desktop through the component object
	container.registerComponent(self.ctxKey,self.options.renderSearchBar,self.options.renderBrowserPanel,self.options.renderFooterBar,self.options);
});
 
wncd.comment(function(){/**
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
*/},
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
});

wncd.comment(function(){/**
 * JQuery NCD plugin binding a contextual menu to a given anchor
 *@return wncd.ContextualMenu
*/},
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
});

wncd.comment(function(){/**
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
*/},
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
					x3(self.options.logo||"&nbsp;"),
					x2().id(desktop.ctxKey+"_userMenu").cssClass("userMenuContainer")
				),
				/* header bar */
				x7.v(
					x1().id(desktop.ctxKey+"_titleBar").cssClass("titleBar").minHeight('20px').maxHeight('40px'),
					x3().id(desktop.ctxKey+"_headerBar").cssClass("headerBar").minHeight('20px')
				)
			).minHeight('100px'),
			/* workzone */
			x10().id(desktop.ctxKey+"_workzone").cssClass("workzone"),
			/* footer bar */
			x1().id(desktop.ctxKey+"_footerBar").cssClass("footerBar").minHeight('20px').maxHeight('40px')
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
});
wncd.createDesktop = function(options) {return new wncd.Desktop(options);}


wncd.comment(function(){/**
 * A task list which remembers past tasks and proposes matching when typing
 * @param wncd.Desktop container the container in which to deploy the component. Today supports only wncd.Desktop.
 * @param Object options a set of options to configure the desktop component. It supports the following attributes :
*/},
wncd.SelfLearningTaskList = function(container, options) {
	var self = this;
	self.className = 'SelfLearningTaskList';
	self.instantiationTime = Date.now();
	self.ctxKey = wncd.ctxKey+'_'+self.className+self.instantiationTime;
	self.options = options || {};
	self.context = {
		taskCatalog:[],
		tasks:[],
		taskIndex:{}
	};
	self.impl = {
	};
	
	// Properties

	self.$ = function() {return $('#'+self.ctxKey);}
	
	// Define default options		
	if(!self.options.label) self.options.label = "&#128203;" // clipboard
	if(!self.options.title) self.options.title = wncd.getHtmlBuilder()
		.tag('p','class','title').out("&#128203;&nbsp;").out("Task List").$tag('p')
		.html();
	if(self.options.enableTimeTracking===undefined) self.options.enableTimeTracking = true;
	if(self.options.enableTaskAbort===undefined) self.options.enableTaskAbort = true;		
	if(self.options.multiTasking===undefined) self.options.multiTasking = true;
	if(!self.options.runButtonLabel) self.options.runButtonLabel = "&#9654;" // play button		
	if(!self.options.pauseButtonLabel) self.options.pauseButtonLabel = "&#9208;" // pause button
	if(self.options.defaultOnTaskEnd===undefined) self.options.defaultOnTaskEnd = function(task,action) {
		var actionLabel = (action=='completed'?'Completed':'Aborted');			
		$('#task_'+task.id).addClass(action).find('span.taskLabel').append("&nbsp;"+actionLabel+(task.duration>0?"&nbsp;duration: "+(task.duration/1000)+"s":''));
	};		
	
	/**
	 * Renders search bar
	 */
	if(!self.options.renderSearchBar) self.options.renderSearchBar = function(container) {
		self.context.searchBar = wncd.currentDiv().reset().display(undefined,undefined,"searchBar");
		var selectTask = function(e){
			var taskLabel = $(this).val();
			// CWE 14.01.2018 to prevent duplicated 'on change' event, taskLabel should be different than last one to be added.
			if(self.context.tasks.length == 0 || self.context.tasks[self.context.tasks.length-1].label != taskLabel) {
				// adds task to list
				var task = self.impl.createTask(taskLabel);					
				self.addTask(task);
			}
		};
		self.context.searchBar.$().on('change',selectTask);
	};
	/**
	 * Renders task
	 */
	if(!self.options.renderTask) self.options.renderTask = function(i,task) {
		// displays only task which status is not completed and not aborted
		if(task.status != 'completed' && task.status != 'aborted') {
			var html = wncd.currentDiv().div(self.ctxKey).htmlBuilder();
			html.tag("p","id","task_"+task.id,"class","task")
				.tag("span","class","taskCompleted").out("&#10004;").$tag("span");
				if(self.options.enableTimeTracking) {
					// displays pause button if status is running
					if(task.status == 'running') html.tag("span","class","taskRun").out(self.options.pauseButtonLabel).$tag("span");
					// else displays play button
					else html.tag("span","class","taskRun").out(self.options.runButtonLabel).$tag("span");
				}
				if(self.options.enableTaskAbort) {
					html.tag("span","class","taskAborted").out("&#10006;").$tag("span");
				}
				html.tag("span","class","taskIndex").out(i).$tag("span")
				.tag("span","class","taskLabel").out(task.label).$tag("span")
			.$tag("p")
			.emit();
			
			// binds on click events
			$("#task_"+task.id+" span.taskCompleted").click(self.impl.onClickTaskCompleted);
			if(self.options.enableTimeTracking) $("#task_"+task.id+" span.taskRun").click(self.impl.onClickTaskRunPause);
			if(self.options.enableTaskAbort) $("#task_"+task.id+" span.taskAborted").click(self.impl.onClickTaskAborted);
		}
	};
	/**
	 * Renders task list
	 */
	if(!self.options.renderTaskList) self.options.renderTaskList = function(container) {
		// creates task list container
		wncd.currentDiv().reset().div(self.ctxKey, "taskList");
		// displays the task list
		for(var i=0;i<self.context.tasks.length;i++) {
			self.options.renderTask(i+1,self.context.tasks[i]);
		}
		// initializes search bar
		self.context.searchBar.autocomplete(self.context.taskCatalog).$().focus();
	};
	/**
	 * Renders footer bar
	 */
	if(!self.options.renderFooterBar) self.options.renderFooterBar = function(container) {
		wncd.currentDiv().reset().out("Wigii.org, NCD App (CWE), 05.03.2018, Self Learning Task List");
		if(container.context.startupLog) wncd.currentDiv().out(", ").out(container.context.startupLog);
	}
	
	// Task list service
	
	self.addTask = function(task) {
		if(!task) throw wncd.createServiceException('task cannot be null',wncd.errorCodes.INVALID_ARGUMENT);
		if(self.context.taskIndex[task.id]) throw wncd.createServiceException("task '"+task.id+"' already exists in the list",wncd.errorCodes.ALREADY_EXISTS);
		var i = self.context.tasks.push(task);
		self.context.taskIndex[task.id] = task;
		// adds task to catalog if not present
		if(!self.context.taskCatalog.includes(task.label)) {
			self.context.taskCatalog.push(task.label);
			self.context.taskCatalog.sort();
		}
		// renders task
		self.options.renderTask(i,task);
	};		
	self.completeTask = function(taskId) {
		var task = self.context.taskIndex[taskId];
		if(!task) throw wncd.createServiceException("task '"+task.id+"' doesn't exists in the list",wncd.errorCodes.DOES_NOT_EXIST);
		self.impl.taskController(task,'completed');
	};
	self.abortTask = function(taskId) {
		var task = self.context.taskIndex[taskId];
		if(!task) throw wncd.createServiceException("task '"+task.id+"' doesn't exists in the list",wncd.errorCodes.DOES_NOT_EXIST);
		self.impl.taskController(task,'aborted');
	};
	self.startTask = function(taskId) {
		var task = self.context.taskIndex[taskId];
		if(!task) throw wncd.createServiceException("task '"+task.id+"' doesn't exists in the list",wncd.errorCodes.DOES_NOT_EXIST);
		self.impl.taskController(task,'run');
	};
	self.pauseTask = function(taskId) {
		var task = self.context.taskIndex[taskId];
		if(!task) throw wncd.createServiceException("task '"+task.id+"' doesn't exists in the list",wncd.errorCodes.DOES_NOT_EXIST);
		self.impl.taskController(task,'pause');
	};		
	/**
	 * Registers an event handler which is called each time a task is ended (completed or aborted)
	 *@param Function eventHandler a function with signature eventHandler(task, action) where action is one of 'completed' or 'aborted'
	 */
	self.onTaskEnd = function(eventHandler) {
		if(!$.isFunction(eventHandler)) throw wigiiNcd.createServiceException('task end event handler should be a function', wigiiNcd.errorCodes.INVALID_ARGUMENT);
		self.onTaskEndSubscribers.push(eventHandler);
	};
	self.onTaskEndSubscribers = [];
	/**
	 * Registers an event handler which is called each time a task is controlled (run, pause, resume)
	 *@param Function eventHandler a function with signature eventHandler(task, action) where action is one of 'run', 'pause', or 'resume'
	 */
	self.onTaskControl = function(eventHandler) {
		if(!$.isFunction(eventHandler)) throw wigiiNcd.createServiceException('task end event handler should be a function', wigiiNcd.errorCodes.INVALID_ARGUMENT);
		self.onTaskControlSubscribers.push(eventHandler);
	};
	self.onTaskControlSubscribers = [];
	
	// Implementation
	self.impl.createTask = function(taskLabel) {
		return {label:taskLabel,id:Date.now(),duration:0};
	};		
	self.impl.onClickTaskCompleted = function(evt) {
		var taskId = $(this).parent().attr('id').replace('task_','');
		self.impl.taskController(self.context.taskIndex[taskId],'completed');
	};
	self.impl.onClickTaskRunPause = function(evt) {
		var taskId = $(this).parent().attr('id').replace('task_','');
		var task = self.context.taskIndex[taskId];
		self.impl.taskController(task,(task.status == 'running'?'pause':'run'));
	};
	self.impl.onClickTaskAborted = function(evt) {
		var taskId = $(this).parent().attr('id').replace('task_','');
		self.impl.taskController(self.context.taskIndex[taskId],'aborted');
	};
	self.impl.taskController = function(task,action) {
		// state machine
		switch(task.status) {
		case 'running':
			if(action == 'pause') {
				task.status = 'paused';
				self.impl.endTask(task);
			}
			else if(action == 'completed') {
				task.status = 'completed';
				self.impl.endTask(task);
			}
			else if(action == 'aborted') {
				task.status = 'aborted';
				self.impl.endTask(task);
			}
			break;
		case undefined:
		case 'paused':
			if(action == 'run') {
				// if not multiTasking then pauses current task before context switching
				if(!self.options.multiTasking && self.context.currentTask && self.context.currentTask.id != task.id) {
					self.impl.taskController(self.context.currentTask,'pause');
					self.context.currentTask = task;
				}
				task.status = 'running';
				self.impl.startTask(task);
			}
			else if(action == 'completed') {
				task.status = 'completed';
				self.impl.endTask(task);
			}
			else if(action == 'aborted') {
				task.status = 'aborted';
				self.impl.endTask(task);
			}
			break;
		case 'completed':
		case 'aborted':
			/* nothing to do */
			break;
		}
	};
	self.impl.startTask = function(task) {
		var startDate = Date.now();
		// records first start date
		if(!task.startDate) task.startDate = startDate;
		// records resume date for duration calculation
		else task.resumeDate = startDate;
		// calls any registred eventHandlers
		if(self.onTaskControlSubscribers.length>0) {
			for(var i=0;i<self.onTaskControlSubscribers.length;i++) {
				// if a resume date, then action is 'resume', else action is first 'run'
				self.onTaskControlSubscribers[i](task, (task.resumeDate?'resume':'run'));
			}
		}
	};
	self.impl.endTask = function(task) {
		var endDate = Date.now();
		// sums up duration
		if(task.resumeDate) task.duration += (endDate - task.resumeDate);
		else task.duration += (endDate - task.startDate);
		// sets end date
		task.endDate = endDate;
		// calls any registred eventHandlers
		if(self.onTaskControlSubscribers.length>0 && task.status == 'paused') {
			for(var i=0;i<self.onTaskControlSubscribers.length;i++) {					
				self.onTaskControlSubscribers[i](task, 'pause');
			}
		}
		else if(self.onTaskEndSubscribers.length>0 && (task.status == 'completed' || task.status == 'aborted')) {
			for(var i=0;i<self.onTaskEndSubscribers.length;i++) {					
				self.onTaskEndSubscribers[i](task, task.status);
			}
		}
	};
	
	// Registers default event handlers
	if(self.options.defaultOnTaskEnd!==false) self.onTaskEnd(self.options.defaultOnTaskEnd);
	if(self.options.enableTimeTracking) self.onTaskControl(function(task,action) {
		if(action=='pause') $("#task_"+task.id+" span.taskRun").html(self.options.runButtonLabel);
		else $("#task_"+task.id+" span.taskRun").html(self.options.pauseButtonLabel);
	});
	
	// Deploys into container		
	// Only supports wncd.Desktop container
	if($.type(container)!=='object' || container.className != 'Desktop') throw wncd.createServiceException("container should be a non null instance of wncd.Desktop",wncd.errorCodes.INVALID_ARGUMENT);
	self.options.obj = self; // passes the SelfLearningTaskList to the Desktop through the component object
	container.registerComponent(self.ctxKey,self.options.renderSearchBar,self.options.renderTaskList,self.options.renderFooterBar,self.options);
});
wncd.createSelfLearningTaskList = function(container, options) {return new wncd.SelfLearningTaskList(container,options);};

wncd.comment(function(){/**
 * A story board which implements Agile Kanban methodology
 * @param wncd.Desktop container the container in which to deploy the component. Supports wncd.Desktop or WigiiApi.WncdContainer.
 * @param Object options a set of options to configure the desktop component. It supports the following attributes :
 * - height: String. CSS height of the board. Defaults to 100%.
 * - width: String. CSS width of the board. Defaults to 100%.
 * - noOrdering: Boolean. If true, then the stories cannot be re-ordered.
 * If deployed into a Wigii WncdContainer, then also supports:
 * - noNotification: Boolean. If true, then changing the status or the position of a story will not trigger a Wigii notification,
 * - noCalculation: Boolean. If true, then changing the status or the position of a story will not launch the re-calculation of calculated fields on server side.
 * - mapElement2Story: Function. Function which maps a given element to a given story.
 * - elementStatusField: String. Element field name which holds the story status. Defaults to "status".
 * - elementPositionField: String. Element field name which holds the story position. Defaults to "position".
 * - renderStory: Function. Renders the story into a column of the board, given a reference to the storyBoard, the HTML emitter for the story and a reference on the story object.
*/},
wncd.AgileStoryBoard = function(container, options) {
	var self = this;
	self.className = 'AgileStoryBoard';
	self.instantiationTime = Date.now();
	self.ctxKey = wncd.ctxKey+'_'+self.className+self.instantiationTime;
	self.options = options || {};
	self.context = {
		storyAssignees:[],
		stories:[],
		storiesIndex:{}
	};
	self.context.container = container;
	self.impl = {
	};
	
	// Properties

	self.$ = function() {return $('#'+self.ctxKey);}
	
	// Define default options		
	if(!self.options.label) self.options.label = "&#9096;" // helm symbol
	if(!self.options.title) self.options.title = wncd.getHtmlBuilder()
		.tag('p','class','title').out("&#9096;&nbsp;").out("Agile Story Board").$tag('p')
		.html();
	if(!self.options.storyStatuses) self.options.storyStatuses = ["Pipeline","Design","Execute","Ready for release","Pre-Production","Done"];
	if(!self.options.height) self.options.height = '100%';
	if(!self.options.width) self.options.width = '100%';
	if(!self.options.createStoryButtonLabel) self.options.createStoryButtonLabel = "+ Story";
	if(self.options.filterLabel===undefined) self.options.filterLabel="Show:";
	if(!self.options.filterAllLabel) self.options.filterAllLabel="All";
	if(!self.options.filterUnassignedLabel) self.options.filterUnassignedLabel="Unassigned";
	if(container && container.className == 'WncdContainer') {
		if(!self.options.elementStatusField) self.options.elementStatusField = 'status';
		if(!self.options.elementPositionField) self.options.elementPositionField = 'position';
		if(!self.options.mapElement2Story) self.options.mapElement2Story = function(element,story) {
			story.id=element.__element.id,
			story.assignee=element.assignee.value;
			story.label=element.label.value;
			story.description=element.description.value;
			story.status=element.status.value;
			if(element.position) story.position=element.position.value;
			else story.position = 0;
		};
	}
	if(!self.options.renderStory) self.options.renderStory = function(storyBoard,storyHtml,story) {		
		var self = storyBoard;			
		storyHtml
		.out("&#9998;","storyEditButton")
		.out("&#10006;","storyDeleteButton");
		if(self.options.noOrdering!=true) {
			storyHtml.out("&#11121;","storyMoveTopButton");
			storyHtml.out("&#129093;","storyMoveUpButton");
			//storyHtml.out("&#11014;","storyMoveUpButton");
			//storyHtml.out("&#11105;","storyMoveUpButton");
			storyHtml.out("&#129095;","storyMoveDownButton");
			//storyHtml.out("&#11015;","storyMoveDownButton");
			//storyHtml.out("&#11107;","storyMoveDownButton");
			storyHtml.out("&#11123;","storyMoveBottomButton");
		}
		storyHtml
		.out((story.assignee===null?self.options.filterUnassignedLabel:story.assignee),"storyAssignee")
		//.out(story.position,"storyPosition")
		if(story.label!==null) storyHtml.out(story.label,"storyLabel");
		if(story.description!==null) storyHtml.out(story.description,"storyDescription");
	};
	/**
	 * Renders footer bar
	 */
	if(!self.options.renderFooterBar) self.options.renderFooterBar = function(container) {
		wncd.currentDiv().reset().out("Wigii.org, NCD App (CWE), 24.03.2018, Agile Story Board v.1.01");
		if(container.context.startupLog) wncd.currentDiv().out(", ").out(container.context.startupLog);
	}
	
	// Implementation
	
	self.impl.renderFilters = function(container) {			
		self.impl.headerBarEmitter = wncd.currentDiv();
		wncd.currentDiv().reset();
		// Create button (only if not deployed in Wigii Wncd container)
		if(!container || container.className != 'WncdContainer') {
			wncd.currentDiv().button(self.options.createStoryButtonLabel,function(){
				self.impl.createStoryInColumn(self.options.storyStatuses[0]);
			},"storyBoardCreateButton");
		}
		if(self.options.filterLabel) wncd.currentDiv().out(self.options.filterLabel,"storyBoardFilterLabel");
		// Adds "All" button which removes filter
		wncd.currentDiv().button(self.options.filterAllLabel,function(){
			self.impl.setAssigneeFilter();
		},"storyBoardFilter","storyBoardFilter_all");
		// Adds one button per assignee which adds assignee as a filter
		for(var i=0;i<self.context.storyAssignees.length;i++) {
			var assignee = self.context.storyAssignees[i];
			wncd.currentDiv().button((assignee===null?self.options.filterUnassignedLabel:assignee),function(e,assignee){
				self.impl.setAssigneeFilter(assignee);
			},"storyBoardFilter","storyBoardFilter_"+i,assignee);
		}			
		// selects current filter
		self.impl.setAssigneeFilter(self.context.assigneeFilter);
	};
	self.impl.setAssigneeFilter = function(assignee) {
		// unselects previous filter
		var filter = 'storyBoardFilter_all';
		if(self.context.assigneeFilter!==undefined) { 
			filter = self.context.storyAssignees.indexOf(self.context.assigneeFilter);
			if(filter >= 0) filter = 'storyBoardFilter_'+filter;
			else filter = 'storyBoardFilter_all';
		}
		$('#'+filter).removeClass('activeFilter');
		// selects new filter
		filter = 'storyBoardFilter_all';
		if(assignee!==undefined) { 
			filter = self.context.storyAssignees.indexOf(assignee);
			if(filter >= 0) filter = 'storyBoardFilter_'+filter;
			else filter = 'storyBoardFilter_all';
		}
		$('#'+filter).addClass('activeFilter');
		// refreshes dashboard if assignee changed
		if(self.context.assigneeFilter!==assignee) {
			self.context.assigneeFilter=assignee;
			wncd.program.context.html(self.impl.workzoneEmitter);
			self.impl.renderStoryBoard(container);
		}			
	};
	self.impl.renderStoryBoard = function(container) {
		self.impl.workzoneEmitter = wncd.currentDiv();
		// Creates agile board
		wncd.currentDiv().reset().layout(function(h,v,x1,x2,x3,x4,x5,x6,x7,x8,x9,x10){
			var boardColumns = [];
			for(var i=0;i<self.options.storyStatuses.length;i++) {
				var statusLabel;
				if(self.options.storyStatusesLabel && self.options.storyStatusesLabel.length>i) statusLabel = self.options.storyStatusesLabel[i];
				else statusLabel = self.options.storyStatuses[i];
				
				var boardColumn = x1.v(
					x1(statusLabel).cssClass("storyBoardColumnHeader").minHeight("50px"),
					x9().cssClass("storyBoardColumnContent")
				).id(self.ctxKey+"_"+i).cssClass("storyBoardColumn");
				boardColumns.push(boardColumn);
			}				
			
			// board template
			return h.apply(undefined,boardColumns).id(self.ctxKey).cssClass("storyBoard").width("100%").height("100%");
		});	
		if(self.options.columnHeaderBackgroundColor) self.$().find("div.storyBoardColumnHeader").css("background-color",self.options.columnHeaderBackgroundColor);
		if(self.options.columnHeaderColor) self.$().find("div.storyBoardColumnHeader").css("color",self.options.columnHeaderColor);
		self.$().find("div.storyBoardColumnContent").droppable({
			accept:"div.story",
			hoverClass:"dragOn",
			drop:function(event,ui) {
				var statusIndex = Number($(this).parent().attr("id").replace(self.ctxKey+"_",''));
				var storyId = ui.draggable.attr("id").replace("story_","");
				ui.draggable.draggable("option","revert",false);
				self.impl.moveStoryToColumn(self.context.storiesIndex[storyId],self.options.storyStatuses[statusIndex])
			}
		});
		// Sorts stories by position
		self.context.stories.sort(function(a,b){
			if(a.position===undefined) a.position=0;
			if(b.position===undefined) b.position=0;
			return a.position - b.position;
		});
		// Adds stories to board
		for(var i=0;i<self.context.stories.length;i++) {
			var story = self.context.stories[i];
			// only shows story compatible with assignee filter
			// and not deleted
			if(!story.deleted && (self.context.assigneeFilter===undefined || self.context.assigneeFilter == story.assignee)) {
				self.impl.addStoryToBoard(story);
			}
		}	
	};
	
	/**
	 * Adds a story to the board in the right column
	 *@param Boolean reorder if true, then story visual position is calculated based on its position, else story is just appended at the end of the column.
	 * By default, stories are added to the board already in the right order, re-ordering is not necessary. Re-ordering is necessary only on drag & drop between columns.
	 */
	self.impl.addStoryToBoard = function(story,reorder) {
		var statusIndex = self.options.storyStatuses.indexOf(story.status);		
		if(statusIndex>=0) {
			//wigii().log(story.status+'#'+self.ctxKey+"_"+statusIndex+" div.storyBoardColumnContent");
			var storyHtml = $('#'+self.ctxKey+"_"+statusIndex+" div.storyBoardColumnContent").wncd("html")
			.div("story_"+story.id,"story");
			// records story position as an attribute
			storyHtml.$().attr('data-storypos',story.position);
			if(reorder) {
				// retrieves lower priority story (ie with higher position)
				var lowerStory = storyHtml.$().parent().find("div.story").filter(function(index,elt){
					return $(this).attr('data-storypos') > story.position;
				});
				if(lowerStory.length > 0) {
					storyHtml.$().insertBefore(lowerStory[0]);
				}
			}				
			// renders story
			self.options.renderStory(self,storyHtml,story);				
			// binds drag&drop + action buttons
			$("#story_"+story.id)				
			.draggable({
				containment:"#"+self.ctxKey,
				cursor:"move",
				helper:"clone",
				revert:true/*,
				stop:function(event,ui) {
					ui.helper.css("position","relative");
				}
				*/
			});
			// if deployed in Wigii container, then binds click on story to Wigii show detail url
			if(container && container.className == 'WncdContainer') $("#story_"+story.id).click(function() {
				container.showElement(story.id);
			});
			var f;
			// binds edit event				
			// if deployed in Wigii container, then binds to Wigii edit url
			if(container && container.className == 'WncdContainer') f = function(e) {
				container.editElement(story.id);
				e.stopPropagation();
			}				
			// else if standalone, then binds internal editStory function
			else f = function(){
				var storyId = $(this).parent().attr("id").replace("story_","");
				self.impl.editStory(self.context.storiesIndex[storyId]);
			}				
			$("#story_"+story.id+" span.storyEditButton").click(f);
			// binds delete event
			// if deployed in Wigii container, then binds to Wigii delete url
			if(container && container.className == 'WncdContainer') f = function(e) {
				container.deleteElement(story.id);
				e.stopPropagation();
			}				
			// else if standalone, then binds internal editStory function
			else f = function(){
				var storyId = $(this).parent().attr("id").replace("story_","");
				self.impl.deleteStory(self.context.storiesIndex[storyId]);
			}
			$("#story_"+story.id+" span.storyDeleteButton").click(f);
			// binds move top event
			if(self.options.noOrdering!=true) $("#story_"+story.id+" span.storyMoveTopButton").click(function(e){
				var storyId = $(this).parent().attr("id").replace("story_","");
				var story = self.context.storiesIndex[storyId];
				self.impl.moveStoryToTop(story);
				e.stopPropagation();
			});
			// binds move up event
			if(self.options.noOrdering!=true) $("#story_"+story.id+" span.storyMoveUpButton").click(function(e){
				var storyId = $(this).parent().attr("id").replace("story_","");
				var story = self.context.storiesIndex[storyId];
				// retrieves higher story
				var higherStory = $("#story_"+storyId).prev();
				if(higherStory) {
					storyId = higherStory.attr("id").replace("story_","");
					higherStory = self.context.storiesIndex[storyId];
					// swaps stories
					self.impl.swapStoryUp(story,higherStory);					
				}
				e.stopPropagation();
			});
			// binds move down event
			if(self.options.noOrdering!=true) $("#story_"+story.id+" span.storyMoveDownButton").click(function(e){
				var storyId = $(this).parent().attr("id").replace("story_","");
				var story = self.context.storiesIndex[storyId];
				// retrieves lower story
				var lowerStory = $("#story_"+storyId).next();
				if(lowerStory) {
					storyId = lowerStory.attr("id").replace("story_","");
					lowerStory = self.context.storiesIndex[storyId];
					// swaps stories
					self.impl.swapStoryUp(lowerStory,story);					
				}
				e.stopPropagation();
			});	
			// binds move bottom event
			if(self.options.noOrdering!=true) $("#story_"+story.id+" span.storyMoveBottomButton").click(function(e){
				var storyId = $(this).parent().attr("id").replace("story_","");
				var story = self.context.storiesIndex[storyId];
				self.impl.moveStoryToBottom(story);
				e.stopPropagation();
			});			
		}
	};
	self.impl.swapStoryUp = function(story,higherStory) {
		var higherPosition = higherStory.position;
		higherStory.position = story.position;
		story.position = higherPosition;
		// if deployed into a Wigii Wncd container, then updates the position on server
		if(container && container.className == 'WncdContainer') {
			container.saveFieldValue(story.id,self.options.elementPositionField,story.position,{							
				noCalculation:(self.options.noCalculation==true),
				noNotification:(self.options.noNotification==true)
			});
			container.saveFieldValue(higherStory.id,self.options.elementPositionField,higherStory.position,{							
				noCalculation:(self.options.noCalculation==true),
				noNotification:(self.options.noNotification==true)
			});
		}
		$("#story_"+story.id).insertBefore($("#story_"+higherStory.id));
		// updates position in dom
		$("#story_"+story.id).attr('data-storypos',story.position);
		$("#story_"+higherStory.id).attr('data-storypos',higherStory.position);
	};
	self.impl.moveStoryToTop = function(story) {
		// retrieves first story of column
		var firstStory = $("#story_"+story.id).parent().children("div:first-child");
		if(firstStory.attr('id') != "story_"+story.id) {
			// updates position of story to firstStory.position - 100
			story.position = Number(firstStory.attr('data-storypos')) - (10+90*Math.random()); //random is to minimize the risk of collision
			// if deployed into a Wigii Wncd container, then updates the position on server
			if(container && container.className == 'WncdContainer') {
				container.saveFieldValue(story.id,self.options.elementPositionField,story.position,{							
					noCalculation:(self.options.noCalculation==true),
					noNotification:(self.options.noNotification==true)
				});
			}
			// updates position in dom
			$("#story_"+story.id).insertBefore(firstStory).attr('data-storypos',story.position);
		}
	};
	self.impl.moveStoryToBottom = function(story) {
		// retrieves last story of column
		var lastStory = $("#story_"+story.id).parent().children("div:last-child");
		if(lastStory.attr('id') != "story_"+story.id) {
			// updates position of story to lastStory.position + 100
			story.position = Number(lastStory.attr('data-storypos')) + (10+90*Math.random());  //random is to minimize the risk of collision
			// if deployed into a Wigii Wncd container, then updates the position on server
			if(container && container.className == 'WncdContainer') {
				container.saveFieldValue(story.id,self.options.elementPositionField,story.position,{							
					noCalculation:(self.options.noCalculation==true),
					noNotification:(self.options.noNotification==true)
				});
			}
			// updates position in dom
			$("#story_"+story.id).insertAfter(lastStory).attr('data-storypos',story.position);
		}
	};
	self.impl.createStoryInColumn = function(status) {
		var statusIndex = self.options.storyStatuses.indexOf(status);
		if(statusIndex==-1) throw wncd.createServiceException("status '"+status+"' is not on the board",wncd.errorCodes.INVALID_ARGUMENT);
		var story = {
			id:Date.now(),
			status:status,
			position:self.context.stories.length+1,
			label:"",
			assignee:"",
			description:"",
			deleted:false
		};
		self.impl.editStory(story);
	};
	self.impl.editStory = function(story) {
		// clears the screen
		wncd.program.context.html(self.impl.workzoneEmitter);			
		wncd.currentDiv().reset().htmlBuilder().tag("div","class","storyBoardForm").insert(function(story){
			// renders a form to input the story
			wncd.form.no("editStory").supprimer();
			wncd.form.no("editStory")
			.createField("storyLabel","Titre du déliverable")
			.createField("storyAssignee","Assigné à")
			.createTextField("storyDescription","Décrire succintement le travail à accomplir")
			.field("storyLabel").value(story.label).focus();
			wncd.form.no("editStory").field("storyDescription").value(story.description);
			wncd.form.no("editStory").field("storyAssignee").value(story.assignee).context.display.autocomplete(self.context.storyAssignees);
			// shows an OK button to save the story
			wncd.currentDiv().button("OK",function(){
				story.label = wncd.form.no("editStory").field("storyLabel").value();
				story.assignee = wncd.form.no("editStory").field("storyAssignee").value();
				story.description = wncd.form.no("editStory").field("storyDescription").value();
				// creates story if needed
				if(!self.context.storiesIndex[story.id]) {
					self.context.stories.push(story);
					self.context.storiesIndex[story.id] = story;
				}
				// adds assignee if new one
				if(self.context.storyAssignees.indexOf(story.assignee)<0) {
					self.context.storyAssignees.push(story.assignee);
					wncd.program.context.html(self.impl.headerBarEmitter);
					self.impl.renderFilters(container);
				}
				// refreshes the story board
				wncd.program.context.html(self.impl.workzoneEmitter);		
				self.impl.renderStoryBoard(container);
			})
			/* shows a cancel button which displays again board without changes */
			.button("Cancel",function(){ 
				wncd.program.context.html(self.impl.workzoneEmitter);		
				self.impl.renderStoryBoard(container); }
			);			
		},story).$tag("div").emit();
	};
	self.impl.deleteStory = function(story) {
		story.deleted = true;
		self.impl.removeStoryFromBoard(story);
	};
	self.impl.removeStoryFromBoard = function(story) {
		$("#story_"+story.id).remove();
	};
	self.impl.moveStoryToColumn = function(story, status) {
		if(status!=story.status) {
			// if deployed into a Wigii Wncd container, then updates the status on server
			if(container && container.className == 'WncdContainer') {
				var rollbackStatus = story.status;
				container.saveFieldValue(story.id,self.options.elementStatusField,status,{
					exceptionHandler:function(exception,context){
						// rollbacks
						self.impl.removeStoryFromBoard(story);
						story.status = rollbackStatus;			
						self.impl.addStoryToBoard(story,true);
						// displays exception
						wncd.publishWigiiException(exception,context);
					},
					noCalculation:(self.options.noCalculation==true),
					noNotification:(self.options.noNotification==true)
				});
			}
			// do the local changes
			self.impl.removeStoryFromBoard(story);
			story.status = status;			
			self.impl.addStoryToBoard(story,true);
		}
	};
	
	// Deploys into desktop if defined		
	if(container && container.className == 'Desktop') {
		self.options.obj = self; // passes the AgileStoryBoard to the Desktop through the component object
		container.registerComponent(self.ctxKey,self.impl.renderFilters,self.impl.renderStoryBoard,self.options.renderFooterBar,self.options);
	}
	// else deploys in current div
	else {
		// if deployed into Wigii Wncd container, then pre-loads the data
		if(container && container.className == 'WncdContainer') {
			var wigiiModel = container.getWigiiDataModel();
			container.iterateOnElementList(wigiiModel,function(index,elementId,element){
				var story = {};
				self.options.mapElement2Story(element,story);
				self.context.stories.push(story);
				self.context.storiesIndex[story.id] = story;
				// adds assignee if new one
				if(self.context.storyAssignees.indexOf(story.assignee)<0) {
					self.context.storyAssignees.push(story.assignee);
				}
			});
			// registers on dataChange event
			container.dataChange(function(container,wigiiModel) {
				container.iterateOnElementList(wigiiModel,function(index,elementId,element){
					var story = {};
					self.options.mapElement2Story(element,story);
					// adds or replaces story object
					self.context.storiesIndex[story.id] = story;
					// adds assignee if new one
					if(self.context.storyAssignees.indexOf(story.assignee)<0) {
						self.context.storyAssignees.push(story.assignee);
					}
					// re-builds story array
					self.context.stories = Object.values(self.context.storiesIndex);
					// re-paints the filters and board
					wncd.program.context.html(self.impl.headerBarEmitter);
					self.impl.renderFilters(container);
					wncd.program.context.html(self.impl.workzoneEmitter);		
					self.impl.renderStoryBoard(container);
				});
			});
			// registers on elementDeleted event
			container.elementDeleted(function(container,elementId){
				var story = self.context.storiesIndex[elementId];
				if(story) self.impl.deleteStory(story);
			});
			
		}
		wncd.currentDiv().layout(function(h,v,x1,x2,x3,x4,x5,x6,x7,x8,x9,x10){
			return v(
				x1().id(self.ctxKey+"_filters").cssClass("storyBoardFilters"),
				x10().id(self.ctxKey+"_storyBoard")
			).width(self.options.width).height(self.options.height)
		});			
		wncd.program.context.html($("#"+self.ctxKey+"_filters").wncd('html'));
		self.impl.renderFilters(container);
		wncd.program.context.html($("#"+self.ctxKey+"_storyBoard").wncd('html'));
		self.impl.renderStoryBoard(container);
	}
});
wncd.createAgileStoryBoard = function(container, options) {return new wncd.AgileStoryBoard(container,options);};