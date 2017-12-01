<?php
if(!isset($configS)) $configS= $this->getConfigurationContext();

$companyColor = $configS->getParameter($p, null, "companyColor");
$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
?>

<div class="toolbarBox" style="width: 100%; color:<?= $rCompanyColor ?>">

    <?php
    //when the searchBar is reloaded, then clear the last
    //$sessAS->clearData($this, "elementListLastConfigKey");
    // module help button
    $this->includeModuleHelpAnchor($p,$exec);

    // list refresh button
    ?><div class="refresh H"><?=$transS->t($p, "refresh");?></div><?
    //in some views, the sortBy and groupBy are not relevant. In this case there are hidden
    //groupBy
    ?>
        <!-- Group By -->
        <div class="btn-group groupBy">
            <button type="button" class="btn btn-default dropdown-toggle dropdown-menu-right" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <?=$transS->t($p, "groupBy");?> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu value scrollable-menu" style="left: -60px;">
            </ul>
        </div>
    <?
    //sortBy
    ?><!-- Sort By -->
    <div class="btn-group sortBy">
        <button type="button" class="btn btn-default dropdown-toggle dropdown-menu-right" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?=$transS->t($p, "sortBy");?> <span class="caret"></span>
        </button>
        <ul class="dropdown-menu value scrollable-menu" style="left: -60px;">
        </ul>
    </div><?

    //add element
    ?><div class="addNewElement ui-corner-all disabledBg">+ <font><?=$transS->h($p, "addElementButton");?></font></div><?

    //switch view
    ?><div class="switchView H L disabledR" style="color:<?= $rCompanyColor ?>"><?=$transS->t($p, $moduleView."View");?></div><?

    ?></div><?

// adds refresh js code
$exec->addJsCode('$("#searchBar div.toolbarBox div.refresh").click(function(){invalidCache("moduleView"); invalidCache("elementDialog"); update("moduleView/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/display/moduleView");});');

