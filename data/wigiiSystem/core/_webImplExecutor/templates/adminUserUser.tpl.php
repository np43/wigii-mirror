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
 * Created on 18 janv. 10
 * by LWR
 */

if(!isset($groupAS)) $groupAS = ServiceProvider::getGroupAdminService();
if(!isset($userAS)) $userAS = ServiceProvider::getUserAdminService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($ac)) $ac = $this->getAdminContext($p);

$matrixRenderer = MatrixUserPListUserPListRenderer::createInstance($p, "adminUserUserMatrix", $ac->getWorkingModule());

//prepare column of the matrix

$matrixRenderer->setCreateCol();

//$lgu = $userAS->getListFilterForUserList();
$lgu = $ac->getUser2ListFilter();
$lgu->setPageSize(20);
if($ac->getDesiredHPage() == null) $ac->setDesiredHPage(1);
$lgu->setDesiredPageNumber($ac->getDesiredHPage());
$userAS->getAllUsers($p, $matrixRenderer, $lgu);
//update the select box for paging
$exec->addJsCode(" adminUser2Paging_matchFor(".$lgu->getDesiredPageNumber().", ".ceil($lgu->getTotalNumberOfObjects()/$lgu->getPageSize()).", '".$transS->t($p, "page")."');");
$lgu->resetPagination();

$matrixRenderer->setCreateRow();
//prepare rows of the matrix
//$lgu = $userAS->getListFilterForUserList();
$lgu = $ac->getUserListFilter();
$userAS->getAllUsers($p, $matrixRenderer, $lgu);

if($matrixRenderer->getMatrix()->countRowHeaders()==0 || $matrixRenderer->getMatrix()->countColHeaders()==0){
	if($ac->getUserListFilter()->getFieldSelectorLogExp()!=null || $ac->getUser2ListFilter()->getFieldSelectorLogExp()!=null){
		echo $transS->t($p, "noResultFound");
		echo $transS->t($p, "redefineFilterUU");
	} else {
		echo '<div class="matrixError">'.$transS->t($p, "emptyMatrixUU").'</div>';
	}
}

if(!$matrixRenderer->getMatrix()->isEmpty()){
	$matrixRenderer->render($p, $exec, $this);
}



