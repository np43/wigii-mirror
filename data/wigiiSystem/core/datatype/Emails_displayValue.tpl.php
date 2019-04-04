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

$val = $this->formatValueFromRecord($fieldName, "value", $this->getRecord());
$proofStatus = $this->getRecord()->getFieldValue($fieldName, "proofStatus");
$proofStatusImg = $this->formatValueFromRecord($fieldName, "proofStatus", $this->getRecord());
$externalAccessLevel = $this->formatValueFromRecord($fieldName, "externalAccessLevel", $this->getRecord());
$fieldXml = $field->getXml();

if($fieldXml["noEmailContextualMenu"]!="1" && !$this->isForNotification() && !$this->isForExternalAccess() && !$this->isForPrint() && !is_a($this->getRecord(), "ActivityRecord")){
	$menu = $this->formatValueFromRecord($fieldName, "externalCode", $this->getRecord());
} else $menu = null;

if($fieldXml["isMultiple"]=="1"){
	if($proofStatus==Emails::PROOF_STATUS_DELETED){
		$this->put($val.'&nbsp;'.$menu.$proofStatusImg.$externalAccessLevel);
	} else {
		$this->put('<div style="');
		if($parentWidth != null) $this->put(' float:left; width: 100%; max-width:'.$parentWidth.'px; ');
		$this->put(' max-height:150px; overflow-y:auto; ');
		$this->put('" >');
		$this->put($val);
		$this->put('</div>'.$menu.$externalAccessLevel);
	}
} else {
	$this->put($val.'&nbsp;'.$menu.$proofStatusImg.$externalAccessLevel);

}


