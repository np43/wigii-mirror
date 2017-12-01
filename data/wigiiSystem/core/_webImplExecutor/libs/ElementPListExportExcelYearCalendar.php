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
 * Created on 11 january 2011
 * by LWR
 */

class ElementPListExportExcelYearCalendar extends ElementPListExportExcel {

	private $p;
	public function setP($p){ $this->p = $p; }
	protected function getP(){ return $this->p; }

	public static function createInstance($wigiiExecutor, $listContext){
		$elPl = new self();
		$elPl->setListContext($listContext);
		$elPl->setWigiiExecutor($wigiiExecutor);
		return $elPl;
	}

	private $year;
	public function setYear($year, $fieldName, $isTimeRanges, $endDateFieldName=null){
		$this->year = $year;
		if($isTimeRanges){
			$fsStartTime = FieldSelector::createInstance($fieldName, "begTime");
			$fsStartDate= FieldSelector::createInstance($fieldName, "begDate");
			$fsEndTime = FieldSelector::createInstance($fieldName, "endTime");
			$fsEndDate= FieldSelector::createInstance($fieldName, "endDate");

			if(!$this->getPeriodField()) $this->setPeriodField($fieldName);

			$dateExp = LogExp::createAndExp();
			//in search don't look for time. because the view are always full day.
			//adding and on time makes problems as well because the logExp should be a concatenation of date + time, not separate
			$dateExp->addOperand(LogExp::createSmallerExp($fsStartDate, ($year+1)."-01-01"));
			$orLogExp = LogExp::createOrExp();
			$orLogExp->addOperand(LogExp::createGreaterEqExp($fsEndDate, $year."-01-01"));
			$and2LogExp = LogExp::createAndExp();
			$and2LogExp->addOperand(LogExp::createEqualExp($fsEndDate, null));
			$and2LogExp->addOperand(LogExp::createGreaterEqExp($fsStartDate, $year."-01-01"));
			$orLogExp->addOperand($and2LogExp);
			$dateExp->addOperand($orLogExp);
		} else {
			$fsDate = FieldSelector::createInstance($fieldName, "value");
			if(!$this->getDateField()) $this->setDateField($fieldName);
			if($endDateFieldName){
				$fsEndDate = FieldSelector::createInstance($endDateFieldName, "value");
				if(!$this->getEndDateField()) $this->setEndDateField($endDateFieldName);
				$dateExp = lxOr(lxAnd(lxSm($fsDate,($year+1)."-01-01"), lxGrEq($fsEndDate, $year."-01-01")),
						lxAnd(lxEq($fsDate, null),lxSm($fsEndDate,($year+1)."-01-01"), lxGrEq($fsEndDate, $year."-01-01")),
						lxAnd(lxEq($fsEndDate, null),lxSm($fsDate,($year+1)."-01-01"), lxGrEq($fsDate, $year."-01-01")));
			} else {
				$dateExp = LogExp::createAndExp();
				$dateExp->addOperand(LogExp::createSmallerExp($fsDate, ($year+1)."-01-01"));
				$dateExp->addOperand(LogExp::createGreaterEqExp($fsDate, ($year)."-01-01"));
			}
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

	//if the calendar is linked to a Dates field
	private $dateFieldname;
	protected function getDateField(){ return $this->dateFieldname; }
	public function setDateField($fieldName){ $this->dateFieldname=$fieldName; }

	//if the calendar is linked to a end Dates field
	private $endDateFieldname;
	protected function getEndDateField(){ return $this->endDateFieldname; }
	public function setEndDateField($fieldName){ $this->endDateFieldname=$fieldName; }

	//if the calendar is linked to a TimeRanges field
	private $periodFieldname;
	protected function getPeriodField(){ return $this->periodFieldname; }
	public function setPeriodField($fieldName){ $this->periodFieldname=$fieldName; }

	private $subjectFieldname;
	public function setSubjectField($fieldName){ $this->subjectFieldname = $fieldName; }
	protected function getSubjectField(){ return $this->subjectFieldname; }

	private $locationFieldname;
	public function setLocationField($fieldName){ $this->locationFieldname = $fieldName; }
	protected function getLocationField(){ return $this->locationFieldname; }

	private $postLocationFieldname;
	public function setPostLocationField($fieldName){ $this->postLocationFieldname = $fieldName; }
	protected function getPostLocationField(){ return $this->postLocationFieldname; }

	private $labelFieldname;
	public function setLabelField($fieldName){ $this->labelFieldname = $fieldName; }
	protected function getLabelField(){ return $this->labelFieldname; }

	private $title;
	public function setTitle($title){ $this->title = $title; }
	protected function getTitle(){ return $this->title; }

	//return a color according to the label
	private $labelMap;
	protected function updateLabelColor($label, $style){
		if(!isset($this->labelMap)){
			$this->labelMap = array();
		}
		if(!isset($this->labelMap[$label])){
			$color = $this->crtElementP->getElement()->getFieldList()->getField($this->getLabelField())->getXml()->xpath('attribute[@color and (text()="'.$label.'")]');
			if($color){ $color = (string)$color[0]["color"]; }
			else $color = "000000";
			$this->labelMap[$label] = $color;
		}
		foreach($style as $key=>$value){
			if($key=="color"){
				$style[$key]=array('argb' => 'FF'.$this->labelMap[$label]);
			} else if($key!="font" && is_array($value)){
				$style[$key]=$this->updateLabelColor($label, $value);
			}
		}
		return $style;
	}

	private $crtWeekNb = 1;
	private $crtYDay = 0; //0 is first of january
	private $firstTimestamp;
	protected function getFirstTimestamp(){
		if(!isset($this->firstTimestamp)){
			$this->firstTimestamp = mktime(0, 0, 1, 1, 1, $this->getYear());
		}
		return $this->firstTimestamp;
	}
	private $lastMonthLine;
	private $lastMonthWeekends;
	protected function createNewMonth($month){
		$transS = ServiceProvider::getTranslationService();
		$exl = $this->getExcelObj();
		$p = $exl->getActiveSheetIndex();
		$s = $exl->getActiveSheet();

		if($month!=1){
			//create a new empty line if previous month exists
			$s->getRowDimension($this->incCrtLineForPage($p))->setRowHeight($this->eventMarkHeight);
			//create a separator line if previous month exists
			$endLine = $this->incCrtLineForPage($p);
			$s->getRowDimension($endLine)->setRowHeight($this->eventMarkHeight);

			//add borders arround
			$s->getStyle("B".($this->lastMonthLine+1).":".num2letter(32).($this->lastMonthLine+2))->applyFromArray($this->inMonthBorderStyle);
			$s->getStyle("B".($endLine-2).":".num2letter(32).($endLine-1))->applyFromArray($this->monthBorderStyle);
			//colorize the weekend in endLine
			foreach($this->lastMonthWeekends as $col){
				$s->getStyle($col.($endLine-2).":".$col.($endLine-1))->applyFromArray($this->weekendStyle);
				$s->getStyle($col.($this->lastMonthLine+1).":".$col.($this->lastMonthLine+2))->applyFromArray($this->weekendStyle);
			}
		}
		if($month > 12) return;

		//create the week# line
		$weekNbLine = $this->incCrtLineForPage($p);
		//create the weekDay line
		$weekDayLine = $this->incCrtLineForPage($p);
		//create the month line
		$dayLine = $this->incCrtLineForPage($p);

		for($i=0; $i<=31; $i++){ //from 0 to include the month column
			$col = num2letter($i+1);
			if($i == 0){
				//first column add only the month title
				$s->getStyle($col.$weekDayLine)->applyFromArray($this->monthHeaderStyle);
				$s->setCellValue($col.$weekDayLine, $transS->t($this->getP(), "month_long_".$month));
				//stores the line of the crt month
				$this->lastMonthLine = $weekDayLine;
				$this->lastMonthWeekends = array(); //reset the weekends
			} else {
				$d = getDate($this->getFirstTimestamp()+($this->crtYDay*24*60*60));
				if($d["mon"]!=$month) break; //reach the end of month

				//weekNb
				if($d["wday"]==1){
					$s->getStyle($col.$weekNbLine)->applyFromArray($this->weekNbStyle);
					$s->setCellValue($col.$weekNbLine, $this->crtWeekNb);
					$this->crtWeekNb++;
				}
				if($d["wday"]==6||$d["wday"]==0){
					$s->getStyle($col.$dayLine)->applyFromArray($this->weekendStyle);
					$s->getStyle($col.$weekDayLine)->applyFromArray($this->weekendStyle);
					$this->lastMonthWeekends[] = $col;
				}

				//weekDay
				$s->getStyle($col.$weekDayLine)->applyFromArray($this->dayHeaderStyle);
				$s->setCellValue($col.$weekDayLine, $transS->t($this->getP(), "dayOfWeek_".($d[wday] ? $d[wday] : 7)));

				//monthDay
				$s->getStyle($col.$dayLine)->applyFromArray($this->dayStyle);
				$s->setCellValue($col.$dayLine, $i);

				$this->crtYDay++;
			}
		}

		$s->getStyle("A".$dayLine.":".num2letter(32).$dayLine)->applyFromArray($this->monthSeparatorStyle);

		//create one empty line after day numbering
		$s->getRowDimension($this->incCrtLineForPage($p))->setRowHeight($this->eventMarkHeight);

		$this->setCrtColForPage("B", $p);

		//add remaining events
		$actualRemainingEvents = $this->remainingEvents; //copy the array
		$this->remainingEvents = array(); //reset array
		if($actualRemainingEvents){
			foreach($actualRemainingEvents as $data){
				list($firstDate, $endDate, $subject, $location, $label) = $data;
				$this->displayEvent($firstDate, $endDate, $subject, $location, $label);
			}
		}
	}

	protected function displayEvent($firstDate, $endDate, $subject, $location, $label){
		$exl = $this->getExcelObj();
		$p = $exl->getActiveSheetIndex();
		$s = $exl->getActiveSheet();

		$crtLine = $this->incCrtLineForPage($p); //line for mark
		$s->getRowDimension($crtLine)->setRowHeight($this->eventMarkHeight);
		$this->incCrtLineForPage($p); //line for label
		//add borders arround the last month
		$s->getStyle("B".($crtLine).":".num2letter(32).($crtLine+1))->applyFromArray($this->inMonthBorderStyle);
		//colorize the weekend of last month
		foreach($this->lastMonthWeekends as $col){
			$s->getStyle($col.($crtLine).":".$col.($crtLine+1))->applyFromArray($this->weekendStyle);
		}

		if($firstDate["year"]!=$this->getYear()) $firstDate = getDate(strtotime($this->getYear()."-01-01"));

		$crtCol = 1+$firstDate["mday"];

		$tempFirstDate = $firstDate["yday"];
		$tempEndDate = null;
		$newFirstDate = null;
		if($endDate) {
			if($endDate["year"]!=$this->getYear()) $endDate = getDate(strtotime($this->getYear()."-12-31"));
			if($endDate["mon"]!=$firstDate["mon"]){
				$newFirstDate = getDate(strtotime($firstDate["year"]."-".($firstDate["mon"]+1)."-01"));
				$tempEndDate = $newFirstDate["yday"]-1; //take the day before the first day of next month

				//add the event in next month
				if(!isset($this->remainingEvents)) $this->remainingEvents = array();
				$this->remainingEvents[] = array($newFirstDate, $endDate, $subject, $location, $label);

			} else {
				$tempEndDate = $endDate["yday"];
			}
		}
		else {
			$tempEndDate = $tempFirstDate;
		}

		//fill label on first cell
		$s->setCellValue(num2letter($crtCol).($crtLine+1), $this->preventFormulasInCellValue($subject));

		//colorize each dates
//		unset($this->eventMarkStyle['borders']['right']);
//		unset($this->eventLabelStyle['borders']['right']);
		$s->getStyle(num2Letter($crtCol).$crtLine.':'.num2Letter($crtCol+$tempEndDate-$tempFirstDate).$crtLine)->applyFromArray($this->updateLabelColor($label, $this->eventMarkStyle));
		$s->getStyle(num2Letter($crtCol).($crtLine+1).':'.num2Letter($crtCol+$tempEndDate-$tempFirstDate).($crtLine+1))->applyFromArray($this->updateLabelColor($label, $this->eventLabelStyle));
	}

	private $count = 0;
	private $crtElementP = null;
	private $crtMonth = null;
	private $remainingEvents = null;
	public function addElementP($elementP){
		try {

//		if($this->count>2) return;

		$this->beginElement($elementP->getId());

		$this->crtElementP = $elementP;

		$exl = $this->getExcelObj();
		$p = $exl->getActiveSheetIndex();
		$s = $exl->getActiveSheet();

		$subject = $elementP->getElement()->getFieldValue($this->getSubjectField());
		if(is_array($subject)){
			$temp = "";
			foreach($subject as $i){
				if($i != null){
					if($temp != null) $temp .= ", ";
					$temp .= $i;
				}
			}
			$subject = $temp;
		}
		if($this->getLocationField()) $location = $elementP->getElement()->getFieldValue($this->getLocationField());
		else $location = null;
		if($this->getPostLocationField()) $postLocation = $elementP->getElement()->getFieldValue($this->getPostLocationField());
		else $postLocation = null;

		$label = null;
		if($this->getLabelField()) {
			$label = $elementP->getElement()->getFieldValue($this->getLabelField());
		}
		if(!$label || $label == "none") $label = "";

		//TimeRanges case
		if($this->getPeriodField()){
			$begTime = $elementP->getElement()->getFieldValue($this->getPeriodField(), "begTime");
			$begDate = $elementP->getElement()->getFieldValue($this->getPeriodField(), "begDate");
			$endTime = $elementP->getElement()->getFieldValue($this->getPeriodField(), "endTime");
			$endDate = $elementP->getElement()->getFieldValue($this->getPeriodField(), "endDate");
			if($begTime && $begTime!="00:00:00") $subject = substr($begTime, 0, 5)." ".$subject;
		//Dates case
		} else {
			$val = $elementP->getElement()->getFieldValue($this->getDateField(), "value");
			$d = $m = $y = $h = $i = $s = null;
			Dates::fromString($val, $d, $m, $y, $h, $i, $s);
			if(($h || $i || $s) && !($h==0 && $i==0 && $s==0)) $begTime = "".($h<10 ? "0".$h : $h).":".($i<10 ? "0".$i : $i).":".($s<10 ? "0".$s : $s)."";
			else $begTime = "";
			if($begTime && $begTime!="00:00:00") $subject = substr($begTime, 0, 5)." ".$subject;
			$begDate = $y."-".$m."-".$d;

			if($this->getEndDateField()){
				$val = $elementP->getElement()->getFieldValue($this->getEndDateField(), "value");
				$d = $m = $y = $h = $i = $s = null;
				Dates::fromString($val, $d, $m, $y, $h, $i, $s);
				if(($h || $i || $s) && !($h==0 && $i==0 && $s==0)) $endTime = "".($h<10 ? "0".$h : $h).":".($i<10 ? "0".$i : $i).":".($s<10 ? "0".$s : $s)."";
				else $endTime = "";
				if($endTime && $endTime!="00:00:00") $subject = substr($endTime, 0, 5)." ".$subject;
				$endDate = $y."-".$m."-".$d;
			} else {
				$endDate = $begDate;
			}
		}

//		$subject = $subject." ".$begDate."->".$endDate;

		if($location) $subject = $subject." (".$location.")";
		if($postLocation) $subject = $subject." ".$postLocation."";


		$firstDate = getDate(strtotime($begDate));
		if($firstDate["year"]!=$this->getYear()) $firstDate = getDate(strtotime($this->getYear()."-01-01"));
		//begin new month
		if($this->crtMonth!=$firstDate["mon"]){
			while($this->crtMonth < $firstDate["mon"]){
				$this->crtMonth ++;
				$this->createNewMonth($this->crtMonth);
			}
		}

		if($endDate) $endDate = getDate(strtotime($endDate));
		else $endDate = null;

		$this->displayEvent($firstDate, $endDate, $subject, $location, $label);



//		$crtLine = $this->incCrtLineForPage($p); //line for mark
//		$s->getRowDimension($crtLine)->setRowHeight($this->eventMarkHeight);
//		$this->incCrtLineForPage($p); //line for label
//		//add borders arround the last month
//		$s->getStyle("B".($crtLine).":".num2letter(32).($crtLine+1))->applyFromArray($this->inMonthBorderStyle);
//		//colorize the weekend of last month
//		foreach($this->lastMonthWeekends as $col){
//			$s->getStyle($col.($crtLine).":".$col.($crtLine+1))->applyFromArray($this->weekendStyle);
//		}
//
//		$crtCol = 1+$firstDate["mday"];
//
//		$tempEndDate = null;
//		$tempFirstDate = null;
//		if($endDate) {
//			$endDate = getDate(strtotime($endDate));
//			if($endDate["year"]!=$this->getYear()) $endDate = getDate(strtotime($this->getYear()."-12-31"));
//
//			if($endDate["mon"]!=$firstDate["mon"]){
//				$tempFirstDate = getDate(strtotime($firstDate["year"]."-".($firstDate["mon"]+1)."-01"));
//				$tempFirstDate = $tempFirstDate["yday"];
//				$tempEndDate = $tempFirstDate-1; //take the day before the first day of next month
//			}
//			if($endDate["mon"]!=$firstDate["mon"]+1){
//				//keep endDate with all info
//			} else {
//				$endDate = $endDate["yday"];
//			}
//		}
//		else {
//			$endDate = $firstDate["yday"];
//		}
//
//		$firstDate = $firstDate["yday"];
//
//		//fill label on first cell
//		$s->setCellValue(num2letter($crtCol).($crtLine+1), $subject);
//
//		//colorize each dates
//		if($tempEndDate || $tempFirstDate){
//
//			//eput("line:".$crtLine." col:".$crtCol." first:".$firstDate." tend:".$tempEndDate." tfirst:".$tempFirstDate." ".$endDate);
//			unset($this->eventMarkStyle['borders']['right']);
//			unset($this->eventLabelStyle['borders']['right']);
//			$s->getStyle(num2Letter($crtCol).$crtLine.':'.num2Letter($crtCol+$tempEndDate-$firstDate).$crtLine)->applyFromArray($this->updateLabelColor($label, $this->eventMarkStyle));
//			$s->getStyle(num2Letter($crtCol).($crtLine+1).':'.num2Letter($crtCol+$tempEndDate-$firstDate).($crtLine+1))->applyFromArray($this->updateLabelColor($label, $this->eventLabelStyle));
//			$this->eventMarkStyle['borders']['right']=$this->eventMarkStyle['borders']['left'];
//			$this->eventLabelStyle['borders']['right']=$this->eventLabelStyle['borders']['left'];
//
//			//add the end of the event!!!!
//			if(!isset($this->remainingEvents)) $this->remainingEvents = array();
//			$this->remainingEvents[] = array($tempFirstDate, $endDate, $subject, $location, $label);
//
////			$crtLine = $this->getYDayMapping($tempFirstDate, 0);
////			$crtCol = $this->getYDayMapping($tempFirstDate, 1);
////
////			//create a new mark line
////			$s->insertNewRowBefore($crtLine+2);
////			$s->getRowDimension($crtLine+1)->setRowHeight($this->eventMarkHeight);
////			$this->insertNewRowInMap($crtLine+1);
////			$crtLine ++;
////			//create a new label line
////			$s->insertNewRowBefore($crtLine+2);
////			$s->getRowDimension($crtLine+1)->setRowHeight($this->eventLabelHeight);
////			$this->insertNewRowInMap($crtLine+1);
////			//fill label on first cell
////			$s->setCellValue(num2letter($crtCol).($crtLine+1), $subject);
////
////			unset($this->eventMarkStyle['borders']['left']);
////			unset($this->eventLabelStyle['borders']['left']);
////			$s->getStyle(num2Letter($crtCol).$crtLine.':'.num2Letter($crtCol+$endDate-$tempFirstDate).$crtLine)->applyFromArray($this->updateLabelColor($label, $this->eventMarkStyle));
////			$s->getStyle(num2Letter($crtCol).($crtLine+1).':'.num2Letter($crtCol+$endDate-$tempFirstDate).($crtLine+1))->applyFromArray($this->updateLabelColor($label, $this->eventLabelStyle));
////			$this->eventMarkStyle['borders']['left']=$this->eventMarkStyle['borders']['right'];
////			$this->eventLabelStyle['borders']['left']=$this->eventLabelStyle['borders']['right'];
////
//		} else {
//			$s->getStyle(num2Letter($crtCol).$crtLine.':'.num2Letter($crtCol+$endDate-$firstDate).$crtLine)->applyFromArray($this->updateLabelColor($label, $this->eventMarkStyle));
//			$s->getStyle(num2Letter($crtCol).($crtLine+1).':'.num2Letter($crtCol+$endDate-$firstDate).($crtLine+1))->applyFromArray($this->updateLabelColor($label, $this->eventLabelStyle));
//		}

		$this->endElement();

		} catch (Exception $e){
			echo alert($e->getMessage()." in ".$e->getFile()." at line ".$e->getLine()."\n");
			echo alert($e->getTraceAsString());
			throw $e;
		}
	}

	protected function beginElement($elId){

	}

	protected function endElement(){
		$this->count++;
	}

	/**
	 * @param $cell array(row, col)
	 */
	private $ydMap;
	protected function addYDayMapping($yday, $cell){
		if(!isset($this->ydMap)) $this->ydMap = array();
		$this->ydMap[$yday] = $cell;
	}

	/**
	 * @param $what int = null: 0=>row, 1=>col
	 */
	protected function getYDayMapping($yday, $what=null){
		if(!isset($this->ydMap)) return null;
		if(!isset($this->ydMap[$yday])) return null;
		if($what === null){
			return $this->ydMap[$yday];
		}
		return $this->ydMap[$yday][$what];
	}

	/**
	 * recalculate the mapping after insert a new row before param
	 */
	protected function insertNewRowInMap($beforeRow){
		if(!isset($this->ydMap)) return null;
		$beforeRow--;
		foreach($this->ydMap as $i=>$rc){
			if($rc[0]>=$beforeRow){
				$this->ydMap[$i]=array($rc[0]+1, $rc[1]);
			}
		}
	}
	/**
	 * recalculate the mapping after insert a new column before param
	 */
	protected function insertNewColumnInMap($beforeColumn){
		if(!isset($this->ydMap)) return null;
		$beforeColumn--;
		foreach($this->ydMap as $i=>$rc){
			if($rc[1]>=$beforeColumn){
				$this->ydMap[$i]=array($rc[0], $rc[1]+1);
			}
		}
	}

	protected function createAnnualGrid(){
		$transS = ServiceProvider::getTranslationService();
		$principal = $this->getP();
		$exl = $this->getExcelObj();
		$p = $exl->getActiveSheetIndex();
		$s = $exl->getActiveSheet();

		//create day header:
		$this->setCrtColForPage(2, $p);
		$this->setCrtLineForPage(2, $p);
		for ($w=1; $w<=6; $w++){
			for ($d=1; $d<=7; $d++){
				$s->setCellValue(num2letter($this->incCrtColForPage($p)).$this->getCrtLineForPage($p), $transS->t($principal, "dayOfWeek_".$d));
			}
		}
		$s->getStyle('B2:AQ2')->applyFromArray($this->dayHeaderStyle);
		//add week-end style
		$s->getStyle('G2:H26')->applyFromArray($this->weekendStyle);
		$s->getStyle('N2:O26')->applyFromArray($this->weekendStyle);
		$s->getStyle('U2:V26')->applyFromArray($this->weekendStyle);
		$s->getStyle('AB2:AC26')->applyFromArray($this->weekendStyle);
		$s->getStyle('AI2:AJ26')->applyFromArray($this->weekendStyle);
		$s->getStyle('AP2:AQ26')->applyFromArray($this->weekendStyle);

		$this->setCrtColForPage(1, $p);
		$this->setCrtLineForPage(3, $p);
		//create month header:
		for ($m=1; $m<=12; $m++){
			$s->getStyle('A'.$this->getCrtLineForPage($p).':AQ'.$this->getCrtLineForPage($p))->applyFromArray($this->monthSeparatorStyle);
			$s->setCellValue(num2letter($this->getCrtColForPage($p)).($this->incCrtLineForPage($p)), $transS->t($principal, "month_long_".$m));
			$this->incCrtLineForPage($p);
		}
		$s->getStyle('A3:A26')->applyFromArray($this->monthHeaderStyle);

		//fill day numbers
		$this->setCrtColForPage(2, $p);
		$this->setCrtLineForPage(2, $p);
		$firstDayOfMonth = 0;
		$crtMonth = 0;
		$firstTimestamp = mktime(0, 0, 0, 1, 1, $this->getYear());
		for ($i = 0; $i<=365; $i++){
			$d = getDate($firstTimestamp+($i*24*60*60));
			if($this->getYear()!=$d["year"]) break;
			if($crtMonth != $d["mon"]){
				$firstDayOfMonth = $d["wday"]; // 0 = dimanche
				if($firstDayOfMonth == 0) $firstDayOfMonth = 7;
				$crtMonth = $d["mon"];
				$this->incCrtLineForPage($p);
				$this->incCrtLineForPage($p);
			}
			$row = $this->getCrtLineForPage($p)-1;
			$col = $firstDayOfMonth+$d["mday"];
			$add = num2letter($col).$row;
			$this->addYDayMapping($i, array($row, $col));
			$s->setCellValue($add, $d["mday"]);
		}
		$s->getStyle('B3:AQ26')->applyFromArray($this->dayStyle);

		$s->getDefaultColumnDimension()->setWidth($this->dayWidth);
		$s->getColumnDimension('A')->setWidth($this->monthWidth);

	}
	public function actOnBeforeAddElementP(){
		//create the first page
		$exl = $this->getExcelObj();
		if($this->getPageIndex() === -1){
			$exl->createSheet();
			$this->first = true;
			$this->setPageIndex($this->getPageIndex()+1);
			$exl->setActiveSheetIndex($this->getPageIndex());
			$this->setCrtLineForPage(1, $exl->getActiveSheetIndex());
			$s = $exl->getActiveSheet();
			//setup global style
			$s->getDefaultStyle()->applyFromArray($this->globalStyle);
			//setup page orientation / dimension
			$s->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT); //ORIENTATION_LANDSCAPE);
			$s->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
			//setup paging scale
			$s->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1,1);
			$s->getPageSetup()->setFitToWidth(1);
			$s->getPageSetup()->setFitToHeight(0);
			//setup margin
			$s->getPageMargins()->setBottom(1/2.5);
			$s->getPageMargins()->setTop(1/2.5);
			$s->getPageMargins()->setLeft(1/2.5);
			$s->getPageMargins()->setRight(1/2.5);
			$s->getPageMargins()->setHeader(0.5/2.5);
			$s->getPageMargins()->setFooter(0.5/2.5);
			//setup headers & footers
			$s->getHeaderFooter()->setOddHeader('&L &C &R&D &T');
			$s->getHeaderFooter()->setOddFooter('&L' . $exl->getProperties()->getTitle() . '&C &RPage &P / &N');
			//add title on first line
			$s->getStyle('A1')->applyFromArray($this->title1Style);
			$s->setCellValue('A'.$this->incCrtLineForPage($exl->getActiveSheetIndex()), $this->preventFormulasInCellValue($this->getTitle()));
			$s->getRowDimension(1)->setRowHeight($this->titleHeight);

			$s->getDefaultColumnDimension()->setWidth($this->dayWidth);
			$s->getColumnDimension('A')->setWidth($this->monthWidth);

			//reset crt position on A2
			$this->setCrtLineForPage(2, $exl->getActiveSheetIndex());
			$this->setCrtColForPage("A", $exl->getActiveSheetIndex());
		}
	}

