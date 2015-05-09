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
 * Created on 21 November 2012
 * by LWR
 */

class ElementPListExportICSCalendar extends ElementPListWebImplWithWigiiExecutor {
	
	private $output;
	protected function put($text){
		$this->output .= $text."\n";
	}
	public function output(){
		return $this->output;
	}
	
	private $p;
	public function setP($p){ $this->p = $p; }
	protected function getP(){ return $this->p; }
	
	private $lcTemp;
	protected function getLCTemp(){ return $this->lcTemp; }
	public function setLCTemp($lcTemp){ $this->lcTemp = $lcTemp; }
	
	private $groupPList;
	public function setGroupPList($gl){ $this->groupPList = $gl; }
	protected function getGroupPList(){
		if(!isset($this->groupPList)){
			$this->groupPList = $this->getListContext()->getGroupPList();
		}
		return $this->groupPList;
	}
	
	private $calName;
	protected function getCalendarName(){
		if(!isset($this->calName)){
//			$transS = ServiceProvider::getTranslationService();
			$g = reset($this->getGroupPList()->getListIterator())->getDbEntity();
			//$this->calName = ($g->getWigiiNamespace()->getWigiiNamespaceName() ? $g->getWigiiNamespace()->getWigiiNamespaceName()." " : "").$g->getGroupName();
			$this->calName = $g->getGroupName();
		}
		return $this->calName;
	}
	
	private $exec;
	public function setExecutionService($exec){ $this->exec = $exec; }
	protected function getExecutionService(){
		return $this->exec;
	}
	
	public static function createInstance($wigiiExecutor, $listContext){
		$elPl = new self();
		$elPl->setListContext($listContext);
		$elPl->setWigiiExecutor($wigiiExecutor);
		$elPl->output = null;
		$elPl->count = null;
		return $elPl;
	}
	
	private $date;
	public function setDate($date, $fieldName, $isTimeRanges){
		$d = explode("-",$date); 
		$this->date = mktime(0,0,0,$d[1], $d[2], $d[0]); //from format Y-m-d
		$dateExp = LogExp::createAndExp();
		
		if($isTimeRanges){
			$fsStartTime = FieldSelector::createInstance($fieldName, "begTime");
			$fsStartDate= FieldSelector::createInstance($fieldName, "begDate");
			$fsEndTime = FieldSelector::createInstance($fieldName, "endTime");
			$fsEndDate= FieldSelector::createInstance($fieldName, "endDate");
			if(!$this->getPeriodField()) $this->setPeriodField($fieldName);
			//log exp on date must be done on timestamp to get good results
			$dateExp->addOperand(LogExp::createSmallerExp($fsStartDate, $this->date+(365*24*3600)));
			$orLogExp = LogExp::createOrExp();
			$orLogExp->addOperand(LogExp::createGreaterEqExp($fsEndDate, $this->date));
			$and2LogExp = LogExp::createAndExp();
			$and2LogExp->addOperand(LogExp::createEqualExp($fsEndDate, null));
			$and2LogExp->addOperand(LogExp::createGreaterEqExp($fsStartDate, $this->date));
			$orLogExp->addOperand($and2LogExp);
			$dateExp->addOperand($orLogExp);
		} else {
			$fsDate= FieldSelector::createInstance($fieldName, "value");
			if(!$this->getDateField()) $this->setDateField($fieldName);
			//log exp on date must be done on timestamp to get good results
			$dateExp->addOperand(LogExp::createSmallerExp($fsDate, $this->date+(365*24*3600)));
			$dateExp->addOperand(LogExp::createGreaterEqExp($fsDate, $this->date));
		}
		
		$crtLogExp = $this->getListContext()->getFieldSelectorLogExp();
		if(isset($crtLogExp)){
			if($crtLogExp instanceof LogExpAnd)
			{
				$crtLogExp->addOperand($dateExp);
			}
			else
			{
				$andExp = LogExp::createAndExp();
				$andExp->addOperand($crtLogExp);
				$andExp->addOperand($dateExp);
				$this->getListContext()->setFieldSelectorLogExp($andExp);
			}
		} else {
			$this->getListContext()->setFieldSelectorLogExp($dateExp);
		}
	}
	protected function getYear(){ return $this->year; }
	
	//in the case the calendar match a Dates field
	private $dateFieldname;
	protected function getDateField(){ return $this->dateFieldname; }
	public function setDateField($fieldName){ $this->dateFieldname=$fieldName; }
	
