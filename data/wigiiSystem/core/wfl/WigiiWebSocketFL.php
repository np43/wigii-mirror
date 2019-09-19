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
 *  @copyright  Copyright (c) 2016  Wigii.org
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
	 * Stops Wigii web socket server
	 * FuncExp signature : <code>wssrvStop()</code>
	 */
	public function wssrvStop($args) {
		return $this->getWebSocketServer()->stop($this->getPrincipal());
	}	
}