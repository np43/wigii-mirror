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

use Guzzle\Http\Client;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;

class Debug_Guzzle extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_Guzzle");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_Guzzle','usage of Guzzle library (http://guzzlephp.org)');
	}
	public function run()
	{		
		$client = new Client('http://localhost:8080');
		$client->addSubscriber(new CookiePlugin(new ArrayCookieJar()));
		$request = $client->get('/jasperserver/rest/login?j_username=joeuser&j_password=joeuser');
		$response = $request->send();
		$this->d()->write($response->getStatusCode());
		
		$request = $client->get('/jasperserver/rest_v2/reports/reports/samples/StandardChartsEyeCandyReport.html');
		$response = $request->send();
		$this->d()->write($response->getStatusCode());
		$this->d()->write($response->getContentLength());		
	}
}
TestRunner::test(new Debug_Guzzle());