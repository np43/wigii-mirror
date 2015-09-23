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
 * Created on 3 déc. 09
 * by LWR
 */
$target = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "target"));
$name = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "name"));
$value = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "url"));
$fieldXml = $field->getXml();
if(!$this->isForNotification()){
	if($target !="_video"){
		if($fieldXml["bigLabel"]=="1"){
			if($parentWidth>=300){
				$this->put('<div class="" style="float:left;margin-top:'.($name ? "8" : "12").'px;font-size:16px;">');
			} else {
				$this->put('<div class="" style="margin-top:'.($name ? "0" : "4").'px;">');
			}
		} /*else {
			$this->put('<div class="" style="float:left;">'); //margin-top:'.($name ? "8" : "12").'px;font-size:16px;">');
		}*/
	}
}

$this->put($this->formatValueFromRecord($fieldName, "url", $this->getRecord()));

if(!$this->isForNotification()){
	if($name!=null){
		if($target == "_self"){
			if(!preg_match('#^(((ht|f)tp(s?))\://)#i', $value)) $value = "http://".$value;
			$this->put('<br /><a href="'.$value.'" target="_self" style="text-decoration:none;"><font style="font-size:10px;font-style:italic;">('.$value.')</font></a>');
		} else if($target == "_file"){
			if(!preg_match('#^((file)\://)#i', $value)) $value = "file:///".$value;
			$this->put('<br /><a href="'.$value.'" target="_blank" style="text-decoration:none;"><font style="font-size:10px;font-style:italic;">('.$value.')</font></a>');
		} else {
			if(!preg_match('#^(((ht|f)tp(s?))\://)#i', $value)) $value = "http://".$value;
			$this->put('<br /><a href="'.$value.'" target="_blank" style="text-decoration:none;"><font style="font-size:10px;font-style:italic;">('.$value.')</font></a>');
		}
	}
	if($target !="_video" && $fieldXml["bigLabel"]=="1"){
		$this->put('</div>');
	}
}

