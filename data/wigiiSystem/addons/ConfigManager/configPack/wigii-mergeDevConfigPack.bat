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
REM This script merges Wigii dev config pack folder with config pack trunk stored into svn
REM Created by CWE on 24.04.2019
REM 

set CONFIGPACK_OU=%1
set USAGE=%0 ConfigPack_OrgUnit
set RETURNVALUE=0
echo Changes code page to UTF-8
chcp 65001

if "%CONFIGPACK_OU%"=="" (echo Wigii ERROR: Config pack organisational unit is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
if "%LOCAL_CONFIGPACK%"=="" (set LOCAL_CONFIGPACK=%~dp0)
IF %LOCAL_CONFIGPACK:~-1%==\ SET LOCAL_CONFIGPACK=%LOCAL_CONFIGPACK:~0,-1%

if "%WIGII_ENV%"=="" (echo Wigii ERROR: WIGII_ENV is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
IF %WIGII_ENV:~-1%==\ SET WIGII_ENV=%WIGII_ENV:~0,-1%

rem asserts working folder does not commit to GitHub Wigii
for /f "tokens=1 delims=" %%a in ('svn info %LOCAL_CONFIGPACK% ^| find /C "https://github.com/wigii/wigii"') do (
	if %%a GTR 0 (echo Wigii ERROR: Cannot commit %CONFIGPACK_OU% information to GitHub/Wigii & set RETURNVALUE=405 & goto end)
)

rem checks installation of WinMerge
if exist "%ProgramFiles%\WinMerge\WinMergeU.exe" (set WINMERGE="%ProgramFiles%\WinMerge\WinMergeU.exe") else (
if exist "%ProgramFiles(x86)%\WinMerge\WinMergeU.exe" (set WINMERGE="%ProgramFiles(x86)%\WinMerge\WinMergeU.exe") else (
	echo Wigii ERROR: WinMerge is not installed & set RETURNVALUE=404 & goto end
))

:svnUpdateTrunkConfigPack
rem creates Config pack organisational unit folder
if not exist %LOCAL_CONFIGPACK%\%CONFIGPACK_OU% (mkdir %LOCAL_CONFIGPACK%\%CONFIGPACK_OU% & svn add %LOCAL_CONFIGPACK%\%CONFIGPACK_OU% & svn commit -m "Create %CONFIGPACK_OU% folder" %LOCAL_CONFIGPACK%\%CONFIGPACK_OU%)

rem svn updates Config pack organisational unit folder
echo SVN updates %CONFIGPACK_OU% config pack folder
svn update %LOCAL_CONFIGPACK%\%CONFIGPACK_OU%

:winMergeDevConfigPack
if not exist %LOCAL_CONFIGPACK%\%CONFIGPACK_OU% (mkdir %LOCAL_CONFIGPACK%\%CONFIGPACK_OU%)
rem opens a winmerge diff window between dev config pack folder and config pack trunk
echo Merges %CONFIGPACK_OU% config pack files
%WINMERGE% /e /x /u /fl /dl "dev config pack   %CONFIGPACK_OU%     %WIGII_ENV%" /dr "%CONFIGPACK_OU%     config pack trunk" %WIGII_ENV%\data\wigiiSystem\configPack\%CONFIGPACK_OU% %LOCAL_CONFIGPACK%\%CONFIGPACK_OU%
goto end

:end
REM clears all variables and exits with return value
set CONFIGPACK_OU=
set LOCAL_CONFIGPACK=
exit /b %RETURNVALUE%