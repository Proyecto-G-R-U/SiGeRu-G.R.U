#!/bin/bash
# respaldar.sh — Respaldo diario de SiGeRU (corre en la VM, por cron)
#   1. Volcado de MySQL desde el contenedor bd (consistente, sin frenar la app)
#   2. Código + docker-compose.yml + .env del contenedor app
#   3. Huella SHA-256 de cada archivo
#   4. Envío al contenedor respaldo por rsync sobre SSH (con clave)
#   5. Retención: 14 días en la VM, 90 en el servidor de respaldo
set -o pipefail

PROYECTO="/opt/sigeru/GRU"
LOCAL="/respaldos"
CLAVE="/root/.ssh/id_ed25519_respaldo"
DESTINO="respaldo@172.30.0.30"
REMOTO="/srv/respaldos"
LOG="/var/log/sigeru-respaldos.log"
FECHA=$(date +%F_%H%M%S)
errores=0

log() { echo "$(date '+%F %T') [$1] $2" | tee -a "$LOG"; }

mkdir -p "$LOCAL/bd" "$LOCAL/app" && chmod 700 "$LOCAL"

# 1) Base de datos: mysqldump dentro del contenedor bd, con la clave de root
#    que el contenedor ya tiene como variable (no aparece en este script ni en ps)
archivo_bd="$LOCAL/bd/sigeru_$FECHA.sql.gz"
docker compose --project-directory "$PROYECTO" exec -T bd \
    sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump -uroot --single-transaction --routines --triggers --databases sigeru' \
    | gzip > "$archivo_bd"
if (( PIPESTATUS[0] == 0 )); then
    log OK "Base respaldada: $archivo_bd"
else
    log ERROR "Falló mysqldump"; rm -f "$archivo_bd"; errores=1
fi

# 2) Aplicación: el proyecto completo (incluye .env y docker-compose.yml)
archivo_app="$LOCAL/app/app_$FECHA.tar.gz"
if tar -czf "$archivo_app" -C "$(dirname "$PROYECTO")" --exclude='.git' "$(basename "$PROYECTO")"; then
    log OK "Aplicación respaldada: $archivo_app"
else
    log ERROR "Falló el respaldo de la aplicación"; errores=1
fi

# 3) Huellas para verificar que la copia no se dañó
for f in "$archivo_bd" "$archivo_app"; do
    [[ -f $f ]] && (cd "$(dirname "$f")" && sha256sum "$(basename "$f")" > "$(basename "$f").sha256")
done
chmod 600 "$LOCAL"/*/*

# 4) Envío al servidor de respaldo (solo lo nuevo)
if rsync -az -e "ssh -i $CLAVE -o BatchMode=yes -o StrictHostKeyChecking=accept-new" \
        "$LOCAL/" "$DESTINO:$REMOTO/"; then
    log OK "Copias enviadas a $DESTINO:$REMOTO"
else
    log ERROR "No se pudo enviar al servidor de respaldo"; errores=1
fi

# 5) Retención
find "$LOCAL" -type f -mtime +14 -delete
ssh -n -i "$CLAVE" -o BatchMode=yes "$DESTINO" "find $REMOTO -type f -mtime +90 -delete" \
    || log AVISO "No se pudo limpiar el servidor de respaldo"

exit "$errores"
