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
 * Created on 20 nov. 09
 * by LWR
 */

class PWithElementWithRecordWithEmails extends BaseEventModel {
	
	protected $element;
	public function setElement($var){ $this->element = $var; }
	public function getElement(){ return $this->element; }
	
	protected $record;
	public function setRecord($var){ $this->record = $var; }
	public function getRecord(){ return $this->record; }
	
	protected $emails;
	public function setEmails($var){ $this->emails = $var; }
	public function getEmails(){ return $this->emails; }
	
	public static function createInstance($p, $element, $record, $emails){
		$r = new self();
		$r->setP($p);
		$r->setElement($element);
		$r->setRecord($record);
		$r->setEmails($emails);
		return $r;
	}
}


