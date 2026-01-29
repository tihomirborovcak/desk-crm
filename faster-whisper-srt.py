#!/usr/bin/env python3
import sys
from faster_whisper import WhisperModel

def format_timestamp(seconds):
    hours = int(seconds // 3600)
    minutes = int((seconds % 3600) // 60)
    secs = int(seconds % 60)
    msecs = int((seconds % 1) * 1000)
    return f"{hours:02d}:{minutes:02d}:{secs:02d},{msecs:03d}"

def transcribe_to_srt(audio_path, language='hr', output_path=None):
    # Koristi small model, CPU, int8 za brzinu
    model = WhisperModel('small', device='cpu', compute_type='int8')

    segments, info = model.transcribe(audio_path, language=language, beam_size=5)

    srt_content = []
    for i, segment in enumerate(segments, 1):
        start = format_timestamp(segment.start)
        end = format_timestamp(segment.end)
        text = segment.text.strip()
        srt_content.append(f"{i}\n{start} --> {end}\n{text}\n")

    result = '\n'.join(srt_content)

    if output_path:
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(result)

    return result

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print('Usage: python3 faster-whisper-srt.py <audio_file> <output_srt> [language]')
        sys.exit(1)

    audio = sys.argv[1]
    output = sys.argv[2]
    lang = sys.argv[3] if len(sys.argv) > 3 else 'hr'

    transcribe_to_srt(audio, lang, output)
    print(f'SRT saved to {output}')
