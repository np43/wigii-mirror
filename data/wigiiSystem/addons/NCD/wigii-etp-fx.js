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
					newFxCtx.html().end();
				}
				catch(exc) {newFxCtx.html().publishException(exc);}
			}
			/* returnValue is ignored */
		};
		// runs the program
		try {
			newFxCtx.html().clearErrors();
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
			newFxCtx.html().end();
		}
		catch(exc) {newFxCtx.html().publishException(exc);}
	};
	
	/**
	 * Wigii NCD contextual runtime object
	 *@param wncd.HtmlEmitter htmlEmitter underlying HtmlEmitter on which to plug a Wigii NCD runtime
	 *@param Object options a set of options to configure the runtime. It supports the following attributes : nothing yet.
	 */
	wigiiNcdEtp.Runtime = function(htmlEmitter,options) {
		var self = this;
		self.className = 'Runtime';
		self.instantiationTime = Date.now();
		self.ctxKey = wigiiNcdEtp.ctxKey+'_'+self.className+self.instantiationTime;
		self.options = options || {};
		self.context = {html:htmlEmitter};
		self.impl = {};
		
		// Defines default options
		/* nothing yet */
		
		/**
		 * Executes a program given as a list of Func Exp
		 */
		self.program = function() {
			// switches to given htmlEmitter
			self.context.currentDiv = wncd.currentDiv();
			wncd.program.context.html(self.context.html);
			// executes the program
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
						newFxCtx.html().end();
					}
					catch(exc) {newFxCtx.html().publishException(exc);}
					// switches back html context
					wncd.program.context.html(self.context.currentDiv);
				}
				/* returnValue is ignored */
			};
			// runs the program
			try {
				newFxCtx.html().clearErrors();
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
				newFxCtx.html().end();
			}
			catch(exc) {newFxCtx.html().publishException(exc);}
			// switches back html context
			wncd.program.context.html(self.context.currentDiv);
		};
		self.programme = self.program;
	};
	/**
	 * Creates a Wigii NCD contextual runtime object
	 *@param wncd.HtmlEmitter htmlEmitter underlying HtmlEmitter on which to plug a Wigii NCD runtime
	 *@param Object options some options to configure the Runtime.
	 *@return wigiiNcdEtp.Runtime
	 */
	wigiiNcdEtp.createRuntime = function(htmlEmitter,options) { return new wigiiNcdEtp.Runtime(htmlEmitter,options);}	
	
	/**
	 * JQuery NCD plugin binding a Wigii NCD runtime to a given anchor
	 *@return wncd.Runtime
	 */
	wigiiNcd().getJQueryService().run = function(selection,options) {
		var returnValue=undefined;
		// checks we have only one element
		if(selection && selection.length==1) {		
			// creates a Runtime
			returnValue = wigiiNcdEtp.createRuntime(wncd.html(selection),options);
		}
		else if(selection && selection.length>1) throw wncd.createServiceException('Wigii NCD run selector can only be activated on a JQuery collection containing one element and not '+selection.length, wncd.errorCodes.INVALID_ARGUMENT);
		return (!returnValue?{$:selection}:returnValue);
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
					catch(exc) {fxCtx.html().publishException(exc);}
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
	
	/**
	 * Interrupts the current FuncExp flow and stores the context so that it can be resumed in future.
	 *@param String fxCtxHolder key under which the fxCtx is stored. Defaults to 'interruption'
	 *@param Object fxCtxStorage object in which the fxCtx is stored. Defaults to programme.context.
	 *@example To interrupt the current flow, call interrupt() Fx.
	 * To resume the interrupted Fx flow, call programme.context.interruption.resume();
	 * To identify the current interrupted flow, call interrupt("myFlow")
	 * Then call: programme.context.myFlow.resume();
	 */
	var interrupt = function(fxCtxHolder,fxCtxStorage) {
		var returnValue = function(fxCtx){			
			if(fxCtx) {
				if(!fxCtxStorage) fxCtxStorage = programme.context;
				if(!fxCtxHolder) fxCtxHolder = 'interruption';
				// stores fxCtx
				fxCtxStorage[fxCtxHolder] = fxCtx;
				// pauses fx flow
				fxCtx.pause();
			}
		};
		returnValue.toFxString = function() {
			var fxs = 'interrupt(';
			if(fxCtxHolder) fxs += wigiiNcd().obj2FxString(fxCtxHolder);
			else fxs += '"interruption"';
			fxs += ',';
			if(fxCtxStorage) fxs += wigiiNcd().obj2FxString(fxCtxStorage);
			else fxs += 'programme.context';
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
	var bouton = function(label,onClick,cssClass,id,context) {return dynImpl_fx_s("bouton",function(fxCtx){return fxCtx.html().button;},label,function(){return onClick;},cssClass,id,context);};
	

	
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
						'style','float:left;'
						+(options.minWidth?'min-width:'+options.minWidth+';':'')+'width:'+options.width+'%;'+(options.maxWidth?'max-width:'+options.maxWidth+';':'')
						+(options.minHeight?'min-height:'+options.minHeight+';':'')+'height:'+options.height+'%;'+(options.maxHeight?'max-height:'+options.maxHeight+';':'')
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
					'style','float:left;'
					+(options.minWidth?'min-width:'+options.minWidth+';':'')+'width:'+width+';'+(options.maxWidth?'max-width:'+options.maxWidth+';':'')
					+(options.minHeight?'min-height:'+options.minHeight+';':'')+'height:'+height+';'+(options.maxHeight?'max-height:'+options.maxHeight+';':'')
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
					'style','float:left;'
					+(options.minWidth?'min-width:'+options.minWidth+';':'')+'width:'+width+';'+(options.maxWidth?'max-width:'+options.maxWidth+';':'')
					+(options.minHeight?'min-height:'+options.minHeight+';':'')+'height:'+height+';'+(options.maxHeight?'max-height:'+options.maxHeight+';':'')
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
			 *@param Object options some rendering options, like the id, cssClass, width or height,minWidth,maxWidth,minHeight,maxHeight.
			 */
			layout.fx = function(name,args,options) {
				options = {
					name:name,
					id:'',
					cssClass:'',
					minWidth:'',
					maxWidth:'',
					minHeight:'',
					maxHeight:'',
					weight:layout.weightOf(name),
					h:(options?options.h:undefined),
					v:(options?options.v:undefined)
				};
				
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
				/**
				 * Sets a min Width to the given box
				 */
				returnValue.minWidth = function(minWidth) {options.minWidth = minWidth;return returnValue;};
				/**
				 * Sets a max Width to the given box
				 */
				returnValue.maxWidth = function(maxWidth) {options.maxWidth = maxWidth;return returnValue;};
				/**
				 * Sets a min Height to the given box
				 */
				returnValue.minHeight = function(minHeight) {options.minHeight = minHeight;return returnValue;};
				/**
				 * Sets a max Height to the given box
				 */
				returnValue.maxHeight = function(maxHeight) {options.maxHeight = maxHeight;return returnValue;};
				
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
					if(options.minWidth) fxs += '.minWidth("'+options.minWidth+'")';
					if(options.maxWidth) fxs += '.maxWidth("'+options.maxWidth+'")';
					if(options.minHeight) fxs += '.minHeight("'+options.minHeight+'")';
					if(options.maxHeight) fxs += '.maxHeight("'+options.maxHeight+'")';
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
	programme.bouton = function(label,onClick,cssClass,id,context) {programme.currentDiv().bouton(label,onClick,cssClass,id,context);return programme;};
	programme.codeSource = function() {return programme.context.codeSource;}; 
	programme.libSource = libSource; 
	programme.libPublic = libPublic;
	programme.txtDate = wigiiNcd().txtDate; 
	programme.txtFrenchDate = wigiiNcd().txtFrenchDate; 
	programme.createSelectionSense = wigiiNcd().createSelectionSense; 
	programme.bindSelectionSense = wigiiNcd().bindSelectionSense; 
	programme.createCountingSense = wigiiNcd().createCountingSense;
	programme.bindCountingSense = wigiiNcd().bindCountingSense;
	programme.ctlSel = wigiiNcd().sel;	
	
	
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
	wigiiNcdEtp.interrupt = interrupt; 
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