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
 * Main HTML renderer helper class. Helps rendering Element Form and Detail, using Element XML configuration.
 * Created on 3 dec. 09 by LWR
 * Updated on 23 march 10 by LWR
 * Modified by Medair in 2016-2017 for maintenance purposes (see SVN log for details)
 */
class TemplateRecordManager extends Model {

	private $enableOutput;
	protected function isOutputEnabled(){ return $this->enableOutput; }
	public function setOutputEnabled($enable){ $this->enableOutput = $enable; return $this; }

	private $isForNotification;
	public function isForNotification(){ return $this->isForNotification; }
	public function setForNotification($enable){ $this->isForNotification = $enable; return $this; }

	private $isForPrint;
	public function isForPrint(){ return $this->isForPrint; }
	public function setForPrint($enable){ $this->isForPrint = $enable; return $this; }

	private $isForExternalAccess;
	public function isForExternalAccess(){ return $this->isForExternalAccess; }
	public function setForExternalAccess($enable){ $this->isForExternalAccess = $enable; return $this; }

	private $externalAccessLevel;
	public function getExternalAccessLevel(){ return $this->externalAccessLevel; }
	public function setExternalAccessLevel($var){ $this->externalAccessLevel = $var; }
	
	private $isForListView;
	public function isForListView(){ return $this->isForListView; }
	public function setForListView($enable){ $this->isForListView = $enable; return $this; }

	private $isForPreviewList;
	public function isForPreviewList(){ return $this->isForPreviewList; }
	public function setForPreviewList($enable){ $this->isForPreviewList = $enable; return $this; }

	private $isWorkzoneViewDocked;
	public function isWorkzoneViewDocked(){
		if($this->isWorkzoneViewDocked===null) {
			$formExec = $this->getFormRenderer();
			if(isset($formExec)) $formExec = $formExec->getFormExecutor();
			if(isset($formExec)) $this->isWorkzoneViewDocked = $formExec->isWorkzoneViewDocked();
		}
		return $this->isWorkzoneViewDocked; 
	}
	public function setWorkzoneViewDocked($enable){ $this->isWorkzoneViewDocked = $enable; return $this; }
	
	private $record;
	public function getRecord(){ return $this->record; }
	public function setRecord($record){
		$this->record = $record;
		if(isset($record) && $record->getAttachedRecord() && is_a($record->getAttachedRecord(), "Element") && $record->getAttachedRecord()->isSubElement()){
			$this->getTranslationService()->setSubExecutionModule($record->getAttachedRecord()->getModule());
		} else if(isset($record) && is_a($record, "Element") && $record->isSubElement()){
			$this->getTranslationService()->setSubExecutionModule($record->getModule());
		}
		else $this->getTranslationService()->resetSubExecutionModule();
		return $this;
	}

	private $formExecutor;
	public function getFormExecutor() {return $this->formExecutor;}
	public function setFormExecutor($formExecutor) {$this->formExecutor = $formExecutor;}
	
	private $_debugLogger;
	private function debugLogger() {
		if(!isset($this->_debugLogger)) $this->_debugLogger = DebugLogger::getInstance("TemplateRecordManager");
		return $this->_debugLogger;
	}
	private $_executionSink;
	private function executionSink() {
		if(!isset($this->_executionSink)) $this->_executionSink = ExecutionSink::getInstance("TemplateRecordManager");
		return $this->_executionSink;
	}
	public function __construct() {
		$this->debugLogger()->write("creating instance");
	}

	private $state; //can store the FormExecutor state, if defined
	public function setState($state){ $this->state = $state; }
	public function getState(){ return $this->state; }

