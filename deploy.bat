@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

echo ============================================================
echo  World Cup Data Portal - Windows deployment
echo ============================================================
echo.
echo WARNING: restore mode rebuilds the worldcup database tables.
echo Existing data requires an explicit RESET confirmation.
echo.

set "PHP_EXE="
for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%P"
if not defined PHP_EXE if exist "D:\vscode101\tools\php-8.2.36\php.exe" set "PHP_EXE=D:\vscode101\tools\php-8.2.36\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "D:\xampp\php\php.exe" set "PHP_EXE=D:\xampp\php\php.exe"
if not defined PHP_EXE (
    echo [ERROR] php.exe was not found. Install PHP 8.2 or add it to PATH.
    pause
    exit /b 1
)

set "MYSQL_EXE="
for /f "delims=" %%M in ('where mysql 2^>nul') do if not defined MYSQL_EXE set "MYSQL_EXE=%%M"
if not defined MYSQL_EXE if exist "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" set "MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
if not defined MYSQL_EXE if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"
if not defined MYSQL_EXE if exist "D:\xampp\mysql\bin\mysql.exe" set "MYSQL_EXE=D:\xampp\mysql\bin\mysql.exe"
if not defined MYSQL_EXE (
    echo [ERROR] mysql.exe was not found. Install MySQL or start XAMPP.
    pause
    exit /b 1
)

echo [1/7] PHP: %PHP_EXE%
"%PHP_EXE%" -r "exit(extension_loaded('pdo_mysql') && extension_loaded('intl') && extension_loaded('mbstring') && extension_loaded('openssl') ? 0 : 1);"
if errorlevel 1 (
    echo [ERROR] PHP extensions pdo_mysql, intl, mbstring and openssl are required.
    pause
    exit /b 1
)

if /I "%~1"=="--check" (
    echo [CHECK] PHP=%PHP_EXE%
    echo [CHECK] MySQL=%MYSQL_EXE%
    "%PHP_EXE%" tools/apply-db-config.php --check
    if errorlevel 1 exit /b 1
    echo [CHECK] deploy.bat dependencies are available.
    exit /b 0
)

if not exist "frontend\config\main-local.php" (
    echo [2/7] Initialize Yii Development configuration
    "%PHP_EXE%" init --env=Development --overwrite=All
    if errorlevel 1 goto :failed
) else (
    echo [2/7] Local configuration already exists, skip init
)

set /p DB_HOST=MySQL host [127.0.0.1]:
if not defined DB_HOST set "DB_HOST=127.0.0.1"
set /p DB_PORT=MySQL port [3306]:
if not defined DB_PORT set "DB_PORT=3306"
set /p DB_USER=MySQL user [root]:
if not defined DB_USER set "DB_USER=root"

for /f "usebackq delims=" %%P in (`powershell -NoProfile -Command "$s=Read-Host -AsSecureString 'MySQL password';$b=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($s);try{[Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)}finally{[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b)}"`) do set "MYSQL_PWD=%%P"

set "WC_DB_HOST=%DB_HOST%"
set "WC_DB_PORT=%DB_PORT%"
set "WC_DB_NAME=worldcup"
set "WC_DB_USER=%DB_USER%"
set "WC_DB_PASSWORD=%MYSQL_PWD%"

echo [3/7] Check MySQL connection
"%MYSQL_EXE%" --batch --skip-column-names --connect-timeout=8 -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" -e "SELECT 1;" >nul
if errorlevel 1 (
    echo [ERROR] MySQL connection failed. Check the service, host and password.
    goto :failed
)

for /f %%C in ('"%MYSQL_EXE%" --batch --skip-column-names -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='worldcup';"') do set "TABLE_COUNT=%%C"
if not defined TABLE_COUNT set "TABLE_COUNT=0"
if not "%TABLE_COUNT%"=="0" (
    echo.
    echo [WARNING] worldcup already has %TABLE_COUNT% tables. Restore will rebuild them.
    set /p RESET_CONFIRM=Type RESET to continue:
    if /I not "%RESET_CONFIRM%"=="RESET" (
        echo Deployment cancelled.
        goto :end
    )
)

echo [4/7] Create database and import schema
"%MYSQL_EXE%" --default-character-set=utf8mb4 -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" --execute="SOURCE data/install.sql"
if errorlevel 1 goto :failed

echo [5/7] Import World Cup data
"%MYSQL_EXE%" --default-character-set=utf8mb4 -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" worldcup --execute="SOURCE data/real_worldcup_data.sql"
if errorlevel 1 goto :failed
"%MYSQL_EXE%" --default-character-set=utf8mb4 -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" worldcup --execute="SOURCE data/real_worldcup_2026.sql"
if errorlevel 1 goto :failed
"%MYSQL_EXE%" --default-character-set=utf8mb4 -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" worldcup --execute="SOURCE data/module1_team_data.sql"
if errorlevel 1 goto :failed

echo [6/7] Write local database config and run checks
"%PHP_EXE%" tools/apply-db-config.php
if errorlevel 1 goto :failed
"%MYSQL_EXE%" --default-character-set=utf8mb4 -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" --execute="SOURCE data/check.sql"
if errorlevel 1 goto :failed

echo [7/7] Start frontend and backend servers
start "WorldCup Frontend :8080" cmd /k ""%PHP_EXE%" yii serve --port=8080 --docroot=frontend/web"
start "WorldCup Backend :8081" cmd /k ""%PHP_EXE%" yii serve --port=8081 --docroot=backend/web"

echo.
echo Deployment completed.
echo Frontend: http://localhost:8080
echo Backend:  http://localhost:8081/site/login
echo Admin:    admin / admin123
echo Change the default password before public deployment.
goto :end

:failed
echo.
echo [FAILED] Deployment stopped. Review the error above.
pause
exit /b 1

:end
set "MYSQL_PWD="
set "WC_DB_PASSWORD="
endlocal
