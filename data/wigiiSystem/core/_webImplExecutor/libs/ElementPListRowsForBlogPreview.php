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
 * ElementPListRowsForBlogPreview display elementP in a blog view. 
 * This is used when displaying element list preview from Links dataType
 * Created by Medair (LMA) on 28 July 2017
 * Modified by Medair (CWE) on 26.09.2017 to manage correct sorting if group by is on a range of values.
 */
class ElementPListRowsForBlogPreview implements ElementPList, DataFlowActivity {

	private $trm_;
	protected function getTRM(){ return $this->trm_; }
	public function setTRM($var){ $this->trm_ = $var; }

	private $configService;
	protected function getConfigService(){ return $this->configService; }
	public function setConfigService($var){ $this->configService = $var; }

	private $fsl;
	protected function getFsl(){ return $this->fsl; }
	public function setFsl($var){ $this->fsl = $var; }

	private $previewListId;
	protected function getPreviewListId(){ return $this->previewListId; }
	public function setPreviewListId($var){ $this->previewListId = $var; }

	private $elementId;
	protected function getElementId(){ return $this->elementId; }
	public function setElementId($var){ $this->elementId = $var; }

	private $linkName;
	protected function getLinkName(){ return $this->linkName; }
	public function setLinkName($var){ $this->linkName = $var; }

	private $elementIsBlocked;
	protected function isElementBlocked() {return $this->elementIsBlocked;}
	public function setElementIsBlocked($bool) {$this->elementIsBlocked = $bool;}
	
	private $elementIsReadonly;
	protected function isElementReadonly() {return $this->elementIsReadonly;}
	public function setElementIsReadonly($bool) {$this->elementIsReadonly = $bool;}
	
	private $p;
	protected function getP(){ return $this->p; }
	public function setP($var){ $this->p = $var; }

	private $exec;
	public function setExec($exec){$this->exec = $exec;}
	protected function getExec(){ return $this->exec; }

	private $width;
	public function setWidth($width) {$this->width = $width;}
	protected function getWidth() {return $this->width;}
	
	private $linkType;
	/**
	 * Sets the link type as a numeric constant (see Links class)
	 * @param int $linkType one of Links::LINKS_TYPE_*
	 */
	public function setLinkType($linkType) {$this->linkType = $linkType;}
	/**
	 * Gets link type as a numeric constant
	 * @return int one of Links::LINK_TYPE_*
	 */
	protected function getLinkType() {return $this->linkType;}
	
	// Object lifecycle
	
	/**
	 * @param TemplateRecordManager $trm
	 * @param FieldSelectorList $fsl
	 */
	public static function createInstance($trm, $p, $exec, $configService, $fsl, $elementId, $linkName, $elementIsBlocked, $previewListId, $linkType){
		$elPl = new self();
		$elPl->setTrm($trm);
		$elPl->setP($p);
		$elPl->setExec($exec);
		$elPl->setConfigService($configService);
		$elPl->setFsl($fsl);
		$elPl->setElementId($elementId);
		$elPl->setLinkName($linkName);
		$elPl->setElementIsBlocked($elementIsBlocked);
		$elPl->setPreviewListId($previewListId);
		$elPl->setLinkType($linkType);
		return $elPl;
	}
	
	public function reset(){
		$this->elementIds = array();
		$this->nb = 0;
		$this->module = null;
		unset($this->isDeletedSubElement);
		unset($this->parentElementBlocked);
		$this->updateContentOnly = false;
		//don't change the other parameters
	}
	
	public function freeMemory() {
		unset($this->trm_);
		unset($this->configService);
		unset($this->fsl);
		unset($this->previewListId);
		unset($this->elementId);
		unset($this->linkName);
		unset($this->elementIsBlocked);
		unset($this->p);
		unset($this->exec);
		unset($this->width);
		unset($this->linkType);
	}
	
	// ElementPList implementation
	
	/**
	 * Adds an element to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same element in the list
	 */
	private $elementIds; //array which stores the elements ids already added
	private $nb;
	private $module;
	public function getElementsIds(){
		return $this->elementIds;
	}

