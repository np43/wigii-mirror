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

/* wigii ElementService implementation which integrates with wigii web site
 * Created by LWR on 10 sept 09
 */
class ElementServiceWebImpl extends ElementServiceImpl
{
	private $_debugLogger;
	private $_executionSink;
	private $elementServiceWebImplCache;
	private $elementGroupsRenderer;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ElementServiceWebImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	// dependency injection

	// service implementation

	//this is used in the Notification to display the state of the element like a field. This implementation
	public function getElementStateAsHtml($p, $element){
		$transS = ServiceProvider::getTranslationService();
		$result = "";
		if(	$element->isState_locked() ||
			$element->isState_blocked() ||
			$element->isState_important1() ||
			$element->isState_important2() ||
			$element->isState_finalized() ||
			$element->isState_approved() ||
			$element->isState_dismissed() ||
			$element->isState_archived() ||
			$element->isState_deprecated() ||
			$element->isState_hidden()
			){

			if($element->isState_locked()){
				$result .= '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-locked.png" />&nbsp;';
				$result .= '<span class="tag ui-corner-all" style="background-color:#CC4B4B;padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;">';
// 				$info = $element->getArrayState_lockedInfo();
// 				if($info){
// 					//$result .= '<span>';
// 					if(is_array($info)){
// 						$result .= $transS->t($p, "lockedThe");
// 						$result .= " ".date("d.m.Y H:i", $info["timestamp"])." ".$transS->t($p, "byUser")." ";
// 						if($info["realUserId"]==null){
// 							//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
// 							$result .= $info["username"];
// 						}
// 						if($info["realUserId"]!=null){
// 							//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
// 							$result .= $info["realUsername"];
// 							/*
// 							$result .= " ".$transS->t($p, "usingRole")." ";
// 							$result .= $info["username"];
// 							*/
// 						}
// 					} else if($info != null){
// 						$result .= $info;
// 					}
// 					//$result .= '</span>';
// 				}
// 				else $result .= $transS->t($p, 'state_locked');
				$result .= $transS->t($p, 'state_locked');
				if($element->getState_LockedInfo()!=null){
					//$result .= '<span>';
					$info = $element->getArrayState_lockedInfo();
					if($info["message"] != null) $result .= "&nbsp;(".$transS->t($p, $info["message"]).")&nbsp;";
					//$result .= '</span>';
				}
				$result .= '</span>';
			}
			if($element->isState_dismissed()){
				$result .= '<img alt="dismissed" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-unreadable.png"" />&nbsp;';
				$result .= '<span class="tag ui-corner-all" style="background-color:#EA2424;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">'.$transS->t($p, 'state_dismissed');
				if($element->getState_dismissedInfo()!=null){
					//$result .= '<span>';
					$info = $element->getArrayState_dismissedInfo();
					if($info["message"] != null) $result .= "&nbsp;(".$transS->t($p, $info["message"]).")&nbsp;";
					//$result .= '</span>';
				}
				$result .= '</span>';
			}
			if($element->isState_finalized()){
				$result .= '<span class="tag ui-corner-all" style="background-color:#008AB8;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">'.$transS->t($p, 'state_finalized');
				if($element->getState_finalizedInfo()!=null){
					//$result .= '<span>';
					$info = $element->getArrayState_finalizedInfo();
					if($info["message"] != null) $result .= "&nbsp;(".$transS->t($p, $info["message"]).")&nbsp;";
					//$result .= '</span>';
				}
				$result .= '</span>';
			}
			if($element->isState_approved()){
				$result .= '<img alt="approved" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/status/available.png" />&nbsp;';
				$result .= '<span class="tag ui-corner-all" style="background-color:#A0E061;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">'.$transS->t($p, 'state_approved');
				if($element->getState_finalizedInfo()!=null){
					//$result .= '<span>';
					$info = $element->getArrayState_approvedInfo();
					if($info["message"] != null) $result .= "&nbsp;(".$transS->t($p, $info["message"]).")&nbsp;";
					//$result .= '</span>';
				}
				$result .= '</span>';
			}
			if($element->isState_blocked()){
				$result .= '<img alt="blocked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/documents/document-denied.png" />&nbsp;';
				$result .= '<span class="tag ui-corner-all" style="background-color:#EA2424;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">'.$transS->t($p, 'state_blocked');
				if($element->getState_blockedInfo()!=null){
					//$result .= '<span>';
					$info = $element->getArrayState_blockedInfo();
					if($info["message"] != null) $result .= "&nbsp;(".$transS->t($p, $info["message"]).")&nbsp;";
					//$result .= '</span>';
				}
				$result .= '</span>';
			}
			if($element->isState_important1()){
				$result .= '<img alt="important1" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-important1.png" />&nbsp;';
				$result .= '<span class="tag ui-corner-all" style="background-color:#F5B06D;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">';
				$result .= $transS->t($p, 'state_important1');
				if($element->getState_important1Info()!=null){
					//$result .= '<span>';
					$info = $element->getArrayState_important1Info();
					if($info["message"] != null) $result .= "&nbsp;(".$transS->t($p, $info["message"]).")&nbsp;";
					//$result .= '</span>';
				}
				$result .= '</span>';
			}
			if($element->isState_important2()){
				$result .= '<img alt="important2" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-important2.png" />&nbsp;';
				$result .= '<span class="tag ui-corner-all" style="background-color:#F57E0B;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">';
				$result .= $transS->t($p, 'state_important2');
				if($element->getState_important2Info()!=null){
					//$result .= '<span>';
					$info = $element->getArrayState_important2Info();
					if($info["message"] != null) $result .= "&nbsp;(".$transS->t($p, $info["message"]).")&nbsp;";
					//$result .= '</span>';
				}
				$result .= '</span>';
			}
			if($element->isState_archived()){
				//$result .= '<img alt="archived" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-archived.png" />&nbsp;';
				$result .= '<span class="tag ui-corner-all" style="background-color:#95CAE4;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">'.$transS->t($p, 'state_archived');
				if($element->getState_archivedInfo()!=null){
					//$result .= '<span>';
					$info = $element->getArrayState_archivedInfo();
					if($info["message"] != null) $result .= "&nbsp;(".$transS->t($p, $info["message"]).")&nbsp;";
					//$result .= '</span>';
					$result .= '</span>';
				}
			}
			if($element->isState_deprecated()){
				//$result .= '<img alt="deprecated" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-deprecated.png" />&nbsp;';
				$result .= '<span class="tag ui-corner-all" style="background-color:#FFCC33;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">'.$transS->t($p, 'state_deprecated');
				if($element->getState_deprecatedInfo()!=null){
					//$result .= '<span>';
					$info = $element->getArrayState_deprecatedInfo();
					if($info["message"] != null) $result .= "&nbsp;(".$transS->t($p, $info["message"]).")&nbsp;";
					//$result .= '</span>';
				}
				$result .= '</span>';
			}
			if($element->isState_hidden()){
				//$result .= '<img alt="hidden" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-hidden.png"  />&nbsp;';
				$result .= '<span class="tag ui-corner-all" style="border: 1px solid #A3A396;background-color:transparent;padding:2px 10px 2px 10px;white-space:nowrap;vertical-align:5px;margin-bottom:4px;margin-right:5px;">'.$transS->t($p, 'state_hidden');
				if($element->getState_hiddenInfo()!=null){
					//$result .= '<span>';
					$info = $element->getArrayState_hiddenInfo();
					if($info["message"] != null) $result .= "&nbsp;(".$transS->t($p, $info["message"]).")&nbsp;";
					//$result .= '</span>';
				}
				$result .= '</span>';
			}
		}
		return $result;
	}

