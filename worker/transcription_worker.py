#!/usr/bin/env python3
"""
Transcription Worker - pokreće se na lokalnom PC-u
Koristi faster-whisper za transkripciju s GPU ubrzanjem
"""

import os
import sys
import ctypes

# Fix za OpenMP duplicate library error na Windowsu
os.environ["KMP_DUPLICATE_LIB_OK"] = "TRUE"

# Windows: Pokaži/sakrij konzolu
def show_console():
    """Pokaži konzolu prozor"""
    if sys.platform == 'win32':
        ctypes.windll.user32.ShowWindow(ctypes.windll.kernel32.GetConsoleWindow(), 1)

def hide_console():
    """Sakrij konzolu prozor"""
    if sys.platform == 'win32':
        ctypes.windll.user32.ShowWindow(ctypes.windll.kernel32.GetConsoleWindow(), 0)

def set_console_title(title):
    """Postavi naslov konzole"""
    if sys.platform == 'win32':
        ctypes.windll.kernel32.SetConsoleTitleW(title)
import sys
import time
import json
import requests
import tempfile
from pathlib import Path

# Konfiguracija
SERVER_URL = "https://www.zagorje-promocija.com/desk-crm/api/worker/"
API_KEY = "REDACTED_WORKER_KEY"  # Mora biti isto kao na serveru!
POLL_INTERVAL = 5  # sekundi između provjera
WHISPER_MODEL = "large-v3"  # best quality - options: tiny, base, small, medium, large-v2, large-v3
DEVICE = "cuda"  # ili "cpu" ako nema GPU
COMPUTE_TYPE = "float32"  # float32 za starije GPU (Quadro P4000), float16 za novije (RTX serija)
FFMPEG_PATH = r"C:\ffmpeg\builds\ffmpeg-2026-01-14-git-6c878f8b82-essentials_build\ffmpeg-2026-01-14-git-6c878f8b82-essentials_build\bin\ffmpeg.exe"

# Headers za API
HEADERS = {
    "X-API-Key": API_KEY,
    "Content-Type": "application/json"
}

LOG_FILE = os.path.join(os.path.dirname(__file__), "worker.log")

def log(message):
    """Ispiši log s timestamp-om"""
    timestamp = time.strftime("%Y-%m-%d %H:%M:%S")
    line = f"[{timestamp}] {message}"
    print(line)
    # Spremi u log file
    try:
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(line + "\n")
    except:
        pass

def get_pending_jobs():
    """Dohvati pending jobove sa servera"""
    try:
        response = requests.get(f"{SERVER_URL}?action=pending", headers=HEADERS, timeout=30)
        if response.status_code == 200:
            return response.json().get("jobs", [])
        else:
            log(f"Greška pri dohvatu jobova: {response.status_code}")
            return []
    except Exception as e:
        log(f"Greška: {e}")
        return []

def claim_job(job_id):
    """Preuzmi job (označi kao processing)"""
    try:
        response = requests.get(f"{SERVER_URL}?action=claim&id={job_id}", headers=HEADERS, timeout=30)
        if response.status_code == 200:
            data = response.json()
            if "error" in data:
                return None
            return data.get("job")
        return None
    except Exception as e:
        log(f"Greška pri preuzimanju joba: {e}")
        return None

def download_file(job_id, output_path):
    """Skini video datoteku sa servera"""
    try:
        response = requests.get(f"{SERVER_URL}?action=download&id={job_id}", headers=HEADERS, timeout=600, stream=True)
        if response.status_code == 200:
            total_size = int(response.headers.get('content-length', 0))
            downloaded = 0
            with open(output_path, "wb") as f:
                for chunk in response.iter_content(chunk_size=65536):
                    f.write(chunk)
                    downloaded += len(chunk)
                    if total_size > 0:
                        pct = downloaded * 100 // total_size
                        print(f"\rSkidam: {pct}% ({downloaded // 1024 // 1024}MB)", end="", flush=True)
            print()  # Nova linija
            return True
        else:
            log(f"Greška pri skidanju: {response.status_code}")
            return False
    except Exception as e:
        log(f"Greška: {e}")
        return False

