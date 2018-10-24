<?php
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
 * Standard Wigii version 
 * and specific version type including customer name and revision number
 */
define("VERSION_NUMBER", "4.701");
define("REVISION_NUMBER", "G312");
define("ASSET_REVISION_NUMBER", "G302"); // this token will be used to load wigii_...js and wigii_...css
define("VERSION_TYPE", ""); // use the version type to version the customer customization, for example 'MyCompany R1234'
define("VERSION_LABEL", "Wigii system, v.".VERSION_NUMBER." ".REVISION_NUMBER." ".VERSION_TYPE." A-".ASSET_REVISION_NUMBER);