	public function displayElementStateAsField($p, $totalWidth, $labelWidth, $element){
		$transS = ServiceProvider::getTranslationService();
		if(	$element->isState_locked() ||
			$element->isState_blocked() ||
			$element->isState_important1() ||
			$element->isState_important2() ||
			$element->isState_finalized() ||
			$element->isState_approved() ||
			$element->isState_dismissed() ||
			$element->isState_archived() ||
			$element->isState_deprecated() ||
			$element->isState_hidden()
			){

			?><div class="elementStates field fieldBorder" style="padding-top:5px;width:<?=$totalWidth;?>px;" ><?
				?><div class="label" style="width:<?=($labelWidth-20);?>px;" ><?=$transS->t($p, "elementStates");?></div><?
				?><div class="value" style="padding-left:0px;width:<?=$totalWidth-$labelWidth;?>px;" ><?
					echo $this->getElementStateAsHtml($p, $element);
				?></div><?
			?></div><?

		}
	}

	/**
	 * deprecated since 08.01.2012
	 * use displayElementAdditionalInformation instead
	 * this was used in the Element details + in Notification to display associated group.
	 */
	public function displayElementGroups($p, $exec, $elementP, $totalWidth = null, $labelWidth = null, $isNotification=true, $title=null){
		$element = $elementP->getDbEntity();
		$transS = ServiceProvider::getTranslationService();
		$paths = $this->getGroupsPathContainingElement($p, $element);
		if($paths){
			?><div class="field fieldBorder elementGroups" style="<?=($totalWidth ? 'width:'.$totalWidth.'px;' : '');?>" ><?
				?><div class="label" style="clear:both;<?=($totalWidth ? 'width:'.($totalWidth-20).'px;' : '');?>" ><?=($title ? $title : $transS->t($p, "itemShownInFolders") );?></div><?
				?><div class="value" style="padding-left:0px;clear:both;<?=($totalWidth ? 'width:'.$totalWidth.'px;' : '');?>" ><?
					foreach($paths as $groupId=>$path){
						if($isNotification){
							?><div><img src="<?=SITE_ROOT_forFileUrl;?>images/icones/22x22/iconfolder22x22.png" style="vertical-align:middle;margin-right:5px;margin-bottom:5px;" /><a class="H" style="font-weight:normal;" href="<?=$this->getGroupAdminServiceImpl()->getUrlForGroup($exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $groupId);?>"><?=implode(" / ", $path);?> /</a></div><?
						} else {
							?><div><img src="<?=SITE_ROOT_forFileUrl;?>images/icones/22x22/iconfolder22x22.png" style="vertical-align:middle;margin-right:5px;margin-bottom:5px;" /><a class="H" style="font-weight:normal;" href="javascript:clickOnGroupInGroupPanel(<?=$groupId;?>);"><?=implode(" / ", $path);?> /</a></div><?
						}
					}
				?></div><?
			?></div><?
		}
	}

	/**
	 * deprecated since 08.01.2012
	 * use displayElementAdditionalInformation instead
	 * this was used in the Element details + in Notification to display associated group.
	 */
	private $displayGroups_cache = null;
	public function displayGroups($p, $exec, $groupPList, $totalWidth, $labelWidth, $isNotification=true, $title=null){
		$transS = ServiceProvider::getTranslationService();
		$paths = $this->getGroupAdminServiceImpl()->getGroupsPath($p, $groupPList);
		if($paths){
			?><div class="field fieldBorder elementGroups" style="width:<?=$totalWidth;?>px;" ><?
				?><div class="label" style="clear:both;width:<?=($totalWidth-20);?>px;" ><?=($title ? $title : $transS->t($p, "itemShownInFolders") );?></div><?
				?><div class="value" style="padding-left:0px;clear:both;width:<?=$totalWidth;?>px;" ><?
					foreach($paths as $groupId=>$path){
						if($isNotification){
							?><div><img src="<?=SITE_ROOT_forFileUrl;?>images/icones/22x22/iconfolder22x22.png" style="vertical-align:middle;margin-right:5px;margin-bottom:5px;" /><a class="H" style="font-weight:normal;" href="<?=$this->getGroupAdminServiceImpl()->getUrlForGroup($exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $groupId)?>"><?=implode(" / ", $path);?> /</a></div><?
						} else {
							?><div><a class="H" style="font-weight:normal;" href="javascript:clickOnGroupInGroupPanel(<?=$groupId;?>);"><?=implode(" / ", $path);?> /</a></div><?
						}
					}
				?></div><?
			?></div><?
		}
	}

