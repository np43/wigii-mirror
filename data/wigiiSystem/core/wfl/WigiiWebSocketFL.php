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
 *  @copyright  Copyright (c) 2019  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * Web sockets Func Exp library to be used with Wigii Web Socket server
 * Created by CWE on 18.09.2019
 */
class WigiiWebSocketFL extends FuncExpVMAbstractFL
{	
	// Dependency injection
	
    private $webSocketServer;
    public function setWebSocketServer($webSocketServer) {
        $this->webSocketServer = $webSocketServer;
    }
    /**
     * @return WigiiWebSocketServer
     */
    protected function getWebSocketServer() {
        if(!isset($this->webSocketServer)) throw new WigiiWebSocketServerException('web socket server is not configured', WigiiWebSocketServerException::CONFIGURATION_ERROR);
        else return $this->webSocketServer;
    }
    
    // Func Exp library
    
    /**
	 * Stops Wigii web socket server.
	 * FuncExp signature : <code>wsSrvStop()</code>
	 */
	public function wsSrvStop($args) {
		return $this->getWebSocketServer()->stop($this->getPrincipal());
	}	
	
	/**
	 * @return String Returns current connection Id
	 */
	public function wsGetConnectionId($args) {
	    return $this->getWebSocketServer()->getCrtConnectionId();
	}
	
	/**
	 * Assigns a value to a variable.
	 * FuncExp signature : <code>wsAssignVar(varName,varValue)</code><br/>
	 * Where arguments are :
	 * - Arg(0) varName: String. Name of the js variable to which assign a value
	 * - Arg(1) varValue: mixed. An JSON serializable value to assign to the variable
	 */
	public function wsAssignVar($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<2) throw new WigiiWebSocketServerException('wsAssignVar takes two arguments: the var name and var value', WigiiWebSocketServerException::INVALID_ARGUMENT);
	    return $this->getWebSocketServer()->jsAssignVar($this->getPrincipal(), $this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}
	
	/**
	 * Evaluates a Func Exp and pushes the result into a callback.
	 * FuncExp signature : <code>wsCallback(funcName,fx)</code><br/>
	 * Where arguments are :
	 * - Arg(0) funcName: String. js function to call back with Func Exp result
	 * - Arg(1) fx: FuncExp. Func Exp to evaluate
	 */
	public function wsCallback($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<2) throw new WigiiWebSocketServerException('wsCallback takes two arguments: the callback function name and Func Exp to evaluate', WigiiWebSocketServerException::INVALID_ARGUMENT);
	    return $this->getWebSocketServer()->jsCallFunction($this->getPrincipal(), $this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}
	
