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
 * Maps an element Db record to an ElementP
 * Created by CWE on 24 sept. 09
 */
class ElementPMapper implements RowList, FieldListVisitor
{
	private $_debugLogger;
	private $lockedForUse = true;
	private $tripod;
	private $principal;
	private $pRights; //public function getPRights(){ return $this->pRigths; }
	private $pRightsNotSet;
	private $fieldList;
	private $calculatedFields;
	private $wigiiBag;
	protected $isWigiiBagBulkLoadable;
	protected $wigiiFixedBag;
	private $recordStructureFactory;
	private $fieldSelectorList;
	private $freeTextFields;
	private $elementPrefix;
	private $elementEvaluator;
	private $elementPListEvalContext;
	private $elementPListEvalContextClassName;

	private $sqlColFieldName;
	private $sqlColSubFieldName;
	private $sqlColMap;
	private $sqlColMapField;
	private $sqlColMapMultiSelect;
	private $sqlColMapLang;
	private $sqlColSubFieldType;
	private $sqlColSqlType;
	private $sqlColLang;
	private $selectedFields;
	protected $elementPBuffer; public function getElementPBuffer(){ return $this->elementPBuffer; }

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementPMapper");
		}
		return $this->_debugLogger;
	}

	// Object life cycle

	/**
	 * recordStructureFactory: is either a recordStructurefactory to create new FieldList and new WigiiBag
	 * or an existing Element with an existing FieldList and an existing WigiiBag
	 * elementEvaluator: if not null, then this ElementEvaluator instance will be used to evaluate calculated fields
	 */
	public static function createInstance($principal, $recordStructureFactory, $pRights = null, $elementEvaluator=null)
	{
		$returnValue = new ElementPMapper();
		$returnValue->reset($principal, $recordStructureFactory, $pRights, $elementEvaluator);
		return $returnValue;
	}

	/**
	 * recordStructureFactory: is either a recordStructurefactory to create new FieldList and new WigiiBag
	 * or an existing Element with an existing FieldList and an existing WigiiBag
	 */
	public function reset($principal, $recordStructureFactory, $pRights=null, $elementEvaluator=null)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->principal = $principal;
		$this->pRights = $pRights;
		$this->elementEvaluator = $elementEvaluator;
		if(is_null($recordStructureFactory)) throw new ElementServiceException('recordStructureFactory can not be null', ElementServiceException::INVALID_ARGUMENT);
		$this->elementPBuffer = array();
		if($recordStructureFactory instanceof RecordStructureFactory)
		{
			$this->fieldList = $recordStructureFactory->createFieldList();
			$this->wigiiBag = $recordStructureFactory->createWigiiBag();
			// CWE 21.06.2018 - if FieldList instance of FormFieldList and WigiiBag instance of FormBag, 
			// then keeps recordStructureFactory active so that each element in list get their own FormBag and FormFieldList.
			if(($this->fieldList instanceof FormFieldList) && ($this->wigiiBag instanceof FormBag)) $this->recordStructureFactory = $recordStructureFactory;
		}
		elseif($recordStructureFactory instanceof Element)
		{
			$this->fieldList = $recordStructureFactory->getFieldList();
			if(!isset($this->fieldList)) throw new ElementServiceException('element->fieldList can not be null', ElementServiceException::INVALID_ARGUMENT);
			$this->wigiiBag = $recordStructureFactory->getWigiiBag();
			if(!isset($this->wigiiBag)) throw new ElementServiceException('element->wigiiBag can not be null', ElementServiceException::INVALID_ARGUMENT);
			// stores existing element if id is defined to not recreate it from database
			$elementId = $recordStructureFactory->getId();
			if(isset($elementId))
			{
				$this->elementPBuffer[$elementId] = ElementP::createInstance($recordStructureFactory);
			}
		}
		else throw new ElementServiceException('recordStructureFactory can only be an instance of RecordStructureFactory or Element', ElementServiceException::INVALID_ARGUMENT);
		// if wigii bag accepts to be bulk loaded then creates a fix bag mapping directly db rows
		if(($this->wigiiBag instanceof BulkLoadableWigiiBag) && $this->wigiiBag->acceptsFixedBag()) {
			$this->isWigiiBagBulkLoadable = true;
			$this->wigiiFixedBag = $this->createSqlMapFixedBag();
		}
		else $this->isWigiiBagBulkLoadable = false;
		$this->sqlColLang = array();
		$this->calculatedFields = FieldListArrayImpl::createInstance(false);
		$this->pRightsNotSet = true;
	}
	public function freeMemory()
	{
		unset($this->fieldList);
		unset($this->wigiiBag);
		unset($this->wigiiFixedBag);
		unset($this->recordStructureFactory);
		unset($this->elementPrefix);
		unset($this->fieldSelectorList);
		unset($this->freeTextFields);
		unset($this->selectedFields);
		unset($this->elementPBuffer);
		unset($this->elementEvaluator);
		unset($this->calculatedFields);
		$this->freeSqlQueryDef();
		if(isset($this->elementPListEvalContext)) {
			$this->elementPListEvalContext->freeMemory();
			unset($this->elementPListEvalContext);
		}
		unset($this->elementPListEvalContextClassName);
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}

	private function freeSqlQueryDef()
	{
		unset($this->sqlColFieldName);
		unset($this->sqlColSubFieldName);
		unset($this->sqlColMap);
		unset($this->sqlColMapField);
		unset($this->sqlColMapMultiSelect);
		unset($this->sqlColMapLang);
		unset($this->sqlColSubFieldType);
		unset($this->sqlColSqlType);
		unset($this->sqlColLang);
	}

	// dependency injection

	public function setTripod($elementServiceTripod)
	{
		$this->tripod = $elementServiceTripod;
		$this->tripod->elementPMapper = $this;
	}
	protected function getTripod()
	{
		return $this->tripod;
	}

	/**
	 * FieldSelectorList by field name.
	 * for each fieldName, contains an array or FieldSelector or the FieldName directly.
	 */
	public function setFieldSelectorListByFieldName($fieldSelectorList)
	{
		$this->fieldSelectorList = $fieldSelectorList;
	}
	protected function getFieldSelectorListByFieldName()
	{
		return $this->fieldSelectorList;
	}


	/**
	 * an array of free text fields, indexed by field name
	 */
	public function setFreeTextFields($freeTextFields)
	{
		$this->freeTextFields = $freeTextFields;
	}
	protected function getFreeTextFields()
	{
		return $this->freeTextFields;
	}

	public function setElementPrefix($elementPrefix)
	{
		$this->elementPrefix = $elementPrefix;
	}
	protected function getElementPrefix()
	{
		return $this->elementPrefix;
	}

	protected function getWigiiBag()
	{
		return $this->wigiiBag;
	}

	protected function getFieldList()
	{
		return $this->fieldList;

	}

	/**
	 * Sets the name of the class that should be used to instanciate an ElementPListEvalContext
	 * @param String $className
	 */
	public function setElementPListEvalContextClassName($className) {
		$this->elementPListEvalContextClassName = $className;
	}
	/**
	 * Returns the name of the class that should be used to instantiate an ElementPListEvalContext
	 * Defaults to ElementPListEvalContext.
	 */
	protected function getElementPListEvalContextClassName() {
		if(!isset($this->elementPListEvalContextClassName)) {
			$this->elementPListEvalContextClassName = 'ElementPListEvalContext';
		}
		return $this->elementPListEvalContextClassName;
	}

	/**
	 * Returns an instance of an ElementPListEvalContext
	 * @return ElementPListEvalContext
	 */
	protected function getElementPListEvalContext() {
		if(!isset($this->elementPListEvalContext)) {
			$this->elementPListEvalContext = ServiceProvider::getExclusiveAccessObject($this->getElementPListEvalContextClassName());
		}
		return $this->elementPListEvalContext;
	}

	/**
	 * Informs the ElementPMapper of the total number of elements that should have been
	 * selected if the pagination was not used.
	 * This information is injected into the ElementPListEvalContext.
	 * @param int $n the total number of objects.
	 */
	public function setTotalNumberOfElements($n) {
		$this->getElementPListEvalContext()->setTotalNumberOfElements($n);
	}

	/**
	 * Creates an instance of a WigiiFixedBag supporting direct sql mapping.
	 * @return EPMSqlMapFixedBag
	 */
	protected function createSqlMapFixedBag() {
		return EPMSqlMapFixedBag::createInstance();
	}

	// ElementP mapper implementation

	/**
	 * Flushes the internal buffer into an ElementPList.
	 */
	public function flush($elementPList)
	{
		if(is_null($elementPList)) throw new ElementServiceException('elementPList cannot be null', ElementServiceException::INVALID_ARGUMENT);

		// injects the fixed wigii bag into the wigii bag
		if($this->isWigiiBagBulkLoadable) {
			if(isset($this->recordStructureFactory)) $this->configureFixedBag();
			else $this->injectFixedBagIntoWigiiBag();
		}

		// flushes fieldList (CWE 21.06.2018: except if FieldList is not shared and per element)
		if(!isset($this->recordStructureFactory)) $this->flushFieldList();
		$hasCalculatedFields = (!is_null($this->elementEvaluator) && ($this->calculatedFields->count() > 0));

		// prepares elementPList eval context
		if($hasCalculatedFields) {
			$eltEvalCtx = $this->getElementPListEvalContext();
			$eltEvalCtx->setElementPListSize($this->count());
		}

		// flushes each elementP in buffer
		$this->manualPaging_globalNb = 0;

		foreach($this->elementPBuffer as $elementP)
		{
			// CWE 21.06.2018 if FieldList and WigiiBag are not shared and per element, then fills FieldList and injects FixedBag if needed
			if(isset($this->recordStructureFactory)) {
				$element = $elementP->getElement();
				$this->debugLogger()->write("filling non shared FieldList and WigiiBag for element ".$element->getId());
				$this->flushFieldList($element);
				if($this->isWigiiBagBulkLoadable) $element->getWigiiBag()->setFixedBag($this->wigiiFixedBag, $element->getId());				
			}
			$this->manualPaging_globalNb++;
			// evaluates calculated fields only if we have at least read rights
			if($hasCalculatedFields && !is_null($elementP->getRights()))
			{
				// sets the evaluation context
				$this->elementEvaluator->setCalculatingOnFetch(true);
				$eltEvalCtx->setElementOrdinal($this->manualPaging_globalNb);

				//if outside of the manual paging range, the ElementP is defined as no rights to prevent extra calculation
				$skip = false;
				if($this->manualPaging_keepRightsInOutOfPage && $this->manualPaging_pageSize!= 0){
					if(	($this->manualPaging_globalNb <= (($this->manualPaging_desiredPage-1)*$this->manualPaging_pageSize)) ||
						($this->manualPaging_globalNb > ($this->manualPaging_desiredPage*$this->manualPaging_pageSize))
						){
						//do nothing
						$skip = true;
					}
				}
				if(!$skip){
					foreach($this->calculatedFields->getListIterator() as $field)
					{
						if($field->shouldCalculateOnFetch() || $field->shouldCalculateInListOnly()){
							$this->elementEvaluator->setElementPListEvalContext($eltEvalCtx);
							$this->elementEvaluator->evaluateElement($this->principal, $elementP->getDbEntity(), $field);
						}
					}
				}
			}

			$elementPList->addElementP($elementP);
		}
		$this->freeMemory();
	}

	/**
	 * Returns single ElementP
	 */
	public function getElementP()
	{
		$returnValue = reset($this->elementPBuffer);
		if($returnValue === false) throw new ElementServiceException("can not read ElementP in buffer", ElementServiceException::UNEXPECTED_ERROR);

		// injects the fixed wigii bag into the wigii bag
		if($this->isWigiiBagBulkLoadable) $this->injectFixedBagIntoWigiiBag();

		// flushes fieldList
		$this->flushFieldList();

		// evaluates calculated fields
		$hasCalculatedFields = (!is_null($this->elementEvaluator) && ($this->calculatedFields->count() > 0));
		if($hasCalculatedFields && !is_null($returnValue->getRights()))
		{
			// indicates the calculatingOnFetch context in the evaluator
			$this->elementEvaluator->setCalculatingOnFetch(true);

			foreach($this->calculatedFields->getListIterator() as $field)
			{
				if($field->shouldCalculateOnFetch() && !$field->shouldCalculateInListOnly()){
					$this->elementEvaluator->evaluateElement($this->principal, $returnValue->getDbEntity(), $field);
				}
			}
		}

		$this->freeMemory();
		return $returnValue;
	}

	/**
	 * fills field list according to fieldSelector list
	 */
	protected function flushFieldList($element=null)
	{
		if(isset($element)) $fieldList = $element->getFieldList();
		else $fieldList = $this->getFieldList();
		$fieldSelectorList = $this->getFieldSelectorListByFieldName();
		if(!isset($fieldSelectorList)) throw new ElementServiceException('fieldSelectorListByFieldName cannot be null', ElementServiceException::UNEXPECTED_ERROR);
		$freeTextFields = $this->getFreeTextFields();
		foreach($fieldSelectorList as $fName => $fs)
		{
			$field = $this->selectedFields[$fName];
			// do we have a freetext ?
			if(!isset($field))
			{
				if(isset($freeTextFields))
				{
					$field = $freeTextFields[$fName];
					if(!isset($field)) throw new ElementServiceException("field $fName was not fetched in database, ElementQueryPlanner bug", ElementServiceException::UNEXPECTED_ERROR);
				}
			}
			$fieldList->addField($field);

			// do we have a calculated field ?
			if($field->isCalculated())
			{
				$this->calculatedFields->addField($field);
			}
		}
	}

	/**
	 * Injects the wigii fixed bag into the wigii bag
	 * Precondition: $this->isWigiiBagBulkLoadable should be true.
	 */
	protected function injectFixedBagIntoWigiiBag() {
		// only strategy JOIN is implemented
		if($this->getTripod()->elementSqlBuilder->getQueryStrategy() == ElementQueryPlanner::QSTRATEGY_JOIN) {
			$this->configureFixedBag();
			$elementIds = array_keys($this->elementPBuffer);
			if(!empty($elementIds)) $elementIds = array_combine($elementIds, $elementIds);
			else $elementIds = null;
			$this->getWigiiBag()->setFixedBag($this->wigiiFixedBag, $elementIds);
		}
	}
	private function configureFixedBag() {
		// only strategy JOIN is implemented
		if($this->getTripod()->elementSqlBuilder->getQueryStrategy() == ElementQueryPlanner::QSTRATEGY_JOIN) {
			$this->wigiiFixedBag->setSqlMapping($this->sqlColMap, $this->sqlColMapField, $this->sqlColMapMultiSelect, $this->sqlColMapLang);
			$this->wigiiFixedBag->setSelectedFields($this->selectedFields);			
		}
	}
	
	// FieldList visitor implementation

	/**
	 * In case of multiple queries, deletes sql columns from previous query to be ready to accept new columns
	 */
	public function resetSelectedColForFieldList()
	{
		if(!$this->isWigiiBagBulkLoadable || !($this->wigiiFixedBag instanceof EPMSqlMapFixedBag)){
			$this->freeSqlQueryDef();
		}
	}

	/**
	 * Informs the ElementPMapper of a new column in the SQL SELECT query and gives information about its mapping to a field.
	 * sqlSelectColName: SQL column name in the result set
	 * field, dataType, subFieldName, subFieldType, sqlType, lang : mapping information between the result column and the final object.
	 */
	public function selectColForFieldList($sqlSelectColName, $field, $dataType, $subFieldName, $subFieldType, $sqlType, $lang=null)
	{
		$fName = $field->getFieldName();
		$this->selectedFields[$fName] = $field;
		$this->sqlColSubFieldName[$sqlSelectColName] = $subFieldName;
		$this->sqlColSubFieldType[$sqlSelectColName] = $subFieldType;
		$this->sqlColSqlType[$sqlSelectColName] = $sqlType;
		$sqlSelectColNameForMap = str_replace("`", "", $sqlSelectColName);
		$this->sqlColMap[$fName."_".$subFieldName] = $sqlSelectColNameForMap;
		$this->sqlColMapField[$fName][$subFieldName] = $sqlSelectColNameForMap;
		if(strtolower($subFieldType) === "multiple-select"){
			$this->sqlColMapMultiSelect[$fName."_".$subFieldName] = $sqlSelectColNameForMap;
		}
		if(isset($lang)){
			$this->sqlColLang[$sqlSelectColName] = $lang;
			$this->sqlColMapLang[$fName."_".$subFieldName][$sqlSelectColNameForMap] = $lang;
		}

		if($this->getTripod()->elementSqlBuilder->getQueryStrategy() === ElementQueryPlanner::QSTRATEGY_JOIN)
		{
			$this->sqlColFieldName[$sqlSelectColName] = $fName;
		}
	}

	public function actOnField($field, $dataType)
	{
		$this->selectedFields[$field->getFieldName()] = $field;
	}

	// RowList implementation to treat database records

	public function addRow($row)
	{
		$this->manualPaging_globalNb++;

//		$this->debugLogger()->write("start add row ".$this->manualPaging_globalNb);
		$elementId = $this->extractElementP($row);
//		$this->debugLogger()->write("elementP is extracted");
		// checks that we have at least read rights before extracting values
		$elementP = $this->elementPBuffer[$elementId];
		if(isset($elementP))
		{
			if(!is_null($elementP->getRights()))
			{
				//if outside of the manual paging range, the ElementP is defined as no rights to prevent extra calculation
				if($this->manualPaging_keepRightsInOutOfPage && $this->manualPaging_pageSize!= 0){
					if(	($this->manualPaging_globalNb <= (($this->manualPaging_desiredPage-1)*$this->manualPaging_pageSize)) ||
						($this->manualPaging_globalNb > ($this->manualPaging_desiredPage*$this->manualPaging_pageSize))
						){
						//do nothing
						return;
					}
				}
				$this->extractFieldValues($elementId, $row);
//				$this->debugLogger()->write("element fields are extracted");
			}
		}
	}

	//if manual paging then the extractElementP is making no rights on element outside of the range
	private $manualPaging_desiredPage = null;
	private $manualPaging_pageSize = null;
	private $manualPaging_globalNb = null;
	private $manualPaging_keepRightsInOutOfPage = null;
	//if keepRightsInOutOfPage then elemenP which are outside of the range will still have the PRights but no values
	public function setManualPaging($desiredPage, $pageSize, $keepRightsInOutOfPage=false){
		if(!$desiredPage) $this->manualPaging_desiredPage = 1;
		else $this->manualPaging_desiredPage = $desiredPage;
		$this->manualPaging_pageSize = $pageSize;
		$this->manualPaging_globalNb = 0;
		$this->manualPaging_keepRightsInOutOfPage = $keepRightsInOutOfPage;
	}

	/**
	 * extract ElementP from row and returns elementId
	 */
	protected function extractElementP($row)
	{
		//eput($row);
		// 1. fetches elementId
		$eP = $this->getElementPrefix();
		$elementId = $row[$eP.'id'];
		if(!isset($elementId)) throw new ElementServiceException('invalid sql, element id column is missing', ElementServiceException::UNEXPECTED_ERROR);
		$elementP = $this->elementPBuffer[$elementId];

		// 2. creates ElementP if does not exist
		if(!isset($elementP))
		{
			//if outside of the manual paging range, the ElementP is defined as no rights to prevent extra calculation
			if(!$this->manualPaging_keepRightsInOutOfPage && $this->manualPaging_pageSize!= 0){
				if(	($this->manualPaging_globalNb <= (($this->manualPaging_desiredPage-1)*$this->manualPaging_pageSize)) ||
					($this->manualPaging_globalNb > ($this->manualPaging_desiredPage*$this->manualPaging_pageSize))
					){
					//create a basic elementP
					$elementP = ElementP::createInstance(Element::createInstance(Module::createInstance()->setModuleName($row[$eP.'module']))->setId($row[$eP.'id']));
					$this->elementPBuffer[$elementId] = $elementP;
					//fput("skip $elementId");
					return $elementId;
				}
			}

			// CWE 21.06.2018 - if recordStructureFactory is active, then creates a fresh instance of WigiiBag and FieldList per element.
			if(isset($this->recordStructureFactory)) {
				$this->debugLogger()->write('creating one instance of FieldList and WigiiBag per element');
				$this->fieldList = $this->recordStructureFactory->createFieldList();
				$this->wigiiBag = $this->recordStructureFactory->createWigiiBag();
				if($this->fieldList instanceof FormFieldList) $this->fieldList->setFormBag($this->wigiiBag);
			}
			$elementP = ElementP::createInstance($this->getTripod()->elementServiceImpl->createElementInstanceFromRow($this->principal, $row,
													$eP, $this->getFieldList(), $this->getWigiiBag()));
			$this->elementPBuffer[$elementId] = $elementP;
			$this->pRightsNotSet = true;
		}

		// 3. creates rights if not yet defined
		if($this->pRightsNotSet)
		{
			if(isset($this->pRights))
			{
				$elementP->setRights($this->pRights);
			}
			else
			{
				$elementP->setRightsFromDB($this->principal, $row, $eP);
			}
			$this->pRightsNotSet = false;
		}

		return $elementId;
	}
	/**
	 * extract Field values from row and fill wigii bag
	 */
	protected function extractFieldValues($elementId, $row)
	{
		// 4. fetches fields
//		$this->debugLogger()->write("start extract field value");
		$eltSqlB = $this->getTripod()->elementSqlBuilder;
		switch($eltSqlB->getQueryStrategy())
		{
			// 4.join 		foreach colName in sqlColFieldName, fill wigiiBag
			case ElementQueryPlanner::QSTRATEGY_JOIN:
				//this can be unset if no field is given
				if(isset($this->sqlColFieldName)){
					/*
					 * since 01/05/2014 an optimization is done here
					 * The time to fill the wigiiBag in a standard way is dependent of the number of fields and subfields
					 * The new approcah is to fill the wigiiBag directly with the portions received from the database
					 * without doing it column by column. The result of this optimization is approximately 10 times faster
					 * per element filled.
					 */
					if($this->isWigiiBagBulkLoadable) {
						$this->wigiiFixedBag->addRow($elementId, $row);
					//fills WigiiBag in the standard way
					} else {
// 						 $GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."add row"] = microtime(true);
						foreach($this->sqlColFieldName as $sqlCol => $fName)
						{
//							$this->debugLogger()->write("select field ".$fName);
							$field = $this->selectedFields[$fName];
							if(!isset($field)) throw new ElementServiceException("field $fName was not selected by query planner", ElementServiceException::UNEXPECTED_ERROR);
							$dataTypeName = $field->getDataType();
							if(!isset($dataTypeName)) throw new ElementServiceException("field $fName dataType cannot be null", ElementServiceException::UNEXPECTED_ERROR);
							$dataTypeName = $dataTypeName->getDataTypeName();
							$subFieldName = $this->sqlColSubFieldName[$sqlCol];
							$subFieldType = $this->sqlColSubFieldType[$sqlCol];
							$sqlType = $this->sqlColSqlType[$sqlCol];
							$lang = $this->sqlColLang[$sqlCol];

							$sqlCol = str_replace("`", "", $sqlCol);
							if(!array_key_exists($sqlCol, $row)) throw new ElementServiceException("invalid sql, field column $sqlCol is missing", ElementServiceException::UNEXPECTED_ERROR);
							$val = $row[$sqlCol];

							$this->fillWigiiBag($elementId, $fName, $subFieldName, $dataTypeName, $val, $subFieldType, $sqlType, $lang);
						}
					}
				}
				break;
			// 4.datatype 	extract field, then foreach colName in sqlColSubFieldName, fill wigiiBag
			case ElementQueryPlanner::QSTRATEGY_DATATYPE:
				$fsqlCol = $eltSqlB->getFieldPrefix().'field';
				if(!array_key_exists($fsqlCol, $row)) throw new ElementServiceException('invalid sql, field column is missing', ElementServiceException::UNEXPECTED_ERROR);
				$fName = $row[$fsqlCol];
				// if fields have values
				if(isset($fName))
				{
					$field = $this->selectedFields[$fName];
					if(!isset($field)) throw new ElementServiceException("field $fName was not selected by query planner", ElementServiceException::UNEXPECTED_ERROR);
					$dataType = $field->getDataType();
					if(!isset($dataType)) throw new ElementServiceException("field $fName dataType cannot be null", ElementServiceException::UNEXPECTED_ERROR);
					$dataTypeName = $dataType->getDataTypeName();
					foreach($this->sqlColSubFieldName as $sqlCol => $subFieldName)
					{
						$subFieldType = $this->sqlColSubFieldType[$sqlCol];
						$sqlType = $this->sqlColSqlType[$sqlCol];
						$lang = $this->sqlColLang[$sqlCol];

						$sqlCol = str_replace("`", "", $sqlCol);
						if(!array_key_exists($sqlCol, $row)) throw new ElementServiceException("invalid sql, field column $sqlCol is missing", ElementServiceException::UNEXPECTED_ERROR);
						$val = $row[$sqlCol];

						$this->fillWigiiBag($elementId, $fName, $subFieldName, $dataTypeName, $val, $subFieldType, $sqlType, $lang);
					}
				}
				break;
			default: throw new ElementServiceException('unsupported strategy', ElementServiceException::UNSUPPORTED_OPERATION);
		}
	}

	/**
	 * (extension point)
	 * Formats value and fills wigii bag
	 */
	protected function fillWigiiBag($elementId, $fieldName, $subFieldName, $dataTypeName, $value, $subFieldType, $sqlType, $lang)
	{
//		$this->debugLogger()->write("start fillWigiiBag");
		$wigiiBag = $this->getWigiiBag();
//		$this->debugLogger()->write("wigii bag fetched");
		// if multilanguage, updates array of multilanguage values
		if(isset($lang))
		{
			$multiLangVal = $wigiiBag->getValue($elementId, $dataTypeName, $fieldName, $subFieldName);
//			$this->debugLogger()->write("value has been fetched");
			if(!isset($multiLangVal)) $multiLangVal = array();
			$multiLangVal[$lang] = $this->formatValue($value, $subFieldType, $sqlType);
			$wigiiBag->setValue($multiLangVal, $elementId, $dataTypeName, $fieldName, $subFieldName);
//			$this->debugLogger()->write("value has been set");
		}
		// stores value
		else
		{
			$wigiiBag->setValue($this->formatValue($value, $subFieldType, $sqlType), $elementId, $dataTypeName, $fieldName, $subFieldName);
//			$this->debugLogger()->write("value has been set");
		}
	}
	/**
	 * (extension point)
	 * Formats value according to sqlType and Wigii subFieldType
	 */
	protected function formatValue($value, $subFieldType, $sqlType)
	{
//		$this->debugLogger()->write("start formatValue");
		// formats boolean
		if($sqlType === MySqlQueryBuilder::SQLTYPE_BOOLEAN)
		{
			if($value == "0" ||
				$value == null ||
				$value === 0 ||
				$value === "NULL" ||
				$value === "null" ||
				$value === "FALSE" ||
				$value === "false" ||
				$value === false) return false;
			else return true;
		}
		// formats multiple-select
		elseif(strtolower($subFieldType) === "multiple-select")
		{
			if(isset($value) && ($value !== "NULL")){
				$tempValues = explode('XAGU___XAGU', $value);
				return array_combine($tempValues, $tempValues);
			} else return null;
		}
		// else only formats NULL to real null
		elseif($value === "NULL" || $value === "null")
		{
			return null;
		}
		else return $value;
	}

	public function getListIterator()
	{
		return $this->elementPBuffer;
	}
	public function isEmpty()
	{
		return (count($this->elementPBuffer) == 0);
	}
	public function count()
	{
		return count($this->elementPBuffer);
	}

}
/**
 * ElementPMapper Sql Mapping Wigii Fixed Bag.
 * WigiiFixedBag used by the ElementPMapper to optimize the loading of an element
 * or a list of elements when using the JOIN strategy.
 * Created by CWE and LWR on 27 mai 2014
 */
