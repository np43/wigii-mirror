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

/*
 * Created on 23 oct. 09
 * by LWR
 */

class SelectSearchBarFieldList implements FieldList {

	private $wigiiNamespace;
	private $module;
	private $listContext;

	private $wigiiExecutor;
	protected function setWigiiExecutor($var){$this->wigiiExecutor = $var; }
	protected function getWigiiExecutor(){return $this->wigiiExecutor; }

	public static function createInstance($wigiiExecutor, $wigiiNamespace, $module, $listContext){
		$ssFl = new self();
		$ssFl->wigiiNamespace = $wigiiNamespace;
		$ssFl->module = $module;
		$ssFl->listContext = $listContext;
		$ssFl->setWigiiExecutor($wigiiExecutor);
		return $ssFl;
	}
	protected function getWigiiNamespace(){ return $this->wigiiNamespace; }
	protected function getModule(){ return $this->module; }
	protected function getListContext(){ return $this->listContext; }

	/**
	 * Adds a field to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same field in the list
	 */
	protected $fields;
	protected $nbFields = 0;

	protected function byPassDataTypeForGroupByFilter($dataTypeName){
		return (!isset($dataTypeName) ||
				$dataTypeName == "Texts" ||
				$dataTypeName == "Varchars" ||
				$dataTypeName == "Blobs"
				);
	}
	protected function defineDataTypeSubFieldForGroupByFilter($dataTypeName){
		if($dataTypeName == "Addresses"){
			$subs = array("city", "state", "country");
		} else if($dataTypeName == "Files"){
			$subs = array("type", "size", "date");
		} else if($dataTypeName == "TimeRanges"){
			$subs = array("begDate");
		} else if($dataTypeName == "Urls"){
			$subs = array("url");
		} else if($dataTypeName == "Emails"){
			$subs = array("value", "proofStatus", "externalAccessLevel", "externalAccessEndDate");
		} else {
			$subs = array(null);
		}
		return $subs;
	}
	protected function defineElementSubFieldForGroupByFilter(){
		return array("sys_date", "sys_creationDate", "version", "state_locked");
	}
	protected function setSelectGroupByFieldInformation($selectGroupByField, $fieldSelector){
		$this->getListContext()->setGroupByFieldSelectorList($selectGroupByField, $fieldSelector);
	}

	protected function addSelectSearchFieldInformation($selectSearchField, $fieldName){
		$this->getListContext()->addSelectSearchField($selectSearchField, $fieldName);
	}
	protected function addSelectGroupFilterInformation($selectGroupFilterField, $groupLogExp){
		$this->getListContext()->addSelectGroupFilterField($selectGroupFilterField, $groupLogExp);
	}

