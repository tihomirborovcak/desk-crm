@echo off
echo ========================================
echo   Transcription Worker
echo ========================================
echo.

REM Aktiviraj virtualenv ako postoji
if exist venv\Scripts\activate.bat (
    call venv\Scripts\activate.bat
)

REM Pokreni worker
python transcription_worker.py

pause
