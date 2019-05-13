FROM	php:7.3-cli

RUN	apt-get update && apt-get install -y cron ssmtp && \
        echo '[mail function]' > /usr/local/etc/php/conf.d/mailsetting.ini && \
        echo 'sendmail_path = /usr/sbin/ssmtp -t' >> /usr/local/etc/php/conf.d/mailsetting.ini && \
        echo 'root=postmaster' > /etc/ssmtp/ssmtp.conf && \
	echo '*/10 * * * * root php /root/rr1.php' >> /etc/crontab
COPY    codes/ /root/
COPY	entrypoint.sh /usr/local/bin/
ENV	REWRITE_DOMAIN example.com

ENTRYPOINT ["entrypoint.sh"]
CMD	["cron", "-f"]
