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
 * Created on 8 July 2011
 * by LWR
 */

//DEPENDENCIES
//$roleList = list principal roles
//$adminRoleIds = list of principal admin roles

if(!isset($config)) $config = $this->getConfigurationContext();

?><ul id="userMenu" class="sf-menu" style="" ><?

	$companyLogo = $config->getParameter($p, null, "companyLogo");
	$companyLogoMargins = $config->getParameter($p, null, "companyLogoMargin");
	if($companyLogo){
		if(!$companyLogoMargins) $companyLogoMargins = "2px 5px 2px 15px";
		$homeClick = $exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), WigiiNamespace::EMPTY_NAMESPACE_URL, Module::HOME_MODULE, "workZone", Module::HOME_MODULE, "start'+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);
		echo '<div class="SBB" style="padding:'.($companyLogoMargins).';px;float:left;border-left:none;border-right:none;border-top:none;cursor: pointer;" onclick="'.$homeClick.'"><img src="'.SITE_ROOT_forFileUrl.$companyLogo.'"/></div>';
	}
	?><li id="userName"><?
		echo '<a href="#" onclick="return false;" class="sf-with-ul">';
		if($p->isRealUserPublic()) {
			echo $transS->t($p, $realUser->getUsername(), $authS->getPublicUserConfigForPrincipal($p));
		}
		else {
			echo $realUser->getUsername();
		}
		//echo ' as '.$p->getAttachedUser()->getUsername();
		echo '<span class="sf-sub-indicator"> »</span>';
		echo '</a>';
		?><ul><?

			//change password
			if($p->canModifyRealUserPassword()){
				?><li id="userMenuChangePassword"><?
					?><a href="#" <?
						?>onclick="<?=$exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), WigiiNamespace::EMPTY_NAMESPACE_URL, Module::EMPTY_MODULE_URL, 'changePasswordDialog', 'changePassword', 'changePassword');?> return false;"<?
						?>><? echo $transS->t($p, "changeOwnPassword");?></a><?
				?></li><?
			}

			if(false){//change details
			?><li id="userMenuChangeUserDetails"><?
				?><a href="#" <?
					?>onclick="<?=$exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), WigiiNamespace::EMPTY_NAMESPACE_URL, Module::EMPTY_MODULE_URL, 'changePasswordDialog', 'changeUserDetails', 'changeUserDetails');?> return false;"<?
					?>><? echo $transS->t($p, "changeOwnDetails");?></a><?
			?></li><?
			}

			//change language
			?><li id="userMenuLanguage"><?
				echo '<a href="#" onclick="return false;" class="sf-with-ul">'.$transS->t($p, "language");
				echo '<span class="sf-sub-indicator"> »</span>';
				echo '</a>';
				?><ul><?
					foreach($transS->getVisibleLanguage() as $lang=>$language){
						?><li class="<?=$lang.($lang == $transS->getLanguage() ? " selected" : "");?>" ><?
							echo '<a href="#" onclick="'.$exec->getUpdateJsCode($p->getRealUserId(), "'+crtRoleId+'", "'+crtWigiiNamespaceUrl+'", "'+crtModuleName+'", 'NoAnswer', 'changeLanguage', 'changeLanguage/'.$lang, true, true).' return false; ">'.$language.'</a>';
						?></li><?
					}
				?></ul><?
			?></li><?

			//admin access
			if($adminRoleIds){
				?><li id="userMenuAdmin"><?
					echo '<a href="#" onclick="return false;" class="sf-with-ul">'.$transS->t($p, "openAdmin");
					echo '<span class="sf-sub-indicator"> »</span>';
					echo '</a>';
					?><ul><?
					foreach($adminRoleIds as $adminRoleId){
						$role = $roleList->getUser($adminRoleId);
						?><li class="<?=($p->getUserId() == $role->getId() ? " selected" : "");?>"><?
							?><a href="#<?=$role->getWigiiNamespace()->getWigiiNamespaceUrl()."/".Module::ADMIN_MODULE;?>" class=""<?
							?>onclick="<?=$exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), $role->getWigiiNamespace(), Module::ADMIN_MODULE, 'NoAnswer', 'openAdmin', 'navigate/user/'.$role->getId()."/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);?>"<? //we try to go in admin in the current module we are
							?>><?
							if($role->getDetail()->isWigiiNamespaceCreator()){
								echo $transS->t($p, "superAdmin").' <font class="darkGrayFont" style="font-size:small;">('.$transS->t($p, "asInRoleMenu")." ".$role->getUsername().(!$role->isRole() ? ' : '.$role->getWigiiNamespace()->getWigiiNamespaceName() : '').')</font>';
							} else {
								$tempWigiiNamespace = $role->getWigiiNamespace()->getWigiiNamespaceName();
								echo $tempWigiiNamespace.' <font class="darkGrayFont" style="font-size:small;">('.$transS->t($p, "asInRoleMenu")." ".str_replace("@".$tempWigiiNamespace, "", $role->getUsername()).')</font>';
							}
							?></a><?
						?></li><?
					}
					?></ul><?
				?></li><?
			}

			//import
			?><li id="userMenuImport" class="notHome"><?
				?><a href="#" <?
					?>onclick="update('importDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/importElementIn'); return false;"<?
					?>><? echo $transS->t($p, "importElementMenuItem");?></a><?
			?></li><?
			//update
			?><li id="userMenuUpdate" class="notHome"><?
				?><a href="#" <?
					?>onclick="update('importDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/updateElementIn'); return false;"<?
					?>><? echo $transS->t($p, "updateElementMenuItem");?></a><?
			?></li><?
			//update
			?><li id="userMenuFindDuplicates" class="notHome"><?
				?><a href="#" <?
					?>onclick="update('organizeDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/findDuplicatesIn'); return false;"<?
					?>><? echo $transS->t($p, "findDuplicatesMenuItem");?></a><?
			?></li><?

			//add indicators
			?><li id="userMenuAddIndicators" class="notHome"><?
				?><a href="#" <?
					?>onclick="update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/addIndicator'); return false;"<?
					?>><? echo $transS->t($p, "addIndicatorMenuItem");?></a><?
			?></li><?

			//logout
			?><li id="userMenuLogout"><?
				// reset wigii_anchor cookie
				$wigii_anchor_cookie = "$.cookie('wigii_anchor', '#logout',  { path: '/' });";
				echo '<a href="#" onclick="'.$wigii_anchor_cookie.$this->getJsCodeBeforeLogout($p).' '.$exec->getUpdateJsCode($p->getRealUserId(), "'+crtRoleId+'", "'+crtWigiiNamespaceUrl+'", "'+crtModuleName+'", 'NoAnswer', 'logout', 'logout', true, true).' return false;">'.$transS->t($p, ($p->isRealUserPublic()?"login":"logout")).'</a>';
			?></li><?
		?></ul><?
	?></li><?

	?><li class="home notHome" style="background-color:inherit;color:inherit;" ><?
		?><a href="#<?=Module::HOME_MODULE;?>" class="" <?
			?>onclick="<?=$exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), WigiiNamespace::EMPTY_NAMESPACE_URL, Module::HOME_MODULE, "workZone", Module::HOME_MODULE, "start'+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);?>"<?
		?>><? echo $transS->t($p, "homeButtonHome");?></a><?
	?></li><?
	if(false){
		?><li class="back homeOnly"><?
			?><a href="#" class="" <?
			?>><? echo $transS->t($p, "homeButtonBack");?></a><?
		?></li><?
	}
	//if no back user in this wigiiNamespace (happen normaly only if user is superadmin and no other role in the wigiiNamespace of
	//the superadmin)
	if($backUser){
		?><li class="admin closeAdmin notHome" style="white-space:nowrap;height:22px;color:#000;"><?
			$tempWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceName();
			?><font class="ui-corner-all SBIB F" style="font-weight:bold;vertical-align:middle;padding:1px 8px;"><?=$transS->t($p, "adminConsole")." : ".$tempWigiiNamespace;?></font>&nbsp;&nbsp;<?
			?><a href="#" class="ui-corner-all S" style="display:inline;vertical-align:middle;"<?
					//go on home when close superadmin
					if($p->isWigiiNamespaceCreator()){
						?>onclick="invalidCompleteCache();<?=$exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), WigiiNamespace::EMPTY_NAMESPACE_URL, Module::EMPTY_MODULE_URL, 'NoAnswer', 'closeAdmin', "navigate/user/".$backUser->getId()."/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);?>"<?
					} else {
						//else go in relevant namespace
						?>onclick="invalidCompleteCache();<?=$exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), "'+crtWigiiNamespaceUrl+'", "'+crtWorkingModuleName+'", 'NoAnswer', 'closeAdmin', "navigate/user/".$backUser->getId()."/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);?>"<?
					}
				?>><? echo $transS->t($p, "closeAdmin")."&nbsp;&nbsp;<b>X</b>";?></a><?
		?></li><?
	}

?></ul><?




