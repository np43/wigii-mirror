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
 * Matrix implementation on a Record
 * Created on 19 avril 2016 by CWE
 */
class MatrixRecordImpl extends Model implements Matrix {
	private $_debugLogger;
	private $record;
	private $rowHeaders=array();
	private $colHeaders=array();
	private $rowDim=0;
	private $colDim=0;
	private $rowHeaderField;
	private $columnsField;
	private $columnsHeaderField;
	private $startIndex;
	private $stopIndex;
	
	// Object lifecycle
	
	/**
	 * Creates a new Matrix Record implementation
	 * @param Record $record underlying Record on which the Matrix is attached
	 * @param WigiiBPLParameter $layout sets the Matrix layout based on Record configuration
	 * @return MatrixRecordImpl
	 */
	public static function createInstance($record=null,$layout=null) {
		$returnValue = new self();
		if(isset($record)) {
			$returnValue->setRecord($record);
			if(isset($layout)) $returnValue->setLayout($layout);
		}
		return $returnValue;
	}
	
	/**
	 * Resets the current row and column index.
	 * This method should be called if the underlying Record has row headers or column headers which changed in time.
	 */
	public function reset() {
		$this->rowHeaders = array();
		$this->colHeaders = array();
		$this->rowDim=0;
		$this->colDim=0;
		$this->buildIndex();
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("MatrixRecordImpl");
		}
		return $this->_debugLogger;
	}
	
	// Configuration
	
	/**
	 * Sets the Record on which the Matrix is attached
	 * @param Record $record
	 */
	public function setRecord($record) {
		$this->record = $record;
	}
	
	/**
	 * Returns a reference to linked Record
	 * @return Record
	 */
	public function getRecord() {
		return $this->record;
	}
	
	/**
	 * Defines Matrix layout based on Record configuration
	 * @param WigiiBPLParameter $options WigiiBPLParameter which should contain :
	 * - rowHeaderField: String. Prefix defining the name of the Fields composing the row headers.
	 * - columnsField: Array. An array of prefixes defining the names of the Fields composing the columns of the matrix.
	 * - columnsHeaderField: Array. An array of prefixes defining the names of the Fields composing the columns of the matrix.
	 * - startIndex: Int. The row index from which to build the matrix. If not specified, then starts with 1.
	 * - stopIndex: Int. The row index until which to build the matrix. If not specified, then takes all the found rows.
	 * @example Consider the matrix:
	 * 
	 * M   |  Col 1   |  Col 2   |  Col 3
	 * R 1 |  Cell11  |  Cell12  |  Cell13
	 * R 2 |  Cell21  |  Cell22  |  Cell23
	 * Tot |  Tot 1   |  Tot 2   |  Tot 3
	 * 
	 * Implemented on a Record with following Fields:
	 * <groupMatrix groupStart="1" noFieldset="1" useMultipleColumn="4" expand="1" ><label>Matrix</label></groupMatrix>
	 * 
	 * <freetext>M</freetext>
	 * <col1 type=Strings" noLabel=1"/>
	 * <col2 type=Strings" noLabel=1"/>
	 * <freetext>Col 3</freetext>
	 * 
	 * <freetext class="clear"></freetext>
	 * 
	 * <h_1 type="Strings" noLabel="1"/>
	 * <c1_1 type="Strings" noLabel="1"/>
	 * <c2_1 type="Strings" noLabel="1"/>
	 * <c3_1 type="Strings" noLabel="1"/>
	 * 
	 * <freetext class="clear"></freetext>
	 * 
	 * <h_2 type="Strings" noLabel="1"/>
	 * <c1_2 type="Strings" noLabel="1"/>
	 * <c2_2 type="Strings" noLabel="1"/>
	 * <c3_2 type="Strings" noLabel="1"/>
	 * 
	 * <freetext class="clear"></freetext>
	 * 
	 * <freetext>Tot</freetext>
	 * <tot1 type="Strings" noLabel="1" readonly="1" funcExp=''/>
	 * <tot2 type="Strings" noLabel="1" readonly="1" funcExp=''/>
	 * <tot3 type="Strings" noLabel="1" readonly="1" funcExp=''/>
	 * 
	 * <groupMatrix groupEnd="1" noFieldset="1" expand="1" />
	 * 
	 * Then, calling $m->setLayout(wigiiBPLParam(
	 * 	 "rowHeaderField", "h_",
	 *   "columnsField", array("c1_","c2_","c3_"),
	 *   "columnsHeaderField", array("col1","col2","col3")
	 * ))
	 * 
	 * Will create a matrix $m which :
	 * $m->getRowIdsIterator() will return array("R 1", "R 2")
	 * $m->getRowHeader("R 1") will return "h_1"
	 * $m->getColIdsIterator() will return array("Col 1", "Col 2", "col3"), notice the col3, because it is not a field
	 * $m->getColHeader("Col 1") will return "col1"
	 * $m->getColHeader("Col 3") will throw a ListException::OUT_OF_BOUND
	 * $m->getColHeader("col3") will return null
	 * $m->getRowIterator("R 1") will return array("c1_1", "c2_1", "c3_1")
	 * $m->getColIterator("Col 2") will return array("c2_1", "c2_2"), Tot 2 is not part of the matrix.
	 * $m->getColIterator("Col 3") will throw a ListException::OUT_OF_BOUND
	 * $m->getColIterator("col3") will return array("c3_1", "c3_2"), Tot 2 is not part of the matrix.
	 */
	public function setLayout($options) {
		if(!isset($this->record)) throw new ListException('Matrix layout can be set only if a record has been attached. First call setRecord method.', ListException::INVALID_STATE);
		// checks options
		if(!($options instanceof WigiiBPLParameter)) throw new ListException('setLayout takes one argument which is a non null instance of WigiiBPLParameter with the options rowHeaderField, columnsField and columnsHeaderField', ListException::INVALID_ARGUMENT);
		$this->rowHeaderField = $options->getValue('rowHeaderField');
		if(empty($this->rowHeaderField)) throw new ListException('rowHeaderField cannot be null', ListException::INVALID_ARGUMENT);
		$this->columnsHeaderField = $options->getValue('columnsHeaderField');
		if(empty($this->columnsHeaderField) || !is_array($this->columnsHeaderField)) throw new ListException('columnsHeaderField should be a non empty array of field names or constants', ListException::INVALID_ARGUMENT);
		$this->columnsField = $options->getValue('columnsField');
		if(empty($this->columnsField) || !is_array($this->columnsField)) throw new ListException('columnsField should be a non empty array of field names prefixes', ListException::INVALID_ARGUMENT);
		// checks columns matching
		$nCells = count($this->columnsField);
		$nColHeaders = count($this->columnsHeaderField);
		if($nCells != $nColHeaders) throw new ListException("columnsField array ($nCells entries: [".implode(',',$this->columnsField)."]) does not match columnsHeaderField array ($nColHeaders entries: [".implode(',',$this->columnsHeaderField)."])", ListException::INVALID_ARGUMENT);
		$this->buildIndex();
	}
	
	// Matrix implementation
	
	/**
	 * Returns the value of a matrix cell stored in the underlying Record field.
	 * @param String $rowId row selector
	 * @param String $colId column selector
	 * @param String $subFieldName Field subfield name for which to get the value. If not defined, assumes 'value' subfield.
	 * @return Scalar|Array
	 */
	public function getValue($rowId, $colId, $subFieldName = null) {
		$fieldName = $this->getFieldName($rowId, $coldId);
		if(isset($fieldName)) return $this->getRecord()->getFieldValue($fieldName,$subFieldName);
		else throw new ListException("no Field is defined on matrix cell pointed by ('$rowId','$colId')");
	}

	/**
	 * Returns the underlying Field name of a matrix cell
	 * @param String $rowId row selector
	 * @param String $colId column selector
	 * @return String the field name or null if a freetext is defined at this location.
	 * @throws ListException::OUT_OF_BOUND if rowId and colId are not part of the matrix.
	 */
	public function getFieldName($rowId, $colId) {
		$rH = $this->rowHeaders[$rowId];
		$cH = $this->colHeaders[$colId];
		if($rH && $cH) {
			if($cH->cellField) return $cH->cellField.$rH->index;
			else return null;
		}
		else throw new ListException("invalid index row '$rowId' and column '$colId'", ListException::OUT_OF_BOUND);
	}
		
	/**
	 * Sets the value of a matrix cell stored in the underlying Record field.
	 * @param String $rowId row selector
	 * @param String $colId column selector
	 * @param Scalar|Array $val value to be set
	 * @param String $subFieldName Field subfield name for which to set the value. If not defined, assumes 'value' subfield.
	 */
	public function setValue($rowId, $colId, $val, $subFieldName = null) {
		$fieldName = $this->getFieldName($rowId, $coldId);
		if(isset($fieldName)) $this->getRecord()->setFieldValue($val, $fieldName,$subFieldName);
		else throw new ListException("no Field is defined on matrix cell pointed by ('$rowId','$colId')");
	}
	
	public function getRowIterator($rowId) {
		$returnValue = MatrixRecordImplIterator::createInstance();
		$returnValue->setMatrixRecordImpl($this);
		$returnValue->setIds($this->colHeaders);
		$returnValue->setListId($rowId);
		$returnValue->setIsColumnIterator(false);
		return $returnValue;
	}
	
	public function getColIterator($colId) {
		$returnValue = MatrixRecordImplIterator::createInstance();
		$returnValue->setMatrixRecordImpl($this);
		$returnValue->setIds($this->rowHeaders);
		$returnValue->setListId($colId);
		$returnValue->setIsColumnIterator(true);
		return $returnValue;
	}
	
	public function getRowIdsIterator() {
		return array_keys($this->rowHeaders);
	}
	
	public function getColIdsIterator() {
		return array_keys($this->colHeaders);
	}
	
	public function countRows() {
		return count($this->rowHeaders);
	}
	
	public function countCols() {
		return count($this->colHeaders);
	}
	
	/**
	 * Not supported. Underlying Record has no method to say if set of fields are empty.
	 * @throws ListException::UNSUPPORTED_OPERATION
	 */
	public function isEmpty() {
		throw new ListException('isEmpty is not supported by MatrixRecordImpl', ListException::UNSUPPORTED_OPERATION);
	}
	
	public function getRowHeader($rowId) {
		$h = $this->rowHeaders[$rowId];
		if(!isset($h)) throw new ListException("Row '$rowId' does not exist", ListException::OUT_OF_BOUND);
		return $h->headerField;
	}
	
	public function getColHeader($colId) {
		$h = $this->colHeaders[$colId];
		if(!isset($h)) throw new ListException("Column '$colId' does not exist", ListException::OUT_OF_BOUND);
		return $h->headerField;
	}
	
	public function rowExists($rowId) {
		return isset($this->rowHeaders[$rowId]);
	}
	
	public function colExists($colId) {
		return isset($this->colHeaders[$colId]);
	}
	
	/**
	 * @see Matrix::addRow()
	 * @param $header always ignored in case of MatrixRecordImpl 
	 */
	public function addRow($rowId, $header=null) {
		if(empty($rowId)) throw new ListException('rowId cannot be null', ListException::INVALID_ARGUMENT);
		if($this->rowExists($rowId)) throw new ListException("a row with ID '$rowId' already exists in the matrix", ListException::ALREADY_EXISTS);
		if(!$this->rowDim) throw new ListException('matrix layout has not been initialized, call method setLayout first', ListException::INVALID_STATE);
		// checks that there are still free rows available in the matrix
		if($this->countRows()>=$this->rowDim) throw new ListException('cannot add a new row in matrix, no free rows available', ListException::OUT_OF_BOUND);
		// retrieves last row configuration and creates new row header
		$lastHeader = end($this->rowHeaders);
		$i = $lastHeader->index+1;
		$fieldName = $this->rowHeaderField.$i;
		$header = (object)array('index'=>$i, 'headerField'=>$fieldName);
		// sets rowId in underlying Record and inserts it in row index
		$this->record->setFieldValue($rowId, $fieldName); 
		$this->rowHeaders[$rowId] = $header;
		//$this->debugLogger()->write('new row '.$rowId.', '.json_encode($header));
	}
	/**
	 * @see Matrix::addCol()
	 * @param $header always ignored in case of MatrixRecordImpl
	 */
	public function addCol($colId, $header=null) {
		if(empty($colId)) throw new ListException('colId cannot be null', ListException::INVALID_ARGUMENT);
		if($this->colExists($colId)) throw new ListException("a column with ID '$colId' already exists in the matrix", ListException::ALREADY_EXISTS);
		if(!$this->colDim) throw new ListException('matrix layout has not been initialized, call method setLayout first', ListException::INVALID_STATE);
		// checks that there are still free rows available in the matrix
		if($this->countCols()>=$this->colDim) throw new ListException('cannot add a new column in matrix, no free columns available', ListException::OUT_OF_BOUND);
		// retrieves last col configuration and creates new col header
		$lastHeader = end($this->colHeaders);
		$i = $lastHeader->index+1;
		$fieldName = $this->columnsHeaderField[$i-1];
		//$this->debugLogger()->write('new header field: '.$fieldName.', index:'.$i.', ['.implode(',',$this->columnsHeaderField).']');
		$header = (object)array('index'=>$i, 'headerField'=>$fieldName, 'cellField'=>$this->columnsField[$i-1]);
		// checks existence of header field
		$fieldList = $this->record->getFieldList();
		if(!$fieldList->doesFieldExist($header->headerField)) throw new ListException("Header field '".$header->headerField."' does not exist", ListException::CONFIGURATION_ERROR);
		// checks existence of first row cell field
		$firstRowHeader = reset($this->rowHeaders);		
		if(!$fieldList->doesFieldExist($header->cellField.$firstRowHeader->index)) throw new ListException("field '".$header->cellField.$firstRowHeader->index."' does not exist and cannot be a valid cell of the matrix", ListException::CONFIGURATION_ERROR);
		// sets colId in underlying Record and inserts it in col index
		$this->record->setFieldValue($colId, $fieldName); 
		$this->colHeaders[$colId] = $header;
	}	
	
	// implementation
	
	/**
	 * Finds rows and columns keys and fills colHeaders and rowHeaders array with objects :
	 * 
	 * row header is a stdClass of the form
	 * { index: int. 1..n row index,
	 *   headerField: String. Field name of the header field, or null if not a field.
	 * }
	 *
	 * col header is a stdClass of the form
	 * { index: int. 1..n column index,
	 *   headerField: String. Field name of the header field, or null if not a field.
	 *   cellField: String. Field name of a cell, or null if not a field.
	 * }
	 */
	protected function buildIndex() {
		$startIndex=$this->startIndex;
		$stopIndex=$this->stopIndex;
		
		if($startIndex===null) $startIndex=1;
		$fieldList = $this->record->getFieldList();
		if($stopIndex===null) {
			$stopIndex=(int)($fieldList->count()/count($this->columnsField));
			$detectLastRow=true;
		}
		else $detectLastRow=false;
		
		// builds column index
		$n = count($this->columnsField);
		for($i=0;$i<$n;$i++) {
			$header = array();
			// extracts header field and key
			$fieldName = $this->columnsHeaderField[$i];
			if($fieldList->doesFieldExist($fieldName)) $key = $this->record->getFieldValue($fieldName);
			else {
				$key = $fieldName;
				$fieldName = null;
			}
			// checks that key does not already exist
			if(!empty($key)) {
				if(isset($this->colHeaders[$key])) throw new ListException("a column with key '$key' already exists in the matrix", ListException::ALREADY_EXISTS);
				$header['index'] = $i+1;
				$header['headerField'] = $fieldName;
		
				// extracts cell field
				$fieldName = $this->columnsField[$i].$startIndex;
				if(!$fieldList->doesFieldExist($fieldName)) throw new ListException("field '$fieldName' does not exist and cannot be a valid cell of the matrix", ListException::CONFIGURATION_ERROR);
				$header['cellField'] = $this->columnsField[$i];
		
				// stores column header
				$this->colHeaders[$key] = (object)$header;
			}
		}
		$this->colDim = $n;
		
		// builds row index
		$this->rowDim=0;
		for($i = $startIndex; $i <= $stopIndex; $i++) {
			$fieldName = $this->rowHeaderField.$i;
			if($detectLastRow && !$fieldList->doesFieldExist($fieldName) && $i>$startIndex) break;
			$key = $this->record->getFieldValue($fieldName);
			if(!empty($key)) {
				if(isset($this->rowHeaders[$key])) throw new ListException("a row with key '$key' already exists in the matrix", ListException::ALREADY_EXISTS);
				$header = array('index'=>$i,'headerField'=>$fieldName);
				$this->rowHeaders[$key] = (object)$header;
			}
			$this->rowDim++;
		}
	}
}