	public function addElementP($elementP){
		$trm = $this->getTRM();
		$elementId = $elementP->getId();
		$this->elementIds[$elementId] = $elementId;
		$this->nb++;
		$trm->setRecord($elementP->getElement());
		
		$this->beginElement($elementP);

        $element = $elementP->getElement();
        // builds element link
        if($this->getLinkType() == Links::LINKS_TYPE_QUERY) {
            $p = $this->getP();
            $wigiiNamespace = $p->getWigiiNamespace();
            $module = $element->getModule();
            $formId = 'prev$$'.$this->getElementId()."$$".$this->getLinkName()."$$".$wigiiNamespace->getWigiiNamespaceUrl()."$$".$module->getModuleUrl()."$$".$elementP->getId();
        }
        else {
            $formId = 'prev$$'.$this->getElementId()."$$".$this->getLinkName()."$$".$elementP->getId();
        }

		foreach($this->getFsl()->getListIterator() as $fs) {
            $field = $element->getFieldList()->getField($fs->getFieldName());
            $fieldXml = $field->getXml();
            if($fieldXml["noLabel"] == '1'){
                $label = false;
            } else {
                $label = $trm->t($fs->getFieldName(), $fieldXml);
            }

            //Add the class
            if($fieldXml["class"]) $class = (string)$fieldXml["class"];
            else $class = null;

			$this->addCell($trm->formatValueFromFS($fs, $element), $label, $class, $formId, $field);
		}

		$this->endElement();

	}

	private $isDeletedSubElement;
	protected function isDeletedSubElement(){
		if(!isset($this->isDeletedSubElement)){
			$trashBinPrefix = (string)$this->getConfigService()->getParameter($this->getP(), null, "deletedSubElementsLinkNamePrefix");
			$this->isDeletedSubElement = (!empty($trashBinPrefix) && !empty($this->linkName) && (strpos($this->getLinkName(), $trashBinPrefix)===0));
		}
		return $this->isDeletedSubElement;
	}

	protected function beginElement($elementP){
		$trm = $this->getTRM();

		$element = $elementP->getElement();

		// builds element link
		if($this->getLinkType() == Links::LINKS_TYPE_QUERY) {
			$p = $this->getP();
			$wigiiNamespace = $p->getWigiiNamespace();
			$module = $element->getModule();
			$linkId = 'prev$$'.$this->getElementId()."$$".$this->getLinkName()."$$".$wigiiNamespace->getWigiiNamespaceUrl()."$$".$module->getModuleUrl()."$$".$elementP->getId();
			$cacheLookup = $this->getExec()->getCacheLookup($p->getRealUserId(), $p->getUserId(), $wigiiNamespace, $module, "selectElementDetail", "element/detail/".$elementP->getId());
		}
		else {
			$cacheLookup = $this->getExec()->getCurrentCacheLookup($this->getP(), "selectElementDetail", "element/detail/".$elementP->getId());
			$linkId = 'prev$$'.$this->getElementId()."$$".$this->getLinkName()."$$".$elementP->getId();
		}
		$trm->put("<div class='blogPreview' id='$linkId' style='width: 40%; float: left; margin-right: 15px; margin-bottom:15px; padding: 5px;'>");
	}

	protected function endElement(){
		$trm = $this->getTRM();
        $trm->put("</div>");
	}

	protected function addCell($value, $label, $class, $formId, $field){
		$trm = $this->getTRM();
		$fieldXml = $field->getXml();
		if(is_array($value)){
			$temp = "";
			foreach($value as $i){
				if($i != null){
					if($temp != null) $temp .= ", ";
					$temp .= $i;
				}
			}
			$value = $temp;
		}
		if(empty($value)) {
		    if($fieldXml['displayEvenIfEmpty']=='1') $value = '<font style="font-weight:normal;color:#aaa;">'.$trm->t("displayEvenIfEmpty").'</font>';
		    else return;
		}
		if($label == false){
            $trm->put('<p id="'. $formId. '__'. $field->getFieldName(). '" class="lineBlog '.$class. '"><span class="value" style="width: 100%; display: block; float: left;">'. $value. '</span> <br />');
        }else{
            $trm->put('<p id="'. $formId. '__'. $field->getFieldName(). '" class="lineBlog '.$class.'"><span class="label" style="width: 70px; display: block; float: left;">'. $label. '</span> <span class="value">'. $value. '</span></p><br />');
        }
	}

