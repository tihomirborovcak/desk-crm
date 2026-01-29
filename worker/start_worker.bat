@echo off
echo ========================================
echo   Transcription Worker
echo ========================================
echo.

cd /d "%~dp0"

REM Pokreni worker
python transcription_worker.py

pause