	/**
	 * Evaluates a Func Exp and pushes the result to a peer.
	 * FuncExp signature : <code>wsForwardToPeer(connectionId,fx)</code><br/>
	 * Where arguments are :
	 * - Arg(0) connectionId: String. A currently open socket id
	 * - Arg(1) fx: FuncExp. Func Exp to evaluate
	 */
	public function wsForwardToPeer($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<2) throw new WigiiWebSocketServerException('wsForwardToPeer takes at least two arguments: the peer connection id and the FuncExp to evaluate', WigiiWebSocketServerException::INVALID_ARGUMENT);
	    $wssrv = $this->getWebSocketServer();
	    $p = $this->getPrincipal();
	    // Adds peer connection to outbounding list (check of connection is done by server)
	    $wssrv->addOutboundingConnection($p, $this->evaluateArg($args[0]));
	    // disables current connection
	    $wssrv->disableCrtConnectionOutbound($p);
	    // evaluates FuncExp
	    return $this->evaluateArg($args[1]);
	}
	
	/**
	 * Evaluates a Func Exp and pushes the result to all open connections into a group.
	 * FuncExp signature : <code>wsForwardToGroup(groupName,fx)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupName: String. An existing group
	 * - Arg(1) fx: FuncExp. Func Exp to evaluate
	 */
	public function wsForwardToGroup($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<2) throw new WigiiWebSocketServerException('wsForwardToGroup takes at least two arguments: the group name and the FuncExp to evaluate', WigiiWebSocketServerException::INVALID_ARGUMENT);
	    $wssrv = $this->getWebSocketServer();
	    $p = $this->getPrincipal();
	    // Adds group to outbounding list (check of is done by server)
	    $wssrv->addOutboundingGroup($p, $this->evaluateArg($args[0]));
	    // disables current connection
	    $wssrv->disableCrtConnectionOutbound($p);
	    // evaluates FuncExp
	    return $this->evaluateArg($args[1]);
	}
	
	/**
	 * Creates a new group (and joins to it).
	 * FuncExp signature : <code>wsCreateGroup(groupName,accessKey=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupName: String. Group name, should be unique for whole wigii client
	 * - Arg(1) accessKey: String. Optional accessKey to protect operations on group.
	 * @throws WigiiWebSocketServerException GROUP_ALREADY_EXISTS if a group of same name already exists. 
	 * In that case, prefer the join method or choose another name.
	 */
	public function wsCreateGroup($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<1) throw new WigiiWebSocketServerException('wsCreateGroup takes at least one argument: the group name', WigiiWebSocketServerException::INVALID_ARGUMENT);
	    $groupName = $this->evaluateArg($args[0]);
	    if($nArgs>1) $accessKey = $this->evaluateArg($args[1]);
	    else $accessKey = null;
	    return $this->getWebSocketServer()->createGroup($this->getPrincipal(), $this->getWebSocketServer()->getCrtConnectionId(), $groupName, $accessKey);
	}
	
	/**
	 * Join an existing group or create a new group.
	 * FuncExp signature : <code>wsJoinGroup(groupName,accessKey=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupName: String. Group name, should be unique for whole wigii client
	 * - Arg(1) accessKey: String. Optional accessKey to access group.
	 */
	public function wsJoinGroup($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<1) throw new WigiiWebSocketServerException('wsJoinGroup takes at least one argument: the group name', WigiiWebSocketServerException::INVALID_ARGUMENT);
	    $groupName = $this->evaluateArg($args[0]);
	    if($nArgs>1) $accessKey = $this->evaluateArg($args[1]);
	    else $accessKey = null;
	    return $this->getWebSocketServer()->joinGroup($this->getPrincipal(), $this->getWebSocketServer()->getCrtConnectionId(), $groupName, $accessKey);
	}
	
	/**
	 * Leave a group. When everyone left, group is automatically deleted.
	 * FuncExp signature : <code>wsLeaveGroup(groupName)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupName: String. Group name to leave from
	 */
	public function wsLeaveGroup($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<1) throw new WigiiWebSocketServerException('wsLeaveGroup takes at least one argument: the group name', WigiiWebSocketServerException::INVALID_ARGUMENT);
	    $groupName = $this->evaluateArg($args[0]);
	    return $this->getWebSocketServer()->leaveGroup($this->getPrincipal(), $this->getWebSocketServer()->getCrtConnectionId(), $groupName);
	}
	
	/**
	 * Shares some data into a group. (group is created if not already existing).
	 * FuncExp signature : <code>wsShareDataInGroup(data,groupName,accessKey=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: StdClass. An std class instance which keys will be shared with others.
	 * If some keys are already present in current shared data, then their value are merged.
	 * At this level, no version control is done. Existing values are always updated with new ones.
	 * Locks or access rights on data branches should be built on top of this function.
	 * - Arg(1) groupName: String. Group name, should be unique for whole wigii client
	 * - Arg(2) accessKey: String. Optional accessKey to access group.
	 * @return stdClass merged existing data
	 */
	public function wsShareDataInGroup($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<2) throw new WigiiWebSocketServerException('wsShareDataInGroup takes at least two arguments: the data to share and the group name', WigiiWebSocketServerException::INVALID_ARGUMENT);
	    $data = $this->evaluateArg($args[0]);
	    $groupName = $this->evaluateArg($args[1]);
	    if($nArgs>2) $accessKey = $this->evaluateArg($args[2]);
	    else $accessKey = null;
	    if(!($data instanceof stdClass)) throw new WigiiWebSocketServerException('data should be a non null object', WigiiWebSocketServerException::INVALID_ARGUMENT);
	    // joins group
	    $wssrv = $this->getWebSocketServer();
	    $p = $this->getPrincipal();
	    $wssrv->joinGroup($p, $wssrv->getCrtConnectionId(), $groupName, $accessKey);
	    // gets existing data for group
	    $groupDataKey = 'group/'.$groupName;
	    $existingData = $wssrv->getData($this, $groupDataKey);
	    if(!isset($existingData)) $existingData = new stdClass();
	    // merges data and stores it back
	    $this->mergeObjects($existingData, $data);
	    $wssrv->storeData($this, $groupDataKey, $existingData);
	    return $existingData;
	}
	
	/**
	 * Gets the shared data from a group.
	 * FuncExp signature : <code>wsGetSharedDataFromGroup(groupName,accessKey=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupName: String. Group name, should be unique for whole wigii client
	 * - Arg(1) accessKey: String. Optional accessKey to enable operations on group.
	 * @return stdClass shared data or null if no shared data or group does not exist
	 */
	public function wsGetSharedDataFromGroup($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<1) throw new WigiiWebSocketServerException('wsGetSharedDataFromGroup takes at least one arguments: the group name', WigiiWebSocketServerException::INVALID_ARGUMENT);
	    $groupName = $this->evaluateArg($args[0]);
	    if($nArgs>1) $accessKey = $this->evaluateArg($args[1]);
	    else $accessKey = null;
	    $wssrv = $this->getWebSocketServer();
	    $p = $this->getPrincipal();
	    // checks that group exists
	    if(!$wssrv->groupExists($p,$groupName)) return null;
	    // joins group	    
	    $wssrv->joinGroup($p, $wssrv->getCrtConnectionId(), $groupName, $accessKey);
	    // gets existing data for group
	    $groupDataKey = 'group/'.$groupName;
	    return $wssrv->getData($this, $groupDataKey);
	}
	
	
	// Utils
	
	/**
	 * Copies entries of obj2 into obj1 (deep copy)
	 * @param stdClass $obj1 object which will receive entries of object 2
	 * @param stdClass $obj2 object which will have its entries copied to object 1
	 */
	private function mergeObjects($obj1,$obj2) {
	    if(!($obj1 instanceof stdClass)) throw new ServiceException('obj1 should be a non null object', ServiceException::INVALID_ARGUMENT);
	    if(!isset($obj2)) return $obj1;
	    if(!($obj2 instanceof stdClass)) throw new ServiceException('obj2 should be a valid stdClass instance', ServiceException::INVALID_ARGUMENT);
	    foreach($obj2 as $key=>$value) {
	        // if value is an object, then goes down recursively
	        if($value instanceof stdClass) {
	            if(!($obj1->{$key} instanceof stdClass)) $obj1->{$key} = new stdClass();
	            $this->mergeObjects($obj1->{$key}, $value);
	        }
	        // else copies value in object
	        else $obj1->{$key} = $value;
	    }
	    
	}
}