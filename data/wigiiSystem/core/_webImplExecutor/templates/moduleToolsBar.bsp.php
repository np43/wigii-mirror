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
if(!isset($configS)) $configS= $this->getConfigurationContext();

$companyColor = $configS->getParameter($p, null, "companyColor");
$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
?>

<div class="toolbarBox" style="width: 100%; color:<?= $rCompanyColor ?>">

    <?php

    //the refresh button is nevers used, therefore to gain clarity and space Lionel Weber removed it on the 26.07.2018
    if(false){
	    // list refresh button
	    ?><div class="refresh H"><?=$transS->t($p, "refresh");?></div><?
    }
    //add element
    ?><div class="addNewElement ui-corner-all disabledBg">+ <font><?=$transS->h($p, "addElementButton");?></font></div><?
    //in some views, the sortBy and groupBy are not relevant. In this case there are hidden
    //sortBy
    ?><!-- Sort By -->
    <div class="btn-group sortBy">
        <button type="button" class="btn btn-default dropdown-toggle dropdown-menu-right" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?=$transS->t($p, "sortBy");?> <span class="caret"></span>
        </button>
        <ul class="dropdown-menu value scrollable-menu" style="left: 8px;">
        </ul>
    </div><?
    //groupBy
    ?>
        <!-- Group By -->
        <div class="btn-group groupBy">
            <button type="button" class="btn btn-default dropdown-toggle dropdown-menu-right" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <?=$transS->t($p, "groupBy");?> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu value scrollable-menu" style="left: -80px;">
            </ul>
        </div>
    <?


    //switch view
    ?><div class="switchView H L disabledR" style="color:<?= $rCompanyColor ?>"><?=$transS->t($p, $moduleView."View");?></div><?

    //when the searchBar is reloaded, then clear the last
    //$sessAS->clearData($this, "elementListLastConfigKey");
    // module help button
    $this->includeModuleHelpAnchor($p,$exec);
    
    ?></div><?

// adds refresh js code
$exec->addJsCode('$("#searchBar div.toolbarBox div.refresh").click(function(){invalidCache("moduleView"); invalidCache("elementDialog"); update("moduleView/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/display/moduleView");});');

