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
 * Element Service Query planner
 * Created by CWE on 24 sept. 09
 */
class ElementQueryPlanner extends Model implements FieldList
{
	private $_debugLogger;
	private $lockedForUse = true;

	private $mysqlF;
	private $sqlQueryType;
	private $maxNumberOfFieldsPerQuery;
	private $maxRelativeWeightPerQuery;
	private $rowSizeCutting;
	private $numberOfQueries;
	private $fieldCounter;
	private $queryRelativeWeight;
	private $fieldGlobalCounter;

	private $fieldQueryIndex;
	private $fieldSelectorList;
	private $selectedLanguages;
	private $userDefinedFieldSelectorList;
	private $fieldList;
	private $dataTypeList;
	private $dataTypeSubFields;
	private $freeTextFieldList;
	private $structuralFields;
	private $structuralFieldsCounter;
	private $structuralFieldsExtractor;
	private $sortOrFilterOnElement;
	private $fieldSelectorLogExp;
	private $fieldSortingKeyList;
	private $isFieldListValid;
	private $queryStrategy;
	private $ignoreElementDetail;
	private $elementAttributeFieldSelectorList;
	private $element;
	private $principal;
	private $sys_user;
	private $sys_username;
	private $sys_date;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementQueryPlanner");
		}
		return $this->_debugLogger;
	}

	/**
	 * Query strategy based on one join per field
	 */
	const QSTRATEGY_JOIN = 1;
	/**
	 * Query strategy based on one query per datatype
	 */
	const QSTRATEGY_DATATYPE = 2;
	/**
	 * Query strategy based on one query per field
	 */
	const QSTRATEGY_FIELD = 3;


	// Object lifecycle


	public static function createInstance($sqlQueryType, $reservedNumberOfJoins=0, $fieldSelectorList=null, $fieldSelectorLogExp=null, $fieldSortingKeyList=null)
	{
		$returnValue = new ElementQueryPlanner();
		$returnValue->reset($sqlQueryType, $reservedNumberOfJoins, $fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
		return $returnValue;
	}
	/**
	 * reservedNumberOfJoins: number of joins in the final query reserved by the caller, that can not be used to join fields.
	 */
	public function reset($sqlQueryType, $reservedNumberOfJoins=0, $fieldSelectorList=null, $fieldSelectorLogExp=null, $fieldSortingKeyList=null)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->sqlQueryType = $sqlQueryType;
		$this->fieldList = array();
		$this->freeTextFieldList = array();
		$this->isFieldListValid = true;
		$this->fieldQueryIndex = array();
		$this->fieldCounter = 0;		
		$this->fieldGlobalCounter = 0;
		$this->numberOfQueries = 0;
		$this->queryStrategy = 0;
		$this->ignoreElementDetail = false;
		$this->rowSizeCutting = false;
		switch($this->sqlQueryType)
		{
			case MySqlFacade::Q_SELECTALL:
			case MySqlFacade::Q_SELECTONE:
				$this->maxNumberOfFieldsPerQuery = $this->getMaxJoinsInQuery() - $reservedNumberOfJoins;
				$this->maxRelativeWeightPerQuery = $this->getMaxRelativeWeightOfQuery();
				$this->queryRelativeWeight = 0;
				break;
//			case MySqlFacade::Q_SELECTONE:
//				$this->dataTypeList = array();
//				break;
			case MySqlFacade::Q_INSERTONE:
				$this->dataTypeList = array();
				// by default forces strategy to datatype
				$this->queryStrategy = ElementQueryPlanner::QSTRATEGY_DATATYPE;
				break;
			case MySqlFacade::Q_UPDATE:
				$this->elementAttributeFieldSelectorList = FieldSelectorListArrayImpl::createInstance(false);
				break;
			default: throw new ElementServiceException('invalid sql type, should be one of SELECTALL, SELECTONE, INSERTONE, UPDATE', ElementServiceException::INVALID_ARGUMENT);
		}

		// Field Selector list
		$this->fieldSelectorList = array();
		if(isset($fieldSelectorList))
		{			
			foreach($fieldSelectorList->getListIterator() as $fs)
			{
				//eput($fs->getFieldName()." ".$fs->getSubFieldName()."\n");
				// extracts element attribute selectors
				if($fs->isElementAttributeSelector())
				{
					// if update, then keeps element attributes to be updated
					if($this->sqlQueryType === MySqlFacade::Q_UPDATE)
					{
						$this->elementAttributeFieldSelectorList->addFieldSelectorInstance($fs);
					}
					//an element attribute selector has nothing to do in sql query
					continue;
				}

				$fName = $fs->getFieldName();
				$subFieldName = $fs->getSubFieldName();
				// a defined subfield has priority on a fieldselector without subfield
//				//changed by LWR on 26/06/2012, if fieldselector without subfield and with some subfield, then add the subfield as "value" in the list of subfields
//				if(isset($subFieldName))
//				{
//					//if already fill with an empty fs then add this empty fs as the general value fs
//					if(!is_array($this->fieldSelectorList[$fName])){
//						$tempFs = $this->fieldSelectorList[$fName];
//						$this->fieldSelectorList[$fName] = array();
//						if($tempFs){
//							$this->fieldSelectorList[$fName]["value"] = FieldSelector::createInstance($fName, "value");
//						}
//					}
//					$this->fieldSelectorList[$fName][$subFieldName] = $fs;
//				}
//				elseif(!is_array($this->fieldSelectorList[$fName]))
//				{
//					$this->fieldSelectorList[$fName] = $fs;
//				} else if(is_array($this->fieldSelectorList[$fName])){
//					$this->fieldSelectorList[$fName]["value"] = FieldSelector::createInstance($fName, "value");
//				}

				if(isset($subFieldName))
				{
					if(!is_array($this->fieldSelectorList[$fName])) $this->fieldSelectorList[$fName] = array();
					$this->fieldSelectorList[$fName][$subFieldName] = $fs;
				}
				elseif(!is_array($this->fieldSelectorList[$fName]))
				{
					$this->fieldSelectorList[$fName] = $fs;
				}

				$this->isFieldListValid = false;
				$this->fieldQueryIndex[$fName] = -1;
			}
			$this->userDefinedFieldSelectorList = true;
			$this->selectedLanguages = $fieldSelectorList->getSelectedLanguages();
		}
		else $this->userDefinedFieldSelectorList = false;

		// Extracts structural fields from Field Selector log exp and Field Sorting keys
		$this->structuralFields = array();
		$this->structuralFieldsCounter = 0;
		$this->fieldSelectorLogExp = $fieldSelectorLogExp;
		$this->fieldSortingKeyList = $fieldSortingKeyList;
		$sfExt = $this->getStructuralFieldsExtractor();
		$sfExt->extractStructuralFields($fieldSelectorLogExp, $fieldSortingKeyList);
		$this->sortOrFilterOnElement = $sfExt->isSortingOrFilteringOnElement();
	}
	public function freeMemory()
	{
		unset($this->fieldList);
		unset($this->fieldSelectorList);
		unset($this->selectedLanguages);
		unset($this->fieldQueryIndex);
		unset($this->dataTypeList);
		unset($this->dataTypeSubFields);
		unset($this->freeTextFieldList);
		unset($this->structuralFields);
		unset($this->fieldSelectorLogExp);
		unset($this->fieldSortingKeyList);
		unset($this->elementAttributeFieldSelectorList);
		unset($this->element);
		unset($this->principal);
		unset($this->sys_user);
		unset($this->sys_username);
		unset($this->sys_date);
		$this->lockedForUse = false;
	}

	public function isLockedForUse() {
		return $this->lockedForUse;
	}

	/**
	 * Resets the object after an operation has been canceled
	 * Current implementation :
	 * - queryType = INSERTONE and strategy = DATATYPE ==> strategy = FIELD
	 * - else : normal reset.
	 */
	public function retryAfterCancel($sqlQueryType, $reservedNumberOfJoins=0, $fieldSelectorList=null, $fieldSelectorLogExp=null, $fieldSortingKeyList=null) {
		// reacts differently according to last query and strategy
		$crtEl = $this->element;
		$crtP = $this->principal;
		switch($this->sqlQueryType)
		{
			case MySqlFacade::Q_INSERTONE:
				if($this->queryStrategy === ElementQueryPlanner::QSTRATEGY_DATATYPE) {
					$this->reset($sqlQueryType, $reservedNumberOfJoins, $fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
					// switches to strategy Field
					$this->queryStrategy = ElementQueryPlanner::QSTRATEGY_FIELD;
				}
				else {
					$s = $this->queryStrategy;
					$this->reset($sqlQueryType, $reservedNumberOfJoins, $fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
					// keeps same strategy
					$this->queryStrategy = $s;
				}
				break;
			default: $this->reset($sqlQueryType, $reservedNumberOfJoins, $fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
		}
		$this->setElement($crtEl);
		$this->setPrincipal($crtP);
	}

	// dependency injection

	public function setMySqlFacade($mysqlFacade)
	{
		$this->mysqlF = $mysqlFacade;
	}
	protected function getMySqlFacade()
	{
		// autowired
		if(!isset($this->mysqlF))
		{
			$this->mysqlF = TechnicalServiceProvider::getMySqlFacade();
		}
		return $this->mysqlF;
	}

	protected function getMaxJoinsInQuery()
	{
		return $this->getMySqlFacade()->getMaxJoinsInQuery();
	}

	protected function getMultiQuerySpeedRatio()
	{
		return $this->getMySqlFacade()->getMultiQuerySpeedRatio();
	}
	
	public function ignoreElementDetail()
	{
		$this->ignoreElementDetail = true;
	}

	protected function getStructuralFieldsExtractor()
	{
		// autowired
		if(!isset($this->structuralFieldsExtractor))
		{
			$this->structuralFieldsExtractor = StructuralFieldsExtractor::createInstance($this);
		}
		else
		{
			$this->structuralFieldsExtractor->reset($this);
		}
		return $this->structuralFieldsExtractor;
	}
	public function setStructuralFieldsExtractor($structuralFieldsExtractor)
	{
		$this->structuralFieldsExtractor = $structuralFieldsExtractor;
	}

	public function setPrincipal($principal) {
		$this->principal = $principal;
	}

	public function setElement($element) {
		$this->element = $element;
	}

	/**
	 * Activates the sql splitting according to row size limit. No active by default. 
	 * This option should be activated when planning queries to create tables (create table from select).
	 * @param boolean $bool if true, then sql is splitted in several queries depending of row size and number of joins,
	 * else sql is only splitted according to max number of joins.
	 */
	public function setRowSizeCutting($bool) {
		$this->rowSizeCutting = $bool;
	}
	
	// implementation

	private function validateFieldList()
	{
		// does element have inner fields
		$elementHasInnerFields = isset($this->element) && $this->element->hasInnerFields();

		// validates field list
		if(isset($this->fieldQueryIndex) && isset($this->fieldSelectorList))
		{
			foreach($this->fieldQueryIndex as $fName => $index)
			{
				if($index < 0 && (!$elementHasInnerFields || !$this->element->hasInnerField($fName)))
				{
					throw new ElementServiceException("Field $fName used in field selector list is not declared in the configuration", ElementServiceException::INVALID_ARGUMENT);
				}
			}
		}
		// validates structural fields
		if($this->structuralFieldsCounter > 0)
		{
			//$this->debugLogger()->write("nb structural fields: ".$this->structuralFieldsCounter.", is array? ".(is_array($this->structuralFields)?" true, array_count: ".count($this->structuralFields):false));
			foreach($this->structuralFields as $sfName => $selected)
			{
				if($selected === -1)
				{
					throw new ElementServiceException("Field $sfName used in where clause or in sort by clause is not declared in the configuration", ElementServiceException::INVALID_ARGUMENT);
				}
			}
		}
		$this->isFieldListValid = true;
	}

	public function getNumberOfQueries()
	{
		// first validates fields list.
		$this->validateFieldList();

		// ok, then calculates query strategy and returns number of queries
		switch($this->sqlQueryType)
		{
			case MySqlFacade::Q_SELECTALL:
			case MySqlFacade::Q_SELECTONE:
				$this->queryStrategy = ElementQueryPlanner::QSTRATEGY_JOIN;
				if($this->rowSizeCutting) $this->debugLogger()->write("query relative weight = ".$this->queryRelativeWeight);
				break;
//			case MySqlFacade::Q_SELECTONE:
//				$this->queryStrategy = ElementQueryPlanner::QSTRATEGY_DATATYPE;
//				break;
			case MySqlFacade::Q_INSERTONE:
				// strategy is already defined to datatype or field
				break;
			case MySqlFacade::Q_UPDATE:
				$this->queryStrategy = ElementQueryPlanner::QSTRATEGY_FIELD;
				break;
			default: throw new ElementServiceException('invalid sql type, should be one of SELECTALL, SELECTONE, INSERTONE, UPDATE', ElementServiceException::UNEXPECTED_ERROR);
		}
//		// if SELECTONE and number of datatypes >= multiQuerySpeedRatio * numberOfFields then
//		// prefer multiple joins to multiple datatype queries
//		if($this->sqlQueryType === MySqlFacade::Q_SELECTONE &&
//			($this->numberOfQueries >= $this->getMultiQuerySpeedRatio() * $this->count()))
//		{
//			for($i = 1; $i < $this->numberOfQueries; $i++)
//			{
//				foreach($this->fieldList[$i] as $fName => $field)
//				{
//					$this->fieldList[0][$fName] = $field;
//					$this->fieldQueryIndex[$fName] = 0;
//				}
//			}
//			$this->numberOfQueries = 0;
//			$this->queryStrategy = ElementQueryPlanner::QSTRATEGY_JOIN;
//		}
		if($this->queryStrategy === ElementQueryPlanner::QSTRATEGY_JOIN)
		{
			return $this->numberOfQueries+1;
		}
		else
		{
			return $this->numberOfQueries;
		}
	}

	public function getQueryStrategy()
	{
		if($this->queryStrategy === 0)
		{
			$this->getNumberOfQueries();
		}
		return $this->queryStrategy;
	}

	public function getSql($queryIndex, $elementSqlBuilder)
	{
		// builds sql
		$elementSqlBuilder->setFieldSelectorListByFieldName($this->fieldSelectorList);
		$elementSqlBuilder->setFreeTextFields($this->freeTextFieldList);
		$elementSqlBuilder->setStructuralFields($this->structuralFields);
		if(isset($this->fieldSelectorLogExp)) $elementSqlBuilder->setFieldSelectorLogExp($this->fieldSelectorLogExp);
		if(isset($this->fieldSortingKeyList)) $elementSqlBuilder->setFieldSortingKeyList($this->fieldSortingKeyList);
		if($this->ignoreElementDetail && !$this->sortOrFilterOnElement)
		{
			$elementSqlBuilder->setIncludeElementDetail(false);
		}
		else
		{
			$elementSqlBuilder->setIncludeElementDetail(($queryIndex === 0) || $this->sortOrFilterOnElement);
		}
		$elementSqlBuilder->setQueryStrategy($this->getQueryStrategy());
		$elementSqlBuilder->setSqlQueryType($this->sqlQueryType);
		if(isset($this->selectedLanguages)) $elementSqlBuilder->setSelectedLanguages($this->selectedLanguages);
		if(isset($this->fieldList[$queryIndex])){
			foreach($this->fieldList[$queryIndex] as $field)
			{
				$elementSqlBuilder->actOnField($field, $field->getDataType());
			}
		}
		// returns sql
		$returnValue = $elementSqlBuilder->getSql();
		$elementSqlBuilder->freeMemory();
		return $returnValue;
	}

	public function getElementAttributeFieldSelectorList()
	{
		return $this->elementAttributeFieldSelectorList;
	}

	/**
	 * Returns true if query selects some fields and not only element attributes
	 */
	public function areFieldSelected()
	{
		return !$this->userDefinedFieldSelectorList || 
			$this->userDefinedFieldSelectorList && (count($this->fieldSelectorList) > 0) ||
			($this->structuralFieldsCounter > 0);
	}

	// FieldList implementation

	public function addField($field)
	{
		$fName = $field->getFieldName();
		$dt = $field->getDataType();
		$isFieldStructural = isset($this->structuralFields[$fName]);
		// records field if we do not have any fieldSelectorList
		// or if we have a fieldSelectorList and field is selected
		// or if it is a structural field (used in where clause or in sorting clause)
		if(!$this->userDefinedFieldSelectorList ||
			($this->userDefinedFieldSelectorList && isset($this->fieldSelectorList[$fName])) ||
			$isFieldStructural)
		{
			$this->fieldGlobalCounter++;

			// if no user field selector list is defined then
			// - if current field name already exists, then renames it
			// - records current field as selected
			if(!$this->userDefinedFieldSelectorList)
			{
				// renames only if already defined and
				// sqlQueryType is a select or field is a free text
				// and field is not a structural field
				if(!$isFieldStructural && $this->isFieldAlreadyDefined($fName))
				{
					if(($this->sqlQueryType === MySqlFacade::Q_SELECTALL) ||
					 	($this->sqlQueryType === MySqlFacade::Q_SELECTONE) ||
					 	!isset($dt))
					{
						$fName = $fName.$this->fieldGlobalCounter;
						if($this->isFieldAlreadyDefined($fName))
						{
							throw new ElementServiceException("Field $fName is defined twice in config, cannot decide", ElementServiceException::FIELD_DEFINED_TWICE_IN_CONFIG);
						}
						$field->setFieldName($fName);
					}
					else throw new ElementServiceException("Field $fName is defined twice in config, cannot decide", ElementServiceException::FIELD_DEFINED_TWICE_IN_CONFIG);
				}
				$this->fieldSelectorList[$fName] = $fName;
			}
			// if a user field selector list is defined then
			// raises an error if current field name already planned for a query
			elseif(!$isFieldStructural && $this->isFieldAlreadyDefined($fName))
			{
				throw new ElementServiceException("Field $fName is defined twice in config, cannot decide", ElementServiceException::FIELD_DEFINED_TWICE_IN_CONFIG);
			}

			// if we have a free text field -> ignores sql query planning
			if(!isset($dt)) // && !$field->isCalculated()
			{
				if($isFieldStructural) throw new ElementServiceException("Free text field $fName cannot be a structural field.", ElementServiceException::INVALID_ARGUMENT);
				$this->freeTextFieldList[$fName] = $field;
			}
			// else plans sql query
			else
			{
				switch($this->sqlQueryType)
				{
					case MySqlFacade::Q_SELECTALL:
					case MySqlFacade::Q_SELECTONE:
						// computes relative field weight if field is selected
						if($this->rowSizeCutting && isset($this->fieldSelectorList[$fName])) {
							// field weight is calculated using only data type. We could use an index per field instead, but not implemented for now.
							//$fieldWeight = $this->getFieldRelativeWeight($field, $this->fieldSelectorList[$fName]);
							$fieldWeight = $this->getDataTypeRelativeWeight($dt, $this->fieldSelectorList[$fName]);
							// if query weight becomes higher than max allowed weight, creates new query
							if($this->queryRelativeWeight + $fieldWeight >= $this->maxRelativeWeightPerQuery) {
								if($fieldWeight >= $this->maxRelativeWeightPerQuery) throw new ElementServiceException("Field '$fName' relative weight is too high to hold into one query. Planning is impossible.", ElementServiceException::CONFIGURATION_ERROR);
								$this->debugLogger()->write("row relative weight too high ".$this->queryRelativeWeight." + ".$fieldWeight." = ".($this->queryRelativeWeight + $fieldWeight)." >= ".$this->maxRelativeWeightPerQuery."), creates a new query");
								$this->fieldCounter = 0;
								$this->queryRelativeWeight = $fieldWeight;
								$this->numberOfQueries++;
								// recopies existing structural fields in new query
								if($this->structuralFieldsCounter > 0)
								{
									foreach($this->structuralFields as $sfName => $selected)
									{
										// if already initialized
										if($selected !== -1)
										{
											$this->fieldList[$this->numberOfQueries][$sfName] = $this->fieldList[$this->numberOfQueries-1][$sfName];
										}
									}
								}
							}
							// else sums query weight
							else $this->queryRelativeWeight += $fieldWeight;
						}
						
						// adds field to query
						$this->fieldList[$this->numberOfQueries][$fName] = $field;
						$this->fieldQueryIndex[$fName] = $this->numberOfQueries;
						// if field is structural, then adds it to all existing queries
						if($isFieldStructural)
						{
							// if field is selected, then marks it
							$this->structuralFields[$fName] = isset($this->fieldSelectorList[$fName]);
							// adds structural field to existing queries
							for($i = 0; $i < $this->numberOfQueries; $i++)
							{
								$this->fieldList[$i][$fName] = $field;
							}
						}
						else $this->fieldCounter++;
						
						// if max number of joins is reached, then creates new query 
						if($this->fieldCounter >= $this->maxNumberOfFieldsPerQuery - $this->structuralFieldsCounter)
						{
							$this->fieldCounter = 0;
							if($this->rowSizeCutting) $this->debugLogger()->write("query relative weight = ".$this->queryRelativeWeight);
							$this->queryRelativeWeight = 0;
							$this->numberOfQueries++;
							// recopies existing structural fields in new query
							if($this->structuralFieldsCounter > 0)
							{
								foreach($this->structuralFields as $sfName => $selected)
								{
									// if already initialized
									if($selected !== -1)
									{
										$this->fieldList[$this->numberOfQueries][$sfName] = $this->fieldList[$this->numberOfQueries-1][$sfName];
									}
								}
							}
						}
						break;
//					case MySqlFacade::Q_SELECTONE:
//						$dtName = $dt->getDataTypeName();
//						$queryIndex = $this->dataTypeList[$dtName];
//						if(!isset($queryIndex))
//						{
//							$queryIndex = $this->numberOfQueries;
//							$this->dataTypeList[$dtName] = $queryIndex;
//							$this->numberOfQueries++;
//						}
//						$this->fieldList[$queryIndex][$fName] = $field;
//						$this->fieldQueryIndex[$fName] = $queryIndex;
//						break;
					case MySqlFacade::Q_INSERTONE:
						$this->addSysInfoOnField($field);
						if(ElementQueryPlanner::QSTRATEGY_DATATYPE === $this->queryStrategy) {
							if($this->userDefinedFieldSelectorList) $this->assertFieldSubFieldsConsistentWithDatatypeSubFields($field);

							$dtName = $dt->getDataTypeName();
							$queryIndex = $this->dataTypeList[$dtName];
							if(!isset($queryIndex))
							{
								$queryIndex = $this->numberOfQueries;
								$this->dataTypeList[$dtName] = $queryIndex;
								$this->numberOfQueries++;
							}
							$this->fieldList[$queryIndex][$fName] = $field;
							$this->fieldQueryIndex[$fName] = $queryIndex;
						}
						elseif (ElementQueryPlanner::QSTRATEGY_FIELD === $this->queryStrategy) {
							$this->fieldList[$this->numberOfQueries][$fName] = $field;
							$this->fieldQueryIndex[$fName] = $this->numberOfQueries;
							$this->numberOfQueries++;
						}
						else throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_STATE);
						break;
					case MySqlFacade::Q_UPDATE:
						$this->addSysInfoOnField($field);
						$this->fieldList[$this->numberOfQueries][$fName] = $field;
						$this->fieldQueryIndex[$fName] = $this->numberOfQueries;
						$this->numberOfQueries++;
						break;
					default: throw new ElementServiceException('invalid sql type, should be one of SELECTALL, SELECTONE, INSERTONE, UPDATE', ElementServiceException::INVALID_ARGUMENT);
				}
			}
		}
	}

	/**
	 * Returns the relative weight of a datatype, 
	 * taking into account the selected subfields, or all subfields if none are selected.
	 * @param DataType $dataType a data type instance
	 * @param Array $subfields an array of selected subfields (keys are subfield names), or null.
	 * @return int the relative weight
	 */
	protected function getDataTypeRelativeWeight($dataType, $subfields=null) {
		if(isset($dataType)) {
			$returnValue = 0;
			$x = $dataType->getXml();
			if(is_array($subfields)) {
				foreach($subfields as $subField => $v) {
					$dbFieldParams = $x->xpath($subField);
					if($dbFieldParams)
					{
						$dbFieldParams = $dbFieldParams[0];
						$returnValue += $this->getDataTypeSqlTypeRelativeWeight((string)$dbFieldParams['sqlType'], (string)$dbFieldParams['sqlLength']);
					}				
				}
			}
			else {
				// gets cached relative weight
				$returnValue = $dataType->getRelativeWeight();
				// if not calculated, then calculates it for all subfields
				if(!($returnValue > 0)) {
					foreach($x as $dbFieldName => $dbFieldParams)
					{
						$returnValue += $this->getDataTypeSqlTypeRelativeWeight((string)$dbFieldParams['sqlType'], (string)$dbFieldParams['sqlLength']);
					}
					// caches relative weight.
					$dataType->setRelativeWeight($returnValue);
				}
			}
			return $returnValue;
		}
		else return 1;
	}
	/**
	 * Returns the relative weight of the sql type of a DataType subfield.
	 * see: https://dev.mysql.com/doc/refman/5.6/en/storage-requirements.html
	 * @param string $sqlTypeName one of :
	 * "varchar","text","boolean","date","bigint","datetime","int","longblob","blob","double","decimal","time"
	 * @param string $sqlLength optional constant. One of : 'valueLength', 'numberLength', 'nameLength'
	 * @return int
	 */
	protected function getDataTypeSqlTypeRelativeWeight($sqlTypeName, $sqlLength='') {
		if(is_null($sqlTypeName)) $returnValue =  1;
		else {
			$sqlTypeName = strtolower($sqlTypeName);
			$sqlLength = strtolower($sqlLength);
			$nbBytesPerChar = 4; //default utf8mb4
			if(DB_CHARSET=="utf8") $nbBytesPerChar = 3;
			switch($sqlTypeName)
			{
				case "varchar": 
					switch($sqlLength) {
						case "valueLength": $returnValue =  (254*$nbBytesPerChar)+2; break; /* VARCHAR(254) UTF8 = 254*3 + prefix (2 bytes) UTF8MB4 = 254*4 + prefix (2 bytes) */
						case "nameLength": $returnValue =  (32*$nbBytesPerChar)+1; break; /* VARCHAR(32) UTF8 = 32*3 + prefix (1 byte) UTF8MB4 = 32*4 + prefix (1 byte) */
						case "numberLength": $returnValue =  (16*$nbBytesPerChar)+1; break; /* VARCHAR(16) UTF8 = 16*3 + prefix (1 byte) UTF8MB4 = 16*4 + prefix (1 byte)*/
						default: $returnValue =  (64*$nbBytesPerChar)+1; break; /* VARCHAR(64) UTF8 = 64*3 + prefix (1 byte) UTF8MB4 = 64*4 + prefix (1 byte) */							
					}
					break;
				case "text": $returnValue =  2; break; /* prefix size 2 bytes */
				case "boolean": $returnValue =  1; break;
				case "date": $returnValue =  3; break;
				case "bigint": $returnValue =  8; break;
				case "datetime": $returnValue =  8; break;
				case "int": $returnValue =  4; break;
				case "longblob": $returnValue =  2; break; /* prefix size 2 bytes */
				case "blob": $returnValue =  2; break; /* prefix size 2 bytes */
				case "double": $returnValue =  8; break;
				case "decimal": $returnValue =  15; break;/* DECIMAL(32,2) = 3*4+2+1 see: https://dev.mysql.com/doc/refman/5.1/en/precision-math-decimal-characteristics.html */
				case "time": $returnValue =  3; break;
				default: $returnValue =  1; break;
			}
		}
		return $returnValue;
	}
	
	/**
	 * Returns the maximum relative weight allowed for one query
	 * @return int
	 */
	protected function getMaxRelativeWeightOfQuery() {
		// in theory MySql allows a max row size equal to 65535,
		// in pratice, the calculated weight cannot be higher that 58000 to prevent MySql error row size too large.		
		//$returnValue = 65535;
		$returnValue = 58000;
		//$this->debugLogger()->write("getMaxRelativeWeightOfQuery=$returnValue");
		return $returnValue;		
	}
	
	//caching of SysInfo
	protected function getSys_date(){
		if(!isset($this->sys_date)){
			$this->sys_date = time();
		}
		return $this->sys_date;
	}
	protected function getSys_user(){
		if(!isset($this->sys_user)){
			$this->sys_user = $this->principal->getRealUserId();
		}
		return $this->sys_user;
	}
	protected function getSys_username(){
		if(!isset($this->sys_username)){
			$this->sys_username = $this->principal->getRealUsername();
		}
		return $this->sys_username;
	}
	protected function addSysInfoOnField($field){
		$fName = $field->getFieldName();
		if(is_a($this->element, "MultipleElement")){
			$op = SUPDOP_SET; //for sys_ set the value
			$dataTypeName = $field->getDataType()->getDataTypeName();
			$this->element->getWigiiBag()->applyOperator($op, $this->getSys_user(), $dataTypeName, $fName, "sys_user", null, $field);
			$this->element->getWigiiBag()->applyOperator($op, $this->getSys_username(), $dataTypeName, $fName, "sys_username", null, $field);
			$this->element->getWigiiBag()->applyOperator($op, $this->getSys_date(), $dataTypeName, $fName, "sys_date", null, $field);
			//sys_creation* are defined with the same date in case there where not existing.
			//if there exist there are not overwrited as ElementSqlBuilderForMultipleElement->actOnSubfieldForUpdate call insertValueIfNotExist for sys_creation* subfields
			$op = SUPDOP_SET_IF_NULL; //for sys_creation* set the value only if empty
			$this->element->getWigiiBag()->applyOperator($op, $this->getSys_user(), $dataTypeName, $fName, "sys_creationUser", null, $field);
			$this->element->getWigiiBag()->applyOperator($op, $this->getSys_username(), $dataTypeName, $fName, "sys_creationUsername", null, $field);
			$this->element->getWigiiBag()->applyOperator($op, $this->getSys_date(), $dataTypeName, $fName, "sys_creationDate", null, $field);
		} else {
			$this->element->setFieldValue($this->getSys_user(), $fName, "sys_user");
			$this->element->setFieldValue($this->getSys_username(), $fName, "sys_username");
			$this->element->setFieldValue($this->getSys_date(), $fName, "sys_date");
			//sys_creation* are defined with the same date in case there where not existing.
			//if there exist there are not overwrited as ElementSqlBuilder->actOnSubfieldForUpdate call insertValueIfNotExist for sys_creation* subfields
			if($this->element->getFieldValue($fName, "sys_creationUser")==null){
				$this->element->setFieldValue($this->getSys_user(), $fName, "sys_creationUser");
			}
			if($this->element->getFieldValue($fName, "sys_creationUsername")==null){
				$this->element->setFieldValue($this->getSys_username(), $fName, "sys_creationUsername");
			}
			if($this->element->getFieldValue($fName, "sys_creationDate")==null){
				$this->element->setFieldValue($this->getSys_date(), $fName, "sys_creationDate");
			}
		}
	}

	/**
	 * checks that, the subField array of this Field is identical to the subField array of its datatype
	 * if not, then aborts and switches to a strategy of type Field.
	 * precondition: this method works only if $this->userDefinedFieldSelectorList = true
	 */
	private function assertFieldSubFieldsConsistentWithDatatypeSubFields($field) {

		$fName = $field->getFieldName();
		$dtName = $field->getDataType()->getDataTypeName();

		// 1. do we have some selected subfields for this field
		$fSubfields = $this->fieldSelectorList[$fName];

		// 2. first selected fields -> stores it into dataType subFields array as a model
		if(!is_array($this->dataTypeSubFields)) $this->dataTypeSubFields = array();
		$dtSubfields = $this->dataTypeSubFields[$dtName];
		if(!isset($dtSubfields)) {
			$this->dataTypeSubFields[$dtName] = $fSubfields;
		}
		// 3. compares dataType subfields to Field subfields, it should be identical
		else {
			$fSelectedSubFields = is_array($fSubfields);
			$dtSelectedSubFields = is_array($dtSubfields);
			$cancel = false;
			// if one has some selected subfields and the other not then cancels
			if($fSelectedSubFields && !$dtSelectedSubFields ||
				!$fSelectedSubFields && $dtSelectedSubFields) {
				$cancel = true;
			}
			// if the two have some selected subfields, then the two lists must be identical
			elseif($fSelectedSubFields && $dtSelectedSubFields) {
				// cancel if selected subfields differs in length
				if(count($fSubfields) != count($dtSubfields)) $cancel = true;
				// cancel if length of array diff is bigger than 0
				else $cancel = count(array_diff_key($fSubfields, $dtSubfields)) > 0;
			}
			if($cancel) {
				throw new ServiceException('selected subfields list is not identical for all fields having same datatype, retry with strategy FIELD', ServiceException::OPERATION_CANCELED);
			}
		}
	}

	private function isFieldAlreadyDefined($fName)
	{
		return isset($this->freeTextFieldList[$fName]) ||
				(isset($this->fieldQueryIndex[$fName]) && ($this->fieldQueryIndex[$fName] >= 0));
	}
	public function addStructuralField($structuralFieldName)
	{
		if(!isset($this->structuralFields[$structuralFieldName]))
		{
			$this->structuralFields[$structuralFieldName] = -1;
			$this->structuralFieldsCounter++;
		}
	}
	public function getField($fieldName)
	{
		return $this->fieldList[$this->fieldQueryIndex[$fieldName]][$fieldName];
	}
	public function doesFieldExist($fieldName)
	{
		return $this->fieldList[$this->fieldQueryIndex[$fieldName]][$fieldName];
	}
	public function getListIterator()
	{
		throw new ElementServiceException("ElementServiceFieldList is multi list", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function isEmpty()
	{
		return ($this->numberOfQueries == 0);
	}
	public function count()
	{
		return count($this->fieldQueryIndex);
	}
}
/**
 * Extracts structural fields from FieldSelector LogExp and FieldSortingKeyList,
 * and adds them to structuralFields array in ElementQueryPlanner for later use.
 */
class StructuralFieldsExtractor implements LogExpVisitor
{
	private $elementQueryPlanner;
	private $isSortingOrFilteringOnElement;

	// Object lifecycle
	public static function createInstance($elementQueryPlanner)
	{
		$returnValue = new StructuralFieldsExtractor();
		$returnValue->reset($elementQueryPlanner);
		return $returnValue;
	}
	public function reset($elementQueryPlanner)
	{
		$this->elementQueryPlanner = $elementQueryPlanner;
		$this->isSortingOrFilteringOnElement = false;
	}

	// implementation

	public function extractStructuralFields($fieldSelectorLogExp, $fieldSortingKeyList)
	{
		// FieldSelector extraction
		if(isset($fieldSelectorLogExp)) $fieldSelectorLogExp->acceptLogExpVisitor($this);

		// FieldSortingKey extraction
		if(isset($fieldSortingKeyList))
		{
			foreach($fieldSortingKeyList->getListIterator() as $fsk)
			{
				if($fieldSortingKeyList->isElementSortingKey($fsk))
				{
					$this->isSortingOrFilteringOnElement = true;
				}
				else
				{
					$this->elementQueryPlanner->addStructuralField($fsk->getFieldName());
				}
			}
		}
	}

	/**
	 * Return true if at least one sorting key  or one filter is on element instead of only fields
	 */
	public function isSortingOrFilteringOnElement()
	{
		return $this->isSortingOrFilteringOnElement;
	}

	// LogExp visitor implementation
	public function actOnAndExp($andLogExp)
	{
		$ops = $andLogExp->getOperands();
		if(isset($ops))
		{
			foreach($ops as $logExp)
			{
				$logExp->acceptLogExpVisitor($this);
			}
		}
	}
	public function actOnOrExp($orLogExp)
	{
		$ops = $orLogExp->getOperands();
		if(isset($ops))
		{
			foreach($ops as $logExp)
			{
				$logExp->acceptLogExpVisitor($this);
			}
		}
	}
	public function actOnNotExp($notLogExp)
	{
		$logExp = $notLogExp->getLogExp();
		if(isset($logExp)) $logExp->acceptLogExpVisitor($this);
	}
	public function actOnSmaller($obj, $val)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	public function actOnSmallerEq($obj, $val)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	public function actOnGreater($obj, $val)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	public function actOnGreaterEq($obj, $val)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	public function actOnEqual($obj, $val)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	public function actOnNotEqual($obj, $val)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	public function actOnIn($obj, $vals)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	public function actOnNotIn($obj, $vals)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	public function actOnLike($obj, $val)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	public function actOnMatchAgainst($obj, $val)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	public function actOnNotLike($obj, $val)
	{
		$this->extractStructuralFieldFromFieldSelector($obj);
	}
	protected function extractStructuralFieldFromFieldSelector($fs)
	{
		if(isset($fs))
		{
			if($fs->isElementAttributeSelector())
			{
				$this->isSortingOrFilteringOnElement = true;
			}
			else
			{
				$this->elementQueryPlanner->addStructuralField($fs->getFieldName());
			}
		}
	}
	public function actOnInGroup($inGroupLogExp)
	{
		throw new ElementServiceException("actOnInGroup is not supported", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnNotInGroup($notInGroupLogExp)
	{
		throw new ElementServiceException("actOnNotInGroup is not supported", ElementServiceException::UNSUPPORTED_OPERATION);
	}
}