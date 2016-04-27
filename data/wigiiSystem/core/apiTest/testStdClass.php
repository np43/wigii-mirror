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
 * tests PHP StdClass
 * Created by CWE on 13 nov. 2013
 */
class Test_StdClass extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_StdClass','various test with the PHP stdClass');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_StdClass");		
		$o = array('my first property' => 'hello',
		'my second property' => 'world');
		$this->modifyArray($o, 'my first property');
		$this->assertEqual('array is passed by value', $o['my first property'], 'hello');
		
		$o = (object)$o;
		$d->write($o->{'my first property'}.' '.$o->{'my second property'});		
		
		$o->myMethod = function($a, $b){return $a.' '.$b;};
		//eput($o);
		$c = $o->myMethod;
		$d->write($c($o->{'my first property'}, $o->{'my second property'}));
		
		$this->modifyObject($o, 'my first property');
		$d->write($o->{'my first property'});
	}	
	
	private function modifyArray($ar, $key) {
		$ar[$key] = 'modified in modifyArray';
	}
	private function modifyObject($obj, $key) {
		$obj->{$key} = 'modified in modifyObject';
	}
}
TestRunner::test(new Test_StdClass());