/**
 * Matrix Record Impl Iterator on rows or columns
 * Created on 20 avril 2016 by CWE
 */
class MatrixRecordImplIterator implements Iterator {

	private $matrixRecordImpl;
	private $ids;
	private $listId;
	private $isColIterator;

	// Object lifecycle

	public static function createInstance() {
		$returnValue = new self();
		return $returnValue;
	}

	// Configuration

	/**
	 * @param MatrixRecordImpl $m sets the MatrixRecordImpl on which to run the Iterator
	 */
	public function setMatrixRecordImpl($m) {
		$this->matrixRecordImpl = $m;
	}

	/**
	 * @param Array $array injects a copy of the array of Ids on which to iterate
	 */
	public function setIds($array) {
		$this->ids = $array;
	}
	/**
	 * @return String Id of current step
	 */
	public function getId() {
		if(isset($this->ids)) {
			return key($this->ids);
		}
		else return null;
	}
	/**
	 * @param String $id sets the List ID (if iterates on a row, then row ID, if iterates on a column then column ID)
	 */
	public function setListId($id) {
		$this->listId = $id;
	}
	/**
	 * @return String returns the List ID on which iteration is done
	 * (if iterates on a row, then row ID, if iterates on a column then column ID)
	 */
	public function getListId() {
		return $this->listId;
	}
	/**
	 * @param Boolean $boolean if true indicates that iteration is on a column,
	 * else iterates on a row
	 */
	public function setIsColumnIterator($boolean) {
		$this->isColIterator = $boolean;
	}
	/**
	 * @return Boolean if true indicates that iteration is on a column,
	 * else iterates on a row
	 */
	public function isColumnIterator() {
		return $this->isColIterator;
	}

