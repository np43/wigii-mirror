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
 * Created on 28 September 2011
 * by LWR
 */

class GroupPTreeAdminGroupImpl extends GroupPTreeGroupPanelImpl {
	
	//vars from Parent
//	protected $nb;
//	protected $groups;
//	protected $p;
//	protected $exec;
//	protected $nbLevelToExpandOnInit;
//	protected $displayCM;
	
	public function createInstance($p, $exec, $nbLevelToExpandOnInit, $displayContextMenu = true){
		$gt = new self();
		$gt->setNbLevelToExpandOnInit($nbLevelToExpandOnInit);
		$gt->setP($p);
		$gt->setExec($exec);
		$gt->setDisplayContextMenu($displayContextMenu);
		return $gt;
	}
	
	protected function addEndingJsCode($p, $exec){
		$exec->addJsCode("
setListenersToAdminGroup();
adminGroupOnResize();
li = $('#adminGroup_list #group_'+adminGroup_crtSelectedGroup);
if(li.length>0){
	li.click();
} else {
	if($('#adminGroup_list  li:first').length>0){
		$('#adminGroup_list  li:first').click();
	} else {
		$('#adminGroup_detail .commands>div').not(':first').addClass('disabled');
	}
}
");
	}
}


