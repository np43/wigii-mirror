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
class EditEventFormExecutor extends EditElementFormExecutor {

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	protected function reloadAfterCheckedRecord($p, $exec){
		$exec->addJsCode("$('#moduleView .calendar').fullCalendar('refetchEvents');");
	}

	protected function getCancelJsCode($p, $exec, $state){
		$cancelJsCode = parent::getCancelJsCode($p, $exec, $state);
		$cancelJsCode .= " if(lastResizeOrDragRevertFunction)lastResizeOrDragRevertFunction(); lastResizeOrDragRevertFunction = null;";
		return $cancelJsCode;
	}
	
	protected function getOkJsCode(){
		$OkJsCode = parent::getOkJsCode();
		$OkJsCode .= " lastResizeOrDragRevertFunction = null;";
		return $OkJsCode;
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		
		$fsl = FieldSelectorListForActivity :: createInstance(false, false);
		$fsl->setSelectedLanguages(array ($transS->getLanguage() => $transS->getLanguage()));
		$config->getFields($p, $exec->getCrtModule(), Activity :: createInstance("calendarView"), $fsl);
		
		$fieldList = FormFieldList :: createInstance(null);
		$config->getFields($p, $exec->getCrtModule(), null, $fieldList);
		foreach ($fieldList->getListIterator() as $field) {
		    if ($field->isCalculated() && $field->shouldCalculateOnFetch() && $fsl->containsField($field->getFieldName())) {
		        $field->getFuncExpDependencies($fsl);
		    }
		}

		$element = $this->getRecord();
		$state = $this->getState();

		if($state == "start"){
			//look for parameters in exec:
			$startDate = $exec->getCrtParameters(2);
			$endDate = $exec->getCrtParameters(3);
			$isAllDay = $exec->getCrtParameters(4)==="true";
			if($startDate != null){
				//find first TimeRange field or Dates
				//foreach($element->getFieldList()->getListIterator() as $field){
				foreach($fsl->getListIterator() as $fs){
					$field = $fieldList->getField($fs->getFieldName());
					if($field->getDataType() && $field->getDataType()->getDataTypeName() == "TimeRanges"){
						$startDate = date_parse($startDate);
						$endDate = date_parse($endDate);
						$element->setFieldValue($isAllDay, $field->getFieldName(), "isAllDay");
						$element->setFieldValue($startDate["day"].".".$startDate["month"].".".$startDate["year"], $field->getFieldName(), "begDate");
						$element->setFieldValue($startDate["hour"].":".$startDate["minute"], $field->getFieldName(), "begTime");
						$element->setFieldValue($endDate["day"].".".$endDate["month"].".".$endDate["year"], $field->getFieldName(), "endDate");
						$element->setFieldValue($endDate["hour"].":".$endDate["minute"], $field->getFieldName(), "endTime");
						break;
					}
					if($field->getDataType() && $field->getDataType()->getDataTypeName() == "Dates"){
						$startDate = date_parse($startDate);
						$element->setFieldValue($startDate["day"].".".$startDate["month"].".".$startDate["year"]." ".$startDate["hour"].":".$startDate["minute"], $field->getFieldName(), "value");
						//in the case of a second date
						if($endDate) {
							$field2 = null;
							foreach ($fsl->getListIterator() as $fs2) {
								$field2 = $fieldList->getField($fs2->getFieldName());
								if ($fs2 == $fs)continue;//skip if same field.
								if ($field2->getDataType() && $field2->getDataType()->getDataTypeName() == "Dates") {
									$field2 = $fieldList->getField($fs2->getFieldName());
									break 1;
								}
							}
							if($field2){
								$endDate = date_parse($endDate);
								$element->setFieldValue($endDate["day"].".".$endDate["month"].".".$endDate["year"]." ".$endDate["hour"].":".$endDate["minute"], $field2->getFieldName(), "value");
							}
						}
						break;
					}
				}
			}
		}
		parent::doRenderForm($p, $exec);
	}
}