	/*
	 * dependency Injection
	 */
	private $p;
	public function setP($p){ $this->p = $p; return $this; }
	protected function getP(){
		if(!isset($this->p)){
			$this->p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		}
		return $this->p;
	}
	private $translationService;
	public function setTranslationService($translationService){
		$this->translationService = $translationService;
		return $this;
	}
	protected function getTranslationService(){
		//autowired
		if(!isset($this->translationService)){
			$this->translationService = ServiceProvider::getTranslationService();
		}
		return $this->translationService;
	}
	//alias of t (translate) method of the Translation service
	public function t($text, $node=null){
		return $this->getTranslationService()->t($this->getP(), $text, $node);
	}
	public function h($text, $node=null){
		return $this->getTranslationService()->h($this->getP(), $text, $node);
	}
	private $executionService;
	public function setExecutionService($executionService){
		$this->executionService = $executionService;
		return $this;
	}
	protected function getExecutionService(){
		//autowired
		if(!isset($this->executionService)){
			$this->executionService = ServiceProvider::getExecutionService();
		}
		return $this->executionService;
	}
	public function addJsCode($code){
		$this->getExecutionService()->addJsCode($code);
	}
	private $configService;
	public function setConfigService($configService){
		$this->configService = $configService;
		return $this;
	}
	protected function getConfigService(){
		//autowired
		if(!isset($this->configService)){
			$this->configService = ServiceProvider::getConfigService();
		}
		return $this->configService;
	}
	private $elementService;
	public function setElementService($elementService){
		$this->elementService = $elementService;
		return $this;
	}
	protected function getElementService(){
		//autowired
		if(!isset($this->elementService)){
			$this->elementService = ServiceProvider::getElementService();
		}
		return $this->elementService;
	}
	private $sessionAdminService;
	public function setSessionAdminService($sessionAdminService){
		$this->sessionAdminService = $sessionAdminService;
		return $this;
	}
	protected function getSessionAdminService(){
		//autowired
		if(!isset($this->sessionAdminService)){
			$this->sessionAdminService = ServiceProvider::getSessionAdminService();
		}
		return $this->sessionAdminService;
	}
	private $moduleAS;
	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
		return $this;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}
	private $dflowS;
	public function setDataFlowService($dataFlowService)
	{
		$this->dflowS = $dataFlowService;
	}
	protected function getDataFlowService()
	{
		// autowired
		if(!isset($this->dflowS))
		{
			$this->dflowS = ServiceProvider::getDataFlowService();
		}
		return $this->dflowS;
	}
	
	/**
	 * Returns an instance of a FuncExpEvaluator configured for the context of the given Record.
	 * @param Principal $p principal executing the request
	 * @param Record $rec record for which to get an FuncExpEvaluator
	 * @return FuncExpEvaluator
	 */
	public function getFuncExpEvaluator($p, $rec) {
		if(!isset($rec)) throw new ServiceProviderException('record cannot be null', ServiceProviderException::INVALID_ARGUMENT);
		// extracts attached FormExecutor
		$formExec = $this->getFormRenderer();
		if(isset($formExec)) $formExec = $formExec->getFormExecutor();
		if(!isset($formExec)) $formExec = $this->getFormExecutor();	
		
		// gets RecordEvaluator
		if($rec instanceof Element) $evaluatorClassName = (string)$this->getConfigService()->getParameter($p, $rec->getModule(), "Element_evaluator");
		elseif($formExec instanceof DetailGroupFormExecutor && !is_null($formExec->getGroupP())) $evaluatorClassName = (string)$formExec->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $formExec->getGroupP()->getDbEntity()->getModule(), "Element_evaluator");
		else $evaluatorClassName = null;
		if(empty($evaluatorClassName)) $evaluatorClassName = (string)$this->getConfigService()->getParameter($p, $this->getExecutionService()->getCrtModule(), "Element_evaluator");
		$evaluator = ServiceProvider::getRecordEvaluator($p, $evaluatorClassName);
		// injects the context
		$evaluator->setContext($p, $rec);		
		if(isset($formExec)) $evaluator->setFormExecutor($formExec);
		// gets vm
		$returnValue = ServiceProvider::getFuncExpVM($p, $evaluator);
		$returnValue->setFreeParentEvaluatorOnFreeMemory(true);
		//$this->debugLogger()->write("instanciated FuncExpEvaluator of class ".get_class($returnValue));
		return $returnValue;
	}
	/**
	 * Evaluates a FuncExp in the context of the Principal and Record attached to the TRM.
	 * @param FuncExp|FieldSelector $fx the FuncExp to evaluate
	 * @return Any the FuncExp result
	 */
	public function evalfx($fx) {
		$fxEval=$this->getFuncExpEvaluator($this->getP(), $this->getRecord());
		$returnValue=null;
		try {
			$returnValue = $fxEval->evaluateFuncExp($fx);
			$fxEval->freeMemory();
		}
		catch(Exception $e) {
			$fxEval->freeMemory();
			throw $e;
		}
		return $returnValue;
	}
    /**
     * Evaluates a Configuration Parameter which can be either a constant or a FuncExp.
     * @param String $parameter the configuration parameter to evaluate
     * @return Any FuncExp result
     */
    public function evaluateConfigParameter($parameter) {
        return $this->getFormExecutor()->getWigiiExecutor()->evaluateConfigParameter($this->getP(),$this->getExecutionService(),$parameter,$this->getRecord());
    }
	public static function createInstance($isForNotification = false, $isForPrint=false, $isForExternalAccess=false, $isForListView=false, $isForPreviewList=false, $isOutputEnabled = true){
		$r = new TemplateRecordManager();
		$r->reset(null, $isForNotification, $isForPrint, $isForExternalAccess, $isForListView, $isForPreviewList, $isOutputEnabled);
		return $r;
	}

	private $fieldCache;
	public function resetFieldCache(){
		$this->fieldCache = null;
	}
	protected function isFieldCached($fieldName){
		if(!isset($this->fieldCache)) return false;
		return $this->fieldCache[$fieldName]!=null;
	}
	protected function cacheField($field){
		if(!isset($this->fieldCache)){
			$this->fieldCache = array();
		}
		$this->fieldCache[$field->getFieldName()] = $field;
	}
	protected function getField($fieldName){
		if(!$this->isFieldCached($fieldName)){
			$this->cacheField($this->getRecord()->getFieldList()->getField($fieldName));
		}
		return $this->fieldCache[$fieldName];
	}

	/**
	 * Reset the TRM (not the output buffer)
	 * if parameters are not passed, then the value of those parameter are not changed
	 * @param isForNotification = null, if null, then the current value remains
	 * @param isOutputEnabled = null, if null, then the current value remains
	 * @return this, to allow chaining
	 */
	public function reset($record=null, $isForNotification = null, $isForPrint=null, $isForExternalAccess=null, $isForListView=null, $isForPreviewList=null, $isOutputEnabled = null){
		if($record !== null) $this->setRecord($record);
		if($isForNotification !== null) $this->setForNotification($isForNotification);
		if($isForExternalAccess !== null) $this->setForExternalAccess($isForExternalAccess);
		if($isForPrint !== null) $this->setForPrint($isForPrint);
		if($isForListView !== null) $this->setForListView($isForListView);
		if($isForPreviewList !== null) $this->setForPreviewList($isForPreviewList);
		if($isOutputEnabled !== null) $this->setOutputEnabled($isOutputEnabled);
		$this->resetFieldCache();
		//by default always useHTMLPurifier on displaying html.
		//this can be disabled for some specific cases (example to diplay the preview of groupSubscriptions)
		$this->enableHTMLPurifier();
		return $this;
	}
	/**
	 * Clear the TRM
	 * each attribute is setted to null or default, all caches are emptied
	 * and cleaned
	 */
	public function clear(){
		$this->setRecord(null);
		$this->setForNotification(false);
		$this->setForExternalAccess(false);
		$this->setForPrint(false);
		$this->setForListView(false);
		$this->setForPreviewList(false);
		$this->setOutputEnabled(true);
		$this->resetFieldCache();
		//by default always useHTMLPurifier on displaying html.
		//this can be disabled for some specific cases (example to diplay the preview of groupSubscriptions)
		$this->enableHTMLPurifier();
		return $this->getHtmlAndClean();
	}

	private $useHTMLPurifier;
	public function enableHTMLPurifier(){
		$this->useHTMLPurifier = true;
	}
	public function disableHTMLPurifier(){
		$this->useHTMLPurifier = false;
	}
	protected function isHTMLPurifierEnabled(){
		return $this->useHTMLPurifier;
	}

	/**
	 * output/return an html form of the field
	 */
	public function displayForm($formId, $fieldName, $parentWidth = null, $labelWidth=null, $visibleLanguage = null){
		$field = $this->getField($fieldName);
		if($field->getDataType()!=null){
			include DATATYPE_PATH.$field->getDataType()->getDataTypeName()."_displayForm.tpl.php";
		} else {
			$this->displayNonDataTypeField($field);
		}
	}

	/**
	 * output/return an html string which represent an textual explanation of an empty value of the field
	 */
	public function displayEvenIfEmpty($fieldName, $parentWidth = null, $visibleLanguage = null){
		$field = $this->getField($fieldName);
		$fieldXml = $field->getXml();
		if($field->getDataType()!=null && $field->getDataType()->getDataTypeName()=="Files" && $fieldXml["displayLabel"]=="1"){
			$this->put('<font class="grayFont" >'.$this->t($fieldName, $field->getXml()).':</font><br />');
		}
		if($field->getDataType()!=null && ($field->getDataType()->getDataTypeName()=="Numerics" || $field->getDataType()->getDataTypeName()=="Floats")){
			$this->emptyNumber();
		} else {
			$this->emptyContent();
		}
	}
	public function emptyNumber(){
		$this->put('<font style="font-weight:normal;color:#aaa;">-</font>');
	}
	public function emptyContent(){
		$this->put('<font style="font-weight:normal;color:#aaa;">'.$this->t("displayEvenIfEmpty").'</font>');
	}
	/**
	 * output/return an html string which represent the value of the field
	 */
	public function displayValue($fieldName, $parentWidth = null, $visibleLanguage = null){
		if(!is_array($visibleLanguage)) $visibleLanguage = $this->getTranslationService()->getVisibleLanguage();
		else $visibleLanguage = array_intersect_key($this->getTranslationService()->getVisibleLanguage(), $visibleLanguage);
		
		$field = $this->getField($fieldName);
		if($field->getDataType()!=null){
			include DATATYPE_PATH.$field->getDataType()->getDataTypeName()."_displayValue.tpl.php";
		} else {
			$this->displayNonDataTypeField($field);
		}
	}

	protected function displayNonDataTypeField($field){
		if($field->getDataType()!=null) throw new ServiceException("try to display a field which should not have any DataType, and a DataType ".$field->getDataType()->getDataTypeName()." is found.", ServiceException::INVALID_ARGUMENT);
		$fieldXml = $field->getXml();
		$style = null;
		if(isset($fieldXml["align"])){
			$style .= "text-align:".$fieldXml["align"].";";
		}
		if($fieldXml["displayAsTitle"]=="1"){
			$this->put('<h4 style="'.$style.'" >');
		} elseif($style != null) {
			$this->put('<div style="'.$style.'" >');
		}

		if($fieldXml["needTranslation"]=="1"){
			//eput($fieldXml->asXML());
			$this->put($this->t((string)$fieldXml, $fieldXml));
		} else {
			$freeText = $field->getXml()->asXML();
			$freeText = str_replace('<?xml version="1.0"?>', "", $freeText);
			$freeText = str_replace("<".$field->getXml()->getName(). "", "", $freeText);
			$freeText = str_replace("</".$field->getXml()->getName(). ">", "", $freeText);
			$pos = strpos($freeText, ">"); //remove everything from begining until the next > (including the >)
			if($pos!==false) $freeText = substr($freeText, $pos+1);
			$this->put($freeText);
		}

		if($fieldXml["displayAsTitle"]=="1"){
			$this->put("</h4>");
		} elseif($style != null) {
			$this->put('</div>');
		}
	}

	/**
	 * displays an html table with the related items
	 * @param Principal $p
	 * @param int $width
	 * @param Element $element
	 * @param String $linkName
	 * @param FieldSelectorList $fsl
	 * @param FieldSortingKeyList $fskl
	 * @param int $limit
	 */
	public function displayElementPListPreview($p, $width, $element, $linkName, $fsl, $fskl, $limit) {
		//if element is not yet an element, no links are defined
		if(!$element || !$element->getId()) return;

		// gets link type
		$fieldXml = $element->getFieldList()->getField($linkName)->getXml();
		$query = null;
		$linkType = Links::linkTypeFromString((string)$fieldXml['linkType']);		
		switch($linkType) {
			case Links::LINKS_TYPE_SUBITEM:
				break;
			case Links::LINKS_TYPE_LINK:				
				break;
			case Links::LINKS_TYPE_QUERY:
				$query = (string)$fieldXml['source'];
				break;
			default: /*no preview supported*/ return;
		}

		$trmIsForPreviewList = $this->isForPreviewList();
		$trmRecord = $this->getRecord();
		$this->setForPreviewList(true);

		$previewListId = 'previewList_'.$element->getId()."_".$linkName;
		$this->put('<div class="SBIB ui-corner-all preview" id="'.$previewListId.'" style="overflow-x:auto;width: 100%; max-width:'.$width.'px;'.($fieldXml['expand']=="0" ? 'display:none;' : '').'">');

		$elementIsBlocked = $element->isState_blocked();
		// checks if parent is blocked
		if(!$elementIsBlocked && $element->isSubElement()) {
			foreach($this->getConfigService()->getCurrentSubElementPathFromRoot()->getListIterator() as $ls) {
				if($ls->isOwnerElementBlocked()) {
					$elementIsBlocked = true;
					break;
				}
			}
		}
		$elementIsReadonly = $fieldXml['readonly']=='1' || $fieldXml['disabled']=='1';
		
		$listFilter = ListFilter::createInstance();
		$listFilter->setFieldSelectorList($fsl);
		$listFilter->setFieldSortingKeyList($fskl);
		if($limit){
			$listFilter->setPageSize($limit);
			$listFilter->setDesiredPageNumber(1);
		}
		
		$elS = ServiceProvider::getElementService();
		
		// links of type query
		if($linkType == Links::LINKS_TYPE_QUERY && !empty($query)) {
			// parses query and creates func exp
			$queryFx = str2fx($query);
			// gets func exp evaluator
			$evalFx = $this->getFuncExpEvaluator($p, $element);
			$querySource = null;
			try {
				// evaluates query and builds data source object
				$querySource = $evalFx->evaluateFuncExp($queryFx, $this);								
				// frees evaluator
				$evalFx->freeMemory();
			}
			catch(Exception $e) {
				$evalFx->freeMemory();
				throw $e;
			}
			// updates list filter if set
			if($querySource instanceof ElementPListDataFlowConnector) {
				$querySourceLf = $querySource->getListFilter();
				if(isset($querySourceLf)) {
				    // Medair (CWE) 08.01.2018: merge given fsl with existing one to preserve extra needed calculated fields dependencies				    
				    if(isset($fsl)) {
				        $querySourceFsl = $querySourceLf->getFieldSelectorList();
				        if($querySourceFsl instanceof FieldSelectorListArrayImpl) $querySourceFsl->mergeFieldSelectorList($fsl);
				        else $querySourceLf->setFieldSelectorList($fsl);
				    }
					if(isset($fskl)) $querySourceLf->setFieldSortingKeyList($fskl);
					if($limit) {
						$querySourceLf->setPageSize($limit);
						$querySourceLf->setDesiredPageNumber(1);
					}
				}
				else $querySource->setListFilter($listFilter);				
			}
			// executes data flow and builds html
			if(isset($querySource)) {
				$currentNamespace = $p->getWigiiNamespace();
				$adaptiveWigiiNamespace = $p->hasAdaptiveWigiiNamespace();
				$p->setAdaptiveWigiiNamespace(true);
				try {
					$nb = $this->getDataFlowService()->processDataSource($p, $querySource, dfasl(
						dfas('ElementPListRowsForPreview',
							'setTrm', $this,
							'setP', $p,
							'setExec', $this->getExecutionService(),
							'setConfigService', $this->getConfigService(),
							'setFsl', $fsl,
							'setElementId', $element->getId(),
							'setLinkName', $linkName,
							'setLinkType', $linkType,
							'setElementIsBlocked', $elementIsBlocked,
							'setElementIsReadonly', $elementIsReadonly,
							'setPreviewListId', $previewListId,
							'setWidth', $width)
					), false);				
				}
				catch(ServiceException $se) {
					// if AuthorizationServiceException::FORBIDDEN then displays an empty table
					if($se->getWigiiRootException()->getCode() == AuthorizationServiceException::FORBIDDEN) {
						$nb = 0;
					}
					// else propagates exception
					else throw $se;
				}
				if(!$nb) {
					// if no rows, then displays an empty table
					$elementPList = ElementPListRowsForPreview::createInstance($this, $p, $this->getExecutionService(), $this->getConfigService(), $fsl, $element->getId(), $linkName, $elementIsBlocked, $previewListId, $linkType);
					$elementPList->setElementIsReadonly($elementIsReadonly);
					if($querySource instanceof ElementPListDataFlowConnector) {
						$groupList = $querySource->getCalculatedGroupList();
						if(isset($groupList) && !$groupList->isEmpty()) {
							$g = reset($querySource->getCalculatedGroupList()->getListIterator());
							$elementPList->setModule($g->getModule());
						}						
					}
					$elementPList->actOnBeforeAddElementP($p);
					$elementPList->actOnFinishAddElementP($p, ($listFilter->isPaged() ? ($listFilter->getTotalNumberOfObjects() > 0 ? $listFilter->getTotalNumberOfObjects():0) : ($nb > 0? $nb:0)), ($nb > 0? $nb:0), $listFilter->getPageSize(), $width);
				}		
				if(method_exists($querySource, 'freeMemory')) $querySource->freeMemory();		
				if($adaptiveWigiiNamespace) $p->setAdaptiveWigiiNamespace(false);
				$p->bindToWigiiNamespace($currentNamespace);
			}
		}
		// else subitem or link
		else {	
			$elementPList = ElementPListRowsForPreview::createInstance($this, $p, $this->getExecutionService(), $this->getConfigService(), $fsl, $element->getId(), $linkName, $elementIsBlocked, $previewListId, $linkType);
			$elementPList->setElementIsReadonly($elementIsReadonly);
			$elementPList->actOnBeforeAddElementP($p);
			if($linkType == Links::LINKS_TYPE_SUBITEM) {
			    // Medair (CWE) 08.01.2018, adds calculated on fetch dependencies to fsl
			    $listFilter->setFieldSelectorList(ServiceProvider::getWigiiBPL()->buildFslForSubElementWithFxDependencies($p, $element->getId(), $linkName, $fsl));
				$nb = $elS->getSubElementsForField($p, $element->getId(), $linkName, $elementPList, $listFilter);
				$listFilter->setFieldSelectorList($fsl);/* restores original field selector list before serialization to session */
			}
			//else : not implemented.
			
			$elementPList->actOnFinishAddElementP($p, ($listFilter->isPaged() ? $listFilter->getTotalNumberOfObjects() : $nb), $nb, $listFilter->getPageSize(), $width);
		}	
		$this->getSessionAdminService()->storeData($elS, $previewListId."_".$this->getExecutionService()->getCrtContext(), array($element->getId(), $linkName, (string)$fieldXml['linkType'], $listFilter, $elementIsBlocked, $query, $elementIsReadonly));
		
		$this->put('</div>');

		$this->setForPreviewList($trmIsForPreviewList);
		$this->setRecord($trmRecord);
	}

    /**
     * displays a blog view with the related items
     * @param Principal $p
     * @param int $width
     * @param Element $element
     * @param String $linkName
     * @param FieldSelectorList $fsl
     * @param FieldSortingKeyList $fskl
     * @param int $limit
     */
    public function displayElementPListBlogPreview($p, $width, $element, $linkName, $fsl, $fskl, $limit) {
        //if element is not yet an element, no links are defined
        if(!$element || !$element->getId()) return;

        // gets link type
        $fieldXml = $element->getFieldList()->getField($linkName)->getXml();
        $query = null;
        $linkType = Links::linkTypeFromString((string)$fieldXml['linkType']);
        switch($linkType) {
            case Links::LINKS_TYPE_SUBITEM:
                break;
            case Links::LINKS_TYPE_LINK:
                break;
            case Links::LINKS_TYPE_QUERY:
                $query = (string)$fieldXml['source'];
                break;
            default: /*no preview supported*/ return;
        }

        $trmIsForPreviewList = $this->isForPreviewList();
        $trmRecord = $this->getRecord();
        $this->setForPreviewList(true);

        $previewListId = 'previewList_'.$element->getId()."_".$linkName;
        $this->put('<div class="SBIB ui-corner-all preview" id="'.$previewListId.'" style="overflow-x:auto;width: 100%; max-width:'.$width.'px;'.($fieldXml['expand']=="0" ? 'display:none;' : '').'">');

        $elementIsBlocked = $element->isState_blocked();
        // checks if parent is blocked
        if(!$elementIsBlocked && $element->isSubElement()) {
            foreach($this->getConfigService()->getCurrentSubElementPathFromRoot()->getListIterator() as $ls) {
                if($ls->isOwnerElementBlocked()) {
                    $elementIsBlocked = true;
                    break;
                }
            }
        }
        $elementIsReadonly = $fieldXml['readonly']=='1' || $fieldXml['disabled']=='1';

        $listFilter = ListFilter::createInstance();
        $listFilter->setFieldSelectorList($fsl);
        $listFilter->setFieldSortingKeyList($fskl);
        if($limit){
            $listFilter->setPageSize($limit);
            $listFilter->setDesiredPageNumber(1);
        }

        $elS = ServiceProvider::getElementService();

        // links of type query
        if($linkType == Links::LINKS_TYPE_QUERY && !empty($query)) {
            // parses query and creates func exp
            $queryFx = str2fx($query);
            // gets func exp evaluator
            $evalFx = $this->getFuncExpEvaluator($p, $element);
            $querySource = null;
            try {
                // evaluates query and builds data source object
                $querySource = $evalFx->evaluateFuncExp($queryFx, $this);
                // frees evaluator
                $evalFx->freeMemory();
            }
            catch(Exception $e) {
                $evalFx->freeMemory();
                throw $e;
            }
            // updates list filter if set
            if($querySource instanceof ElementPListDataFlowConnector) {
                $querySourceLf = $querySource->getListFilter();
                if(isset($querySourceLf)) {
                    // Medair (CWE) 08.01.2018: merge given fsl with existing one to preserve extra needed calculated fields dependencies
                    if(isset($fsl)) {
                        $querySourceFsl = $querySourceLf->getFieldSelectorList();
                        if($querySourceFsl instanceof FieldSelectorListArrayImpl) $querySourceFsl->mergeFieldSelectorList($fsl);
                        else $querySourceLf->setFieldSelectorList($fsl);
                    }
                    if(isset($fskl)) $querySourceLf->setFieldSortingKeyList($fskl);
                    if($limit) {
                        $querySourceLf->setPageSize($limit);
                        $querySourceLf->setDesiredPageNumber(1);
                    }
                }
                else $querySource->setListFilter($listFilter);
            }
            // executes data flow and builds html
            if(isset($querySource)) {
                $currentNamespace = $p->getWigiiNamespace();
                $adaptiveWigiiNamespace = $p->hasAdaptiveWigiiNamespace();
                $p->setAdaptiveWigiiNamespace(true);
                try {
                    $nb = $this->getDataFlowService()->processDataSource($p, $querySource, dfasl(
                        dfas('ElementPListRowsForBlogPreview',
                            'setTrm', $this,
                            'setP', $p,
                            'setExec', $this->getExecutionService(),
                            'setConfigService', $this->getConfigService(),
                            'setFsl', $fsl,
                            'setElementId', $element->getId(),
                            'setLinkName', $linkName,
                            'setLinkType', $linkType,
                            'setElementIsBlocked', $elementIsBlocked,
                            'setElementIsReadonly', $elementIsReadonly,
                            'setPreviewListId', $previewListId,
                            'setWidth', $width)
                    ), false);
                }
                catch(ServiceException $se) {
                    // if AuthorizationServiceException::FORBIDDEN then displays an empty table
                    if($se->getWigiiRootException()->getCode() == AuthorizationServiceException::FORBIDDEN) {
                        $nb = 0;
                    }
                    // else propagates exception
                    else throw $se;
                }
                if(!$nb) {
                    // if no rows, then displays an empty table
                    $elementPList = ElementPListRowsForBlogPreview::createInstance($this, $p, $this->getExecutionService(), $this->getConfigService(), $fsl, $element->getId(), $linkName, $elementIsBlocked, $previewListId, $linkType);
                    $elementPList->setElementIsReadonly($elementIsReadonly);
                    if($querySource instanceof ElementPListDataFlowConnector) {
                        $groupList = $querySource->getCalculatedGroupList();
                        if(isset($groupList) && !$groupList->isEmpty()) {
                            $g = reset($querySource->getCalculatedGroupList()->getListIterator());
                            $elementPList->setModule($g->getModule());
                        }
                    }
                    $elementPList->actOnBeforeAddElementP($p);
                    $elementPList->actOnFinishAddElementP($p, ($listFilter->isPaged() ? ($listFilter->getTotalNumberOfObjects() > 0 ? $listFilter->getTotalNumberOfObjects():0) : ($nb > 0? $nb:0)), ($nb > 0? $nb:0), $listFilter->getPageSize(), $width);
                }
                if(method_exists($querySource, 'freeMemory')) $querySource->freeMemory();
                if($adaptiveWigiiNamespace) $p->setAdaptiveWigiiNamespace(false);
                $p->bindToWigiiNamespace($currentNamespace);
            }
        }
        // else subitem or link
        else {
            $elementPList = ElementPListRowsForBlogPreview::createInstance($this, $p, $this->getExecutionService(), $this->getConfigService(), $fsl, $element->getId(), $linkName, $elementIsBlocked, $previewListId, $linkType);
            $elementPList->setElementIsReadonly($elementIsReadonly);
            $elementPList->actOnBeforeAddElementP($p);
            if($linkType == Links::LINKS_TYPE_SUBITEM) {
                // Medair (CWE) 08.01.2018, adds calculated on fetch dependencies to fsl
                $listFilter->setFieldSelectorList(ServiceProvider::getWigiiBPL()->buildFslForSubElementWithFxDependencies($p, $element->getId(), $linkName, $fsl));
                $nb = $elS->getSubElementsForField($p, $element->getId(), $linkName, $elementPList, $listFilter);
                $listFilter->setFieldSelectorList($fsl);/* restores original field selector list before serialization to session */
            }
            //else : not implemented.

            $elementPList->actOnFinishAddElementP($p, ($listFilter->isPaged() ? $listFilter->getTotalNumberOfObjects() : $nb), $nb, $listFilter->getPageSize(), $width);
        }
        $this->getSessionAdminService()->storeData($elS, $previewListId."_".$this->getExecutionService()->getCrtContext(), array($element->getId(), $linkName, (string)$fieldXml['linkType'], $listFilter, $elementIsBlocked, $query, $elementIsReadonly));

        $this->put('</div>');

        $this->setForPreviewList($trmIsForPreviewList);
        $this->setRecord($trmRecord);
    }

	/**
	 * output/return an html string which represent the label of the field
	 * @param $allowSpecialLabel : bool = true, if true the label will be construct with the datatype_displayLabel.tpl.php if existing
	 */
	public function displayLabel($fieldName, $parentWidth = null, $visibleLanguage = null, $allowSpecialLabel = true){
		$field = $this->getField($fieldName);
		if($allowSpecialLabel && $field->getDataType()!=null){
			$path = DATATYPE_PATH.$field->getDataType()->getDataTypeName()."_displayLabel.tpl.php";
			if(file_exists($path)){
				include $path;
				return;
			}
		}
		$this->put($this->t($fieldName, $field->getXml()));
	}

	/**
	 * output/return an html string which represent the label of a header of a column
	 * defined with a fieldSelector
	 */
	public function displayHeaderLabel($fieldSelector, $fieldXml=null, $xmlHeader=null, $forceDisplayElementState=false){
		if($xmlHeader != null && count($xmlHeader->children()) > 0){
			if($xmlHeader["displayHTMLinLabel"]=="1"){
				$label = $this->t($fieldSelector->getFieldName(), $xmlHeader);
			} else {
				$label = str_replace(" ", "&nbsp;", $this->t($fieldSelector->getFieldName(), $xmlHeader));
			}
			$this->put(preg_replace("#<a&nbsp;class=\"(\w+)\"&nbsp;href=\"(\d+)\">&nbsp;</a>#i","<a class=\"$1\" href=\"$2\"> </a>", $label));
		} else if($fieldSelector->isElementAttributeSelector()){
			//if states, then by default no label
			if(!$forceDisplayElementState && substr($fieldSelector->getSubFieldName(), 0, 6) == "state_"){
				$this->put('<font style="display:none;">'.$this->t($fieldSelector->getSubFieldName()).'</font>');
				$this->put("&nbsp;");
			} else {
				$this->put(str_replace(" ", "&nbsp;", $this->t($fieldSelector->getSubFieldName())));
			}
		} else {
			//if download file or mime type, by default no label
			if(($fieldSelector->getSubFieldName() == "path" || $fieldSelector->getSubFieldName() == "mime") && $fieldXml["type"]=="Files"){
				$this->put("&nbsp;");
			} else if($fieldSelector->getSubFieldName() == null || $fieldSelector->getSubFieldName() == 'value'){
				if($xmlHeader["displayHTMLinLabel"]=="1"){
					$this->put($this->t($fieldSelector->getFieldName(), $fieldXml));
				} else {
					$this->put(str_replace(" ", "&nbsp;", $this->t($fieldSelector->getFieldName(), $fieldXml)));
				}
			} else {
				if($xmlHeader["displayHTMLinLabel"]=="1"){
					$this->put($this->t($fieldSelector->getFieldName(), $fieldXml)." ".$this->t($fieldSelector->getSubFieldName()));
				} else {
					$this->put(str_replace(" ", "&nbsp;", $this->t($fieldSelector->getFieldName(), $fieldXml)." ".$this->t($fieldSelector->getSubFieldName())));
				}
			}
		}
	}

	/**
	 * $contentWillBeReplaced : Boolean, if true the method return the new item String with the value $$conten$$ where
	 * the content should be placed in. This way we can wrap the user details arround the item value
	 */
	public function getNewJournalItemString($p, $isHtml, $contentWillBeReplaced=false){
		$returnValue = "";
		if($isHtml){
			$returnValue .= "<p style=&quot;color:#666;&quot;>&gt; ";
		} else {
			$returnValue .= "> ";
		}
		$returnValue .= date("d.m.Y H:i")." ".($p->getRealUser() ? $p->getRealUser()->getUserName() : $this->t("externalAccessUsernameForJournal"));
		if($isHtml){
			$returnValue .= "</p>";
		} else {
			$returnValue .= "\\\\n";
		}
		if($contentWillBeReplaced){
			$returnValue .= $this->getNewJournalContentStringCode();
		} else {
			$returnValue .= '<p>'.$this->t("externalAccessTypeHereForJournal").'</p>';
		}
		if($isHtml){
			$returnValue .= "<p>&nbsp;</p>";
		} else {
			$returnValue .= "\\\\n\\\\n";
		}
		return $returnValue;
	}
	public function getNewJournalContentStringCode(){
		return '$$conten$$';
	}

	/**
	 * return html string displaying:
	 * 	- Last updated by: sys_username
	 * 	- Last updated on: sys_date
	 * 	- Created by: sys_creationUsername
	 * 	- Created on: sys_creationDate
	 * @param $sysInformationObject must implement interface SysInformation
	 */
	public function getAdditionalInformation($fieldName){
		$returnValue = "";
		if($this->getRecord()->getFieldValue($fieldName, "sys_username")) $returnValue .= $this->h("sys_username").": ".$this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "sys_username"))."<br />";
		if($this->getRecord()->getFieldValue($fieldName, "sys_date")) $returnValue .= $this->h("sys_date").": ".date("d.m.Y H:i:s", $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "sys_date")))."<br />";
		if($this->getRecord()->getFieldValue($fieldName, "sys_creationUsername")) $returnValue .= $this->h("sys_creationUsername").": ".$this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "sys_creationUsername"))."<br />";
		if($this->getRecord()->getFieldValue($fieldName, "sys_creationDate")) $returnValue .= $this->h("sys_creationDate").": ".date("d.m.Y H:i:s", $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "sys_creationDate")))."<br />";
		return $returnValue;
	}
	
	/**
	 * return true if the field has been updated within $nbOfDays:default 7 days
	 * if sys_date!= sys_creationDate and (sys_date + nbOfDays > today)
	 */
	public function isFieldUpdatedRecently($fieldName,$nbOfDays=7){
		if($this->getRecord() instanceof Element) {
			if($nbOfDays){
				$lastUpdate = $this->getRecord()->getFieldValue($fieldName, "sys_date");
				$creationDate = $this->getRecord()->getFieldValue($fieldName, "sys_creationDate");
				if($lastUpdate!=$creationDate && ($lastUpdate+($nbOfDays*24*3600))>time()){
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * return string with classes separated by space (starting with a space):
	 * 	- updatedRecently : if isFieldUpdatedRecently (only for fields with DataType)
	 */
	public function getAdditionalFieldClass($fieldName, $dataTypeName){
		$returnValue = "";
		//add class if not external, listView, Notification or Print
		if(!$this->isForExternalAccess() && !$this->isForListView() && !$this->isForNotification() && !$this->isForPrint()){
			//add class on Records
			if($this->getRecord() instanceof Element) {
				//add class if udpated recently
				$highlightLastChanges = (string)$this->getConfigService()->getParameter($this->getP(), $this->getRecord()->getModule(), "Element_highlightLastchanges");
				$highlightLastChangesClass = (string)$this->getConfigService()->getParameter($this->getP(), $this->getRecord()->getModule(), "Element_highlightLastchangesClass");
				if($highlightLastChanges!= "" && $dataTypeName){ //updatedRecently is only for fields with datatype
					if($this->isFieldUpdatedRecently($fieldName,$highlightLastChanges)){
						if($highlightLastChangesClass) $returnValue .= " ".$highlightLastChangesClass;
						else $returnValue .= " updatedRecently";
					}
				}
			}
		}
		return $returnValue;
	}
	
	/**
	 * @param additionalRowInfo, is information added in a column per elements (i.e. used to say those element remains elsewhere)
	 */
	public function displayElementPAdvancedList($elementPAdvancedList, $parentWidth = null, $tableClass=null, $tableStyle=null, $additionalRowInfo=null, $idsAreNew=false){
		if(isset($elementPAdvancedList) && is_a($elementPAdvancedList, "ElementPAdvancedList")){
			$trmIsForListView = $this->isForListView();
			$this->setForListView(true);
			$fsl = $elementPAdvancedList->getFieldSelectorList();
			$isaFieldSelectorFromActivity = is_a($fsl, "FieldSelectorListForActivity");

			$firstElement = true;
			if($parentWidth!=null) $parentWidth = "width: 100%; max-width:".$parentWidth."px;";
			$this->put('<TABLE class="'.$tableClass.'" style="'.$parentWidth.' '.$tableStyle.'" >');
			$nb = 0;
			$nbCol = 0;
			foreach($elementPAdvancedList->getListIterator() as $elementP){
				$this->setRecord($elementP->getElement());
				if($firstElement){
					//make header
					$this->put("<THEAD><TR>");
					if($additionalRowInfo!=null){
						$this->put("<TH'></TH>");
						$nbCol++;
					}
					//add element link
					$this->put("<TH><div>".$this->t("link")."</div></TH>");
					$nbCol++;
					foreach($fsl->getListIterator() as $key=>$fs){
						if($fs->isElementAttributeSelector()) $fieldXml = null;
						else $fieldXml = $elementP->getElement()->getFieldList()->getField($fs->getFieldName())->getXml();
						if($isaFieldSelectorFromActivity) $xmlHeader = $fsl->getXml($key);
						else $xmlHeader = null;
						if(isset($xmlHeader) && $xmlHeader["width"]=="0") continue;
						if(isset($xmlHeader) && $xmlHeader["hidden"]=="1") continue;
						$this->put("<TH><div>");
						$this->displayHeaderLabel($fs, $fieldXml, $xmlHeader);
						$nbCol++;
						$this->put("</div></TH>");
					}
					$this->put("</TR></THEAD><TBODY>");
					$firstElement = false;
				}
				$this->put('<TR class="'.(($nb % 2) == 0 ? "even" : "odd").'" >');
				if($additionalRowInfo!=null){
					$this->put("<TD><div>".$additionalRowInfo[$elementP->getId()]."</div></TD>");
				}
				//add element link
				$this->put("<TD><div><a href='".$this->getElementService()->getUrlForElement($this->getExecutionService()->getCrtWigiiNamespace(), $elementP->getElement()->getModule(), $elementP->getElement())."' target='_blank'>".$elementP->getId()."</a></div></TD>");
				$html2text = new Html2text();
				foreach($fsl->getListIterator() as $key=>$fs){
					if($isaFieldSelectorFromActivity) $xmlHeader = $fsl->getXml($key);
					else $xmlHeader = null;
					if(isset($xmlHeader) && $xmlHeader["hidden"]=="1") continue;

					$val = $this->formatValueFromFS($fs, $elementP->getElement(), false, $idsAreNew);
					if(is_array($val)){
						//this can be either a multipleAttribut either a multilanguage field
						if($fsl->getSelectedLanguages()!=null){
							//if language selected, then take only selected language
							$tempVal = array_intersect_key($val, $fsl->getSelectedLanguages());
							//if no intersection, this is probably not a multilanguage field.
							if($tempVal != null) $val = $tempVal;
						}
						$val = implode(", ", $val);
					}
					$html2text->setHtml($val);
					$this->put("<TD><div>".$html2text->getText()."</div></TD>");
// 					$html2text->clear();
				}
				$this->put("</TR>");
				$nb++;
			}
			if($additionalRowInfo != null && $additionalRowInfo["footer"]!=null){
				$this->put('<TR><TD COLSPAN="'.$nbCol.'" style="border-top:1px solid #ccc" >');
				$this->put($additionalRowInfo["footer"]);
				$this->put("</TD></TR>");
			}
			$this->put("</TBODY></TABLE>");
			$this->setForListView($trmIsForListView);
		} else {
			throw new ServiceException("elementPAdvancedList cannot be null or not a ElementPAdvancedList class or subclass", ServiceException::INVALID_ARGUMENT);
		}
	}

	/**
	 * display the fields. Each fields which has already been displayed (label or value or form)
	 * will not be diplayed again. This is based on the cache.
	 */
	public function displayRemainingDetails($stopBeforeField=null){
		$fieldListIterator = $this->getRecord()->getFieldList()->getListIterator();
		if($fieldListIterator){
			foreach($fieldListIterator as $field){
				if(!$this->isFieldCached($field->getFieldName())){
					if(isset($stopBeforeField) && $stopBeforeField == $field->getFieldName()) return;
					$this->cacheField($field);
					$field->acceptFieldListVisitor($this->getDetailRenderer());
				}
			}
		}
		$this->getDetailRenderer()->finish();
	}

	private $DetailRenderer;
	public function getDetailRenderer(){
		return $this->DetailRenderer;
	}
	public function setDetailRenderer($dv){
		$this->DetailRenderer = $dv;
	}

	/**
	 * display the forms. Each fields which has already been displayed (label or value or form)
	 * will not be diplayed again. This is based on the cache.
	 * $stopBeforeField : string, if defined, then the displayRemainingForms will stop before rendering this fieldname
	 */
	public function displayRemainingForms($stopBeforeField=null){
		$fieldListIterator = $this->getRecord()->getFieldList()->getListIterator();
		if($fieldListIterator){
			foreach($fieldListIterator as $field){
				if(!$this->isFieldCached($field->getFieldName())){
					if(isset($stopBeforeField) && $stopBeforeField == $field->getFieldName()) return;
					$this->cacheField($field);
					$field->acceptFieldListVisitor($this->getFormRenderer());
				}
			}
		}
		$this->getFormRenderer()->finish();
	}

	private $FormRenderer;
	public function getFormRenderer(){
		return $this->FormRenderer;
	}
	public function setFormRenderer($fv){
		$this->FormRenderer = $fv;
	}

	protected $htmlBuffer;
	public function put($string){
		if($this->isOutputEnabled()) echo $string;
		else $this->htmlBuffer .= $string;
	}

	public function getHtml(){
		return $this->htmlBuffer;
	}
	public function getHtmlAndClean(){
		$returnValue = $this->htmlBuffer;
		$this->htmlBuffer = null;
		return $returnValue;
	}

	//utils

	public function openForm($formId, $submitAction, $totalWidth=null, $isDialog = true, $class=null){
		$this->put('<form ');
		$this->put(' id="'.$formId.'" ');
		$this->put(' method="post" ');
		$this->put(' enctype="multipart/form-data" ');
		$this->put(' action="'.$submitAction.'" ');
		if($class)	$this->put(' class="'.$class.'" ');
		// CWE 26.08.2016: prevents autofilling forms into Admin module, except for login page
		// autocomplete=off doesn't work at field level for special login fields like username,password,email. autocomplete=off should be put at form level		
		$crtModule = ServiceProvider::getExecutionService()->getCrtModule();
		if(!ServiceProvider::getAuthenticationService()->isMainPrincipalMinimal() && $crtModule->isAdminModule() ||
			$this->getConfigService()->getParameter($this->getP(), $crtModule, "noAutofill")=='1') {
			$this->put('autocomplete="off"');
		}		
		$this->put(' >');
	}

	/**
	 * the cancel name and ref is used only in the case if isDialog is false
	 */
	public function closeForm($formId, $state, $submitName="ok", $isDialog = true, $cancelName=null, $cancelRef=null, $cancelJsCode=null){

		//on ajoute en POST l'id du formulaire pour pouvoir retrouver facilement les ids
		//des différents champ du formulaire
		$this->put('<input type="hidden" name="idForm" value="'.$formId.'" />');
		$this->put('<input type="hidden" name="action" value="'.$state.'" />');

		$this->put('<div class="clear"></div>');

		if($isDialog){
			//if not in dialog then we create our self the submit button
			$this->put('<input type="submit" value="'.str_replace('"', '&quot;', $this->t($submitName)).'" style="display:none;" />');
		}

		if($cancelName != null){
			if($cancelRef) $cancelRef = "self.location='".str_replace("//", '\/\/', $cancelRef)."';";
			$this->put('<input type="button" class="InputCancel" value="'.str_replace('"', '&quot;', $this->t($cancelName)).'" onclick="'.$cancelJsCode." ".$cancelRef.'" style="display:none;" />');
		}
		if(!$isDialog){

			$this->put('<div id="validateButton" style="border-width:2px 0px 0px 0px;background-color:transparent;padding-top:10px;margin-bottom:30px;" class="publicFormBorder">');
			if($cancelName != null){
				$this->put('<button style="margin-left:10px;float:right;" type="button" class="H cancel ui-button publicFormBorder ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false"><span class="ui-button-text">'.$this->t($cancelName).'</span></button>');
			}
			$this->put('<button style="margin-left:10px;float:right;" type="button" class="H ok ui-button publicFormBorder ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false"><span class="ui-button-text">'.$this->t($submitName).'</span></button>');
			$this->put('</div>');
		}
		$this->put('</form>');

		/**
		 * All specific JS to do on specific type of field, as htmlArea, textarea elastic, focus on first
		 * input, will be done in the Form Executor, resolve form, after the doRender. Like this the JS Code
		 * is done when the form is already shown
		 */
		if($isDialog){
			// press on enter key, clicks on OK button
			// except if noSubmitOnEnter=1
			if((string)$this->getConfigService()->getParameter($this->getP(), $this->getRecord()->getModule(), "noSubmitOnEnter")=="1") {
				$this->getExecutionService()->addJsCode(" $('#$formId :not(textarea):input').keydown(function(e){ if(e.keyCode == 13){ e.stopPropagation(); e.preventDefault(); $(this).change();}});");
			}
			else $this->getExecutionService()->addJsCode(" $('#$formId :not(textarea):input').keydown(function(e){ if(e.keyCode == 13){ $('#$formId').closest('.ui-dialog').find('.ui-dialog-buttonpane .ok:not(:disabled)').click(); e.stopPropagation(); e.preventDefault(); }});");
			
			
		} else {
			//to prevent compatibility problems on clickin on the normal submit button
			//we create buttons reflecting the forms buttons:

			// press on enter key, clicks on OK button
			// except if noSubmitOnEnter=1
			if((string)$this->getConfigService()->getParameter($this->getP(), $this->getRecord()->getModule(), "noSubmitOnEnter")=="1") {
				$this->getExecutionService()->addJsCode("$('#$formId :not(textarea):input').keydown(function(e){if(e.keyCode == 13){ e.stopPropagation(); e.preventDefault(); $(this).change();}});");
			}
			else $this->getExecutionService()->addJsCode("$('#$formId :not(textarea):input').keydown(function(e){if(e.keyCode == 13){ ".(!$this->isForExternalAccess() ? "$('#$formId .ok').click();" : "")."e.stopPropagation(); e.preventDefault(); }});");
			
			//add control to limit double subscription click
			$this->getExecutionService()->addJsCode("" .
				"".($this->isForExternalAccess() ? "clearTimeout(externalAccessTimeoutTimer); $.unblockUI();" : "")."" .
				"$('#$formId .ok').click(function(e){ $('#$formId').submit(); ".($this->isForExternalAccess() ? "$.blockUI({ css: { border:'none' }, message: $('#loadingBar'), overlayCSS: { backgroundColor: '#f1f3f7' } }); externalAccessTimeoutTimer = setTimeout(function(){ $.unblockUI({ onUnblock:function(){ alert('".$this->h("unusualServerTiming")."'); } }); }, 60*10*1000); " : "")." e.stopPropagation(); e.preventDefault(); });" .
				"".($cancelName != null ? "$('#$formId .cancel').click(function(){ $('#$formId .InputCancel').click(); });" : '')."" .
				"");
		}
	}

	//addJSCode for HTMLAREA. this is done here and not in the form template,
	//because we need to do the htmlArea after the show is done.
	//the same for focus, and textareaElastic
	public function addJsCodeAfterFormIsShown($formId){
		//do the mapping from the translation service to the ckeditor
		switch($this->getTranslationService()->getLanguage()){
			case "l02" : $lang = "fr"; $scayt_lang = "fr_FR"; break;
			case "l01" :
			default:
				$lang = "en"; $scayt_lang = "en_GB"; 
		}
		//enterMode : CKEDITOR.ENTER_P or ENTER_BR or ENTER_DIV
		$ckTemplateFile = null;
		$exec = $this->getExecutionService();
		$p = $this->getP();
		//first look config template
		$ckTemplateFile = $this->getConfigService()->getParameter($p, $exec->getCrtModule(), "CKTemplatePath");
		//do not add default by default. This can be defined in the confi if wanted. This gives more flexibility
		$templateFilter = $this->getConfigService()->getParameter($p, $exec->getCrtModule(), "CKTemplateFilter");
		if(!$ckTemplateFile || !file_exists(CLIENT_WEB_PATH.$ckTemplateFile)){
			//if not found look for namespace templates
			$ckTemplateFile = CLIENT_WEB_PATH.$this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."_CKTemplates.js.php";
			$templateFilter = $this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceUrl().",default";
			if(!file_exists($ckTemplateFile)){
				$ckTemplateFile = CLIENT_WEB_PATH.$this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."_CKTemplates.js";
			}
			//if not found look for default
			if(!file_exists($ckTemplateFile)){
				$ckTemplateFile = CLIENT_WEB_PATH."CKTemplates.js.php";
				if(!file_exists($ckTemplateFile)){
					$ckTemplateFile = CLIENT_WEB_PATH."CKTemplates.js";
				}
				$templateFilter = "default";
			}
		} else {
			$ckTemplateFile = CLIENT_WEB_PATH.$ckTemplateFile;
		}
		$this->getExecutionService()->addJsCode("addJsCodeAfterFormIsShown('#$formId', '$lang', '$scayt_lang', '$templateFilter', '$ckTemplateFile');");
	}

	/**
	 * CAPTCHA management
	 */
	public function getCaptchaFile(){
		$img = new Securimage();
		//Warning we need to reset the font filename to the wigii architechture
		$img->ttf_file = IMPL_PATH."libs/securimage/elephant.ttf";
//		$img->bgimg = IMPL_PATH."libs/securimage/images/bg.png";
		$img->show(); // alternate use:  $img->show('/path/to/background.jpg');
	}
	public function getCaptchaHTML($formExecutor){
		if(!$formExecutor->protectWithCaptcha()) return;

		$this->put('<div id="'.$formExecutor->getFormId().'_captcha" class="field" style="width: 100%; max-width:'.$formExecutor->getTotalWidth().'px;padding-bottom:10px;" >');

		$error = $this->getRecord()->getWigiiBag()->getError("captcha_code");
		if(!empty($error)){
			$this->put('<div class="fieldError" style="width: 100%; max-width:'.((int)$formExecutor->getFormRenderer()->getTotalWidth()).'px;">'.$this->t($error).'</div><div class="clear"></div>');
			$error = '<img  class="icon" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-unreadable.png" />&nbsp;';
		}

		$this->put('<div class="label" style="width: 100%; max-width:'.((int)$formExecutor->getFormRenderer()->getLabelWidth()).'px;">');
		$this->put('<img src="'.SITE_ROOT.'NoWigiiNamespace/NoModule/download/captcha?sid='.md5(uniqid(time())).'" alt="CAPTCHA Image" />');
		$this->put('</div>');
		$this->put('<div class="input input_captcha">* '.$error.$this->getTranslationService()->t($this->getP(), "pleaseEnterCode").'<br><input type="text" name="captcha_code" style="width: 100%; max-width:150px;margin-top:8px;" maxlength="4" /></div>');
		$this->put('</div><div class="clear"></div>');
	}
	public function validateCaptcha($formExecutor){
		if(!$formExecutor->protectWithCaptcha()) return;

		$securimage = new Securimage();
		if ($securimage->check($_POST['captcha_code']) == false) {
			// the code was incorrect
			// handle the error accordingly with your other error checking
			// or you can do something really basic like this
			$formExecutor->addErrorToField("invalidCaptchaCode","captcha_code");
			//eput("Bad code: ".$_POST['captcha_code']." should be: ".put($_SESSION['securimage_code_value']));
			//die(' The code you entered was incorrect.  Go back and try again.');
		}
	}

	protected function displayForm_0_TillPossibleAdditionalAttribute($labelWidth, $valueWidth, $subFieldName, $dataTypeName, $inputNode, $inputType, $inputId, $inputName, $isRequire, $noLabel = false, $isNoAutofill = false){
		if(!$noLabel){
			$this->put('<div class="subLabel" style="'.trim($labelWidth).'" ><label for="'.$inputId.'" >');
			if($isRequire) $this->put("* ");
			$this->put($this->t($dataTypeName."_".$subFieldName));
			$this->put('</label></div>');
		}
		$this->put('<div class="subInput" style="'.trim($valueWidth).'" >');
		$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" style="'.trim($valueWidth).'" ');
		if($inputType != null) $this->put(' type="'.$inputType.'" ');
		if($isNoAutofill) $this->put('autocomplete="off"');
	}
	protected function displayForm_1_TillClassDefinition(){
		$this->put(' class="');

	}
	protected function displayForm_2_TillStyleDefinition($labelWidth, $valueWidth, $subFieldName, $readonly, $disabled){
		if($readonly) $this->put(' removeDisableOnSubmit ');
		$this->put('" ');
		if($disabled) $this->put(' disabled ');
		if($readonly) $this->put(' disabled ');
		$this->put(' style="'.$valueWidth);
		if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome

	}
	protected function displayForm_3a_CloseStyleBeginValueAsAttribute(){
		$this->put('" value="');

	}
	protected function displayForm_3b_CloseStyleBeginValueAsNode(){
		$this->put('" >');

	}
	//this is used for checkboxes, because the value is set in the additional attribute section
	protected function displayForm_3c_CloseStyleAndFinish(){
		$this->put('" /></div><div class="clear" ></div>');

	}
	protected function displayForm_4a_CloseValueAsAttribute(){
		$this->put('" />');
		$this->put('</div><div class="clear" ></div>');

	}
	protected function displayForm_4b_CloseValueAsNode($inputNode){
		$this->put('</'.$inputNode.'>');
		$this->put('</div><div class="clear" ></div>');

	}

	/**
	 * Format value
	 */
	public function doFormatForText($value){
		//nothing special to format for standard text
		if(is_array($value)){
			return $value;
		}
		return $value;
	}
	/**
	 *
	 */
	public function doFormatForHtmlText($value, $purify=true){
		$purifier = TechnicalServiceProvider::getHTMLPurifier();
		if(is_array($value)){
			$result = array();
			foreach($value as $key=>$val){
				if($purify && $this->isHTMLPurifierEnabled()){
					$result[$key] = $purifier->purify(htmlspecialchars_decode($val, ENT_QUOTES));
				} else {
					$result[$key] = htmlspecialchars_decode($val, ENT_QUOTES);
				}
			}
			return $result;
		}
		if($purify && $this->isHTMLPurifierEnabled()){
			return $purifier->purify(htmlspecialchars_decode($value, ENT_QUOTES));
		} else {
			return htmlspecialchars_decode($value, ENT_QUOTES);
		}
	}
	public function doFormatForColor($value){
		if($value){
			if($value[0]!="#"){
				$value = "#".$value;
			}
			return '<div style="float:left;height:15px;width: 100%; max-width:25px;background-color:'.$value.';"></div>&nbsp;<span style="color:#666;font-size:x-small;">('.$value.')</span>';
		}
		return "";
	}

	public function doFormatForLinks($value, $xml=array(), $doRegroupSimilarValue=false){
		//we do a groupement on the value
		if(!$doRegroupSimilarValue){
			if ($value==null) {
				return 0;
			}
			return $value;
		}
		if ($value===null) {
			$value = "= 0";
		}
		elseif ($value >= 100000) $value = ">= ".number_format(100000, 0, ".", "'");
		elseif ($value >= 50000) $value = ">= ".number_format(50000, 0, ".", "'");
		elseif ($value >= 20000) $value = ">= ".number_format(20000, 0, ".", "'");
		elseif ($value >= 10000) $value = ">= ".number_format(10000, 0, ".", "'");
		elseif ($value >= 5000) $value = ">= ".number_format(5000, 0, ".", "'");
		elseif ($value >= 2000) $value = ">= ".number_format(2000, 0, ".", "'");
		elseif ($value >= 1000) $value = ">= ".number_format(1000, 0, ".", "'");
		elseif ($value >= 500) $value = ">= ".number_format(500, 0, ".", "'");
		elseif ($value >= 200) $value = ">= ".number_format(200, 0, ".", "'");
		elseif ($value >= 100) $value = ">= ".number_format(100, 0, ".", "'");
		elseif ($value >= 50) $value = ">= ".number_format(50, 0, ".", "'");
		elseif ($value >= 40) $value = ">= ".number_format(40, 0, ".", "'");
		elseif ($value >= 30) $value = ">= ".number_format(30, 0, ".", "'");
		elseif ($value >= 20) $value = ">= ".number_format(20, 0, ".", "'");
		elseif ($value >= 15) $value = ">= ".number_format(15, 0, ".", "'");
		elseif ($value >= 10) $value = ">= ".number_format(10, 0, ".", "'");
		elseif ($value >= 5) $value = ">= ".number_format(5, 0, ".", "'");
		elseif ($value >= 1) $value = ">= ".number_format(1, 0, ".", "'");
		elseif ($value > 0) $value = "> 0";
		elseif ($value == 0) $value = "= 0";
		return $value;
	}

	public function doFormatForBirthDay($value, $doRegroupSimilarValue=false){
		if($value == null) return null;
		if(!$doRegroupSimilarValue){
			if ($value==null) return null;
			return $value; //the value needs to remain to keep the sorting order
			//return substr($value, 3, 2)." ".$this->t("month_".str_replace("0", "", substr($value, 0, 2)));
		}
		//regroup per month
		return $this->t("month_long_".str_replace("0", "", substr($value, 0, 1)).substr($value, 1, 1));

	}
	public function doFormatForDate($value, $doRegroupSimilarValue = false, $isBirthdate=false, $includeTime=false){
		$now = time();

		$d = $m = $y = $h = $i = $s = null;
		if(is_numeric($value)){
			//$value is a timestamp
			//nothing to do, value is stamp
		} else {
			if($value!=null && Dates::fromString($value, $d, $m, $y, $h, $i, $s)){
				if(($h || $i || $s) && !($h==0 && $i==0 && $s==0)) $time = "$h:$i:$s";
				else {
					$time = "";
					$includeTime = false;
				}
				$value = strtotime("$y/$m/$d $time");
			} else {
				$value = null;
			}
		}
//		$stamp  = strtotime($value);
//		//if the value was already a timstamp
//		if($stamp!==false){
//			$value = $stamp;
//		}
		if(!$doRegroupSimilarValue){
			if ($value==null) return null;
			if($isBirthdate){
				return date("d.m.Y", $value)." (".floor(($now-$value)/(24*3600*365.25)).")";//Dates::formatDisplay($value, "dd.mm.yyyy");
			} else {
				if($includeTime){
					return date("d.m.Y H:i", $value);//Dates::formatDisplay($value, "dd.mm.yyyy");
				} else {
					return date("d.m.Y", $value);//Dates::formatDisplay($value, "dd.mm.yyyy");
				}
			}
		}
		if($isBirthdate){
			$age = floor(($now-$value)/(24*3600*365.25));
			if ($value==null) $value = $this->t("empty");
			elseif ($age >= 100) $value = $this->t("+100");
			elseif ($age >= 65) $value = $this->t("65-99");
			elseif ($age >= 50) $value = $this->t("50-64");
			elseif ($age >= 40) $value = $this->t("40-50");
			elseif ($age >= 35) $value = $this->t("35-39");
			elseif ($age >= 30) $value = $this->t("30-34");
			elseif ($age >= 25) $value = $this->t("25-29");
			elseif ($age >= 20) $value = $this->t("20-24");
			elseif ($age >= 18) $value = $this->t("18-19");
			elseif ($age >= 16) $value = $this->t("16-17");
			elseif ($age >= 12) $value = $this->t("12-15");
			elseif ($age >= 5) $value = $this->t("5-11");
			elseif ($age >= 3) $value = $this->t("3-4");
			elseif ($age >= 1) $value = $this->t("1-2");
			else $value = $this->t("<1");
			return $value;
		} else {
			if ($value==null) $value = $this->t("empty");
			elseif ($value > $now+3600*24*365) $value = $this->t("afterMoreThan365days");
			elseif ($value > $now+3600*24*90) $value = $this->t("afterMoreThan90days");
			elseif ($value > $now+3600*24*60) $value = $this->t("afterMoreThan60days");
			elseif ($value > $now+3600*24*30) $value = $this->t("afterMoreThan30days");
			elseif ($value > $now+3600*24*7) $value = $this->t("afterMoreThan7days");
			elseif ($value > $now+3600*24) $value = $this->t("after24H");
			elseif ($value > $now-3600*24) $value = $this->t("inThose24H");
			elseif ($value > $now-3600*24*7) $value = $this->t("inTheLast7days");
			elseif ($value > $now-3600*24*30) $value = $this->t("inTheLast30days");
			elseif ($value > $now-3600*24*60) $value = $this->t("inTheLast60days");
			elseif ($value > $now-3600*24*90) $value = $this->t("inTheLast90days");
			elseif ($value > $now-3600*24*365) $value = $this->t("inTheLast365days");
			else $value = $this->t("before365days");
			return $value;
		}
	}
	public function doFormatForTimeRanges($isAllDay, $begTime, $endTime, $begDate, $endDate, $doRegroupSimilarValue){
		$now = time();

		$d = $m = $y = $h = $i = $s = null;
		if($begTime!=null && Dates::fromString($begDate.(!$isAllDay ? " ".$begTime : ""), $d, $m, $y, $h, $i, $s)){
			if($h || $i || $s) $time = "$h:$i:$s";
			else $time = "";
			$value = strtotime("$y/$m/$d $time");
		} else {
			$value = null;
		}
		$d = $m = $y = $h = $i = $s = null;
		if($endDate!=null && Dates::fromString($endDate.(!$isAllDay ? " ".$endTime : ""), $d, $m, $y, $h, $i, $s)){
			if($h || $i || $s) $time = "$h:$i:$s";
			else $time = "";
			$endValue = strtotime("$y/$m/$d $time");
		} else {
			$endValue = null;
		}
		if(!$doRegroupSimilarValue){
			if ($value==null) return null;
			if($isAllDay){
				return date("d.m.Y", $value).($begDate!=$endDate && $endValue ? ' <img src="'.SITE_ROOT_forFileUrl.'images/gui/arrow-right-0.png" alt=">" /> '.date("d.m.Y", $endValue) : "");
			} else {
				return date(($begTime ? "d.m.Y H:i" : "d.m.Y"), $value).($begDate!=$endDate && $endValue ? ' <img src="'.SITE_ROOT_forFileUrl.'images/gui/arrow-right-0.png" alt=">" /> '.date(($endTime ? "d.m.Y H:i" : "d.m.Y"), $endValue) : "");
			}
		}
		if ($value==null) $value = $this->t("empty");
		elseif ($value > $now+3600*24*365) $value = $this->t("afterMoreThan365days");
		elseif ($value > $now+3600*24*90) $value = $this->t("afterMoreThan90days");
		elseif ($value > $now+3600*24*60) $value = $this->t("afterMoreThan60days");
		elseif ($value > $now+3600*24*30) $value = $this->t("afterMoreThan30days");
		elseif ($value > $now+3600*24*7) $value = $this->t("afterMoreThan7days");
		elseif ($value > $now+3600*24) $value = $this->t("after24H");
		elseif ($value > $now-3600*24) $value = $this->t("inThose24H");
		elseif ($value > $now-3600*24*7) $value = $this->t("inTheLast7days");
		elseif ($value > $now-3600*24*30) $value = $this->t("inTheLast30days");
		elseif ($value > $now-3600*24*60) $value = $this->t("inTheLast60days");
		elseif ($value > $now-3600*24*90) $value = $this->t("inTheLast90days");
		elseif ($value > $now-3600*24*365) $value = $this->t("inTheLast365days");
		else $value = $this->t("before365days");
		return $value;
	}
	public function doFormatForTime($value, $doRegroupSimilarValue = true){
		if($doRegroupSimilarValue){
			if ($value==null) $value = $this->t("empty");
			elseif ($value >= 12 || $value >= "12:00" ) $value = $this->t("afternoon");
			else $value = $this->t("morning");
			return $value;
		} else {
			return Times::formatDisplay($value, "hh:mm");
		}
	}
	//fieldName is used to define a title to the page
	public function wrapHtmlFilesWithHeader($content, $filename){
		$returnValue = $this->preHtmlHeader($filename);
		$returnValue .=  $content;
		$returnValue .= $this->postHtmlHeader();
		return $returnValue;
	}
	public function preHtmlHeader($filename){
		$returnValue = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html>';
		$returnValue .= '<head><title>' . $filename . '</title>';
		$returnValue .= '<meta http-equiv="content-type" content="text/html;charset=utf-8" />';
		if(defined("PREVENT_INDEXING") && PREVENT_INDEXING) $returnValue .=  '<meta name="robots" content="none,noindex" />';
		$returnValue .=  '</head><style type="text/css">body { font-family:Arial; } p { margin:0px;} </style><body>';
		return $returnValue;
	}
	public function postHtmlHeader(){
		$returnValue =  "</body></html>";
		return $returnValue;
	}
	public function doFormatForFileSize($value, $doRegroupSimilarValue = false){
		//we do a groupement on the size
		if(!$doRegroupSimilarValue){
			if ($value==null) {
				return null;
			}
			return formatFileSize($value);
		}
		if ($value==null) {
			$value = $this->t("empty");
		} elseif ($value > 1024*100) $value = "> ".formatFileSize(1024*100);
		else if ($value > 1024*50) $value = "> ".formatFileSize(1024*50);
		else if ($value > 1024*20) $value = "> ".formatFileSize(1024*20);
		else if ($value > 1024*10) $value = "> ".formatFileSize(1024*10);
		else if ($value > 1024*5) $value = "> ".formatFileSize(1024*5);
		else if ($value > 1024*2) $value = "> ".formatFileSize(1024*2);
		else if($value > 1024) $value = "> ".formatFileSize(1024);
		else $value = "< ".formatFileSize(1024);
		return $value;
	}
	public function doFormatForFloat($value, $doRegroupSimilarValue = false){
		//we do a groupement on the size
		if(!$doRegroupSimilarValue){
			return $value;
		}
		if ($value===null) {
			$value = $this->t("empty");
		} elseif ($value >= 1000000000) $value = ">= ".number_format(1000000000, 0, ".", "'");
		elseif ($value >= 1000000) $value = ">= ".number_format(1000000, 0, ".", "'");
		elseif ($value >= 1000) $value = ">= ".number_format(1000, 0, ".", "'");
		elseif ($value >= 100) $value = ">= ".number_format(100, 0, ".", "'");
		elseif ($value >= 10) $value = ">= ".number_format(10, 0, ".", "'");
		elseif ($value >= 1) $value = ">= ".number_format(1, 0, ".", "'");
		elseif ($value >= 0.1) $value = ">= ".number_format(0.1, 1, ".", "'");
		elseif ($value >= 0.01) $value = ">= ".number_format(0.01, 2, ".", "'");
		elseif ($value >= 0.001) $value = ">= ".number_format(0.001, 3, ".", "'");
		elseif ($value > 0) $value = "> 0";
		elseif ($value == 0) $value = "= 0";
		elseif ($value < -1000000000) $value = "< ".number_format(-1000000000, 0, ".", "'");
		elseif ($value < -1000000) $value = "< ".number_format(-1000000, 0, ".", "'");
		elseif ($value < -1000) $value = "< ".number_format(-1000, 0, ".", "'");
		elseif ($value < -100) $value = "< ".number_format(-100, 0, ".", "'");
		elseif ($value < -10) $value = "< ".number_format(-10, 0, ".", "'");
		elseif ($value < -1) $value = "< ".number_format(-1, 0, ".", "'");
		elseif ($value < -0.1) $value = "< ".number_format(-0.1, 1, ".", "'");
		elseif ($value < -0.01) $value = "< ".number_format(-0.01, 2, ".", "'");
		elseif ($value < -0.001) $value = "< ".number_format(-0.001, 3, ".", "'");
		elseif ($value < 0) $value = "< 0";
		return $value;
	}
	public function doFormatForNumeric($value, $xml = array(), $doRegroupSimilarValue = false){
		//we do a groupement on the size
		if(!$doRegroupSimilarValue){
			//check if there is any E in the $value. In that case round the value (prevent rounding errors with values almost around 0
			if(strpos($value,'E')) $value = round($value,2);
		    $decimalPosition = strpos($value,".");
			if($decimalPosition===false && $value!==null && $value!==""){
				$value .= ".00";
				$decimalPosition = strpos($value,".");
			}
			if($xml["noDecimals"]=="1"){
				$value = substr($value, 0, $decimalPosition);
			} else {
				//limit to 2 digit (could be more if based on calculation)
				$value = substr($value, 0, $decimalPosition+3);
			}
			if($xml["noThousandSeparator"]=="1") return $value;
			//do to precision constraint the number_format will mix up the number if there are too large
			return preg_replace("/(?<=\d)(?=(\d{3})+(?!\d))/","'",$value); // number_format($value, 2, ".", "'");
		}
		if ($value===null) {
			$value = $this->t("empty");
		} elseif ($value >= 1000000000) $value = ">= ".number_format(1000000000, 0, ".", "'");
		elseif ($value >= 1000000) $value = ">= ".number_format(1000000, 0, ".", "'");
		elseif ($value >= 1000) $value = ">= ".number_format(1000, 0, ".", "'");
		elseif ($value >= 100) $value = ">= ".number_format(100, 0, ".", "'");
		elseif ($value >= 10) $value = ">= ".number_format(10, 0, ".", "'");
		elseif ($value >= 1) $value = ">= ".number_format(1, 0, ".", "'");
		elseif ($value > 0) $value = "> 0";
		elseif ($value == 0) $value = "= 0";
		elseif ($value < -1000000000) $value = "< ".number_format(-1000000000, 0, ".", "'");
		elseif ($value < -1000000) $value = "< ".number_format(-1000000, 0, ".", "'");
		elseif ($value < -1000) $value = "< ".number_format(-1000, 0, ".", "'");
		elseif ($value < -100) $value = "< ".number_format(-100, 0, ".", "'");
		elseif ($value < -10) $value = "< ".number_format(-10, 0, ".", "'");
		elseif ($value < -1) $value = "< ".number_format(-1, 0, ".", "'");
		elseif ($value < 0) $value = "< 0";
		return $value;
	}
	//additionalValue could be added in order to add after the check mark the value (usefull in listView for mouseover)
	public function doFormatForBoolean($value, $doRegroupSimilarValue = false, $additionalValue=null){
		if($value){
			$value = '<img align="absmiddle" src="'.SITE_ROOT_forFileUrl.'images/icones/18px/accept.png"/>';
		} else {
			$value = '<img align="absmiddle" src="'.SITE_ROOT_forFileUrl.'images/icones/18px/cancel.png"/>';
		}
		if($additionalValue){
			$value .= '<br /><br />'.$additionalValue;
		}
		return $value;
	}
	public function doFormatForType($value, $doRegroupSimilarValue = false){
		if($value){
			if(file_exists('./images/preview/prev.18'.$value.'.png')){
				$value = '<img align="absmiddle" src="'.SITE_ROOT_forFileUrl.'images/preview/prev.18'.$value.'.png"/>';
			} else {
				$value = '<img align="absmiddle" src="'.SITE_ROOT_forFileUrl.'images/preview/prev.18.png"/>';
			}
		} else {
			$value = '';
		}
		return $value;
	}
	public function doFormatForThumbs($id, $fieldname){
		return '<img align="absmiddle" src="'.SITE_ROOT.'/'.$this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceUrl().'/'.$this->getExecutionService()->getCrtModule()->getModuleUrl().'/download/'.$id.'/'.$fieldname.'/thumbs"/>';
	}
	public function doFormatForIsDocAvailable($value, $doRegroupSimilarValue = false){
		if($value){
			$value = '<img align="absmiddle" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/status/mail-attachment.png"/>';
		} else {
			$value = '';
		}
		return $value;
	}
	public function doFormatForState($state, $value, $doRegroupSimilarValue = false, $onlyIcons=false){
		if($value){
			//$value = '<img align="absmiddle" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/emblems/emblem-'.$state.'.png"/>';
			//$value = '<span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;">'.$this->t('state_'.$state).'</span>';
			switch($state) {
				case 'locked':
					$value = '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-locked.png" />';
					if(!$onlyIcons){
						$value .= '&nbsp;<span class="tag ui-corner-all" style="background-color:#CC4B4B;padding:2px 10px 2px 10px;vertical-align:5px;white-space:nowrap;margin-bottom:4px;margin-right:5px;">';
						$value .= $this->t('state_locked');
						$value .= '</span>';
					}
					break;
				case 'blocked':
					$value = '<img alt="blocked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/documents/document-denied.png" />';
					if(!$onlyIcons){
						$value .= '&nbsp;<span class="tag ui-corner-all" style="background-color:#EA2424;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">';
						$value .= $this->t('state_blocked');
						$value .= '</span>';
					}
					break;
				case 'important1':
					$value = '<img alt="important1" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-important1.png" />';
					if(!$onlyIcons){
						$value .= '&nbsp;<span class="tag ui-corner-all" style="background-color:#F5B06D;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;;margin-bottom:4px;margin-right:5px;">';
						$value .= $this->t('state_important1');
						$value .= '</span>';
					}
					break;
				case 'important2':
					$value = '<img alt="important2" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-important2.png" />';
					if(!$onlyIcons){
						$value .= '&nbsp;<span class="tag ui-corner-all" style="background-color:#F57E0B;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;;margin-bottom:4px;margin-right:5px;">';
						$value .= $this->t('state_important2');
						$value .= '</span>';
					}
					break;
				case 'approved':
					$value = '<img alt="approved" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/status/available.png" />';
					if(!$onlyIcons){
						$value .= '&nbsp;<span class="tag ui-corner-all" style="background-color:#A0E061;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">';
						$value .= $this->t('state_approved');
						$value .= '</span>';
					}
					break;
				case 'finalized':
					$value = '<span class="tag ui-corner-all" style="background-color:#008AB8;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">';
					//$value .= '<img alt="finalized" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/emblems/emblem-finalized.png" />&nbsp;';
					$value .= $this->t('state_finalized');
					$value .= '</span>';
					break;
				case 'dismissed':
					$value = '<img alt="dimissed" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-unreadable.png" />';
					if(!$onlyIcons){
						$value .= '&nbsp;<span class="tag ui-corner-all" style="background-color:#EA2424;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">';
						$value .= $this->t('state_dismissed');
						$value .= '</span>';
					}
					break;
				case 'deprecated':
					$value = '<span class="tag ui-corner-all" style="background-color:#FFCC33;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">';
					//$value .= '<img alt="deprecated" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/emblems/emblem-deprecated.png" />&nbsp;';
					$value .= $this->t('state_deprecated');
					$value .= '</span>';
					break;
				case 'archived':
					$value = '<span class="tag ui-corner-all" style="background-color:#95CAE4;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">';
					//$value .= '<img alt="archived" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/emblems/emblem-archived.png" />&nbsp;';
					$value .= $this->t('state_archived');
					$value .= '</span>';
					break;
				case 'hidden':
					$value = '<span class="tag ui-corner-all" style="border: 1px solid #A3A396;background-color:transparent;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">';
					//$value .= '<img alt="hidden" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/emblems/emblem-hidden.png" />&nbsp;';
					$value .= $this->t('state_hidden');
					$value .= '</span>';
					break;
			}
		} else {
			$value = '';
		}
		return $value;
	}
	//$labelDBValue is to find the right color in case there is color attributes
	public function doFormatForTag($value, $xml, $labelDBValue=null){
		if($value==null) return;	
		// Modified by AC on 07.19.2016 we remove the rule css "white-space: nowrap" to prevent the sector tag be too long
		//$preFix = '<span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;'.$xml["tagStyle"].'" >';
		if(is_array($value)){
			//the labelDBValue contains the list of options in the same order but not translated. We need to do the correct matching after to take only the values to have numeric fields.
			if($labelDBValue) $labelDBValue = array_values($labelDBValue);
			$returnValue = "";
			foreach($value as $key=>$val){
				if($val!=null && $val!="none"){
					$color = $xml->xpath('attribute[@color and (text()="'.($labelDBValue ? $labelDBValue[$key] : $value[$key]).'")]');
					if($color){ $color = (string)$color[0]["color"]; }
					$returnValue .= '<span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;float:left;margin-bottom:4px;margin-right:5px;'.$xml["tagStyle"].($color ? "background-color:#".$color.";color:#".getBlackOrWhiteFromBackgroundColor($color).";" : "").'" >'.$value[$key].'</span> ';
				}
			}
			return $returnValue;
		} else {
			if($value!=null && $value!="none"){
				$color = $xml->xpath('attribute[@color and (text()="'.($labelDBValue ? $labelDBValue : $value).'")]');
				if($color){ $color = (string)$color[0]["color"]; }
				return '<span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;float:left;margin-bottom:4px;margin-right:5px;'.$xml["tagStyle"].($color ? "background-color:#".$color.";color:#".getBlackOrWhiteFromBackgroundColor($color).";" : "").'" >'.$value.'</span> ';
			}
		}
	}

	/**
	 * int colorCode: 1 = Green
	 * int colorCode: 2 = Yellow
	 * int colorCode: 3 = Orange
	 * int colorCode: 4 = Red
	 */
	public function doFormatForProgressBar($value, $colorCode, $doRegroupSimilarValue = false, $isFlatProgressBar = false, $width=200, $height=4){
		if($value===null) return "";
		if($value == null) $value = 0;
		//$value = "<script>display ('element1',$value,$colorCode);</script>";
		$imageWidth=240;
		$initial=-120;
		$eachPercent = ($imageWidth/2)/100;
		$percentageWidth = $eachPercent * min(100, $value);
    	$actualWidth = $initial + $percentageWidth ;
    	$value = round($value,0);
    	if($colorCode==null){
    		if($value < 25){
    			$colorCode = 4;
    		} else if ($value < 50){
    			$colorCode = 3;
    		} else if ($value < 75){
    			$colorCode = 2;
    		} else {
    			$colorCode = 1;
    		}
    	}
    	if($isFlatProgressBar){
    	    $percentageWidth = ($value / 100)*$width;
    	    $status = $value. $this->t('p_of_time');
    	    if($value>=100)
    	        $status=$this->t('time_complete');
    	    if($value < 0){
                $status=$this->t('not_started_yet');
                $percentageWidth = 0;
            }
            if($value == -1){
    	        $status = $this->t('open_ended');
                $value = '<p>'. $status. '</p>';
                $value .= '<div style="width: '.$width.'px; margin: 0; height: '.$height.'px; background: lightgrey; padding: 0;" class="progressBarBackground"></div>';
            }else{
                $value = '<p>'. $status. '</p>';
                $value .= '<div style="width: '.$width.'px; margin: 0; height: '.$height.'px; background: #FCDF80; padding: 0;" class="progressBarBackground"><div style="background: #C3AC5F; margin: 0; padding: 0; height: '.$height.'px; width: '. $percentageWidth. 'px;" class="progressBarProgression">&nbsp;</div></div>';
            }
        } else{
            $value = '<img src="'.SITE_ROOT_forFileUrl.'/assets/css/images_progressBar/percentImage.png" alt="'.$value.'%" class="percentImage'.$colorCode.'" style="background-position: '.$actualWidth.'px 0pt;"/> <span>'.$value.'%</span>';
        }
        return $value;
	}
	public function formatValueFromFS($fieldSelector, $record, $doRegroupSimilarValue = false, $idsAreNew=false){
//		if(!$fieldSelector) return;
		$fieldName = $fieldSelector->getFieldName();
		$subFieldName = $fieldSelector->getSubFieldName();
		if($fieldSelector->isElementAttributeSelector()){
			$value = $record->getAttribute($fieldSelector);
			$field = null;
		} else {
			if($idsAreNew){
				$value = $record->getFieldValueBeforeInsert($fieldName, $subFieldName);
			} else {
				$value = $record->getFieldValue($fieldName, $subFieldName);
			}
			$field = $record->getFieldList()->getField($fieldName);
		}
		return $this->formatValue($fieldName, $subFieldName, $value, $field, $doRegroupSimilarValue);
	}
	public function formatValueFromRecord($fieldName, $subFieldName, $record, $doRegroupSimilarValue = false){
		$value = $record->getFieldValue($fieldName, $subFieldName);
		$field = $record->getFieldList()->getField($fieldName);
		return $this->formatValue($fieldName, $subFieldName, $value, $field, $doRegroupSimilarValue);
	}
	public function formatValueToPreventInjection($value){
		//prevent any html or script injection
		if(is_string($value)){
			$value = str_replace('&amp;#92;','\\' ,stripslashes(htmlspecialchars($value, ENT_QUOTES, "UTF-8")));
			//$value = stripslashes(htmlspecialchars($value, ENT_QUOTES, "UTF-8"));
		} else if(is_array($value)) {
			foreach($value as $i=>$j){
				$value[$i] = str_replace('&amp;#92;','\\' ,stripslashes(htmlspecialchars($j, ENT_QUOTES, "UTF-8")));
				//$value[$i] = stripslashes(htmlspecialchars($j, ENT_QUOTES, "UTF-8"));
			}
		} else if(is_bool($value) || is_numeric($value)){
			//nothing to do
		} else if($value){
			//prevent displaying any special types
			throw new ServiceException("invalid value type: ".put($value).":".$value.":", ServiceException::UNEXPECTED_ERROR);
		}
		return $value;
	}
	public function formatValue($fieldName, $subFieldName, $value, $field, $doRegroupSimilarValue = false){
		$exec = $this->getExecutionService();
		if($this->getDetailRenderer()){
			$fieldId = $this->getDetailRenderer()->getDetailId()."__".$fieldName;
		} else {
			$fieldId = null;
		}

		if($field != null){
			$typename = $field->getDataType()->getDataTypeName();
		} else {
			$typename = $fieldName;
		}
		$value = $this->formatValueToPreventInjection($value);

		if($field!=null) $xml = $field->getXml();
		else $xml = array();

		//the MultipleAttributs case is handled separately in the datatype method
		if($xml["displayDBValue"]=="1" && $typename!="MultipleAttributs"){
			return $value;
		}

		//check sys fields:
		if($subFieldName=="sys_user" || $subFieldName=="sys_username" ||
			$subFieldName=="sys_creationUser" || $subFieldName=="sys_creationUsername"){
				return $this->doFormatForText($value);
		}
		if($subFieldName=="sys_date" || $subFieldName=="sys_creationDate"){
				return $this->doFormatForDate($value, $doRegroupSimilarValue, false, true);
		}
		//eput($fieldName." ".$subFieldName." ".$typename." ".$doRegroupSimilarValue);
		switch($typename){
			case "Addresses":
				switch($subFieldName){
					case "street":
					case "zip_code":
					case "city":
					case "state":
					case "country":
						return $this->doFormatForText($value);
						break;
					default:
						if($this->getRecord()!=null){
							//create a general file summary
							$street = $this->getRecord()->getFieldValue($fieldName, "street");
							$zip_code = $this->getRecord()->getFieldValue($fieldName, "zip_code");
							$city = $this->getRecord()->getFieldValue($fieldName, "city");
							$state = $this->getRecord()->getFieldValue($fieldName, "state");
							$country = $this->getRecord()->getFieldValue($fieldName, "country");
							return ($street ? nl2br($street)."<br />" : '').($zip_code ? $zip_code."&nbsp;" : '').($city ? $city."<br />" : '').$state.($state && $country ? " / " : '').$country;
						}
						break;
				}
				break;
			case "Attributs":
				//Attributes value need translation
				return Attributs::formatDisplay($value, $field);
				break;
			case "Blobs":
			case "Texts":
				if($xml["htmlArea"]=="1"){
					return $this->doFormatForHtmlText($value, $xml["deactivateHTMLPurifier"]!="1");
				} else {
					return $this->doFormatForText($value);
				}
				break;
			case "Links":
				return $this->doFormatForLinks($value, $xml, $doRegroupSimilarValue);
				break;
			case "Floats":
				return $this->doFormatForFloat($value, $doRegroupSimilarValue);
				break;
			case "Numerics":
				if($xml["isProgressBar"]=="1" && !$this->isForNotification()){
					return $this->doFormatForProgressBar($value, $xml["progressBarColor"], $doRegroupSimilarValue, $xml["isFlatProgressBar"], 250, 4);
				} else {
					return $this->doFormatForNumeric($value, $xml, $doRegroupSimilarValue);
				}
				break;
			case "Booleans":
				return $this->doFormatForBoolean($value, $doRegroupSimilarValue);
				break;
			case "Dates":
// 				if($this->getRecord()!=null){
// 					$xml = $this->getRecord()->getFieldList()->getField($fieldName)->getXml();
// 				}
				$temp = $this->doFormatForDate($value, $doRegroupSimilarValue, $xml["isBirthDate"]=="1", $xml["includeTime"]=="1");
				return $temp;
				break;
			case "Emails":
				switch($subFieldName){
					case null:
					case "value":
						if(!$value) return $value;
						if($xml["isMultiple"]=="1"){
							$emails = array();
							$emailsSplitted = preg_split("/".ValueListArrayMapper::Natural_Separators."/", $value);
							foreach ($emailsSplitted as $email) {
								$emails[] = '<a href="mailto:'.$email.'" >'.$email.'</a>';
							}
							$value = implode(", ", $emails);
						} else {
							$value = '<a href="mailto:'.$value.'" >'.$value.'</a>';
						}
						return $value;
						break;
					case "proofStatus":
						$image = null;
						$displayNotConfirmIcon = (int)$this->getConfigService()->getParameter($this->getP(), $this->getRecord()->getModule(), 'displayNotConfirmedIcon')==1;
						if($value == Emails::PROOF_STATUS_DELETED){
							$image = '<img align="absmiddle" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/status/dialog-error.png"';
							$image .= ' title="'.$this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "proof")).'" />';
						} else if($value == Emails::PROOF_STATUS_VALIDATED){
							$image = '<img align="absmiddle" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/status/available.png"';
							$image .= ' title="'.$this->t("emailIsValidated").'&#xA;'.$this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "proof")).'" />';
						} else {
							//vide sauf si displayNotConfirmedIcon=1
 							if($displayNotConfirmIcon) {
								$image = '<img style="" align="absmiddle" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/status/info.png"';
								$image .= ' title="'.$this->t("emailIsNotValidated").'" />';
							}
						}
						//$image .= '/>';
						return $image;
						break;
					case "externalAccessEndDate":
						return $this->doFormatForDate($value, $doRegroupSimilarValue, false, false);
						break;
					case "externalAccessLevel":
						$externalAccessEndDate = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "externalAccessEndDate"));
						if($externalAccessEndDate){
							if($externalAccessEndDate < time()){
								//external access has been reached -> value = 0;
								$value = 0;
							} else {
								$externalAccessEndDate = $this->doFormatForDate($externalAccessEndDate, false, false, false);
							}
						}

						$externalAccessCode = $this->getRecord()->getFieldValue($fieldName, "externalCode");
						$externalAccessLink = $this->getElementService()->getExternalAccessLinkFromCode($this->getP(), $this->getExecutionService()->getCrtWigiiNamespace(), $this->getExecutionService()->getCrtModule(), $externalAccessCode);

						//public editable, if it's on read only do not show
						if($value == 2){
							$image = '<img class="H" '.(!$this->isForNotification() && !$this->isForListView() && !$this->isForPreviewList() && !$this->isForExternalAccess() && $this->getDetailRenderer()->getRecordIsWritable() ? ' onclick="displayLink($(this).parent(), \''.str_replace("//", '\/\/', $externalAccessLink).'/edit/'.$this->getTranslationService()->getLanguage().'\'); event.stopPropagation();" ' : '').' align="absmiddle" src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/emblems/globe-edit.png"';
							$image .= ' title="'.$this->t("thisElementCanBeEditedExternalyThroughACodedLink").($externalAccessEndDate ? '&#xA;'.$this->t("availableTill")." ".$externalAccessEndDate.'' : '').'" ';
							$image .= '/>';
							//$exec->addJsCode("$('#".$idAnswer."')$findSelector('<a class=\"H el_sendLink\" href=\"javascript:mailToFromLink(\\'".$idAnswer."\\', \\'".str_replace("//", '\/\/', $elS->getUrlForElement($exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $element))."\\');\">".$transS->t($p, "sendLink")."</a>');");

						//public viewable
						} else if($value == 1){
							$image = '<img class="H" '.(!$this->isForNotification() && !$this->isForListView() && !$this->isForPreviewList() && !$this->isForExternalAccess() ? ' onclick="displayLink($(this).parent(), \''.str_replace("//", '\/\/', $externalAccessLink).'/view/'.$this->getTranslationService()->getLanguage().'\'); event.stopPropagation();"' : '').' align="absmiddle" src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/emblems/webview.png"';
							$image .= ' title="'.$this->t("thisElementCanBeViewedExternalyThroughACodedLink").($externalAccessEndDate ? '&#xA;'.$this->t("availableTill")." ".$externalAccessEndDate.'' : '').'" ';
							$image .= '/>';
						//no public access
						} else {
							$image = '';
						}
						return $image;
						break;
					case "externalCode":
						//display the email menu:
						if(!$this->isForNotification() && $this->getRecord()!=null){

							$proofStatus = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "proofStatus"));
							if($proofStatus == Emails::PROOF_STATUS_DELETED) return null;

							$externalAccessLevel = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "externalAccessLevel"));
							$externalAccessEndDate = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "externalAccessEndDate"));
							$proofKey = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "proofKey"));
							$proofStatus = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "proofStatus"));
							$externalCode = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "externalCode"));
							$email = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "value"));

							if(!$this->isForNotification() && !$this->isForListView() && !$this->isForPreviewList() && !$this->isForExternalAccess()){

								$menuItems = array();
								$menuItems[] = "externalAccessMenusendEmail";

								if($xml["isMultiple"]!="1"){
									if($proofStatus==Emails::PROOF_STATUS_NONE){
										//propose to send a validation link
										$menuItems[] = "externalAccessMenuValidationLink";
									}
								}
								if($externalAccessEndDate) {
									if($externalAccessEndDate < time()){
										$externalAccessEndDate = null; //don't propose to setup a past date
									} else {
										$externalAccessEndDate = $this->doFormatForDate($externalAccessEndDate, false, false, false);
									}
								}

								if($xml["maxExternalAccessLevel"]=="0"){
									$maxExternalAccessLevel = Emails::EXTERNAL_ACCESS_STOP;
								} else if($xml["maxExternalAccessLevel"]=="1"){
									$maxExternalAccessLevel = Emails::EXTERNAL_ACCESS_VIEW;
								} else {
									$maxExternalAccessLevel = Emails::EXTERNAL_ACCESS_EDIT;
								}
								if($maxExternalAccessLevel >= Emails::EXTERNAL_ACCESS_VIEW){ // && $externalAccessLevel != Emails::EXTERNAL_ACCESS_VIEW){
									//since 20/11/2014 we allow to send again an email with the link even if it is already activated. $externalAccessLevel != Emails::EXTERNAL_ACCESS_VIEW &&
									//propose to send a view link
									$menuItems[] = "externalAccessMenuViewLink";
								}
								if($maxExternalAccessLevel >= Emails::EXTERNAL_ACCESS_EDIT){ // && $externalAccessLevel != Emails::EXTERNAL_ACCESS_EDIT){
									//since 20/11/2014 we allow to send again an email with the link even if it is already activated. $externalAccessLevel != Emails::EXTERNAL_ACCESS_EDIT &&
									//propose to send a change link
									$menuItems[] = "externalAccessMenuEditLink";
								}

								if($maxExternalAccessLevel > Emails::EXTERNAL_ACCESS_STOP && $externalAccessLevel > Emails::EXTERNAL_ACCESS_STOP){
									//propose a stop external possible external view
									$menuItems[] = "externalAccessMenuStop";
								}
								$image = null;
								if($menuItems){
									$emailManageButtonId = 'emailManage_'.$this->getRecord()->getId().'_'.$fieldName.'_button';
									$image = '<img id="'.$emailManageButtonId.'" class="ui-corner-all SBB" style="cursor:pointer;margin-left:2px;margin-right:5px;padding:1px 3px 1px 3px;" align="absmiddle" src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/actions/send-email-user-other.png"';
									$image .= ' onmouseover="$(this).addClass(\'over\'); showHelp(this, \''.$this->h("externalAccessMenusendEmailHelp").'\');" ';
									$image .= ' onclick="" ';
									$image .= ' onmouseout="$(this).removeClass(\'over\'); hideHelp();" ';
									$image .= '/>';

									$options = "";
									foreach($menuItems as $item){
										//$options .= "<input type='radio' name='".$emailManageButtonId."[]' value='$item' />".$this->t($item).'<br />';
										$options .= '<input type="radio" name="'.$emailManageButtonId.'" value="'.$item.'" /><span style="cursor:pointer;margin-top:-2px;" onclick="$(this).prev().click();"> '.($item == "externalAccessMenuStop" && $externalAccessLevel==Emails::EXTERNAL_ACCESS_EDIT ? $this->h("externalAccessMenuStopEdit") : ($item == "externalAccessMenuStop" && $externalAccessLevel==Emails::EXTERNAL_ACCESS_VIEW ? $this->h("externalAccessMenuStopRead") : $this->h($item))).'</span><br />';
									}


									//all the other need of translation and html email content is callback to the server on the click of the menu
									//cf: getExternalAccessMenuContent
									$this->getExecutionService()->addJsCode("setListenerToEmailExternalCode('".$this->getExecutionService()->getIdAnswer()."', '$emailManageButtonId','$proofKey','$proofStatus','$externalCode','$externalAccessEndDate','$options','".$this->getTranslationService()->getLanguage()."','".$this->getRecord()->getId()."','".$fieldName."','".$email."');");
								}
								return $image;
							} else {
								return $externalCode;
							}
						}
						break;
				}
				return $this->doFormatForText($value);
				break;
			case "Files":
				switch($subFieldName){
					case "name":
					case "content":
						return $this->doFormatForText($value);
						break;
					case "thumbnail":
						if($this->getRecord()!=null){
							return $this->doFormatForThumbs($this->getRecord()->getId(), $fieldName);
						}
						break;
					case "textContent":
						if($xml["htmlArea"]=="1"){
							return $this->doFormatForHtmlText($value, $xml["deactivateHTMLPurifier"]!="1");
						} else {
							return $this->doFormatForText($value);
						}
						break;
					case "size":
						return $this->doFormatForFileSize($value, $doRegroupSimilarValue);
						break;
					case "date":
						return $this->doFormatForDate($value, $doRegroupSimilarValue, false, true);
						break;
					case "mime":
						return $this->doFormatForType(mime2ext($value), $doRegroupSimilarValue);
						break;
					case "path":
						return $this->doFormatForIsDocAvailable($value, $doRegroupSimilarValue);
						break;
					case "":
						if($this->getRecord()!=null){
							//create a general file summary
							$octet = $this->getRecord()->getFieldValue($fieldName, "size");
							$size = $this->doFormatForFileSize($octet);
							$date = $this->doFormatForDate($this->getRecord()->getFieldValue($fieldName, "date"));
							$path = $this->getRecord()->getFieldValue($fieldName, "path");
							$name = $this->getRecord()->getFieldValue($fieldName, "name");
							$type = $this->getRecord()->getFieldValue($fieldName, "type");
							$textContent = null;
							$preview = null;

							//return null if empty
							if(!$date && !$size) return null;

							if (file_exists("./images/preview/prev.18$type.png")){
								$prevPath = SITE_ROOT_forFileUrl."images/preview/prev.18".$type.".png";
							} else {
								$prevPath = SITE_ROOT_forFileUrl."images/preview/prev.18.jpg";
							}

							//the ssrc is used for the media unzip, pdf, or html type
							if($this->isForExternalAccess()) {
								$ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/downloadFromExternalAccess/".$exec->getCrtParameters(0)."/download/".$this->getRecord()->getId()."/".$fieldName;
							} else {
								$ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/".$this->getRecord()->getId()."/".$fieldName;
							}
							$src = SITE_ROOT.$ssrc;

							if($type == ".pdf" || $type == ".csv" || $type == ".sql" || $type == ".config" || $type == ".xml" || $type == ".bat" || $type == ".dat" || $type == ".data" || $type == ".html" || $type == ".htm" || $type == ".svg" || $type == ".txt" || $type == ".jpg"  || $type == ".jpeg"  || $type == ".gif"  || $type == ".png"  || $type == ".bmp"){
								//$textContent = '</div></div><div style="width:100%" class="field" >'.$this->doFormatForHtmlText($this->getRecord()->getFieldValue($fieldName, "textContent")).'</div><div><div>';
								//display content
								$previewClass = 'htmlPreview';
								if($xml["htmlArea"]=="1"){
									if($xml["displayContentInDetail"]=="1"){
										$textContent = '</div></div><div style="width:100%" class="field" >'.$this->doFormatForHtmlText($this->getRecord()->getFieldValue($fieldName, "textContent"), $xml["deactivateHTMLPurifier"]!="1").'</div><div><div>';
									}
								} else if($type == ".jpg"  || $type == ".jpeg"  || $type == ".gif"  || $type == ".png"  || $type == ".bmp"){
									if($xml["displayContentInDetail"]=="1"){
										$textContent = '</div></div><div style="width:100%;" class="field" ><img style="vertical-align: bottom; max-width:100%;" class="field" src="'.$ssrc.'" /></div><div><div>';
									}
									$previewClass = 'imgPreview';
								} else if($type == ".svg" || $type == ".txt"){
									if($octet < 1024*1024){
										$textContent = '</div></div><div style="width:100%" class="field" >'.file_get_contents(FILES_PATH.$path).'</div><div><div>';
									}
								}
								//add preview
								$preview = ' <img class="H read '.$previewClass.'" style="vertical-align: bottom;" title="'.$this->t("detailViewButton").'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/system-search.png" /> ';
								if($fieldId && !$this->isForPreviewList()) {
									$exec->addJsCode("setListenerToPreviewFile('$fieldId', '$fieldName', '$src', '".time()."');");
								}
							}
							if($fieldId && !$this->isForPreviewList()) {
								$exec->addJsCode("setListenerToDownloadFile('$fieldId', '".$fieldName."', '$src');");
							}
							if($this->isForPreviewList()) {
								return '<img class="prev" style="vertical-align: bottom;" src="'.$prevPath.'" />'.$name.$type." (".$size.")".$textContent;
							}
							else return '<img class="prev" style="vertical-align: bottom;" src="'.$prevPath.'" /> <a class="H fileDownload" href="#" target="_self"> '.$name.$type." (".$size.")</a>".$preview.$textContent;
						}
						break;
				}
				break;
			case "MultipleAttributs":
				if($field == null) throw new ListException("try to create a goupBy title with empty field for MultipleAttribut", ListException::INVALID_ARGUMENT);
