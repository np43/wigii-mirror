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
 * Element Service base SQL builder
 * Created by CWE on 26 sept. 09
 * Changed by Medair (CWE,LMA) on 06.12.2016 to allow deeper inheritance
 */
class ElementSqlBuilder extends MySqlQueryBuilder implements FieldListVisitor, ElementDataTypeSubfieldVisitor, LogExpVisitor
{
	private $_debugLogger;

	private $tripod;
	private $transS;
	private $dbAS;
	private $fieldSelectorList;
	private $selectedLanguages;
	protected $structuralFields;
	protected $structuralFieldTable;
	private $fieldSelectorLogExp;
	private $fieldSortingKeyList;
	private $incElementDetail;
	private $queryStrategy;
	private $sqlQueryType;
	private $dataTypeTableAlias;
	private $elementTableAlias;
	private $elementJoinColumn;
	private $fieldPrefix;
	private $fieldStartDelimiter;
	private $fieldEndDelimiter;
	private $userFriendlySqlColNames;
	private $fieldCounter;
	private $sqlColsFL;
	private $sqlFromFL;
	private $sqlWhereFL;
	private $element;
	private $beforeInsertId;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementSqlBuilder");
		}
		return $this->_debugLogger;
	}

	// Object life cycle

	public static function createInstance()
	{
		$returnValue = new ElementSqlBuilder();
		$returnValue->reset();
		return $returnValue;
	}
	public function reset()
	{
		parent::reset();
		$this->freeMemory();
		$this->fieldCounter = 0;
		$this->setFieldPrefix('F'); // default field prefix
		$this->setFieldStartDelimiter('X'); // default field start delimiter
		$this->setFieldEndDelimiter('Z'); // default field end delimiter
		$this->userFriendlySqlColNames = false;
		$tripod = $this->getTripod();
		if(isset($tripod) && isset($tripod->elementPMapper))
		{
			$tripod->elementPMapper->resetSelectedColForFieldList();
		}
	}
	public function freeMemory()
	{
		unset($this->fieldSelectorList);
		unset($this->selectedLanguages);
		unset($this->structuralFields);
		unset($this->structuralFieldTable);
		unset($this->fieldSelectorLogExp);
		unset($this->fieldSortingKeyList);
		unset($this->dataTypeTableAlias);
		unset($this->elementTableAlias);
		unset($this->elementJoinColumn);
		unset($this->sqlColsFL);
		unset($this->sqlFromFL);
		unset($this->sqlWhereFL);
		unset($this->element);
		unset($this->beforeInsertId);
	}

	// dependency injection

	public function setTripod($elementServiceTripod)
	{
		$this->tripod = $elementServiceTripod;
		$this->tripod->elementSqlBuilder = $this;
	}
	protected function getTripod()
	{
		return $this->tripod;
	}
	public function setTranslationService($translationService)
	{
		$this->transS = $translationService;
	}
	protected function getTranslationService()
	{
		// autowired
		if(!isset($this->transS))
		{
			$this->transS = ServiceProvider::getTranslationService();
		}
		return $this->transS;
	}

	public function setDbAdminService($dbAdminService)
	{
		$this->dbAS = $dbAdminService;
	}
	protected function getDbAdminService()
	{
		// autowired
		if(!isset($this->dbAS))
		{
			$this->dbAS = ServiceProvider::getDbAdminService();
		}
		return $this->dbAS;
	}

	// Element Sql Builder parameters

	/**
	 * FieldSelectorList by field name.
	 * for each fieldName, contains an array or FieldSelector or the FieldName directly.
	 */
	public function setFieldSelectorListByFieldName($fieldSelectorList)
	{
		$this->fieldSelectorList = $fieldSelectorList;
		$tripod = $this->getTripod();
		if(isset($tripod) && isset($tripod->elementPMapper))
		{
			$tripod->elementPMapper->setFieldSelectorListByFieldName($fieldSelectorList);
		}
	}
	protected function getFieldSelectorListByFieldName()
	{
		return $this->fieldSelectorList;
	}

	/**
	 * Sets an array of selected languages for multi language fields
	 */
	public function setSelectedLanguages($selectedLanguages)
	{
		ArgValidator::assertArrayInclusion('selected languages are not valid installed languages',
			$selectedLanguages,
			$this->getTranslationService()->getVisibleLanguage());
		$this->selectedLanguages = $selectedLanguages;
	}
	protected function getSelectedLanguages()
	{
		return $this->selectedLanguages;
	}

	/**
	 * an array of free text fields, indexed by field name
	 */
	public function setFreeTextFields($freeTextFields)
	{
		$tripod = $this->getTripod();
		if(isset($tripod) && isset($tripod->elementPMapper))
		{
			$tripod->elementPMapper->setFreeTextFields($freeTextFields);
		}
	}

	/**
	 * an array of mandatory fields for the query structure.
	 * indexed by field name and containing a boolean as a value. If true, then structural field will be a selected column.
	 */
	public function setStructuralFields($structuralFields)
	{
		$this->structuralFields = $structuralFields;
	}
	public function setFieldSelectorLogExp($fieldSelectorLogExp)
	{
		$this->fieldSelectorLogExp = $fieldSelectorLogExp;
	}
	public function setFieldSortingKeyList($fieldSortingKeyList)
	{
		$this->fieldSortingKeyList = $fieldSortingKeyList;
	}

	public function setIncludeElementDetail($includeElementDetail)
	{
		$this->incElementDetail = $includeElementDetail;
	}
	protected function includeElementDetail()
	{
		return $this->incElementDetail;
	}

	public function setQueryStrategy($queryStrategy)
	{
		$this->queryStrategy = $queryStrategy;
	}
	public function getQueryStrategy()
	{
		return $this->queryStrategy;
	}

	public function setSqlQueryType($sqlQueryType)
	{
		$this->sqlQueryType = $sqlQueryType;
	}
	public function getSqlQueryType()
	{
		return $this->sqlQueryType;
	}

	protected function setDataTypeTableAlias($dataTypeTableAlias)
	{
		$this->dataTypeTableAlias = $dataTypeTableAlias;
	}
	protected function getDataTypeTableAlias()
	{
		if($this->getQueryStrategy() === ElementQueryPlanner::QSTRATEGY_JOIN)
		{
			throw new ElementServiceException('unsupported operation in join strategy', ElementServiceException::UNSUPPORTED_OPERATION);
		}
		return $this->dataTypeTableAlias;
	}
	protected function setElementJoinColumn($elementJoinColumn)
	{
		$this->elementJoinColumn = $elementJoinColumn;
	}
	protected function getElementJoinColumn()
	{
		if($this->getQueryStrategy() !== ElementQueryPlanner::QSTRATEGY_JOIN)
		{
			throw new ElementServiceException('operation only supported in join strategy', ElementServiceException::UNSUPPORTED_OPERATION);
		}
		return $this->elementJoinColumn;
	}

	protected function setFieldPrefix($fieldPrefix)
	{
		$this->fieldPrefix = $fieldPrefix;
	}
	public function getFieldPrefix()
	{
		return $this->fieldPrefix;
	}

	protected function setFieldStartDelimiter($fieldStartDelimiter)
	{
		$this->fieldStartDelimiter = $fieldStartDelimiter;
	}
	public function getFieldStartDelimiter()
	{
		return $this->fieldStartDelimiter;
	}
	protected function setFieldEndDelimiter($fieldEndDelimiter)
	{
		$this->fieldEndDelimiter = $fieldEndDelimiter;
	}
	public function getFieldEndDelimiter()
	{
		return $this->fieldEndDelimiter;
	}

	public function setUserFriendlySqlColNames($bool) {
		$this->userFriendlySqlColNames = $bool;
	}
	public function hasUserFriendlySqlColNames() {
		return $this->userFriendlySqlColNames;
	}

	protected function getElement()
	{
		return $this->element;
	}
	/**
	 * sets the element for persistance
	 * if insert, then must provide the user elementId under which values are stored in the wigiiBag.
	 */
	public function setElement($element, $beforeInsertId=null)
	{
		$this->element = $element;
	}
	protected function getBeforeInsertId()
	{
		return $this->beforeInsertId;
	}

	// Element Sql Builder implementation

	public function getSql()
	{
		$sqlT = $this->getSqlQueryType();
		if($sqlT === MySqlFacade::Q_INSERTONE ||
			$sqlT === MySqlFacade::Q_UPDATE)
		{
			return parent::getSql();
		}
		else throw new ElementServiceException('needs implementation in child', ElementServiceException::UNSUPPORTED_OPERATION);
	}

	protected function getSqlColumnsForFieldList()
	{
		$sqlT = $this->getSqlQueryType();
		if(($sqlT !==  MySqlFacade::Q_SELECTALL) &&
			($sqlT !==  MySqlFacade::Q_SELECTONE))
		{
			throw new ElementServiceException('operation only for select queries', ElementServiceException::UNSUPPORTED_OPERATION);
		}
		return $this->sqlColsFL;
	}

	protected function getFromClauseForFieldList()
	{
		$sqlT = $this->getSqlQueryType();
		if(($sqlT !==  MySqlFacade::Q_SELECTALL) &&
			($sqlT !==  MySqlFacade::Q_SELECTONE))
		{
			throw new ElementServiceException('operation only for select queries', ElementServiceException::UNSUPPORTED_OPERATION);
		}
		return $this->sqlFromFL;
	}

	/**
	 * $elementTableAlias = Element table alias to use when filtering on Element columns
	 */
	protected function getWhereClauseForFieldList($elementTableAlias='E')
	{
		$sqlT = $this->getSqlQueryType();
		if(($sqlT !==  MySqlFacade::Q_SELECTALL) &&
			($sqlT !==  MySqlFacade::Q_SELECTONE))
		{
			throw new ElementServiceException('operation only for select queries', ElementServiceException::UNSUPPORTED_OPERATION);
		}
		if($this->getQueryStrategy() === ElementQueryPlanner::QSTRATEGY_DATATYPE)
		{
			return $this->getDataTypeTableAlias().".field in ($this->sqlWhereFL)";
		}
		// builds where clause based on FieldSelector LogExp if not done yet
		elseif(strlen($this->sqlWhereFL) == 0 && isset($this->fieldSelectorLogExp))
		{
			if(!isset($this->structuralFields)) throw new ElementServiceException("cannot convert field selector log exp to where clause if structural fields have not been calculated", ElementServiceException::UNEXPECTED_ERROR);
			$this->elementTableAlias = $elementTableAlias;
			// reduces LogExp (clones if NOT not to destroy original tree).
			$this->fieldSelectorLogExp = $this->fieldSelectorLogExp->reduceNegation($this->fieldSelectorLogExp instanceof LogExpNot);
			$this->fieldSelectorLogExp->acceptLogExpVisitor($this);
			return $this->sqlWhereFL;
		}
		else return $this->sqlWhereFL;
	}

	protected function getSqlColumnsForElement($tableAlias, $elementPrefix='E')
	{
		$sqlT = $this->getSqlQueryType();
		if(($sqlT !==  MySqlFacade::Q_SELECTALL) &&
			($sqlT !==  MySqlFacade::Q_SELECTONE) ||
			!$this->includeElementDetail())
		{
			throw new ElementServiceException('operation only for select queries with element detail', ElementServiceException::UNSUPPORTED_OPERATION);
		}
		$tripod = $this->getTripod();
		if(!isset($tripod) || !isset($tripod->elementServiceImpl)) throw new ElementServiceException('ElementServiceTripod can not be null', ElementServiceException::UNEXPECTED_ERROR);
		return $tripod->elementServiceImpl->getSqlColumnsForElement($tableAlias, $elementPrefix);
	}

	/**
	 * $elementTableAlias = Element table alias to use when sorting on Element columns
	 */
	protected function convertFieldSortingKeyListToOrderByClause($elementTableAlias='E')
	{
		if(isset($this->fieldSortingKeyList))
		{
			if(!isset($this->structuralFields)) throw new ElementServiceException("cannot convert field sorting key list to order by clause if structural fields have not been calculated", ElementServiceException::UNEXPECTED_ERROR);
			$selectedLanguages = $this->getSelectedLanguages();
			if(!isset($selectedLanguages)) $selectedLanguages = $this->getTranslationService()->getVisibleLanguage();

			foreach($this->fieldSortingKeyList->getListIterator() as $fsk)
			{
				// if element sorting key
				if($this->fieldSortingKeyList->isElementSortingKey($fsk))
				{
					$subfieldName = $fsk->getSubFieldName();
					if($subfieldName === "id") $subfieldName.= "_element";
					$this->orderByCol("$elementTableAlias.$subfieldName", $fsk->isAscending());
				}
				// else we have a field sorting key
				else
				{
					// retrieves field
					$field = $this->structuralFields[$fsk->getFieldName()];
					if(!isset($field) || $field === true || $field === false) throw new ElementServiceException("cannot convert field sorting key list to order by clause if structural fields have not been linked to a field", ElementServiceException::UNEXPECTED_ERROR);
					// retrieves table alias
					$tableAlias = $this->structuralFieldTable[$fsk->getFieldName()];
					// retrieves subfield name
					$subfieldName = $fsk->getSubFieldName();
					if(!isset($subfieldName)) $subfieldName = "value";

					// checks subfield and extracts multilanguage parameter
					$dataType = $field->getDataType();
					$x = $dataType->getXml();
					$dbFieldParams = $x->xpath($subfieldName);
					if(!$dbFieldParams) throw new ElementServiceException("subField '$subfieldName' is not defined in config of datatype ".$dataType->getDataTypeName(), ElementServiceException::NO_CORRESPONDANT_FIELD);
					$dbFieldParams = $dbFieldParams[0];
					$multilanguage = (((string)$dbFieldParams['multiLanguage']) == '1');

					if($multilanguage)
					{
						foreach($selectedLanguages as $lang => $language)
						{
							$this->orderByCol("$tableAlias.$subfieldName".(isset($lang)?'_'.$lang:''), $fsk->isAscending());
						}
					}
					else
					{
						$this->orderByCol("$tableAlias.$subfieldName", $fsk->isAscending());
					}
				}
			}
		}
	}

	// FieldList visitor implementation

	public function actOnField($field, $dataType)
	{
		if(is_null($field)) throw new ElementServiceException('field can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($dataType)) throw new ElementServiceException('dataType can not be null', ElementServiceException::INVALID_ARGUMENT);
		switch($this->getSqlQueryType())
		{
			case MySqlFacade::Q_SELECTALL:
				switch($this->getQueryStrategy())
				{
					case ElementQueryPlanner::QSTRATEGY_JOIN:
						$this->actOnFieldForSelectAllStrategyJoin($field, $dataType);
						break;
					default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
				}
				break;
			case MySqlFacade::Q_SELECTONE:
				switch($this->getQueryStrategy())
				{
					case ElementQueryPlanner::QSTRATEGY_JOIN:
						$this->actOnFieldForSelectOneStrategyJoin($field, $dataType);
						break;
					case ElementQueryPlanner::QSTRATEGY_DATATYPE:
						$this->actOnFieldForSelectOneStrategyDataType($field, $dataType);
						break;
					default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
				}
				break;
			case MySqlFacade::Q_INSERTONE:
				switch($this->getQueryStrategy())
				{
					case ElementQueryPlanner::QSTRATEGY_DATATYPE:
						$this->actOnFieldForInsertOneStrategyDataType($field, $dataType);
						break;
					case ElementQueryPlanner::QSTRATEGY_FIELD:
						$this->actOnFieldForInsertOneStrategyField($field, $dataType);
						break;
					default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
				}
				break;
			case MySqlFacade::Q_UPDATE:
				switch($this->getQueryStrategy())
				{
					case ElementQueryPlanner::QSTRATEGY_FIELD:
						$this->actOnFieldForUpdate($field, $dataType);
						break;
					default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
				}
				break;
			default: throw new ElementServiceException('invalid sql type, should be one of SELECTALL, SELECTONE, INSERTONE, UPDATE', ElementServiceException::INVALID_ARGUMENT);
		}
		$this->fieldCounter++;
	}
	protected function actOnFieldForSelectAllStrategyJoin($field, $dataType)
	{
		// example: select FName.value as FXNameXvalueZZ from Element E left join Strings as FName on FName.id_element = E.id_element and FName.field = Name
		$fName = $field->getFieldName();

		// adds columns to select only if not a structural field or an explicitely selected structural field
		if(is_null($this->structuralFields) || is_null($this->structuralFields[$fName]) || ($this->structuralFields[$fName] === true))
		{
			$this->visitDataTypeSubfields($field, $dataType);
		}

		// adds left join for field
		$fP = $this->getFieldPrefix();
		$tableAlias = "`".$fP.$fName."`";
		$this->leftJoinForFieldList($this->getDbAdminService()->getDataTypeTableName($dataType),
				$tableAlias,
				"id_element",
				$this->getElementJoinColumn(),
				$this->formatBinExp("$tableAlias.field", '=', $fName, MySqlQueryBuilder::SQLTYPE_VARCHAR));

		// records field and table alias for structural fields
		$this->structuralFields[$fName] = $field;
		$this->structuralFieldTable[$fName] = $tableAlias;
	}
	protected function actOnFieldForSelectOneStrategyJoin($field, $dataType)
	{
		// example: select FName.value as FXNameXvalueZZ from Element E left join Strings as FName on FName.id_element = E.id_element and FName.field = Name

		// adds columns to select
		$this->visitDataTypeSubfields($field, $dataType);

		// adds left join for field
		$fP = $this->getFieldPrefix();
		$fName = $field->getFieldName();
		$tableAlias = "`".$fP.$fName."`";
		$this->leftJoinForFieldList($this->getDbAdminService()->getDataTypeTableName($dataType),
				$tableAlias,
				"id_element",
				$this->getElementJoinColumn(),
				$this->formatBinExp("$tableAlias.field", '=', $fName, MySqlQueryBuilder::SQLTYPE_VARCHAR));
	}
	protected function actOnFieldForSelectOneStrategyDataType($field, $dataType)
	{
		// example: select dt.field, dt.value from Strings as dt where dt.field in (FirstName, Name)

		// adds columns to select if not done yet
		$tripod = $this->getTripod();
		if($this->sqlColsFL == '')
		{
			$tableAlias = $this->getDataTypeTableAlias();
			// adds datatype table to FROM clause
			$this->sqlFromFL = $this->getDbAdminService()->getDataTypeTableName($dataType)." as ".$tableAlias;
			// adds field column
			$this->selectColForFieldList($tableAlias, 'field', $this->getFieldPrefix().'field');
			// adds subfields
			$this->visitDataTypeSubfields($field, $dataType);
		}
		// communicates selected field to elementPMapper
		elseif(isset($tripod) && isset($tripod->elementPMapper))
		{
			$tripod->elementPMapper->actOnField($field, $dataType);
		}

		// adds where clause on field
		if(strlen($this->sqlWhereFL) > 0) $this->sqlWhereFL .= ', ';
		$this->sqlWhereFL .= $this->formatValue($field->getFieldName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
	}
	protected function actOnFieldForInsertOneStrategyDataType($field, $dataType)
	{
		// first field to be inserted, then adds columns name and table name
		$element = $this->getElement();
		if($this->fieldCounter === 0)
		{
			if(!isset($element)) throw new ElementServiceException("for insertOne, element should be set", ElementServiceException::INVALID_ARGUMENT);
			$this->setTableForInsert($this->getDbAdminService()->getDataTypeTableName($dataType));
			$this->insertValue("id_element", $element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
			$this->insertValue("field", $field->getFieldName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$this->visitDataTypeSubfields($field, $dataType);
		}
		// else only adds new values
		else
		{
			$this->insertMoreRecord();
			$this->insertMoreValue($element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
			$this->insertMoreValue($field->getFieldName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$this->visitDataTypeSubfields($field, $dataType);
		}
	}
	protected function actOnFieldForInsertOneStrategyField($field, $dataType)
	{
		$element = $this->getElement();
		if(!isset($element)) throw new ElementServiceException("for insertOne, element should be set", ElementServiceException::INVALID_ARGUMENT);
		$this->setTableForInsert($this->getDbAdminService()->getDataTypeTableName($dataType));
		$this->insertValue("id_element", $element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
		$this->insertValue("field", $field->getFieldName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$this->visitDataTypeSubfields($field, $dataType);
	}

	protected function actOnFieldForUpdate($field, $dataType)
	{
		$element = $this->getElement();
		if(!isset($element)) throw new ElementServiceException("for update, element should be set", ElementServiceException::INVALID_ARGUMENT);
		$this->setTableForUpdate($this->getDbAdminService()->getDataTypeTableName($dataType), true);
		$this->updateValue("id_element", $element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
		$this->updateValue("field", $field->getFieldName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$this->visitDataTypeSubfields($field, $dataType);
	}

	// Element DataType subfield visitor implementation

	protected function visitDataTypeSubfields($field, $dataType)
	{
		$fsl = $this->getFieldSelectorListByFieldName();
		if(isset($fsl))
		{
			$fsl = $fsl[$field->getFieldName()];
			if(!is_array($fsl))
			{
				unset($fsl);
			}
		}
		$this->getTripod()->elementServiceImpl->visitDataTypeSubfields($field, $dataType, $this, $fsl, $this->getSelectedLanguages());
	}

	public function actOnSubfield($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang=null)
	{
		switch($this->getSqlQueryType())
		{
			case MySqlFacade::Q_SELECTALL:
				switch($this->getQueryStrategy())
				{
					case ElementQueryPlanner::QSTRATEGY_JOIN:
						$this->actOnSubfieldForSelectAllStrategyJoin($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang);
						break;
					default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
				}
				break;
			case MySqlFacade::Q_SELECTONE:
				switch($this->getQueryStrategy())
				{
					case ElementQueryPlanner::QSTRATEGY_JOIN:
						$this->actOnSubfieldForSelectOneStrategyJoin($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang);
						break;
					case ElementQueryPlanner::QSTRATEGY_DATATYPE:
						$this->actOnSubfieldForSelectOneStrategyDataType($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang);
						break;
					default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
				}
				break;
			case MySqlFacade::Q_INSERTONE:
				switch($this->getQueryStrategy())
				{
					case ElementQueryPlanner::QSTRATEGY_DATATYPE:
						$this->actOnSubfieldForInsertOneStrategyDataType($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang);
						break;
					case ElementQueryPlanner::QSTRATEGY_FIELD:
						$this->actOnSubfieldForInsertOneStrategyField($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang);
						break;
					default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
				}
				break;
			case MySqlFacade::Q_UPDATE:
				switch($this->getQueryStrategy())
				{
					case ElementQueryPlanner::QSTRATEGY_FIELD:
						$this->actOnSubfieldForUpdate($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang);
						break;
					default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
				}
				break;
			default: throw new ElementServiceException('invalid sql type, should be one of SELECTALL, SELECTONE, INSERTONE, UPDATE', ElementServiceException::INVALID_ARGUMENT);
		}
	}
	protected function actOnSubfieldForSelectAllStrategyJoin($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang)
	{
		$this->actOnSubfieldForSelectStrategyJoin($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang);
	}
	protected function actOnSubfieldForSelectOneStrategyJoin($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang)
	{
		$this->actOnSubfieldForSelectStrategyJoin($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang);
	}
	protected function actOnSubfieldForSelectStrategyJoin($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang)
	{
		// if sqlType is longblob or blob and user did not explicitly select subfield, then ignores it
		if((($sqlType === MySqlQueryBuilder::SQLTYPE_BLOB) || ($sqlType === MySqlQueryBuilder::SQLTYPE_LONGBLOB)) && !$userSelectedSubField) return;

		// example: select FName.value as FXNameXvalueZZ from Element E left join Strings as FName on FName.id_element = E.id_element and FName.field = Name
		$fP = $this->getFieldPrefix();
		$fName = $field->getFieldName();
		$sqlColName = $this->encodeFieldNameForSelect($fName, $subFieldName, $lang);
		$this->selectColForFieldList("`".$fP.$fName."`", $subFieldName.(isset($lang)?'_'.$lang:''), $sqlColName);
		$this->informElementPMapperOfSelectedCol($sqlColName, $field, $dataType, $subFieldName, $subFieldType, $sqlType, $lang);
	}
	protected function actOnSubfieldForSelectOneStrategyDataType($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang)
	{
		//eput($field->getFieldName()." ".$subFieldName." ".$userSelectedSubField.":\n");
		// if sqlType is longblob or blob and user did not explicitly select subfield, then ignores it
		if((($sqlType === MySqlQueryBuilder::SQLTYPE_BLOB) || ($sqlType === MySqlQueryBuilder::SQLTYPE_LONGBLOB)) && !$userSelectedSubField) return;

		// example: select dt.field, dt.value from Strings as dt where dt.field in (FirstName, Name)
		$sqlColName = $this->getFieldPrefix().$subFieldName.(isset($lang)?'_'.$lang:'');
		$this->selectColForFieldList($this->getDataTypeTableAlias(), $subFieldName.(isset($lang)?'_'.$lang:''), $sqlColName);
		$this->informElementPMapperOfSelectedCol($sqlColName, $field, $dataType, $subFieldName, $subFieldType, $sqlType, $lang);
	}
	protected function actOnSubfieldForInsertOneStrategyDataType($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang)
	{
		$element = $this->getElement();
		$val = $field->getValue($this->getBeforeInsertId(), $element->getWigiiBag(), $subFieldName);
		//if multilanguage
		if(isset($lang))
		{
			if(is_array($val)) $val = $val[$lang];
			$subFieldName .= "_".$lang;
		}
		// If first field then inserts field name
		if($this->fieldCounter === 0)
		{
			$this->insertValue($subFieldName, $this->preformatValue($val, $subFieldType, $sqlType), $sqlType);
		}
		else
		{
			$this->insertMoreValue($this->preformatValue($val, $subFieldType, $sqlType), $sqlType);
		}
	}
	protected function actOnSubfieldForInsertOneStrategyField($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang)
	{
		$element = $this->getElement();
		$val = $field->getValue($this->getBeforeInsertId(), $element->getWigiiBag(), $subFieldName);
		//if multilanguage
		if(isset($lang))
		{
			if(is_array($val)) $val = $val[$lang];
			$subFieldName .= "_".$lang;
		}
		$this->insertValue($subFieldName, $this->preformatValue($val, $subFieldType, $sqlType), $sqlType);
	}
	protected function actOnSubfieldForUpdate($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang)
	{
		$element = $this->getElement();
		$val = $field->getValue($element->getId(), $element->getWigiiBag(), $subFieldName);
		if(($sqlType === MySqlQueryBuilder::SQLTYPE_BLOB) || ($sqlType === MySqlQueryBuilder::SQLTYPE_LONGBLOB))
		{
			// if blobs then update if
			// - content is not null
			// - subfield is selected by user
			// - content is null and path is null
			if(isset($val) || $userSelectedSubField) $okForUpdate = true;
			else
			{
				if($dataType->getDataTypeName() === "Files")
				{
					$path = $field->getValue($element->getId(), $element->getWigiiBag(), "path");
					$okForUpdate = !isset($path);
				}
				else $okForUpdate = false;
			}
		}
		else $okForUpdate = true;

		if($okForUpdate)
		{
			//if multilanguage
			if(isset($lang))
			{
				if(is_array($val)) $val = $val[$lang];
				$subFieldName .= "_".$lang;
			}
			if(in_array($subFieldName, array("sys_creationUser", "sys_creationUsername", "sys_creationDate"))){
				$this->insertValueIfNotExist($subFieldName, $this->preformatValue($val, $subFieldType, $sqlType), $sqlType);
			} else {
				$this->updateValue($subFieldName, $this->preformatValue($val, $subFieldType, $sqlType), $sqlType);
			}
		}
	}
	/**
	 * (extension point)
	 * Pre-Formats value according to Wigii subFieldType and perhaps sqlType
	 * This method is called before calling MySqlQueryBuilder->formatValue
	 */
	protected function preformatValue($value, $subFieldType, $sqlType)
	{
		// formats multiple-select
		if(strtolower($subFieldType) === "multiple-select")
		{
			if(is_array($value))
			{
				return implode('XAGU___XAGU', $value);
			}
			else return $value;
		}
		// does nothing
		else return $value;
	}

	protected function informElementPMapperOfSelectedCol($sqlColName, $field, $dataType, $subFieldName, $subFieldType, $sqlType, $lang)
	{
		$tripod = $this->getTripod();
		if(isset($tripod) && isset($tripod->elementPMapper))
		{
			$tripod->elementPMapper->selectColForFieldList($sqlColName, $field, $dataType, $subFieldName, $subFieldType, $sqlType, $lang);
		}
	}


	// FieldSelector LogExp visitor implementation

	public function actOnAndExp($andLogExp)
	{
		$ops = $andLogExp->getOperands();
		if(isset($ops))
		{
			$firstOp = true;
			foreach($ops as $logExp)
			{
				if($firstOp) $firstOp = false;
				else
				{
					$this->sqlWhereFL .= " AND ";
				}
				$logExp->acceptLogExpVisitor($this);
			}
		}
	}
	public function actOnOrExp($orLogExp)
	{
		$ops = $orLogExp->getOperands();
		if(isset($ops))
		{
			$firstOp = true;
			$this->sqlWhereFL .= " (";
			foreach($ops as $logExp)
			{
				if($firstOp) $firstOp = false;
				else
				{
					$this->sqlWhereFL .= " OR ";
				}
				$logExp->acceptLogExpVisitor($this);
			}
			$this->sqlWhereFL .= ") ";
		}
	}
	public function actOnNotExp($notLogExp)
	{
		$logExp = $notLogExp->getLogExp();
		if(isset($logExp))
		{
			$this->sqlWhereFL .= " NOT(";
			$logExp->acceptLogExpVisitor($this);
			$this->sqlWhereFL .= ") ";
		}
	}
	public function actOnSmaller($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '<', $val);
	}
	public function actOnSmallerEq($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '<=', $val);
	}
	public function actOnGreater($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '>', $val);
	}
	public function actOnGreaterEq($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '>=', $val);
	}
	public function actOnEqual($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '=', $val);
	}
	public function actOnNotEqual($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '!=', $val);
	}
	public function actOnIn($obj, $vals)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, 'IN', $vals);
	}
	public function actOnNotIn($obj, $vals)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, 'NOTIN', $vals);
	}
	public function actOnLike($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, 'LIKE', $val);
	}
	public function actOnMatchAgainst($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, 'MATCHAGAINST', $val);
	}
	public function actOnNotLike($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, 'NOTLIKE', $val);
	}
	protected function convertFieldSelectorBinExpToWhereClauseChunk($fs, $op, $val)
	{
		if(is_null($fs)) throw new ElementServiceException("Field selector binary expression cannot be null", ElementServiceException::INVALID_ARGUMENT);

		// if element field selector
		if($fs->isElementAttributeSelector())
		{
			$subfieldName = $fs->getSubFieldName();
			$sqlColName = $this->elementTableAlias.".".$subfieldName;
			switch($subfieldName)
			{
				case 'id':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName."_element", $op, $val, MySqlQueryBuilder::SQLTYPE_INT);
					break;
				case 'modulename':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_VARCHAR);
					break;
				case 'sys_creationDate':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_INT);
					break;
				case 'sys_creationUser':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_INT);
					break;
				case 'sys_creationUsername':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_VARCHAR);
					break;
				case 'sys_date':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_INT);
					break;
				case 'sys_user':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_INT);
					break;
				case 'sys_username':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_VARCHAR);
					break;
				case 'version':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_VARCHAR);
					break;
				case 'state_locked':
				case 'state_important1':
				case 'state_important2':
				case 'state_hidden':
				case 'state_archived':
				case 'state_deprecated':
				case "state_finalized" :
				case "state_approved" :
				case "state_dismissed" :
				case "state_blocked" :
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
					break;
				case 'state_lockedInfo':
				case 'state_important1Info':
				case 'state_important2Info':
				case 'state_hiddenInfo':
				case 'state_archivedInfo':
				case 'state_deprecatedInfo':
				case "state_finalizedInfo" :
				case "state_approvedInfo" :
				case "state_dismissedInfo" :
				case "state_blockedInfo" :
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_TEXT);
					break;
				case 'sys_lockMicroTime':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_INT);
					break;
				case 'sys_lockId':
					$this->sqlWhereFL .= $this->formatBinExp($sqlColName, $op, $val, MySqlQueryBuilder::SQLTYPE_VARCHAR);
					break;
				default: throw new ElementServiceException("Invalid element attribute selector $subfieldName used in where clause", ElementServiceException::INVALID_ARGUMENT);
			}
		}
		// else we have a field selector
		else
		{
			// retrieves field
			$field = $this->structuralFields[$fs->getFieldName()];
			if(!isset($field) || $field === true || $field === false) throw new ElementServiceException("cannot convert field log exp to where clause if structural fields have not been linked to a field", ElementServiceException::UNEXPECTED_ERROR);
			// retrieves table alias
			$tableAlias = $this->structuralFieldTable[$fs->getFieldName()];
			// retrieves subfield name
			$subfieldName = $fs->getSubFieldName();
			if(!isset($subfieldName)) $subfieldName = "value";

			// checks subfield
			$dataType = $field->getDataType();
			$x = $dataType->getXml();
			$dbFieldParams = $x->xpath($subfieldName);
			if(!$dbFieldParams) throw new ElementServiceException("subField $subFieldName is not defined in config of datatype ".$dataType->getDataTypeName(), ElementServiceException::NO_CORRESPONDANT_FIELD);
			$dbFieldParams = $dbFieldParams[0];

			// extracts multilanguage parameter
			$multilanguage = (((string)$dbFieldParams['multiLanguage']) == '1');

			// multiple-select
			$multipleSelect = (strtolower((string)$dbFieldParams['type']) === 'multiple-select');

			// extracts sql type parameter
			$sqlType = (string)$dbFieldParams['sqlType'];
			$tripod = $this->getTripod();
			if(!isset($tripod) || !isset($tripod->elementServiceImpl)) throw new ElementServiceException('ElementServiceTripod can not be null', ElementServiceException::UNEXPECTED_ERROR);
			$sqlType = $tripod->elementServiceImpl->dataTypeSqlType2sqlType($sqlType);

			if($multilanguage)
			{
				$selectedLanguages = $this->getSelectedLanguages();
				if(!isset($selectedLanguages)) $selectedLanguages = $this->getTranslationService()->getVisibleLanguage();
				$langCount = 0; $langExp = '';
				foreach($selectedLanguages as $lang => $language)
				{
					if($langCount > 0)
					{
						if($op == "NOTLIKE" || $op=="!=") $langExp .= " AND ";
						else $langExp .= " OR ";
					}
					if($multipleSelect)
					{
						$langExp .= $this->formatMultipleSelectBinExp("$tableAlias.$subfieldName".(isset($lang)?'_'.$lang:''), $op, $val, $sqlType);
					}
					else
					{
						$langExp .= $this->formatBinExp("$tableAlias.$subfieldName".(isset($lang)?'_'.$lang:''), $op, $val, $sqlType);
					}
					$langCount++;
				}
				if($langCount > 1)
				{
					$this->sqlWhereFL .= "($langExp)";
				}
				else
				{
					$this->sqlWhereFL .= $langExp;
				}
			}
			else
			{
				if($multipleSelect)
				{
					$this->sqlWhereFL .= $this->formatMultipleSelectBinExp("$tableAlias.$subfieldName", $op, $val, $sqlType);
				}
				else
				{
					$this->sqlWhereFL .= $this->formatBinExp("$tableAlias.$subfieldName", $op, $val, $sqlType);
				}
			}
		}
	}
	protected function formatMultipleSelectBinExp($fieldName, $op, $val, $sqlType)
	{
		if($op != '=' &&
			$op != '!=' &&
			$op != 'IN' &&
			$op != 'NOTIN' &&
			$op != 'LIKE' &&
			$op != 'NOTLIKE'
			) throw new ElementServiceException("binary expression operator $op is not supported with multiple-select datatypes", ElementServiceException::UNSUPPORTED_OPERATION);

		$returnValue = '';
		if($op === 'IN' && is_array($val))
		{
			$valCount = 0;
			foreach($val as $vali)
			{
				if($valCount > 0) $returnValue .= " OR ";
				$returnValue .= $this->formatMultipleSelectBinExpArrayChunk($fieldName, $op, $vali, $sqlType);
				$valCount++;
			}
			if($valCount > 1) $returnValue = "($returnValue)";
		}
		elseif($op === 'NOTIN')
		{
			if(is_array($val))
			{
				$valCount = 0;
				foreach($val as $vali)
				{
					if($valCount > 0) $returnValue .= " OR ";
					$returnValue .= $this->formatMultipleSelectBinExpArrayChunk($fieldName, $op, $vali, $sqlType);
					$valCount++;
				}
				if($valCount > 1) $returnValue = "($returnValue)";
			}
			else $returnValue = $this->formatMultipleSelectBinExpArrayChunk($fieldName, $op, $val, $sqlType);

			$returnValue = "(NOT($returnValue) OR $fieldName IS NULL)";
		}
		else $returnValue = $this->formatMultipleSelectBinExpArrayChunk($fieldName, $op, $val, $sqlType);

		return $returnValue;
	}
	private function formatMultipleSelectBinExpArrayChunk($fieldName, $op, $val, $sqlType)
	{
		$returnValue = '';
		if(is_array($val))
		{
			$firstVal = true;
			foreach($val as $vali)
			{
				if($firstVal) $firstVal = false;
				else $returnValue .= " AND ";
				$returnValue .= $this->formatMultipleSelectBinExpChunk($fieldName, $op, $vali, $sqlType);
			}
		}
		else $returnValue = $this->formatMultipleSelectBinExpChunk($fieldName, $op, $val, $sqlType);

		return $returnValue;
	}
	private function formatMultipleSelectBinExpChunk($fieldName, $op, $val, $sqlType)
	{
		switch($op)
		{
			case '=':
			case 'IN':
			case 'NOTIN':
				return $this->formatBinExp($fieldName, 'LIKE', '%'.str_replace('%', '\%', $val).'%', $sqlType);
			case '!=':
				return $this->formatBinExp($fieldName, 'NOTLIKE', '%'.str_replace('%', '\%', $val).'%', $sqlType);
			case 'LIKE':
				return $this->formatBinExp($fieldName, 'LIKE', (substr($val, 0, 1) === '%' ? '' : '%').$val.(substr($val, -1, 1) === '%' ? '' : '%'), $sqlType);
			case 'NOTLIKE':
				return $this->formatBinExp($fieldName, 'NOTLIKE', (substr($val, 0, 1) === '%' ? '' : '%').$val.(substr($val, -1, 1) === '%' ? '' : '%'), $sqlType);
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

	// SQL Builder implementation

	/**
	 * Adds a column to the SELECT for the field list
	 */
	protected function selectColForFieldList($tableAlias, $colName, $colAlias = null)
	{
		if(strlen($this->sqlColsFL) > 0) $this->sqlColsFL .= ', ';
		if(isset($colAlias)) $this->sqlColsFL .= "$tableAlias.$colName as $colAlias";
		else $this->sqlColsFL .= "$tableAlias.$colName";
	}
	/**
	 * Adds a left join to the from clause for the field list
	 * adds "left join tableName as tableAlias on tableAlias.colToJoin = joinExpression and additionalCondition"
	 */
	protected function leftJoinForFieldList($tableName, $tableAlias, $colToJoin, $joinExpression, $additionalCondition=null)
	{
		if(strlen($this->sqlFromFL) > 0) $this->sqlFromFL .= ' ';
		$this->sqlFromFL .= "left join $tableName as $tableAlias on $tableAlias.$colToJoin = $joinExpression";
		if(isset($additionalCondition)) $this->sqlFromFL .= " and $additionalCondition";
	}

	/**
	 * Encodes a field/subfield to put as a column in the select
	 */
	public function encodeFieldNameForSelect($fieldName, $subFieldName, $lang=null)
	{
		if($this->hasUserFriendlySqlColNames()) {			
			return '`'.$fieldName.($subFieldName == 'value'?'':' '.$subFieldName).(isset($lang)?' '.$lang:'').'`';
		}
		else {
			$fP = $this->getFieldPrefix();
			$fSD = $this->getFieldStartDelimiter();
			$fED = $this->getFieldEndDelimiter();
			return "`".$fP.$fSD.$fieldName.$fSD.$subFieldName.(isset($lang)?'_'.$lang:'').$fED.$fED."`";
		}
	}
}