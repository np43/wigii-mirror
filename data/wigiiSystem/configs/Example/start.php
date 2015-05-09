<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *  
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

// DB settings
define ("DB_HOST", "localhost");
define ("DB_USER", "wgi");
define ("DB_PWD" , "mywgi-pass");
define ("DB_NAME", "wigii_example");
//define ("REDIRECT_ALL_EMAILS_TO", "info@xxx.xx");
//define ("DB_HOST", "localhost");
//define ("DB_USER", ".........");
//define ("DB_PWD" , ".........");
//define ("DB_NAME", ".........");
////define ("REDIRECT_ALL_EMAILS_TO", "info@xxx.xx");

//starts the Wigii Client implementation

define ("TEST_ON", false); //this will launch the test impl (no wigii GUI)
if(TEST_ON){
	$WigiiExecutor = testWigiiExecutor :: start();
} else {
	$WigiiExecutor = ExampleWigiiExecutor :: start();
}




