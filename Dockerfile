FROM php:8.2-apache

# Abilita modulo rewrite
RUN a2enmod rewrite

# Copia i file del backend
COPY . /var/www/html/

# Configura i permessi in sola lettura per sicurezza
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/
    
# Render cerca una porta esposta, la 80 e quella di default di Apache
EXPOSE 80
