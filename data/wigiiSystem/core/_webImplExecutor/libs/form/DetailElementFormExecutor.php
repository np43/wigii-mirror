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
 * Created on 15 sept. 09
 * by LWR
 */
class DetailElementFormExecutor extends FormExecutor {

	private $elementP;
	protected function setElementP($elemenP){ $this->elementP = $elemenP; }
	protected function getElementP(){ return $this->elementP; }

	public static function createInstance($wigiiExecutor, $record, $elementP, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setElementP($elementP);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setRecord($record);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){}

	protected function actOnCheckedRecord($p, $exec){}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$elS = ServiceProvider::getElementService();

		$this->getDetailRenderer()->resetJsCodeAfterShow();
		$idAnswer = $exec->getIdAnswer();
		if(!$idAnswer){
			$idAnswer = "mainDiv";
			$exec->addJsCode("$('#mainDiv').addClass('elementDialog');");
		}

		$config->mf($p, $exec->getCrtModule());
		$element = $this->getRecord();
		$elementP = $this->getElementP();

		if($this->getState() == "start"){
			if($config->getParameter($p, $exec->getCrtModule(), "preventFolderContentCaching") !="1"){
				$exec->cacheAnswer($p, $idAnswer, "selectElementDetail", "element/detail/".$element->getId());
			}
		}

		// if sub-element, checks if parent Links field is readonly or disabled
		$parentReadonly = false;
		if($element->isSubElement()) {
			$parentFieldXml = $config->getCurrentFieldXml($p);
			if(!isset($parentFieldXml)) throw new ConfigServiceException("Could not retrieve XML configuration of parent field '".$config->getCurrentFieldName()."' in module ".$config->getCurrentModule()->getModuleName(), ConfigServiceException::CONFIGURATION_ERROR);
			$parentReadonly = $parentFieldXml['readonly']=='1' || $parentFieldXml['disabled']=='1';
		}
		
