#!/bin/bash
set -e

# ── Persistir configuración de Claude ──────────────────────────────────────
# /root/.claude vive dentro del contenedor y se pierde al reiniciar.
# Lo reemplazamos con un symlink a /var/www/html/.claude, que sí persiste
# gracias al volumen montado desde el host.

CLAUDE_PERSIST="/var/www/html/.claude"
CLAUDE_HOME="/root/.claude"

mkdir -p "$CLAUDE_PERSIST"

if [ ! -L "$CLAUDE_HOME" ]; then
    # Si existe como directorio real (primer arranque tras el cambio), migramos su contenido
    if [ -d "$CLAUDE_HOME" ]; then
        cp -rn "$CLAUDE_HOME/." "$CLAUDE_PERSIST/" 2>/dev/null || true
        rm -rf "$CLAUDE_HOME"
    fi
    ln -s "$CLAUDE_PERSIST" "$CLAUDE_HOME"
fi

# ── Arrancar PHP-FPM ───────────────────────────────────────────────────────
exec php-fpm
