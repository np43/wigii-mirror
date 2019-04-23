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

REM
REM Creates a new client in dev environment
REM Created by CWE on 16.04.2019
REM
set RETURNVALUE=0
echo Changes code page to UTF-8
chcp 65001
if "%WIGII_CLI%"=="" (echo Wigii ERROR: Wigii dev CLI is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
call %WIGII_CLI%\wigii_createClient.bat %*
SET RETURNVALUE=%ERRORLEVEL%
goto end
:end
REM clears all variables and exits with return value
exit /b %RETURNVALUE%