@echo off
chcp 65001 > nul
setlocal enabledelayedexpansion

REM ============================================
REM EVENT COLLECTION SYSTEM - BACKUP SCRIPT
REM ============================================

set PROJECT_DIR=C:\xampp\htdocs\event-collection
set BACKUP_ROOT=C:\backups\event-collection
set DB_NAME=event_collection
set MYSQL_BIN=C:\xampp\mysql\bin

REM Date format: YYYY-MM-DD_HH-MM
for /f "tokens=1-4 delims=/ " %%i in ('date /t') do (
    set day=%%i
    set month=%%j
    set year=%%k
)
for /f "tokens=1-2 delims=: " %%i in ('time /t') do (
    set hour=%%i
    set min=%%j
)

set DATE_STR=%year%-%month%-%day%_%hour%-%min%
set BACKUP_DIR=%BACKUP_ROOT%\%DATE_STR%

echo.
echo ============================================
echo   EVENT COLLECTION - BACKUP
echo ============================================
echo   Backup Folder: %BACKUP_DIR%
echo ============================================
echo.

REM Create backup folder
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

REM ---- 1. Database Backup ----
echo [1/4] Database backup...
"%MYSQL_BIN%\mysqldump" -u root --single-transaction --routines --triggers %DB_NAME% > "%BACKUP_DIR%\database.sql"
if %errorlevel% equ 0 (
    echo       OK - database.sql
) else (
    echo       FAILED - Database backup error!
)

REM ---- 2. Uploads Folder ----
echo [2/4] Uploads folder...
xcopy "%PROJECT_DIR%\uploads" "%BACKUP_DIR%\uploads\" /E /I /Y /Q > nul
if %errorlevel% equ 0 (
    echo       OK - uploads
) else (
    echo       FAILED - Uploads backup error!
)

REM ---- 3. Config Files ----
echo [3/4] Config files...
xcopy "%PROJECT_DIR%\config" "%BACKUP_DIR%\config\" /E /I /Y /Q > nul
echo       OK - config

REM ---- 4. Source Code (Optional - Only custom files) ----
echo [4/4] Source code (custom files only)...
mkdir "%BACKUP_DIR%\code" 2>nul
xcopy "%PROJECT_DIR%\admin" "%BACKUP_DIR%\code\admin\" /E /I /Y /Q > nul
xcopy "%PROJECT_DIR%\super-admin" "%BACKUP_DIR%\code\super-admin\" /E /I /Y /Q > nul
xcopy "%PROJECT_DIR%\includes" "%BACKUP_DIR%\code\includes\" /E /I /Y /Q > nul
xcopy "%PROJECT_DIR%\assets" "%BACKUP_DIR%\code\assets\" /E /I /Y /Q > nul
copy "%PROJECT_DIR%\*.php" "%BACKUP_DIR%\code\" > nul 2>&1
copy "%PROJECT_DIR%\composer.json" "%BACKUP_DIR%\code\" > nul 2>&1
echo       OK - code

REM ---- Verify ----
echo.
echo ============================================
echo   Backup Complete!
echo ============================================
echo.
echo Contents:
dir "%BACKUP_DIR%" /b

REM Calculate folder size
for /f "tokens=3" %%a in ('dir "%BACKUP_DIR%" /s /-c ^| findstr /C:")"') do set size=%%a
echo.
echo Total size: %size% bytes
echo Location:   %BACKUP_DIR%
echo.

REM ---- Cleanup Old Backups (Keep last 30) ----
echo Cleaning up old backups (keeping last 30)...
set /a count=0
for /f "delims=" %%d in ('dir "%BACKUP_ROOT%" /ad /b /o-d') do (
    set /a count+=1
    if !count! GTR 30 (
        rmdir /s /q "%BACKUP_ROOT%\%%d"
        echo   Removed old: %%d
    )
)

echo.
echo ============================================
echo   DONE! 
echo ============================================
pause