class EPMSqlMapFixedBag implements WigiiFixedBag {
	private $_debugLogger;
	private $sqlRows;
	private $sqlColMap = array();
	private $sqlColMapField = array();
	private $sqlColMapMultiSelect = array();
	private $sqlColMapLang = array();
	private $selectedFields = array();
	private $langCache;
	private $multiSelectCache;

	// Object lifecycle

	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->debugLogger()->write('WigiiFixedBag instance created');
		return $returnValue;
	}

	// Dependency injection

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("EPMSqlMapFixedBag");
		}
		return $this->_debugLogger;
	}

	// Configuration

	/**
	 * Sets the mapping in order to be able to retrieve data when filling the wigiiBag through the addRow method
	 * @param Array $sqlColMap array(fieldName_subFielName=>sqlColName)
	 * @param Array $sqlColMapField array(fieldName=>array(subFieldName=>sqlColName))
	 * @param Array $sqlColMapMultiSelect array(fieldName_subFielName=>sqlColName)
	 * 	only for columns that have the multi-select subFieldType
	 * 	in this case the values are splitted with XAGU_XAGU
	 * @param Array $sqlColMapLang array(fieldName_subFielName=>array(sqlColName=>lang))
	 * 	only for columns that are multi lang
	 * 	in this case we return a slice of the row containg the different values for each language
	 */
	public function setSqlMapping($sqlColMap, $sqlColMapField, $sqlColMapMultiSelect, $sqlColMapLang) {
		if($this->sqlColMap) $this->sqlColMap = array_merge($this->sqlColMap, $sqlColMap);
		else $this->sqlColMap = $sqlColMap;
		if($this->sqlColMapField) $this->sqlColMapField = array_merge($this->sqlColMapField, $sqlColMapField);
		else $this->sqlColMapField = $sqlColMapField;
		if($this->sqlColMapMultiSelect) $this->sqlColMapMultiSelect = array_merge($this->sqlColMapMultiSelect, $sqlColMapMultiSelect);
		else $this->sqlColMapMultiSelect = $sqlColMapMultiSelect;
		if($this->sqlColMapLang) $this->sqlColMapLang = array_merge($this->sqlColMapLang, $sqlColMapLang);
		else $this->sqlColMapLang = $sqlColMapLang;
	}

	/**
	 * Sets the array of selected fields
	 * @param Array $fields array(fieldName=>Field)
	 */
	public function setSelectedFields($fields) {
		if($this->selectedFields) $this->selectedFields = array_merge($this->selectedFields, $fields);
		else $this->selectedFields = $fields;
	}

	// Implementation

	/**
	 * Fills the fixed bag by adding a row from the database
	 */
	public function addRow($elementId, $row){
		if(isset($this->sqlRows[$elementId])){
			//if already exist, then extend the columns by merging the row
			$this->sqlRows[$elementId] = array_merge($this->sqlRows[$elementId], $row);
		} else {
			$this->sqlRows[$elementId] = $row;
		}
		//$this->debugLogger()->write("add row");
	}

	// Wigii fixed bag implementation

	public function getValue($elementId, $fieldName, $subFieldName=null) {
		if(is_null($subFieldName)) $subFieldName = "value";

		$index = $fieldName."_".$subFieldName;
		$sqlCol = $this->sqlColMap[$index];
		$lang = $this->sqlColMapLang[$index];

		//$this->debugLogger()->write("getValue ".$elementId." $fieldName $subFieldName sqlCol: $sqlCol index: $index lang: $lang");

		if($this->sqlColMapMultiSelect[$index]){
			if(!isset($this->multiSelectCache)) $this->multiSelectCache = array();
			if(!$this->multiSelectCache[$elementId.$index]){
				$tempValues = explode('XAGU___XAGU', $this->sqlRows[$elementId][$sqlCol]);
				$this->multiSelectCache[$elementId.$index] = array_combine($tempValues, $tempValues);
			}
			//$this->debugLogger()->write("multiSelectCache: ".$this->multiSelectCache[$elementId.$index]);
			return $this->multiSelectCache[$elementId.$index];
		}
		if($lang){
			if(!isset($this->langCache)) $this->langCache = array();
			if(!$this->langCache[$elementId.$index]){
				$this->langCache[$elementId.$index] = array_combine($lang, array_intersect_key($this->sqlRows[$elementId], $lang));
			}
			//$this->debugLogger()->write("langCache: ".$this->langCache[$elementId.$index]);
			return $this->langCache[$elementId.$index];
		}
		//$this->debugLogger()->write("getValue=".$this->sqlRows[$elementId][$sqlCol]);
		return $this->sqlRows[$elementId][$sqlCol];
	}

	public function copyIntoWigiiBag($wigiiBag) {
		if(!isset($wigiiBag)) throw new RecordException('wigiiBag cannot be null', RecordException::INVALID_ARGUMENT);
		if(isset($this->sqlRows)) {
			// foreach element
			foreach(array_keys($this->sqlRows) as $eltId) {
				// foreach field
				foreach($this->sqlColMapField as $fieldName=>$subFields) {
					$dataTypeName = $this->selectedFields[$fieldName]->getDataType()->getDataTypeName();
					// foreach subfield
					foreach($subFields as $subFieldName=>$sqlCol) {
						// copies value into wigii bag
						$wigiiBag->setValue($this->getValue($eltId, $fieldName, $subFieldName),
								$eltId, $dataTypeName, $fieldName, $subFieldName);
					}
				}
			}
		}
	}
}