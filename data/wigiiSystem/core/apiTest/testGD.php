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
 * tests GD
 * Created by CWE on 12 sept 2013
 */
class Test_GD extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_GD','various test with the GD Graphical library');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_GD");
		
		$width = 400;
		$height = 400;
			
	    $img = imagecreate($width, $height);
	
	    $bg = $white = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);
	    $black = imagecolorallocate($img, 0, 0, 0);
	
	    imagerectangle($img, 0, 0, $width-1, $height-1, $black);
	
	    $center_x = (int)$width/2;
	    $center_y = (int)$height/2;
	
	    $angle = 0;
	    $radius = 0;
	    while($radius <= $width ) {
	        imagearc($img, $center_x, $center_y, $radius,
	                 $radius, $angle-5, $angle, $black);
	        $angle += 5;
	        $radius++;
	    }		  
	    imagepng($img, TEMPORARYUNZIPFORVIEWINGFILE_path.'Test_GD.png');
		$d->write('<img src="'.TEMPORARYUNZIPFORVIEWINGFILE_path.'Test_GD.png'.'"/>');
		
		/* for splines : http://www.script-tutorials.com/smooth-curve-graphs-with-php-and-gd/ */
	}	
}
TestRunner::test(new Test_GD());