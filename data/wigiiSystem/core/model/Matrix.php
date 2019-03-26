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
 * PHP Interface on Matrices
 * Created on 19 janv. 10 by LWR
 */
interface Matrix {	
	/**
	 * Gets value object from a cell in the matrix
	 * @param String $rowId row selector
	 * @param String $colId column selector
	 * @return Any
	 */
	public function getValue($rowId, $colId);

	/**
	 * Sets the value of a cell in the matrix
	 * @param String $rowId row selector
	 * @param String $colId column selector
	 * @param mixed $val the value
	 */
	public function setValue($rowId, $colId, $val);
	
	/**
	 * Gets a row Iterator
	 * @param String $rowId row selector
	 * @return Array|Traversable
	 */	
	public function getRowIterator($rowId);
	
	/**
	 * Gets a column iterator
	 * @param String $colId column selector
	 * @return Array|Traversable
	 */
	public function getColIterator($colId);
	
	/**
	 * Gets a row headers iterator (list of ids) in one dimension
	 * @return Array|Traversable
	 */
	public function getRowIdsIterator();
	/**
	 * Gets a column headers iterator (list of ids) in one dimension
	 * @return Array|Traversable
	 */
	public function getColIdsIterator();
	
	/**
	 * Returns the number of rows in the matrix
	 * @return int
	 */
	public function countRows();
	/**
	 * Returns the number of columns in the matrix
	 * @return int
	 */
	public function countCols();
	
	/**
	 * Returns true if the matrix is empty
	 * @return boolean
	 */
	public function isEmpty();
	
	/**
	 * Gets row header object
	 * @param String $rowId row selector
	 * @return Any	 
	 */
	public function getRowHeader($rowId);
	
	/**
	 * Gets col header object
	 * @param String $colId column selector
	 * @return Any
	 */
	public function getColHeader($colId);
	
	/**
	 * Checks if a row exists with the given Id
	 * @param String $rowId row selector
	 * @return Boolean true if a row is defined with the given header, else false
	 */
	public function rowExists($rowId);
	
	/**
	 * Checks if a column exists with the given Id
	 * @param String $colId column selector
	 * @return Boolean true if a column is defined with the given header, else false
	 */
	public function colExists($colId);
	
	/**
	 * Adds a row definition in the matrix
	 * @param String $rowId row selector
	 * @param mixed $header row header object
	 */
	public function addRow($rowId, $header);

	/**
	 * Adds a column definition in the matrix
	 * @param String $colId column selector
	 * @param mixed $header column header object
	 */
	public function addCol($colId, $header);	
}