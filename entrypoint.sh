#!/bin/sh
set -e

echo 'rewriteDomain='$REWRITE_DOMAIN >> /etc/ssmtp/ssmtp.conf
echo 'mailhub='$(hostname -i|sed -e 's/\.[0-9]*$/.1/') >> /etc/ssmtp/ssmtp.conf

exec "$@"
