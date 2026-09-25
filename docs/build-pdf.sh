#!/usr/bin/env bash
# Cetak ulang panduan: HTML -> PDF.
# WAJIB dijalankan setiap kali panduan-dashboard-gti.html diubah,
# kalau tidak, file PDF tetap berisi versi lama.
set -e
cd "$(dirname "$0")"
google-chrome --headless --disable-gpu --no-sandbox --no-pdf-header-footer \
  --print-to-pdf=Panduan-Dashboard-GTI.pdf panduan-dashboard-gti.html
echo "PDF diperbarui: $(date '+%H:%M:%S')"
