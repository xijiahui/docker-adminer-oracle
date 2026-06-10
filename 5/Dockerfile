FROM php:8.3-alpine

RUN	echo "upload_max_filesize = 128M" >> /usr/local/etc/php/conf.d/0-upload_large_dumps.ini \
&&	echo "post_max_size = 128M" >> /usr/local/etc/php/conf.d/0-upload_large_dumps.ini \
&&	echo "memory_limit = 1G" >> /usr/local/etc/php/conf.d/0-upload_large_dumps.ini \
&&	echo "max_execution_time = 600" >> /usr/local/etc/php/conf.d/0-upload_large_dumps.ini \
&&	echo "max_input_vars = 5000" >> /usr/local/etc/php/conf.d/0-upload_large_dumps.ini

STOPSIGNAL SIGINT

RUN	addgroup -S adminer \
&&	adduser -S -G adminer adminer \
&&	mkdir -p /var/www/html \
&&	mkdir /var/www/html/plugins-enabled \
&&	chown -R adminer:adminer /var/www/html

WORKDIR /var/www/html

RUN	set -x \
&&	apk add --no-cache --virtual .build-deps \
	postgresql-dev \
	sqlite-dev \
	unixodbc-dev \
	freetds-dev \
&&	docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr \
&&	docker-php-ext-install \
	mysqli \
	pdo_pgsql \
	pdo_sqlite \
	pdo_odbc \
	pdo_dblib \
&&	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)" \
&&	apk add --virtual .phpexts-rundeps $runDeps \
&&	apk del --no-network .build-deps

COPY	*.php /var/www/html/

ENV	ADMINER_VERSION=5.4.2
ENV	ADMINER_DOWNLOAD_SHA256=5b761efe7049bf586119256324fd417b49e5bb9243b40d9734fe86655e4402fd
ENV	ADMINER_SRC_DOWNLOAD_SHA256=a4106d61bc81575d0b45c762105eead064384643418cad197a3257677625bd10

RUN	set -x \
&&	curl -fsSL https://github.com/vrana/adminer/releases/download/v$ADMINER_VERSION/adminer-$ADMINER_VERSION.php -o adminer.php \
&&	echo "$ADMINER_DOWNLOAD_SHA256  adminer.php" |sha256sum -c - \
&&	curl -fsSL https://github.com/vrana/adminer/archive/v$ADMINER_VERSION.tar.gz -o source.tar.gz \
&&	echo "$ADMINER_SRC_DOWNLOAD_SHA256  source.tar.gz" |sha256sum -c - \
&&	tar xzf source.tar.gz --strip-components=1 "adminer-$ADMINER_VERSION/designs/" "adminer-$ADMINER_VERSION/plugins/" \
&&	rm source.tar.gz

COPY	entrypoint.sh /usr/local/bin/
ENTRYPOINT	[ "entrypoint.sh", "docker-php-entrypoint" ]

USER	adminer
CMD	[ "php", "-S", "[::]:8080", "-t", "/var/www/html" ]

EXPOSE 8080

# # support oracle
USER root

ENV ORACLE_BASE     /usr/local/oracle
ENV LD_LIBRARY_PATH /usr/local/oracle/instantclient_19_30:/lib
ENV TNS_ADMIN       /usr/local/oracle/instantclient_19_30/network/admin
ENV ORACLE_HOME     /usr/local/oracle/instantclient_19_30

RUN apk update && apk upgrade --available && \
  apk add --no-cache bash autoconf build-base composer libaio libnsl libc6-compat busybox-extras curl && \
  ln -s /lib/libc.so.6 /usr/lib/libresolv.so.2 && \
  ln -s /lib64/ld-linux-x86-64.so.2 /usr/lib/ld-linux-x86-64.so.2 && \
  ln -s /usr/lib/libnsl.so.3.0.0 /usr/lib/libnsl.so.1 && \
  rm -rf /var/cache/apk/*  && \
  cd /tmp

# # Install Oracle Client and build OCI8 (Oracle Command Interface 8 - PHP extension)
RUN \
## Download and unarchive Instant Client v19.30
  mkdir            /usr/local/oracle && \
  curl -o          /tmp/sdk.zip            https://download.oracle.com/otn_software/linux/instantclient/1930000/instantclient-sdk-linux.x64-19.30.0.0.0dbru.zip && \
  curl -o          /tmp/basic_lite.zip     https://download.oracle.com/otn_software/linux/instantclient/1930000/instantclient-basiclite-linux.x64-19.30.0.0.0dbru.zip && \
# curl -o          /tmp/sqlplus.zip        https://download.oracle.com/otn_software/linux/instantclient/1930000/instantclient-sqlplus-linux.x64-19.30.0.0.0dbru.zip && \
  unzip -o -d      /usr/local/oracle       /tmp/sdk.zip && \
  unzip -o -d      /usr/local/oracle       /tmp/basic_lite.zip  && \
# unzip -o -d      /usr/local/oracle       /tmp/sqlplus.zip && \
  chown adminer:adminer /usr/local/oracle/instantclient_19_30/network/admin && \
  rm -rf           /tmp/*.zip && \
  mkdir            /etc/php.d && \
  echo "extension=oci8.so" > /etc/php.d/instantclient.ini

## Build OCI8 with PECL
RUN \
  C_INCLUDE_PATH=/usr/local/oracle/instantclient_19_30/sdk/include/ docker-php-ext-configure oci8       --with-oci8=instantclient,/usr/local/oracle/instantclient_19_30 && \
  docker-php-ext-install oci8 && \
  C_INCLUDE_PATH=/usr/local/oracle/instantclient_19_30/sdk/include/ docker-php-ext-configure pdo_oci --with-pdo-oci=instantclient,/usr/local/oracle/instantclient_19_30 && \
  docker-php-ext-install pdo_oci 

#  Clean up
RUN \
  rm -rf /tmp/*.zip /var/cache/apk/* /tmp/pear/

USER adminer
