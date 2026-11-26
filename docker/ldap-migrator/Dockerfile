FROM python:3.14-slim-trixie

ENV LDAPTLS_CACERT="/etc/ssl/certs/ldap.crt" \
    LDAPTLS_REQCERT="demand" \
    TZ="Europe/Prague"

RUN apt-get update && \
    apt-get install -y ldap-utils && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /phishingator

COPY docker/ldap-migrator/entrypoint.sh docker/ldap-migrator/common.sh docker/ldap-migrator/run-ldapsearch.sh ./
RUN chmod +x entrypoint.sh run-ldapsearch.sh

ENTRYPOINT ["./entrypoint.sh"]