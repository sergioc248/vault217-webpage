FROM php:8.5.12-apache

ENV VAULT217_SESSION_PATH=/var/lib/vault217/sessions

COPY app/ /var/www/app/
COPY public/ /var/www/html/

RUN install -d -m 0700 -o www-data -g www-data /var/lib/vault217/sessions
