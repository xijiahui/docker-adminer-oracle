#!/bin/sh
set -e

if [ -n "$ADMINER_DESIGN" ]; then
	# Only create link on initial start, to ensure that explicit changes to
	# adminer.css after the container was started once are preserved.
	if [ ! -e .adminer-init ]; then
		ln -sf "designs/$ADMINER_DESIGN/adminer.css" .
	fi
fi

number=1
for PLUGIN in $ADMINER_PLUGINS; do
	php plugin-loader.php "$PLUGIN" > plugins-enabled/$(printf "%03d" $number)-$PLUGIN.php
	number=$(($number+1))
done

touch .adminer-init || true

if [[ ! -f /usr/local/oracle/instantclient_19_23/network/admin/tnsnames.ora ]]; then
    if [ -n "$ORACLE_SID" ] && [ -n "$ORACLE_HOST" ] && [ -n "$ORACLE_PORT" ]; then
        echo "$ORACLE_SID= 
(DESCRIPTION = 
  (ADDRESS = (PROTOCOL = TCP)(HOST = $ORACLE_HOST)(PORT = $ORACLE_PORT))
  (CONNECT_DATA =
    (SERVER = DEDICATED)
    (SERVICE_NAME = $ORACLE_SID)
  )
)" >> /usr/local/oracle/instantclient_19_23/network/admin/tnsnames.ora
    fi
fi

exec "$@"
