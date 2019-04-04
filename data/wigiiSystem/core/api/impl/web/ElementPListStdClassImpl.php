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
 * ElementPList StdClass impl
 * Created by CWE on 19.02.2018
 */
class ElementPListStdClassImpl extends ElementPGroupableSelectablePagedListImpl implements ElementPList {

	private $_debugLogger;
	private $stdClass;
	private $first;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementPListStdClassImpl");
		}
		return $this->_debugLogger;
	}
	
	private $configS;
	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	public function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getWigiiBPL()->getConfigService();
		}
		return $this->configS;
	}
	
	private $transS;
	public function setTranslationService($translationService)
	{
		$this->transS= $translationService;
	}
	protected function getTranslationService()
	{
		// autowired
		if(!isset($this->transS))
		{
			$this->transS= ServiceProvider::getTranslationService();
		}
		return $this->transS;
	}
	
	// Object lifecycle
	
	public static function createInstance($wigiiExecutor, $listContext)
	{
		$returnValue = new self();
		$returnValue->reset($wigiiExecutor, $listContext);
		return $returnValue;
	}
	public function reset($wigiiExecutor, $listContext)
	{
		$this->freeMemory();
		parent::reset($wigiiExecutor, $listContext);
		$this->stdClass = (object)array("elementList"=>null,"count"=>0);
		$this->first = true;
	}
	public function freeMemory()
	{
		unset($this->stdClass);
	}
	
	// Configuration
	
	/**
	 * Configures this ElementPList to be used in the given Module Activity, in the scope of the current request
	 * @param Principal $p current principal performing the operation
	 * @param ExecutionService $exec Current request ExecutionService instance
	 * @param Activity $activity Defined activity
	 * @param WigiiBPLParameter $options a set of options to configure the stdClass model, like:
	 * - language: String. The language in which to translate the labels and content returned into the stdClass model. Defaults to current language.
	 * - includeMultiLanguageFields: Boolean. If true, then multilanguage content (fields of type Varchars or Texts) are returned in all selected languages and not translated.
	 * - includeElementDetail: Boolean. If true, then includes element detail into StdClass model. Defaults to true.
	 * - includeFieldSysInfo: Boolean. If true, then includes sys info attributes for each field. Defaults to false.
	 * - includeFieldList: Boolean. If true, then includes the FieldList into StdClass model. Defaults to true.
	 * - includeFieldCfgAttributs: Boolean. If true, then Fields of type Attributs or MultipleAttributs get the content of the drop-down fetched. Default to false.
	 * - includeFileTextContent: Boolean. If true, then content of Files of type Text or HTML is retrieved, else content is ignored. Default to false.
	 * - resetGroupBy: Boolean. If true, the current group by is reset and ListContext is updated consequently.
	 * - doOnlyRows: Boolean. If true then the ElementPList structure is not needed, but only the content. Typically when displaying a next page into a paged view.
	 * - doOnlyRowsContent: Boolean. If true, indicates that only the row content should be sent, not several rows.
	 * @return ListContext the updated ListContext object that should be used to fetch the data. 
	 * Additional FieldSelectors can be added as needed.
	 */
	public function configureForActivity($p,$exec,$activity,$options=null) {
		if(!isset($activity)) throw new ServiceException('activity cannot be null',ServiceException::INVALID_ARGUMENT);
		
		// Extracts and set options
		if(!isset($options)) $options = WigiiBPLParameterArrayImpl::createInstance();
		$listContext = $this->getListContext();
		$wigiiExecutor = $this->getWigiiExecutor();
		$module = $exec->getCrtModule();
		$configS = $this->getConfigService();
		$activityXml = $configS->ma($p, $module, $activity);
		
		// do only rows options for ElementPGroupableSelectablePagedListImpl
		$doOnlyRows = $options->getValue('doOnlyRows');
		$doOnlyRowsContent = $options->getValue('doOnlyRowsContent');
		
		// language
		$language = $options->getValue('language');
		if(!isset($language)) $language = $this->getTranslationService()->getLanguage();
		$this->setLanguage($language);
		
		// multilanguage fields
		$multiLanguageFields = $options->getValue('includeMultiLanguageFields');
		if(isset($multiLanguageFields)) $this->setIncludeMultiLanguageFields($multiLanguageFields);
		else $multiLanguageFields = $this->getIncludeMultiLanguageFields();
		
		// element detail
		$elementDetail = $options->getValue('includeElementDetail');
		if(isset($elementDetail)) $this->setIncludeElementDetail($elementDetail);
		else $elementDetail = $this->getIncludeElementDetail();
		
		// sys info
		$includeSysInfo = $options->getValue('includeFieldSysInfo');
		if(isset($includeSysInfo)) $this->setIncludeFieldSysInfo($includeSysInfo);
		else $includeSysInfo = $this->getIncludeFieldSysInfo();
		
		// field list
		$includeFieldList = $options->getValue('includeFieldList');		
		if(isset($includeFieldList)) $this->setIncludeFieldList($includeFieldList);
		elseif(isset($doOnlyRows)) $this->setIncludeFieldList(!$doOnlyRows);
		else $includeFieldList = $this->getIncludeFieldList();
		
		// Attributs and MultipleAttributs content
		$includeCfgAttributs = $options->getValue('includeFieldCfgAttributs');
		if(isset($includeCfgAttributs)) $this->setIncludeFieldCfgAttributs($includeCfgAttributs);
		else $includeCfgAttributs = $this->getIncludeFieldCfgAttributs();
		
		// Files text content
		$includeFileTextContent = $options->getValue('includeFileTextContent');
		if(isset($includeFileTextContent)) $this->setIncludeFileTextContent($includeFileTextContent);
		else $includeFileTextContent = $this->getIncludeFileTextContent();
		
		// Creates FieldSelectorList for Activity
		$fsl = FieldSelectorListForActivity::createInstance(false,$includeSysInfo,true);
		if(!$multiLanguageFields) $fsl->setSelectedLanguages(array($language=>$language));
		
		// Fills FieldSelectorList based on Activity fields and maps to underlying Element Fields
		$configS->getFields($p, $module, $activity, $fsl);
		
		// Maps element fields to activity fields
		$this->setFieldMapping($this->createFieldMapping($fsl));
		
		// Autocompletes FieldSelectorList with dependent calculates fields and required subfields
		$fieldList = $this->autocompleteFieldSelectorList($p, $fsl, $module, $listContext);
		
		// Configures fieldselector list
		$this->setFieldSelectorList($fsl);
		$listContext->setFieldSelectorList($fsl);
		
		// Configures group by and sort by in ListContext
		if($options->getValue('resetGroupBy')) $listContext->setGroupBy("reset");
		$listContext->addGroupByFieldSelector($fieldList);
		$listContext->addSortByFieldSelector($fieldList);
		
		// Recalculates search bar
		if(!$doOnlyRows) {
			$listContext->recalculateSearchBar($p, $wigiiExecutor);
		}
		
		// Forces fetch of children groups if includeChildrenGroupsOnSelect=1 in activity
		if($activityXml['includeChildrenGroupsOnSelect']=="1") {
			$listContext->setGroupPList($listContext->getGroupPList(), true);
		}
		
		// Configures the parent list with updated ListContext and options
		$this->refreshGroupByAndSortByInfo();
		$this->setFieldList($fieldList);
		$this->setP($p);
		$this->setExec($exec);
		$this->setElementPolicyEvaluator($wigiiExecutor->getElementPolicyEvaluator($p, $module));
		if($doOnlyRows) $this->doOnlyRows($doOnlyRowsContent);
		
		// Prepares list context for filter on duplicates (if needed)
		$wigiiExecutor->prepareListContextForDuplicates($listContext, $p, $exec);
		
		return $listContext;
	}

	private $fieldSelectorList;
	/**
	 * Optionally informs this ElementPList of the FieldSelectorList that will be used to fetch the data.
	 * If method configureForActivity has been called, then a FieldSelectorList has already been calculated.
	 * @param FieldSelectorList $fieldSelectorList
	 */
	public function setFieldSelectorList($fieldSelectorList) {
		$this->fieldSelectorList = $fieldSelectorList;
	}
	/**
	 * @return FieldSelectorList
	 */
	public function getFieldSelectorList() {
		return $this->fieldSelectorList;
	}
	
	private $language;
	/**
	 * Sets the language in which to get the labels and the content.
	 * If not defined, takes current user language.
	 * If method configureForActivity has been called, then a language mapping has already been set.
	 * @param String $lang an installed language code like l01, l02, etc.
	 */
	public function setLanguage($lang) {
		$this->language = $lang;
	}
	/**	 
	 * @return String language code
	 */
	public function getLanguage() {
		return $this->language;
	}
	
	private $fieldMapping;
	/**
	 * Sets a mapping table which re-maps element fields to other field names.
	 * If method configureForActivity has been called, then a field mapping has already been calculated.
	 * @param CalculatedFieldSelectorMap $fieldRemap a cfsMap instance which associates Element FieldSelector to StdClass FieldSelector.
	 */
	public function setFieldMapping($fieldRemap) {
		$this->fieldMapping = $fieldRemap;	
	}
	/**	 
	 * @return CalculatedFieldSelectorMap
	 */
	public function getFieldMapping() {
		return $this->fieldMapping;
	}
	
	private $includeElementDetail = true;
	/**
	 * If true, then includes element detail into StdClass model. Defaults to true.
	 * The element detail will include element ID, principal rights, and other attributes as defined in the FieldSelectorList
	 */
	public function setIncludeElementDetail($bool) {
		$this->includeElementDetail = ($bool==true);
	}
	protected function getIncludeElementDetail() {
		return $this->includeElementDetail;
	}
	
	private $includeFieldSysInfo = false;
	/**
	 * If true, then includes sys info attributes for each field. Defaults to false.
	 */
	public function setIncludeFieldSysInfo($bool) {
		$this->includeFieldSysInfo= ($bool==true);
	}
	protected function getIncludeFieldSysInfo() {
		return $this->includeFieldSysInfo;
	}
	
	private $includeFieldList = true;
	/**
	 * If true, then includes the FieldList into StdClass model. Defaults to true.
	 */
	public function setIncludeFieldList($bool) {
		$this->includeFieldList = ($bool==true);
	}
	protected function getIncludeFieldList() {
		return $this->includeFieldList;
	}
	
	private $includeFieldCfgAttributs = false;
	/**
	 * If true, then Fields of type Attributs or MultipleAttributs get the content of the drop-down fetched. Default to false.
	 * This option only takes effect if includeFieldList is true.
	 */
	public function setIncludeFieldCfgAttributs($bool) {
		$this->includeFieldCfgAttributs= ($bool==true);
	}
	protected function getIncludeFieldCfgAttributs() {
		return $this->includeFieldCfgAttributs;
	}
	
	private $includeMultiLanguageFields = false;
	/**
	 * If true, then multilanguage content (fields of type Varchars or Texts) are returned in all selected languages,
	 * else only content in current language is returned. Defaults to false.
	 */
	public function setIncludeMultiLanguageFields($bool) {
		$this->includeMultiLanguageFields = ($bool==true);
	}
	protected function getIncludeMultiLanguageFields() {
		return $this->includeMultiLanguageFields;
	}
	
	private $includeFileTextContent = false;
	/**
	 * If true, then content of Files of type Text or HTML is retrieved, else content is ignored. Default to false.
	 */
	public function setIncludeFileTextContent($bool) {
		$this->includeFileTextContent= ($bool==true);
	}
	protected function getIncludeFileTextContent() {
		return $this->includeFileTextContent;
	}
	
	// ElementPList implementation
	
	public function doAddElementP($elementP){
		// on first incoming element, creates structure.
		if($this->first) {
			// fills the FieldList if required
			if($this->includeFieldList) {
				if(!isset($this->stdClass->fieldList)) $this->stdClass->fieldList = (object)array();
				$this->fillFieldList($elementP->getElement()->getFieldList(), $this->stdClass->fieldList);
			}
			// connects the WigiiBag stdClass to the elementList structure
			$bag = $elementP->getElement()->getWigiiBag();
			if(!($bag instanceof WigiiBagStdClassImpl)) throw new ServiceException("ElementPListStdClassImpl only supports WigiiBagStdClassImpl and not ".get_class($bag), ServiceException::INVALID_ARGUMENT);
			$this->stdClass->elementList = $bag->getStdClass();
			$this->first = false;	
		}
		// adds incoming element to list (to preserve order)
		if(is_null($this->stdClass->elementList)) $this->stdClass->elementList = (object)array();
		$elementId = $elementP->getElement()->getId();
		$key = 'elt_'.$elementId;
		$elementStdClass = $elementP->getElement()->getWigiiBag()->getStdClass()->{$key};
		if(!isset($elementStdClass)) $elementStdClass = (object)array();
		$this->stdClass->elementList->{$key} = $elementStdClass;
		
		// fills element detail if required
		if($this->includeElementDetail) {
			if(is_null($elementStdClass->__element)) $elementStdClass->__element = (object)array();
			$this->fillElementDetail($elementP, $elementStdClass->__element);
		}
		// increments counter
		$this->stdClass->count += 1;
	}

	public function createFieldList(){
		return FieldListArrayImpl::createInstance(true,true);
	}
	public function createWigiiBag(){
		$returnValue = WigiiBagStdClassImpl::createInstance();
		// sets language to flatten multi-language fields
		if(!$this->includeMultiLanguageFields) {
			$returnValue->setLanguage((isset($this->language)?$this->language:$this->getTranslationService()->getLanguage()));
		}
		if(isset($this->fieldMapping)) $returnValue->setFieldMapping($this->fieldMapping);
		return $returnValue;
	}

	/**
	 * @return StdClass returns the list of elements as an StdClass instance of the form 
	 * {
     *	* elt_elementId: {
     *		__element: { element static attributes + any dynamic attributes at element level },
     *		* fieldName: { dataType subfields + sys info subfields + any dynamic attribute at field level }
     * }
	 */
	public function getListIterator() {
		return $this->stdClass->elementList;
	}
	
	public function isEmpty() {
		return ($this->stdClass->count == 0);
	}
	
	public function count() {
		return $this->stdClass->count;
	}
	
	public function actOnFinishAddElementP($numberOfObjects){
		parent::actOnFinishAddElementP($numberOfObjects);
		$this->stdClass->{'totalNumberOfObjects'} = $numberOfObjects;
		$listContext = $this->getListContext();
		if($listContext->isPaged()) {
			$this->stdClass->{'pageSize'} = $listContext->getPageSize();
			$this->stdClass->{'pageNumber'} = $listContext->getDesiredPageNumber();
		}
	}
	
	// Accessors
	
	/**
	 * @return StdClass Returns this ElementPList as an StdClass instance of the form
	 * {
	 *	 0..1 fieldList: {
	 *	    * fieldName: {
	 *	      fieldName: String, name of the field,
	 *	      dataType: String, the Wigii DataType name of the field,
	 *	      label: String, a label for the end user (already translated),
	 *	      attributes: { any attributes defined in xml config file },
	 *	      0..1 (if dataType is Attributs or MultipleAttributs) cfgAttributs: {
	 *	        * value: {
	 *	          value: String|Number, the value of the drop-down attribute,
	 *	          label: String, a label for the end user (already translated),
	 *	          attributes: { an optional map of attributes }
	 *	        }
	 *	      },
	 *	      ... other dynamic attribute at field level
	 *	    }
	 *	  },
	 *	  elementList: {
	 *	    * elt_elementId: {
	 *	        __element: { element static attributes + any dynamic attributes at element level },
	 *	        * fieldName: { dataType subfields + sys info subfields + any dynamic attribute at field level }
	 *	    }
	 *	  },
	 *	  count: number of elements in the list,
	 *	  pageSize: the maximum number of elements returned in the page or undefined if no pagination,
	 *	  pageNumber: the actual page number, starts with 1, undefined if no pagination,
	 *	  totalNumberOfObjects: the total number of objects without pagination,
	 *	  ... other dynamic attributes at ModuleView level.
	 *	} 
	 */
	public function getStdClass() {
		return $this->stdClass;
	}
	
	/**
	 * Converts model to JSON string
	 * @return string
	 */
	public function toJson() {
		$returnValue = json_encode($this->stdClass,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES);
		if($returnValue === false) throw new ServiceException('JSON encode error '.json_last_error().' '.json_last_error_msg(), ServiceException::UNEXPECTED_ERROR);
		return $returnValue;
	}
	
	// Model implementation
	
	/**
	 * Fills the given Element as StdClass model based on the given ElementP
	 * @param ElementP $elementP current ElementP
	 * @param StdClass $elementStdClass stdClass instance representing the Element details to be filled using the configured FieldSelectorList
	 */
	protected function fillElementDetail($elementP,$elementStdClass) {
		$element = $elementP->getElement();
		$elementStdClass->{'id'} = $element->getId();
		$elementStdClass->{'sys_creationDate'} = $element->getSys_creationDate();
		$elementStdClass->{'sys_creationUser'} = $element->getSys_creationUser();
		$elementStdClass->{'sys_creationUsername'} = $element->getSys_creationUsername();
		$elementStdClass->{'sys_date'} = $element->getSys_date();
		$elementStdClass->{'sys_user'} = $element->getSys_user();
		$elementStdClass->{'sys_username'} = $element->getSys_username();
		if(!is_null($elementP->getRights())) $elementStdClass->{'pRights'} = $elementP->getRights()->getLetter();
		$elementStdClass->{'state'} = $element->getStateAsInt();
		$elementStdClass->{'enableState'} = $elementP->getEnableElementStateAsInt();
	}
	
	/**
	 * Fills the given FieldList as StdClass model based on the given FieldList
	 * @param FieldList $fieldList filled underyling Element FieldList
	 * @param StdClass $fieldListStdClass stdClass instance representing the FieldList details to be filled using the configured FieldSelectorList
	 */
	protected function fillFieldList($fieldList,$fieldListStdClass) {
		foreach($fieldList->getListIterator() as $field) {
			$fieldListStdClass->{$field->getFieldName()} = (object)array(
				'fieldName' => $field->getFieldName(),
				'dataType' => $field->getDataType()->getDataTypeName()
			);
		}
	}	
	
	/**
	 * Fills the given FieldSelectorList with required subfields and dependent calculated fields.
	 * @param Principal $p current Principal performing the operation
	 * @param FieldSelectorList $fieldSelectorList current FieldSelectorList beeing filled
	 * @param Module $module current ElementPList Module
	 * @param ListContext $listContext current ListContext scope
	 * @return FieldList returns the FieldList used to autocomplete the FieldSelectorList
	 */
	protected function autocompleteFieldSelectorList($p, $fieldSelectorList, $module, $listContext) {
		// gets module fields
		$fieldList = $this->createFieldList();
		$this->getConfigService()->getFields($p, $module, null, $fieldList);
		$groupByFieldName = $listContext->getGroupByItemFieldSelector();
		if(isset($groupByFieldName)) $groupByFieldName = $groupByFieldName->getFieldName();
		// autocomplete selected fields
		foreach($fieldList as $field) {
			$fieldName = $field->getFieldName();
			// gets FuncExp dependencies			
			if ($field->isCalculated() && $field->shouldCalculateOnFetch() && ($fieldSelectorList->containsField($fieldName) || $groupByFieldName == $fieldName)) {
				$field->getFuncExpDependencies($fieldSelectorList);
			}
			// autocompletes field
			if($fieldSelectorList->containsField($fieldName)) {
				$this->autocompleteField($p, $fieldSelectorList, $field);
			}
		}
		return $fieldList;
	}
	/**
	 * Fills the given FieldSelectorList with the needed subfields according to the given Field
	 * @param Principal $p current Principal performing the operation
	 * @param FieldSelectorList $fieldSelectorList current FieldSelectorList beeing filled
	 * @param Field $field element Field for which to add subfields.
	 */
	protected function autocompleteField($p, $fieldSelectorList, $field) {
		if(!isset($field)) throw new ServiceException('field cannot be null',ServiceException::INVALID_ARGUMENT);
		$dataType = $field->getDataType();
		$fieldName = $field->getFieldName();
		
		// TimeRanges
		if($dataType instanceof TimeRanges) {
			$fieldSelectorList->addFieldSelector($fieldName,'begDate');
			$fieldSelectorList->addFieldSelector($fieldName,'begTime');
			$fieldSelectorList->addFieldSelector($fieldName,'endDate');
			$fieldSelectorList->addFieldSelector($fieldName,'endTime');
			$fieldSelectorList->addFieldSelector($fieldName,'isAllDay');
		}
		// Addresses
		elseif($dataType instanceof Addresses) {
			$fieldSelectorList->addFieldSelector($fieldName,'street');
			$fieldSelectorList->addFieldSelector($fieldName,'zip_code');
			$fieldSelectorList->addFieldSelector($fieldName,'city');
			$fieldSelectorList->addFieldSelector($fieldName,'state');
			$fieldSelectorList->addFieldSelector($fieldName,'country');
		}
		// Files
		elseif($dataType instanceof Files) {
			$fieldSelectorList->addFieldSelector($fieldName,'name');
			$fieldSelectorList->addFieldSelector($fieldName,'type');
			$fieldSelectorList->addFieldSelector($fieldName,'size');
			$fieldSelectorList->addFieldSelector($fieldName,'mime');
			$fieldSelectorList->addFieldSelector($fieldName,'date');
			$fieldSelectorList->addFieldSelector($fieldName,'user');
			$fieldSelectorList->addFieldSelector($fieldName,'username');
			$fieldSelectorList->addFieldSelector($fieldName,'version');
			if($this->getIncludeFileTextContent()) $fieldSelectorList->addFieldSelector($fieldName,'textContent');			
		}
		// Addresses
		elseif($dataType instanceof Emails) {
			$fieldSelectorList->addFieldSelector($fieldName,'proofStatus');
			$fieldSelectorList->addFieldSelector($fieldName,'proofKey');
			$fieldSelectorList->addFieldSelector($fieldName,'proof');
			$fieldSelectorList->addFieldSelector($fieldName,'externalConfigGroup');
			$fieldSelectorList->addFieldSelector($fieldName,'externalAccessLevel');
			$fieldSelectorList->addFieldSelector($fieldName,'externalCode');
			$fieldSelectorList->addFieldSelector($fieldName,'externalAccessEndDate');
			$fieldSelectorList->addFieldSelector($fieldName,'value');
		}
		// Urls
		elseif($dataType instanceof Emails) {
			$fieldSelectorList->addFieldSelector($fieldName,'name');
			$fieldSelectorList->addFieldSelector($fieldName,'target');
			$fieldSelectorList->addFieldSelector($fieldName,'url');
		}
	}
	
	/**
	 * Creates a Field mapping table based on the given FieldSelectorList for Activity
	 * @param FieldSelectorListForActivity $fieldSelectorList
	 * @return CalculatedFieldSelectorMap a cfsMap instance which associates Element FieldSelector to StdClass FieldSelector.
	 */
	protected function createFieldMapping($fieldSelectorList) {
		if(!($fieldSelectorList instanceof FieldSelectorListForActivity)) throw new ServiceException('fieldSelectorList should be a non null instance of FieldSelectorListForActivity',ServiceException::INVALID_ARGUMENT);
		$returnValue = null;
		// iterates through the FieldSelectorList
		if(!$fieldSelectorList->isEmpty()) {
			$returnValue = CalculatedFieldSelectorMapArrayImpl::createInstance();
			foreach($fieldSelectorList->getListIterator() as $fs) {
				// extracts activity field name
				$activityFieldName = $fieldSelectorList->getXmlFromField($fs->getFieldName(),$fs->getSubFieldName());
				if(isset($activityFieldName)) {
					$activityFieldName = $activityFieldName->getName();
					// maps Element field to activity field
					$mapping = $returnValue->getFuncExp($fs);
					if(!isset($mapping)) $mapping = fs($activityFieldName);
					elseif(is_array($mapping)) $mapping[] = fs($activityFieldName);
					else $mapping = array($mapping,fs($activityFieldName));
					// in this case, the FuncExp is not a FuncExp but an array of FieldSelectors or a single FieldSelector.
					$returnValue->setCalculatedFieldSelector(cfs($fs,$mapping));
				}				
			}
		}
		return $returnValue;
	}
}