		//display the toolbar only when editable, the feedback and the link is integrated in the bottom of the element:
		$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		$crtModule = $exec->getCrtModule()->getModuleUrl();
		if($elementP->getRights()->canWriteElement() || $element->isSubElement()){
			?><div class="T" href="#<?=$element->getId();?>" style="width:100%;"><?
			if($elementP->getRights()->canWriteElement()){
				$enableElementState = $this->computeEnableElementState($p, $exec, $elementP);
				//edit
				if(!($element->isState_blocked() || $elementP->isParentElementState_blocked() || $parentReadonly)) {
					?><div class="H el_edit"><?=$transS->t($p, "edit");?></div><?
				}
				//copy
				if(!$elementP->isParentElementState_blocked() && !$parentReadonly) {
					?><div class="H el_copy"><?=$transS->t($p, "copy");?></div><?
				}
				//element status
				if($enableElementState > 0 && !$parentReadonly){
					?><div class="H el_status"><?=$transS->t($p, "changeElementStates");?><?
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
					?><div class="H el_organize"><?=$transS->t($p, "organize");?></div><?
				}

				//delete
				if(!($element->isState_blocked() || $elementP->isParentElementState_blocked() || $parentReadonly) &&
					($config->getParameter($p, $exec->getCrtModule(),'enableDeleteOnlyForAdmin')=="1" && $elementP->getRights()->canModify() ||
					 $config->getParameter($p, $exec->getCrtModule(),'enableDeleteOnlyForAdmin')!="1")) {
					?><div class="H el_delete"><?=$transS->t($p, "delete");?></div><?
				}
			}

			// sub element back button
			if($element->isSubElement()) {
				?><div class="H el_back"><?=$transS->t($p, "backToParent");?></div><?
			}
			?></div><div class="clear"></div><?
		}
		//add a div here for can scroll all content except the menu above
		?><div id="scrollElement" style="<?=(!$this->isWorkzoneViewDocked())? 'overflow:auto;':''?> "><?
		?><div id="<?=$this->getFormId(); ?>" class="elementDetail" ><?
		if($element->isState_locked()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#CC4B4B;" ><legend class="ui-corner-all" style="background-color:#CC4B4B;" >';
 			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-locked.png" style="vertical-align:middle;" />&nbsp;';
			$info = $element->getArrayState_lockedInfo();
			if(is_array($info)){
				echo $transS->t($p, "lockedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$transS->t($p, "byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					echo " ".$transS->t($p, "usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.$this->getTotalWidth().'px;" >'.nl2br($info["message"]).'</div>';
			}
		}
		if($element->isState_blocked() && $elementP->isEnabledElementState_blocked()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#EA2424;" ><legend class="ui-corner-all" style="background-color:#EA2424;" >';
			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/documents/document-denied.png" style="vertical-align:middle;" />&nbsp;';
			$info = $element->getArrayState_blockedInfo();
			if(is_array($info)){
				echo $transS->t($p, "blockedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$transS->t($p, "byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					echo " ".$transS->t($p, "usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
			//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
			//			$this->setTotalWidth($this->getTotalWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.($this->getTotalWidth()).'px;" >'.nl2br($transS->t($p, $info["message"])).'</div>';
			}
		}
		if($element->isState_dismissed()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#EA2424;" ><legend class="ui-corner-all" style="background-color:#EA2424;" >';
			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-unreadable.png" style="vertical-align:middle;" />&nbsp;';
			$info = $element->getArrayState_dismissedInfo();
			if(is_array($info)){
				echo $transS->t($p, "dismissedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$transS->t($p, "byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					 echo " ".$transS->t($p, "usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
			//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
			//			$this->setTotalWidth($this->getTotalWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.($this->getTotalWidth()).'px;" >'.nl2br($transS->t($p, $info["message"])).'</div>';
			}
		}
		if($element->isState_finalized()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#008AB8;" ><legend class="ui-corner-all" style="background-color:#008AB8;" >';
			$info = $element->getArrayState_finalizedInfo();
			if(is_array($info)){
				echo $transS->t($p, "finalizedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$transS->t($p, "byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					echo " ".$transS->t($p, "usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
			//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
			//			$this->setTotalWidth($this->getTotalWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.($this->getTotalWidth()).'px;" >'.nl2br($transS->t($p, $info["message"])).'</div>';
			}
		}
		if($element->isState_approved()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#A0E061;" ><legend class="ui-corner-all" style="background-color:#A0E061;" >';
			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/status/available.png" style="vertical-align:middle;" />&nbsp;';
			$info = $element->getArrayState_approvedInfo();
			if(is_array($info)){
				echo $transS->t($p, "approvedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$transS->t($p, "byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					echo " ".$transS->t($p, "usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
			//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
			//			$this->setTotalWidth($this->getTotalWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.($this->getTotalWidth()).'px;" >'.nl2br($transS->t($p, $info["message"])).'</div>';
			}
		}
		if($element->isState_deprecated()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#FFCC33;" ><legend class="ui-corner-all" style="background-color:#FFCC33;" >';
			$info = $element->getArrayState_deprecatedInfo();
			if(is_array($info)){
				echo $transS->t($p, "deprecatedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$transS->t($p, "byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					echo " ".$transS->t($p, "usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
//			$this->setTotalWidth($this->getTotalWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.($this->getTotalWidth()).'px;" >'.nl2br($transS->t($p, $info["message"])).'</div>';
			}
		}

		$this->getDetailRenderer()->setP($p)->setRecordIsWritable($elementP->getRights()->canWriteElement() && !($elementP->getElement()->isState_blocked() || $elementP->isParentElementState_blocked()));
		$this->getTrm()->setDetailRenderer($this->getDetailRenderer());
		$this->getTrm()->displayRemainingDetails();

		//display the status
		$elS->displayElementStateAsField($p, $this->getTotalWidth(), $this->getLabelWidth(), $element);

		//since 08.01.2013 groups are displayed in the additional information
//		//display the groups
//		$elS->displayElementGroups($p, $exec, $elementP, $this->getTotalWidth(), $this->getLabelWidth(), false);

		//add higlight in each folders that the item is contained in
		if(!$element->isSubElement()) $exec->addJsCode("if($('#groupPanel').length){ $('#groupPanel li>div.highlight').removeClass('highlight');$('#".$idAnswer." .elementHistoric .group a').each(function(){ $('#groupPanel li#group_'+$(this).attr('href').match(/\((.*)\)/)[1]+'>div').addClass('highlight'); }); }");

		if($element->isState_locked()){
			echo '</fieldset> ';
			$this->setTotalWidth($this->getTotalWidth()+45);
		}
		if($element->isState_blocked() && $elementP->isEnabledElementState_blocked()){
			echo '</fieldset> ';
			$this->setTotalWidth($this->getTotalWidth()+45);
		}
		if($element->isState_dismissed()){
			echo '</fieldset> ';
			$this->setTotalWidth($this->getTotalWidth()+45);
		}
		if($element->isState_finalized()){
			echo '</fieldset> ';
			$this->setTotalWidth($this->getTotalWidth()+45);
		}
		if($element->isState_approved()){
			echo '</fieldset> ';
			$this->setTotalWidth($this->getTotalWidth()+45);
		}
		if($element->isState_deprecated()){
			echo '</fieldset> ';
			$this->setTotalWidth($this->getTotalWidth()+45);
		}
		?></div><?

		//the scrollTop is to prevent autoscroll on a group link at the bellow of the detail (happens in Safari and googleChrome)
		$exec->addJsCode("$(window).scrollTop(0); ");
		if($idAnswer!="mainDiv"){
			$this->getWigiiExecutor()->openAsDialog(
				$idAnswer, $this->getTotalWidth()+$this->getCorrectionWidth(),
				$transS->t($p, "detailElement"), (!$element->isSubElement() ? "if($('#groupPanel').length){ $('#groupPanel li>div.highlight').removeClass('highlight'); }":null));
		} else {
//			$this->getWigiiExecutor()->openAsDialog(
//				$idAnswer, $this->getTotalWidth()+$this->getCorrectionWidth(),
//				$transS->t($p, "detailElement"), "");
			$exec->addJsCode("$('#mainDiv').css('margin', '10px');");
		}
		
		if($this->isWorkzoneViewDocked()) {//add link and feedback in the dialog Title
			$findSelector = ".find('.T').append";
		} elseif($idAnswer!="mainDiv"){
			$findSelector = ".parent().find('.ui-dialog-title').after";
		} else {
			$findSelector = ".find('.T div:last').after";
		}

		// feedback
		if($config->getParameter($p, $exec->getCrtModule(), "FeedbackOnElement_enable")=="1"){
			$exec->addJsCode("$('#".$idAnswer."')$findSelector('<a class=\"H el_feedback\" href=\"#".$element->getId()."\">".$transS->t($p, "feedback")."</a>');");
		}
		//link
		//if(!$element->isSubElement())
		$exec->addJsCode("$('#".$idAnswer."')$findSelector('<a class=\"H el_sendLink\" href=\"javascript:mailToFromLink(\\'".$idAnswer."\\', \\'".str_replace("//", '\/\/', $elS->getUrlForElement($exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $element))."\\');\">".$transS->t($p, "sendLink")."</a>');");

		//print
		//use the same context for print, to use the same configuration
		$exec->addJsCode("$('#".$idAnswer."')$findSelector('<a class=\"H el_printDetails\" href=\"".str_replace("//", '\/\/', SITE_ROOT."usecontext/".$exec->getCrtContext()."/__/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/print/".$element->getId())."\" target=\"_blank\" >".$transS->t($p, "printDetails")."</a>');");

		//$exec->addJsCode("$('#elementDialogContent .tags').corner();");
		//if(!$element->isSubElement()) $exec->addJsCode("$('#".$idAnswer."').parent().css('overflow','visible');");

		//back to parent on close sub item
		if($element->isSubElement()) {
			$exec->addJsCode("$('#".$idAnswer."').parents('.ui-dialog').find('.ui-dialog-titlebar-close').unbind('click').click(function(e){ $(this).parents('.ui-dialog').find('.T .el_back').click(); return false; });");
		}
		
		if($this->isWorkzoneViewDocked()) {
			$exec->addJsCode("$('#".$idAnswer."')$findSelector('<div class=\"H el_closeDetails\">".$transS->t($p, "close")."</div>');");
		}

		//since 08.01.2013 all those informations are unified in displayElementAdditionalInformation
//		//display the id
//		if($config->getParameter($p, $exec->getCrtModule(), "Element_viewId")=="1"){
//			$elS->displayElementId($p, $element, $exec->getCrtWigiiNamespace(), $exec->getCrtModule());
//		}
//
//		//display the version
//		if($config->getParameter($p, $exec->getCrtModule(), "Element_viewVersion")=="1"){
//			$elS->displayElementVersion($p, $element);
//		}
//
//		//display element infos
//		if($config->getParameter($p, $exec->getCrtModule(), "Element_viewInfo")=="1"){
//			$elS->displayElementHistoric($p, $element);
//		}

		$elS->displayElementAdditionalInformation($p, $exec, $element,$this->getTotalWidth(), $this->getLabelWidth());
		//end of scroll div
		?></div><?
		// if subelement then
		// - replaces the copy button url by subelement/copy/...
		// - replaces the module in delete button for confirmation dialog translation
		if($element->isSubElement()) {
			$parentModuleUrl = ServiceProvider::getModuleAdminService()->getModule($p, $config->getCurrentSubElementPathFromRoot()->getLastLinkSelector()->getModuleName())->getModuleUrl();
			$parentCacheLookup = $exec->getCurrentCacheLookup($p, "selectElementDetail", "element/detail/".$element->getElementParentId());
			$exec->addJsCode("setListenersToElementDetail('".$idAnswer."', '".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."', '".$exec->getCrtModule()->getModuleUrl()."', true, '$parentModuleUrl', ".$element->getElementParentId().", '".$element->getLinkName()."', '$parentCacheLookup'); $(window).scrollTop(0); ");
		} else {
			$exec->addJsCode("setListenersToElementDetail('".$idAnswer."'); $(window).scrollTop(0); ");
		}
		$exec->addJsCode($this->getDetailRenderer()->getJsCodeAfterShow());

	}

	/**
	 * Computes the "enableElementState" menu using the ElementPolicyEvaluator if any and returns an int.
	 */
	protected function computeEnableElementState($p, $exec, $elementP) {
		// sets default policy
		$config = $this->getWigiiExecutor()->getConfigurationContext();
		$m = $elementP->getElement()->getModule();
		$elementP->enableElementState_locked($config->getParameter($p, $m, 'Element_enableLockedStatus'));
		$elementP->enableElementState_blocked($config->getParameter($p, $m, 'Element_enableBlockedStatus'));
		$elementP->enableElementState_important1($config->getParameter($p, $m, 'Element_enableImportant1Status'));
		$elementP->enableElementState_important2($config->getParameter($p, $m, 'Element_enableImportant2Status'));
		$elementP->enableElementState_finalized($config->getParameter($p, $m, 'Element_enableFinalizedStatus'));
		$elementP->enableElementState_approved($config->getParameter($p, $m, 'Element_enableApprovedStatus'));
		$elementP->enableElementState_dismissed($config->getParameter($p, $m, 'Element_enableDismissedStatus'));
		$elementP->enableElementState_archived($config->getParameter($p, $m, 'Element_enableArchivedStatus'));
		$elementP->enableElementState_deprecated($config->getParameter($p, $m, 'Element_enableDeprecatedStatus'));
		$elementP->enableElementState_hidden($config->getParameter($p, $m, 'Element_enableHiddenStatus'));

		// if subelement, then computes propagation of blocked status
		if($elementP->getElement()->isSubElement()) {
			foreach($config->getCurrentSubElementPathFromRoot()->getListIterator() as $ls) {
				if($ls->isOwnerElementBlocked()) {
					$elementP->setParentElementState_blocked(true);
					break;
				}
			}
		}

		// updates policy using the ElementPolicyEvaluator
		$policyEval = $this->getElementPolicyEvaluator();
		if(isset($policyEval)) $policyEval->computeEnableElementState($p, $elementP);

		return $elementP->getEnableElementStateAsInt();
	}
}