	public function actOnFinishAddElementP($numberOfObjects){
		//if not all the months are displayed
		while($this->crtMonth < 13){
			$this->crtMonth ++;
			$this->createNewMonth($this->crtMonth);
		}

		//display the legend:
		$transS = ServiceProvider::getTranslationService();
		$exl = $this->getExcelObj();
		$s = $exl->getActiveSheet();
		$crtCol = 2;
		$crtLine = $s->getHighestRow()+1;
		//if there is at least one element and labels -> display legend
		if($this->crtElementP && $this->getLabelField()){
			$xml = $this->crtElementP->getElement()->getFieldList()->getField($this->getLabelField())->getXml();
			if($this->labelMap){
				ksort($this->labelMap);
				foreach($this->labelMap as $label=>$color){
					if($label == null){
						$label = "empty";
						$attribute = null;
					} else {
						$attribute = $xml->xpath('attribute[(text()="'.$label.'")]');
					}
					$s->setCellValue(num2Letter($crtCol+1).$crtLine, $this->preventFormulasInCellValue($transS->t($this->getP(), $label, ($attribute ? $attribute[0] : null))));
					$s->getStyle(num2Letter($crtCol).$crtLine)->getFill()->applyFromArray(array("type"=>PHPExcel_Style_Fill::FILL_SOLID, "color"=>array("argb"=>"FF".$color)));
					$crtLine++;
				}
			}
		}
	}

