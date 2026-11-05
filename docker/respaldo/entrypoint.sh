#!/bin/bash
# Arranque del contenedor respaldo: deja todo en orden y levanta sshd.
set -e
# Clave del servidor: se crea una vez y se guarda en un volumen,
# así no cambia al recrear el contenedor
[[ -f /etc/ssh/hostkeys/ssh_host_ed25519_key ]] \
    || ssh-keygen -q -t ed25519 -N '' -f /etc/ssh/hostkeys/ssh_host_ed25519_key
install -d -m 700 -o respaldo -g respaldo /home/respaldo/.ssh
touch /home/respaldo/.ssh/authorized_keys
chown respaldo:respaldo /home/respaldo/.ssh/authorized_keys /srv/respaldos
chmod 600 /home/respaldo/.ssh/authorized_keys
chmod 750 /srv/respaldos
/usr/sbin/sshd -t
exec /usr/sbin/sshd -D -e