def extract_audio(video_path, audio_path):
    """Izvuci audio iz videa koristeći ffmpeg"""
    import subprocess

    cmd = [
        FFMPEG_PATH, '-i', video_path,
        '-vn', '-acodec', 'libmp3lame', '-ar', '16000', '-ac', '1', '-b:a', '64k',
        '-y', audio_path
    ]

    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=300)
        return result.returncode == 0 and os.path.exists(audio_path)
    except Exception as e:
        log(f"Greška pri ekstrakciji audia: {e}")
        return False

def burn_subtitles(video_path, srt_path, output_path):
    """Ugradi titlove u video koristeći ffmpeg"""
    import subprocess

    # Escape za ffmpeg subtitle filter (Windows paths)
    srt_escaped = srt_path.replace('\\', '/').replace(':', '\\:')

    cmd = [
        FFMPEG_PATH, '-i', video_path,
        '-vf', f"subtitles='{srt_escaped}':force_style='FontSize=16,PrimaryColour=&HFFFFFF&,OutlineColour=&H000000&,Outline=1,MarginV=20'",
        '-c:v', 'libx264', '-crf', '23', '-preset', 'fast',
        '-c:a', 'aac', '-b:a', '128k',
        '-movflags', '+faststart',
        '-y', output_path
    ]

    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=600)
        return result.returncode == 0 and os.path.exists(output_path)
    except Exception as e:
        log(f"Greška pri ugrađivanju titlova: {e}")
        return False

def transcribe(audio_path, language="hr"):
    """Transkribiraj s faster-whisper (GPU ubrzanje)"""
    from faster_whisper import WhisperModel
    import torch

    # Provjeri GPU dostupnost
    device = DEVICE if torch.cuda.is_available() else "cpu"
    compute_type = COMPUTE_TYPE if device == "cuda" else "int8"

    log(f"Učitavam faster-whisper model ({WHISPER_MODEL}) na {device}...")
    model = WhisperModel(WHISPER_MODEL, device=device, compute_type=compute_type)

    log("Transkribiram...")
    segments, info = model.transcribe(
        audio_path,
        language=language,
        beam_size=5,
        vad_filter=True,  # Voice Activity Detection za bolje filtriranje tišine
        vad_parameters=dict(min_silence_duration_ms=500)
    )

    log(f"Detektirani jezik: {info.language} (vjerojatnost: {info.language_probability:.2%})")
    log(f"Trajanje: {info.duration:.1f}s")

    return [{"start": s.start, "end": s.end, "text": s.text} for s in segments]

