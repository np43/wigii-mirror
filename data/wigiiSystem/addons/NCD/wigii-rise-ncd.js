/**
 *  Rise.wigii.org NCD Web Service JS client
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
 
/**
 * Rise.wigii.org NCD Web Service JS client
 * Created by Camille Weber on 08.11.2016
 * Modified by Camille Weber on 18.04.2018 to include Move Forward project
 * @param wigiiFacade wigii the Wigii JS client facade function (defined in /assets/js/wigii_XXXX.js)
 * @param JQuery $ depends on JQuery 1.8.x
 */
(function(wigii,$,SITE_ROOT) {
	var wigiiApi = wigii();
	if(!wigiiApi.SITE_ROOT) wigiiApi.SITE_ROOT = SITE_ROOT;
	
	/**
	 * Rise NCD client class
	 */
	var RiseNcd = function() {
		var riseNcd = this;
		riseNcd.className = 'RiseNcd';
		riseNcd.instantiationTime = (new Date()).getTime();
		riseNcd.ctxKey = wigiiApi.ctxKey+'_'+riseNcd.className+'_'+riseNcd.instantiationTime;		
		
		riseNcd.context = {
			mfCodeCache: {}
		};		
		
		riseNcd.login = function(username, password) {				
			return riseNcd.call('sysLogin("'+username+'","'+password+'")');			
		};
		riseNcd.ping = function() {
			return riseNcd.call('riseNcd_ping()');
		};		
		riseNcd.version = function() {
			return riseNcd.call('riseNcd_version()');
		};
		riseNcd.sysUsername = function() {
			if(!riseNcd.context.username) {
				riseNcd.context.username = riseNcd.call('sysUsername()');
			}
			return riseNcd.context.username;
		};
		riseNcd.storeData = function(elementId, keyField, data) {
			riseNcd.call('riseNcd_storeData("'+elementId+'"'+(keyField?',"'+keyField+'"':'')+')', data);
		};
		riseNcd.getData = function(elementId) {
			var data = riseNcd.call('riseNcd_getData("'+elementId+'")');
			if(data) {
				//data = data.replace(/\n|\r/g,' ');
				//console.debug(data);
				data = JSON.parse(data);
			}
			return data;
		};		
		riseNcd.createDataStorage = function(groupId, description) {
			return riseNcd.call('riseNcd_createDataStorage("'+groupId+'"'+(description?',"'+description+'"':'')+')');
		};
		/**
		 * Calls synchronously Rise NCD Web service and returns the result as a String
		 *@param String fx the FuncExp to call as a web service expression
		 *@param Object|Array data optional data to post with the Fx call. (if null, then calls using GET, else POSTS)
		 */
		riseNcd.call = function(fx,data) {		
			var url = wigiiApi.SITE_ROOT+'NCD/Espace/fx/'+$.base64EncodeUrl(fx);
			var returnValue;
			if(data) {
				$.ajax({type:"POST",
					url:url,
					dataType:'json',
					contentType: 'text/plain',
					data: JSON.stringify(data),
					crossDomain: true,
					xhrFields: {withCredentials: true},
					async:false,
					success: function(data) {returnValue=data;}					
				});
			}
			else {
				$.ajax({type:"GET",
					url:url,
					crossDomain: true,
					xhrFields: {withCredentials: true},
					async:false,
					success: function(data) {returnValue=data;}					
				});
			}
			return returnValue;
		};
		
		// Projet ATELIER ENCODE / PARTAGE
		
		riseNcd.partage_sauverArticle = function(repositoryId,withContent,articles) {
			riseNcd.call('partage_sauverArticle("'+repositoryId+'"'+(withContent?',logTrue()':',logFalse()')+')', articles);
		};
		riseNcd.partage_rateArticle = function(repositoryId,rating,articles) {
			riseNcd.call('partage_rateArticle("'+repositoryId+'","'+rating+'")', articles);
		};
		riseNcd.partage_sauverContenu = function(repositoryId,contenus) {
			riseNcd.call('partage_sauverContenu("'+repositoryId+'")', contenus);
		};
		
		// Projet ATELIER ENCODE / MOVE Forward
		
		/**
		 * display svg code in a given svg tag
		 * @param String svg code to insert
		 * @param String jquery selector on a svg tag
		 */
		riseNcd.putSVG = function(svg, selector) {
			var div= document.createElementNS('http://www.w3.org/1999/xhtml', 'div');
			var svgTag = ["svg"];
			div.innerHTML= wncd.getHtmlBuilder().tag.apply(undefined,svgTag).out(svg).$tag('svg').html();
			var frag= document.createDocumentFragment();
			while (div.firstChild.firstChild){
				frag.appendChild(div.firstChild.firstChild);
			}
			$(selector).append(frag);
			return true;
		};
		
		/**
		 * Loads some svg code from Rise.wigii.org catalog and displays it into a given svg tag
		 *@param String codeId ID of the object stored into the catalog
		 *@param String jquery selector on a svg tag
		 */
		riseNcd.loadSVG = function(codeId,selector) {
			riseNcd.mf_actOnCode(codeId, function(code){
				riseNcd.putSVG(code.svgDefs, selector);
				riseNcd.putSVG(code.svg, selector);
			});
		};
		
		/**
		 * Loads a visible object from the catalog and displays it into the given target div.
		 *@param String catalogId Visible Object ID to load from rise.wigii.org Move Forward catalog.
		 *@param JQuery targetDiv a JQuery selector on a div in which to display the loaded visible object
		 *@param object options a bag of options to be applied when loading the visible object from the catalog. Supports the following options:
		 * - name: VisibleObject logical name to identify it on the scene (for example, explorer or bootle or chair, etc). If not given, a name is generated based on the catalogId.
		 * - show: VisibleState name. Instructs the system to show the visible object in this state by default. (for example: walking).
		 * - freq: Positive int. Gives the frequency at which the visible state should change its inner views.
		 * - actOnDone: optional callback which is called when the VisibleObject has been successfully loaded.
		 * - addVOTo: Object. Optional object which will receive the loaded VisibleObject under property 'vo'. The property has boolean value false, until the VisibleObject is loaded.
		 * - x: int. Gives the X (horizontal) coordinate (relative to SVG container center) where to position the visible object.
		 * - y: int. Gives the Y (vertical) coordinate (relative to SVG container center) where to position the visible object.
		 * - position: string, one of N,S,E,W,NW,NE,center,SE,SW. Gives the positioning code of the visible object relative to the center of the container. Center by default.
		 * - width: string. Relative (%) or absolute (px) width of the visible object
		 * - height: string. Relative (%) or absolute (px) height of the visible object
		 * - size: percent. Relative (%) size of the visible object according to container. 
		 *@return object return a Visible Object proxy of the form {loading:true,loaded:false}
		 * Once loading is false and loaded is true, then the proxy exposes a read method which enables to fetch the loaded and ready MoveForward.VisibleObject
		 *@example var exploVo = wncd.mf.load("PERS201800031",$("explo#div"));
		 * then in the playing control loop ...
		 * if(exploVo.loading) break;
		 * if(exploVo.loaded) exploVo = exploVo.read();
		 * ... exploVo normal usage.
		 */
		riseNcd.loadVisibleObject = function(catalogId,targetDiv,options) {
			options = options || {};
			options.load = catalogId;
			return $(targetDiv).wigii('mf').vo(options.name,options);
		};
		/**
		 *@see loadVisibleObject
		 */
		riseNcd.loadVO = riseNcd.loadVisibleObject;
		/**
		 * Fetches asynchronously an object into Rise.wigii.org Move Forward catalog and executes some action on the fetched code
		 *@param String codeId ID of the object stored into the catalog
		 *@param Function callback action to do on the fetched code. Callback is a function which takes one parameter of type object of the form
		 *{ id: object ID in catalog,
		 *  type: svg|ncd type of code fetched,
		 *  svg: String. SVG code fetched if defined,
		 *  ncd: String. WNCD code fetched if defined
		 *  objectName: String. Name of object in catalog
	     *  objectType: String. Type of object in catalog
		 *}
		 * If type is ncd both svg and ncd code can be defined. In that case, ncd holds an expression which uses in some way the svg code.
		 *@param Function exceptionHandler an optional exception handler in case of error. Function signature is exceptionHandler(exception,context) where exception is Wigii API exception object of the form {name:string,code:int,message:string}
		 * and context is an object with some server context information of the form {request:string, wigiiNamespace:string, module:string, action:string, realUsername:string, username:string, principalNamespace:string, version:string}
		 */
		riseNcd.mf_actOnCode = function(codeId,callback,exceptionHandler) {
			// if code is already in cache, then executes directly action on it
			if(riseNcd.context.mfCodeCache[codeId]) {
				if($.isFunction(callback)) {
					try {callback(riseNcd.context.mfCodeCache[codeId]);}
					catch(exc) {						
						if($.isFunction(exceptionHandler)) exceptionHandler(exc);
						else wigiiApi.publishException(exc);
					}
				}
			}
			// else loads it first and puts it in cache.
			else {
				// extracts any postfixed svgId
				var svgId = undefined;
				if(codeId.indexOf('__'>=0)) {
					codeId = codeId.split('__');
					svgId = codeId[1];
					codeId = codeId[0];
				}
				// code parts loader
				var loadSvgCodeParts = function(code,svgCode,svgId) {
					svgElt = svgCode.find('#'+svgId);
					// if specified svgId is found in svg code, 
					// then clones code as a codePart and assigns it to this svgId
					if(svgElt.length==1) {
						var codePart = Object.assign({},code);
						codePart.id += '__'+svgId;
						svgElt[0].id = codePart.id;
						codePart.svg = wigiiApi.xml2string(svgElt[0]);
						riseNcd.context.mfCodeCache[codePart.id] = codePart;
						// extracts all siblings element which have a defined id
						svgElt.siblings().each(function(i,elt){
							if(elt.id) {
								// clones code as a codePart and assigns it the specified id
								codePart = Object.assign({},code);
								codePart.id += '__'+elt.id;
								elt.id = codePart.id;
								codePart.svg = wigiiApi.xml2string(elt);
								// clears any attached NCD code to prevent duplicate execution
								codePart.ncd = undefined;
								codePart.type = 'svg';
								riseNcd.context.mfCodeCache[codePart.id] = codePart;
							}
						});
					}
				};
				// if loading a part, checks that root code is not already present in cache
				if(svgId && riseNcd.context.mfCodeCache[codeId]) {
					try {
						var code = riseNcd.context.mfCodeCache[codeId];
						if(code.svg) {
							// parses SVG as XML and loads jQuery on it
							var svgCode = $.parseXML(code.svg);
							loadSvgCodeParts(code,$(svgCode),svgId);
							if($.isFunction(callback)) callback(riseNcd.context.mfCodeCache[codeId+(svgId?'__'+svgId:'')]);
						}
					}
					catch(exc) {						
						if($.isFunction(exceptionHandler)) exceptionHandler(exc);
						else wigiiApi.publishException(exc);
					}
				}
				else {
					wigiiApi.callFx('mf_jsonEncode(mf_getCode("'+codeId+'"))',{
						fxEndPoint:wigiiApi.SITE_ROOT+'NCD/Catalog/fx/',
						resultHandler:function(code) {
							try {
								code = JSON.parse(code);
								// cleans up svg code from non needed elements and keeps defs separated
								if(code.svg) {
									// parses SVG as XML and loads jQuery on it
									var svgCode = $.parseXML(code.svg);
									svgCode = $(svgCode).find('svg');
									// extracts SVG defs and saves it into code.svgDefs
									var svgElt = svgCode.children('defs');
									if(svgElt.length>0) code.svgDefs = wigiiApi.xml2string(svgElt[0]);
									// loads code parts 
									if(svgId) loadSvgCodeParts(code,svgCode,svgId);
									// extracts first valuable group of objects
									svgElt = svgCode.children('g');
									if(svgElt.length>1) {
										svgElt.wrapAll('<g/>');
										svgElt = svgCode.children('g');
									}
									if(svgElt.length==1) {
										svgCode = svgElt;
										svgElt = svgCode.children();
										if(svgElt.length==1) svgElt = svgCode.children('g');
										if(svgElt.length!=1) svgElt = svgCode;
										svgElt[0].id = code.id;
										code.svg = wigiiApi.xml2string(svgElt[0]);
									}
								}
								riseNcd.context.mfCodeCache[codeId] = code;
								if($.isFunction(callback)) callback(riseNcd.context.mfCodeCache[codeId+(svgId?'__'+svgId:'')]);
							}
							catch(exc) {						
								if($.isFunction(exceptionHandler)) exceptionHandler(exc);
								else wigiiApi.publishException(exc);
							}
						},
						exceptionHandler:exceptionHandler
					});
				}
			}
		};
		/**
		 * Clears current Move Forward cache 
		 */
		riseNcd.mf_clearCache = function() {
			riseNcd.context.mfCodeCache = {};
		};		
	};
	// registers the RiseNcd plugin into the Wigii JS client
	if(!wigiiApi['getRiseNcd']) wigiiApi.getRiseNcd = function() {
		if(!wigiiApi['riseNcdInstance']) {
			wigiiApi.riseNcdInstance = new RiseNcd();
		}
		return wigiiApi.riseNcdInstance;
	};
	
	
	
	// Wigii NCD Move Forward
	
	/**
	 * Move Forward NCD object constructor
	 * @param Object self javascript object to initialize as a Move Forward object or undefined to create a new object
	 * @param Object options a set of options to configure Move Forward
	 */
	var mfNcd = function(self, options) {
		self = self || {};
		var mf = self; // ref to current library
		mf.ncd = undefined; // internal ref to contextual wncd.mf language
		self.className = 'MoveForward';
		self.instantiationTime = Date.now();
		self.ctxKey = wncd.ctxKey+'_'+self.className+self.instantiationTime;
		self.options = options || {};
		
		self.context = self.context || {};
		self.context.backgroundId=undefined;
		self.context.explorerId=undefined;
		self.context.sceneId=undefined;
		self.context.sceneQueue=[];
		
		self.impl = self.impl || {};
		self.impl.gameBoard=undefined;		
		self.impl.gameRuntime=undefined;
		self.impl.pendingObjects = {};
		
		// Properties
	
		self.$ = function() {return $('#'+self.ctxKey);}
		
		// Move Forward objects
		
		/**
		 * MoveForward client Message Service
		 *@param wncd.MoveForward mf the current instance of the MoveForward client
		 *@param wncd.HtmlEmitter messageEmitter the html emitter linked to the message zone in the MoveForward client
		 *@param Object options a bag of options to configure the MessageService
		 */
		self.MessageService = function(mf,messageEmitter,options) {
			var self = this;
			self.className = 'MessageService';
			self.instantiationTime = Date.now();
			self.ctxKey = mf.ctxKey+'_'+self.className;
			self.options = options || {};
			self.context = {
				mf:mf,
				messageEmitter:messageEmitter
			};
			self.impl = {				
			};
			
			// Methods
			
			/**
			 * Logs a message in the player message zone
			 */
			self.log = function(msg) {
				self.context.messageEmitter.reset().p().out(msg).$p();
			};
			/**
			 * Clears the message zone
			 */
			self.clear = function() {
				self.context.messageEmitter.reset();
			};			
		};		
		
		/**
		 * MoveForward Game board based on HTML canvas
		 *@param wncd.MoveForward mf the current instance of the MoveForward client
		 *@param Canvas canvas the html canvas linked to the game board zone in which to draw some 2D or 3D scenes
		 *@param Object options a bag of options to configure the game board
		 */
		self.GameBoard = function(mf,canvas,options) {
			var self = this;
			self.className = 'GameBoard';
			self.instantiationTime = Date.now();
			self.ctxKey = mf.ctxKey+'_'+self.className;
			self.options = options || {};
			self.context = {
				mf:mf,
				canvas:canvas,
				width:canvas.width,
				height:canvas.height,
				ctx2D:canvas.getContext("2d")
			};
			self.impl = {				
			};
			
			// Drawing pen
			
			self.Pen = function(width,color) {
				var self = this;
				self.width = width;
				self.color = color;
			};
			self.Crayon = self.Pen;
			
			// Drawing tools
			
			self.drawLine = function(pen,x1,y1,x2,y2) {
				var ctx = self.context.ctx2D;
				ctx.strokeStyle = pen.color;
				ctx.lineWidth = pen.width;
				ctx.moveTo(x1,y1);
				ctx.lineTo(x2,y2);
				ctx.stroke();
				return self;
			};
			self.tracerTrait = self.drawLine;
			
			self.drawSquare = function(pen,x,y,l) {
				var ctx = self.context.ctx2D;
				ctx.fillStyle = pen.color;
				ctx.fillRect(x-l/2,y-l/2,l,l);
				return self;
			};
			self.tracerCarre = self.drawSquare;
			
			self.drawCircle = function(pen,x,y,r) {
				var ctx = self.context.ctx2D;	
				ctx.fillStyle = pen.color;
				ctx.beginPath();
				ctx.arc(x,y,r,0,2*Math.PI);
				ctx.fill();
				return self;
			};
			self.tracerCercle = self.drawCircle;
			
			// Event handling
			
			self.impl.mousePos = function(evt) {
				var rect = self.context.canvas.getBoundingClientRect();
				return {
					x: Math.floor(evt.clientX - rect.left),
					y: Math.floor(evt.clientY - rect.top)
				};
			};
			self.onMouseMove = function(eventHandler) {
				self.context.canvas.addEventListener('mousemove',function(evt){
					var p = self.mousePos(evt);
					eventHandler(p.x,p.y);
				});
				return self;
			};
			self.onMouseDown = function(eventHandler) {
				self.context.canvas.addEventListener('mousedown',function(evt){
					var p = self.mousePos(evt);
					eventHandler(p.x,p.y);
				});
				return self;
			};
			self.onMouseUp = function(eventHandler) {
				self.context.canvas.addEventListener('mouseup',function(evt){
					var p = self.mousePos(evt);
					eventHandler(p.x,p.y);
				});
				return self;
			};
			self.onClick = function(eventHandler) {
				self.context.canvas.addEventListener('mousedown',function(evt){
					var p = self.mousePos(evt);
					eventHandler(p.x,p.y);
				});
			};
			
			// Implementation 
			
			self.impl.showBanner = function(txt,color) {
				var ctx = self.context.ctx2D;
				ctx.font = "30px Arial";
				ctx.fillStyle = color || self.context.backgroundColor;
				ctx.textAlign = "center";
				ctx.fillText(txt,self.context.width/2,self.context.height/2);
			};
			
			self.impl.showBanner(self.ctxKey);
		};		
		
		/**
		 * MoveForward client Game board based on SVG technology
		 *@param wncd.MoveForward mf the current instance of the MoveForward client
		 *@param JQuery svg jquery selector on the SVG container
		 *@param Object options a bag of options to configure the game board
		 */
		self.GameBoardSVG = function(mf,svg,options) {
			var self = this;
			self.className = 'GameBoardSVG';
			self.instantiationTime = Date.now();
			self.ctxKey = mf.ctxKey+'_'+self.className;
			self.options = options || {};
			svg = $(svg);
			self.context = {
				mf:mf,				
			};			
			self.impl = {				
				svgEmitter:svg.wncd('html')
			};
			/**
			 * Emits some SVG code
			 *@param String svg some valid svg code to emit
			 *@param String defs some svg definitions to add
			 */
			self.impl.svgEmitter.putSVG = function(svg,defs) {
				var div= document.createElementNS('http://www.w3.org/1999/xhtml', 'div');
				var svgTag = ["svg"];
				for(var xmlns in self.options.svgXmlNamespaces) {
					svgTag.push(xmlns)
					svgTag.push(self.options.svgXmlNamespaces[xmlns]);
				}
				div.innerHTML= wncd.getHtmlBuilder().tag.apply(undefined,svgTag).out(defs||'').out(svg).$tag('svg').html();
				var frag= document.createDocumentFragment();
				while (div.firstChild.firstChild) frag.appendChild(div.firstChild.firstChild);
				self.impl.svgEmitter.putHtml(frag);
				return self.impl.svgEmitter;
			};
			/**
			 * SVG Builder
			 */
			self.impl.svgEmitter.svgBuilder = function() {
				var svgBuilder = self.impl.svgEmitter.htmlBuilder();
				svgBuilder.emit = function() {
					self.impl.svgEmitter.putSVG(svgBuilder.html());
					return self.impl.svgEmitter;
				}
				return svgBuilder;
			};
			
			// Accessors
			
			self.$ = function() { return self.svgEmitter().$();};
			
			self.svgEmitter = function() {
				return self.impl.svgEmitter;
			};
			
			/**
			 * Center of the game board in view port coordinates
			 */
			self.cx = function() {
				var r = self.$()[0].getBoundingClientRect();
				return Math.floor(r.left+(r.right-r.left)/2);
			};
			/**
			 * Center of the game board in view port coordinates
			 */
			self.cy = function() {
				var r = self.$()[0].getBoundingClientRect();
				return Math.floor(r.top+(r.bottom-r.top)/2);
			};
			/**
			 * Returns x coordinate of a named inner point
			 *@param String name a specific inner point given by its name. For example :
			 * N: top edge, center point;
			 * S: bottom edge, center point;
			 * W: left edge, center point;
			 * E: right edge, center point;
			 * NE: top-right corner;
			 * SE: bottom-right corner;
			 * SW: bottom-left corner;
			 * NW: top-left corner.
			 * Name can also be the ID of an inner SVG element for which a Rectangle will be calculated and its center returned.
			 */
			self.x = function(name) {
				var r = self.$()[0].getBoundingClientRect();
				var halfWidth = Math.floor((r.right-r.left)/2);
				var cx = Math.floor(r.left + halfWidth - mf.impl.gameBoard.cx());
				if(name===undefined 
					|| name=='center'
					|| name=='N'
					|| name=='S') return cx;
				if(name=='E'
					|| name=='NE'
					|| name=='SE') return cx + halfWidth;
				if(name=='W'
					|| name=='NW'
					|| name=='SW') return cx - halfWidth;
			};
			
			/**
			 * Returns y coordinate of a named inner point
			 *@param String name a specific inner point given by its name. For example :
			 * N: top edge, center point;
			 * S: bottom edge, center point;
			 * W: left edge, center point;
			 * E: right edge, center point;
			 * NE: top-right corner;
			 * SE: bottom-right corner;
			 * SW: bottom-left corner;
			 * NW: top-left corner.
			 * Name can also be the ID of an inner SVG element for which a Rectangle will be calculated and its center returned.
			 */
			self.y = function(name) {
				var r = self.$()[0].getBoundingClientRect();
				var halfHeight = Math.floor((r.bottom-r.top)/2);
				var cy = -Math.floor(r.top + halfHeight - mf.impl.gameBoard.cy());
				if(name===undefined 
					|| name=='center'
					|| name=='W'
					|| name=='E') return cy;
				if(name=='S'
					|| name=='SW'
					|| name=='SE') return cy - halfHeight;
				if(name=='N'
					|| name=='NW'
					|| name=='NE') return cy + halfHeight;
			};
			/**
			 * Game board width
			 */
			self.width = function() {
				var r = self.$()[0].getBoundingClientRect();
				return r.right-r.left;
			};
			/**
			 * Game board width
			 */
			self.height = function() {
				var r = self.$()[0].getBoundingClientRect();
				return r.bottom-r.top;
			};
			
			// Methods
			
			/**
			 * Loads a game SceneBackground given its catalog ID and replaces current one
			 *@return MoveForward.GameBoardSVG for chaining
			 */
			self.loadBackground = function(backgroundId,options) {
				// hides old background
				var oldSceneBackground = mf.ncd.background;
				if(oldSceneBackground) oldSceneBackground.$().hide();
				// creates new background
				var options = options || {};
				options.gameBoard = self;				
				var newSceneBackground = new mf.SceneBackground(backgroundId,options);
				mf.ncd.background = newSceneBackground;
				mf.context.backgroundId = backgroundId;
				// disposes old background
				if(oldSceneBackground) oldSceneBackground.$().remove();
				return self;
			};
			/**
			 * Loads user explorer given its VisibleObject catalog ID and replaces current one
			 *@return MoveForward.GameBoardSVG for chaining
			 */
			self.loadExplorer = function(explorerId,options) {
				if(!explorerId) throw wncd.createServiceException("explorerId cannot be null",wncd.errorCodes.INVALID_ARGUMENT);
				mf.ncd.explorer = new mf.Explorer(explorerId,options);				
				mf.context.explorerId = explorerId;
				return self;
			};			
			
			/**
			 * Gets a SVG rectangle service around the given SVG object
			 *@param Object|JQuery svgObj MoveForward object which map to a SVG element through the $() method 
			 * or directly a JQuery selector on a SVG element.
			 *@return MoveForward.RectangleSVGService
			 */
			self.getRectangleService = function(svgObj) {
				return new mf.RectangleSVGService(svgObj);
			};
			
			/**
			 * Flips the height and the width of the game board. 
			 * Only if options.flippable is true and not a square board (height != width)
			 */
			self.flip = function() {
				if(!self.options.flippable) return;
				var w = self.width();
				var h = self.height();
				if(w != h) {
					self.$().height(w);					
					self.$().width(h);
					mf.context.boardWidth = h;
					mf.context.boardHeight = w;
				}
				return self;
			};
			
			/**
			 * Aligns the board with the given rectangle.
			 * Flips the board if needed and 
			 * scales the edges to have the same shape as the given rectangle.
			 */
			self.alignWith = function(rectangle) {
				if(!self.options.flippable) return;
				var rH = rectangle.height();
				var rW = rectangle.width();
				var bH = self.height();
				var bW = self.width();				
				// flips board if needed
				if((rH - rW) * (bH - bW) < 0) self.flip();
				// if height is bigger than width, then shapes according to height
				if(rH > rW) {
					bH = mf.context.boardMaxHeight;
					bW = Math.ceil(rH/mf.context.boardMaxHeight*rW);
					self.$().height(bH);
					self.$().width(bW);
					mf.context.boardWidth = bW;
					mf.context.boardHeight = bH;
				}
				// else if width is bigger than height, then shapes according to width
				else if(rW > rH) {
					bW = mf.context.boardMaxWidth
					bH = Math.ceil(rW/mf.context.boardMaxWidth*rH);
					self.$().width(bW);
					self.$().height(bH);
					mf.context.boardWidth = bW;
					mf.context.boardHeight = bH;
				}
				return self;
			};
		};
		
		/**
		 * SVG Rectangle service which helps manipulating SVG object clipped into a rectangle
		 *@param Object|JQuery|String svgObj MoveForward object which map to a SVG element through the $() method or a JQuery selector on a SVG element.
		 */
		self.RectangleSVGService = function(svgObj) {
			var self = this;
			var svgObj = svgObj;
			self.className = 'RectangleSVGService';
			self.instantiationTime = Date.now();
			self.ctxKey = mf.ctxKey+'_'+self.className+'_'+self.instantiationTime;
			self.impl = {				
			};
			
			// registers SVG object and checks that it exposes a $() method pointing on its SVG node. If creates a wrapper.
			if(!svgObj) throw wncd.createServiceException("svgObj cannot be null",wncd.errorCodes.INVALID_ARGUMENT);
			if(!$.type(svgObj) === 'object') svgObj = $(svgObj);
			if(svgObj.jquery === $().jquery) svgObj = {$:function(){ return svgObj;}};
			self.impl.svgObj = svgObj;
			
			// Accessors
			
			self.$ = function(){return self.impl.svgObj.$();};
			self.getBoundingClientRect = function() {return self.$()[0].getBoundingClientRect();}
			
			// Methods
			
			/**
			 * Returns x coordinate of the rectangle center or a named inner point
			 *@param String name a specific inner point given by its name. For example :
			 * N: top edge, center point;
			 * S: bottom edge, center point;
			 * W: left edge, center point;
			 * E: right edge, center point;
			 * NE: top-right corner;
			 * SE: bottom-right corner;
			 * SW: bottom-left corner;
			 * NW: top-left corner.
			 * Name can also be the ID of an inner SVG element for which a Rectangle will be calculated and its center returned.
			 */
			self.x = function(name) {
				var r = self.$()[0].getBoundingClientRect();
				var halfWidth = Math.floor((r.right-r.left)/2);
				var cx = Math.floor(r.left + halfWidth - mf.impl.gameBoard.cx());
				if(name===undefined 
					|| name=='center'
					|| name=='N'
					|| name=='S') return cx;
				if(name=='E'
					|| name=='NE'
					|| name=='SE') return cx + halfWidth;
				if(name=='W'
					|| name=='NW'
					|| name=='SW') return cx - halfWidth;
			};
			
			/**
			 * Returns y coordinate of the rectangle center or a named inner point
			 *@param String name a specific inner point given by its name. For example :
			 * N: top edge, center point;
			 * S: bottom edge, center point;
			 * W: left edge, center point;
			 * E: right edge, center point;
			 * NE: top-right corner;
			 * SE: bottom-right corner;
			 * SW: bottom-left corner;
			 * NW: top-left corner.
			 * Name can also be the ID of an inner SVG element for which a Rectangle will be calculated and its center returned.
			 */
			self.y = function(name) {
				var r = self.$()[0].getBoundingClientRect();
				var halfHeight = Math.floor((r.bottom-r.top)/2);
				var cy = -Math.floor(r.top + halfHeight - mf.impl.gameBoard.cy());
				if(name===undefined 
					|| name=='center'
					|| name=='W'
					|| name=='E') return cy;
				if(name=='S'
					|| name=='SW'
					|| name=='SE') return cy - halfHeight;
				if(name=='N'
					|| name=='NW'
					|| name=='NE') return cy + halfHeight;
			};
			
			/**
			 * Returns the width of the rectangle in units 
			 * or scales the underlying object to have a width equal to the given percentage of GameBoard width.
			 *@param String percent a portion of the GameBoard width in percentage
			 */
			self.width = function(percent) {
				var r = self.$()[0].getBoundingClientRect();
				var w = Math.floor(r.right-r.left);
				if(percent && w>0) {
					// computes new width
					var newW = w;
					if($.type(percent)==='number') newW = percent; 
					else if(percent.indexOf('px')>0) newW = Number(percent.replace('px','').trim());
					else if(percent.indexOf('%')>0) newW = Number(percent.replace('%','').trim())/100 * mf.impl.gameBoard.width();
					newW = Math.abs(newW);
					if(newW != w) {
						var cx = self.x(); var cy = self.y();
						newW = newW/w;					
						// adds scale transformation
						var t = mf.impl.gameBoard.$()[0].createSVGTransform();
						t.setScale(newW,newW);
						var tList = self.$()[0].transform;
						if(tList) tList = tList.baseVal;
						if(tList) {
							// prepends new transformation
							tList.insertItemBefore(t,0);
							// consolidates in one matrix
							tList.consolidate();
							// re-centers the object
							self.position(cx,cy);
						}
					}
					return self;
				}
				else return w;
			};
			
			/**
			 * Returns the height of the rectangle in units 
			 * or scales the underlying object to have a height equal to the given percentage of GameBoard height.
			 *@param String percent a portion of the GameBoard height in percentage
			 */
			self.height = function(percent) {
				var r = self.$()[0].getBoundingClientRect();
				var h = Math.floor(r.bottom-r.top);
				if(percent && h>0) {
					// computes new height
					var newH = h;
					if($.type(percent)==='number') newH = percent; 
					else if(percent.indexOf('px')>0) newH = Number(percent.replace('px','').trim());
					else if(percent.indexOf('%')>0) newH = Number(percent.replace('%','').trim())/100 * mf.impl.gameBoard.height();
					newH = Math.abs(newH);
					if(newH != h) {
						var cx = self.x(); var cy = self.y();
						newH = newH/h;
						// adds scale transformation
						var t = mf.impl.gameBoard.$()[0].createSVGTransform();
						t.setScale(newH,newH);
						var tList = self.$()[0].transform;
						if(tList) tList = tList.baseVal;
						if(tList) {
							// prepends new transformation
							tList.insertItemBefore(t,0);
							// consolidates in one matrix
							tList.consolidate();
							// re-centers the object
							self.position(cx,cy);
						}
					}
					return self;
				}
				else return h;
			};
			
			/**
			 * Scales the underlying object to take the given percentage of the GameBoard size, but keeping the right proportions.			
			 */
			self.size = function(percent) {
				// if board is horizontal, then resizes height of object to ensure not to cut some edges
				if(mf.impl.gameBoard.width() > mf.impl.gameBoard.height()) return self.height(percent);
				// else resizes width
				else self.width(percent);
			};
			
			/**
			 * Positions the underlying object relatively to a given point (x,y)
			 *@param Int x horizontal coordinate of the point on which to position the object
			 *@param Int y vertical coordinate of the point on which to position the object
			 *@param String pos specifies the position of the rectangle relatively to the given point.
			 * If pos equals :
			 * center: svg object is centered on the given point (x,y)
			 * N: svg object bottom edge center is on the given point
			 * S: svg object top edge center is on the given point
			 * W: svg object right edge center is on the given point
			 * E: svg object left edge center is on the given point
			 * NE: svg object bottom left corner is on the given point
			 * SE: svg object top left corner is on the given point
			 * SW: svg object top right corner is on the given point
			 * NW: svg object bottom left corner is on the given point
			 *
			 * This function also supports passing an object {x,y,pos|position} describing the position instead of individual arguments.
			 * position({x:0,y:0,pos:'N'})
			 */
			self.position = function(x,y,pos) {
				// checks if first argument is an object and extracts the fields (x,y,pos)
				if($.isPlainObject(x)) {
					y = x.y;
					pos = x.pos || x.position;
					x = x.x;
				}
				// computes position
				var basePos = 'center';
				switch(pos) {
					case 'N': basePos='S';break;
					case 'S': basePos='N';break;
					case 'E': basePos='W';break;
					case 'W': basePos='E';break;
					case 'NE': basePos='SW';break;
					case 'NW': basePos='SE';break;
					case 'SW': basePos='NE';break;
					case 'SE': basePos='NW';break;
				}
				// computes translation amplitude
				var dx = x-self.x(basePos);
				var dy = y-self.y(basePos);
				// creates translation transformation
				var t = mf.impl.gameBoard.$()[0].createSVGTransform();
				t.setTranslate(dx,-dy);
				var tList = self.$()[0].transform;
				if(tList) tList = tList.baseVal;
				if(tList) {
					// prepends new transformation
					tList.insertItemBefore(t,0);
					// consolidates in one matrix
					tList.consolidate();
				}
			};
			
			/**
			 * Exposes all RectangleService methods directly into the attached SVG object
			 */
			self.attach = function() {
				self.impl.svgObj.x = function(name) {return self.x(name);};
				self.impl.svgObj.y = function(name) {return self.y(name);};
				self.impl.svgObj.width = function(percent) {
					if(percent===undefined) return self.width();
					else {
						self.width(percent);
						return self.impl.svgObj;
					}
				};
				self.impl.svgObj.height = function(percent) {
					if(percent===undefined) return self.height();
					else {
						self.height(percent);
						return self.impl.svgObj;
					}
				};
				self.impl.svgObj.position = function(x,y,pos) {
					self.position(x,y,pos);
					return self.impl.svgObj;
				};
				self.impl.svgObj.size = function(percent) {
					self.size(percent);
					return self.impl.svgObj;
				};
				self.impl.svgObj.getBoundingClientRect = function() {
					return self.getBoundingClientRect();
				};
			};
		};
		
		
		// Move Forward Logical Objects
		
		/**
		 * Object Visible State
		 */
		self.ObjVisibleState = function(name,objs,options) {
			var self = this;
			self.className = 'ObjVisibleState';
			self.instantiationTime = Date.now();
			self.ctxKey = mf.className+'_'+self.className+'_'+self.instantiationTime;
			self.options = options || {};
			self.context = {
				name:name,
				objs:undefined,
				objsOnBoard:{},
				objVisible:undefined,
				selectionSense:undefined,
				ready:false
			};
			self.impl = {};
			
			// Accessors
			
			self.$ = function() { return mf.impl.gameBoard.svgEmitter().$().find('#'+self.ctxKey+self.context.objVisible);};
			self.name = function() {return self.context.name;};
			
			// Methods
			
			/**
			 * Shows the visible state views
			 *@param Object options some options to configure the way views a shown. 
			 * It supports the following attributes :
			 * dx: int. A positive or negative horizontal offset from current position,
			 * dy: int. A positive or negative vertical offset from current position,
			 * dHeight: int. A positive or negative height offset from current height,
			 * dWidth: int. A positive or negative width offset from current width,
			 * freq: Number. Frequency of view changing (x/seconds). Defaults to 1.
			 * nStep: int. Number of animated steps to execute. 
			 * If not defined, then animation runs until self.context.stop is true.			 
			 */
			self.show = function(options) {
				// shows animation if several views or if specific animation options are defined
				if(self.context.objs && (self.context.objs.length > 1) || options) {
					var f = 1;
					if(options && options.freq) f = options.freq;
					self.context.freq = f;
					var nStep = undefined;
					if(options && options.nStep) nStep = options.nStep;
					self.context.stop = false;
					self.impl.animationRuntime.program(wncd.ctlGen(
						function(step){return !self.context.stop && (!nStep || step<=nStep);},
						function(step){return wncd.ctlSeq(wncd.script(function(){
							self.impl.show((step-1)%self.context.objs.length,options)
						}),wncd.pause(1/f));}
					));
				}
				// else shows only single view
				else self.impl.show(0,options);
			};
			self.impl.show = function(index,options) {
				if(!index) index=0;
				if(self.context.objs && (index !== self.context.objVisible || options)) {
					var pos = undefined;
					if(self.context.objVisible !== undefined) {
						// stores current position and size
						pos = {x:self.x(),y:self.y(),height:self.height(),width:self.width()};
						// hides current object
						self.$().hide();					
					}
					self.context.objVisible=index;
					// if new obj is not on board, then adds it
					if(!self.context.objsOnBoard[index]) {
						var code = self.context.objs[index];						
						var svgObj = mf.impl.gameBoard.svgEmitter().putSVG(code.svg,code.svgDefs)
						.$().find('#'+code.id).attr('id',self.ctxKey+index);
						// binds selection sense if defined
						if(self.context.selectionSense) self.context.selectionSense.bind(svgObj);
						self.context.objsOnBoard[index] = true;						
					}
					// else only shows it
					else {
						self.$().show();
					}					
					if(pos) {
						// transforms current position and size with provided offset
						if(options) {
							if(options.dx) pos.x += options.dx;
							if(options.dy) pos.y += options.dy;
						}
						// sets position on new object
						self.position(pos.x,pos.y);
												
						// transforms current size with provided offset
						if(options) {
							if(options.dHeight) {
								pos.height += options.dHeight;
								pos.width = undefined;
							}
							else if(options.dWidth) {
								pos.width += options.dWidth;
								pos.height = undefined;
							}
						}						
						// sets size on new object						
						if(pos.height) self.height(pos.height);
						else if(pos.width) self.width(pos.width);
					}
				}
			};			
			/**
			 * Hides current visible state
			 */
			self.hide = function() {
				if(self.context.objVisible !== undefined) {
					self.$().hide();
					self.context.objVisible = undefined;
					// stops animation
					self.context.stop=true;
				}
			};
			
			/**
			 * Registers a onReady event handler
			 */
			self.ready = function(onReady) {
				if($.isFunction(onReady)) {
					if(self.context.ready) onReady(self);
					else {
						if(!self.context.onReadySubscribers) self.context.onReadySubscribers = [];
						self.context.onReadySubscribers.push(onReady);
					}
				}
				else if(onReady===undefined) {
					self.context.ready = true;
					if(self.context.onReadySubscribers) {
						for(var i=0;i<self.context.onReadySubscribers.length;i++) {
							var eh = self.context.onReadySubscribers[i];
							if($.isFunction(eh)) eh(self);
						}
						// empties subscribers list
						self.context.onReadySubscribers=undefined;
					}					
				}
				return self;
			};
			
			// Loads visible state attached SVG drawings from catalog
			if(mf.options.startupMode != 'documentation') {				
				if($.type(objs)!=='array') objs = [objs];
				var asyncLoader = {readyIndex:{},loadingSequence:objs,nbLoaded:0};
				var validateAndTagSVG = function(svg,id) {
					// parses SVG xml code
					var svgCode = $.parseXML(svg);					
					// sets ID on root element
					svgCode = $(svgCode).children()[0];
					svgCode.id = id;
					// returns modified SVG as a string
					var returnValue = wigiiApi.xml2string(svgCode);
					return returnValue;
				};
				var actOnCode = function(code){
					// if no svg code and some serialized NCD code, then calls it to generate some SVG code
					if(!code.svg && code.ncd) {
						code.svg = mf.impl.fxString2obj(code.ncd);
						// validates and tags generated svg code
						code.svg = validateAndTagSVG(code.svg,code.id);
					}
					asyncLoader.readyIndex[code.id] = code;
					asyncLoader.nbLoaded++;						
					// if all loaded, then finalizes context with ready data
					if(asyncLoader.nbLoaded == asyncLoader.loadingSequence.length) {
						self.context.objs = [];
						var readyStamp = '';
						for(var i=0;i<asyncLoader.loadingSequence.length;i++) {
							var obj = asyncLoader.readyIndex[asyncLoader.loadingSequence[i]];
							self.context.objs.push(obj);								
							readyStamp += (i>0?', ':'')+obj.type+" "+obj.id;
						}
						//mf.ncd.message.log("Visible state '"+self.name()+"' received "+readyStamp);
						// Creates a wncd Runtime to execute animations
						self.impl.animationRuntime = wncd.createRuntime(mf.impl.gameBoard.svgEmitter());
						// marks visible state as ready
						self.ready();
					}
				};
				for(var i=0;i<objs.length;i++) {
					// if objs[i] is function, then executes svg generator and builds a dynamic code object
					if($.isFunction(objs[i])) {
						var dynCode = {};
						dynCode.svg = objs[i](self.options);
						dynCode.id = 'DYNCOD'+self.ctxKey+i;
						// validates and tags generated svg code
						dynCode.svg = validateAndTagSVG(dynCode.svg,dynCode.id);
						// replaces code generator by dynamic code id
						objs[i] = dynCode.id;
						// acts on dynamic code
						actOnCode(dynCode);
					}
					// else loads code from catalog
					else {
						mf.ncd.actOnCode(objs[i],actOnCode);
					}
				}
				// attach a RectangleService to ease svg object manipulation
				mf.ncd.background.gameBoard().getRectangleService(self).attach();
			}
		};
		
		/**
		 * Visible objects displayed on scene background and game board
		 */
		self.VisibleObject = function(states,options) {
			var self = this;
			self.className = 'VisibleObject';
			self.instantiationTime = Date.now();
			self.ctxKey = mf.className+'_'+self.className+'_'+self.instantiationTime;
			self.options = options || {};
			self.context = {
				sceneBackground:undefined,
				name:undefined,
				selectionSense:undefined,
				ready:false
			};			
			self.impl = {
			};

			// Properties and accessors
			
			/**
			 * JQuery pointer on the underyling visible SVG element
			 */
			self.$ = function() {return self.context.visibleState.$();};
			
			/**
			 *@return Array an array with the VisibleObject states name
			 */
			self.states = function() {
				var returnValue=[];
				for(var i=0;i<self.context.visibleStates.length;i++) {
					var visibleState = self.context.visibleStates[i];
					returnValue.push(visibleState.name());
				}
				return returnValue;
			};
			
			/**
			 * Returns the name of the current visible state or undefined if not visible.
			 */
			self.state = function() {
				if(self.context.visibleState) return self.context.visibleState.context.name;
			};
			
			// Methods
			
			/**
			 * Resets the VisibleObject configuration using a set of options
			 *@return MoveForward.VisibleObject for chaining
			 */
			self.reset = function(options) {
				if(options) {
					self.context.name = options.name || self.context.name;
					self.context.sceneBackground = options.sceneBackground || self.context.sceneBackground;
					self.options.showStateDefaultPosition = options.showStateDefaultPosition || self.options.showStateDefaultPosition;					
					self.options.showStateDefaultSize = options.showStateDefaultSize || self.options.showStateDefaultSize;					
				}
				return self;
			};
			
			/**
			 * Displays the visible object into the chosen state
			 *@param String state name of the visible state in which to show the visible object
			 *@param Object options some options to configure the way the state is displayed
			 *@return MoveForward.VisibleObject for chaining
			 */
			self.show = function(state,options) {
				if(self.context.visibleStatesIndex) {
					var visibleState = self.context.visibleStatesIndex[state];
					if(visibleState) {						
						options = options || {};
						var pos = undefined;
						if(self.context.visibleState) {
							// takes current frequency if defined
							options.freq = options.freq || self.context.visibleState.context.freq;
							// stores current position and size
							pos = {x:self.x(),y:self.y(),height:self.height()};
							// hides previous visible state
							self.context.visibleState.hide();
						}
						// shows new state
						visibleState.show(options);						
						self.context.visibleState = visibleState;
						// shows it in default position 
						if(self.options.showStateDefaultPosition) self.position(self.options.showStateDefaultPosition);
						// or keeps actual position
						else if(pos) self.position(pos.x,pos.y);
						// shows it in default size
						if(self.options.showStateDefaultSize) self.size(self.options.showStateDefaultSize);
						// or keeps actual size
						else if(pos) self.height(pos.height);
					}
				}
				return self;
			};			
			
			/**
			 * Registers a onClick event handler
			 */
			self.click = function(onClick) {
				if($.isFunction(onClick)) {
					if(!self.context.onClickSubscribers) self.context.onClickSubscribers = [];
					self.context.onClickSubscribers.push(onClick);
				}
				else if(onClick===undefined) {
					if(self.context.onClickSubscribers) {
						for(var i=0;i<self.context.onClickSubscribers.length;i++) {
							var eh = self.context.onClickSubscribers[i];
							if($.isFunction(eh)) eh(self);
						}
					}
				}
				return self;
			};
			
			/**
			 * Registers a onReady event handler
			 */
			self.ready = function(onReady) {
				if($.isFunction(onReady)) {
					if(self.context.ready) onReady(self);
					else {
						if(!self.context.onReadySubscribers) self.context.onReadySubscribers = [];
						self.context.onReadySubscribers.push(onReady);
					}
				}
				else if(onReady===undefined) {
					self.context.ready = true;
					if(self.context.onReadySubscribers) {
						for(var i=0;i<self.context.onReadySubscribers.length;i++) {
							var eh = self.context.onReadySubscribers[i];
							if($.isFunction(eh)) eh(self);
						}
						// empties subscribers list
						self.context.onReadySubscribers=undefined;
					}					
				}
				return self;
			};
			
			// Implementation
			
			self.impl.onSelect = function(selectionSense) {
				self.click();
			};
			
			// Initializes VisibleObject
			if(mf.options.startupMode != 'documentation') {
				// Creates a SelectionSense
				self.context.selectionSense = wncd.createSelectionSense(self.impl.onSelect);
				// Loads object visible states from catalog
				mf.ncd.createAndAct(states,function(visibleStates){
					self.context.visibleStates = ($.type(visibleStates)==='array'?visibleStates:[visibleStates]);
					self.context.visibleStatesIndex = {};					
					self.context.visibleStatesToLoad = self.context.visibleStates.length;
					var onReadyVisibleState = function(visibleState) {
						self.context.visibleStatesToLoad -= 1;
						// marks visible object as ready once all visible states are successfully loaded
						if(self.context.visibleStatesToLoad<=0) self.ready();						
					};						
					// creates VisibleState index					
					for(var i=0;i<self.context.visibleStates.length;i++) {
						var visibleState = self.context.visibleStates[i];						
						visibleState.ctxKey += i;/* avoids clashes by appending numerical index */
						// injects selection sense
						visibleState.context.selectionSense = self.context.selectionSense;
						self.context.visibleStatesIndex[visibleState.name()] = visibleState;
						// registers ready callback
						visibleState.ready(onReadyVisibleState);
					}
					//mf.ncd.message.log("Visible object "+(self.context.objectID?"'"+self.context.objectID+"' ":"")+"received "+self.context.visibleStates.length+" VisibleStates: "+self.states().join(','));					
				});	
				// attach a RectangleService to ease svg object manipulation
				mf.ncd.background.gameBoard().getRectangleService(self).attach();
			}
		};
		
		/**
		 * Scene background displayed on game board
		 */
		self.SceneBackground = function(objId,options) {
			var self = this;
			self.className = 'SceneBackground';
			self.instantiationTime = Date.now();
			self.ctxKey = mf.className+'_'+self.className+'_'+self.instantiationTime;
			self.options = options || {};
			self.context = {
				gameBoard:options.gameBoard,
				visibleObjects:[],
				visibleObjectsIndex:{}
			};			
			self.impl = {				
			};
			
			// Accessors
			
			self.gameBoard = function() { return self.context.gameBoard;};
			self.$ = function() {return $('#'+self.ctxKey);};
			
			// Methods
			
			/**
			 * Loads a scene background given its catalog ID
			 *@param String objId catalog ID of the SVG drawing to be used as a background
			 *@param Object options an optional bag of options to configure the scene background
			 *@return MoveForward.SceneBackground the new loaded scene background instance
			 */
			self.load = function(objId,options) {
				self.context.gameBoard.loadBackground(objId,options);
				return mf.ncd.background;
			};
			
			/**
			 * Adds a visible object to the scene background
			 *@param VisibleObject|String visibleObj a VisibleObject instance or its catalog ID
			 *@param Object options an optional bag of options to configure the visible object and its layout in the scene background
			 *@param Function actOnDone optional callback which is called when the VisibleObject has been successfully added to the scene background
			 *@return MoveForward.SceneBackground for chaining
			 */
			self.add = function(visibleObj,options,actOnDone) {
				if(!visibleObj) throw wncd.createServiceException("visibleObj cannot be null",wncd.errorCodes.INVALID_ARGUMENT);
				// sets default options
				options = options || {};
				options.sceneBackground = self;
				// if needed, loads VisibleObject given its catalog ID and then register it into the visibleObjects collection
				mf.ncd.createAndAct(visibleObj,function(visibleObj) {
					visibleObj.reset(options);
					visibleObj.ctxKey += self.context.visibleObjects.length;/* avoids clashes by appending numerical index */
					// generates a name if no logical name is given
					if(!visibleObj.context.name) visibleObj.context.name = visibleObj.context.objectID+"_"+self.context.visibleObjects.length;
					self.context.visibleObjects.push(visibleObj);					
					// hides old visible object stored on this name
					var oldVisibleObj = self.context.visibleObjectsIndex[visibleObj.context.name];
					if(oldVisibleObj) oldVisibleObj.$().hide();
					self.context.visibleObjectsIndex[visibleObj.context.name] = visibleObj;
					// queues ready callback
					if(actOnDone) visibleObj.ready(actOnDone);
				});
				return self;
			};
			
			/**
			 * Returns a reference to a visible object displayed on the scene background given its logical name
			 *@return MoveForward.VisibleObject
			 */
			self.visibleObject = function(name) {
				if(name===undefined) if(self.context.visibleObjects.length>0) return self.context.visibleObjects[0];
				else return self.context.visibleObjectsIndex[name];
			};
			
			/**
			 * Iterates on each selected visible objects and calls doAction on it
			 *@param Function doAction callback of the form doAction(visibleObject)
			 */
			self.forEachSelectedObjects = function(doAction) {
				for(var i=0;i<self.context.visibleObjects.length;i++) {
					var visibleObj = self.context.visibleObjects[i];
					if(visibleObj.context.selectionSense.selected()) doAction(visibleObj);
				}
			};
			
			/**
			 * Clears selection of visible objects
			 */
			self.clearSelection = function() {
				self.forEachSelectedObjects(function(visibleObj){visibleObj.context.selectionSense.selected(false);});
			};
			
			if(objId && mf.options.startupMode != 'documentation') {
				// Loads scene background from catalog and displays it
				mf.ncd.actOnCode(objId,function(code){
					if(code && code.type == 'svg') {
						try {
							self.context.gameBoard.svgEmitter().putSVG(code.svg,code.svgDefs)
							.$().find('#'+code.id).attr('id',self.ctxKey);
							// attach a RectangleService to ease svg object manipulation
							self.context.gameBoard.getRectangleService(self).attach();
							// moves background to top of svg to ensure it stays on the back
							var firstChild = self.context.gameBoard.svgEmitter().$().children("g").first();
							if(firstChild.attr('id') != self.ctxKey) self.$().insertBefore(firstChild);
							// centers and resizes background to take full screen
							self.position(0,0).size("100%");
						}
						catch(exc) {
							mf.impl.publishException(exc);
						}
					}
				});
			}
		};
		
		/**
		 * Scene facade
		 */
		self.Scene = function() {
			var self = this;
			/**
			 * Scene context
			 */
			self.context = {
			};			
			
			// Methods
			
			/**
			 * Interrupts current scene to call a sub-scene to execute a specific task
			 *@param String sceneId catalog ID of the scene to load and execute.
			 *@return Function a wncd FuncExp which executes the specified scene
			 */
			self.call = function(sceneId) {
				return ctlSeq(
					scripte(function(){
						mf.impl.playSceneScript(sceneId);
					}),
					wncd.interrupt()
				);
			};
			/**
			 * Returns to calling scene once sub-scene is finished
			 */
			self.resume = function() {
				if(wncd.program.context.interruption) {
					var ctx = wncd.program.context.interruption
					wncd.program.context.interruption = undefined;
					ctx.resume();
				}
			};
			/**
			 * Chains future scene once current one is over 
			 *@param String sceneId catalog ID of the scene to add to waiting queue.			 
			 */
			self.queue = function(sceneId) {
				if(sceneId) mf.context.sceneQueue.push(sceneId);
				return self;
			};
		};
		
		/**
		 * The user as an explorer, displayed on game board
		 */
		self.Explorer = function(visibleObjectId,options) {
			var self = this;
			self.className = 'Explorer';
			self.instantiationTime = Date.now();
			self.ctxKey = mf.className+'_'+self.className+'_'+self.instantiationTime;
			self.options = options || {};
			self.context = {
				visibleObject:undefined
			};
			self.impl = {				
			};
			
			// Accessors
			
			/**
			 * JQuery pointer on the underyling visible SVG element
			 */
			self.$ = function() {return self.context.visibleObject.$();};
			
			// Methods
			
			self.show = function(state,options) {
				if(self.context.visibleObject) self.context.visibleObject.show(state,options);
				return self;
			};
			
			// Loading
			
			if(!visibleObjectId) throw wncd.createServiceException("visibleObjectId cannot be null",wncd.errorCodes.INVALID_ARGUMENT);
			if(!mf.ncd.background) throw wncd.createServiceException("no SceneBackground available, please load one first",wncd.errorCodes.INVALID_STATE);
			// sets default options
			if(!self.options.name) self.options.name = 'explorer';
			if(mf.options.startupMode != 'documentation') {
				// loads explorer VisibleObject from catalog and adds it to current background
				mf.ncd.background.add(visibleObjectId,self.options,function(visibleObj){
					self.context.visibleObject = visibleObj;
					// attach a RectangleService to ease svg object manipulation
					mf.ncd.background.gameBoard().getRectangleService(self).attach();
				});
			}
		};
		
		/**
		 * Move Forward Natural Code Development interface
		 * Exposed through the wncd.mf symbol
		 */
		self.MoveForwardNCD = function() {
			var self = this;
			self.ctxKey = mf.ctxKey;
			
			// Move Forward Natural Code Language
			
			/**
			 * Creates an Object Visible State
			 *@param String|Array objs one catalog ID or an array of catalog IDs of the SVG drawings to associate with the visible state.
			 *@param Object options an optional bag of options to configure the visible state
			 *@return MoveForward.ObjVisibleState
			 */
			self.visibleState = function(name,objs,options) {
				var returnValue = new mf.ObjVisibleState(name,objs,options);
				return returnValue;
			};
			/**
			 * Creates a visible object instance exposing several visible states
			 *@param MoveForward.ObjVisibleState|Array states a single or an array of visible states to be associated with this visible object
			 *@param Object options an optional bag of options to configure the visible object
			 *@return MoveForward.VisibleObject 
			 */
			self.visibleObject = function(states,options) {
				var returnValue = new mf.VisibleObject(states,options);
				return returnValue;
			};
						
			/**
			 * Scene facade
			 */			
			self.scene = new mf.Scene();
			/**
			 * Scene background
			 */
			self.background = undefined; /* loaded afterwards */
			/**
			 * Explorer
			 */
			self.explorer = undefined; /* loaded afterwards */
			/**
			 * Message Service facade
			 */
			self.message = undefined; /* loaded afterwards */
			/**
			 * Game pad left
			 */
			self.padLeft = undefined; /* loaded afterwards */
			/**
			 * Game pad right
			 */
			self.padRight = undefined; /* loaded afterwards */
			
			// Natural Code Development interface
			
			/**
			 * Selects or adds a visible object on the scene background
			 *@param String name the logical name of the visible object present on the scene (for example, explorer or bootle or chair, etc)
			 *@param Object options a bag of options to be applied when loading the visible object from the catalog. Supports the following options:
			 * - load: CatalogId. Instructs the system to load the visible object from the catalog using the given ID.
			 * - show: VisibleState name. Instructs the system to show the visible object in this state by default. (for example: walking).
			 * - freq: Positive int. Gives the frequency at which the visible state should change its inner views.
			 * - actOnDone: optional callback which is called when the VisibleObject has been successfully loaded.
			 * - addVOTo: Object. Optional object which will receive the loaded VisibleObject under property 'vo'. The property has boolean value false, until the VisibleObject is loaded.
			 * - x: int. Gives the X (horizontal) coordinate (relative to SVG container center) where to position the visible object.
			 * - y: int. Gives the Y (vertical) coordinate (relative to SVG container center) where to position the visible object.
			 * - position: string, one of N,S,E,W,NW,NE,center,SE,SW. Gives the positioning code of the visible object relative to the center of the container. Center by default.
			 * - width: string. Relative (%) or absolute (px) width of the visible object
			 * - height: string. Relative (%) or absolute (px) height of the visible object
			 * - size: percent. Relative (%) size of the visible object according to container.
			 */
			self.vo = function(name,options) {
				// applies some options on a visible object
				var applyOptions = function(visibleObj,options) {
					if(!visibleObj) return;
					if(!options) return;
					if(options.show) visibleObj.show(options.show,options);
					var x = (options.x !== undefined? options.x : visibleObj.x());
					var y = (options.y !== undefined? options.y : visibleObj.y());
					var pos = options.position || 'center';
					if(options.x !== undefined || options.y !== undefined || options.position !== undefined) visibleObj.position(x,y,pos);
					if(options.width) visibleObj.width(options.width);
					if(options.height) visibleObj.height(options.height);
					if(options.size) visibleObj.size(options.size);
				};
				// loads from catalog if options.load is defined
				if(options && options.load) {
					options.name = name;
					if(!(options.width || options.height || options.size)) {
						options.size = '100%';
						options.showStateDefaultSize = '100%';
					}
					if(!(options.x || options.y || options.position)) {
						options.x = 0; options.y = 0; options.position = 'center';
						options.showStateDefaultPosition = {x:0,y:0,position:'center'};
					}
					var opt = options;
					var voProxy = {loaded:false,loading:true};
					if(options.addVOTo) options.addVOTo.vo = false;
					self.background.add(options.load,options,function(visibleObj){
						applyOptions(visibleObj,opt);
						// changes vo proxy to indicate that loading is finished 
						// and enables read function to fetch available VisibleObject
						voProxy.loaded=true;
						voProxy.loading=false;
						voProxy.obj=visibleObj;
						voProxy.read = function(){return voProxy.obj;};
						// stores visible object in given container
						if(opt.addVOTo) opt.addVOTo.vo = visibleObj;
						// calls actOnDone callback if defined
						if($.isFunction(opt.actOnDone)) opt.actOnDone(visibleObj);
					});
					return voProxy;
				}
				// fetches visible object by name and applies options
				else {
					var returnValue = self.background.visibleObject(name);
					if(returnValue && options) applyOptions(returnValue,options);
					return returnValue;
				}
			};
			
			/**
			 * Loads a visible object from the catalog and displays it into the given target div.
			 *@param String catalogId Visible Object ID to load from rise.wigii.org Move Forward catalog.
			 *@param JQuery targetDiv a JQuery selector on a div in which to display the loaded visible object			 
			 *@param object options a bag of options to be applied when loading the visible object from the catalog. Supports the following options:
			 * - name: VisibleObject logical name to identify it on the scene (for example, explorer or bootle or chair, etc). If not given, a name is generated based on the catalogId.
			 * - show: VisibleState name. Instructs the system to show the visible object in this state by default. (for example: walking).
			 * - freq: Positive int. Gives the frequency at which the visible state should change its inner views.
			 * - actOnDone: optional callback which is called when the VisibleObject has been successfully loaded.
			 * - addVOTo: Object. Optional object which will receive the loaded VisibleObject under property 'vo'. The property has boolean value false, until the VisibleObject is loaded.
			 * - x: int. Gives the X (horizontal) coordinate (relative to SVG container center) where to position the visible object.
			 * - y: int. Gives the Y (vertical) coordinate (relative to SVG container center) where to position the visible object.
			 * - position: string, one of N,S,E,W,NW,NE,center,SE,SW. Gives the positioning code of the visible object relative to the center of the container. Center by default.
			 * - width: string. Relative (%) or absolute (px) width of the visible object
			 * - height: string. Relative (%) or absolute (px) height of the visible object
			 * - size: percent. Relative (%) size of the visible object according to container. 
			 *@return object return a Visible Object proxy of the form {loading:true,loaded:false}
			 * Once loading is false and loaded is true, then the proxy exposes a read method which enables to fetch the loaded and ready MoveForward.VisibleObject
			 *@example var exploVo = wncd.mf.load("PERS201800031",$("explo#div"));
			 * then in the playing control loop ...
			 * if(exploVo.loading) break;
			 * if(exploVo.loaded) exploVo = exploVo.read();
			 * ... exploVo normal usage.
			 */
			self.load = function(catalogId,targetDiv,options) { return wncd.server.rise().loadVisibleObject(catalogId,targetDiv,options);};
			/**
			 *@see MoveForwardNCD.load
			 */
			self.loadVO = self.load;
			
			/**
			 * Loads some svg code the catalog and displays it into a given svg tag
			 *@param String codeId ID of the object stored into the catalog
			 *@param String jquery selector on a svg tag
			 */
			self.loadSVG = function(codeId,selector) {return wncd.server.rise().loadSVG(codeId,selector);};
			/**
			 * Displays svg code in a given svg tag
			 * @param String svg code to insert
			 * @param String jquery selector on a svg tag
			 */
			self.putSVG = function(svg,selector) {return wncd.server.rise().putSVG(svg,selector);};
			/**
			 * Fetches some code given its catalog ID and acts on it
			 *@param String codeId the catalog ID of the object to retrieve and act on.
			 * The catalog ID can be followed with a double underscore and SVG ID to select a specific SVG element instead of whole code.
			 * This is useful to load at once a bunch of SVG objects defined in one drawing, instead of loading them separately.
			 * Example: load PERS201800007__face or PERS201800007__droite_marche_1
			 *@param Function action callback on the fetched code. Callback is a function which takes one parameter of type object of the form
			 *{ id: object ID in catalog, optionally followed by svgId,
			 *  type: svg|ncd type of code fetched,
			 *  svg: String. SVG code fetched if defined,
			 *  ncd: String. WNCD code fetched if defined
			 *  objectName: String. Name of object in catalog
			 *  objectType: String. Type of object in catalog
			 *}
			 * If type is ncd both svg and ncd code can be defined. In that case, ncd holds an expression which uses in some way the svg code.
			 *@return MoveForward.MoveForwardNCD for chaining
			 */
			self.actOnCode = function(codeId,action) {
				// Registers codeId in pending objects until code is received
				mf.impl.pendingObjects[codeId] = Date.now();
				// Removes codeId from pending object on reception or failure
				var onReception = function(code) {
					delete mf.impl.pendingObjects[codeId];
					action(code);
				};
				var onFailure = function(exception,context) {
					delete mf.impl.pendingObjects[codeId];
					mf.impl.publishException(exception,context);
				};
				// calls rise.wigii.org
				wncd.server.rise().mf_actOnCode(codeId,onReception,onFailure);
				return self;
			};
			/**
			 * Returns map of pending objects not yet loaded.
			 * Key is object catalog ID, value is timestamp when http request has been sent.
			 */
			self.pendingObjects = function() {
				return mf.impl.pendingObjects;
			};
			/**
			 * Fetches an object given its catalog ID, creates a local instance and acts with it
			 *@param String|Array objId the catalog ID of the object to retrieve and act with, 
			 * or an array of object ID to retrieve multiple objects
			 *@param Function action callback on the instantiated object(s) of the form action(obj) or action(array)
			 *@return MoveForward.MoveForwardNCD for chaining
			 */
			self.createAndAct = function(objId,action) {
				// asynchronous object constructor
				var doCreateAndAct = function(objId,action) {
					self.actOnCode(objId,function(code){
						if(code && code.type == 'ncd') {
							try {
								// instantiates the object from its code only if a callback is given
								if($.isFunction(action)) {
									var obj = mf.impl.fxString2obj(code.ncd);
									// injects catalog attributes in context
									if(obj.context) {
										obj.context.objectID = objId;
										obj.context.objectName = code.objectName;
										obj.context.objectType = code.objectType;
										// calls action on object
										action(obj);	
									}
									else {
										// calls action on object with context
										action(obj,{
											objectID: objId,
											objectName: code.objectName,
											objectType: code.objectType
										});
									}
								}
							}
							catch(exc) {
								mf.impl.publishException(exc);
							}
						}
					});
				};
				// creates from object ID
				if($.type(objId)==='string') doCreateAndAct(objId,action);
				// creates an array of objects given their IDs
				else if($.type(objId)==='array' && $.type(objId[0])==='string') {
					var asyncLoader = {readyIndex:{},loadingSequence:objId,nbLoaded:0};
					for(var i=0;i<objId.length;i++) {
						doCreateAndAct(objId[i],function(obj){
							asyncLoader.readyIndex[obj.context.objectID] = obj;
							asyncLoader.nbLoaded++;						
							// if all loaded, then calls the action with the array of ready data
							if(asyncLoader.nbLoaded == asyncLoader.loadingSequence.length) {
								var returnValue = [];
								for(var i=0;i<asyncLoader.loadingSequence.length;i++) {
									obj = asyncLoader.readyIndex[asyncLoader.loadingSequence[i]];
									returnValue.push(obj);
								}
								if($.isFunction(action)) action(returnValue);
							}
						});
					}
				}
				// if objId is not a string or an array, assumes it is already the object instance and then acts on it directly
				else if($.isFunction(action)) action(objId);
				return self;
			};			
			/**
			 * Includes a piece of code from catalogue and executes it
			 *@param String codeId the ID of the code to be included
			 *@param Function actAfterInclude optional function to be executed after code has been included
			 *@return MoveForward.MoveForwardNCD for chaining
			 */
			self.include = function(codeId,actAfterInclude) {
				self.actOnCode(codeId,function(code){
					if(code && code.type == 'ncd') {
						try {
							// runs code
							mf.impl.fxString2obj(code.ncd);
							// calls action after include if defined
							if($.isFunction(actAfterInclude)) actAfterInclude();
						}
						catch(exc) {
							mf.impl.publishException(exc);
						}
					}
				});
				return self;
			};
			/**
			 * Includes a piece of code from catalogue and executes it only once.
			 *@param String codeId the ID of the code to be included
			 *@param Function actAfterInclude optional function to be executed after code has been included
			 *@return MoveForward.MoveForwardNCD for chaining
			 */
			self.includeOnce = function(codeId,actAfterInclude) {
				// includes code only if not already in cache
				if(!wncd.server.rise().mfCodeCache[codeId])	self.include(codeId,actAfterInclude);
				else if($.isFunction(actAfterInclude)) actAfterInclude();
				return self;
			};
			/**
			 * Registers a keydown event handler on the Move Forward console.
			 *@param Function eh event handler for keydown event (cf. https://api.jquery.com/keydown/)
			 *@param Boolean keepActive if true, then keydown event handler stays active even if scene changes.
			 * Else keydown event handler is removed when scene changes. 
			 * By default, keyboards events are automatically removed when scene changes.
			 *@return MoveForward.MoveForwardNCD for chaining
			 */
			self.keydown = function(eh,keepActive) {
				// sticks event handler on body
				$('body').keydown(eh);
				return self;
			};
			/**
			 * Registers a keyup event handler on the Move Forward console.
			 *@param Function eh event handler for keyup event (cf. https://api.jquery.com/keyup/)
			 *@param Boolean keepActive if true, then keyup event handler stays active even if scene changes.
			 * Else keyup event handler is removed when scene changes. 
			 * By default, keyboards events are automatically removed when scene changes.
			 *@return MoveForward.MoveForwardNCD for chaining
			 */
			self.keyup = function(eh,keepActive) {
				// sticks event handler on body
				$('body').keyup(eh);
				return self;
			};
			/**
			 * Registers a keypress event handler on the Move Forward console.
			 *@param Function eh event handler for keypress event (cf. https://api.jquery.com/keypress/)
			 *@param Boolean keepActive if true, then keypress event handler stays active even if scene changes.
			 * Else keydpress event handler is removed when scene changes. 
			 * By default, keyboards events are automatically removed when scene changes.
			 *@return MoveForward.MoveForwardNCD for chaining
			 */
			self.keypress = function(eh,keepActive) {
				// sticks event handler on body
				$('body').keypress(eh);
				return self;
			};
		};		
				
		// Implementation
				
		self.impl.publishException = function(exception,context) {
			if($.isPlainObject(exception)) wncd.publishWigiiException(exception,context);
			else wigii().publishException(exception);
		};
		
		/**
		 * Plays a scene given its catalog ID
		 *@param String sceneId the catalog ID of the scene for which to load the script
		 * A scene script should be a FuncExp: either a scripte object or a ctlSeq object or a ctlGen object.
		 */
		self.impl.playSceneScript = function(sceneId) {
			mf.ncd.createAndAct(sceneId,function(sceneScript,context){
				// Injects scene attributes in facade
				mf.ncd.scene.objectID = context.objectID;
				mf.ncd.scene.objectName = context.objectName;
				mf.ncd.scene.objectType = context.objectType;
				self.context.sceneId = mf.ncd.scene.objectID;
				// Runs the scene script in the context of the dedicated wncd Runtime for the game
				self.impl.gameRuntime.program(sceneScript);
			});
		};
		/**
		 * Plays at Move Forward.
		 * Runs the scenes waiting in the queue until the queue is empty.
		 */
		self.impl.play = function() {
			// Reads next waiting scene in queue
			var sceneId = self.context.sceneQueue.shift();
			if(sceneId) {
				// Loads the scene
				mf.ncd.createAndAct(sceneId,function(sceneScript,context){
					// Injects scene attributes in facade
					mf.ncd.scene.objectID = context.objectID;
					mf.ncd.scene.objectName = context.objectName;
					mf.ncd.scene.objectType = context.objectType;
					self.context.sceneId = mf.ncd.scene.objectID;
					// Runs the scene script in the context of the dedicated wncd Runtime for the game
					// Then interrupts stack by pausing 100ms and plays again until queue is empty.
					self.impl.gameRuntime.program(ctlSeq(
						sceneScript,
						pause(0.1),
						scripte(function(){self.impl.play();})
					));
				});
			}
			//else mf.ncd.message.log("End.");
		};
		/**
		 * @return Object Converts an Fx string back to its object representation (in the scope of Move Forward)
		 */
		self.impl.fxString2obj = function(code) {
			if(code) {
				// Remaps wncd.mf.* to mf.ncd.* to stay contextual
				code = code.replace(/wncd\.mf\./g,'mf.ncd.');
				// initializes scope
				var wigiiNcd = window.wigiiNcd;
				// evaluates code
				var returnValue = eval(code);
				return returnValue;
			}
		};
		return self;
	};
		
	// Registers MoveForwardNcd constructor into the Wigii JS client
	if(!wigiiApi['createMoveForwardNcd']) wigiiApi.createMoveForwardNcd = mfNcd;
	
	// Extends Wigii JQueryService with mf plugin
	if(!wigiiApi.getJQueryService()['mf']) wigiiApi.getJQueryService().mf = function(selection,options) {
		var returnValue=undefined;
		// checks we have only one element			
		if(selection && selection.length==1) {
			// Checks if we already have a Move Forward environment attached to current selection
			var mfEnv = undefined;
			var mfEnvCtxKey = selection.attr('data-wncd-mfctxkey');
			if(mfEnvCtxKey) mfEnv = selection.data(mfEnvCtxKey);
			// creates Move Forward environment
			if(!mfEnv) {			
				mfEnv = wigiiApi.createMoveForwardNcd({},options);
				
				// Define default options
				mfEnv.options.startupMode = 'multipleInstance'; /* conscious of the fact that several mf environments can be loaded on the same page */
				if(mfEnv.options.svgBoard===undefined) mfEnv.options.svgBoard=true;
			
				// creates game board HtmlEmitter on selected html element				
				mfEnv.impl.boardEmitter = wncd.html(selection).reset();
				var board = mfEnv.impl.boardEmitter;
				// if max width or max height on container, then initializes board to max.
				var maxWidth = board.$().css('max-width');
				if(maxWidth) maxWidth = Number(maxWidth.replace('px','').trim());
				var maxHeight = board.$().css('max-height');
				if(maxHeight) maxHeight = Number(maxHeight.replace('px','').trim());
				if(maxWidth && maxHeight) {
					mfEnv.context.boardMaxWidth = maxWidth-5;
					mfEnv.context.boardMaxHeight = maxHeight-5;
					mfEnv.context.boardWidth = mfEnv.context.boardMaxWidth;
					mfEnv.context.boardHeight = mfEnv.context.boardMaxHeight;
					mfEnv.options.flippable = (mfEnv.options.flippable !== false);
				}
				// else takes container width
				else {
					mfEnv.context.boardWidth = board.$().width()-5;
					mfEnv.context.boardHeight = board.$().height()-5;
					mfEnv.options.flippable = false;
				}
				
				
				// creates SVG board
				if(mfEnv.options.svgBoard) {
					// injects default supported SVG related xml namespaces
					if(!mfEnv.options.svgXmlNamespaces) mfEnv.options.svgXmlNamespaces = {
						"xmlns:dc":"http://purl.org/dc/elements/1.1/",
						"xmlns:cc":"http://creativecommons.org/ns#",
						"xmlns:rdf":"http://www.w3.org/1999/02/22-rdf-syntax-ns#",
						"xmlns:sodipodi":"http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd",
						"xmlns:inkscape":"http://www.inkscape.org/namespaces/inkscape"
					};
					mfEnv.options.svgXmlNamespaces["xmlns:svg"] = "http://www.w3.org/2000/svg";
					mfEnv.options.svgXmlNamespaces["xmlns:xlink"]="http://www.w3.org/1999/xlink";
					mfEnv.options.svgXmlNamespaces["xmlns"] = "http://www.w3.org/2000/svg";				
					var svgTag = ["svg","width",mfEnv.context.boardWidth,"height",mfEnv.context.boardHeight];
					for(var xmlns in mfEnv.options.svgXmlNamespaces) {
						svgTag.push(xmlns)
						svgTag.push(mfEnv.options.svgXmlNamespaces[xmlns]);
					}
					board.htmlBuilder().tag.apply(undefined,svgTag).$tag("svg").emit();
					mfEnv.impl.gameBoard = new mfEnv.GameBoardSVG(mfEnv,board.$().find("svg"),mfEnv.options);
				}
				// or creates Canvas board
				else {
					board.htmlBuilder().tag("canvas","width",mfEnv.context.boardWidth,"height",mfEnv.context.boardHeight).$tag("canvas").emit();
					mfEnv.impl.gameBoard = new mfEnv.GameBoard(mfEnv,board.$().find("canvas").get(0),mfEnv.options);
				}
				
				// creates game Runtime
				mfEnv.impl.gameRuntime = wncd.createRuntime(board);
										
				// Launches Move Forward NCD engine
				mfEnv.impl.mfNcd = new mfEnv.MoveForwardNCD();
				// Exposes MF NCD language internally through contextual mf.ncd symbol
				mfEnv.ncd = mfEnv.impl.mfNcd;
				
				// loads empty background
				if(mfEnv.options.svgBoard) mfEnv.impl.gameBoard.loadBackground();
				
				// stores Move Forward environment in selection and sets ctxKey as DOM data attribute
				selection.data(mfEnv.ctxKey,mfEnv);
				selection.attr('data-wncd-mfctxkey',mfEnv.ctxKey);
			}			
			
			returnValue = mfEnv.ncd;
		}
		else if(selection && selection.length>1) throw wigiiApi.createServiceException('Wigii mf selector can only be activated on a JQuery collection containing one element and not '+selection.length, wigiiApi.errorCodes.INVALID_ARGUMENT);
		return (!returnValue?{$:selection}:returnValue);
	};
})(wigii,jQuery,"https://rise.wigii.org/")