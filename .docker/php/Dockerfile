FROM php:7.2.17-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends openssh-server

COPY php.ini /usr/local/etc/php/php.ini
COPY php.ini /etc/php/7.2.3/php.ini
COPY php-fpm-pool.conf /etc/php/7.2.3/pool.d/www.conf

#COMPOSER
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
	mv composer.phar /usr/local/bin/composer
ENV COMPOSER_MEMORY_LIMIT -1

#XDEBUG
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

#SSH
RUN mkdir /var/run/sshd
RUN echo 'root:root' | chpasswd
RUN sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config
RUN  /usr/bin/ssh-keygen -A
EXPOSE 22

ARG PUID=1000
ARG PGID=1000

ENV PUID ${PUID}
ENV PGID ${PGID}

RUN groupadd dev -g ${PGID}
RUN useradd -u ${PUID} -g dev -d /home/dev -m dev

#Install GIT since composer might need it
RUN apt-get install -y --no-install-recommends git zip unzip

RUN rm -rf /var/lib/apt/lists/*

WORKDIR /home/wwwroot/

EXPOSE 9000

ADD run.sh /usr/local/bin/run.sh

CMD ["/usr/local/bin/run.sh"]