def format_srt(segments):
    """Pretvori segmente u SRT format"""
    def format_timestamp(seconds):
        hours = int(seconds // 3600)
        minutes = int((seconds % 3600) // 60)
        secs = int(seconds % 60)
        msecs = int((seconds % 1) * 1000)
        return f"{hours:02d}:{minutes:02d}:{secs:02d},{msecs:03d}"

    srt_lines = []
    for i, segment in enumerate(segments, 1):
        start = format_timestamp(segment["start"])
        end = format_timestamp(segment["end"])
        text = segment["text"].strip()
        srt_lines.append(f"{i}\n{start} --> {end}\n{text}\n")

    return "\n".join(srt_lines)

def upload_result(job_id, srt_content, processing_time, video_path=None, error=None):
    """Upload rezultata na server"""
    try:
        if video_path and os.path.exists(video_path):
            # Upload s video datotekom (multipart)
            with open(video_path, 'rb') as f:
                files = {'video': ('video.mp4', f, 'video/mp4')}
                data = {
                    'srt_content': srt_content,
                    'processing_time': str(processing_time),
                    'error': error or ''
                }
                # Remove Content-Type header for multipart
                headers = {"X-API-Key": API_KEY}
                response = requests.post(
                    f"{SERVER_URL}?action=complete&id={job_id}",
                    headers=headers,
                    data=data,
                    files=files,
                    timeout=300
                )
        else:
            # Samo SRT (JSON)
            data = {
                "srt_content": srt_content,
                "processing_time": processing_time,
                "error": error
            }
            response = requests.post(
                f"{SERVER_URL}?action=complete&id={job_id}",
                headers=HEADERS,
                json=data,
                timeout=60
            )
        return response.status_code == 200
    except Exception as e:
        log(f"Greška pri uploadu: {e}")
        return False

def process_job(job):
    """Obradi jedan job"""
    job_id = job["id"]
    filename = job["original_filename"]
    language = job.get("language", "hr")
    burn_subs = job.get("burn_subtitles", 0)

    # Pokaži prozor dok obrađuje
    show_console()
    set_console_title(f"Transcription Worker - Job #{job_id}")

    log(f"=== Obrađujem job #{job_id}: {filename} ===")

    start_time = time.time()

    # Kreiraj temp direktorij
    with tempfile.TemporaryDirectory() as temp_dir:
        video_path = os.path.join(temp_dir, "video.mp4")
        audio_path = os.path.join(temp_dir, "audio.mp3")
        srt_path = os.path.join(temp_dir, "subtitles.srt")
        output_video_path = os.path.join(temp_dir, "output.mp4")

        # 1. Skini video
        log("Skidam video...")
        if not download_file(job_id, video_path):
            upload_result(job_id, "", 0, error="Greška pri skidanju videa")
            return False
        log(f"Video skinut ({os.path.getsize(video_path) / 1024 / 1024:.1f} MB)")

        # 2. Izvuci audio lokalno
        log("Ekstrahiram audio...")
        if not extract_audio(video_path, audio_path):
            upload_result(job_id, "", 0, error="Greška pri ekstrakciji audia")
            return False

        # 3. Transkribiraj
        try:
            segments = transcribe(audio_path, language)
            srt_content = format_srt(segments)
        except Exception as e:
            log(f"Greška pri transkripciji: {e}")
            upload_result(job_id, "", 0, error=str(e))
            return False

        # 4. Ugradi titlove u video
        final_video_path = None
        if burn_subs:
            # Spremi SRT privremeno
            with open(srt_path, 'w', encoding='utf-8') as f:
                f.write(srt_content)

            log("Ugrađujem titlove u video...")
            if burn_subtitles(video_path, srt_path, output_video_path):
                final_video_path = output_video_path
                log(f"Video s titlovima spreman ({os.path.getsize(output_video_path) / 1024 / 1024:.1f} MB)")
            else:
                log("Upozorenje: Nije uspjelo ugrađivanje titlova, šaljem samo SRT")

        processing_time = time.time() - start_time

        # Upload rezultata
        if final_video_path:
            log(f"Uploadam video ({os.path.getsize(final_video_path) / 1024 / 1024:.1f} MB)...")
        else:
            log(f"Uploadam SRT ({len(srt_content)} bytes)...")

        if upload_result(job_id, srt_content, processing_time, final_video_path):
            log(f"✓ Job #{job_id} završen za {processing_time:.1f}s")
            time.sleep(2)  # Pauza da korisnik vidi poruku
            hide_console()
            set_console_title("Transcription Worker - Čekam...")
            return True
        else:
            log(f"✗ Greška pri uploadu joba #{job_id}")
            time.sleep(3)
            hide_console()
            return False

def main():
    """Glavna petlja"""
    log("=" * 50)
    log("Transcription Worker pokrenut")
    log(f"Server: {SERVER_URL}")
    log(f"Model: {WHISPER_MODEL}")
    log(f"Compute type: {COMPUTE_TYPE}")
    log("=" * 50)

    # Provjeri GPU
    try:
        import torch
        if torch.cuda.is_available():
            gpu_name = torch.cuda.get_device_name(0)
            gpu_mem = torch.cuda.get_device_properties(0).total_memory / 1024**3
            log(f"GPU: {gpu_name} ({gpu_mem:.1f} GB)")
        else:
            log("GPU nije dostupan, koristim CPU (sporije)")
    except Exception as e:
        log(f"PyTorch nije instaliran ili greška: {e}")

    log("Čekam jobove...\n")

    # Sakrij prozor dok čeka
    time.sleep(2)
    hide_console()
    set_console_title("Transcription Worker - Čekam...")

    while True:
        try:
            jobs = get_pending_jobs()

            if jobs:
                for job in jobs:
                    # Preuzmi job
                    claimed_job = claim_job(job["id"])
                    if claimed_job:
                        process_job(claimed_job)
                    time.sleep(1)
            else:
                # Čekaj prije sljedeće provjere
                time.sleep(POLL_INTERVAL)

        except KeyboardInterrupt:
            log("\nWorker zaustavljen.")
            break
        except Exception as e:
            log(f"Greška u glavnoj petlji: {e}")
            time.sleep(POLL_INTERVAL)

if __name__ == "__main__":
    main()
