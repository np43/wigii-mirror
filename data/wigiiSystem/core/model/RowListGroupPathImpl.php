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
 * RowList implementation for calculating Group Path
 * Created by LWR on 5 August 2011
 */
class RowListGroupPathImpl extends RowListArrayImpl
{
	protected $crtWigiiNamespaceName;
	protected $paths;
	/**
	 * idColumnName: existing column in row which is the id, to be used as a key in the array.
	 * if null, then row are numerically indexed according to php default array behavior.
	 */
	public static function createInstance($crtWigiiNamespaceName = null)
	{
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->crtWigiiNamespaceName = $crtWigiiNamespaceName;
		$returnValue->paths = array();
		return $returnValue;
	}
	
	private $groupNamespaceMapping = array(); //used to reorder the namespaces
	public function addRow($row)
	{
		if(is_null($row)) throw new ListException("row cannot be null", ListException::INVALID_ARGUMENT);
		
		if($row["isParent"]){
			$this->objArray[$row["id"]] = $row;
		} else {
			$this->objArray[$row["id"]] = $row;
			$crtId = $row["id"];
			$orgId = $crtId;
			$this->paths[$crtId] = array();
			while($crtId!=0){
				$ns = $this->objArray[$crtId]["wigiiNamespace"];
				//add the namespace only in the parent groups
				$this->paths[$orgId][$crtId] = ($ns && $ns!=$this->crtWigiiNamespaceName && $this->objArray[$crtId]["id_group_parent"] == 0 ? $ns." : " : "").$this->objArray[$crtId]["groupname"];
				$crtId = $this->objArray[$crtId]["id_group_parent"];
			}
			//reverse order to make parent first
			$this->paths[$orgId] = array_reverse($this->paths[$orgId], true);
			$this->groupNamespaceMapping[$row["wigiiNamespace"]][$orgId] = "";
		}
	}
	/**
	 * if array was created with an idColumnName, then index should be a real database id
	 * else index is a numerical index.
	 */
	public function getRow($index)
	{
		return $this->objArray[$index];
	}
	
	/**
	 * returns an array with the group containing element as an id and an array as value representing the path
	 */
	public function getPaths(){
		if(!$this->paths) return null;
		
		//order the paths with first the current namespace and then the others
		if($this->groupNamespaceMapping){
			
			//sort per namesapce
			ksort($this->groupNamespaceMapping);
			$tempGroupNamespaceMapping = array();
			foreach($this->groupNamespaceMapping as $namespace=>$groupGroups){
				$tempGroups = array();
				//sort in namespace per path
				foreach($groupGroups as $group=>$path){
					$tempGroupNamespaceMapping[$namespace][$group] = implode("/", $this->paths[$group]);
				}
				asort($tempGroupNamespaceMapping[$namespace]);
			}
			$this->groupNamespaceMapping = $tempGroupNamespaceMapping;
			
			$resultPaths = array();
			
			//rebuild the array in the complete good order (first with current namespace)
			if($this->groupNamespaceMapping[$this->crtWigiiNamespaceName]){
				foreach($this->groupNamespaceMapping[$this->crtWigiiNamespaceName] as $group=>$path){
					$resultPaths[$group] = $this->paths[$group];
				}
			}
			//then add groups in other namespace
			foreach($this->groupNamespaceMapping as $namespace=>$groups){
				if($namespace==$this->crtWigiiNamespaceName) continue;
				foreach($groups as $group=>$path){
					$resultPaths[$group] = $this->paths[$group];
				}
			}
			
			$this->paths = $resultPaths;
		}
		return $this->paths;
	}
}
