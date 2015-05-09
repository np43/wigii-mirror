SET PATH=%PATH%;C:\wamp\bin\php\php5.3.13

rem .\ApiGen-2.8.0-standalone\apigen --source-code yes --source ..\impl\ElementEvaluator.php --source ..\impl\RecordEvaluator.php --source ..\WigiiBPL.php --source ..\ServiceProvider.php --source ..\ClientAdminService.php --source ..\ModuleAdminService.php --source ..\UserAdminService.php --source ..\GroupAdminService.php --source ..\WigiiNamespaceAdminService.php --source ..\TechnicalServiceProvider.php --source ..\DebugLogger.php --source ..\ElementPolicyEvaluator.php --source ..\AuthenticationService.php --source ..\AuthorizationService.php --source ..\ConfigController.php --source ..\DataFlowActivity.php --source ..\DataFlowDumpable.php --source ..\InputDataFlow.php --source ..\DataFlowService.php   --destination ..\..\..\..\..\www\src-doc\api --title "Wigii System - API"
rem .\ApiGen-2.8.0-standalone\apigen --source-code yes --source ..\exceptions --destination ..\..\..\..\..\www\src-doc\exceptions --title "Wigii System - Exceptions"
rem .\ApiGen-2.8.0-standalone\apigen --source-code yes --source ..\..\model --destination ..\..\..\..\..\www\src-doc\model --title "Wigii System - Models"
rem .\ApiGen-2.8.0-standalone\apigen --source-code yes --source ..\..\wpl --destination ..\..\..\..\..\www\src-doc\wpl --title "Wigii System - WPL"
.\ApiGen-2.8.0-standalone\apigen --source-code yes --source .\functions.php --source ..\..\wfl --destination ..\..\..\..\..\www\src-doc\wfl --title "Wigii System - WFL"
rem .\ApiGen-2.8.0-standalone\apigen --source-code yes --source ..\..\..\addons\Geometry --destination ..\..\..\..\..\www\src-doc\addons\geometry --title "Wigii System - Geometry addon"

