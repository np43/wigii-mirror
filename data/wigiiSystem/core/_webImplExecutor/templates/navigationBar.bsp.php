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

/**
 * Wigii navigation bar and search bar
 * Created on 18.07.2017 by Medair (LMA)
 */
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($authS)) $authS = ServiceProvider::getAuthenticationService();
if(!isset($p)) $p = $authS->getMainPrincipal();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($config)) $config = $this->getConfigurationContext();
if(!isset($sessAS)) $sessAS = ServiceProvider::getSessionAdminService();
//if(!isset($moduleAS)) $moduleAS = ServiceProvider::getModuleAdminService();
if(!isset($userAS)) $userAS = ServiceProvider::getUserAdminService();

if($p->isPlayingRole()){
    $realUser = $p->getRealUser();
    $wigiiNamespace = $realUser->getWigiiNamespace()->getWigiiNamespaceName();
} else {
    $realUser = $p->getAttachedUser();
    $wigiiNamespace = $realUser->getWigiiNamespace()->getWigiiNamespaceName();
}
$roleList = $p->getRoleListener();
//add the real user itself in the list, to ensure to appear if he has some admin rights
if($roleList){
    $roleList->addUser($p->getRealUser());
    $defaultWigiiNamespace = $roleList->getDefaultWigiiNamespace();
    $adminRoleIds = $roleList->getAdminRoleIds();
    $calculatedRolesIds = $roleList->getCalculatedRoleIds();

    //Calculate the BackUser to the close buton in admin console
    $backUserId = null;
    if(!$p->isWigiiNamespaceCreator()){
        $backUserId = $roleList->getCalculatedRoleId($exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl());
    }
    if($backUserId==null){
        $backUserId = reset($roleList->getCalculatedRoleIds());
    }
    $backUser = $roleList->getUser($backUserId);
}

$companyLogo = $config->getParameter($p, null, "companyLogo");
$companyLogoMargins = $config->getParameter($p, null, "companyLogoMargin");
$crtWigiiNamespace = $defaultWigiiNamespace;
$menuItem = array();
$crtWigiiNamespace=str_replace('%20',' ',$crtWigiiNamespace);

if($roleList->getDefaultWigiiNamespaceModules()){
    $moduleReorder = reorderTabBasedOnKeyPriority($roleList->getDefaultWigiiNamespaceModules(), (string)$config->getParameter($p, null, "prioritizeModuleInHomePage"), true);
    foreach($moduleReorder as $module=>$roleId){
        $customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$module);
        $other[$customLabel]['href'] = '#'.str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;
        $onclick = $exec->getUpdateJsCode($p->getRealUser(), $roleId, $crtWigiiNamespace, $module, "NoAnswer", "userNavigate", "navigate/user/$roleId/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);
        $other[$customLabel]['onclick'] = "if(!ctrlPressed) { $onclick }";
        if($customLabel == "homePage_".$crtWigiiNamespace."_".$module) $other[$customLabel]['title'] = $transS->t($p, $module);
        else $other[$customLabel]['title'] = $customLabel; //." (".$transS->t($p, $module).")";
    }
}


