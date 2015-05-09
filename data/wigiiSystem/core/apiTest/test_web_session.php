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

/*
 * Created on 22 juil. 09
 * created by LWR
 */

//session test
$testVarname = "nb of reload with the same session";
echo $testVarname.": ";
if(!isset($_SESSION[$testVarname])){
	$_SESSION[$testVarname] = 1;
} else {
	$GLOBALS[$testVarname] = $_SESSION[$testVarname];
}
echo $$testVarname;
echo "<br>";

$$testVarname ++;
//we need to register agin, because it is a native object and not a ref.
$_SESSION[$testVarname] = $$testVarname;