	public function getHtmlElementId($p, $element, $wigiiNamespace, $module, $label=null, $onlyLink=false, $linkLabel=null, $style=null){
		$result = "";
		$transS = ServiceProvider::getTranslationService();
		if($label == null) $label = $transS->t($p, "idOfElement").": ";
		if(!$onlyLink){
			$result .= '<div class="displayId" style="'.$style.'"><span class="label" >'.$label.'</span><span class="value" >';
		}
		$result .= '<a class="H" href="'.$this->getUrlForElement($wigiiNamespace, $module, $element).'">'.($linkLabel == null ? $element->getId() : $linkLabel).'</a>';
		if(!$onlyLink){
			$result .= '</span></div>';
		}
		return $result;
	}
	/**
	 * @param linkLabel string = null, if null then the elementId is put as label
	 */
	public function displayElementId($p, $element, $wigiiNamespace, $module, $label=null, $onlyLink=false, $linkLabel=null, $style=null){
		echo $this->getHtmlElementId($p, $element, $wigiiNamespace, $module, $label, $onlyLink, $linkLabel, $style);
	}
	//element object or id
	public function getUrlForElement($wigiiNamespace, $module, $element){
		if(is_object($element)){
			$element = $element->getId();
		}
		//return SITE_ROOT.$wigiiNamespace->getWigiiNamespaceUrl()."/".$module->getModuleUrl().'/display/all/do/elementDialog/'.$wigiiNamespace->getWigiiNamespaceUrl().'/'.$module->getModuleUrl().'/element/detail/'.$element.'/';
		return str_replace(" ", "%20", SITE_ROOT."#".$wigiiNamespace->getWigiiNamespaceUrl()."/".$module->getModuleUrl().'/item/'.$element.'');
	}

	/**
	 * deprecated since 08.01.2012
	 * use displayElementAdditionalInformation instead
	 */
	public function displayElementVersion($p, $element){
		$transS = ServiceProvider::getTranslationService();
		echo '<div class="displayVersion" ><span class="label">'.$transS->t($p, "versionOfElement").': </span><span class="value">'.$element->getVersion().'</span></div>';
	}
	/**
	 * deprecated since 08.01.2012
	 * use displayElementAdditionalInformation instead
	 */
	public function displayElementHistoric($p, $element){
		$transS = ServiceProvider::getTranslationService();
		$uAS = ServiceProvider::getUserAdminService();

		?><div class="elementHistoric"><?
			?><div class="label collapsed SBB"><?=$transS->t($p, "historicOfElement");?></div><?
			?><table style="display:none;"><?
				echo '<tr><td class="label">'.$transS->t($p, "sys_creationUsername").' :</td><td>'.($element->getSys_creationUsername() ? $element->getSys_creationUsername() : "-").'</td></tr>';
				echo '<tr><td class="label">'.$transS->t($p, "sys_creationDate").' :</td><td>'.($element->getSys_creationDate() ? date("d.m.Y H:i:s", $element->getSys_creationDate()) : "-").'</td></tr>';
				echo '<tr><td class="label">'.$transS->t($p, "sys_username").' :</td><td>'.($element->getSys_username() ? $element->getSys_username() : "-").'</td></tr>';
				echo '<tr><td class="label">'.$transS->t($p, "sys_date").' :</td><td>'.($element->getSys_date() ? date("d.m.Y H:i:s", $element->getSys_date()) : "-").'</td></tr>';

			?></table><?
		?></div><?
	}


	public function displayElementAdditionalInformation($p, $exec, $element, $totalWidth, $labelWidth){
		$transS = ServiceProvider::getTranslationService();
		$uAS = ServiceProvider::getUserAdminService();

		$labelWidth -= 5; //5px is in the padding of the cell;
		?><div class="elementHistoric"><?
			?><div class="label collapsed SBB"><?=$transS->t($p, "elementAdditionalInformation");?></div><?
			?><table style="display:none;" width="<?=$totalWidth;?>"><?

				if(!$element->isSubElement()) {
					//display groups
					$paths = $this->getGroupsPathContainingElement($p, $element);
					if($paths){
						?><tr><td class="label" width="<?=$labelWidth;?>"><?=$transS->t($p, "foldersOfElement");?></td><?
						$count = 0;
						foreach($paths as $groupId=>$path){
							if($count!=0){ ?><tr><td width="<?=$labelWidth;?>"></td><?; }
							?><td class="group"><img src="<?=SITE_ROOT_forFileUrl;?>images/icones/22x22/iconfolder22x22.png" style="vertical-align:middle;margin-right:5px;margin-bottom:5px;" /><a class="H" style="font-weight:normal;" href="javascript:clickOnGroupInGroupPanel(<?=$groupId;?>);"><?=implode(" / ", $path);?> /</a></td><?
							?></tr><?;
							$count ++;
						}
	//					echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
					}
				}

				//display ID
				echo '<tr style="height:27px;"><td class="label" width="'.$labelWidth.'">'.$transS->t($p, "idOfElement").' :</td><td class="id">'.$this->getHtmlElementId($p, $element, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), null, true).'</td></tr>';

				echo '<tr><td class="label" width="'.$labelWidth.'">'.$transS->t($p, "sys_username").' :</td><td class="sys_username">'.($element->getSys_username() ? $element->getSys_username() : "-").'</td></tr>';
				echo '<tr><td class="label" width="'.$labelWidth.'">'.$transS->t($p, "sys_date").' :</td><td class="sys_date">'.($element->getSys_date() ? date("d.m.Y H:i:s", $element->getSys_date()) : "-").'</td></tr>';
				echo '<tr><td class="label" width="'.$labelWidth.'">'.$transS->t($p, "sys_creationUsername").' :</td><td class="sys_creationUsername">'.($element->getSys_creationUsername() ? $element->getSys_creationUsername() : "-").'</td></tr>';
				echo '<tr><td class="label" width="'.$labelWidth.'">'.$transS->t($p, "sys_creationDate").' :</td><td class="sys_creationDate">'.($element->getSys_creationDate() ? date("d.m.Y H:i:s", $element->getSys_creationDate()) : "-").'</td></tr>';

