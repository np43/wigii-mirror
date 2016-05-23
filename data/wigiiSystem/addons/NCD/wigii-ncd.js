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
 
 /**
  * Wigii Natural Code Development (NCD) library
  * Created by Camille Weber (camille@wigii.org), 23.05.2016
  * @param Window window current browser window
  * @param JQuery $ depends on JQuery 1.8.x
  */
 (function (window, $){
	// Wigii NCD
	var WigiiNcd = function() {
		var wigiiNcd = this;
		wigiiNcd.instantiationTime = (new Date()).getTime();
		wigiiNcd.ctxKey = 'WigiiNcd_'+wigiiNcd.instantiationTime;
		/**
		 * Object which holds inner private mutable state variables.
		 */
		wigiiNcd.context = {};
		/**
		 * @return String returns the Wigii NCD version number
		 */
		wigiiNcd.version = function() {return "1.0";}
	},	
	// Default WigiiNCD instance
	wigiiNcdInstance = new WigiiNcd(),
	// WigiiNCD Functional facade 
	wigiiNcdFacade = function(selector,options) {
		var wigiiNcd = wigiiNcdInstance;
		return wigiiNcd;
	};
	// Bootstrap
	window.wigiiNcd = wigiiNcdFacade;
 })(window, jQuery);