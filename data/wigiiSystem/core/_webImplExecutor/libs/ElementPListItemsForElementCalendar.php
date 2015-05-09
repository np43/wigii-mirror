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
 * Created on 6 oct. 09
 * by LWR
 */

class ElementPListItemsForElementCalendar extends ElementPListWebImplWithWigiiExecutor {

	private $isGroupedBy;
	private $crtGroupByValue;
	private $highlight;
	private $timeZoneOffset;
	public function setTimeZoneOffset($val){
		$this->timeZoneOffset = $val;
	}
	public function getTimeZoneOffset(){
		return $this->timeZoneOffset;
	}
	public function setHighlight($true){
		$this->highlight = $true;
	}
	public function isHighlight(){ return $this->highlight; }

	public static function createInstance($wigiiExecutor, $listContext){
		$elPl = new self();
		$elPl->setListContext($listContext);
		$elPl->isGroupedBy = $listContext->getGroupBy();
		$elPl->crtGroupByValue = $listContext->getGroupByItemCurrentValue();
		$elPl->setWigiiExecutor($wigiiExecutor);
		$elPl->resetFieldSelectorListForCalendarView();
		return $elPl;
	}

	protected function createFieldSelectorListForActivityInstance(){
		return FieldSelectorListForActivity::createInstance();
	}

	private $p;
	public function setP($p){
		$this->p = $p;
	}
	protected function getP(){
		return $this->p;
	}

	private $fslForCalendarView;
	protected function resetFieldSelectorListForCalendarView($fsl = null){
		unset($this->fslForCalendarView);
		if($fsl != null){
			$this->fslForCalendarView = $fsl;
		}
	}

	protected function getFieldSelectorListForCalendarView($module){
		if(!isset($this->fslForCalendarView)){
			$transS = ServiceProvider::getTranslationService();
			$configS = $this->getWigiiExecutor()->getConfigurationContext();
			$p = $this->getP();

			$fsl = $this->createFieldSelectorListForActivityInstance();
			$fsl->setSelectedLanguages(array($transS->getLanguage()=>$transS->getLanguage()));
			$configS->getFields($p, $module, Activity::createInstance("calendarView"), $fsl);
			$this->fslForCalendarView = $fsl;
		}
		return $this->fslForCalendarView;
	}

	private $fMap;
	protected function resetFMap($array=null){
		unset($this->FMap);
		if($array != null){
			$this->FMap = $array;
		}
	}
	protected function getFieldMapFromCalendarView($module, $elementFieldList){
		if(!isset($this->FMap)){
			$fsl = $this->getFieldSelectorListForCalendarView($module);
			$fMap = array("subject"=>null, "locationString"=>null, "locationAddress"=>null, "postLocation"=>null, "period"=>null, "description"=>null, "label"=>null);
			foreach($fsl->getListIterator() as $fs){
				$field = $elementFieldList->getField($fs->getFieldName());
				if($field->getDataType() == null) continue;
				switch ($field->getDataType()->getDataTypeName()){
					case "Strings":
						if($fMap["subject"]==null) $fMap["subject"] = $fs->getFieldName();
						else if($fMap["locationString"]==null && $fMap["locationAddress"]==null) $fMap["locationString"] = $fs->getFieldName();
						else if($fMap["postLocation"]==null) $fMap["postLocation"] = $fs->getFieldName();
						break;
					case "Varchars":
						if($fMap["subject"]==null) $fMap["subject"] = $fs->getFieldName();
						break;
					case "Addresses":
						if($fMap["locationAddress"]==null) $fMap["locationAddress"] = $fs->getFieldName();
						break;
					case "Dates":
						if($fMap["date"]==null) $fMap["date"] = $fs->getFieldName();
						break;
					case "TimeRanges":
						if($fMap["period"]==null) $fMap["period"] = $fs->getFieldName();
						break;
					case "Blobs":
						if($fMap["description"]==null) $fMap["description"] = $fs->getFieldName();
						break;
					case "Texts":
						if($fMap["description"]==null) $fMap["description"] = $fs->getFieldName();
						break;
					case "Attributs":
					case "MultipleAttributs":
						$colors = $elementFieldList->getField($fs->getFieldName())->getXml()->xpath('*[@color]');
						if($colors && $fMap["label"]==null) $fMap["label"] = $fs->getFieldName();
						break;
				}
			}
			$this->FMap = $fMap;
		}
		return $this->FMap;
	}

