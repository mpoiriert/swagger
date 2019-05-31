#!/bin/sh
# Start PHP
php-fpm -D
# Start SSH
/usr/sbin/sshd -D