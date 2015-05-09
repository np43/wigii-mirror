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
 * Created 01/05/2013
 * by LWR
 */

class RowListForDeleteEmailAttachements implements RowList {
	
	public static function createInstance()
	{
		$returnValue = new self();
		return $returnValue;
	}
	
	private $nb = 0;
	public function addRow($row){
		@unlink($row["path"]);
		$this->nb++;
	}
	
	public function getListIterator(){
		throw new ServiceException("UNSUPPORTED_OPERATION", ServiceException::UNSUPPORTED_OPERATION);
	}
	public function isEmpty(){
		return $this->nb == 0;
	}
	public function count(){
		return $this->nb;
	}
	
}
