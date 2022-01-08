#!/bin/bash
sleep 10
mysql -u root -p"$MYSQL_ROOT_PASSWORD" < /init.sql