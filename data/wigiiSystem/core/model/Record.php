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
 * A wigii Record is the parent of an element or an activity. 
 * A record is a set of fields with values.
 * Created by LWR 15 sept 09
 * Modified by Medair (CWE) on 07.07.2017 to add field update helpers
 */
abstract class Record extends DbEntityInstance
{
	const GroupEmailNotificationString = "##GroupEmailNotification##";

	private $idBeforeInsert;
	public function setId($id)
	{
		// if still new, then stores id before insert
		if($this->isNew($id))
		{
			$this->idBeforeInsert = $this->getId();
		}
		return parent::setId($id);
	}
	private $fieldList;
	public function getFieldList(){ return $this->fieldList; }
	public function setFieldList($fieldList) { $this->fieldList = $fieldList; }
	private $wigiiBag;
	public function getWigiiBag() { return $this->wigiiBag; }
	public function setWigiiBag($wigiiBag) { $this->wigiiBag = $wigiiBag; }

	/**
	 * An attached rec can be set for resolving redirect attribute of some
	 * fields
	 */
	protected $attachedRecord;
	public function getAttachedRecord(){ return $this->attachedRecord; }
	public function attachRecord($rec){ $this->attachedRecord = $rec; }
	
	/**
	 * Returns the value of a field given its name
	 * subFieldName: the dataType subfield name from which to retrieve the value. If null takes the predefined "value" subfield.
	 */
	public function getFieldValue($fieldName, $subFieldName = null) {
		$field = $this->getFieldList()->getField($fieldName);
		if(isset($field)) return $field->getValue($this->getId(), $this->getWigiiBag(), $subFieldName);
		else return null;
	}
	/**
	 * Returns the value of a field stored under a specific record id, given its name
	 * This method is useful if record id changed, but wigii bag content is stored under previous id.
	 */
	public function getFieldValueUnderSpecificId($elementId, $fieldName, $subFieldName = null)
	{
		$field = $this->getFieldList()->getField($fieldName);
		if(isset($field)) return $field->getValue($elementId, $this->getWigiiBag(), $subFieldName);
		else return null;
	}

	/**
	 * Returns the value of a field given its name as it was before the database insertion
	 */
	public function getFieldValueBeforeInsert($fieldName, $subFieldName = null)
	{
		return $this->getFieldValueUnderSpecificId($this->idBeforeInsert, $fieldName, $subFieldName);
	}

	/**
	 * Sets the value of a field given its name
	 * subFieldName: the dataType subfield name for which to store the value. If null takes the predefined "value" subfield.
	 */
	public function setFieldValue($value, $fieldName, $subFieldName = null) {
		$field = $this->getFieldList()->getField($fieldName);
		if(isset($field)) $field->setValue($value, $this->getId(), $this->getWigiiBag(), $subFieldName);
		else throw new RecordException("field '$fieldName' is not a valid field name in the record", RecordException::INVALID_ARGUMENT);
	}

	public abstract function getModule();
	public abstract function getActivity();