	public function actOnBeforeAddElementP($principal){
		$this->nb = 0;
		$this->elementIds = array();
		$trm = $this->getTRM();
	}

	public function actOnFinishAddElementP($principal, $total, $number, $pageSize, $width){
		$trm = $this->getTRM();
		if(!$trm->isForPrint()) {
			$refresh = '<img class="H refresh" align="absmiddle" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/view-refresh.png"';
			$refresh .= ' onmouseover="showHelp(this, \''.$trm->h("refresh").'\');" ';
			$refresh .= ' onmouseout="hideHelp();" ';
			$refresh .= '/>';
		}
		else $refresh = '';
	}

	/**
	 * Returns an iterator on this list
	 * Compatible with the foreach control structure
	 */
	public function getListIterator(){
		throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION);
	}
	/**
	 * Returns true if the list is empty
	 */
	public function isEmpty(){
		return $this->nb == null;
	}
	/**
	 * Returns the number of items in the list
	 */
	public function count(){
		return $this->nb;
	}
	/**
	 * Creates a new empty FieldList
	 */
	public function createFieldList(){
		return FieldListArrayImpl::createInstance();
	}
	/**
	 * Creates an empty wigii bag
	 */
	public function createWigiiBag(){
		return WigiiBagBaseImpl::createInstance();
	}

	
	// DataFlowActivity implementation
	
	private $updateContentOnly;
	public function setUpdateContentOnly($bool) {
		$this->updateContentOnly = $bool;
	}	
	
	/**
	 * Sets the module of the elements inside the list
	 * @param Module $module
	 */
	public function setModule($module) {
		$this->module = $module;
	}
	
	public function startOfStream($dataFlowContext) {
	    $apiClient = $dataFlowContext->getAttribute('GroupBasedWigiiApiClient');
	    if(isset($apiClient)) $this->setConfigService($apiClient->getConfigService());
	    // checks ConfigService injection
	    if(!isset($this->configService)) throw new DataFlowServiceException('ConfigService is not set, please inject one', DataFlowServiceException::CONFIGURATION_ERROR);
		// checks Principal injection
		if(!isset($this->p)) {
			$this->setP($dataFlowContext->getPrincipal());			
		}
	}
	
	public function processDataChunk($data, $dataFlowContext) {
		if($this->nb == 0) {
			// sets module of element
			if(($data instanceof ElementP) || ($data instanceof Element)) $this->module = $data->getDbEntity()->getModule();
			// initializes the list preview
			if(!$this->updateContentOnly) $this->actOnBeforeAddElementP($this->getP());
		}
		// displays element in preview list 
		$this->addElementP($data);
	}
	
	/**
	 * Pushes the number of selected elements in the stream for further processing
	 * @see DataFlowActivity::endOfStream()
	 */
	public function endOfStream($dataFlowContext) {		
		// extracts the listFilter paging info if set
		$listFilter = $dataFlowContext->getAttribute('ListFilter');
		if(isset($listFilter) && $listFilter->isPaged()) {
			$total = $listFilter->getTotalNumberOfObjects();
			$pageSize = $listFilter->getPageSize();
		}
		else {
			$total = $this->nb;
			$pageSize = null;
		}
		// ends the list preview
		if(!$this->updateContentOnly) $this->actOnFinishAddElementP($this->getP(), ($total > 0 ? $total:0), ($this->nb > 0? $this->nb:0), $pageSize, $this->getWidth());
		// pushes the number of element in the stream
		$dataFlowContext->writeResultToOutput($this->nb, $this);
	}

	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}

