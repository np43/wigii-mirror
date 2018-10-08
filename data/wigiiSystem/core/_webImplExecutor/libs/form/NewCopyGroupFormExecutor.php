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

/*
 * Created on 15 sept. 09
 * by LWR
 */
class NewCopyGroupFormExecutor extends EditGroupFormExecutor {

	private $originalConfigFiles;
	public static function createInstance($wigiiExecutor, $groupP, $record, $formId, $submitUrl=MANDATORY_ARG, $actOnCheckedRecordRequest=""){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setGroupP($groupP);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setActOnCheckedRecordRequest($actOnCheckedRecordRequest);
		return $fe;
	}

	protected function actOnCheckedRecord($p, $exec) {
		$rec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();
		$config = $this->getWigiiExecutor()->getConfigurationContext();

		if($config->doesGroupHasConfigFile($p, $group)){
			$originalConfigFile = $config->getGroupConfigFilename($p, $group);
		} else $originalConfigFile = null;

		//empty id
		$group->setId(0);
		//reset the wigiiNamespace to principal wigiiNamespace if no parent is defined and if !isWigiiNamespaceCreator
		if(!$p->isWigiiNamespaceCreator() && $group->getGroupParentId()==null){
			$group->setWigiiNamespace($p->getWigiiNamespace());
		}

		//empty possible files in activities
		$groupActivityWithConfig = array("groupPortal", "groupHtmlContent", "groupXmlPublish", "groupSubscription");
		foreach($groupActivityWithConfig as $activityName){
			$rec = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity::createInstance($activityName), $exec->getCrtModule());
			$shouldReset = false;

			switch($activityName){
				case "groupPortal":
					$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getPortal(), $rec->getActivity());
					break;
				case "groupHtmlContent":
					$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getHtmlContent(), $rec->getActivity());
					break;
				case "groupXmlPublish":
					$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getXmlPublish(), $rec->getActivity());
					//delete the publishCode
					$rec->setFieldValue($group->getDetail()->getNewXmlPublishCode($p, $group), "xmlPublishCode");
					$shouldReset = true;
					break;
				case "groupSubscription":
					$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getSubscription(), $rec->getActivity());
					break;
			}
			foreach($rec->getFieldList()->getListIterator() as $field){
				if($field->getDataType()!=null && $field->getDataType()->getDataTypeName()=="Files"){
					$rec->getWigiiBag()->emptyFieldValue($field->getFieldName());
					$shouldReset = true;
				}
			}
			if($shouldReset){
				switch($activityName){
					case "groupPortal":
						$group->getDetail()->setPortal($rec->getWigiiBag()->exportAsSerializedArray($rec->getActivity()));
						break;
					case "groupHtmlContent":
						$group->getDetail()->setHtmlContent($rec->getWigiiBag()->exportAsSerializedArray($rec->getActivity()));
						break;
					case "groupXmlPublish":
						$group->getDetail()->setXmlPublish($rec->getWigiiBag()->exportAsSerializedArray($rec->getActivity()));
						break;
					case "groupSubscription":
						$group->getDetail()->setSubscription($rec->getWigiiBag()->exportAsSerializedArray($rec->getActivity()));
						break;
				}
			}
		}

		parent::actOnCheckedRecord($p, $exec);

		if($originalConfigFile!=null){
			$newCFile = $config->getGroupConfigFilename($p, $group);
			if(file_exists($originalConfigFile)){
				copy($originalConfigFile, $newCFile);
			}
		}

	}

	public function getDialogBoxTitle($p){
		$transS = ServiceProvider::getTranslationService();
		return $transS->t($p, "copyGroup");
	}

	protected function getCancelJSCode($p, $exec, $group){
		return "";
	}
}



