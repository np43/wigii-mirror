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
REM This script synchronizes client configuration trunk with client production configuration folder
REM Created by CWE on 16.04.2019
REM 

set WIGII_CLIENT=%1
set USAGE=%0 wigii_client
set RETURNVALUE=0
SET PREVIOUS_PATH=%CD%
cd %~dp0
echo Changes code page to UTF-8
chcp 65001

if "%WIGII_CLIENT%"=="" (echo Wigii ERROR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
if "%WIGII_HOST%"=="" (echo Wigii ERROR: WIGII_HOST is not defined. & set RETURNVALUE=1009 & goto end)
if "%LOCAL_CONFIGS%"=="" (set LOCAL_CONFIGS=%~dp0)
IF %LOCAL_CONFIGS:~-1%==\ SET LOCAL_CONFIGS=%LOCAL_CONFIGS:~0,-1%

rem checks installation of WinMerge
if exist "%ProgramFiles%\WinMerge\WinMergeU.exe" (set WINMERGE="%ProgramFiles%\WinMerge\WinMergeU.exe") else (
if exist "%ProgramFiles(x86)%\WinMerge\WinMergeU.exe" (set WINMERGE="%ProgramFiles(x86)%\WinMerge\WinMergeU.exe") else (
	echo Wigii ERROR: WinMerge is not installed & set RETURNVALUE=404 & goto end
))

rem asserts working folder does not commit to GitHub Wigii
for /f "tokens=1 delims=" %%a in ('svn info %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod ^| find /C "https://github.com/wigii/wigii"') do (
	if %%a GTR 0 (echo Wigii ERROR: Cannot commit %WIGII_CLIENT% information to GitHub/Wigii & set RETURNVALUE=405 & goto end)
)

:svnUpdateTrunkConfig
rem creates Wigii client config folder
if not exist %LOCAL_CONFIGS%\%WIGII_CLIENT% (mkdir %LOCAL_CONFIGS%\%WIGII_CLIENT% & svn add %LOCAL_CONFIGS%\%WIGII_CLIENT% & svn commit -m "Create %WIGII_CLIENT% folder" %LOCAL_CONFIGS%\%WIGII_CLIENT%)

rem svn updates Wigii client config folder
echo SVN updates %WIGII_CLIENT% config folder
svn update %LOCAL_CONFIGS%\%WIGII_CLIENT%

:versionProdConfig
call %WIGII_HOST%-versionProdConfig.bat %WIGII_CLIENT%

:winMergeTrunkConfig
if not exist %LOCAL_CONFIGS%\%WIGII_CLIENT% (mkdir %LOCAL_CONFIGS%\%WIGII_CLIENT%)
rem opens a winmerge diff window between dev config folder and config trunk
echo Synchronizes %WIGII_CLIENT% trunk config files with prod
%WINMERGE% /e /x /u /fl /dl "%WIGII_CLIENT%     configs trunk" /dr "%WIGII_CLIENT%     PROD" %LOCAL_CONFIGS%\%WIGII_CLIENT% %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod
goto end

:end
REM clears all variables and exits with return value
set WIGII_CLIENT=
set LOCAL_CONFIGS=
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%