	/**
	 * Adds an element to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same element in the list
	 *  *********************************************
		**** WARNING
		**** the activity calendarView will be used as:
		**** - first string field as Subject
		**** - first Address field will be considered as Location
		**** - if no Address field is found, then the second string field will be considered as Location
		**** - first Blobs field will be considered as description
		**** - first TimeRanges field will be considered as period
		**** - any other field will be ignored
		**********************************************
	 */
	private $first;
	public function addElementP($elementP){
		$exec = ServiceProvider::getExecutionService();

		$element = $elementP->getElement();
		$elementId = $element->getId();
		//fetch field map from CalendarView
		$fMap = $this->getFieldMapFromCalendarView($element->getModule(), $element->getFieldList());
		//calculating the text
		//subject
		$value = $element->getFieldValue($fMap["subject"]);
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
		$text = str_replace("'", "\'", $value);
		//location
		if($fMap["locationAddress"]){
			$location = array();
			$location[] = $element->getFieldValue($fMap["locationAddress"], "city");
			$location[] = $element->getFieldValue($fMap["locationAddress"], "state");
			$location[] = $element->getFieldValue($fMap["locationAddress"], "country");
			$location = implode(" / ", $location);
		} else if ($fMap["locationString"]){
			$location = $element->getFieldValue($fMap["locationString"]);
		} else {
			$location = null;
		}
		if($location) $text .= " (".str_replace("'", "\'", $location).")";
		//postLocation
		if($fMap["postLocation"]){
			$postLocation = $element->getFieldValue($fMap["postLocation"]);
		} else {
			$postLocation = null;
		}
		if($postLocation) $text .= " ".str_replace("'", "\'", $postLocation)."";

//		//description
		if($fMap["description"]){
			$description = $element->getFieldValue($fMap["description"]);
			if(is_array($description)){
				$temp = "";
				foreach($description as $i){
					if($i != null){
						if($temp != null) $temp .= ", ";
						$temp .= $i;
					}
				}
				$description = $temp;
			}
			$description = str_replace("'", "\'", str_replace("\n", "", str_replace("\r", "", $description)));
//			if($description){
//				$text .= "<br />".str_replace("'", "\'", $description);
//			}
		}

		//calculating the date
//		$now = date_parse(date("Y-m-d"));
//		$startDate = $element->getFieldValue($fMap["period"], "begDate");
//		$begTime = $element->getFieldValue($fMap["period"], "begTime");
//		$startDate = date_parse(($begTime ? $startDate." ".$begTime : $startDate));
//		$startDateStr = "y+".($startDate["year"]-$now["year"]).",m+".($startDate["month"]-$now["month"]).",d+".($startDate["day"]-$now["day"])."";
//		if($begTime) $startDateStr .= ",".$startDate["hour"].",".$startDate["minute"]."";
//		$endDate = $element->getFieldValue($fMap["period"], "endDate");
//		$endTime = $element->getFieldValue($fMap["period"], "endTime");
//		if($endDate == null) $endDateStr = $startDateStr;
//		else {
//			$endDate = date_parse(($endTime ? $endDate." ".$endTime : $endDate));
//			$endDateStr = "y+".($endDate["year"]-$now["year"]).",m+".($endDate["month"]-$now["month"]).",d+".($endDate["day"]-$now["day"])."";
//			if($endTime) $endDateStr .= ",".$endDate["hour"].",".$endDate["minute"]."";
//		}

		//TimeRanges case
		if($fMap["period"]){
			$startDate = $element->getFieldValue($fMap["period"], "begDate");
			$begTime = $element->getFieldValue($fMap["period"], "begTime");
			$d = $m = $y = $h = $i = $s = null;
			Dates::fromString($startDate." ".$begTime, $d, $m, $y, $h, $i, $s);
			if(($h || $i || $s) && !($h==0 && $i==0 && $s==0)) $time = "$h:$i:$s";
			else $time = "";
			$startDateInt = strtotime("$y/$m/$d $time");

			$endDate = $element->getFieldValue($fMap["period"], "endDate");
			if($endDate == null) $endDate = $startDate;
			$endTime = $element->getFieldValue($fMap["period"], "endTime");
			//if no endTime default to one hour
			if($endTime==null && $endDate==$startDate){
				$endDateInt = ($startDateInt + 3600);
			} else {
				$d = $m = $y = $h = $i = $s = null;
				Dates::fromString($endDate." ".$endTime, $d, $m, $y, $h, $i, $s);
				if(($h || $i || $s) && !($h==0 && $i==0 && $s==0)) $time = "$h:$i:$s";
				else $time = "";
				$endDateInt = strtotime("$y/$m/$d $time");
			}

			$allDay = $element->getFieldValue($fMap["period"], "isAllDay");
			if($allDay == null) $allDay = "false";
			else $allDay = "true";
		}
		if($fMap["date"]){
			$startDate = $element->getFieldValue($fMap["date"], "value");
			$d = $m = $y = $h = $i = $s = null;
			Dates::fromString($startDate, $d, $m, $y, $h, $i, $s);
			if(($h || $i || $s) && !($h==0 && $i==0 && $s==0)) $time = "$h:$i:$s";
			else $time = "";
			$startDateInt = strtotime("$y/$m/$d $time");
			if($time==null){
				$allDay = "true";
				$endDateInt = $startDateInt;
			} else {
				$allDay = "false";
				//add one hour
				$endDateInt = $startDateInt+3600;
			}
		}

		//rendering the JSCode
		if(!$this->first) echo ",
";
		else $this->first = false;

		//color label calculation
		$color = null;
		if($fMap["label"]){
			$label = $element->getFieldValue($fMap["label"]);
//			fput($label);
//			fput($element->getFieldList()->getField($fMap["label"])->getXml()->asXML());
//			fput($element->getFieldList()->getField($fMap["label"])->getXml()->xpath('attribute[(text()="'.$label.'")]'));
//			$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
//			$exec = ServiceProvider::getExecutionService();
//			fput($exec->getCrtModule());
//			fput($this->getWigiiExecutor()->getConfigurationContext()->getCrtConfigGroupId($p, $exec));
//			fput($this->getWigiiExecutor()->getConfigurationContext()->m($p, $exec->getCrtModule())->fields);
			$color = $element->getFieldList()->getField($fMap["label"])->getXml()->xpath('attribute[@color and (text()="'.$label.'")]');
			if($color){ $color = (string)$color[0]["color"]; }
		}


		//ajust time zone offset settings
		$startDateInt = $startDateInt + $this->getTimeZoneOffset();
		$endDateInt = $endDateInt + $this->getTimeZoneOffset();


		echo "{";
		echo "id:'$elementId',";
		echo "title:'$text',";
		echo "description:'$description',";
		echo "start:'$startDateInt',";
		echo "end:'$endDateInt',";
		echo "allDay: $allDay,";
		echo "className:'".($this->isHighlight() ? ' highlight ' : '')." ".($elementP->getRights()==null || !$elementP->getRights()->canWriteElement() ? ' readOnly ' : '')."".($element->isState_important1() ? ' important1 ' : '')."".($element->isState_important2() ? ' important2 ' : '')."".($element->isState_locked() ? ' locked ' : '')."',"; //"+(elementCalendar_currentEventSelected == $elementId ? 'selected' : '')";
		if(!$this->isHighlight() && $color){
			echo "color:'#".$color."',";
			echo "textColor:'#".getBlackOrWhiteFromBackgroundColor($color)."',";
		}
		echo "editable:".($elementP->getRights()==null || !$elementP->getRights()->canWriteElement() ? "false" : "true");
		echo "}";
		flush();
	}

	public function actOnBeforeAddElementP(){
		$this->first = true;
	}

	public function actOnFinishAddElementP($numberOfObjects){

	}

}