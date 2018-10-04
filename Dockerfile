FROM centos
LABEL	maintainer "yuriko"
RUN     yum -y update && yum clean all && \
	localedef -v -c -i ja_JP -f UTF-8 ja_JP.UTF-8; echo ""; env LANG=ja_JP.UTF-8
RUN     yum -y install centos-release-scl centos-release-scl-rha
RUN	yum -y install postfix rh-php71-php rsyslog zip unzip rh-php71-php-mbstring rh-php71-php-gd rh-php71-php-xml && \
	echo \#\!/bin/bash >> /etc/profile.d/scl-enable.sh&& \
        echo source /opt/rh/rh-php71/enable >> /etc/profile.d/scl-enable.sh&& \
        echo X_SCLS="`scl enable rh-php71 'echo $X_SCLS'`" >> /etc/profile.d/scl-enable.sh && \
	. /opt/rh/rh-php71/enable && \
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
	php composer-setup.php && \
	php -r "unlink('composer-setup.php');" && \
	mv composer.phar /usr/local/bin/composer && \
	composer require phpoffice/phpspreadsheet mpdf/mpdf

COPY	main.cf		/etc/postfix/main.cf
COPY	transport.db	/etc/postfix/transport.db
COPY	php.ini /opt/rh/rh-php70/register.content/etc/opt/rh/rh-php70/php.ini
COPY	./ /

ENTRYPOINT	["/sbin/init"]
