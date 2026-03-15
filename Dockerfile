FROM php:8.2-apache

# Desactiva el MPM extra y deja solo prefork
RUN a2dismod mpm_event && a2enmod mpm_prefork

COPY . /var/www/html/

EXPOSE 80