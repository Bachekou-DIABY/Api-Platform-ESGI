FROM php:8.1-apache
COPY . /var/www/html
WORKDIR /var/www/html
RUN apt-get update && \
    apt-get install -y \
    && rm -rf /var/lib/apt/lists/*
EXPOSE 80
CMD ["apache2-foreground"]
