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
 * Updated version 2.0 by Camille Weber (camille@wigii.org), 17.10.2016
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
	wigiiNcdEtp.ctlSeq = ctlSeq;
	wigiiNcdEtp.sequence = ctlSeq; 
	wigiiNcdEtp.sequence.ajouter = wigiiNcdEtp.sequence.addFx; 
	wigiiNcdEtp.sequence.fin = wigiiNcdEtp.sequence.toFx; 
	wigiiNcdEtp.scripte = scripte; 
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
		if(footer.length>0) footer.append('<span><i>&nbsp;(etp-fx v.'+wigiiNcdEtp.version()+' loaded)</i></span>');
	}
	if(wigiiNcdEtpOptions.ncdEtpFxReady) wigiiNcdEtpOptions.ncdEtpFxReady(wigiiNcdEtp);
})(window, jQuery, wigiiNcd, wigiiNcdEtp);