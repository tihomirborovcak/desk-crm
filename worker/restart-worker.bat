@echo off
echo Zaustavljam worker...
taskkill /F /IM python.exe /FI "WINDOWTITLE eq Transcription Worker*" 2>nul
taskkill /F /FI "WINDOWTITLE eq Transcription Worker*" 2>nul

timeout /t 2 /nobreak >nul

echo Pokrecem worker...
cd /d "%~dp0"
start "Transcription Worker" /min pythonw transcription_worker.py

echo.
echo Worker pokrenut u pozadini!
echo Prozor ce se pokazati kad bude obradivao job.
timeout /t 3