	/**
	 * styles & width
	 */

	private $titleHeight = 80;
	private $monthWidth = 16;
	private $dayWidth = 3.5;
	private $eventMarkHeight = 7;
	private $eventLabelHeight = 15;

	private $minDayOffset = 7;
	private $minDayBeforeOverLap = 3;

	private $globalStyle = array(
			'font' => array(
				'bold' => false,
				'color' => array('argb' => '000000'),
				'size' => 11
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			)
			,'fill' => array(
	 			'type' => PHPExcel_Style_Fill::FILL_SOLID,
	 			'color' => array('argb' => 'FFFFFFFF')
	 		)
		);
	private $title1Style = array(
			'font' => array(
				'bold' => true,
				'color' => array('argb' => '000000'),
				'size' => 18
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			)
		);
	private $dayHeaderStyle = array(
			'font' => array(
				'bold' => false,
				'color' => array('argb' => '000000'),
				'size' => 11
			)
			,'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			)
		);
	private $weekendStyle = array(
			'fill' => array(
	 			'type' => PHPExcel_Style_Fill::FILL_SOLID,
	 			'color' => array('argb' => 'FFD9D9D9')
	 		)
		);
	private $weekNbStyle = array(
			'font' => array(
				'bold' => false,
				'color' => array('argb' => '000000'),
				'size' => 11
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			),
			'fill' => array(
	 			'type' => PHPExcel_Style_Fill::FILL_SOLID,
	 			'color' => array('argb' => 'FFD9D9D9')
	 		)
		);
	private $dayStyle = array(
			'font' => array(
				'bold' => true,
				'color' => array('argb' => '000000'),
				'size' => 11
			)
			,'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			)
		);
	private $monthHeaderStyle = array(
			'font' => array(
				'bold' => true,
				'color' => array('argb' => '000000'),
				'size' => 11
			)
			,'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'indent'=>2,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			)
		);
	private $monthSeparatorStyle = array(
			'borders' => array(
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF808080'))
			)
		);
	private $monthBorderStyle = array(
			'borders' => array(
				'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF808080')),
				'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF808080')),
				'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF808080'))
			)
		);
	private $inMonthBorderStyle = array(
			'borders' => array(
				'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF808080')),
				'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF808080')),
			)
		);
	private $eventMarkStyle = array(
			'font' => array(
				'bold' => false,
				'color' => array('argb' => '000000'),
				'size' => 11
			)
			,'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			)
			,'borders' => array(
				'top' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => 'FF800080'))
				,'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => 'FF800080'))
				,'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => 'FF800080'))
				,'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => 'FF800080'))
			)
			,'fill' => array(
	 			'type' => PHPExcel_Style_Fill::FILL_SOLID,
	 			'color' => array('argb' => 'FF800080')
	 		)
		);
	private $eventLabelStyle = array(
			'font' => array(
				'bold' => false,
				'color' => array('argb' => '000000'),
				'size' => 11
			)
			,'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			)
			,'borders' => array(
				'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => 'FF800080'))
				,'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => 'FF800080'))
				,'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => 'FF800080'))
			)
		);

}


