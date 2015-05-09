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
 * Created by CWE on March 14th 2014
 *
 */
class CopySubElementFormExecutor extends AddSubElementFormExecutor {

public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl, $masterElementId, $linkName){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setMasterElementId($masterElementId);
		$fe->setLinkName($linkName);
		return $fe;
	}

	protected function getDialogTitle($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		return $transS->t($p, "copyElement");
	}

	protected function renderInForm($p, $exec, $state){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

		$element = $this->getRecord();

		if($this->getState()=="start"){
			//remove any key field value
			$isKey = $this->getWigiiExecutor()->doesCrtModuleHasIsKeyField($p, $exec->getCrtModule());
			if($isKey) $this->getRecord()->setFieldValue(null, $isKey->getName(), $this->getWigiiExecutor()->getSubFieldnameForIsKeyField($isKey));
			//empty all Files none htmlArea form
			$oldmask = umask(0000);
			foreach($element->getFieldList()->getListIterator() as $field){
				$dt = $field->getDataType();
				$xml = $field->getXml();

				//empties any field with clearOnCopy = 1
				if($xml["clearOnCopy"] == '1'){
					$element->getWigiiBag()->emptyFieldValue($field->getFieldName());
					continue;
				}

				//empties Files
				if($dt && $dt->getDataTypeName()=="Files" && $xml["htmlArea"]!="1"){
					$element->getWigiiBag()->emptyFieldValue($field->getFieldName());
				}
				// empties Links
				if($dt && $dt->getDataTypeName()=="Links") {
					$element->getWigiiBag()->emptyFieldValue($field->getFieldName());
				}
				//duplicate any content in html Files, Blobs and Texts
				if($dt && $dt->getDataTypeName()=="Files" && $xml["htmlArea"]=="1"){
					$match = array();
					$val = $element->getFieldValue($field->getFieldName(), "textContent");
					preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $val, $match);
					if($match && $match[0]){
						foreach($match[0] as $tempPath){
							$newPath = $this->getCopyFile($p, $field, $tempPath);
							$val = str_replace($tempPath, $newPath, $val);
						}
						$element->setFieldValue($val, $field->getFieldName(), "textContent");
					}
				} else if($dt && $dt->getDataTypeName()=="Blobs" && $xml["htmlArea"]=="1"){
					$match = array();
					$val = $element->getFieldValue($field->getFieldName(), "value");
					preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $val, $match);
					if($match && $match[0]){
						foreach($match[0] as $tempPath){
							$newPath = $this->getCopyFile($p, $field, $tempPath);
							$val = str_replace($tempPath, $newPath, $val);
						}
						$element->setFieldValue($val, $field->getFieldName(), "value");
					}
				}  else if($dt && $dt->getDataTypeName()=="Texts" && $xml["htmlArea"]=="1"){
					$match = array();
					$multiLanguageValues = $element->getFieldValue($field->getFieldName(), "value");
					foreach($multiLanguageValues as $key=>$val){
						$match[$key] = array();
						preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $val, $match[$key]);
					}
					foreach($match as $key=>$mat){
						if($mat && $mat[0]){
							foreach($mat[0] as $tempPath){
								$newPath = $this->getCopyFile($p, $field, $tempPath);
								$multiLanguageValues[$key] = str_replace($tempPath, $newPath, $multiLanguageValues[$key]);
							}
						}
					}
					$element->setFieldValue($multiLanguageValues, $field->getFieldName(), "value");
				}
			}
			umask($oldmask);
		}
		return parent::renderInForm($p, $exec, $state);
	}

	//unmask needs to be done before and after
	protected function getCopyFile($p, $field, $tempPath){
		$newPath = $p->getWigiiNamespace()->getWigiiNamespaceName()."_".time().ipToStr($_SERVER["REMOTE_ADDR"]).$p->getUsername().$field->getFieldName().substr($tempPath, -15);
		$newPath = preg_replace('/[^a-zA-Z0-9\.\-\_]/',"",$newPath);
		$newPath = CLIENT_WEB_PATH."imageForHtmlEditor/".$newPath;
		@copy($tempPath, $newPath);
		chmod($newPath, 0666);
		return $newPath;
	}

}



