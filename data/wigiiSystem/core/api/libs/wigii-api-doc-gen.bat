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

rem set PATH=C:\wamp\bin\php\php5.6.35;%PATH%
rem set PATH=C:\wamp\bin\php\php7.1.26;%PATH%
rem set PATH=C:\wamp\bin\php\php7.2.14;%PATH%
set PATH=C:\wamp\bin\php\php7.3.1;%PATH%
set DOC_CODEBASE=C:\Users\weber\Documents\dev\php-src\Wigii_git
set DOC_OUTPUT=C:\Users\weber\Documents\dev\wigii-doc
rmdir %DOC_OUTPUT% /s /q
mkdir %DOC_OUTPUT%
mkdir %DOC_OUTPUT%\api
mkdir %DOC_OUTPUT%\wfl
mkdir %DOC_OUTPUT%\wpl
mkdir %DOC_OUTPUT%\model
mkdir %DOC_OUTPUT%\exceptions
rem mkdir %DOC_OUTPUT%\addons
rem mkdir %DOC_OUTPUT%\addons\reporting
rem mkdir %DOC_OUTPUT%\addons\geometry
rem mkdir %DOC_OUTPUT%\addons\campaignmonitor
rem mkdir %DOC_OUTPUT%\addons\codeprofiler
rem mkdir %DOC_OUTPUT%\addons\medidata

call .\ApiGen-2.8.0-standalone\apigen --source ..\..\api\impl\RecordEvaluator.php --source ..\..\api\impl\ElementEvaluator.php --source ..\..\wfl\WigiiFL.php --source ..\..\wfl\FuncExpVMStdFL.php --source ..\..\wfl\PHPStdFL.php --source ..\..\wfl\FuncExpBuilder.php --source ..\..\..\configs\company\CompanyElementEvaluator.php  --exclude "..\libs\*" --destination %DOC_OUTPUT%\wfl --title "Wigii Functional Expressions Reference" --groups "packages" --autocomplete "methods" --access-levels "public" --internal No --php No --tree No --update-check No
call .\ApiGen-2.8.0-standalone\apigen --source ..\..\wpl --exclude "..\libs\*" --destination %DOC_OUTPUT%\wpl --title "Wigii DataFlows Reference" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No
call .\ApiGen-2.8.0-standalone\apigen --source ..\..\model --exclude "..\libs\*" --destination %DOC_OUTPUT%\model --title "Wigii System - Models" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No
call .\ApiGen-2.8.0-standalone\apigen --source ..\..\api\exceptions --exclude "..\libs\*" --destination %DOC_OUTPUT%\exceptions --title "Wigii System - Exceptions" --groups "packages" --autocomplete "classes,methods,classconstants" --access-levels "public" --internal No --php No --tree Yes --update-check No
call .\ApiGen-2.8.0-standalone\apigen --source "..\..\api" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\api\technical\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\api\libs\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\api\impl\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\api\exceptions\*" --destination %DOC_OUTPUT%\api --title "Wigii System - API" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No
rem .\ApiGen-2.8.0-standalone\apigen --source "..\..\_webImplExecutor\libs" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\form\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\htmlpurifier-4.5.0-lite\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\PHPExcel\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\securimage\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\Twig\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\Zend\*" --destination %DOC_OUTPUT% --title "Wigii System - UI" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No --sourceCode No
rem .\ApiGen-2.8.0-standalone\apigen --source "..\..\_webImplExecutor\libs\form" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\htmlpurifier-4.5.0-lite\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\PHPExcel\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\securimage\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\Twig\*" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\libs\Zend\*" --destination %DOC_OUTPUT% --title "Wigii System - UI - Forms" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No
rem .\ApiGen-2.8.0-standalone\apigen --source "..\..\_webImplExecutor\templates" --exclude "%DOC_CODEBASE%\data\wigiiSystem\core\_webImplExecutor\templates\twig\*"  --destination %DOC_OUTPUT% --title "Wigii System - UI - Templates" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No

rem .\ApiGen-2.8.0-standalone\apigen --source ..\..\..\addons\Reporting --exclude "..\libs\*" --destination %DOC_OUTPUT%\addons\reporting --title "Wigii System - Reporting addon" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No
rem .\ApiGen-2.8.0-standalone\apigen --source ..\..\..\addons\Geometry --exclude "..\libs\*" --destination %DOC_OUTPUT%\addons\geometry --title "Wigii System - Geometry addon" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No
rem .\ApiGen-2.8.0-standalone\apigen --source ..\..\..\addons\CampaignMonitor --exclude "..\libs\*" --destination %DOC_OUTPUT%\addons\campaignmonitor --title "Wigii System - Campaign Monitor addon" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No
rem .\ApiGen-2.8.0-standalone\apigen --source ..\..\..\addons\CodeProfiler --exclude "..\libs\*" --destination %DOC_OUTPUT%\addons\codeprofiler --title "Wigii System - Code Profiler addon" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No
rem .\ApiGen-2.8.0-standalone\apigen --source ..\..\..\addons\Medidata --exclude "..\libs\*" --destination %DOC_OUTPUT%\addons\medidata --title "Wigii System - Medidata addon" --groups "packages" --autocomplete "classes,methods" --access-levels "public" --internal No --php No --tree Yes --update-check No