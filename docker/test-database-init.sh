#!/bin/sh
# Runs once, when the disposable database of docker/compose.test.yml is first started (the image's entrypoint runs every
# script of /docker-entrypoint-initdb.d): one database for each parallel process of the mutation testing, which Infection
# numbers in TEST_TOKEN from 1 up. A process that has a database of its own cannot empty the tables of another.
(
    set -eu
    i=1
    while [ "$i" -le 32 ]; do
        printf 'CREATE DATABASE ampf_kit_test_%s; GRANT ALL PRIVILEGES ON ampf_kit_test_%s.* TO '"'"'ampf_kit_test'"'"'@'"'"'%%'"'"';\n' "$i" "$i"
        i=$((i + 1))
    done | mariadb -uroot -p"$MARIADB_ROOT_PASSWORD"
)
