REM SET WIGII_CLI_PHP_ENGINE=C:\wamp\bin\php\php5.6.35\php
REM SET WIGII_CLI_PHP_ENGINE=C:\wamp\bin\php\php7.1.26\php
REM SET WIGII_CLI_PHP_ENGINE=C:\wamp\bin\php\php7.2.14\php
SET WIGII_CLI_PHP_ENGINE=C:\wamp\bin\php\php7.3.1\php
SET PREVIOUS_PATH=%CD%
cd %~dp0
%WIGII_CLI_PHP_ENGINE% -c .\php.ini -f main.php -- %* > out.log 2> err.log
cd %PREVIOUS_PATH%