@echo off
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

SET PREVIOUS_PATH=%CD%
cd %~dp0
if "%WIGII_PHP_ENV%"=="" (set WIGII_PHP_ENV=C:\wamp\bin\php\php7.3.1)
SET WIGII_CLI_PHP_ENGINE=%WIGII_PHP_ENV%\php.exe
IF NOT exist %WIGII_CLI_PHP_ENGINE% (echo Wigii ERREUR: %WIGII_CLI_PHP_ENGINE% has not been found & set RETURNVALUE=404 & goto end)

set WIGII_CLI_PHP_INI=.\php.ini
if exist %WIGII_PHP_ENV%\php.ini (set WIGII_CLI_PHP_INI=%WIGII_PHP_ENV%\php.ini)

set OPENSSL_CONF=%WIGII_PHP_ENV%\extras\ssl\openssl.cnf

if "%1"=="-shell" (%WIGII_CLI_PHP_ENGINE% -c %WIGII_CLI_PHP_INI% -f main.php -- %*) else (%WIGII_CLI_PHP_ENGINE% -c %WIGII_CLI_PHP_INI% -f main.php -- %* > out.log 2> err.log)
set RETURNVALUE=%ERRORLEVEL%
:end
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%