	//in the case the calendar match a TimeRanges field
	private $periodFieldname;
	protected function getPeriodField(){ return $this->periodFieldname; }
	public function setPeriodField($fieldName){ $this->periodFieldname=$fieldName; }
	
	private $subjectFieldname;
	public function setSubjectField($fieldName){ $this->subjectFieldname = $fieldName; }
	protected function getSubjectField(){ return $this->subjectFieldname; }
	
	private $descriptionFieldname;
	public function setDescriptionField($fieldName){ $this->descriptionFieldname = $fieldName; }
	protected function getDescriptionField(){ return $this->descriptionFieldname; }
	
	private $locationFieldname;
	public function setLocationField($fieldName){ $this->locationFieldname = $fieldName; }
	protected function getLocationField(){ return $this->locationFieldname; }
	
	private $postLocationFieldname;
	public function setPostLocationField($fieldName){ $this->postLocationFieldname = $fieldName; }
	protected function getPostLocationField(){ return $this->postLocationFieldname; }
	
	private $labelFieldname;
	public function setLabelField($fieldName){ $this->labelFieldname = $fieldName; }
	protected function getLabelField(){ return $this->labelFieldname; }
	
	private $organizerFieldname;
	public function setOrganizerField($fieldName){ $this->organizerFieldname = $fieldName; }
	protected function getOrganizerField(){ return $this->organizerFieldname; }
	
	private $count;
	public function isEmpty(){
		return $this->count == 0;
	}
	public function count(){
		return $this->count;
	}
	
