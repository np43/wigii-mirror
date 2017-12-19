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
$exec = $this->getExecutionService();

$fieldXml = $field->getXml();
$elementId = $this->getRecord()->getId();



$name = $this->formatValueFromRecord($fieldName, "name", $this->getRecord());
$target = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "target"));
if($this->isForNotification()){
//	$this->put($this->t($fieldName, $field->getXml()));
	$path = SITE_ROOT_forFileUrl."images/preview/prev.18.htm.png";
	$this->put('<img style="margin-right:5px;float:left;" src="'.$path.'" />');
} else {
	if ($target=="_video"){
		if($this->isForPrint()){
			if($fieldXml["bigLabel"]=="1"){
				$path = SITE_ROOT_forFileUrl."images/preview/prev.46.avi.png";
				$this->put('<img style="margin-left:'.(($parentWidth-46)/2).'px; margin-bottom:5px;" src="'.$path.'" />');
			} else {
				$path = SITE_ROOT_forFileUrl."images/preview/prev.18.avi.png";
				$this->put('<img style="margin-right:5px;float:left;" src="'.$path.'" />');
				$this->put('<font style="margin-top:2px;float:left;width: 100%; max-width:'.($parentWidth-25-18).'px;">'.$this->t($fieldName, $field->getXml())."</font>");
			}
		} else {
			//embeded video
			//we add just the name of the field
			$this->put($this->t($fieldName, $field->getXml()));
		}
	} else {
		if($fieldXml["bigLabel"]=="1"){
			$path = SITE_ROOT_forFileUrl."images/preview/prev.46.htm.png";
			//if the size of label is smaller than usual, define a width
			if($parentWidth && $parentWidth<(100-20)){
				$style = 'style="width: 100%; max-width:'.$parentWidth.'px;"';
			} else {
				$style = 'style="margin-left:'.(($parentWidth-46)/2).'px; margin-bottom:5px;"';
			}
			$this->put('<img '.$style.' src="'.$path.'" />');
		} else {
			$path = SITE_ROOT_forFileUrl."images/preview/prev.18.htm.png";
			$this->put('<img style="margin-right:5px;float:left;" src="'.$path.'" />');
			$this->put('<font style="margin-top:2px;float:left;width: 100%; max-width:'.($parentWidth-25-18).'px;">'.$this->t($fieldName, $field->getXml())."</font>");
		}

		//standard link
		//$this->put('<font style="font-size:8px;font-stlye:italic;">'.$this->t($fieldName, $field->getXml())."</font><br />");
	//	$path = SITE_ROOT_forFileUrl."images/preview/prev.46.htm.png";
	//	$this->put('<img style="margin-left:'.(($parentWidth-46)/2).'px; margin-bottom:5px;" src="'.$path.'" />');
	}
}