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
 * SystemConsoleService base implementation
 * Created on 24 juil. 09 by LWR
 * Modified by CWE on 01.12.2015 to support JS notifications
 */
class SystemConsoleServiceImpl implements SystemConsoleService {
	private $_debugLogger;
	private $_executionSink;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("SystemConsoleServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("SystemConsoleServiceImpl");
		}
		return $this->_executionSink;
	}

	//message management
	protected $messages;
	public function getMessages() {
		return $this->messages;
	}
	protected $startTime;
	protected $typeList; //will contain the list of the different type message
	//JSCode management
	protected $JSCode;
	protected $JSNotif;
	
	private $lastTime; //has the last time, to be able to manage the diff between operations
	private $operationsStackTime; //at each START OPERATION the time is pushed. On each END Operation the stack is poped
	private $operationsStackName; //at each START OPERATION the operation name is pushed. On each END Operation the stack is poped
	public function __construct() {
		$this->startTime = $this->getMicroTime();
		$this->lastTime = $this->startTime;
		$this->operationsStackTime = array ();
		$this->operationsStackName = array ();
	}

	protected function getMicroTime() {
		return microtime(true);

		list ($usec, $sec) = explode(" ", microtime());
		return ((float) $usec + (float) $sec);
	}

	protected function shouldStoreMessage($classname) {
		return true;
	}

	/**
	 * this will store a message in the SystemConsole
	 */
	public function storeMessage($type, $attachedClass, $message) {
		if (!isset ($this->messages)) {
			$this->messages = array ();
			$this->typeList = array ();
		}

		if(!$this->shouldStoreMessage($attachedClass)) return;

		$microTime = $this->getMicroTime();

		$opDiff = null;
		$back = debug_backtrace();
		$level = 3;
		$backTrace = $back[$level]["class"].$back[$level]["type"].$back[$level]["function"] . " ".basename($back[$level-1]["file"])."[" . $back[$level-1]["line"]."]";

		if (false !== strpos($message, "START OPERATION")) {
			array_push($this->operationsStackTime, $microTime);
			$stackName = $back[$level]["class"].$back[$level]["type"].$back[$level]["function"];
			array_push($this->operationsStackName, $stackName);
			//do not show operations stack as this makes it less redable
//			$message = $message . "\n(" . implode(" >> ", $this->operationsStackName) . ")";
			//add a backTrace to max 4 levels
			$maxBackTrace = count($back);
			$maxBackTrace = min(12, $maxBackTrace);
			$backTrace .= "\n--Trace--";
			for($level=2; $level<$maxBackTrace; $level++){
				$backTrace .= "\n".basename($back[$level]["file"])."[" . $back[$level]["line"]."]".$back[$level]["type"].$back[$level]["function"];
			}
		} else if (false !== strpos($message, "END OPERATION")) {
			$opDiff = array_pop($this->operationsStackTime);
			array_pop($this->operationsStackName);
			//do not show operations stack as this makes it less redable
//			$message = $message . " (" . implode(" >> ", $this->operationsStackName) . ")";
		}

		$this->typeList[$type] = $this->typeList[$type] + 1;
		$this->messages[] = array (
			"type" => $type,
			"attachedClass" => $attachedClass,
			"message" => $message,
			"backTrace" => $backTrace,
			"absoluteTime" => $microTime,
			"diffTime" => $microTime - $this->startTime,
			"diffTimeRel" => $microTime - $this->lastTime,
			"diffTimeOp" => ($opDiff ? ($microTime - $opDiff
		) : null),);
		$this->lastTime = $microTime;

		//fput($type.":".$attachedClass.":\n".$message);
	}

	public function flushMessages() {
		throw new SystemConsoleServiceException("Flush message in the SystemConsoleService can not be done, because need of an implementation. Use Web Impl for i.e.", SystemConsoleServiceException :: UNSUPPORTED_OPERATION);
	}

	public function addJsCode($code, $currentRequestId = null) {
		if (!isset ($this->JSCode))
			$this->JSCode = array (); //"";
		if (!isset ($this->JSCode[$currentRequestId]))
			$this->JSCode[$currentRequestId] = "";

		if ($code == "##revealJsCode##") {
			eput($this->JSCode);
		} else {
			$this->JSCode[$currentRequestId] .= $code . "; ";
		}
	}
	public function flushJSCode() {
		throw new SystemConsoleServiceException("Flush JSCode in the SystemConsoleService can not be done, because need of an implementation. Use Web Impl for i.e.", SystemConsoleServiceException :: UNSUPPORTED_OPERATION);
	}
	
	public function addJsNotif($target,$type,$url,$options=null) {
		if(!isset($this->JSNotif)) $this->JSNotif = array();
		if(empty($url)) throw new SystemConsoleServiceException('notification callback url cannot be null');
		if(!isset($target)) $target = 'searchBar';
		if(!isset($type)) $type = 'info';
		
		// creates JS notification object
		$jsNotif = array();
		// fills existing options
		if(isset($options) && !$options->isEmpty()) {
			foreach($options->getIterator() as $k=>$v) {
				$jsNotif[$k]=$v;
			}			
		}
		// adds required values
		$jsNotif['target'] = $target;
		$jsNotif['type'] = $type;
		$jsNotif['url'] = $url;
		// stores the object in the buffer
		$this->JSNotif[] = (object)$jsNotif;
	}
	
	public function flushJSNotif() {
		throw new SystemConsoleServiceException("Flush JSNotif in the SystemConsoleService can not be done, because need of an implementation. Use Web Impl for i.e.", SystemConsoleServiceException :: UNSUPPORTED_OPERATION);
	}
}