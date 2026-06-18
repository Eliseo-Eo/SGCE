#!/usr/bin/env bash
# SGCE 1.0.185 - Wrapper de capturas visuales móviles
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
python3 "$SCRIPT_DIR/visual-mobile-smoke.py"