if($roleList->getOtherWigiiNamespaces()){
    $wigiiNamespaceReorder = reorderTabBasedOnKeyPriority($roleList->getOtherWigiiNamespaces(), (string)$config->getParameter($p, null, "prioritizeWigiiNamespaceInHomePage"), true);
    foreach($wigiiNamespaceReorder as $crtWigiiNamespace=>$subMenu){
        $crtWigiiNamespace=str_replace('%20',' ',$crtWigiiNamespace);
        if($subMenu){
            $moduleReorder = reorderTabBasedOnKeyPriority($subMenu, (string)$config->getParameter($p, null, "prioritizeModuleInHomePage"), true);
            if(count($moduleReorder)==1){
                $roleId = reset($moduleReorder);
                $module = key($moduleReorder);
                $menuItem[$crtWigiiNamespace]['title'] = $crtWigiiNamespace;
                $menuItem[$crtWigiiNamespace]['active'] = ($roleId == $p->getUserId() && $exec->getCrtModule()->getModuleName() == $module) ? true : false;
                $menuItem[$crtWigiiNamespace]['href'] = '#'. str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;
                $menuItem[$crtWigiiNamespace]['onclick'] = 'if(!ctrlPressed){ '. $exec->getUpdateJsCode($p->getRealUser(), $roleId, $crtWigiiNamespace, $module, "NoAnswer", "userNavigate", "navigate/user/$roleId/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true). ' }';
            } else {
                $menuItem[$crtWigiiNamespace]['title'] = $crtWigiiNamespace;
                $menuItem[$crtWigiiNamespace]['active'] = ($roleId == $p->getUserId() && $exec->getCrtModule()->getModuleName() == $module) ? true : false;
                $menuItem[$crtWigiiNamespace]['href' ]= '#'. str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;
                $menuItem[$crtWigiiNamespace]['onclick'] = 'if(!ctrlPressed){ '. $exec->getUpdateJsCode($p->getRealUser(), $roleId, $crtWigiiNamespace, $module, "NoAnswer", "userNavigate", "navigate/user/$roleId/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true). ' }';
                foreach($moduleReorder as $module=>$roleId){
                    if(strpos($module, "[title]")===0) continue;
                    $customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$module);
                    if($customLabel == "homePage_".$crtWigiiNamespace."_".$module) $menuItem[$crtWigiiNamespace]['subItem'][$customLabel]['title'] = $transS->t($p, $module);
                    else $menuItem[$crtWigiiNamespace]['subItem'][$customLabel]['title'] = $customLabel;
                    $menuItem[$crtWigiiNamespace]['subItem'][$customLabel]['active'] = ($roleId == $p->getUserId() && $exec->getCrtModule()->getModuleName() == $module ? true : false);
                    $menuItem[$crtWigiiNamespace]['subItem'][$customLabel]['href'] = '#'. str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;
                    $menuItem[$crtWigiiNamespace]['subItem'][$customLabel]['onclick'] = 'if(!ctrlPressed){ '. $exec->getUpdateJsCode($p->getRealUser(), $roleId, $crtWigiiNamespace, $module, "NoAnswer", "userNavigate", "navigate/user/$roleId/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true). ' }';
                }
            }
        }
    }
}

if(!isset($configS)) $configS = $this->getConfigurationContext();
$companyColor = $configS->getParameter($p, null, "companyColor");
$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
if(!$companyColor) $companyColor = "#3E4552";
if(!$rCompanyColor) $rCompanyColor = "#fff";

//var_dump($menuItem);
//Split the username to show only intials

if($p->isRealUserPublic()) {
    $username = $transS->t($p, $realUser->getUsername(), $authS->getPublicUserConfigForPrincipal($p));
} else{
	$usernameToSplit = $realUser->getUsername();
	$usernameToSplit = explode("@", $usernameToSplit);
	$usernameToSplit = $usernameToSplit[0]; //take only left part of an @ style user
	$usernameParts = preg_split( "/(\.|-|_| )/", $usernameToSplit);
	$username = "";
	foreach($usernameParts as $part) {
		$username .= strtoupper($part[0]); //take the first letter
	}
// 	$un = explode('.', $realUser->getUsername());
//     if(!$un[1]){
//         $un = explode('_', $realUser->getUsername());
//         if(!$un[1]){
//             $un = explode('-', $realUser->getUsername());
//         }
//     }
//     $fl = strtoupper($un[0][0]);
//     $ll = strtoupper($un[1][0]);
//     $username = $fl. $ll;
}
?>

<nav id="navigationBarBsp" class="navbar navbar-default" style="background-color: <?= $companyColor ?>;color: <?= $rCompanyColor ?> !important;">
    <div id="NMContainerBsp" class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <?php if($companyLogo){
                if(!$companyLogoMargins) $companyLogoMargins = "0 5px 0 0";
                $homeClick = $exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), WigiiNamespace::EMPTY_NAMESPACE_URL, Module::HOME_MODULE, "workZone", Module::HOME_MODULE, "start'+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);
                echo '<div class="" style="padding:'.($companyLogoMargins).';px;float:left;border-left:none;border-right:none;border-top:none;cursor: pointer;margin-right:10px;" onclick="'.$homeClick.'"><img src="'.SITE_ROOT_forFileUrl.$companyLogo.'"/></div>';
            } ?>
        </div>
            <ul id="navigateMenuBsp" class="nav navbar-nav">
                <li class="dropdown">
                    <a href="#" class="username data-submenu" data-submenu data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?= $username; ?> <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li style="background: #EDEFF2;">
                            <a href="#" style=""><?= $transS->t($p, 'username') ?>: <?= $realUser->getUsername() ?></a>
                        </li>
                        <li style="background: #EDEFF2;">
                            <a href="#" style=""><?= $transS->t($p, 'principalEmail') ?>: <?= $p->getValueInGeneralContext('email') ?></a>
                        </li>
                        <?

                        //change password
                        if($p->canModifyRealUserPassword()){
                            ?><li id="userMenuChangePassword"><?
                            ?><a href="#" <?
                            ?>onclick="<?=$exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), WigiiNamespace::EMPTY_NAMESPACE_URL, Module::EMPTY_MODULE_URL, 'changePasswordDialog', 'changePassword', 'changePassword');?> return false;"<?
                            ?>><? echo $transS->t($p, "changeOwnPassword");?></a><?
                            ?></li><?
                        }

                        ?>
                        <li id="userMenuLanguage" class="dropdown-submenu"><?
                            echo '<a href="#" tabindex="0">'.$transS->t($p, "language");
                            echo '</a>';
                            ?>
                            <ul class="dropdown-menu"><?
                                foreach($transS->getVisibleLanguage() as $lang=>$language){
                                    ?><li class="<?=$lang.($lang == $transS->getLanguage() ? " selected" : "");?>" ><?
                                    echo '<a href="#" onclick="'.$exec->getUpdateJsCode($p->getRealUserId(), "'+crtRoleId+'", "'+crtWigiiNamespaceUrl+'", "'+crtModuleName+'", 'NoAnswer', 'changeLanguage', 'changeLanguage/'.$lang, true, true).' return false; ">'.$language.'</a>';
                                    ?></li><?
                                }
                                ?></ul>
                        </li>
                        <?

                        //admin access
                        if($adminRoleIds){
                            ?><li id="userMenuAdmin" class="dropdown-submenu"><?
                            echo '<a href="#" onclick="return false;" class="sf-with-ul">'.$transS->t($p, "openAdmin");
                            echo '</a>';
                            ?><ul class="dropdown-menu"><?
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
                        ?><li id="userMenuImport" class="home notHome">
                            <a href="#" <?
                            ?>onclick="update('importDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/importElementIn'); return false;"<?
                            ?>><? echo $transS->t($p, "importElementMenuItem");?></a><?
                            ?></li><?
                        //update
                        ?><li id="userMenuUpdate" class="home notHome"><?
                            ?><a href="#" <?
                            ?>onclick="update('importDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/updateElementIn'); return false;"<?
                            ?>><? echo $transS->t($p, "updateElementMenuItem");?></a><?
                            ?></li><?
                        //update
                        ?><li id="userMenuFindDuplicates" class="home notHome"><?
                            ?><a href="#" <?
                            ?>onclick="update('organizeDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/findDuplicatesIn'); return false;"<?
                            ?>><? echo $transS->t($p, "findDuplicatesMenuItem");?></a><?
                            ?></li><?

                        //add indicators
                        $responseDiv = 'elementDialog';
                        if($this->isWorkzoneViewDocked()) $responseDiv = 'confirmationDialog';
                        ?><li id="userMenuAddIndicators" class="home notHome"><?
                            ?><a href="#" <?
                            ?>onclick="update('<?= $responseDiv ?>/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/addIndicator'); return false;"<?
                            ?>><? echo $transS->t($p, "addIndicatorMenuItem");?></a><?
                            ?></li><?

                        //logout
                        ?><li id="userMenuLogout"><?
                            // reset wigii_anchor cookie
                            $wigii_anchor_cookie = "$.cookie('wigii_anchor', '#logout',  { path: '/' });";
                            echo '<a href="#" onclick="'.$wigii_anchor_cookie.$this->getJsCodeBeforeLogout($p).' '.$exec->getUpdateJsCode($p->getRealUserId(), "'+crtRoleId+'", "'+crtWigiiNamespaceUrl+'", "'+crtModuleName+'", 'NoAnswer', 'logout', 'logout', true, true).' return false;">'.$transS->t($p, ($p->isRealUserPublic()?"login":"logout")).'</a>';
                            ?></li><?
                        ?></ul><?
                    ?></li>
                <?php if(!$exec->getCrtModule()->isAdminModule()): ?>
                <li class="home notHome"><a href="#<?=Module::HOME_MODULE;?>" style="color: <?= $rCompanyColor ?>;" class="" <?
                    ?>onclick="<?=$exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), WigiiNamespace::EMPTY_NAMESPACE_URL, Module::HOME_MODULE, "workZone", Module::HOME_MODULE, "start'+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);?>"<?
                    ?>><span class="glyphicon glyphicon-home" aria-hidden="true"></span></a></li>
                        <li><a class="carret notHome" style="display:none; color: <?= $rCompanyColor ?>;">></a></li>
                <li id="base-dropdown" class="dropdown">
                    <a id="dropdown-title" href="#" class="dropdown-toggle data-submenu" data-toggle="dropdown" data-submenu role="button" aria-haspopup="true" aria-expanded="false" style="color: <?= $rCompanyColor ?>;"><?= $exec->getCrtWigiiNamespace()->getWigiiNamespaceName() ?> <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <?php if($other): ?>

                                    <?php foreach ($other as $item): ?>
                                        <?php $title2Encode = str_replace(' ', '-', $item['title']) ?>
                                        <li><a href="<?= $item['href'] ?>" id="submenu-<?= $title2Encode ?>" onclick="<?= $item['onclick'] ?>" class="toClick submenu" title="<?= $item['title'] ?>"><?= $item['title'] ?></a></li>
                                    <?php endforeach; ?>

                        <?php endif; ?>
                        <?php foreach($menuItem as $k => $v): ?>
                            <?php
                            if($v['subItem']) {
                                $onclick = each($v['subItem'])[1]['onclick'];
                            }else{
                                $onclick = $v['onclick'];
                            }

                            //Encode the title
                            $titleEncode = str_replace(' ', '-', $v['title']);
                            ?>
                            <li class="<?= ($menuItem[$v['title']]['subItem'])?'dropdown-submenu':'dropwithoutmenu' ?>" title="<?= $titleEncode ?>">
                                <a tabindex="0" <?= ($menuItem[$v['title']]['subItem'])?'href="#"':'href="'. $v['href'].'" class="toClick"' ?> onclick="<?= ($menuItem[$v['title']]['subItem'])?'':$onclick ?>"><?= $v['title'] ?></a>
                                <?php if($menuItem[$v['title']]['subItem']): ?>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($menuItem[$v['title']]['subItem'] as $item): ?>
                                            <?php $title2Encode = str_replace(' ', '-', $item['title']) ?>
                                            <li><a href="<?= $item['href'] ?>" id="submenu-<?= $title2Encode ?>" onclick="<?= $item['onclick'] ?>" class="toClick submenu" title="<?= $item['title'] ?>"><?= $item['title'] ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endif; ?>
                <li><a class="carret-submenu notHome with-submenu" style="display:none; color: <?= $rCompanyColor ?>;">></a></li>
                <?php foreach ($menuItem as $k => $v): ?>
                    <?php
                        //Encode the title
                        $titleEncode = str_replace(' ', '-', $v['title']);
                        //Create the classname for menu
                        $className = 'submenu-'. $titleEncode;
                    ?>
                    <?php if(!empty($v['subItem'])): ?>
                    <li class="dropdown notHome with-submenu" id="<?= $className ?>" style="display: none">
                        <a href="#" id="dropdown-subtitle-<?= $className ?>" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" style="color: <?= $rCompanyColor ?>;" aria-expanded="false"><?= $label ?> <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                                <?php foreach ($v['subItem'] as $item): ?>
                                    <li><a href="<?= $item['href'] ?>" onclick="<?= $item['onclick'] ?>" class="toClickSub 2ndmenu"><?= $item['title'] ?></a></li>
                                <?php endforeach; ?>
                        </ul>
                    </li>
                        <?php endif; ?>
                    <?php endforeach;

                    if($other):
                    ?>
                        <li class="dropdown notHome with-submenu" id="submenu-Other" style="display: none">
                            <a href="#" id="dropdown-subtitle-<?= $className ?>" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" style="color: <?= $rCompanyColor ?>;"><?= $label ?> <span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <?php foreach ($other as $item): ?>
                                    <li><a href="<?= $item['href'] ?>" onclick="<?= $item['onclick'] ?>" class="toClickSub 2ndmenu"><?= $item['title'] ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php
                    endif;
                //if no back user in this wigiiNamespace (happen normaly only if user is superadmin and no other role in the wigiiNamespace of
                //the superadmin)
                    ?><li class="admin closeAdmin notHome" style="display: none; white-space:nowrap;height:22px;color:#000;margin-top:5px; padding-left: 20px;"><?
                    $tempWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceName();
                    ?><font id="adminWigiiNamespace" class="ui-corner-all SBIB F" style="font-weight:bold;vertical-align:middle;padding:1px 8px;"><?=$transS->t($p, "adminConsole")." : ".$tempWigiiNamespace;?></font>&nbsp;&nbsp;<?
                    ?><a href="#" class="ui-corner-all" style="background-color:#fff;display:inline;vertical-align:middle;"
                        <?php
                        ?>onclick="invalidCompleteCache();<?=$exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), WigiiNamespace::EMPTY_NAMESPACE_URL, Module::EMPTY_MODULE_URL, 'NoAnswer', 'closeAdmin', "navigate/user/".$backUser->getId()."/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);?>"<?
                        ?>
                    >
                        <? echo $transS->t($p, "closeAdmin")."&nbsp;&nbsp;<b>X</b>";?></a><?
                    ?></li><?

                if(!isset($lc)) $lc = $lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");

                ?>

            </ul>
                <div id="searchField" class="firstBox notHome" style="width: 340px;display:none;'"><?
                    ?><input class="SBIB R" type="text" <?
                    ?>name="<?=ListContext::TextSearchField;?>" value="<?=$lc->getTextSearch();?>" /><?
                    ?>
                    <div class="searchButtons SBIB">
                    <span id="filtersButton" class="grayFont H" <?
                    ?>onmouseover="showHelp(this, '<?=str_replace('"', '&quot;', str_replace("'", '&rsquo;', $transS->t($p, "advancedFiltersHelp")));?>', 30, 'fromLeft');" onmouseout="hideHelp();" <?
                    ?>><font style="font-size:12px;font-weight:normal;"><?=$transS->t($p, "advancedFiltersButtonText");?></font></span><span id="removeFiltersButton" class="H grayFont" <?
                    ?>onmouseover="showHelp(this, '<?=str_replace('"', '&quot;', str_replace("'", '&rsquo;', $transS->t($p, "removeFilters")));?>', 30, 'fromLeft');" onmouseout="hideHelp();" <?
                    ?>>X</span>
                    </div>
                        <?
                    ?><div id="goForSearch" class="H G SBIB" ><span class="glyphicon glyphicon-search" aria-hidden="true"></span></div><?
                    ?></div>

            <div id="navToolBar"></div>

            </div>
</nav>
<?php

$exec->addJsCode("setListenersToFiltersBsp(); setFiltersButton(".($lc->getSearchBar() ? 'true' : 'false').");");

$exec->addJsCode("$('.data-submenu').submenupicker();");
if($exec->getCrtModule()->isAdminModule()){
    $exec->addJsCode("setNavigationBarInAdminStateBsp();");
} else {
    //These functions are executed in navigate case (WigiiCoreExecutor)
    //$exec->addJsCode("setNavigationBarNotInHomeStateBsp(".$config->getParameter($p, $exec->getCrtModule(), "FeedbackOnSystem_enable").");");
}
?>