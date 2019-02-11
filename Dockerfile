FROM hub.bbobo.com/gc/php:uploader

COPY . /www
COPY app/docker/supervisord.conf /etc/supervisord.conf

RUN  mkdir /data