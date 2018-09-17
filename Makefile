NAME=yuriko/redroute1
CNTNAME=rr1
VERSION=php71

build:
	postmap transport
	docker build -t $(NAME):$(VERSION) .

restart: stop start

start:
	docker start $(CNTNAME)

stop:
	docker stop $(CNTNAME)

run:
	docker run -d \
	--privileged \
	--name $(CNTNAME) \
	--restart=always \
	$(NAME):$(VERSION)

contener=`docker ps -a -q`
image=`docker images | awk '/^<none>/ { print $$3 }'`

clean:
	@if [ "$(image)" != "" ] ; then \
		docker rmi $(image); \
	fi
	@if [ "$(contener)" != "" ] ; then \
		docker rm $(contener); \
	fi

rm:
	docker rm $(CNTNAME)

attach:
	docker exec -it $(CNTNAME) /bin/bash

job:
	docker exec -i $(CNTNAME) systemctl start rsyslog postfix
	docker exec -i $(CNTNAME) scl enable rh-php71 'php /rr1.php'

logs:
#	docker logs $(CNTNAME)
	docker exec -it $(CNTNAME) tail -F /var/log/maillog

