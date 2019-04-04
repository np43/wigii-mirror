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


if(!isset($userAS)) $userAS = ServiceProvider::getUserAdminService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($ac)) $ac = $this->getAdminContext($p);


?><div id="adminUser_list" class="BSB"><?

$userPRenderer = UserPListRenderer::createInstance($p, "adminUser_list");
$userPRenderer->prepareRendering($p, $exec, $this);
$lf = $userAS->getListFilterForRoleList();
$acFsle = $ac->getUserListFilter()->getFieldSelectorLogExp();
if($acFsle){
	$lf->getFieldSelectorLogExp()->addOperand($acFsle->reduceNegation(true));
}
$userAS->getAllUsers($p, $userPRenderer, $lf); //, $userAS->getListFilterForUserList());
$userPRenderer->endRendering($p, $exec, $this);

?></div><?

$isFromAdminUser = false;
include(TEMPLATE_PATH . "adminUserDetail.tpl.php");