	public function addField($field){
		$transS = ServiceProvider::getTranslationService();
		$exec = ServiceProvider::getExecutionService();
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //Serviceprovider::getConfigService();

		$fieldName = $field->getFieldName();
		if($this->fields[$fieldName] != null) throw new ListException($fieldName." already exist", ListException::ALREADY_EXISTS);
		$this->field[$fieldName] = 1;
		$this->nbFields++;

		?><div class="selectBoxItem"><?

		$fieldXml = $field->getXml();

		//create a select with the attribute of the xml
		$style = "";
		$width = $fieldXml["width"];
		if($width != null) $style .= "width:".$width."px;";

		if($fieldXml["type"] == "groupFilter"){

			?><select id="selectSearch_<?=$fieldName;?>" name="<?=$fieldName;?>" style="<?=$style;?>" ><?
			//add the none option with the label
			?><option <?
				$label = "--".$transS->t($p, $fieldXml->getName(), $fieldXml)."--";
				?> title="<?=$label;?>" <?
				?> value=""  <?
				?> ><?=$label;?></option><?

			$groupLogExpList = array();
			foreach($fieldXml->attribute as $option){
				if((string)$option == "none") continue;
				?><option <?
					$label = $transS->t($p, (string)$option, $option);
					$value = (string)$option;
					$groupLogExpList[$value] = (string)$option["groupLogExp"];
					?> title="<?=$label;?>" <?
					?> value="<?=$value;?>"  <?
					?> ><?=$label;?></option><?
			}

			$this->addSelectGroupFilterInformation($fieldName, $groupLogExpList);

		} else if($fieldXml["type"] == "groupByFieldListAndElementInfo"){

			?><select class="groupBy" id="selectSearch_<?=ListContext::GroupByField;;?>" name="<?=ListContext::GroupByField;?>" style="<?=$style;?>" ><?
			//add the none option with the label
			?><option <?
				$label = "--".$transS->t($p, $fieldName, $fieldXml)."--";
				?> title="<?=$label;?>" <?
				?> value=""  <?
				?> ><?=$label;?></option><?

			//create the fieldSelectorList for all fields and for element info
			$elementFieldsXml = $configS->mf($p, $this->getModule());
			$fieldSelectorList = FieldSelectorListArrayWebImpl::createInstance();
			foreach($elementFieldsXml->children() as $elementFieldXml){
				if($this->byPassDataTypeForGroupByFilter($elementFieldXml["type"])) continue;
				$subFieldNames = $this->defineDataTypeSubFieldForGroupByFilter($elementFieldXml["type"]);
				foreach($subFieldNames as $subFieldName){
					$key = $fieldSelectorList->addFieldSelector($elementFieldXml->getName(), $subFieldName);
					$label = $transS->t($p, $elementFieldXml->getName(), $elementFieldXml);
					if($subFieldName != "" && $subFieldName != "value") $label .= " ".$transS->t($p, $elementFieldXml["type"]."_".$subFieldName);
					?><option <?
						?> title="<?=$label;?>" <?
						?> value="<?=$key;?>"  <?
						?> ><?=$label;?></option><?
				}
			}
			//add the element infos
			$subFieldNames = $this->defineElementSubFieldForGroupByFilter();
			foreach($subFieldNames as $subFieldName){
				$fieldSelector = FieldSelector::createElementAttributeSelector($subFieldName);
				$key = $fieldSelectorList->addFieldSelectorInstance($fieldSelector);
				$label = $transS->t($p, $subFieldName);
				?><option <?
					?> title="<?=$label;?>" <?
					?> value="<?=$key;?>"  <?
					?> ><?=$label;?></option><?
			}

			$this->setSelectGroupByFieldInformation($fieldName, $fieldSelectorList);

		} else {
			$elFieldName = (string)$fieldXml["field"];
			$elField = $configS->mf($p, $this->getModule())->xpath("$elFieldName");
			if($elField === false) throw new ServiceException("Field: $elFieldName for select search in: $fieldName, does not exists in elements field ".$this->getModule()->getModuleUrl(), ServiceException::INVALID_ARGUMENT);
			$elField = $elField[0];
			if($elField["type"]!="Attributs" && $elField["type"]!="MultipleAttributs") throw new ServiceException("Wrong field: $elFieldName for search as select:$fieldName in ".$this->getModule()->getModuleUrl(), ServiceException::INVALID_ARGUMENT);

			$this->addSelectSearchFieldInformation($fieldName, $elFieldName);

			//add the reverse button only for elementFields which are Attributs or MultipleAttributs
			if($elField["isReversible"]!="0"){
				?><img class="reverse lightGrayBorder ui-corner-all" src="<?=SITE_ROOT_forFileUrl;?>images/icones/18px/invert.png" /><?
			}

			?><select id="selectSearch_<?=$fieldName;?>" name="<?=$fieldName;?>" style="<?=$style;?>" ><?

			//add the none option with the label
			?><option <?
				if(count($fieldXml->children()) > 0){
					$label = "--".$transS->t($p, $fieldXml->getName(), $fieldXml)."--";
				} else {
					$label = "--".$transS->t($p, $elFieldName, $elField)."--";
				}

				?> title="<?=$label;?>" <?
				?> value=""  <?
				?> ><?=$label;?></option><?

			foreach($elField->attribute as $option){
				if((string)$option == "none") continue;
				?><option <?
					$label = $transS->t($p, (string)$option, $option);
					$value = (string)$option;
					?> title="<?=$label;?>" <?
					?> value="<?=$value;?>"  <?
					?> ><?=$label;?></option><?
			}
		}

		?></select><?
		//eput($elField);

		?></div><?

	}

	public function getField($fieldName){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function doesFieldExist($fieldName){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function getListIterator(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function count(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
}


