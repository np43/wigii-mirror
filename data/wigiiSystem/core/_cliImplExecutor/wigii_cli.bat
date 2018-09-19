SET WIGII_CLI_PHP_ENGINE=C:\wamp\bin\php\php5.6.35\php
SET PREVIOUS_PATH=%CD%
cd %~dp0
%WIGII_CLI_PHP_ENGINE% -c .\php.ini -f main.php -- %* > out.log 2> err.log
cd %PREVIOUS_PATH%