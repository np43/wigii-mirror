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

$value = $this->formatValueFromRecord($fieldName, null, $this->getRecord());
$xml = $field->getXml();
$color = $xml->xpath('attribute[@color and (text()="'.$this->getRecord()->getFieldValue($fieldName).'")]');
if($color){ $color = (string)$color[0]["color"]; }
if($xml["displayAsTag"]=="1"){
	if($value != null){
		$this->put('<span class="tag ui-corner-all" style="padding:0px 10px 2px 10px;'.$xml["tagStyle"].($color ? "background-color:#".$color.";color:#".getBlackOrWhiteFromBackgroundColor($color).";" : "").'">'.$value.'</span>');
	}
} else {
	if($color){
		$this->put('<span style="padding:2px 10px 2px 10px;background-color:#'.$color.';color:#'.getBlackOrWhiteFromBackgroundColor($color).'">'.$value.'</span>');
	} else {
		$this->put($value);
	}
}


