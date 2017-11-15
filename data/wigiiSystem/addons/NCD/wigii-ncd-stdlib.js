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
  * Wigii Natural Code Development (NCD) standard library
  * Created by Camille Weber (camille@wigii.org), 15.11.2017
  */

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
	else if(selection && selection.length>1) throw wigiiNcd.createServiceException('Wigii NCD menu selector can only be activated on a JQuery collection containing one element and not '+selection.length, wncd.errorCodes.INVALID_ARGUMENT);
	return (!returnValue?{$:selection}:returnValue);
};