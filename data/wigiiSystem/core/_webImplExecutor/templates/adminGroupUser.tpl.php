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
 * Created on 18 janv. 10
 * by LWR
 */


if(!isset($groupAS)) $groupAS = ServiceProvider::getGroupAdminService();
if(!isset($userAS)) $userAS = ServiceProvider::getUserAdminService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($ac)) $ac = $this->getAdminContext($p);

$matrixRenderer = MatrixGroupPListUserPListRenderer::createInstance($p, "adminGroupUserMatrix", $ac);

$lgu = $ac->getUserListFilter();
$lgu->setPageSize(20);
if($ac->getDesiredHPage() == null) $ac->setDesiredHPage(1);
$lgu->setDesiredPageNumber($ac->getDesiredHPage());
$userAS->getAllUsers($p, $matrixRenderer, $lgu);
$matrixRenderer->setPageSize($lgu->getPageSize());
$matrixRenderer->setCrtPage($lgu->getDesiredPageNumber());
$matrixRenderer->setTotalNumberOfUsers($lgu->getTotalNumberOfObjects());
//update the select box for paging
$exec->addJsCode(" adminUserPaging_matchFor(".$lgu->getDesiredPageNumber().", ".ceil($lgu->getTotalNumberOfObjects()/$lgu->getPageSize()).", '".$transS->t($p, "page")."');");
//important to reset pagination, because we don't want to use pagination in the groupAS->getAllUsers
$lgu->resetPagination();

$groupPTreeArrayImpl = GroupPListTreeArrayImpl::createInstance();
$groupAS->getAllGroups($p, $ac->getWorkingModule(), $groupPTreeArrayImpl, $ac->getGroupListFilter());
$groupPTreeArrayImpl->cleanOnMarkup("r");
foreach($groupPTreeArrayImpl->getListIterator() as $groupP){
	$matrixRenderer->addGroupP($groupP);
}

if($matrixRenderer->getMatrix()->countRowHeaders()==0 || $matrixRenderer->getMatrix()->countColHeaders()==0){
	if($ac->getUserListFilter()->getFieldSelectorLogExp()!=null || $ac->getGroupListFilter()->getFieldSelectorLogExp()!=null){
		echo $transS->t($p, "noResultFound");
		echo $transS->t($p, "redefineFilterGU");
	} else {
		echo '<div class="matrixError">'.$transS->t($p, "emptyMatrixGU").'</div>';
	}
}

if(!$matrixRenderer->getMatrix()->isEmpty()){
	$matrixRenderer->render($p, $exec, $this);
}




