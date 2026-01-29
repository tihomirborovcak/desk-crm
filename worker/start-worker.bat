@echo off
cd /d "%~dp0"
echo Pokrecem worker...
start "Transcription Worker" /min pythonw transcription_worker.py
echo.
echo Worker pokrenut u pozadini!
echo Prozor ce se pokazati kad bude obradivao job.
timeout /t 3