	// Iterator implementation

	public function current () {
		if(isset($this->matrixRecordImpl)) {
			if($this->isColumnIterator()) {
				$rowId = $this->getId();
				$colId = $this->getListId();
			}
			else {
				$rowId = $this->getListId();
				$colId = $this->getId();
			}
			return $this->matrixRecordImpl->getFieldName($rowId, $colId);
		}
		else return null;
	}

	public function next () {
		if(isset($this->ids)) next($this->ids);
	}

	public function key () {
		return $this->getId();
	}

	public function valid () {
		if(isset($this->matrixRecordImpl)) {
			$id = $this->getId();
			$listId = $this->getListId();
			if($id && $listId) return true;
			else return false;
		}
		else return false;
	}

	public function rewind () {
		if(isset($this->ids)) {
			reset($this->ids);
			// checks validity of listId
			$listId = $this->getListId();
			if($listId && $this->matrixRecordImpl) {
				if($this->isColumnIterator() && !$this->matrixRecordImpl->colExists($listId)) throw new ListException("Column '$listId' does not exist", ListException::OUT_OF_BOUND);
				elseif(!$this->isColumnIterator() && !$this->matrixRecordImpl->rowExists($listId)) throw new ListException("Row '$listId' does not exist", ListException::OUT_OF_BOUND);
			}
		}
	}
}