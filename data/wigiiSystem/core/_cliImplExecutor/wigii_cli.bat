REM
REM This file is part of Wigii (R) software.
REM Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
REM 
REM Wigii is free software: you can redistribute it and/or modify it 
REM under the terms of the GNU General Public License as published by
REM the Free Software Foundation, either version 3 of the License, 
REM or (at your option) any later version.
REM 
REM Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
REM without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
REM See the GNU General Public License for more details.
REM
REM A copy of the GNU General Public License is available in the Readme folder of the source code.  
REM If not, see <http://www.gnu.org/licenses/>.
REM
REM @copyright  Copyright (c) 2019  Wigii.org
REM @author     <http://www.wigii.org/system>      Wigii.org 
REM @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
REM @license    <http://www.gnu.org/licenses/>     GNU General Public License
REM

REM SET WIGII_CLI_PHP_ENGINE=C:\wamp\bin\php\php5.6.35\php
REM SET WIGII_CLI_PHP_ENGINE=C:\wamp\bin\php\php7.1.26\php
REM SET WIGII_CLI_PHP_ENGINE=C:\wamp\bin\php\php7.2.14\php
SET WIGII_CLI_PHP_ENGINE=C:\wamp\bin\php\php7.3.1\php
SET PREVIOUS_PATH=%CD%
cd %~dp0
%WIGII_CLI_PHP_ENGINE% -c .\php.ini -f main.php -- %* > out.log 2> err.log
cd %PREVIOUS_PATH%