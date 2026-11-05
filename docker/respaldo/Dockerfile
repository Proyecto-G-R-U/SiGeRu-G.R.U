# Contenedor "respaldo" — servidor de respaldo (Rocky Linux 9)
FROM rockylinux/rockylinux:9

RUN dnf -y install openssh-server openssh-clients rsync findutils \
 && dnf clean all \
 && useradd -m -s /bin/bash -c "Recibe respaldos" respaldo \
 && usermod -p '*' respaldo \
 && mkdir -p /srv/respaldos /etc/ssh/hostkeys \
 && chown respaldo:respaldo /srv/respaldos && chmod 750 /srv/respaldos

COPY docker/respaldo/10-sigeru.conf   /etc/ssh/sshd_config.d/10-sigeru.conf
COPY docker/respaldo/entrypoint.sh    /usr/local/bin/entrypoint.sh
COPY docker/respaldo/autorizar-clave  /usr/local/bin/autorizar-clave
RUN chmod 600 /etc/ssh/sshd_config.d/10-sigeru.conf \
 && chmod 755 /usr/local/bin/entrypoint.sh /usr/local/bin/autorizar-clave

EXPOSE 22
CMD ["/usr/local/bin/entrypoint.sh"]
