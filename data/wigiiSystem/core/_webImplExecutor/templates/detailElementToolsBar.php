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
 * Element detail toolbar
 * Created by Medair (LMA) on 18.07.2017 
 */
//display the toolbar only when editable, the feedback and the link is integrated in the bottom of the element:
$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
$crtModule = $exec->getCrtModule()->getModuleUrl();
if($elementP->getRights()->canWriteElement() || $element->isSubElement()){
    ?><div class="T" href="#<?=$element->getId();?>" style="width: 100%;"><?
    if($elementP->getRights()->canWriteElement()){
        $enableElementState = $this->computeEnableElementState($p, $exec, $elementP);
        //edit
        if(!($element->isState_blocked() || $elementP->isParentElementState_blocked() || $parentReadonly)) {
            ?><div class="H el_edit"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> <?=$transS->t($p, "edit");?></div><?
        }
        //copy
        if(!$elementP->isParentElementState_blocked() && !$parentReadonly) {
            ?><div class="H el_copy"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span> <?=$transS->t($p, "copy");?></div><?
        }
        //element status
        if($enableElementState > 0 && !$parentReadonly){
            ?><div class="H el_status"><span class="glyphicon glyphicon-tags" aria-hidden="true"></span> <?=$transS->t($p, "changeElementStates");?><?
            ?><div class="cm SBB elementStatusMenu" style="display:none;" ><?
            ?><div class="exit SBB">x</div><?
            if($elementP->isEnabledElementState_locked()){
                ?><div class="H fB <?=($element->isState_locked() ? 'checked' : '');?>" href="#locked"><?
                echo $transS->t($p, "state_locked");
                ?></div><?
            }
            if($elementP->isEnabledElementState_blocked()){
                ?><div class="H fB <?=($element->isState_blocked() ? 'checked' : '');?>" href="#blocked"><?
                echo $transS->t($p, "state_blocked");
                ?></div><?
            }
            if($elementP->isEnabledElementState_important1()){
                ?><div class="H fB <?=($element->isState_important1() ? 'checked' : '');?>" href="#important1"><?
                echo $transS->t($p, "state_important1");
                ?></div><?
            }
            if($elementP->isEnabledElementState_important2()){
                ?><div class="H fB <?=($element->isState_important2() ? 'checked' : '');?>" href="#important2"><?
                echo $transS->t($p, "state_important2");
                ?></div><?
            }
            if($elementP->isEnabledElementState_finalized()){
                ?><div class="H fB <?=($element->isState_finalized() ? 'checked' : '');?>" href="#finalized"><?
                echo $transS->t($p, "state_finalized");
                ?></div><?
            }
            if($elementP->isEnabledElementState_approved()){
                ?><div class="H fB <?=($element->isState_approved() ? 'checked' : '');?>" href="#approved"><?
                echo $transS->t($p, "state_approved");
                ?></div><?
            }
            if($elementP->isEnabledElementState_dismissed()){
                ?><div class="H fB <?=($element->isState_dismissed() ? 'checked' : '');?>" href="#dismissed"><?
                echo $transS->t($p, "state_dismissed");
                ?></div><?
            }
            if($elementP->isEnabledElementState_archived()){
                ?><div class="H fB <?=($element->isState_archived() ? 'checked' : '');?>" href="#archived"><?
                echo $transS->t($p, "state_archived");
                ?></div><?
            }
            if($elementP->isEnabledElementState_deprecated()){
                ?><div class="H fB <?=($element->isState_deprecated() ? 'checked' : '');?>" href="#deprecated"><?
                echo $transS->t($p, "state_deprecated");
                ?></div><?
            }
            if($elementP->isEnabledElementState_hidden()){
                ?><div class="H fB <?=($element->isState_hidden() ? 'checked' : '');?>" href="#hidden"><?
                echo $transS->t($p, "state_hidden");
                ?></div><?
            }
            ?></div><?
            ?></div><?
        }
        //modify groups sharing
        if(!$element->isSubElement() && !$element->isState_blocked() && !$parentReadonly){
            ?><div class="H el_organize"><span class="glyphicon glyphicon-th-list" aria-hidden="true"></span> <?=$transS->t($p, "organize");?></div><?
        }

        //delete
        if(!($element->isState_blocked() || $elementP->isParentElementState_blocked() || $parentReadonly) &&
            ($config->getParameter($p, $exec->getCrtModule(),'enableDeleteOnlyForAdmin')=="1" && $elementP->getRights()->canModify() ||
                $config->getParameter($p, $exec->getCrtModule(),'enableDeleteOnlyForAdmin')!="1") &&
            ((string)$config->getParameter($p, $exec->getCrtModule(),'Element_beforeDeleteExp')!=="0")) {
            ?><div class="H R el_delete"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> <?=$transS->t($p, "delete");?></div><?
        }
    }

    // sub element back button
    if($element->isSubElement()) {
        ?><div class="H el_back"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span> <?=$transS->t($p, "backToParent");?></div><?
    }
    ?></div><div class="clear"></div><?
}