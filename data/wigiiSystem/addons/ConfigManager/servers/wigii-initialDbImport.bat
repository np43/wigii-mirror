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
REM This script executes the initial database import for a new client on a Wigii server, based on a dump located in the deployement package
REM Created by CWE on 18.04.2019
REM 

set USAGE=%0 wigii_client
set RETURNVALUE=0
SET PREVIOUS_PATH=%CD%
cd %~dp0
echo Changes code page to UTF-8
chcp 65001

set WIGII_CLIENT=%1
if "%WIGII_CLIENT%"=="" (echo Wigii ERROR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1004 & goto end)
if "%WIGII_HOST%"=="" (echo Wigii ERROR: WIGII_HOST is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_SERVER%"=="" (echo Wigii ERROR: WIGII_SERVER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_CERTIFICATE%"=="" (echo Wigii ERROR: WIGII_CERTIFICATE is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_USER%"=="" (echo Wigii ERROR: WIGII_USER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_PWD%"=="" (echo Wigii ERROR: WIGII_PWD is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_DBDUMP%"=="" (set WIGII_DBDUMP=wigii/db-dump & echo WIGII_DBDUMP is not defined. Sets it to %WIGII_DBDUMP%)

rem checks installation of WinSCP
if exist "%ProgramFiles%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles%\WinSCP\WinSCP.exe") else (
if exist "%ProgramFiles(x86)%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles(x86)%\WinSCP\WinSCP.exe") else (
	echo Wigii ERROR: WinScp is not installed & set RETURNVALUE=404 & goto end
))
rem checks presence of plink.exe
set PLINK=..\plink.exe
if not exist %PLINK% (echo Wigii ERROR: plink.exe has not been found & set RETURNVALUE=404 & goto end)

rem prepares Wigii server connexion string
set WIGII_CONNEXION=open ftps://%WIGII_USER%:%WIGII_PWD%@%WIGII_SERVER% -explicit -certificate="%WIGII_CERTIFICATE%"
set WINSCP_CMD=%WINSCP% /log=.\winscp.log /loglevel=0 /logsize=5*2M /command

for /f "tokens=1-3 delims=. " %%a in ('date /T') do (set TIMESTAMP=%%c%%b%%a)
for /f "tokens=1,2 delims=: " %%a in ('time /T') do (set TIMESTAMP=%TIMESTAMP%%%a%%b)

:checkProdPackage
echo Checks %WIGII_HOST%-prod folder structure
set WIGII_TARGET=%~dp0%WIGII_HOST%-prod
if not exist %WIGII_TARGET% (echo Wigii ERROR: %WIGII_HOST%-prod is not correctly setup. & set RETURNVALUE=1009 & goto end)
call %~dp0%WIGII_HOST%-setup.bat %WIGII_TARGET%
if "%WIGII_TARGET_WEB%"=="" (echo Wigii ERROR: WIGII_TARGET_WEB is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_TARGET_ENV%"=="" (echo Wigii ERROR: WIGII_TARGET_ENV is not defined. & set RETURNVALUE=1009 & goto end)

:getDbDump
echo Checks presence of a db dump in %WIGII_HOST%-prod
rem checks that there is only one sql file in db dump folder
set LOCAL_DBDUMP=0
for %%a in (%WIGII_TARGET_ENV%\db-dump\*.sql) do (set /A LOCAL_DBDUMP+=1)
if %LOCAL_DBDUMP% GTR 1 (echo Wigii ERROR: Several sql files found. Can only import one file. & set RETURNVALUE=1004 & goto end)
if %LOCAL_DBDUMP% == 0 (echo Wigii ERROR: No sql dump to import. & set RETURNVALUE=1004 & goto end)
for %%a in (%WIGII_TARGET_ENV%\db-dump\*.sql) do (set LOCAL_DBDUMP=%%a & set LOCAL_DBDUMP_FILENAME=%%~nxa)

:getDbConnectionInfo
echo Gets database connection information from %WIGII_HOST%-prod
for /F "tokens=3 delims=,) " %%a in ('findstr /C:"DB_HOST" %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php') do (set WIGII_DB_HOST=%%a)
for /F "tokens=3 delims=,) " %%a in ('findstr /C:"DB_USER" %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php') do (set WIGII_DB_USER=%%a)
for /F "tokens=3 delims=,) " %%a in ('findstr /C:"DB_PWD" %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php') do (set WIGII_DB_PWD=%%a)
for /F "tokens=3 delims=,) " %%a in ('findstr /C:"DB_NAME" %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php') do (set WIGII_DB_NAME=%%~a)
if "%WIGII_DB_HOST%"=="" (echo Wigii ERROR: WIGII_DB_HOST is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_DB_USER%"=="" (echo Wigii ERROR: WIGII_DB_USER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_DB_PWD%"=="" (echo Wigii ERROR: WIGII_DB_PWD is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_DB_NAME%"=="" (echo Wigii ERROR: WIGII_DB_NAME is not defined. & set RETURNVALUE=1009 & goto end)

:confirmDbImport
echo Are you OK to do the initial import of database %WIGII_DB_NAME% on server %WIGII_DB_HOST% using db dump
echo %LOCAL_DBDUMP%
set /P WIGII_OPTION_DBIMPORT="(y/n) ? "
set WIGII_OPTION_DBIMPORT=%WIGII_OPTION_DBIMPORT:y=1%
if not "%WIGII_OPTION_DBIMPORT%"=="1" (echo Wigii ERROR: Initial db import of %WIGII_DB_NAME% canceled by user. & set RETURNVALUE=1010 & goto end)

for /f "tokens=1-3 delims=. " %%a in ('date /T') do (set SHCMD_CMDFILE=%WIGII_DB_NAME%_%%c%%b%%a.sh)
SET MYSQL_CMD=mysql --host=%WIGII_DB_HOST% --user=%WIGII_DB_USER% --password=%WIGII_DB_PWD% -e 

:convertsDbToUTF8
echo Converts db %WIGII_DB_NAME% to utf8mb4_unicode_ci
copy NUL %SHCMD_CMDFILE%
echo %MYSQL_CMD% "charset utf8mb4;set names utf8mb4;use %WIGII_DB_NAME%;ALTER DATABASE %WIGII_DB_NAME% CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;" >> %SHCMD_CMDFILE%
rem uploads sql command to wigii server
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "cd %WIGII_DBDUMP%" ^
 "put -transfer=ascii %SHCMD_CMDFILE%" ^
 "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError
%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% chmod 764 ~/%WIGII_DBDUMP%/%SHCMD_CMDFILE%
%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% ~/%WIGII_DBDUMP%/%SHCMD_CMDFILE%
if %ERRORLEVEL% neq 0 goto mySqlError

:doInitialDbImport
echo Uploads db import sql file to %WIGII_HOST%
copy NUL %SHCMD_CMDFILE%
echo %MYSQL_CMD% "use %WIGII_DB_NAME%;set names utf8mb4 collate utf8mb4_unicode_ci;source ~/%WIGII_DBDUMP%/%LOCAL_DBDUMP_FILENAME%;" >> %SHCMD_CMDFILE%
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "cd %WIGII_DBDUMP%" ^
 "put -transfer=binary %LOCAL_DBDUMP%" ^
 "put -transfer=ascii %SHCMD_CMDFILE%" ^
 "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError
%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% chmod 764 ~/%WIGII_DBDUMP%/%SHCMD_CMDFILE%
%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% ~/%WIGII_DBDUMP%/%SHCMD_CMDFILE%
if %ERRORLEVEL% neq 0 goto mySqlError

:resetSuperAdminPwd
echo Prepares wigii superadmin user
set WIGII_SUPERADMIN=%WIGII_CLIENT%.admin
rem checks if superadmin user is present in sql dump
for /f "tokens=3 delims=:" %%a in ('find /C "%WIGII_SUPERADMIN%" %LOCAL_DBDUMP%') do (
	if %%a GTR 0 (goto endAskSuperAdminUser) else (goto askSuperAminUser)
)
:askSuperAminUser
set /P WIGII_SUPERADMIN="Enter %WIGII_CLIENT% super admin user name: "
if WIGII_SUPERADMIN="Enter %WIGII_CLIENT% super admin user name: ")
if "%WIGII_SUPERADMIN%"=="" (echo Wigii ERROR: WIGII_SUPERADMIN is not defined. & set RETURNVALUE=1004 & goto end)
:endAskSuperAdminUser
if "%WIGII_CLI%"=="" goto askForSuperAdminPwd
rem if wigii cli is installed, generates a password
:genSuperAdminPwd
set WIGII_FX=genPassword()
for /F "delims=" %%a in ('%WIGII_CLI%\wigii_cli.bat -shell -c NoClient fx "%WIGII_FX%"') do (set WIGII_SUPERADMIN_PWD=%%a)
rem else asks a password to user
:askForSuperAdminPwd
if "%WIGII_SUPERADMIN_PWD%"=="" (set /P WIGII_SUPERADMIN_PWD="Enter %WIGII_SUPERADMIN% new password: ")
if "%WIGII_SUPERADMIN_PWD%"=="" (set /P WIGII_SUPERADMIN_PWD="Enter %WIGII_SUPERADMIN% new password: ")
if "%WIGII_SUPERADMIN_PWD%"=="" (echo Wigii ERROR: WIGII_SUPERADMIN_PWD is not defined. & set RETURNVALUE=1004 & goto end)
:updateSuperAdminPwd
echo Resets password of %WIGII_SUPERADMIN%.
rem creates update sql command
for /f "tokens=1-3 delims=. " %%a in ('date /T') do (set MYSQL_CMDFILE=%WIGII_DB_NAME%_%%c%%b%%a.sql)
copy NUL %MYSQL_CMDFILE%
echo update `Users` SET  >> %MYSQL_CMDFILE%
echo 	`password` = 						md5("%WIGII_SUPERADMIN_PWD%"),  >> %MYSQL_CMDFILE%
echo 	`passwordLength` = 					char_length("%WIGII_SUPERADMIN_PWD%")  >> %MYSQL_CMDFILE%
echo where `username` = "%WIGII_SUPERADMIN%"  >> %MYSQL_CMDFILE%
echo ;  >> %MYSQL_CMDFILE%
rem creates script to run sql update command
copy NUL %SHCMD_CMDFILE%
echo %MYSQL_CMD% "charset utf8mb4;set names utf8mb4;use %WIGII_DB_NAME%;source ~/%WIGII_DBDUMP%/%MYSQL_CMDFILE%;" >> %SHCMD_CMDFILE%
rem uploads sql command to wigii server
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "cd %WIGII_DBDUMP%" ^
 "put -transfer=binary %MYSQL_CMDFILE%" ^
 "put -transfer=ascii %SHCMD_CMDFILE%" ^
 "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError
%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% chmod 764 ~/%WIGII_DBDUMP%/%SHCMD_CMDFILE%
%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% ~/%WIGII_DBDUMP%/%SHCMD_CMDFILE%
if %ERRORLEVEL% neq 0 goto mySqlError
:endUpdateSuperAdminPwd

:cleanupSqlScripts
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "cd %WIGII_DBDUMP%" ^
 "rm %LOCAL_DBDUMP_FILENAME% %SHCMD_CMDFILE% %MYSQL_CMDFILE%" ^
 "close" ^
 "exit"

echo _________________________________________________
echo Done. Database %WIGII_DB_NAME% for new client %WIGII_CLIENT% is ready for go live.
echo Do not forget to adapt the .htaccess and index.php files with the %WIGII_CLIENT% url before pushing package live. 
echo To connect to Wigii web interface use %WIGII_SUPERADMIN% / %WIGII_SUPERADMIN_PWD%
goto end
:winScpError
set RETURNVALUE=10303
echo Erreur de communication WinScp
goto end
:mySqlError
set RETURNVALUE=2501
echo Erreur de MySql
goto end
:end
REM clears all variables and exits with return value
set LOCAL_HTACCESS=
set WIGII_HOST=
set WIGII_HOST_INDEX=
set WIGII_HOST_HTACCESS=
set WIGII_TARGET_HTACCESS=
set WIGII_SERVER=
set WIGII_CERTIFICATE=
set WIGII_USER=
set WIGII_PWD=
set WIGII_DB=
set WIGII_DB_HOST=
set WIGII_DB_USER=
set WIGII_DB_PWD=
set WIGII_SUPERADMIN=
set WIGII_SUPERADMIN_PWD=
set WIGII_DBDUMP=
set LOCAL_DBDUMP=
set LOCAL_DBDUMP_FILENAME=
set WIGII_OPTION_DBIMPORT=
set SHCMD=
if not "%SHCMD_CMDFILE%"=="" del /Q %SHCMD_CMDFILE%
SET SHCMD_CMDFILE=
if not "%MYSQL_CMDFILE%"=="" del /Q %MYSQL_CMDFILE%
SET MYSQL_CMDFILE=
set PLINK=
set WINSCP=
set WINSCP_CMD=
set MYSQL_CMD=
SET WIGII_FX=
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%