	private $html2text;
	public function addElementP($elementP){
		$transS = ServiceProvider::getTranslationService();
		$this->count++;
		
		$this->beginElement($elementP->getId());
		
		$this->put("CLASS:PUBLIC");
		$this->put("UID:".str_replace(" ", "_", $this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceUrl())."_".str_replace(" ", "_", $this->getExecutionService()->getCrtModule()->getModuleUrl())."_".$elementP->getId());
		$this->put("CREATED:".date("Ymd\THisZ", $elementP->getElement()->getSys_creationDate()));
		$this->put("LAST-MODIFIED:".date("Ymd\THisZ", $elementP->getElement()->getSys_date()));
		
		if($this->getDateField()){
			//does it have time?
			$val = $elementP->getElement()->getFieldValue($this->getDateField(), "value");
			$d = $m = $y = $h = $i = $s = null;
			Dates::fromString($val, $d, $m, $y, $h, $i, $s);
			if(($h || $i || $s) && !($h==0 && $i==0 && $s==0)) $time = "$h:$i:$s";
			else $time = "";
			if($time){
				$this->put("DTSTART;TZID=Europe/Berlin:".$y.$m.$d."T".$h.$i.$s);
				//default to one hour length if time
				$this->put("DTEND;TZID=Europe/Berlin:".date("Ymd\THis", strtotime($y."-".$m."-".$d." ".$h.":".$i.":".$s)+3600));
			} else {
				//default all day if no time
				$this->put("DTSTART;VALUE=DATE:".$y.$m.$d);
				$this->put("DTSTART;VALUE=DATE:".date("Ymd", strtotime($y."-".$m."-".$d)+24*3600));
			}
		} else if($this->getPeriodField() && $elementP->getElement()->getFieldValue($this->getPeriodField(), "isAllDay")){
			$this->put("DTSTART;VALUE=DATE:".date("Ymd", strtotime($elementP->getElement()->getFieldValue($this->getPeriodField(), "begDate"))));
			if($elementP->getElement()->getFieldValue($this->getPeriodField(), "endDate")==null){
				$this->put("DTEND;VALUE=DATE:".date("Ymd", strtotime($elementP->getElement()->getFieldValue($this->getPeriodField(), "begDate"))+24*3600));
			} else {
				$this->put("DTEND;VALUE=DATE:".date("Ymd", strtotime($elementP->getElement()->getFieldValue($this->getPeriodField(), "endDate"))+24*3600));
			}
		} else if($this->getPeriodField()){
			$this->put("DTSTART;TZID=Europe/Berlin:".date("Ymd\THis", strtotime($elementP->getElement()->getFieldValue($this->getPeriodField(), "begDate")." ".$elementP->getElement()->getFieldValue($this->getPeriodField(), "begTime"))));
			if($elementP->getElement()->getFieldValue($this->getPeriodField(), "endTime")==null && ($elementP->getElement()->getFieldValue($this->getPeriodField(), "begDate")==$elementP->getElement()->getFieldValue($this->getPeriodField(), "endDate") || $elementP->getElement()->getFieldValue($this->getPeriodField(), "endDate")==null)){
				//default time lenght to one hour
				$this->put("DTEND;TZID=Europe/Berlin:".date("Ymd\THis", strtotime($elementP->getElement()->getFieldValue($this->getPeriodField(), "begDate")." ".$elementP->getElement()->getFieldValue($this->getPeriodField(), "begTime"))+3600));
			} else {
				if($elementP->getElement()->getFieldValue($this->getPeriodField(), "endDate")==null){
					$this->put("DTEND;TZID=Europe/Berlin:".date("Ymd\THis", strtotime($elementP->getElement()->getFieldValue($this->getPeriodField(), "begDate")." ".$elementP->getElement()->getFieldValue($this->getPeriodField(), "endTime"))));
				} else {
					$this->put("DTEND;TZID=Europe/Berlin:".date("Ymd\THis", strtotime($elementP->getElement()->getFieldValue($this->getPeriodField(), "endDate")." ".$elementP->getElement()->getFieldValue($this->getPeriodField(), "endTime"))));
				}
			}
		}
		
		$summary = $elementP->getElement()->getFieldValue($this->getSubjectField());
		if(is_array($summary)){
			$finSum = "";
			foreach($summary as $lang=>$sum){
				if($sum==null) continue;
				if($finSum) $finDescr .= " / ";
				$finSum = $sum;
			}
			$summary = $finSum;
		}
		$this->put("SUMMARY:".(is_array($summary) ? implode(" / ", $summary) : $summary));
		
		if($this->getLocationField() && $elementP->getElement()->getFieldValue($this->getLocationField())) $this->put("LOCATION:".$elementP->getElement()->getFieldValue($this->getLocationField()));
		
		if($this->getOrganizerField() && $elementP->getElement()->getFieldValue($this->getOrganizerField())) $this->put("ORGANIZER:".$elementP->getElement()->getFieldValue($this->getOrganizerField()));
		
		if($this->getLabelField() && $elementP->getElement()->getFieldValue($this->getLabelField())) $this->put("CATEGORIES:".Attributs::formatDisplay($elementP->getElement()->getFieldValue($this->getLabelField()), $elementP->getElement()->getFieldList()->getField($this->getLabelField())));
		
		if($this->getDescriptionField() && $elementP->getElement()->getFieldValue($this->getDescriptionField())){
			if(!isset($this->html2text)) $this->html2text = new Html2text();
			$description = $elementP->getElement()->getFieldValue($this->getDescriptionField());
			if(is_array($description)){
				$finDescr = "";
				$newline = "\n";
				$fxml = $elementP->getElement()->getFieldList()->getField($this->getDescriptionField())->getXml();
				if($fxml["htmlArea"]=="1") $newline = "<br />";
				foreach($description as $lang=>$descr){
					if($descr==null) continue;
					if($finDescr) $finDescr .= $newline.$newline;
					$finDescr = $transS->getVisibleLanguage($lang).":".$newline.$descr;
				}
				$description = $finDescr;
			}
			$this->html2text->html2text($description);
			$this->put("DESCRIPTION:".str_replace(array("\n", "\r"), array('\n', ""), $this->html2text->get_text()));
			$this->html2text->clear();
			$this->put("X-ALT-DESC;FMTTYPE=text/html:".str_replace(array("\n", "\r"), array('\n', ""), $description));
		}
		
		if($elementP->getElement()->isState_important1()){
			$this->put("X-MICROSOFT-CDO-IMPORTANCE:2");
		}
		
		
		$this->endElement();
		
	}
	
	protected function beginElement($elId){
//		eput("beginElement");
		$this->put("BEGIN:VEVENT");
	}
	
	protected function endElement(){
		$this->put("END:VEVENT");
		$this->count++;
	}
	
	public function actOnBeforeAddElementP(){
		
//		eput("ActOnBeforeAddElementP");
		$this->put("BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Wigii system//Wigii ".VERSION_NUMBER." MIMEDIR//EN
X-WR-CALNAME:".$this->getCalendarName()."
BEGIN:VTIMEZONE
TZID:Europe/Berlin
X-LIC-LOCATION:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10
END:STANDARD
END:VTIMEZONE");
	}
	
	public function actOnFinishAddElementP($numberOfObjects){
//		eput("actOnFinishAddElementP");
		$this->put("END:VCALENDAR");
	}
	
}


