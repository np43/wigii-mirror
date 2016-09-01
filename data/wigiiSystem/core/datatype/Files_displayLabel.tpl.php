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
$idAnswer = $exec->getIdAnswer();
if(!$idAnswer) $idAnswer = "mainDiv";

$fieldXml = $field->getXml();
$elementId = $this->getRecord()->getId();

/******************
 * Preview only!!!!
 ******************/
if($fieldXml["displayPreviewOnly"]=="1" && !$this->isForNotification()){

	if($elementId==0){
		$this->put($this->t($fieldName, $field->getXml())."<br />");
		$src = SITE_ROOT_forFileUrl."images/preview/preview.jpg";
		$this->put('<a href="'.$src.'" target="_self" >');
		$this->put('<img style="width:'.min(150, $parentWidth-20).'px;" src="'.$src.'" />');
		$this->put('</a>');
	} else {
		if($this->isForExternalAccess()) {
			$src = SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/downloadFromExternalAccess/".$exec->getCrtParameters(0)."/download/".$elementId."/".$field->getFieldName();
		} else {
			$src = SITE_ROOT."useContext/".$exec->getCrtContext().ExecutionServiceImpl::requestSeparator.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/".$elementId."/".$field->getFieldName();
		}

		//the default preview management is done into the download part
		//add the time to prevent rpeview caching!!!
		$this->put('<a href="'.$src.'" target="_self" >');
		$this->put('<img style="width:'.min(150, $parentWidth-20).'px;" src="'.$src.'/thumbs?_'.time().'" />');
		$this->put('</a>');
	}

/******************
 * Normal view
 ******************/
} else {
	if(!$this->getRecord()->getFieldValue($fieldName, "size") && !(strstr($this->getRecord()->getFieldValue($fieldName, "path"), "box://"))){
		$src = SITE_ROOT_forFileUrl."images/preview/preview.46.jpg";
		$this->put('<img class="prev" style="width:'.min(46, $parentWidth-20).'px;" src="'.$src.'" />');
	} else {
		//display the whole file in label, display button to interact in value:
		if($fieldXml["displayContentInDetail"]!="1" && !$this->isForNotification()){
			//the ssrc is used for the media unzip, pdf, or html type
			if(is_a($this->getRecord(), "ActivityRecord")){
				$src = SITE_ROOT."useContext/".$exec->getCrtContext().ExecutionServiceImpl::requestSeparator.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/".$this->getRecord()->getActivity()->getActivityName()."/".$field->getFieldName()."/".$this->getRecord()->getAttachedRecord()->getId();
			} else if($this->isForExternalAccess()) {
				$src = SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/downloadFromExternalAccess/".$exec->getCrtParameters(0)."/download/".$elementId."/".$field->getFieldName();
			} else {
				$src = SITE_ROOT."useContext/".$exec->getCrtContext().ExecutionServiceImpl::requestSeparator.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/".$elementId."/".$field->getFieldName();
			}
			//if the size of label is smaller than usual, define a width
			if($parentWidth && $parentWidth<(100-20)){
				$style = 'style="width:'.$parentWidth.'px;"';
			} else $style = "";
			//the default preview management is done into the download part
			//add the time to prevent preview caching!!!
			$this->put('<img class="prev" '.$style.' src="'.$src.'/thumbs?_'.time().'" />');
		} else if($fieldXml["displayContentInDetail"]=="1" && !$this->isForNotification()){
			$type = $this->getRecord()->getFieldValue($fieldName, "type");

			if (file_exists("./images/preview/prev.46$type.png")){
				$path = SITE_ROOT_forFileUrl."images/preview/prev.46".$type.".png";
			} else {
				$path = SITE_ROOT_forFileUrl."images/preview/prev.46.jpg";
			}
			$this->put('<img class="prev" style="width:'.min(46, $parentWidth-20).'px;" src="'.$path.'" />');
		} else {
			//in notification mode, we just add a link to a standard preview (which work without rights)
			$type = $this->getRecord()->getFieldValue($fieldName, "type");

			if (file_exists("./images/preview/prev.18$type.png")){
				$path = SITE_ROOT_forFileUrl."images/preview/prev.18".$type.".png";
			} else {
				$path = SITE_ROOT_forFileUrl."images/preview/prev.18.jpg";
			}
			$this->put('<img class="prev" src="'.$path.'" />');
		}
	}
}
