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
 * Created on 17 February 2011 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class CopyElementFormExecutor extends AddElementFormExecutor {

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	protected function getGroupIdInWhichToAdd($p, $exec){
		return $exec->getCrtParameters(2);
	}

	public function initializeDefaultValues($p, $exec){
		$this->cleanupKeyAndFilesFields($p, $exec);
		return parent::initializeDefaultValues($p, $exec);
	}

	protected function cleanupKeyAndFilesFields($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

		$element = $this->getRecord();

		//remove any key field value
		$isKey = $this->getWigiiExecutor()->doesCrtModuleHasIsKeyField($p, $exec->getCrtModule());
		if($isKey) $this->getRecord()->setFieldValue(null, $isKey->getName(), $this->getWigiiExecutor()->getSubFieldnameForIsKeyField($isKey));
		//empty all Files none htmlArea form, clear emails externalAccess code
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
			// CWE 02.09.2016: clears external access codes on emails
			else if($dt && $dt->getDataTypeName()=="Emails") {
				$element->setFieldValue(null, $field->getFieldName(), "externalCode");
			}
		}
		umask($oldmask);
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

	protected function reloadAfterCheckedRecord($p, $exec){
		/* CWE 23.08.2016 already done on client side before showing the copy form
		// if destination folder is different than current folder, then refreshes group panel and module view
		$currentGroupId = $this->getCurrentSelectedGroup($p, $exec);
		$groupId = $this->getGroupIdInWhichToAdd($p, $exec);
		if($currentGroupId && $currentGroupId != $groupId) {
			$exec->addJsCode("setTimeout(function(){update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/groupSelectorPanel/selectGroup/".$groupId."');}, 100);");
		}
		// else refreshes only list and invalids cache
		else parent::reloadAfterCheckedRecord($p, $exec);
		*/
		parent::reloadAfterCheckedRecord($p, $exec);
	}
}