			?></div><?
		?></table><?
	}

	/**
	 * enables read access to this element from a public url containing the externalCode
	 * externalCode is not defined in this method. the externalCode should already exist in the Email field
	 * @return true on success
	 * @param fieldName : string / array. The only check done is if the field exist in the DB
	 * @param configGroup : group / id. No check is done on this parameter. The fieldname is not checked if corresponding to the config
	 */
	public function setExternalAccessForView($principal, $elementId, $fieldName, $externalAccessEndDate=null, $configGroup=null){
		$this->executionSink()->publishStartOperation("setExternalAccessForView", $principal);
		try
		{
			//fetches elementP
			$elementP = $this->getElementPWithoutFields($principal, $elementId);
			if(is_null($elementP)) throw new ElementServiceException("element $elementId does not exists in database", ElementServiceException::INVALID_ARGUMENT);
			$element = $elementP->getElement();

			// checks authorization
			$this->assertPrincipalAuthorizedForSetExternalAccess($principal, $elementP, $externalAccessEndDate);

			$returnValue = $this->doSetExternalAccess($principal, $elementP, $fieldName, Emails::EXTERNAL_ACCESS_VIEW, $externalAccessEndDate, $configGroup);
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("setExternalAccessForView", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setExternalAccessForView", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setExternalAccessForView", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setExternalAccessForView", $principal);
		return $returnValue;
	}

	/**
	 * enables write access to this element from a public url containing the externalCode
	 * @return 40 hexdecimal char reprensenting the external code
	 */
	public function setExternalAccessForEdit($principal, $elementId, $fieldName, $externalAccessEndDate=null, $configGroup=null){
		$this->executionSink()->publishStartOperation("setExternalAccessForEdit", $principal);
		try
		{
			//fetches elementP
			if($this->getAuthorizationService()->isPublicPrincipal($principal)){
				$element = $this->getElementWithoutFields($principal, $elementId);
				$elementP = ElementP::createInstance($element);
				$elementP->setRights(PrincipalRights::createInstance(array("canWriteElement"=>true, "canShareElement"=>true)));
			} else {
				$elementP = $this->getElementPWithoutFields($principal, $elementId);
			}
			if(is_null($elementP)) throw new ElementServiceException("element $elementId does not exists in database", ElementServiceException::INVALID_ARGUMENT);
			$element = $elementP->getElement();

			// checks authorization
			$this->assertPrincipalAuthorizedForSetExternalAccess($principal, $elementP, $externalAccessEndDate);

			$returnValue = $this->doSetExternalAccess($principal, $elementP, $fieldName, Emails::EXTERNAL_ACCESS_EDIT, $externalAccessEndDate, $configGroup);
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("setExternalAccessForEdit", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setExternalAccessForEdit", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setExternalAccessForEdit", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setExternalAccessForEdit", $principal);
		return $returnValue;
	}
	/**
	 * disables access to this element from a public url containing the externalCode
	 * @return true on success
	 */
	public function stopExternalAccess($principal, $elementId, $fieldName){
		$this->executionSink()->publishStartOperation("stopExternalAccess", $principal);
		try
		{
			//fetches elementP
			$elementP = $this->getElementPWithoutFields($principal, $elementId);
			if(is_null($elementP)) throw new ElementServiceException("element $elementId does not exists in database", ElementServiceException::INVALID_ARGUMENT);
			$element = $elementP->getElement();

			// checks authorization
			$this->assertPrincipalAuthorizedForSetExternalAccess($principal, $elementP, null);

			$returnValue = $this->doSetExternalAccess($principal, $elementP, $fieldName, Emails::EXTERNAL_ACCESS_STOP, null, null);
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("stopExternalAccess", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("stopExternalAccess", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("stopExternalAccess", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("stopExternalAccess", $principal);
		return $returnValue;
	}

	/**
	 * enables read access to this list of element from a public url containing the externalCode
	 * externalCode is not defined in this method. the externalCode should already exist in the Email field
	 * @return true on success
	 * @param fieldName : string / array. The only check done is if the field exist in the DB
	 * @param configGroup : group / id. No check is done on this parameter. The fieldname is not checked if corresponding to the config
	 */
	public function setMultipleExternalAccessForView($principal, $elementIds, $fieldName, $externalAccessEndDate=null, $configGroupList=null){
		$this->executionSink()->publishStartOperation("setMultipleExternalAccessForView", $principal);
		try
		{
			//fetches elementP list
			$elementPList = ElementPAdvancedListArrayImpl::createInstance();
			$lf = ListFilter::createInstance();
			$fsl = FieldSelectorListArrayImpl::createInstance();
			if(is_array($fieldName)){
				foreach($fieldName as $fieldNameItem){
					$fsl->addFieldSelector($fieldNameItem);
				}
			} else {
				$fsl->addFieldSelector($fieldName);
			}
			$lf->setFieldSelectorList($fsl);
			$lf->setConfigGroupList($configGroupList);
			$this->getSelectedElements($principal, $elementIds, $elementPList, $lf);

			if(is_null($elementPList)) throw new ElementServiceException("elementIds contains unexisting elements (not exists in database)", ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForSetExternalAccess($principal, $elementPList, $externalAccessEndDate);

			$returnValue = $this->doSetExternalAccess($principal, $elementPList, $fieldName, Emails::EXTERNAL_ACCESS_VIEW, $externalAccessEndDate, ($configGroupList ? reset($configGroupList->getListIterator())->getId() : null));
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("setMultipleExternalAccessForView", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setMultipleExternalAccessForView", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setMultipleExternalAccessForView", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setMultipleExternalAccessForView", $principal);
		return $returnValue;
	}

	/**
	 * enables write access to those elements from a public url containing the externalCode
	 * @return 40 hexdecimal char reprensenting the external code
	 */
	public function setMultipleExternalAccessForEdit($principal, $elementIds, $fieldName, $externalAccessEndDate=null, $configGroupList=null){
		$this->executionSink()->publishStartOperation("setMultipleExternalAccessForEdit", $principal);
		try
		{
			//fetches elementP list
			$elementPList = ElementPAdvancedListArrayImpl::createInstance();
			$lf = ListFilter::createInstance();
			$fsl = FieldSelectorListArrayImpl::createInstance();
			if(is_array($fieldName)){
				foreach($fieldName as $fieldNameItem){
					$fsl->addFieldSelector($fieldNameItem);
				}
			} else {
				$fsl->addFieldSelector($fieldName);
			}
			$lf->setFieldSelectorList($fsl);
			$lf->setConfigGroupList($configGroupList);
			$this->getSelectedElements($principal, $elementIds, $elementPList, $lf);

			if(is_null($elementPList)) throw new ElementServiceException("elementIds contains unexisting elements (not exists in database)", ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForSetExternalAccess($principal, $elementPList, $externalAccessEndDate);

			$returnValue = $this->doSetExternalAccess($principal, $elementPList, $fieldName, Emails::EXTERNAL_ACCESS_EDIT, $externalAccessEndDate, ($configGroupList ? reset($configGroupList->getListIterator())->getId() : null));
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("setMultipleExternalAccessForEdit", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setMultipleExternalAccessForEdit", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setMultipleExternalAccessForEdit", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setMultipleExternalAccessForEdit", $principal);
		return $returnValue;
	}
	/**
	 * disables access to those elements from a public url containing the externalCode
	 * @return true on success
	 */
	public function stopMultipleExternalAccess($principal, $elementIds, $fieldName){
		$this->executionSink()->publishStartOperation("stopMultipleExternalAccess", $principal);
		try
		{
			//fetches elementP list
			$elementPList = ElementPAdvancedListArrayImpl::createInstance();
			$lf = ListFilter::createInstance();
			$fsl = FieldSelectorListArrayImpl::createInstance();
			if(is_array($fieldName)){
				foreach($fieldName as $fieldNameItem){
					$fsl->addFieldSelector($fieldNameItem);
				}
			} else {
				$fsl->addFieldSelector($fieldName);
			}
			$lf->setFieldSelectorList($fsl);
			$this->getSelectedElements($principal, $elementIds, $elementPList, $lf);

			if(is_null($elementPList)) throw new ElementServiceException("elementIds contains unexisting elements (not exists in database)", ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForSetExternalAccess($principal, $elementPList, null);

			$returnValue = $this->doSetExternalAccess($principal, $elementPList, $fieldName, Emails::EXTERNAL_ACCESS_STOP, null, null);
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("stopMultipleExternalAccess", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("stopMultipleExternalAccess", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("stopMultipleExternalAccess", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("stopMultipleExternalAccess", $principal);
		return $returnValue;
	}

	protected function assertPrincipalAuthorizedForSetExternalAccess($principal, $elementP, $externalAccessEndDate)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementP)) throw new ElementServiceException('elementP can not be null', ElementServiceException::INVALID_ARGUMENT);
		if($externalAccessEndDate!=null && !is_numeric($externalAccessEndDate)) throw new ElementServiceException('externalAccessEndDate should be numeric timestamp: '.$externalAccessEndDate, ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "setExternalAccess");
		// checks rights on Element
		if(is_a($elementP, "ElementPAdvancedList")){
			if(!$elementP->allHaveWriteRights())
			{
				$autoS->fail($principal, 'has no right to update elements '.implode(",", $elementP->getElementIds()).' for external access');
			}
		} else {
			$pRights = $elementP->getRights();
			if(!isset($pRights) || !$pRights->canWriteElement())
			{
				$autoS->fail($principal, 'has no right to update element '.$elementP->getId().' for external access');
			}
		}
	}

	protected function doSetExternalAccess($principal, $elementP, $fieldName, $externalAccessLevel, $externalAccessEndDate=null, $configGroup=null){
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

		// acquires lock
		$shouldUnlock = $this->lock($principal, $elementP);
		try
		{
			// ok, go on
			$sql = $this->getSqlForDoSetExternalAccess($principal, $elementP, $fieldName, $externalAccessLevel, $externalAccessEndDate, $configGroup);
			$returnValue = $this->getMySqlFacade()->update($principal,
				$sql,
				$dbCS);
		}
		// releases lock
		catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $elementP); throw $ne;}
		if($shouldUnlock) $this->unLock($principal, $elementP);

		if(!$returnValue){
			$this->executionSink()->log("doSetExternalAccess has affected no values with request $sql. Possible cause: either no email exist, or the values where already as is.");
			//throw new ElementServiceException("no emails exists for element $elementId and field $fieldName.", ElementServiceException::INVALID_ARGUMENT);
		}
		return $returnValue;
	}
	protected function getSqlForDoSetExternalAccess($principal, $elementP, $fieldName, $externalAccessLevel, $externalAccessEndDate, $configGroup){
		if($configGroup != null){
			if(!is_string($configGroup) && !is_int($configGroup)){
				$configGroup = $configGroup->getId();
			}
		}
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Emails');
		$sqlB->updateValue('externalConfigGroup', $configGroup, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('externalAccessLevel', $externalAccessLevel, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('externalAccessEndDate', $externalAccessEndDate, MySqlQueryBuilder::SQLTYPE_INT);
		if(is_array($fieldName)){
			$fieldName = implode('","', $fieldName);
		}
		if(is_a($elementP, "ElementPAdvancedList")){
			$sqlB->setWhereClause('id_element IN('.implode(",", $elementP->getElementIds()).') AND field IN("'.$fieldName.'") AND (value != "" AND value IS NOT NULL)');
		} else {
			$sqlB->setWhereClause('id_element = '.$elementP->getId().' AND field IN("'.$fieldName.'") AND (value != "" AND value IS NOT NULL)');
		}
		$sql = $sqlB->getSql();
		return $sql;
	}

	/**
	 * @return 40 hexdecimal char representing the external code (the code is unique per elementId / field / value)
	 */
	public function getEmailExternalCode($principal, $idElement, $fieldName, $value){
		//this logic is defined as well in: getSqlForFillEmptyEmailValidationAndExternalCode
		return sha1(trim(strtolower(CLIENT_NAME."-".microtime()."-".time()."-".rand()."-".$fieldName."-".$value)));
	}
	public function getExternalAccessLinkFromCode($principal, $wigiiNamespace, $module, $code){
		return str_replace(" ", "%20", SITE_ROOT.$wigiiNamespace->getWigiiNamespaceUrl()."/".$module->getModuleUrl()."/externalAccess/".$code);
	}

	/**
	 * retrieve element information from an external code.
	 * @param principal, must be public principal or root principal
	 * @return array($elementId, $fieldName, $value, $externalAccessLevel, $externalConfigGroup)
	 */
	public function getElementInfoFromExternalCode($principal, $externalCode){
		$this->executionSink()->publishStartOperation("getElementInfoFromExternalCode", $principal);
		try
		{
			// checks authorization
			//only public principal can validate emails
			$this->assertPrincipalAuthorizedForGetElementInfoFromExternalCode($principal, $externalCode);

			$returnValue = $this->getCached("getElementInfoFromExternalCode", array($externalCode));
			if(is_null($returnValue)){
				$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

				//no need to lock to get info
				$returnValue = $this->getMySqlFacade()->selectFirst($principal,
					$this->getSqlForGetElementInfoFromExternalCode($principal, $externalCode),
					$dbCS);

				$this->cache($returnValue, "getElementInfoFromExternalCode", array($externalCode));
			}

			if(!$returnValue){
				throw new ElementServiceException("no emails exists with this code: ".$externalCode, ElementServiceException::INVALID_ARGUMENT);
			}
			$returnValue = array_values($returnValue);
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("getElementInfoFromExternalCode", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getElementInfoFromExternalCode", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getElementInfoFromExternalCode", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getElementInfoFromExternalCode", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetElementInfoFromExternalCode($principal, $externalCode)
	{
		if(is_null($externalCode)) throw new ElementServiceException('code can not be null', ElementServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "getElementInfoFromExternalCode");
	}
	protected function getSqlForGetElementInfoFromExternalCode($principal, $externalCode){
		$sql = 'SELECT id_element, field, value, externalAccessLevel, externalAccessEndDate, externalConfigGroup FROM Emails WHERE externalCode = "'.$externalCode.'"';
		return $sql;
	}

	public function fillEmptyEmailValidationAndExternalCode($principal){
		$this->executionSink()->publishStartOperation("fillEmptyEmailValidationAndExternalCode", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForFillEmptyEmailValidationAndExternalCode($principal);

			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			try
			{
				$returnValue = $this->getMySqlFacade()->update($principal,
					$this->getSqlForFillEmptyEmailValidationAndExternalCode($principal),
					$dbCS);
			}
			catch(Exception $ne) { throw $ne;}
		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("fillEmptyEmailValidationAndExternalCode", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("fillEmptyEmailValidationAndExternalCode", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("fillEmptyEmailValidationAndExternalCode", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("fillEmptyEmailValidationAndExternalCode", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForFillEmptyEmailValidationAndExternalCode($principal)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "fillEmptyEmailValidationAndExternalCode");

	}
	protected function getSqlForFillEmptyEmailValidationAndExternalCode($principal){
		$sql = " UPDATE Emails SET `proofKey` = ".$this->getSqlLogicForValidationCode().", `externalCode` = ".$this->getSqlLogicForExternalCode()." WHERE (`proofKey` IS NULL OR `externalCode` IS NULL) AND (`value` IS NOT NULL OR `value` != '')";
		return $sql;
	}
	protected function getSqlLogicForValidationCode(){
		//getEmailValidationCode: md5(trim(strtolower(CLIENT_NAME."-".microtime()."-".time()."-".rand()."-".$email)));
		$salt = microtime()."-".time()."-".rand();
		return "MD5(TRIM(LOWER(CONCAT('".CLIENT_NAME."-', '$salt', '-',`value`))))";
	}
	protected function getSqlLogicForExternalCode(){
		//getEmailExternalCode: sha1(trim(strtolower(CLIENT_NAME."-"."-".microtime()."-".time()."-".rand()."-".$fieldName."-".$value)));
		$salt = microtime()."-".time()."-".rand();
		return "SHA1(TRIM(LOWER(CONCAT('".CLIENT_NAME."-', '$salt', '-', `field`, '-',`value`))))";
	}

	public function correctEmailValidationCode($principal, $elementIds){
		$this->executionSink()->publishStartOperation("correctEmailValidationCode", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForCorrectEmailValidationCode($principal, $elementIds);

			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			try
			{
				$returnValue = $this->getMySqlFacade()->update($principal,
					$this->getSqlForCorrectEmailValidationCode($principal, $elementIds),
					$dbCS);
			}
			catch(Exception $ne) { throw $ne;}
		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("correctEmailValidationCode", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("correctEmailValidationCode", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("correctEmailValidationCode", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("correctEmailValidationCode", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForCorrectEmailValidationCode($principal, $elementIds)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementIds)) throw new ElementServiceException('elementIds can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "correctEmailValidationCode");

	}
	protected function getSqlForCorrectEmailValidationCode($principal, $elementIds){
		$sql = " UPDATE Emails SET `proofKey` = ".$this->getSqlLogicForValidationCode().", `proof` = NULL, `proofStatus` = 0, `externalCode` = ".$this->getSqlLogicForExternalCode().", `externalAccessLevel` = 0, `externalAccessEndDate` = NULL WHERE `id_element` IN (".implode(",",$elementIds).") AND (`value` IS NOT NULL OR `value` != '') AND `proofKey` != ".$this->getSqlLogicForValidationCode()."";
		return $sql;
	}

	/**
	 * Gets or refreshes an element with data from the database.
	 * principal: public principal or root principal
	 * externalCode: an externalCode (40 char) allowing to retrieve information on the element to fetch
	 * element : an element instance to fill
	 * fieldSelectorList : only selected fields will be filled
	 * throws ElementServiceException in case of error
	 * returns an ElementP wrapping the element with principal rights or null if no element with this id.
	 * precondition: WigiiBag and FieldList instances must have been set in element
	 */
	public function fillElementFromExternalCode($principal, $externalCode, $element, $fieldSelectorList=null){
		$this->executionSink()->publishStartOperation("fillElementFromExternalCode", $principal);
		try
		{
			// checks authorization
			//only public principal can validate emails
			$this->assertPrincipalAuthorizedForFillElementFromExternalCode($principal, $externalCode);

			list($elementId, $fieldName, $value, $externalAccessLevel, $externalAccessEndDate, $externalConfigGroup) = $this->getElementInfoFromExternalCode($principal, $externalCode);
			if($externalAccessLevel < Emails::EXTERNAL_ACCESS_VIEW){
				throw new ElementServiceException("external access level is not sufficient:".$externalAccessLevel, ElementServiceException::FORBIDDEN);
			}
			if($externalAccessEndDate!=null && $externalAccessEndDate < time()){
				throw new ElementServiceException("externalAccessEndDate is reached:".date("d.m.Y H:i:s", $externalAccessEndDate), ElementServiceException::FORBIDDEN);
			}
			$elementP = $this->fillElement($principal, $element, $fieldSelectorList);
			//PRights from public principal are always canWrite + canShare
			//update PRights on element based on the externalAccess code
			if($externalAccessLevel < Emails::EXTERNAL_ACCESS_EDIT){
				$elementP->getRights()->setCanWriteElement(false);
				$elementP->getRights()->setCanShareElement(false);
			}
			//update PRights on element state
			if($element->isState_blocked() || $elementP->isParentElementState_blocked()){
				$elementP->getRights()->setCanWriteElement(false);
				$elementP->getRights()->setCanShareElement(false);
			}
			$returnValue = $elementP;
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("fillElementFromExternalCode", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("fillElementFromExternalCode", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("fillElementFromExternalCode", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("fillElementFromExternalCode", $principal);
		return $returnValue;
	}

	protected function assertPrincipalAuthorizedForFillElementFromExternalCode($principal, $externalCode)
	{
		if(is_null($externalCode)) throw new ElementServiceException('code can not be null', ElementServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "fillElementFromExternalCode");
	}



	public function getEmailValidationCode($principal, $email){
		//this logic is defined as well in: getSqlForFillEmptyEmailValidationAndExternalCode
		//until 09/07/2013 Validation code was not unique withing the whole client. Same emails where giving the same validation code.
		//the reason behind was to validate all those emails within the client. However since 09/07/2013 the experience showed that
		//usual need is more to be able to validate an email against the item itself (as it could be the result of a subscription in
		//a specific folder). The advantage is that we can then manage unsubscribe links towards the specific card itself.
		//return md5(trim(strtolower(CLIENT_NAME.$email)));
		return md5(trim(strtolower(CLIENT_NAME."-".microtime()."-".time()."-".rand()."-".$email)));
	}
	public function getEmailValidationLink($principal, $email){
		return SITE_ROOT.WigiiNamespace::EMPTY_NAMESPACE_URL."/".Module::EMPTY_MODULE_URL."/validateEmailFromCode/".$this->getEmailValidationCode($principal, $email);
	}
	public function getEmailValidationLinkFromCode($principal, $code){
		return SITE_ROOT.WigiiNamespace::EMPTY_NAMESPACE_URL."/".Module::EMPTY_MODULE_URL."/validateEmailFromCode/".$code;
	}
	public function getEmailUnsubscribeLinkFromCode($principal, $code){
		return SITE_ROOT.WigiiNamespace::EMPTY_NAMESPACE_URL."/".Module::EMPTY_MODULE_URL."/unsubscribeEmailFromCode/".$code;
	}
	public function validateEmailFromCode($principal, $code){
		$this->executionSink()->publishStartOperation("validateEmailFromCode", $principal);
		try
		{
			// checks authorization
			//only public principal can validate emails
			$this->assertPrincipalAuthorizedForValidateEmailFromCode($principal, $code);

			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			//don't need to lock or unlock elements because the proof field is always ignored in the update

			//update the emails with the proof code
			$returnValue = $this->getMySqlFacade()->update($principal,
				$this->getSqlForSetValidateEmailFromCode($principal, $code),
				$dbCS);

			//if no change this is ok because it could already be validated once. (in this case I do not revalidate the email)
			if(!$returnValue){
				//if no result check if the code exist already
				$returnValue = $this->getMySqlFacade()->selectFirst($principal,
					$this->getSqlForGetValidatedEmailFromCode($principal, $code),
					$dbCS);
			}
			if(!$returnValue){
				throw new ElementServiceException("no emails exists with this code: ".$code, ElementServiceException::INVALID_ARGUMENT);
			}
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("validateEmailFromCode", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("validateEmailFromCode", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("validateEmailFromCode", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("validateEmailFromCode", $principal);
		return $returnValue;


	}
	protected function assertPrincipalAuthorizedForValidateEmailFromCode($principal, $code)
	{
		if(is_null($code)) throw new ElementServiceException('code can not be null', ElementServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "validateEmailFromCode");
		return $pRights;
	}
	protected function getSqlForSetValidateEmailFromCode($principal, $code){
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Emails');
		$sqlB->updateValue('proofStatus', Emails::PROOF_STATUS_VALIDATED, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('proof', $this->getValidatedProofValue(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		//if proof status = 2, that means the email has been deleted by the owner --> this cannot be reset
		//except by reseting the email field.
		$sqlB->setWhereClause('proofKey = "'.$code.'" AND (proofStatus = '.Emails::PROOF_STATUS_NONE.' OR proofStatus IS NULL)');
		$sql = $sqlB->getSql();
		return $sql;
	}
	protected function getSqlForGetValidatedEmailFromCode($principal, $code){
		$sql = 'SELECT proofStatus FROM Emails WHERE proofKey = "'.$code.'"';
		return $sql;
	}
	public function unsubscribeEmailFromCode($principal, $code){
		$this->executionSink()->publishStartOperation("unsubscribeEmailFromCode", $principal);
		try
		{
			// checks authorization
			//only public principal can validate emails
			$this->assertPrincipalAuthorizedForUnsubscribeEmailFromCode($principal, $code);

			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			//don't need to lock or unlock elements because the proof field is always ignored in the update

			//update the emails with empty values except the proof that contains the summary of the action
			$returnValue = $this->getMySqlFacade()->update($principal,
				$this->getSqlForSetUnsubscribeEmailFromCode($principal, $code),
				$dbCS);
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("unsubscribeEmailFromCode", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("unsubscribeEmailFromCode", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("unsubscribeEmailFromCode", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("unsubscribeEmailFromCode", $principal);
		return $returnValue;


	}
	protected function assertPrincipalAuthorizedForUnsubscribeEmailFromCode($principal, $code)
	{
		if(is_null($code)) throw new ElementServiceException('code can not be null', ElementServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "unsubscribeEmailFromCode");
		return $pRights;
	}
	protected function getSqlForSetUnsubscribeEmailFromCode($principal, $code){
//		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
//		$sqlB->setTableForUpdate('Emails');
//		$sqlB->updateValue('proofStatus', Emails::PROOF_STATUS_DELETED, MySqlQueryBuilder::SQLTYPE_INT);
//		//the use of SQLTYPE_INT is a trick to allow the evaluation of the CONCAT function, as SQLTYPE_INT will not add ''
//		$sqlB->updateValue('proof', "CONCAT(`value`,'<br />".$this->getDeletedProofValue()."')", MySqlQueryBuilder::SQLTYPE_INT);
//		$sqlB->updateValue('proofKey', null, MySqlQueryBuilder::SQLTYPE_VARCHAR);
//		$sqlB->updateValue('value', null, MySqlQueryBuilder::SQLTYPE_VARCHAR);
//		$sqlB->updateValue('externalConfigGroup', null, MySqlQueryBuilder::SQLTYPE_INT);
//		$sqlB->updateValue('externalAccessLevel', null, MySqlQueryBuilder::SQLTYPE_INT);
//		$sqlB->updateValue('externalCode', null, MySqlQueryBuilder::SQLTYPE_VARCHAR);
//		$sqlB->setWhereClause('proofKey = "'.$code.'"');
//		$sql = $sqlB->getSql();
//		echo $sql;

		//as we need to reuse email value, the SQLBuilder is not giving proper method do include a SQL function
		$sql = "UPDATE Emails SET `proofStatus` = ".Emails::PROOF_STATUS_DELETED.", `proof` = CONCAT(`value`,'<br />".$this->getDeletedProofValue()."'), `proofKey` = NULL, `value` = NULL, `externalConfigGroup` = NULL, `externalAccessLevel` = NULL, `externalCode` = NULL WHERE proofKey = '".$code."'";
		return $sql;
	}

	public function getValidatedProofValue(){
		return "validated by IP:".$_SERVER["REMOTE_ADDR"]." / ".date("d.m.Y H:i:s");
	}
	public function getDeletedProofValue(){
		return "deleted by IP:".$_SERVER["REMOTE_ADDR"]." / ".date("d.m.Y H:i:s");
	}
	public function getUnsubscribeEmailDeprectaedInfo($p){
		return "unsubscribed by: ".$p->getRealUser()->getUsername()." / ".date("d.m.Y H:i:s");
	}



	// Cache management

	private function getCached($methodName, $params)
	{
		if(!isset($this->elementServiceWebImplCache)) return null;
		$key = $this->getCacheKey($methodName, $params);
		$returnValue = $this->elementServiceWebImplCache[$key];
		if(!isset($returnValue))
		{
			$this->debugLogger()->write("no result in cache: ".$key);
			return null;
		}
		$this->debugLogger()->write("get result in cache: ".$key);
		return $returnValue;
	}

	private function cache($result, $methodName, $params)
	{
		$key = $this->getCacheKey($methodName, $params);
		$this->debugLogger()->write("cache result: ".$key);
		if(!isset($this->elementServiceWebImplCache)){
			$this->elementServiceWebImplCache = array();
		}
		$this->elementServiceWebImplCache[$key] = $result;
	}

	private function getCacheKey($methodName, $params)
	{
		return "($methodName(".implode(",", $params)."))";
	}
}

// Specific renderers

class ESWElementGroupsRenderer implements GroupPList
{
	private $wigiiNamespace;
	private $elementP;
	private $preFix;
	private $postFix;

	public static function createInstance($wigiiNamespace, $elementP)
	{
		$returnValue = new ESWElementGroupsRenderer();
		$returnValue->reset($wigiiNamespace, $elementP);
		return $returnValue;
	}

	public function reset($wigiiNamespace, $elementP)
	{
		$this->freeMemory();
		$this->wigiiNamespace = $wigiiNamespace;
		$this->elementP = $elementP;
	}

	protected function freeMemory()
	{
		unset($this->preFix);
		unset($this->postFix);
	}

	// GroupPList implementation

	public function getListIterator()
	{
		throw new ElementServiceException("write only list", ElementServiceException::UNSUPPORTED_OPERATION);
	}

	public function isEmpty()
	{
		throw new ElementServiceException("write only list", ElementServiceException::UNSUPPORTED_OPERATION);
	}

	public function count()
	{
		throw new ElementServiceException("write only list", ElementServiceException::UNSUPPORTED_OPERATION);
	}

	/**
	 * Renders Group list
	 */
	public function addGroupP($groupP)
	{
		$class = "readOnly";
		if($groupP->getRights() != null && $groupP->getRights()->canWriteElement()){
			$class = "";
		}

		//S right
		if($groupP->getRights() != null && $groupP->getRights()->canShareElement() && !$groupP->getRights()->canWriteElement()){
			if($this->elementP->getRights()->canWriteElement()){
				//owner
				$class = "";
			}
		}

		$group = $groupP->getGroup();

		$groupS = ServiceProvider::getGroupAdminService();
		?><a class="<?=$class;?>" href="<?=$groupS->getUrlForGroup($this->wigiiNamespace, $this->elementP->getElement()->getModule(), $group);?>" target="_self"><?
			if($group->getWigiiNamespace()->getWigiiNamespaceName()!=null){
				if(ServiceProvider::getAuthenticationService()->getMainPrincipal()->getWigiiNamespace()->getWigiiNamespaceName() != $group->getWigiiNamespace()->getWigiiNamespaceName()){
					echo $group->getWigiiNamespace()->getWigiiNamespaceName()." : ";
				}
			}
			echo $group->getGroupName();
		?></a><?
	}
}