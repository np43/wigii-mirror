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

class PWithElementWithGroupPList extends BaseEventModel {

	protected $element;
	public function setElement($var){ $this->element = $var; }
	public function getElement(){ return $this->element; }

	protected $groupPList;
	public function setGroupPList($var){ $this->groupPList = $var; }
	public function getGroupPList(){ return $this->groupPList; }

	protected $linkSelector;
	public function setLinkSelector($var){ $this->linkSelector = $var; }
	/**
	 * Master (sub-)element link selector in case of subitem, owner element in case of links.
	 */
	public function getLinkSelector(){ return $this->linkSelector; }
	protected $subElementPathFromRoot;
	/**
	 * Returns the subelement path from root in case of subitem, else null
	 * @return LinkSelectorList
	 */
	public function getSubElementPathFromRoot() {return $this->subElementPathFromRoot;}
	/**
	 * In case of subitems: sets the path from the root to the sub element
	 * @param LinkSelectorList $linkSelectorList
	 */
	public function setSubElementPathFromRoot($linkSelectorList) {$this->subElementPathFromRoot = $linkSelectorList;}



	public static function createInstance($p, $element, $groupPList){
		$r = new self();
		$r->setP($p);
		$r->setElement($element);
		$r->setGroupPList($groupPList);
		return $r;
	}

	public static function createInstanceForSubElement($p, $element, $linkSelector, $subElementPathFromRoot, $groupPList=null) {
		$r = new self();
		$r->setP($p);
		$r->setElement($element);
		$r->setLinkSelector($linkSelector);
		$r->setSubElementPathFromRoot($subElementPathFromRoot);
		$r->setGroupPList($groupPList);
		return $r;
	}
}



