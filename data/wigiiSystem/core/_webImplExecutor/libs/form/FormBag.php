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
 * Main WigiiBag implementation used by all Forms in Wigii WebImpl.
 * Created by LWR on 1 sept. 09
 * Modified by CWE  on 27 mai 2014 to implement BulkLoadableWigiiBag
 */
class FormBag extends Model implements BulkLoadableWigiiBag {

	private $_debugLogger;
	protected $bag;
	private $fixedBag;
	private $writtenValues;
	private $loadingFromFixedBag = false;
	private $loadedFromFixedBag = false;
	private $elementId;
	protected $errors;
	protected $helps;
	protected $disabled;
	protected $hidden;
	protected $readonly;
	protected $filled;
	protected $changed;
	protected $multipleChecked;
	protected $multipleAddOnlyChecked;
	protected $formFieldList;

	public function setFormFieldList($var){ $this->formFieldList = $var; }
	public function getFormFieldList(){ return $this->formFieldList; }

	//service dependency
	private $systemVersion;
	protected function getSystemVersion(){
		if(!isset($this->systemVersion)){
			$this->systemVersion = ServiceProvider::getExecutionService()->getVersion();
		}
		return $this->systemVersion;
	}
	public function setSystemVersion($v){
		$this->systemVersion = $v;
	}

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FormBag");
		}
		return $this->_debugLogger;
	}

	//don't stores the formFieldList because it contains some xml data...
	public function __sleep(){
		if(isset($this->fixedBag) && !$this->loadedFromFixedBag) $this->loadFromFixedBag();

		return array("bag", "errors", "helps", "disabled", "hidden", "readonly", "filled", "changed", "multipleChecked", "multipleAddOnlyChecked");
	}

	public static function createInstance(){
		$fb = new self();
		$fb->debugLogger()->write("wigii bag created");
		return $fb;
	}

	public function renameField($fieldName , $newName){
		if(isset($this->fixedBag) && !$this->loadedFromFixedBag) $this->loadFromFixedBag();
		//else $this->debugLogger()->write("renameField in local bag");

		if($this->bag[$newName]!=null) throw new ElementServiceException("Field $fieldName cannot be rename in $newName ($newName field already exists)", ElementServiceException::FIELD_DEFINED_TWICE_IN_CONFIG);
		$this->bag[$newName] = $this->bag[$fieldName]; $this->bag[$fieldName] = null;
		$this->errors[$newName] = $this->errors[$fieldName]; $this->errors[$fieldName] = null;
		$this->helps[$newName] = $this->helps[$fieldName]; $this->helps[$fieldName] = null;
		$this->disabled[$newName] = $this->disabled[$fieldName]; $this->disabled[$fieldName] = null;
		$this->hidden[$newName] = $this->hidden[$fieldName]; $this->hidden[$fieldName] = null;
		$this->readonly[$newName] = $this->readonly[$fieldName]; $this->readonly[$fieldName] = null;
		$this->filled[$newName] = $this->filled[$fieldName]; $this->filled[$fieldName] = null;
		$this->changed[$newName] = $this->changed[$fieldName]; $this->changed[$fieldName] = null;
		$this->multipleChecked[$newName] = $this->multipleChecked[$fieldName]; $this->multipleChecked[$fieldName] = null;
		$this->multipleAddOnlyChecked[$newName] = $this->multipleAddOnlyChecked[$fieldName]; $this->multipleAddOnlyChecked[$fieldName] = null;
		$this->resetSubFieldIsFilledCacheKey();
	}

	/**
	 * add an error value to the specific field
	 */
	public function addError($error, $fieldName){
		if(!isset($this->errors)) $this->errors = array();
		if($this->errors[$fieldName] != null)  $this->errors[$fieldName] .= '<br style="font-size:4px;" />';
		$this->errors[$fieldName] .= $error;
	}
	public function getError($fieldName){
		if(!isset($this->errors)) return null;
		return $this->errors[$fieldName];
	}
	public function hasError($fieldName){
		return $this->getError($fieldName)!=null;
	}
	public function hasErrors(){
		if(!isset($this->errors)) return false;
		//if there is one value filled, then true will be found because true == any filled string
		return false !== array_search(true, $this->errors);
	}
	public function getErrors(){
		return $this->errors;
	}
	public function getErrorsAsString() {
		$returnValue = '';
		if(!empty($this->errors)) {
			foreach($this->errors as $fieldName => $error) {
				if($returnValue != '') $returnValue .= "\n";
				$returnValue .= '* '.$fieldName;
				$errorField = '';
				$error = preg_split('/\<br.*?\/\>/', $error);
				if(!empty($error)) {
					$returnValue .= "\n";
					foreach($error as $er) {
						if($errorField != '') $errorField .= "\n";
						$errorField .= '  - '.$er;
					}
					$returnValue .= $errorField;
				}				
			}
		}
		return $returnValue;
	}
	public function resetErrors(){
		unset($this->errors);
	}

	/**
	 * Field help values
	 * For help no need
	 */
	public function setHelp($help, $fieldName){
		if(!isset($this->helps)) $this->helps = array();
		$this->helps[$fieldName] = $help;
	}
	public function getHelp($fieldName){
		if(!isset($this->helps)) return null;
		return $this->helps[$fieldName];
	}

	/**
	 * Disabled fields
	 */
	public function setDisabled($isDisabled, $fieldName){
		if(!isset($this->disabled)) $this->disabled = array();
		$this->disabled[$fieldName] = $isDisabled;
	}
	public function isDisabled($fieldName){
		if(!isset($this->disabled)) return null;
		return $this->disabled[$fieldName];
	}

	/**
	 * Hidden fields
	 */
	public function setHidden($isHidden, $fieldName){
		if(!isset($this->hidden)) $this->hidden = array();
		$this->hidden[$fieldName] = $isHidden;
	}
	public function isHidden($fieldName){
		if(!isset($this->hidden)) return null;
		return $this->hidden[$fieldName];
	}

	/**
	 * ReadOnly fields
	 */
	public function setReadonly($isReadonly, $fieldName){
		if(!isset($this->readonly)) $this->readonly = array();
		$this->readonly[$fieldName] = $isReadonly;
	}
	public function isReadonly($fieldName){
		if(!isset($this->readonly)) return null;
		return $this->readonly[$fieldName];
	}

	/**
	 * MultipleChecked fields
	 */
	public function setMultipleChecked($multipleChecked, $fieldName){
		if(!isset($this->multipleChecked)) $this->multipleChecked = array();
		$this->multipleChecked[$fieldName] = $multipleChecked;
	}
	public function isMultipleChecked($fieldName){
		if(!isset($this->multipleChecked)) return null;
		return $this->multipleChecked[$fieldName];
	}
	public function setMultipleAddOnlyChecked($multipleChecked, $fieldName){
		if(!isset($this->multipleAddOnlyChecked)) $this->multipleAddOnlyChecked = array();
		$this->multipleAddOnlyChecked[$fieldName] = $multipleChecked;
	}
	public function isMultipleAddOnlyChecked($fieldName){
		if(!isset($this->multipleAddOnlyChecked)) return null;
		return $this->multipleAddOnlyChecked[$fieldName];
	}

	/**
	 * Changed fields
	 */
	public function setChanged($fieldName){
		if(!isset($this->changed)) $this->changed = array();
		$this->changed[$fieldName] = true;
	}
	public function isChanged($fieldName){
		if(!isset($this->changed)) return null;
		return $this->changed[$fieldName];
	}
	public function hasChanges(){
		if(!isset($this->changed)) return false;
		return false !== array_search(true, $this->changed, true);
	}
	public function resetChanges(){
		unset($this->changed);
	}

	/**
	 * convert 'lt;', 'gt;','#92;'  to 'lt_;','gt_;', '#92_;' for prevent convetion to <,>,\ by html_entity_decode fonction.
	 * @param string $value the HTML text.
	 * 
	 * @return string
	 * 	the HTML string encoded
	 */
	public function formatBracketsBeforeDecode($value){
		$value = str_replace('lt;','lt_;',$value);
		$value = str_replace('gt;','gt_;',$value);
		$value = str_replace('#92;','#92_;',$value);
		return $value;
	}
	/**
	 * convert 'lt_;','gt_;', '#92_;' to 'lt;', 'gt;','#92;' after convertion by html_entity_decode to display correctly in the text.
	 * @param string $value the HTML text.
	 * 
	 * @return string
	 * 	the HTML string decoded
	 */
	public function formatBracketsAfterDecode($value){
		$value = str_replace('lt_;','lt;',$value);
		$value = str_replace('gt_;','gt;',$value);
		$value = str_replace('#92_;','#92;',$value);
		return $value;
	}

	
	/**
	 * WARNING, this method format the value of the $newValue!
	 */
	public function isNewValueEqual(&$newValue, $oldValue, $dataTypeName, $subFieldXml){
		$subFieldName = $subFieldXml->getName();

		if($subFieldXml["multiLanguage"]=="1" && $newValue != null){
			//the oldValue has all the language
			//the get has only visible language
			if(!isset($oldValue)) $oldValue = array();
			if(!is_array($oldValue)) $oldValue = array(DEFAULT_LANGUAGE=>$oldValue); //in case the config changed between
			else $oldValue = array_intersect_key($oldValue, $newValue);
		}
		if($dataTypeName == "MultipleAttributs" && $subFieldName=="value"){
			//the oldValue has value has key
			//the get has numeric key
			if(!isset($oldValue)) $oldValue = array();
			else $oldValue = array_values($oldValue);
			if(!$newValue) $newValue = array(0=>""); //if new value is empty, simulate that the empty option has been selected
		}
		if($dataTypeName == "Emails" && $subFieldName=="value"){
			//replace each separator
			if($newValue!=null){ //if null, do nothing to prevent changing null in ""
				//the separator ", " is used in Emails_displayValue.php
				$newValue = preg_replace("/".ValueListArrayMapper::Natural_Separators."/", ", ", $newValue);
				//delete separator to the end
				$newValue = preg_replace("/".ValueListArrayMapper::Natural_Separators."$/", "", $newValue);
				//delete separator to the start
				$newValue = preg_replace("/^".ValueListArrayMapper::Natural_Separators."/", "", $newValue);
			}
		}
		//eput($dataTypeName." ".$fieldName." ".$subFieldName."\n");
		//declare regex into variable for use twice
		$classRegEx = '#class=[\\\]?[\"]([\w\s-]*(elementDialog|elementDetail|value|label|addinfo|field)[\w\s-]*)([\\\]?)[\"]#iU';
		$idRegEx = "#id=\"(elementDialog|detailElement_form.*|editElement_form.*|addElement_form.*)\"#iU";
		//Global reformating depending on sqlType
		if($subFieldXml["sqlType"]=="varchar" || $subFieldXml["sqlType"]=="text" || ($dataTypeName=="Files"&&$subFieldName=="textContent")){
			//the texts are always slashed
			if(is_array($oldValue)){
				foreach($oldValue as $oldValKey=>$oldValSub){
					$oldValSub = $this->formatBracketsBeforeDecode($oldValSub);
					$oldValue[$oldValKey] = addslashes($this->formatBracketsAfterDecode(html_entity_decode($oldValSub, ENT_COMPAT, "UTF-8"))); //preg_replace('/&quot;/', '"', $oldValSub));
					//in the case that the text was added from a copy paste
					if($newValue[$oldValKey]!=null){ //if null, do nothing to prevent changing null in ""
						$newValue[$oldValKey] = $this->formatBracketsBeforeDecode($newValue[$oldValKey]);
						$newValue[$oldValKey] = addslashes($this->formatBracketsAfterDecode(html_entity_decode(stripslashes($newValue[$oldValKey]), ENT_COMPAT, "UTF-8"))); //preg_replace('/&quot;/', '\"', $newValue[$oldValKey]);
						//delete id for elementDialog, detailElement_form*, editElement_form* and addElement_form*
						$newValue = preg_replace($idRegEx,'', $newValue);
						//remove class name in this list : elementDialog, elementDetail, value, label, addinfo, field
						$newValue = preg_replace_callback($classRegEx, function ($matches) {
							return 'class='.$matches[3].'"'.preg_replace("#\b(elementDialog|elementDetail|value|label|addinfo|field)\b#iU", '', $matches[1]).$matches[3].'"';
						}, $newValue);
					}
				}
			} else {
				$oldValue = $this->formatBracketsBeforeDecode($oldValue);
				$oldValue = addslashes($this->formatBracketsAfterDecode(html_entity_decode($oldValue, ENT_COMPAT, "UTF-8"))); //preg_replace('/&quot;/', '"', $oldValue));
				//in the case that the text was added from a copy paste
				if($newValue!=null){ //if null, do nothing to prevent changing null in ""
					$newValue = $this->formatBracketsBeforeDecode($newValue);
					$newValue = addslashes($this->formatBracketsAfterDecode(html_entity_decode(stripslashes($newValue), ENT_COMPAT, "UTF-8"))); //preg_replace('/&quot;/', '\"', $newValue);
					//delete id for elementDialog, detailElement_form*, editElement_form* and addElement_form*
					$newValue = preg_replace($idRegEx,'', $newValue);
					//remove class name in this list : elementDialog, elementDetail, value, label, addinfo, field
					$newValue = preg_replace_callback($classRegEx, function ($matches) {
						return 'class='.$matches[3].'"'.preg_replace("#\b(elementDialog|elementDetail|value|label|addinfo|field)\b#iU", '', $matches[1]).$matches[3].'"';
					}, $newValue);
				}
			}
//			fput('$oldValue : '.$oldValue);
//			fput('$newValue : '.$newValue);
//			if($dataTypeName=="Files"&&$subFieldName=="content"){
//				fput($newValue);
//				fput($oldValue);
//				//eput($newValue == $oldValue);
//			}
		}
		if($subFieldXml["sqlType"]=="boolean"){
			if(is_string($newValue)) $newValue = $newValue ==="on";
			$newValue = ($newValue != null);
		}
		if($subFieldXml["sqlType"]=="time"){
			$h = null; $m = null; $s = null;
			if (Times::fromString($newValue, $h, $m, $s)){
				$newValue = Times::toString($h, $m, $s, "hh:mm:ss");
			} else {
				throw new ServiceException("invalidTime", ServiceException::INVALID_ARGUMENT);
			}
		}
		if($subFieldXml["sqlType"]=="date" || $subFieldXml["sqlType"]=="datetime"){
			$D = null; $M = null; $Y = null;
			$h = null; $i = null; $s = null;
			if (Dates::fromString($newValue, $D, $M, $Y, $h, $i, $s)){
				$newValue = Dates::toString($D, $M, $Y, "yyyy-mm-dd", $h, $i, $s, "hh:mm:ss");
				$oldValue = Dates::formatDisplay($oldValue, "yyyy-mm-dd", "hh:mm:ss");
			} else {
				throw new ServiceException("invalidDate", ServiceException::INVALID_ARGUMENT);
			}
		}
		if($subFieldXml["sqlType"]=="double" || $subFieldXml["sqlType"]=="decimal"){
			//replace any thousand separator
			$newValue = str_replace("'", "", str_replace("\'", "", str_replace(" ", "", str_replace(",", "", $newValue))));
			if (is_numeric($newValue)){
				if($subFieldXml["sqlType"]=="decimal"){
					//the round of the numeric to 2 digit will be done in DB
					// --> do nothing
					$newValue = $newValue;
				}
				//nothing to in the case of double
			} else if($newValue) {
				throw new ServiceException("invalidNumber", ServiceException::INVALID_ARGUMENT);
			}
		}

		if($newValue == $oldValue){
			return true;
		} else {
//			eput($newValue);
//			eput($oldValue);
			return false;
		}
	}

	/**
	 * Filled fields
	 */
	public function setFilled($isFilled, $fieldName){
		if(!isset($this->filled)) $this->filled = array();
		$this->filled[$fieldName] = $isFilled;
	}

	/**
	 * return if the bag contains essential data for this fieldName
	 * either the filled array is already calculated (if possible it is better)
	 * either if it is not define, the calculation will be done there
	 */
	public function isFilled($fieldName){
		if(!isset($this->filled)) $this->filled = array();
		if(!isset($this->bag) && !isset($this->fixedBag)) $this->filled[$fieldName] = false;

//		eput($fieldName);
		if(!isset($this->filled[$fieldName])){
			if($this->getFormFieldList() == null) throw new FormException("FormFieldList must be setted to a FormBag", FormException::FORM_FIELDLIST_NOT_SET);
			$hasOneValue = false;
			//look only in subFields which would be required
//			$subfields = $this->getFormFieldList()->getField($fieldName)->getDataType()->getXml()->xpath("*[@require='1']");
			$dataType = $this->getFormFieldList()->getField($fieldName)->getDataType();
			if($dataType) $dataTypeName = $dataType->getDataTypeName();
			else $dataTypeName = null;
			foreach($dataType->getXml()->children() as $subField){
				//exception for TimeRange where data is filled if there is something in end date
				switch($dataTypeName){
					case "TimeRanges":
						//only subfield dates makes it filled or not, if there is only time, that make no sense
						if($subField->getName()=="begDate" || $subField->getName()=="endDate"){
							if($this->subFieldIsFilled($dataTypeName, $fieldName, $subField)){
								$hasOneValue = true;
								break;
							}
						}
						break;
					default:
						if($this->subFieldIsRequiredAndFilled($dataTypeName, $fieldName, $subField)){
							$hasOneValue = true;
							break;
						}
				}
			}
			$this->filled[$fieldName] = $hasOneValue;
		}
//		eput($fieldName).":".eput($this->filled[$fieldName]);
		return $this->filled[$fieldName];
	}

	public function subFieldIsRequiredAndFilled($dataTypeName, $fieldName, $subFieldXml){
		if($subFieldXml["require"] =="1"){
			return $this->subFieldIsFilled($dataTypeName, $fieldName, $subFieldXml);
		}
		return false;
	}
	public function subFieldIsRequiredAndNotFilled($dataTypeName, $fieldName, $subFieldXml){
		if($subFieldXml["require"] =="1"){
			return !$this->subFieldIsFilled($dataTypeName, $fieldName, $subFieldXml);
		}
		return false;
	}
	private $subFieldIsFilledCache;
	protected function subFieldIsFilled($dataTypeName, $fieldName, $subFieldXml){
		if(!isset($this->subFieldIsFilledCache)) $this->subFieldIsFilledCache=array();
		$key = $this->subFieldIsFilledCacheKey($dataTypeName, $fieldName, $subFieldXml);
		if(!isset($this->subFieldIsFilledCache[$key])){
			$subValue = $this->getValue($this->elementId, $dataTypeName, $fieldName, $subFieldXml->getName());
			if(is_array($subValue)){ //this is the case for multilanguage values or for MultipleAttributs
				foreach($subValue as $subSubValue){
					if($this->itemHasValue($dataTypeName, $subFieldXml["sqlType"], $subSubValue)){
						$this->subFieldIsFilledCache[$key] = true;
						break;
					}
				}
			} else {
				if($this->itemHasValue($dataTypeName, $subFieldXml["sqlType"], $subValue)){
					$this->subFieldIsFilledCache[$key] = true;
				}
			}
		}
		return $this->subFieldIsFilledCache[$key];
	}
	private function resetSubFieldIsFilledCacheKey(){
		$this->subFieldIsFilledCache = null;
	}
	private function subFieldIsFilledCacheKey($dataTypeName, $fieldName, $subFieldXml){
		return "($dataTypeName($fieldName(".$subFieldXml->getName().")))";
	}

	protected function itemHasValue ($dataTypeName, $sqlType, $value){
		if(	$value === null ||
			(($dataTypeName == "Attributs" || $dataTypeName == "MultipleAttributs") && $value === "none") ||
			$value===""
			){
			return false;
		}
		return true;
	}

	public function setFixedBag($wigiiFixedBag, $elementIds) {
		if(!isset($this->fixedBag)) {
			$this->fixedBag = $wigiiFixedBag;
			if(is_array($elementIds)) {
				if(!empty($elementIds)) $this->elementId = reset($elementIds);
				else $this->elementId = null;
			}
			else $this->elementId = $elementIds;
			$this->debugLogger()->write("injected a fixed bag");
		}
		elseif(isset($wigiiFixedBag)) throw new RecordException('A fix bag is already set. Please first reset the bag.', RecordException::INVALID_STATE);
	}

	public function acceptsFixedBag() {
		return !isset($this->fixedBag);
		//return false;
	}

	/**
	 * Copies the content of the fixed bag into the local bag
	 */
	public function loadFromFixedBag() {
		if(isset($this->fixedBag) && !$this->loadingFromFixedBag) {
			$this->loadingFromFixedBag = true;
			try {
				$this->fixedBag->copyIntoWigiiBag($this);
				$this->loadingFromFixedBag = false;
			}
			catch(Exception $e) {
				$this->loadingFromFixedBag = false;
				throw $e;
			}
			$this->loadedFromFixedBag = true;
		}
	}

	/**
	 * Returns an element field value stored in the wigii bag
	 * elementId: is not taken in consideration
	 * dataTypeName: is not taken in consideration
	 * subFieldName: the dataType subfield name. If null, then uses the predefined "value" subfield
	 */
	public function getValue($elementId, $dataTypeName, $fieldName, $subFieldName=null){
		if($subFieldName == null) $subFieldName = "value";

		// if we have a fixed bag and no local value --> returns the value in the fixed bag
		if((!isset($this->writtenValues) || !isset($this->writtenValues["($fieldName($subFieldName))"])) && isset($this->fixedBag)) {
			//$this->debugLogger()->write("getValue in fixed bag");
			return $this->fixedBag->getValue($elementId, $fieldName, $subFieldName);
		}
		else {
			//$this->debugLogger()->write("getValue in local bag");
			if(!isset($this->bag)) return null;
			if(!isset($this->bag[$fieldName])) return null;
			return $this->bag[$fieldName][$subFieldName];
		}
	}

	/**
	 * Sets an element field value in the wigii bag. Replaces the actual value if already exists in the bag.
	 * Set isChanged for the field if the seted value is different than the existing one.
	 * @param $elementId: is not taken in consideration
	 * @param $dataTypeName: is not taken in consideration
	 * @param $subFieldName: =null, the dataType subfield name. If null, then uses the predefined "value" subfield
	 * @param $subFieldXml : = null, xml of subfield, if not null, then evaluation of the value is done based on the sqlType and dataType and reformat the value correctly
	 * @return if the value has been changed
	 */
	public function setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null, $subFieldXml=null){
		if($subFieldName == null) $subFieldName = "value";
		// if loadingFromFixedBag then setValue is authorized only if value does not exist in local bag.
		if(!$this->loadingFromFixedBag || ($this->loadingFromFixedBag && (!isset($this->writtenValues) || !isset($this->writtenValues["($fieldName($subFieldName))"])))) {
			if(!isset($this->bag)) $this->bag = array();
			if(!isset($this->bag[$fieldName])) $this->bag[$fieldName] = array();
			$oldValue = $this->getValue($elementId, $dataTypeName, $fieldName, $subFieldName);
			//do the reformating in case of having the subFieldXml
			$returnValue = false;
			if($subFieldXml){
				if(!$this->isNewValueEqual($value, $oldValue, $dataTypeName, $subFieldXml)){
					$this->setChanged($fieldName);
					$returnValue = true;
				}
			} else if($oldValue!=$value){
				$this->setChanged($fieldName);
				$returnValue = true;
			}
			$this->bag[$fieldName][$subFieldName] = $value;
			// indexes the fact that the value is written
			if(!isset($this->writtenValues)) $this->writtenValues = array();
			$this->writtenValues["($fieldName($subFieldName))"] = true;
			return $returnValue;
		}
		else return false;
	}

	public function applyOperator($operator, $value, $dataTypeName, $fieldName, $subFieldName=null, $field=null){ ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }

	/**
	 * empty the values for a field. if subFieldName is not set then the whole field values
	 * are emptied
	 */
	public function emptyFieldValue($fieldName, $subFieldName=null){
		if(!isset($subFieldName) && isset($this->fixedBag) && !$this->loadedFromFixedBag) $this->loadFromFixedBag();
		if(!isset($this->bag)) return;
		if(!isset($this->bag[$fieldName])) return;
		if($subFieldName == null){
			$this->bag[$fieldName] = null;
		} else {
			$this->bag[$fieldName][$subFieldName] = null;
			// indexes the fact that the value is written
			if(!isset($this->writtenValues)) $this->writtenValues = array();
			$this->writtenValues["($fieldName($subFieldName))"] = true;
		}
	}

	/**
	 * 	Serialize the FormBag
	 *  @param $activity : Activity, the current activity. if activity is null then throw exception
	 *  @param $fieldSelectorList : FieldSelectorList, if define, then the serailization is only for selected field
	 * 	@return string
	 */
	public function exportAsSerializedArray($activity = null, $fieldSelectorList=null){
		return $this->getSystemVersion()."##_v_##".$this->doExportAsSerializedArray($activity, $fieldSelectorList);
	}
	protected function doExportAsSerializedArray($activity = null, $fieldSelectorList=null){
		if($activity==null) throw new ServiceExpception("this implementation requires activity", ServiceException::INVALID_ARGUMENT);
		if(isset($this->fixedBag) && !$this->loadedFromFixedBag) $this->loadFromFixedBag();
		//else $this->debugLogger()->write("doExportAsSerializedArray using local bag");

		$r = array();
		if(isset($fieldSelectorList)){
			//export only selected fields
			foreach($fieldSelectorList->getListIterator() as $fieldSelector){
				$fieldName = $fieldSelector->getFieldName();
				$subFieldName = $fieldSelector->getSubFieldName();
				if($subFieldName == null){
					//export the whole field
					$r[$fieldName]=$this->bag[$fieldName];
				} else {
					//export the specific subfield
					if(!isset($r[$fieldName])) $r[$fieldName] = array();
					$r[$fieldName][$subFieldName] = $this->bag[$fieldName][$subFieldName];
				}
			}
		} else {
			//export the whole bag
			$r = $this->bag;
		}
		return array2str($r);
	}

	/**
	 * Merge in the current FormBag datas from the serialized string
	 * @param $activity : Activity, the current activity. if activity is null then throw exception
	 * @return void
	 */
	public function importFromSerializedArray($string, $activity = null){
		if(false!==strpos($string, "##_v_##")){
			list($versionNb, $string) = explode("##_v_##", $string);
		} else {
			$versionNb = null;
		}
		return $this->doImportFromSerializedArray($versionNb, $string, $activity);
	}
	protected function doImportFromSerializedArray($versionNb, $string, $activity = null){
		if($activity==null) throw new ServiceExpception("this implementation requires activity", ServiceException::INVALID_ARGUMENT);
		if(isset($this->fixedBag) && !$this->loadedFromFixedBag) $this->loadFromFixedBag();
		//else $this->debugLogger()->write("doImportFromSerializedArray using local bag");

		$versionNb = (float)$versionNb;
		if($versionNb < 3.0){
			//importation from Wigii2
			return $this->doImportFromWigii2($string, $activity);
		} else if($versionNb < $this->getSystemVersion()){
			//continue as is, not change for now
		}

		if($string==null) return;
		$bag = str2array($string);
//		eput($string);
//		eput($bag);
		if($bag === false){
			//unserialization hasen't worked
			throw new ServiceException('ImportFormSerializedArray failed to unserialize', ServiceException::INVALID_ARGUMENT);
		} else {
			if(isset($this->bag)){
				$this->bag = array_merge_recursive($this->bag, $bag);
			} else {
				$this->bag = $bag;
			}
		}
	}

	protected function doImportFromWigii2($string, $activity){
		if(!isset($this->bag)) $this->bag = array();
		switch($activity->getActivityName()){
			case "groupPortal":
				if(!isset($this->bag["url"])) $this->bag["url"] = array();
				$this->bag["url"]["url"] = $string;
				break;
			case "groupHtmlContent":
				if(!isset($this->bag["text"])) $this->bag["text"] = array();
				$this->bag["text"]["value"] = $string;
				break;
			case "groupXmlPublish":
				if(!isset($this->bag["enableGroupXmlPublish"])) $this->bag["enableGroupXmlPublish"] = array();
				if(!isset($this->bag["xmlPublishCode"])) $this->bag["xmlPublishCode"] = array();
				if($string == null){
					$this->bag["enableGroupXmlPublish"]["value"] = false;
					$this->bag["xmlPublishCode"]["value"] = null;
				} else {
					$this->bag["enableGroupXmlPublish"]["value"] = true;
					$this->bag["xmlPublishCode"]["value"] = $string;
				}
				break;
			case "groupSubscription":
				if($string == null) return;
				$subscription = unserialize(str_replace("'", "\'", $string)); //on chope ici toutes les paramêtres du subscription
				if($subscription === false) $subscription = unserialize(str_replace("'", "\'", utf8_decode($string)));
				if(is_array($subscription)){
					foreach($subscription as $key=>$val) $subscription[$key]=str_replace("\'", "'",$val);
				}
				//eput($subscription);
				//enableGroupSubscription
				if(!isset($this->bag["enableGroupSubscription"])) $this->bag["enableGroupSubscription"] = array();
				$this->bag["enableGroupSubscription"]["value"] = true;
				//title
				if(!isset($this->bag["title"])) $this->bag["title"] = array();
				if(!isset($this->bag["title"]["value"])) $this->bag["title"]["value"] = array();
				$this->bag["title"]["value"][DEFAULT_LANGUAGE] = $subscription["public_title"];
				//subscriptionPeriod
				if(!isset($this->bag["subscriptionPeriod"])) $this->bag["subscriptionPeriod"] = array();
				$this->bag["subscriptionPeriod"]["isAllDay"] = $subscription["public_dateRange_isAllDay"];
				$this->bag["subscriptionPeriod"]["begTime"] = $subscription["public_dateRange_begTime"];
				$this->bag["subscriptionPeriod"]["endTime"] = $subscription["public_dateRange_endTime"];
				$this->bag["subscriptionPeriod"]["begDate"] = $subscription["public_dateRange_begDate"];
				$this->bag["subscriptionPeriod"]["endDate"] = $subscription["public_dateRange_endDate"];
				//maxSubscriptionNb
				if(!isset($this->bag["maxSubscriptionNb"])) $this->bag["maxSubscriptionNb"] = array();
				$this->bag["maxSubscriptionNb"]["value"] = $subscription["public_maxNb"];
				//subscriptionIllustration
				if(!isset($this->bag["subscriptionIllustration"])) $this->bag["subscriptionIllustration"] = array();
				$this->bag["subscriptionIllustration"]["value"] = $subscription["public_image"];
				//subscriptionClosingMessageAddin
				if(!isset($this->bag["subscriptionClosingMessageAddin"])) $this->bag["subscriptionClosingMessageAddin"] = array();
				if(!isset($this->bag["subscriptionClosingMessageAddin"]["value"])) $this->bag["subscriptionClosingMessageAddin"]["value"] = array();
				$this->bag["subscriptionClosingMessageAddin"]["value"][DEFAULT_LANGUAGE] = nl2br($subscription["public_closeMsg"]);
				//subscriptionBackgroundColorCode
				if(!isset($this->bag["subscriptionBackgroundColorCode"])) $this->bag["subscriptionBackgroundColorCode"] = array();
				$this->bag["subscriptionBackgroundColorCode"]["value"] = $subscription["public_backgroundColor"];
				//subscriptionReturnUrl
				if(!isset($this->bag["subscriptionReturnUrl"])) $this->bag["subscriptionReturnUrl"] = array();
				$this->bag["subscriptionReturnUrl"]["url"] = $subscription["public_returnUrl"];
				//subscriptionConfEmailFrom
				if(!isset($this->bag["subscriptionConfEmailFrom"])) $this->bag["subscriptionConfEmailFrom"] = array();
				$this->bag["subscriptionConfEmailFrom"]["value"] = $subscription["public_conf_emailFrom"];
				//subscriptionConfEmailSubject
				if(!isset($this->bag["subscriptionConfEmailSubject"])) $this->bag["subscriptionConfEmailSubject"] = array();
				if(!isset($this->bag["subscriptionConfEmailSubject"]["value"])) $this->bag["subscriptionConfEmailSubject"]["value"] = array();
				$this->bag["subscriptionConfEmailSubject"]["value"][DEFAULT_LANGUAGE] = $subscription["public_conf_subject"];
				//subscriptionConfEmailText
				if(!isset($this->bag["subscriptionConfEmailText"])) $this->bag["subscriptionConfEmailText"] = array();
				if(!isset($this->bag["subscriptionConfEmailText"]["value"])) $this->bag["subscriptionConfEmailText"]["value"] = array();
				$this->bag["subscriptionConfEmailText"]["value"][DEFAULT_LANGUAGE] = nl2br($subscription["public_conf_text"]);
				//subscriptionConfEmailAttachement1
				if(!isset($this->bag["subscriptionConfEmailAttachement1"])) $this->bag["subscriptionConfEmailAttachement1"] = array();
				//copy the file into FILE_PATH
				$path = $subscription["publicConfFile_realPath"];
				if($path!=null){
					$path = explode("/", $path);
					$path = end($path);
					if(!copy(CLIENT_WEB_PATH."internet_subscriptions_attachement/".$path, FILES_PATH.$path)){
						//problem in moving the file
						$this->bag["subscriptionConfEmailAttachement1"]["name"] = "error importing file from Wigii2";
					} else {
						$this->bag["subscriptionConfEmailAttachement1"]["name"] = $subscription["publicConfFile_name"];
						$this->bag["subscriptionConfEmailAttachement1"]["type"] = $subscription["publicConfFile_type"];
						$this->bag["subscriptionConfEmailAttachement1"]["size"] = $subscription["publicConfFile_size"];
						$this->bag["subscriptionConfEmailAttachement1"]["mime"] = $subscription["publicConfFile_mime"];
						$this->bag["subscriptionConfEmailAttachement1"]["path"] = $path;
						$this->bag["subscriptionConfEmailAttachement1"]["date"] = $subscription["publicConfFile_date"];
						$this->bag["subscriptionConfEmailAttachement1"]["user"] = $subscription["publicConfFile_user"];
						$this->bag["subscriptionConfEmailAttachement1"]["username"] = $subscription["publicConfFile_username"];
					}
				}
				break;
			default:
				throw new ServiceException("unknown activity, impossible to import the activity ".$activity->getActivityName()." from Wigii2", ServiceException::INVALID_ARGUMENT);
		}
	}
}


