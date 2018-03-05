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
 * Created on 21 sept 2011
 * by LWR
 */
class PrintElementFormExecutor extends DetailElementFormExecutor {

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
		if(!$idAnswer) $idAnswer = "mainDiv";
		$config->mf($p, $exec->getCrtModule());
		$element = $this->getRecord();
		$elementP = $this->getElementP();

		//Change the responsive div if it's print or screen (may 2017 by Medair (LMA))
        ?>
            <style>
                div#<?=$this->getFormId(); ?> {
                    width: 100%;
                    max-width:<?=$this->getTotalWidth();?>px;
                }
                @media print {
                    div#<?=$this->getFormId(); ?> {
                        width:<?=$this->getTotalWidth();?>px;
                    }
                }

            </style>
        <?php
		//limit the elementDetail width to the TotalWidth with overflow=visible
		?><div id="<?=$this->getFormId(); ?>" class="elementDetail"><?
		$enableElementState = $this->computeEnableElementState($p, $exec, $elementP);
		if($element->isState_locked()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#CC4B4B;" ><legend class="ui-corner-all" style="background-color:#CC4B4B;" >';
 			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-locked.png" />&nbsp;';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.$this->getTotalWidth().'px;" >'.nl2br($info["message"]).'</div>';
			}
		}
		if($element->isState_blocked() && $elementP->isEnabledElementState_blocked()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#EA2424;" ><legend class="ui-corner-all" style="background-color:#EA2424;" >';
			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-blocked.png" />&nbsp;';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.($this->getTotalWidth()).'px;" >'.nl2br($transS->t($p, $info["message"])).'</div>';
			}
		}
		if($element->isState_dismissed()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#EA2424;" ><legend class="ui-corner-all" style="background-color:#EA2424;" >';
			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-unreadable.png" />&nbsp;';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.($this->getTotalWidth()).'px;" >'.nl2br($transS->t($p, $info["message"])).'</div>';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.($this->getTotalWidth()).'px;" >'.nl2br($transS->t($p, $info["message"])).'</div>';
			}
		}
		if($element->isState_approved()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#A0E061;" ><legend class="ui-corner-all" style="background-color:#A0E061;" >';
			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/status/available.png" />&nbsp;';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.($this->getTotalWidth()).'px;" >'.nl2br($transS->t($p, $info["message"])).'</div>';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.($this->getTotalWidth()).'px;" >'.nl2br($transS->t($p, $info["message"])).'</div>';
			}
		}

		$this->getDetailRenderer()->setP($p)->setRecordIsWritable($elementP->getRights()->canWriteElement() && !($elementP->getElement()->isState_blocked() || $elementP->isParentElementState_blocked()));
		$this->getTrm()->setDetailRenderer($this->getDetailRenderer());
		$this->getTrm()->displayRemainingDetails();

		//display the status
		$elS->displayElementStateAsField($p, $this->getTotalWidth(), $this->getLabelWidth(), $element);
		// Medair (CWE) 02.02.2018: saves element state in Wigii Api context for further use
		$exec->addJsCode('wigii().context.crtElementState = '.$element->getStateAsInt());
		
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
		
		$elS->displayElementAdditionalInformation($p, $exec, $element, $this->getTotalWidth(), $this->getLabelWidth());
		$exec->addJsCode("setListenersToElementDetail('$idAnswer');");

		?></div><?

		//add margin and remove max-height
		$exec->addJsCode("" .
			"$('#mainDiv .elementDetail').css('margin', '20px');" .
			"$('div').css('max-height','none').css('height','none');" .
			"");

		$exec->addJsCode($this->getDetailRenderer()->getJsCodeAfterShow());
	}
}



