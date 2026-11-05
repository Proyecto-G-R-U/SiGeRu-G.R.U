#!/bin/bash
# Se ejecuta UNA sola vez, cuando el contenedor bd crea la base.
# Usuario de la aplicación: solo puede leer y modificar datos
# (no crear ni borrar tablas) y solo desde el contenedor app.
mysql --protocol=socket -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
CREATE USER IF NOT EXISTS '${DB_USER}'@'${IP_APP}' IDENTIFIED BY '${DB_PASS}';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${IP_APP}';
FLUSH PRIVILEGES;
SQL
echo "Usuario ${DB_USER}@${IP_APP} creado con SELECT, INSERT, UPDATE, DELETE sobre ${DB_NAME}"

