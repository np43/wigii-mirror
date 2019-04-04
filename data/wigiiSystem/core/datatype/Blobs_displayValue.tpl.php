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

/*
 * Created on 3 déc. 09
 * by LWR
 */
$exec = $this->getExecutionService();

$fieldXml = $field->getXml();

$val = $this->formatValueFromRecord($fieldName, null, $this->getRecord());

if($fieldXml["displayOnRightSide"]!="1"){
	if((string)$fieldXml["height"]!=null && (string)$fieldXml["height"] != "0" && !$this->isForNotification() && !$this->isForPrint()){
		$this->put('<div style="width: 100%; max-width:'.($parentWidth).'px;height:'.$fieldXml["height"].'px;overflow-y:auto;overflow-x:auto;" >');
	} else if($this->isForPrint() && !$this->isForNotification()){
		$this->put('<div style="width: 100%; max-width:auto;overflow-y:visible;height:auto;overflow-x:auto;" >');
	} elseif (!$this->isForNotification()){
		$this->put('<div style="width:100%;max-width:'.($parentWidth).'px;overflow-y:auto;overflow-x:auto;height:auto;" >');
	}
}
if($fieldXml["htmlArea"] == "1"){
	$this->put($val);
} else {
	if($fieldXml["noWrap"] == "1"){
		$this->put(str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',nl2br($val)));
	} else {
		$this->put(nl2br($val));
	}
}

if($fieldXml["displayOnRightSide"]!="1" && !$this->isForNotification()){
	$this->put("</div>");
}

/******************
 * on the right view
 ******************/
if($fieldXml["displayOnRightSide"]=="1" && !$this->isForNotification()){
	$fieldId = $this->getDetailRenderer()->getDetailId()."__".$fieldName;
	$this->getDetailRenderer()->addJsCodeAfterShow("actOnDisplayOnRightSide('".$exec->getIdAnswer()."', '$fieldId', ".max(250, min(350, $this->getDetailRenderer()->getValueWidth())).", ".($this->getDetailRenderer()->getValueWidth()+$this->getDetailRenderer()->getLabelWidth()+40).");");
}