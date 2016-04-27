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

$val = $this->formatValueFromRecord($fieldName, "street", $this->getRecord());
if($val != null) $this->put(nl2br($val)."<br>");

$val = $this->formatValueFromRecord($fieldName, "zip_code", $this->getRecord());
if($val != null) $this->put($val."&nbsp;");

$val = $this->formatValueFromRecord($fieldName, "city", $this->getRecord());
if($val != null) $this->put($val."<br>");

$state = $this->formatValueFromRecord($fieldName, "state", $this->getRecord());
if($state != null) $this->put($state);

$country = $this->formatValueFromRecord($fieldName, "country", $this->getRecord());
if($state != null && $country != null) $this->put(" / ");
if($country != null) $this->put($country);


