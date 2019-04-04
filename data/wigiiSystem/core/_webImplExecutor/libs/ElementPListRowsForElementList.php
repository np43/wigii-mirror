<?php
/**
 *  This file is part of Wigii (R) software.
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
 * Created on 6 oct. 09
 * by LWR
 */

class ElementPListRowsForElementList extends ElementPListWebImplWithWigiiExecutor {

	private $isGroupedBy;
	private $crtGroupByValue;
	private $trm_;
	protected function getTRM(){
		if(!isset($this->trm_)){
			$this->trm_ = $this->createTRMInstance();
		}
		return $this->trm_;
	}
	protected function createTRMInstance(){
		return $this->getWigiiExecutor()->createTRM(null, false, false, false, true);
	}
	public function setTRM($var){ $this->trm_ = $var; }
	
	public static function createInstance($wigiiExecutor, $listContext){
		$elPl = new self();
		$elPl->setWigiiExecutor($wigiiExecutor);
		$elPl->setListContext($listContext);
		$elPl->isGroupedBy = $listContext->getGroupBy();
		$elPl->crtGroupByValue = $listContext->getGroupByItemCurrentValue();
		return $elPl;
	}

	/**
	 * Adds an element to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same element in the list
	 */
	private $elementIds; //array which stores the elements ids already added
	private $headersMade = false;
	private $tableBegan = false;
	private $doOnlyRows = false;
	public function doOnlyRows(){
		$this->doOnlyRows = true;
	}
	public function addElementP($elementP){
		
		$element = $elementP->getElement();
		$this->getTrm()->reset($element);
		
		$elId = $element->getId();
		if($this->elementIds[$elId]!=null) throw new ListException("Id: $elId already exist in the ElementListFrame", ListException::ALREADY_EXIST);
		$this->elementIds[$elId] = $elId;

		$elementFieldSelectorList = $this->getListContext()->getFieldSelectorList();
		if($elementFieldSelectorList == null){
			//we are in the list view, we need a field selector list
			throw new ServiceException("A FieldSelectorList is needed", ServiceException::INVALID_ARGUMENT);
		}

		if(!$this->doOnlyRows && !$this->headersMade){
			$this->makeHeaders($element->getFieldList());
		}

		if(!$this->doOnlyRows && !$this->tableBegan){
			?><div class="dataList"><table  style='' ><?

			//we need to add col attribute to be able to manage the column width in an easy way
			foreach($elementFieldSelectorList->getListIterator() as $key=>$header){
				$xmlHeader = $elementFieldSelectorList->getXml($key);
				if($xmlHeader == null) continue;
				if($xmlHeader["hidden"]=="1") continue;

				?><col></col><?
			}

			?><tbody><?
			$this->tableBegan = true;
		}

		$this->beginElement($elementP);
		
		foreach($elementFieldSelectorList->getListIterator() as $key=>$header) { //$fieldSelectorList->getListIterator() as $fieldSelector){
			$xmlHeader = $elementFieldSelectorList->getXml($key);
			if($xmlHeader == null) continue;
			if($xmlHeader["hidden"]=="1") continue;

			$class = null;
			if(	!$header->isElementAttributeSelector() &&
				$header->getSubFieldName()=="path" && 
				$element->getFieldList()->getField($header->getFieldName())->getDataType()->getDataTypeName()=="Files" &&
				$element->getFieldValue($header->getFieldName(), "path")!=null){
				//if a file exist, then add the download class to make active the click
				$class .= " download";
				//to know what field to download on the element, we add in the class the fieldName after a download_
				$class .= " download_".$header->getFieldName();
			}
			
			$value = $this->getTrm()->formatValueFromFS($header, $element);
			$this->addCell($value, $class);
		}

		$this->endElement();

	}

	/**
	 * groupBy filter management
	 */
	protected function beginElement($elementP){
		echo "\n";
		$element = $elementP->getElement();
		if($this->isGroupedBy !=null){
			$fieldSelector = $this->getListContext()->getGroupByItemFieldSelector();
			
			$crtGroupByValue = $this->getTrm()->formatValueFromFS($fieldSelector, $element, true);

			if($this->crtGroupByValue != $crtGroupByValue){
				$this->crtGroupByValue = $crtGroupByValue;
				$this->getListContext()->setGroupByItemCurrentValue($crtGroupByValue);
				?><tr class="groupByTitle" style=""><td class="grayBorder grayFont" COLSPAN="<?=$this->getListContext()->getFieldSelectorList()->getNbXml();?>" ><?=$crtGroupByValue;?></td></tr><?
				echo "\n";
			}
		}

		//add the current selected item
		$class = "";
		//add the readOnly class if this is the case:
		//if($elementP->getRights()->canShareElement() && !$elementP->getRights()->canWriteElement()) $class .= " shareElement ";
		if(!$elementP->getRights()->canWriteElement()) $class .= " readOnly ";
		if($this->getListContext()->getCrtSelectedItem()==$element->getId()) $class .= " selected ";
		if($this->getListContext()->isInMultipleSelection($element->getId())) $class .= " multipleSelected ";

		?><tr class="<?=$class;?>" id="row_<?=$element->getId();?>" style=""><?
	}