	/**
	 * Returns an array of redirect value attribut for the specific value.
	 * This works only for Attributes or MultipleAttributes or Booleans
	 * with an attribut node with a redirect attribut seted.
	 * @param forValue value of the field:
	 * 	- if Attributs -> string
	 *  - if MultipleAttributs -> array of string
	 *  - if Booleans -> bool (done only if true)
	 */
	public function getRedirectedFieldValues($p, $fieldName, $forValue)
	{
		$f = $this->getFieldList()->getField($fieldName);
		$fxml = $f->getXml();
		$dt = $f->getDataType()->getDataTypeName();
		if($dt == "Attributs" || $dt == "MultipleAttributs"){
			if(is_array($forValue)){
				$forValue = implode("\" or text()=\"", $forValue);
			}
			$redirects = $fxml->xpath("attribute[@redirect and (text()=\"$forValue\")]");
			if($redirects){
				$attachedRec = $this->getAttachedRecord();
				if($attachedRec == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

				if($dt == "MultipleAttributs"){
					$returnValue = array();
					foreach($redirects as $redirect){
						$returnValue[] = $attachedRec->getFieldValue((string)$redirect["redirect"]); //(string)$redirect["redirect"];
					}
				} else {
					$returnValue = $attachedRec->getFieldValue((string)$redirects[0]["redirect"]);
				}
				return $returnValue;
			}
		}

		if($dt == "Booleans" && $fxml["redirect"] != null){
			$attachedRec = $this->getAttachedRecord();
			if($attachedRec == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

			if($forValue){
				$attachedRec->getFieldValue((string)$fxml["redirect"]);
			}
		}
		return false;
	}

	/**
	 * Fill the valueList with the idGroups found in an attribute (even if this attribute is not the one selected)
	 * The redirection for Attributes, MultipleAttributes, Booleans is solved if defined.
	 * This works only for Attributes, MultipleAttributes, Booleans.
	 * No redirection can be defined in this process.
	 * @param $p Principal
	 * @param $valueList : ValueList
	 */
	public function getAllLinkedIdGroupInRecord($p, $valueList){
		$wg = $this->getWigiiBag();
		$wpl = ServiceProvider::getWigiiBPL();
		foreach($this->getFieldList()->getListIterator() as $fieldName=>$f){
			//check only dataTypes
			if($f->getDataType() != null){
				$fxml = $f->getXml();
				$dt = $f->getDataType()->getDataTypeName();
				$gids = array();
				if($dt == "Attributs" || $dt == "MultipleAttributs"){
					// CWE 15.08.2018: evaluates dynamic attribute idGroup if defined
					$dynGids= $fxml->xpath("attribute[@idGroup and @idGroup!=\"\" and @enableDynamicAttributes=\"1\"]/attribute::idGroup");
					if($dynGids) {
						foreach($dynGids as $dynGid) {
							$gids[] = $wpl->evaluateConfigParameter($p, $dynGid,$this);
						}
						$dynGids = implode(";",$gids);
					}
					// Static id groups
					$gids= $fxml->xpath("attribute[@idGroup and @idGroup!=\"\" and not(@enableDynamicAttributes)]/attribute::idGroup");
					if($gids)  $gids = implode(";",$gids);
					else $gids = '';
					if($dynGids) $gids = $dynGids.';'.$gids;
				} else if($dt == "Booleans"){
					if($fxml["idGroup"] != null){
						$gids = (string)$fxml["idGroup"];
						// CWE 15.08.2018: evaluates dynamic attribute idGroup if defined
						if($fxml["enableDynamicAttributes"]=="1") $gids = $wpl->evaluateConfigParameter($p, $gids,$this);
					}
				}
				if($gids) $valueList->addValue($gids);
			}
		}
	}
	/**
	 * Fill the valueList with the idGroups found in an attribute.
	 * The redirection for Attributes, MultipleAttributes, Booleans is solved if defined.
	 * This works only for Attributes, MultipleAttributes, Booleans.
	 * No redirection can be defined in this process.
	 * @param Principal $p
	 * @param ValueList $valueList a ValueList instance to be filled with the group ids. If not given, then creates a ValueListArrayMapper instance
	 * @param Boolean $isMultiple if true the check will be done accordingly to wigiiBag->isMultipleChecked
	 * @return ValueList returns the filled value list.
	 */
	public function getLinkedIdGroupInRecord($p, $valueList, $isMultiple=false){
		if($valueList==null) $valueList = ValueListArrayMapper::createInstance ( true, ValueListArrayMapper::Natural_Separators, true );
		$wg = $this->getWigiiBag();
		$wpl = ServiceProvider::getWigiiBPL();
		foreach($this->getFieldList()->getListIterator() as $fieldName=>$f){
			//check only dataTypes
			if($f->getDataType() != null){
				//if isMultiple, check only the multiple checked field
				if(!$isMultiple || $isMultiple && $wg->isMultipleChecked($fieldName)){
					$fxml = $f->getXml();
					$dt = $f->getDataType()->getDataTypeName();
					$gids = array();
					if($dt == "Attributs" || $dt == "MultipleAttributs"){
						$forValue = $this->getFieldValue($fieldName);
						if($forValue == null) continue; //empty value cause a simpleXml path warning
						if(is_array($forValue)){
							$forValue = implode("\" or text()=\"", $forValue); //$tempRes;
						}
						// CWE 15.08.2018: evaluates dynamic attribute idGroup if defined
						$dynGids= $fxml->xpath("attribute[@idGroup and (text()=\"$forValue\") and @idGroup!=\"\" and @enableDynamicAttributes=\"1\" and not(@movePriority)]/attribute::idGroup");
						if($dynGids) {
							foreach($dynGids as $dynGid) {
								$gids[] = $wpl->evaluateConfigParameter($p, $dynGid,$this);
							}
							$dynGids = implode(";",$gids);
						}
						// Static id groups
						$gids= $fxml->xpath("attribute[@idGroup and (text()=\"$forValue\") and @idGroup!=\"\" and not(@enableDynamicAttributes) and not(@movePriority)]/attribute::idGroup");
						if($gids)  $gids = implode(";",$gids);
						else $gids = '';
						if($dynGids) $gids = $dynGids.';'.$gids;
					} else if($dt == "Booleans"){
						$forValue = $this->getFieldValue($fieldName);
						if($forValue){
							if($fxml["idGroup"] != null && !$fxml["movePriority"]){
								$gids = (string)$fxml["idGroup"];
								// CWE 15.08.2018: evaluates dynamic attribute idGroup if defined
								if($fxml["enableDynamicAttributes"]=="1") $gids = $wpl->evaluateConfigParameter($p, $gids,$this);
							}
						}
					}
					if($gids) $valueList->addValue($gids);
				}
			}
		}
		return $valueList;
	}	
	
	public function getMoveGroupInRecord(){
		$priorityMax = null;
		$gidMax = null;
		foreach($this->getFieldList()->getListIterator() as $fieldName=>$f){
			if($f->getDataType() != null){
				$fxml = $f->getXml();
				$dt = $f->getDataType()->getDataTypeName();
				if($dt == "Attributs" || $dt == "MultipleAttributs"){
					$forValue = $this->getFieldValue($fieldName);
					if($forValue == null) continue; //empty value cause a simpleXml path warning
					if(is_array($forValue)){
						$forValue = implode("\" or text()=\"", $forValue); //$tempRes;
					}
					$result= $fxml->xpath("attribute[@movePriority and (text()=\"$forValue\")]/attribute::idGroup");
					$gids = array();
					foreach($result as $gid){
						$gids[] = (string)$gid;
					}
					
					$result= $fxml->xpath("attribute[@movePriority and (text()=\"$forValue\")]/attribute::movePriority");
					foreach($result as $index=>$prio) {
						$prio = (string)$prio;
						if(!isset($priorityMax)) {
							$priorityMax = $prio;
							$gidMax = $gids[$index];
						}
						elseif($prio > $priorityMax) {
							$priorityMax = $prio;
							$gidMax = $gids[$index];
						}
					}
										
					//$combine = array_combine($gids, $priority);
				} else if($dt == "Booleans"){
					$forValue = $this->getFieldValue($fieldName);
					if($forValue){
						if($fxml["idGroup"] != null){
							$gids = (string)$fxml["idGroup"];
						}
					}
					$prio = (string)$fxml["movePriority"];
					if(!isset($priorityMax)) {
						$priorityMax = $prio;
						$gidMax = $gids;
					}
					elseif($prio > $priorityMax) {
						$priorityMax = $prio;
						$gidMax = $gids;
					}
				}				
			}
		}
		return $gidMax;
	}
	
	/**
	 * Fill the valueList with the idGroups found in any attribute that are not selected
	 * in the multiple edit record. Used in: EditMultipleElementFormExecutor.php
	 * @param $p Principal
	 * @param $valueList : ValueList
	 */
	public function getNoneLinkedIdGroupInRecord($p, $valueList, $isMultiple=false){
		$wg = $this->getWigiiBag();
		$wpl = ServiceProvider::getWigiiBPL();
		foreach($this->getFieldList()->getListIterator() as $fieldName=>$f){
			if($f->getDataType() != null){
				if(!$isMultiple || $isMultiple && $wg->isMultipleChecked($fieldName) && !$wg->isMultipleAddOnlyChecked($fieldName)){
					$fxml = $f->getXml();
					$dt = $f->getDataType()->getDataTypeName();
					$gids = array();
					if($dt == "Attributs" || $dt == "MultipleAttributs"){
						$forValue = $this->getFieldValue($fieldName);
						if($forValue != null){
							if(is_array($forValue)){
								$forValue = implode("\" or text()!=\"", $forValue); //$tempRes;
							}
							// CWE 15.08.2018: evaluates dynamic attribute idGroup if defined
							$dynGids= $fxml->xpath("attribute[@idGroup and (text()!=\"$forValue\") and @idGroup!=\"\" and @enableDynamicAttributes=\"1\"]/attribute::idGroup");
							if($dynGids) {
								foreach($dynGids as $dynGid) {
									$gids[] = $wpl->evaluateConfigParameter($p, $dynGid,$this);
								}
								$dynGids = implode(";",$gids);
							}
							// Static id groups
							$gids= $fxml->xpath("attribute[@idGroup and (text()!=\"$forValue\") and @idGroup!=\"\" and not(@enableDynamicAttributes)]/attribute::idGroup");
						} else {
							//empty value cause a simpleXml path warning
							
							// CWE 15.08.2018: evaluates dynamic attribute idGroup if defined
							$dynGids= $fxml->xpath("attribute[@idGroup and @idGroup!=\"\" and @enableDynamicAttributes=\"1\"]/attribute::idGroup");
							if($dynGids) {
								foreach($dynGids as $dynGid) {
									$gids[] = $wpl->evaluateConfigParameter($p, $dynGid,$this);
								}
								$dynGids = implode(";",$gids);
							}
							// Static id groups
							$gids= $fxml->xpath("attribute[@idGroup and @idGroup!=\"\" and not(@enableDynamicAttributes)]/attribute::idGroup");
						}
						if($gids)  $gids = implode(";",$gids);
						else $gids = '';
						if($dynGids) $gids = $dynGids.';'.$gids;
					} else if($dt == "Booleans"){
						$forValue = $this->getFieldValue($fieldName);
						if(!$forValue){
							if($fxml["idGroup"] != null){
								$gids = (string)$fxml["idGroup"];
								// CWE 15.08.2018: evaluates dynamic attribute idGroup if defined
								if($fxml["enableDynamicAttributes"]=="1") $gids = $wpl->evaluateConfigParameter($p, $gids,$this);
							}
						}
					}
					if($gids) $valueList->addValue($gids);
				}
			}
		}
	}

	/**
	 * Fill the valueList with the emails found in an attribute email.
	 * The redirection for Attributes, MultipleAttributes, Booleans is solved if defined.
	 * This works only for Attributes, MultipleAttributes, Booleans.
	 * This method is recursive for the redirection, but allows a redirection on an Emails field.
	 * Only in the case of a redirection an Emails field value is returned. If a redirection is set and then
	 * no attachedRecord is setted to this level, then an exception is thrown
	 * with an attribut node with a redirect attribut seted.
	 * @param $p Principal
	 * @param $valueList : ValueList
	 * @return bool, true if GroupEmailNotification string request was in the email found. But the GroupEmailNotification emails are not added. this needs to be done externally
	 */
	public function getLinkedEmailInRecord($p, $valueList){
		$hasGroupEmailNotification = false;
		foreach($this->getFieldList()->getListIterator() as $fieldName=>$field){
			if($field->getDataType() != null){
				$value = $this->getLinkedEmailInField($p, $fieldName, $this->getFieldValue($fieldName));
				//we always do the check of GroupEmailNotification to replace it as ""
				if(!is_array($value)) $value = array($value);
				foreach($value as $val){
					if(strstr($val, "##GroupEmailNotification##")) {
						$val = str_replace("##GroupEmailNotification##", "", $val);
						$hasGroupEmailNotification = true;
					}
					if($val) $valueList->addValue($val);
				}
			}
		}
		return $hasGroupEmailNotification;
	}

	/**
	 * Returns a string with the emails define in an attribute email.
	 * The redirection for Attributes, MultipleAttributes, Booleans is solved if defined.
	 * This works only for Attributes, MultipleAttributes, Booleans.
	 * This method is recursive for the redirection, but allows a redirection on an Emails field.
	 * Only in the case of a redirection an Emails field value is returned. If a redirection is set and then
	 * no attachedRecord is setted to this level, then an exception is thrown
	 * with an attribut node with a redirect attribut seted.
	 * @param forValue value of the field:
	 * 	- if Attributs -> string
	 *  - if MultipleAttributs -> array of string
	 *  - if Booleans -> bool (done only if true)
	 */
	public function getLinkedEmailInField($p, $fieldName, $forValue)
	{
		return $this->doGetLinkedEmailInField($p, $fieldName, $forValue, false);
	}

	protected function doGetLinkedEmailInField($p, $fieldName, $forValue, $fromRedirect){
		$f = $this->getFieldList()->getField($fieldName);
		$fxml = $f->getXml();
		$dt = $f->getDataType()->getDataTypeName();

		if($dt == "Emails" && ($fromRedirect || $fxml["enableForNotification"]=="1")){
			return $forValue;
		} else if($dt == "Attributs" || $dt == "MultipleAttributs"){
			if($forValue == null) return null; //empty value cause a simpleXml path warning
			$emails = "";
			if(is_array($forValue)){
//				$tempRes = "";
//				$tempFirst = true;
//				foreach($forValue as $tempVal){
//					if($tempFirst) $tempFirst = false;
//					else $tempRes .= "' or text()='";
//					$tempRes .= str_replace("'", "\\'", $tempVal);
//				}
				$forValue = implode("\" or text()=\"", $forValue); //$tempRes;
			} else {
//				$forValue = str_replace("'", "\\'", $forValue);
			}
			$emails = $fxml->xpath("attribute[@email and (text()=\"$forValue\")]/attribute::email");
			if($emails) $emails = implode(";",$emails); //(string)$emails[0];

			$redirects = $fxml->xpath("attribute[@redirect and (text()=\"$forValue\")]");

			if($redirects){
				$attachedRec = $this->getAttachedRecord();
				if($attachedRec == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

				if($dt == "MultipleAttributs"){
					foreach($redirects as $redirect){
						$emails .= ";".$attachedRec->doGetLinkedEmailInField($p, (string)$redirect["redirect"], $attachedRec->getFieldValue((string)$redirect["redirect"]), true);
					}
				} else {
					$emails .= ";".$attachedRec->doGetLinkedEmailInField($p, (string)$redirects[0]["redirect"], $attachedRec->getFieldValue((string)$redirects[0]["redirect"]), true);
				}
			}
			return $emails;

		} else if($dt == "Booleans" && $forValue){
			$emails = "";
			if($fxml["email"] != null){
				$emails = (string)$fxml["email"];
			}
			if($fxml["redirect"] != null){
				$attachedRec = $this->getAttachedRecord();
				if($attachedRec == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

				if($forValue){
					$emails .= ";".$attachedRec->doGetLinkedEmailInField($p, (string)$fxml["redirect"], $attachedRec->getFieldValue((string)$fxml["redirect"]), true);
					//$attachedRec->getFieldValue((string)$fxml["redirect"]);
				}
			}
			return $emails;
		}
		return false;
	}

	/**
	 * returns a translated label of the field with in () the result
	 * of the redirection if it exist.
	 * if no value is found returns false!
	 * @param SimpleXMLElement $attribute node on which the redirect attribut is set
	 * this method can be used either on an attribut of a select, either on any
	 * other field.
	 */
	public function getRedirectedFieldLabel($p, $fieldName, $attribute){
		$transS = ServiceProvider::getTranslationService();
		$redirect = (string) $attribute["redirect"];
		if($redirect == null) return $transS->t($p, (string)$attribute, $attribute);

		$attachedRec = $this->getAttachedRecord();
		if($attachedRec == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

		$returnValue = $transS->t($p, (string)$attribute, $attribute);

		$value = $attachedRec->getFieldValue($redirect);
		/*********************************
		 * if no value then return FALSE!!
		 ********************************/
		if($value == null) return false;

		$attachedRecField = $attachedRec->getFieldList()->getField($redirect);
		$fieldDatatypeName = $attachedRecField->getDataType()->getDatatypeName();
		//if the redirect points on Attributs or MultipleAttributs
		// then we need to translate the value found
		if($fieldDatatypeName == "Attributs" || $fieldDatatypeName == "MultipleAttributs"){
//			$tempValue = array();
//			foreach($attachedRecField->getXml()->attribute as $attr){
//				if((is_array($value) && array_search((string)$attr, $value) !== false) ||
//					$value == (string)$attr){
//					$tempValue[] = $transS->t($p, (string)$attr, $attr);
//				}
//			}
			if(is_array($value)){
				$value = implode("\" or text()=\"", $value);
			}
			$value = $attachedRecField->getXml()->xpath("attribute[(text()=\"".$value."\")]");
			$tempValue = array();
			foreach($value as $val){
				$tempValue[] = $transS->t($p, (string)$val, $val);
			}
			$value = implode(", ", $tempValue);

		} else if($fieldDatatypeName == "Booleans"){
			$value = $transS->t($p, $redirect, $attachedRecField->getXml());
		}

		$returnValue .= " (".$value.")";

		return $returnValue;
	}
	
	/**
	 * Exports a portion of the record as a matrix.
	 * @param array $columns an array of strings giving the columns prefix to be used
	 * @param int $startIndex the row index from which to export. If not specified, then starts with 1
	 * @param int $stopIndex the row index until which to export. If not specified, then exports all the found rows.
	 * @param ValueList $valueList the value list to be filled with the matrix rows
	 * @example Consider the record with values :
	 * ProjectCode_1: P1, Location_1: L1, Key_1: P1L1
	 * ProjectCode_2: P2, Location_2: L2, Key_2: P2L2
	 * ProjectCode_3: P3, Location_3: L3, Key_3: P3L3
	 * exportMatrix(array('ProjectCode_', 'Location_', 'Key_'), 1, 3) 
	 * will return an array of StdClass [{ProjectCode_ : {value : P1, ...}, Location_ : {value : L1, ...}, Key_ : {value : P1L1, ...}}, {ProjectCode_ : {value : P2, ...}, Location_ : {value : L2, ...}, Key_ : {value : P2L2, ...}}, {ProjectCode_ : {value : P3, ...}, Location_ : {value : L3, ...}, Key_ : {value : P3L3, ...}}] 
	 * @return array|int if valueList is defined, then returns the number of objects added, 
	 * else returns an array containing the added objects
	 */
	public function exportMatrix($columns, $startIndex=null, $stopIndex=null, $valueList=null) {
		if(empty($columns) || !is_array($columns)) throw new RecordException('columns should be a non-empty array of strings specifying the columns of the matrix to be exported', RecordException::INVALID_ARGUMENT);
		$fillValueList = (isset($valueList));
		if($fillValueList) $returnValue = 0; 
		else $returnValue = array();
		
		if($startIndex===null) $startIndex=1;				
		$fieldList = $this->getFieldList();
		if($stopIndex===null) {
			$stopIndex=(int)($fieldList->count()/count($columns));
			$detectLastRow=true;
		}
		else $detectLastRow=false;
		
		// extracts each (non empty) row
		for($i = $startIndex; $i <= $stopIndex; $i++) {
			$emptyRow = true;
			$firstCol=true;
			$row = array();
			// extracts each column of row
			foreach($columns as $col) {
				$fieldName = $col.$i;
				// stops on last row if stopIndex was not specified
				if($firstCol && $detectLastRow && !$fieldList->doesFieldExist($fieldName) && $i>$startIndex) break 2;
				$fieldWithSelectedSubfields = FieldWithSelectedSubfields::createInstance($fieldList->getField($fieldName));
				$fieldWithSelectedSubfields->selectAllsubfields();
				$dtXml = $fieldWithSelectedSubfields->getField()->getDataType();
				if(isset($dtXml)) $dtXml = $dtXml->getXml();				
				$fieldValue = array();				
				// extracts each subfields
				foreach($fieldWithSelectedSubfields->getSelectedSubfieldsIterator() as $subFieldName) {
					$subFieldValue = $this->getFieldValue($fieldName, $subFieldName);
					// checks if row has at least one non hidden subfield which is filled.
					if(!empty($subFieldValue) && $dtXml->{$subFieldName}['type'] != 'hidden') $emptyRow = false;
					$fieldValue[$subFieldName] = $subFieldValue;
				}
				$row[$col] = (object)$fieldValue;
				$firstCol=false;
			}	
										
			// add matrix row to matrix
			if(!$emptyRow) {
				$row = (object)$row;
				if($fillValueList) {
					$valueList->addValue($row);
					$returnValue++;
				}
				else {
					$returnValue[] = $row;
				}
			}
		}
		
		return $returnValue;
	}	
	
	/**
	 * Updates a matrix portion of a record
	 * @param array $columns an array of strings giving the columns prefix to be used
	 * @param int $startIndex the row index from which to update
	 * @param int $stopIndex the row index until which to update
	 * @param ObjectList|array $objectList the matrix rows as an ObjectList or an array of StdClass
	 * @example Consider the matrix $m as an array or an ObjectList of StdClass
	 * $m = [{ProjectCode_ : {value : P1, ...}, Location_ : {value : L1, ...}, Key_ : {value : P1L1, ...}}, {ProjectCode_ : {value : P2, ...}, Location_ : {value : L2, ...}, Key_ : {value : P2L2, ...}}, {ProjectCode_ : {value : P3, ...}, Location_ : {value : L3, ...}, Key_ : {value : P3L3, ...}}]
	 * updateMatrix(array('ProjectCode_', 'Location_', 'Key_'), 1, 3, $m)
	 * will update the WigiiBag by calling
	 * $this->setFieldValue(P1, ProjectCode_1);
	 * $this->setFieldValue(L1, Location_1);
	 * $this->setFieldValue(P1L1, Key_1);
	 * $this->setFieldValue(P2, ProjectCode_2);
	 * $this->setFieldValue(L2, Location_2);
	 * $this->setFieldValue(P2L2, Key2);
	 * $this->setFieldValue(P3, ProjectCode_3);
	 * $this->setFieldValue(L3, Location_3);
	 * $this->setFieldValue(P3L3, Key_3);
	 * @return int the number of updated rows
	 */
	public function updateMatrix($columns, $startIndex, $stopIndex, $objectList) {
		if($objectList instanceOf ObjectList) $rows = $objectList->getListIterator();
		elseif(is_array($objectList)) $rows = $objectList;
		else throw new RecordException('objectList should be an ObjectList or an array containing the matrix rows as StdClasses', RecordException::INVALID_ARGUMENT);
		if(empty($columns) || !is_array($columns)) throw new RecordException('columns should be a non-empty array of strings specifying the columns of the matrix taken to update the record', RecordException::INVALID_ARGUMENT);
		
		$returnValue = 0;
		// updates each row
		if(!empty($rows)) {
			$fieldList = $this->getFieldList();
			$i = $startIndex;
			foreach($rows as $row) {
				// updates row only between start and stop index range
				if($i <= $stopIndex) {
					// updates each field
					foreach($columns as $col) {
						$fieldName = $col.$i;
						$fieldWithSelectedSubfields = FieldWithSelectedSubfields::createInstance($fieldList->getField($fieldName));
						$fieldWithSelectedSubfields->selectAllsubfields();
						// updates each subfield
						$fieldValue = $row->{$col};
						foreach($fieldWithSelectedSubfields->getSelectedSubfieldsIterator() as $subFieldName) {
							if(isset($fieldValue)) $subFieldValue = $fieldValue->{$subFieldName};
							else $subFieldValue = null;
							$this->setFieldValue($subFieldValue, $fieldName, $subFieldName);
						}
					}
					$i++;
					$returnValue++;
				}
				else break;
			}
			// updates missing rows as blank lines
			while($i <= $stopIndex) {
				// updates each field
				foreach($columns as $col) {
					$fieldName = $col.$i;
					$fieldWithSelectedSubfields = FieldWithSelectedSubfields::createInstance($fieldList->getField($fieldName));
					$fieldWithSelectedSubfields->selectAllsubfields();
					// updates each subfield
					foreach($fieldWithSelectedSubfields->getSelectedSubfieldsIterator() as $subFieldName) {
						$this->setFieldValue(null, $fieldName, $subFieldName);
					}
				}
				$i++;
			}
		}
		return $returnValue;
	}
	
	/**
	 * Converts this record (or a portion of it) into a PHP StdClass of the form StdClass {fieldName : subfieldName, ...}
	 * @param FieldSelectorList $fieldSelectorList an optional FieldSelectorList which can be used to select the fields that will be dumped in the StdClass.
	 * If not specified, then all the Fields of the record are dumped.
	 * @return stdClass
	 */
	public function toStdClass($fieldSelectorList=null) {
		$returnValue = array();
		$fieldWithSelectedSubfieldsList = FieldWithSelectedSubfieldsListArrayImpl::createInstance($this->getFieldList(), $fieldSelectorList);
		foreach($fieldWithSelectedSubfieldsList->getListIterator() as $fieldWithSelectedSubfields) {
			$fieldName = $fieldWithSelectedSubfields->getField()->getFieldName();
			$fieldValue = array();
			foreach($fieldWithSelectedSubfields->getSelectedSubfieldsIterator() as $subFieldName) {
				$fieldValue[$subFieldName] = $this->getFieldValue($fieldName, $subFieldName);
			}
			$returnValue[$fieldName] = (object)$fieldValue;
		}
		return (object)$returnValue;
	}
	
	/**
	 * Adds a value to a field of type MultipleAttributs
	 */
	public function addValueToField($value, $fieldName) {
	    if(is_null($value)) return;
	    $field = $this->getFieldList()->getField($fieldName);
	    if(!($field->getDataType() instanceof MultipleAttributs)) throw new RecordException('Field '.$fieldName." is not of type MultipleAttributs. addValueToField only works with MultipleAttributs, use setFieldValue instead.", RecordException::INVALID_ARGUMENT);
	    $currentVal = $this->getFieldValue($fieldName);
	    if(is_array($currentVal)) $currentVal[$value] = $value;
	    elseif(isset($currentVal)) $currentVal = array($currentVal=>$currentVal,$value=>$value);
	    else $currentVal = array($value=>$value);
	    $this->setFieldValue($currentVal,$fieldName);
	}
	/**
	 * Prepends a value to a field
	 * @param String|Number $value value to prepend to the field
	 * @param String $fieldName field name
	 * @param String $sep optional separator that would be put between value and existing value if both are not null
	 * @param String $subFieldName optional subfield name
	 */
	public function prependValueToField($value, $fieldName, $sep=null, $subFieldName=null) {
	    if(is_null($value)) return;
	    $currentVal = $this->getFieldValue($fieldName, $subFieldName);
	    if(!empty($sep) && (!empty($value)||$value===0) && (!empty($currentVal)||$value===0)) $this->setFieldValue($value.$sep.$currentVal, $fieldName,$subFieldName);
	    else $this->setFieldValue($value.$currentVal, $fieldName,$subFieldName);
	}
	/**
	 * Appends a value to a field
	 * @param String|Number $value value to append to the field
	 * @param String $fieldName field name
	 * @param String $sep optional separator that would be put between existing value and value if both are not null
	 * @param String $subFieldName optional subfield name
	 */
	public function appendValueToField($value, $fieldName, $sep=null, $subFieldName=null) {
	    if(is_null($value)) return;
	    $currentVal = $this->getFieldValue($fieldName, $subFieldName);
	    if(!empty($sep) && (!empty($value)||$value===0) && (!empty($currentVal)||$value===0)) $this->setFieldValue($currentVal.$sep.$value, $fieldName,$subFieldName);
	    else $this->setFieldValue($currentVal.$value, $fieldName,$subFieldName);
	}	
}


