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

// DB settings
define ("DB_HOST", "localhost");
define ("DB_USER", "wgi");
define ("DB_PWD" , "mywgi-pass");
define ("DB_NAME", "wigii_example");
//define ("DB_HOST", "localhost");
//define ("DB_USER", ".........");
//define ("DB_PWD" , ".........");
//define ("DB_NAME", ".........");

//define ("REDIRECT_ALL_EMAILS_TO", "info@xxx.xx");
// Medair 22.07.2016: if defined then disables Box integration in whole client (useful for testing and dev environments)
//define ("DISABLE_BOX_INTEGRATION", true);

//starts the Wigii Client implementation

define ("TEST_ON", false); //this will launch the test impl (no wigii GUI)
if(TEST_ON){
	$WigiiExecutor = testWigiiExecutor :: start();
} else {
	$WigiiExecutor = ExampleWigiiExecutor :: start();
}




