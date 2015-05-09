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
if(!isset($ac)) $ac = $this->getAdminContext($p);
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();

$matrixRenderer = MatrixUserPListAdminRenderer::createInstance($p, "adminUserAdminMatrix", $ac->getWorkingModule());

//prepare column of the matrix
$matrixRenderer->getAllAdminLevels();
//prepare rows of the matrix
$userAS->getAllUsers($p, $matrixRenderer, $ac->getUserListFilter()); //, $userAS->getListFilterForUserList());

if($matrixRenderer->getMatrix()->countRowHeaders()==0){
	echo $transS->t($p, "noUserFound");
	if($ac->getUserListFilter()->getFieldSelectorLogExp()!=null){
		echo $transS->t($p, "resetFilterUser");
	}
}

if(!$matrixRenderer->getMatrix()->isEmpty()){
	$matrixRenderer->render($p, $exec, $this);
}