// 				if($this->getRecord()!=null){
// 					$xml = $this->getRecord()->getFieldList()->getField($fieldName)->getXml();
// 				}
				if($xml["displayAsTag"]=="1"){
					$dbValue = $value;
					$values = MultipleAttributs::formatDisplay($value, $field, true);
					return $this->doFormatForTag($values, $xml, $dbValue);
				} else {
					return MultipleAttributs::formatDisplay($value, $field);
				}
				break;
			case "Varchars":
			case "Strings":
// 				if($this->getRecord()!=null){
// 					$xml = $this->getRecord()->getFieldList()->getField($fieldName)->getXml();
// 				}
				if($xml["isBirthDay"]){
					return $this->doFormatForBirthDay($value, $doRegroupSimilarValue);
				} else if($xml["colorPicker"]=="1"){
					return $this->doFormatForColor($value);
				} else if($xml["htmlArea"]=="1"){
					return $this->doFormatForHtmlText($value, $xml["deactivateHTMLPurifier"]!="1");
				} else {
					return $this->doFormatForText($value, $doRegroupSimilarValue);
				}
				break;
			case "Times":
				return $this->doFormatForTime($value, $doRegroupSimilarValue);
				break;
			case "TimeRanges":
				switch($subFieldName){
					case "begDate":
					case "endDate":
						return $this->doFormatForDate($value, $doRegroupSimilarValue);
						break;
					case "begTime":
					case "endTime":
						return $this->doFormatForTime($value, $doRegroupSimilarValue);
					//default: throw new ListException("try to create a goupBy title with wrong parameter: $type : $subFieldName", ListException::INVALID_ARGUMENT);
				}
				//if no subfield is selected then create a concat between beg date + beg time
				//no problem of injection here as all will be checked as valid dates, and isAllDay is only checked as boolean
				return $this->doFormatForTimeRanges($this->getRecord()->getFieldValue($fieldName, "isAllDay"), $this->getRecord()->getFieldValue($fieldName, "begTime"), $this->getRecord()->getFieldValue($fieldName, "endTime"), $this->getRecord()->getFieldValue($fieldName, "begDate"), $this->getRecord()->getFieldValue($fieldName, "endDate"), $doRegroupSimilarValue);
				break;
			case "Urls":
				switch($subFieldName){
					case "name":
						return $this->doFormatForText($value);
						break;
					case null:
						$value = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "url"));
					case "url":
						if($value==null) return null;
						//build the link if available
						if($this->getRecord()!=null){
							$name = $this->formatValueFromRecord($fieldName, "name", $this->getRecord());
							$target = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "target"));
						}
						if($name == null) $name = $value;
						if($target =="") $target = "_blank";
						if($target =="_iframe") {
							$target = "_blank";
							if(!$this->isForNotification()){
								$this->getExecutionService()->addJsCode("$('#$fieldId>.value a:first').click(function(e){ previewIframe('$value'); e.stopPropagation(); e.preventDefault(); });");
							}
						}
						if($target == "_file"){
							if(!preg_match('#^((file)\://)#i', $value)) $value = "file:///".$value;
							$value = '<a href="'.$value.'" target="'.$target.'" ><font>'.$name.'</font></a>';
							break;
						}
						if(!preg_match('#^(((ht|f)tp(s?))://)#i', $value)) $value = "http://".$value;
						if(!$this->isForPrint() && !$this->isForNotification() && $target=="_video"){
							$src = $value;
							$mediaId = $fieldName.'_url_media';
							$width = $this->getDetailRenderer()->getTotalWidth();
							$value = '<a href="'.$value.'" target="'.$target.'" ><font>'.$name.'</font></a>';
							$value .= '</div>'; //end value div
							$value .= '<div style="float:left;padding-bottom:5px;padding-top:-5px;">'; //open a new div in field
							$value .= '<a class="SBB" href="'.$src.'" style="float:left;width: 100%; max-width:'.$width.'px;height:300px;" id="'.$mediaId.'"></a>';
							$this->getExecutionService()->addJsCode("displayVideoPlayer('$mediaId');");
						} else {
							$value = '<a href="'.$value.'" target="'.$target.'" ><font>'.$name.'</font></a>';
						}
						break;
					//default: throw new ListException("try to create a goupBy title with wrong parameter: $type : $subFieldName", ListException::INVALID_ARGUMENT);
				}
				break;
			case "__element":
				switch($subFieldName){
					case "sys_date":
					case "sys_creationDate":
						return $this->doFormatForDate($value, $doRegroupSimilarValue, false, true);
						break;
					case "state_locked":
					case "state_important1":
					case "state_important2":
					case "state_hidden":
					case "state_archived":
					case "state_deprecated":
					case "state_finalized":
					case "state_approved":
					case "state_dismissed":
					case "state_blocked":
						return $this->doFormatForState(str_replace("state_", "", $subFieldName), $value, $doRegroupSimilarValue);
					//other sys fields do not need to be transformed, or are normally never displayd
					//default: throw new ListException("try to create a goupBy title with wrong parameter: $type : $subFieldName", ListException::INVALID_ARGUMENT);
				}
				break;
		}

		if($value==null || $value=="none"){
			if($doRegroupSimilarValue) return $this->t("empty");
			return "";
		}
		return $value;
	}

	public function getBaseEmailSubject(){
		return CLIENT_NAME.' - '.($this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceName() ? $this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceName().' - ' : '').$this->t($this->getExecutionService()->getCrtModule()->getModuleName());
	}
	public function getSubscriptionConfirmationEmailSubject($subject=null){
		if(!$subject) $subject = $this->getBaseEmailSubject();
		return $subject.": ".$this->t("confirmationEmailSubject");
	}
	public function getValidationEmailSubject($subject=null){
		if(!$subject) $subject = $this->getBaseEmailSubject();
		return $subject.": ".$this->t("validateEmailSubject");
	}
	public function getSubscriptionConfirmationEmailHtml($body, $validationLink, $proofStatus, $externalAccessLink, $externalAccessLevel, $externalAccessEndDate, $forMailto=false){
		$newLine = "&nbsp;</p><p>";
		$preNewLine = "<p>";
		if($forMailto) {
			$newLine = "%0D%0A";
			$preNewLine = "";
			$body = str_replace("\n", $newLine, str_replace("\r", "", $body));
		}
		if(!$body){
			$title = $this->getBaseEmailSubject();
			if(!$forMailto) $body = "<h3>".$title."</h3>".$preNewLine;
			else $body = $title.$newLine.$newLine;
			$body .= $this->t("thankYouForYourSubscription").$newLine.$newLine;
		} else {
			$body .= $preNewLine.$newLine;
		}

		//add at the end the links:
		if($validationLink){
			if($proofStatus == Emails::PROOF_STATUS_NONE){
				$body .= $this->t("pleaseValidateYourEmailByClickingOnTheFollowingLink").": ";
				if(!$forMailto) $body .= '<a href="'.$validationLink.'/'.$this->getTranslationService()->getLanguage().'">'.$this->t("validationLink").'</a>';
				else $body .= $validationLink.'/'.$this->getTranslationService()->getLanguage();
			} else if ($proofStatus == Emails::PROOF_STATUS_VALIDATED){
				$body .= $this->t("yourEmailIsAlreadyValidated");
			} else if ($proofStatus == Emails::PROOF_STATUS_DELETED){
				$body .= $this->t("youHaveDeletedYourSubscription");
			}
			$body .= $newLine;
			$body .= $newLine;
		}
		if(!$externalAccessEndDate || $externalAccessEndDate > time()){
			if($externalAccessLink && $externalAccessLevel >= Emails::EXTERNAL_ACCESS_EDIT){
				$body .= $this->t("youCanUpdateYourDetailsByClickingOnTheFollowingLink").": ";
				if(!$forMailto) $body .= '<a href="'.$externalAccessLink.'/edit/'.$this->getTranslationService()->getLanguage().'">'.$this->t("externalAccessLink").'</a>';
				else $body .= $externalAccessLink.'/edit/'.$this->getTranslationService()->getLanguage();
				$body .= $newLine;
				$body .= $newLine;
			} else if($externalAccessLink && $externalAccessLevel >= Emails::EXTERNAL_ACCESS_VIEW){
				$body .= $this->t("youCanViewYourDetailsByClickingOnTheFollowingLink").": ";
				if(!$forMailto) $body .= '<a href="'.$externalAccessLink.'/view/'.$this->getTranslationService()->getLanguage().'">'.$this->t("externalAccessLink").'</a>';
				else $body .= $externalAccessLink.'/view/'.$this->getTranslationService()->getLanguage();
				$body .= $newLine;
				$body .= $newLine;
			}
		} else if($externalAccessEndDate){
			//end date is reached
			$body .= $this->t("theLinkToEditThisSubscriptionHasEndedOn")." <b>".date("d.m.Y", $externalAccessEndDate)."</b>";
			$body .= $newLine;
			$body .= $newLine;
		}

		if(!$forMailto) $body = $body."</p>";

		if(!$forMailto){
			$templatePath = $this->getConfigService()->getTemplatePath($this->getP(), null, $this->getBaseEmailActivity());
			ob_start();
			include($templatePath);
			$body = ob_get_clean();
		}
		return $body;
	}
	public function getValidationEmailHtml($introductionText, $proofKey, $forMailto=false){
		$newLine = "&nbsp;</p><p>";
		$preNewLine = "<p>";
		if($forMailto) {
			$newLine = "%0D%0A";
			$preNewLine = "";
			$body = str_replace("\n", $newLine, str_replace("\r", "", $body));
		}
		$title = $this->getBaseEmailSubject();
		if(!$forMailto) $body = "<h3>".$title."</h3>";
		else $body = $title.$newLine.$newLine;

		if($introductionText){
			$body .= $introductionText.$preNewLine.$newLine;
		} else {
			$body .= $preNewLine;
		}

		$validationLink = $this->getElementService()->getEmailValidationLinkFromCode($this->getP(), $proofKey);
		$body .= $this->t("pleaseValidateYourEmailByClickingOnTheFollowingLink").": ";
		if(!$forMailto) $body .= '<a href="'.$validationLink.'/'.$this->getTranslationService()->getLanguage().'">'.$this->t("validationLink").'</a>';
		else $body .= $validationLink.'/'.$this->getTranslationService()->getLanguage();

		if(!$forMailto) $body = $body."</p>";

		if(!$forMailto){
			$templatePath = $this->getConfigService()->getTemplatePath($this->getP(), null, $this->getBaseEmailActivity());
			ob_start();
			include($templatePath);
			$body = ob_get_clean();
		}
		return $body;
	}
	public function getExternalAccessEditEmailSubject($subject=null){
		if(!$subject) $subject = $this->getBaseEmailSubject();
		return $subject.": ".$this->t("externalAccessEditEmailSubject");
	}
	public function getExternalAccessViewEmailSubject($subject=null){
		if(!$subject) $subject = $this->getBaseEmailSubject();
		return $subject.": ".$this->t("externalAccessViewEmailSubject");
	}
	public function getExternalAccessViewEmailHtml($introductionText, $externalCode, $proofKey=null, $proofStatus=null, $forMailto=false){
		$newLine = "&nbsp;</p><p>";
		$preNewLine = "<p>";
		if($forMailto) {
			$newLine = "%0D%0A";
			$preNewLine = "";
			$body = str_replace("\n", $newLine, str_replace("\r", "", $body));
		}
		$title = $this->getBaseEmailSubject();
		if(!$forMailto) $body = "<h3>".$title."</h3>";
		else $body = $title.$newLine.$newLine;

		if($introductionText){
			$body .= $introductionText.$preNewLine.$newLine;
		} else {
			$body .= $preNewLine;
		}

		if($proofKey && $proofStatus == Emails::PROOF_STATUS_NONE){
			$validationLink = $this->getElementService()->getEmailValidationLinkFromCode($this->getP(), $proofKey);
			$body .= $this->t("pleaseValidateYourEmailByClickingOnTheFollowingLink").": ";
			if(!$forMailto) $body .= '<a href="'.$validationLink.'/'.$this->getTranslationService()->getLanguage().'">'.$this->t("validationLink").'</a>';
			else $body .= $validationLink.'/'.$this->getTranslationService()->getLanguage();
			$body .= $newLine;
		}
		$externalAccessLink = $this->getElementService()->getExternalAccessLinkFromCode($this->getP(), $this->getExecutionService()->getCrtWigiiNamespace(), $this->getExecutionService()->getCrtModule(), $externalCode);
		$body .= $this->t("youCanViewYourDetailsByClickingOnTheFollowingLink").": ";
		if(!$forMailto) $body .= '<a href="'.$externalAccessLink.'/view/'.$this->getTranslationService()->getLanguage().'">'.$this->t("externalAccessLink").'</a>';
		else $body .= $externalAccessLink.'/view/'.$this->getTranslationService()->getLanguage();

		if(!$forMailto) $body .= "</p>";

		if(!$forMailto){
			$templatePath = $this->getConfigService()->getTemplatePath($this->getP(), null, $this->getBaseEmailActivity());
			ob_start();
			include($templatePath);
			$body = ob_get_clean();
		}
		return $body;
	}
	public function getExternalAccessEditEmailHtml($introductionText, $externalCode, $proofKey=null, $proofStatus=null, $forMailto=false){
		$newLine = "&nbsp;</p><p>";
		$prevNewLine = "<p>";
		if($forMailto) {
			$newLine = "%0D%0A";
			$prevNewLine = "";
			$body = str_replace("\n", $newLine, str_replace("\r", "", $body));
		}
		$title = $this->getBaseEmailSubject();
		if(!$forMailto) $body = "<h3>".$title."</h3>";
		else $body = $title.$newLine.$newLine;

		if($introductionText){
			$body .= $introductionText.$prevNewLine.$newLine;
		}

		if($proofKey && $proofStatus == Emails::PROOF_STATUS_NONE){
			$validationLink = $this->getElementService()->getEmailValidationLinkFromCode($this->getP(), $proofKey);
			$body .= $this->t("pleaseValidateYourEmailByClickingOnTheFollowingLink").": ";
			if(!$forMailto) $body .= '<a href="'.$validationLink.'/'.$this->getTranslationService()->getLanguage().'">'.$this->t("validationLink").'</a>';
			else $body .= $validationLink.'/'.$this->getTranslationService()->getLanguage();
			$body .= $newLine;
		}
		$externalAccessLink = $this->getElementService()->getExternalAccessLinkFromCode($this->getP(), $this->getExecutionService()->getCrtWigiiNamespace(), $this->getExecutionService()->getCrtModule(), $externalCode);
		$body .= $this->t("youCanUpdateYourDetailsByClickingOnTheFollowingLink").": ";
		if(!$forMailto) $body .= '<a href="'.$externalAccessLink.'/edit/'.$this->getTranslationService()->getLanguage().'">'.$this->t("externalAccessEditLink").'</a>';
		else $body .= $externalAccessLink.'/edit/'.$this->getTranslationService()->getLanguage();

		if(!$forMailto) $body .= "</p>";

		if(!$forMailto){
			$templatePath = $this->getConfigService()->getTemplatePath($this->getP(), null, $this->getBaseEmailActivity());
			ob_start();
			include($templatePath);
			$body = ob_get_clean();
		}
		return $body;
	}
	private $baseEmailActivity;
	protected function getBaseEmailActivity(){
		if(!isset($this->baseEmailActivity)){
			$this->baseEmailActivity = $this->createBaseEmailActivityInstance();
		}
		return $this->baseEmailActivity;
	}
	protected function createBaseEmailActivityInstance(){
		$r = Activity::createInstance("BaseEmail");
		return $r;
	}

    /**
     * Giving an array with key/value pairs, return the position of a given key
     * @param Array $array
     * @param scalar $key
     * @return int The position in the array starting from 0, -1 if key is not found
     */
    protected function findKeyIndexInArray($array, $key){
        $i = 0;
        foreach ($array as $k=>$v){
            if($k == $key) return $i;
            $i++;
        }
        return -1;
    }
}



