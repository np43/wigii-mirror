<?php
/**
 *  This file is part of Wigii (R) software.
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
 * tests ArrayIterator
 * Created by CWE on 19 ao�t 09
 */
class Test_ArrayIterator extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_ArrayIterator','various test on array iteration');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_ArrayIterator");
		$a = array('Q','W','E','R','T','Z');
		$b = array('A'=>'Q','S'=>'W','D'=>'E','F'=>'R','G'=>'T','H'=>'Z');
		$this->iterateOver($a,$d);
		$this->iterateOver($b,$d);
		$a[] = 'U';$a[]='I';
		$b['J'] = 'U'; $b['K'] = 'I';
		$this->iterateOver($a,$d);
		$this->iterateOver($b,$d);

		/*
		$a->rewind();
		while($a->valid())
		{
			$a->next();
			$d->write($a->key()." : ".$a->current());
		}
		*/
	}
	private function iterateOver($arr, $debugger)
	{
		foreach($arr as $k=>$v)
		{
			$debugger->write($k." : ".$v);
		}
	}
}
TestRunner::test(new Test_ArrayIterator());

class Test_ArrayAffectation extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_ArrayAffectation','various test on array affectation');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_ArrayAffectation");
		$a = array(array(array('ax', 'd'), 'e'), 'b', 'c');
		$b = $a;
		$this->assertEqual("values are equal", $a[0], $b[0]);
		$b[0][0][0] = 'toto';
		$d->write($a[0][0][0]);
		$this->assertNotEqual("values are different after modification of affected array", $a[0], $b[0]);
		
		$val =& $a[0][2];
		$this->assertNotSet("reference to non existing key is unset", $val);
		$a[0][2] = array(7, 8);
		$this->assertIsSet("reference to existing key is set", $val);
		$d->write($val);
		unset($val);
		
		$val =& $a[0][3];
		$this->assertNotSet("reference to non existing key is unset", $val);
		$val = array(9,10);
		$this->assertIsSet("reference to existing key is set", $val);		
		$d->write($a[0][3]);
		
		
	}
}
TestRunner::test(new Test_ArrayAffectation());

class Test_WigiiGraph_storeNodeValueInArrayTree extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_WigiiGraph_storeNodeValueInArrayTree','tests build of array tree');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_WigiiGraph_storeNodeValueInArrayTree");
		$wgs = TechnicalServiceProvider::getWigiiGraphService();
		$a = array();
		$wgs->storeNodeValueInArrayTree($a, array('a', 'b', 'c', 'test1'));
		$wgs->storeNodeValueInArrayTree($a, array('a', 'b', 'd', 'test2'));
		$wgs->storeNodeValueInArrayTree($a, array('a', 'e', 'test3'));
		$wgs->storeNodeValueInArrayTree($a, array('a', 'f', 'g', 'a', 'test4'));
		$wgs->storeNodeValueInArrayTree($a, array('a', 'f', 'g', 'test5'), SUPDOP_ADD);
		$wgs->storeNodeValueInArrayTree($a, array('a', 'f', 'g', 'test6'), SUPDOP_ADD);
		$d->write($a);	
		$wgs->storeNodeValueInArrayTree($a, array('a', 'e', 'test3'), SUPDOP_CAT);
		$wgs->storeNodeValueInArrayTree($a, array('a', 'e', 'test4'), SUPDOP_ADD);
		$wgs->storeNodeValueInArrayTree($a, array('a', 'e', 'test3'), SUPDOP_CAT);
		$d->write($a);
		$wgs->storeNodeValueInArrayTree($a, array('a', 'e', 'test2'), SUPDOP_DEL);
		$wgs->storeNodeValueInArrayTree($a, array('a', 'e', 'test3'), SUPDOP_DEL);
		$wgs->storeNodeValueInArrayTree($a, array('a', 'e', 'test4'), SUPDOP_DEL);
		$wgs->storeNodeValueInArrayTree($a, array('a', 'e', 'test3'), SUPDOP_CAT);
		$wgs->storeNodeValueInArrayTree($a, array('a', 'e', 'test3'), SUPDOP_ADD);
		$d->write($a);
		
		$a = array();
		$wgs->storeNodeValueInArrayTree($a, array('test1.html'), SUPDOP_ADD);
		$wgs->storeNodeValueInArrayTree($a, array('images', 'img1.png'), SUPDOP_ADD);
		$wgs->storeNodeValueInArrayTree($a, array('images', 'img2.png'), SUPDOP_ADD);
		$wgs->storeNodeValueInArrayTree($a, array('images', 'img3.png'), SUPDOP_ADD);
		$wgs->storeNodeValueInArrayTree($a, array('images', 'img4.png'), SUPDOP_ADD);
		$d->write($a);
		
		$a = array();
		$arr = explode('/', 'images/img1.png');
		$arr[] = 'c:/temp/images/img1.png';		
		$wgs->storeNodeValueInArrayTree($a, $arr, SUPDOP_ADD);
		$d->write($a);
		
	}
}
TestRunner::test(new Test_WigiiGraph_storeNodeValueInArrayTree());