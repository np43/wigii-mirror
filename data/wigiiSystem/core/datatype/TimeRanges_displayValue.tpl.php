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
 * Created on 3 déc. 09
 * by LWR
 */

$isAllDay = $this->getRecord()->getFieldValue($fieldName, "isAllDay");
$isAllDay = $isAllDay === "1" || $isAllDay ==="on" || $isAllDay === true;
$begDate = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "begDate"));
$begTime = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "begTime"));
$endDate = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "endDate"));
$endTime = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "endTime"));
$fieldXml = $field->getXml();

if($isAllDay){
	$begTime = null;
	$endTime = null;
}
if($fieldXml["displayAsTitle1"]=="1" || $fieldXml["displayAsTitle2"]=="1" || $fieldXml["displayAsTitle3"]=="1"){
	$dateFormat = "ddd, dd mmm yyyy";
	$timeFontSize = "small";
} else {
	$dateFormat = "dd mmm yyyy";
	$timeFontSize = "x-small";
}
if(!empty($begDate)){
	// si la date de début est vide, tous les champs sont considérés comme vides
	$this->put(Dates::formatDisplay($begDate, $dateFormat, null));

	if($begDate == $endDate || empty($endDate))
		null;
		//echo "<br>";

	$this->put('<font style="font-size:'.$timeFontSize.';">');
	if(!$isAllDay && !empty($begTime)){
		$this->put(" - ");
		$this->put(Times::formatDisplay($begTime, "hh:mm"));
	}
	if($endTime != null && ($begDate == $endDate || empty($endDate))){
		$this->put(' <img src="'.SITE_ROOT_forFileUrl.'images/gui/arrow-right-0.png" alt=">" /> ');
		$this->put(Times::formatDisplay($endTime, "hh:mm"));
	}
	$this->put('</font>');
}

if(!empty($endDate) && $begDate != $endDate){
	if ((!empty($endDate) && $begDate != $endDate) || (!$isAllDay && !empty($endTime)))
	$this->put(' <img src="'.SITE_ROOT_forFileUrl.'images/gui/arrow-right-0.png" alt=">" /> ');

	if(!$isAllDay && !empty($endDate) && $begDate != $endDate){
		//$this->put("<br>");
	}

	if ($begDate != $endDate)
	$this->put(Dates::formatDisplay($endDate, $dateFormat, null));

	if(!$isAllDay && !empty($endTime)){
		$this->put('<font style="font-size:'.$timeFontSize.';">');
		if (!empty($endDate) && $begDate != $endDate) $this->put(" - ");
		$this->put(Times::formatDisplay($endTime, "hh:mm").'</font>');
	}
}