	protected function endElement(){
		?></tr><?
	}

	protected function addCell($value, $class=null){
		if(is_array($value)){
			$temp = "";
			foreach($value as $i){
				if($i != null){
					if($temp != null) $temp .= ", ";
					$temp .= $i;
				}
			}
			$value = $temp;
		}
		//$value = addBreakableChar($value);
		?><td class="grayBorder" ><div class="<?=$class;?>" ><?=$value;?></div></td><?
	}

	public function actOnBeforeAddElementP(){

	}

	public function actOnFinishAddElementP($numberOfObjects){
		if(!$this->doOnlyRows && $this->tableBegan){
			?></tbody></table></div><?
			//addJsCode to match the column width on headers
			//this is done in the element_resize js function
		}
	}

	private $totalPaddingInCol = 15;
	public function getTotalPaddingInCol(){ return $this->totalPaddingInCol; }
	private $nbNoWidth = 0;
	public function getNbNoWidth(){ return $this->nbNoWidth; }
	private $totalWidth = 1; //we begin with one border
	public function getTotalWidth(){ return $this->totalWidth; }

	public function makeHeaders($firstElementFieldList){
		$elementFieldSelectorList = $this->getListContext()->getFieldSelectorList();
		$trm = $this->createTRMInstance();
		$trm->reset(null, false, false, false, false); //we want to be able to buffer the result
		?><div class="headerList listHeader"><?
			foreach($elementFieldSelectorList->getListIterator() as $key=>$fieldSelector){
				if($fieldSelector->isElementAttributeSelector()) $fieldXml = null;
				else $fieldXml = $firstElementFieldList->getField($fieldSelector->getFieldName())->getXml();
				$xmlHeader = $elementFieldSelectorList->getXml($key);
				//if there is no xml attached, that means the fieldSelector is not a header, but an other field we needed
				if(!isset($xmlHeader)) continue;
				if($xmlHeader["hidden"]=="1") continue;
				
				$trm->displayHeaderLabel($fieldSelector, $fieldXml, $xmlHeader);

				$width = 0;
				$class = "key_".$key;
				if($xmlHeader["width"]!="null"){
					$width = (0+$xmlHeader["width"]);
					//we calculate the total fixed width, we add the intern padding, + border
					$this->totalWidth += $width+$this->totalPaddingInCol+1;
				} else{
					$width = 1; //in IE don't support no width, then this width will change after
					$class .= " noWidth";
					$this->nbNoWidth ++;
				}

				//SORTING
				if($xmlHeader["sortable"]=="0"){
					$class .= " noSorting";
				}
				if($xmlHeader["defaultSorted"]!=null){
					$class .= " defaultSorted defaultSorted_".trim((string)$xmlHeader["defaultSorted"]);
				}
				//add the current sorting key
				if($this->getListContext()->getSortedBy()==$key){
					if($this->getListContext()->isAscending()){
						$class .= " ASC ";
					} else {
						$class .= " DESC ";
					}
				}
				
				if($fieldSelector->getSubFieldName() == "path" && $firstElementFieldList->getField($fieldSelector->getFieldName())->getDataType()->getDataTypeName()=="Files"){
					$class .= " noSorting";
				}
				
				?><div class="grayBorder <?=$class;?>" style="width:<?=$width;?>px;"><?=$trm->getHtmlAndClean();?></div><?
			}
			
			$trm->addJsCode("
ElementPListRows_makeHeaders_getTotalWidth = ".$this->getTotalWidth().";
ElementPListRows_makeHeaders_getNbNoWidth = ".$this->getNbNoWidth().";
ElementPListRows_makeHeaders_totalPaddingInCol = ".$this->totalPaddingInCol.";
");

		?></div><div class="clear"></div><?

		$this->headersMade = true;

	}

}



/**
 * ajoute des balise q entre chaque lettre
 * @param string texte à travailler
 * @param nb_char int, nombre de caractère à prendre en compte, important car le texte peut être très long
 * et l'on utilise cette artifice pour afficher correctement qqch sur une ligne... donc en général maximum de 100...
 */
function addBreakableChar($string, $nb_chars = 254) {
	$string = substr($string, 0, $nb_chars);
	$string = mb_str_split($string);
	$string = implode("<q> </q>", $string);
	$string = str_replace("</q> <q>", "</q>&nbsp;<q>", $string);
	return $string;
}

function mb_str_split($str, $length = 1) {
	if ($length < 1)
		return FALSE;

	$result = array ();

	for ($i = 0; $i < mb_strlen($str, "UTF-8"); $i += $length) {
		$result[] = mb_substr($str, $i, $length, "UTF-8");
	}

	return $result;
}
