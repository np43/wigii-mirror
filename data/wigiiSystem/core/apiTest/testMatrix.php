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

/**
 * Tests Matrix impl
 * Created by LWR on 20 jan 2010
 * this test can be used for any implementation of the matrix
 * change the createMatrixInstance to test other matrix implementation
 */
class Debug_Matrix extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_Matrix");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_Matrix','debug various test on Matrix implementation');
	}
	public function run()
	{
		$m = $this->createMatrixInstance();
		$this->assertNotNull('Matrix retrieved', $m);
		
		$this->assertEqual("initialized matrix should have 0 rows and 0 columns", $m->countRows()+$m->countCols, 0);
		
		//add a row
		$m->addRow("a", null);
		$this->assertEqual("after adding one row with no column, matrix should have 0 rows and 0 columns", $m->countRows()+$m->countCols(), 0);
		try {
			$m->addRow("a", null);
			$this->fail("adding a row with an existing id should throw exception");
		} catch (ListException $e){
			$this->assertEqual("adding a row with an existing id should throw ALREADY_EXIST exception", $e->getCode(), ListException::ALREADY_EXISTS);
		}
		//add a row
		$m->addRow("b", null);
		$this->assertEqual("after adding second row with no column, matrix should have 0 rows and 0 columns", $m->countRows()+$m->countCols(), 0);
		
		try {
			$m->getValue("a", null);
			$this->fail("accessing a value when matrix dimension is null should throw out_of_bound exception");
		} catch (ListException $e){
			$this->assertEqual("accessing a value when matrix dimension is null should throw out_of_bound exception", $e->getCode(), ListException::OUT_OF_BOUND);
		}
		try {
			$m->setValue("a", null, "error");
			$this->fail("setting a value when matrix dimension is null should throw out_of_bound exception");
		} catch (ListException $e){
			$this->assertEqual("setting a value when matrix dimension is null should throw out_of_bound exception", $e->getCode(), ListException::OUT_OF_BOUND);
		}
		try {
			$m->getValue("a", "1");
			$this->fail("accessing a value when matrix dimension is null should throw out_of_bound exception");
		} catch (ListException $e){
			$this->assertEqual("accessing a value when matrix dimension is null should throw out_of_bound exception", $e->getCode(), ListException::OUT_OF_BOUND);
		}
		try {
			$m->setValue("a", "1", "error");
			$this->fail("setting a value when matrix dimension is null should throw out_of_bound exception");
		} catch (ListException $e){
			$this->assertEqual("setting a value when matrix dimension is null should throw out_of_bound exception", $e->getCode(), ListException::OUT_OF_BOUND);
		}
		
		$this->assertEqual("rowExist should return true on existing column", $m->rowExists("a"), true);
		$this->assertEqual("rowExist should return false on none-existing column", $m->rowExists(null), false);
		$this->assertEqual("rowExist should return false on none-existing column", $m->rowExists("c"), false);
		$this->assertEqual("colExist should return false on none-existing column", $m->colExists(null), false);
		$this->assertEqual("colExist should return false on none-existing column", $m->colExists("2"), false);
		
		//add a col
		$m->addCol("1", null);
		$this->assertEqual("after adding a col when 2 rows are defined, matrix should have 2 rows and 1 columns", $m->countRows()+$m->countCols(), 3);
		
		$this->assertEqual("accessing a value in correct bound not set should be null", $m->getValue("a","1"), null);
		$m->setValue("b","1", "test");
		$this->assertEqual("accessing a value in correct bound which is set to 'test' be 'test'", $m->getValue("b","1"), "test");
		
		try {
			$m->getValue("a", "2");
			$this->fail("accessing a value out of bound when matrix is set should throw out_of_bound exception");
		} catch (ListException $e){
			$this->assertEqual("accessing a value out of bound when matrix is set should throw out_of_bound exception", $e->getCode(), ListException::OUT_OF_BOUND);
		}
		try {
			$m->setValue("a", "2", "error");
			$this->fail("setting a value out of bound when matrix is set should throw out_of_bound exception");
		} catch (ListException $e){
			$this->assertEqual("setting a value out of bound when matrix is set should throw out_of_bound exception", $e->getCode(), ListException::OUT_OF_BOUND);
		}
		
		$this->assertEqual("rowExist should return true on existing column", $m->rowExists("a"), true);
		$this->assertEqual("rowExist should return false on none-existing column", $m->rowExists(null), false);
		$this->assertEqual("rowExist should return false on none-existing column", $m->rowExists("c"), false);
		$this->assertEqual("colExist should return true on existing column", $m->colExists("1"), true);
		$this->assertEqual("colExist should return false on none-existing column", $m->colExists(null), false);
		$this->assertEqual("colExist should return false on none-existing column", $m->colExists("2"), false);
		
		$m->addCol("2", null);
		$m->addCol("3", null);
		$this->assertEqual("when added 3 col, countCols should be 3", $m->countCols(), 3);
		
		$m->addRow("c", null);
		$this->assertEqual("when added 3 row, countRows should be 3", $m->countRows(), 3);
		$this->assertEqual("after adding a new line the rowIterator keys of line b should be the same than for line c", array_keys($m->getRowIterator("b")), array_keys($m->getRowIterator("c")));
		$this->assertNotEqual("after adding a new line the rowIterator values of line b should be different than for line c because a value is already setted in line b", $m->getRowIterator("b"), $m->getRowIterator("c"));
		
		$this->d()->write("-------Column iteration TESTING -------------");
		foreach($m->getColIterator("1") as $key=>$val) $a1[$key]=$val;
		foreach($m->getColIterator("2") as $key=>$val) $a2[$key]=$val;
		foreach($m->getColIterator("3") as $key=>$val) $a3[$key]=$val;
		
		$this->assertEqual("second column should be the same than the third. Only the first cell has a value yet.", $a2, $a3);
		$this->assertNotEqual("first column should be different than the second. Only the first cell has a value yet.", $a1, $a3);
		
		try {
			$m->getColIterator("4");
			$this->fail("getColIterator on none existing column should throw out_of_bound exception");
		} catch (ListException $e){
			$this->assertEqual("getColIterator on none existing column should throw out_of_bound exception", $e->getCode(), ListException::OUT_OF_BOUND);
		}
		try {
			$m->getRowIterator("d");
			$this->fail("getRowIterator on none existing row should throw out_of_bound exception");
		} catch (ListException $e){
			$this->assertEqual("getRowIterator on none existing row should throw out_of_bound exception", $e->getCode(), ListException::OUT_OF_BOUND);
		}
		
		$this->d()->write("-------Header iteration TESTING -------------");
		$this->assertEqual("RowIdsIterator should be as array('a', 'b', 'c')", $m->getRowIdsIterator(), array("a", "b", "c"));
		$this->assertEqual("ColIdsIterator should be as array('1', '2', '3')", $m->getColIdsIterator(), array("1", "2", "3"));
		$this->assertNotEqual("ColIdsIterator should not be as array('1', '2', '3', '4')", $m->getColIdsIterator(), array("1", "2", "3", "4"));
		$this->assertEqual("ColHeader for column 1 should be null, because not seted in begining", $m->getColHeader("1"), null);
		$this->assertEqual("RowHeader for row a should be null, because not seted in begining", $m->getRowHeader("a"), null);
		
		$header = "Header4!!";
		$m->addCol("head4", $header);
		$this->assertEqual("ColHeader for column 4 should be 'Header4!'", $m->getColHeader("head4"), $header);
		try {
			$m->getColHeader("5");
			$this->fail("getColHeader on none existing col should throw out_of_bound exception");
		} catch (ListException $e){
			$this->assertEqual("getColHeader on none existing col should throw out_of_bound exception", $e->getCode(), ListException::OUT_OF_BOUND);
		}
		try {
			$m->getRowHeader("e");
			$this->fail("getRowHeader on none existing col should throw out_of_bound exception");
		} catch (ListException $e){
			$this->assertEqual("getRowHeader on none existing col should throw out_of_bound exception", $e->getCode(), ListException::OUT_OF_BOUND);
		}
		
		//view the matrix
		//eput($m->displayDebug());
		
	}
	protected function createMatrixInstance(){
		return new MatrixArrayImpl();
	}
}
TestRunner::test(new Debug_Matrix());

