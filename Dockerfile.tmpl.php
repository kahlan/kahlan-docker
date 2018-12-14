<?php
$var = getopt('', ['version:', 'dockerfile:']);
$type = end(explode('/', $var['dockerfile']));
$isPHP5 = (reset(explode('-', $type)) === 'php5');
$isAlpine = (end(explode('-', $type)) === 'alpine');
$KahlanVer = reset(explode('-', $var['version']));
?>
# AUTOMATICALLY GENERATED
# DO NOT EDIT THIS FILE DIRECTLY, USE /Dockerfile.tmpl.php

<? if ($isAlpine && $isPHP5) { ?>
FROM php:5-alpine
<? } elseif ($isAlpine) { ?>
FROM php:7.2-alpine
<? } elseif ($isPHP5) { ?>
FROM php:5-cli
<? } else { ?>
FROM php:7.2-cli
<? } ?>

MAINTAINER CrysaLEAD <contact@crysalead.com>


# Tweak up PHP cli configuration
RUN echo -n 'memory_limit=-1' > /usr/local/etc/php/conf.d/memory-limit.ini


# Install Kahlan
RUN curl -fL -o /tmp/kahlan.tar.gz \
         https://github.com/kahlan/kahlan/archive/<?= $KahlanVer; ?>.tar.gz \
    \
 && curl -fL -o /tmp/composer-setup.php \
         https://getcomposer.org/installer \
 && curl -fL -o /tmp/composer-setup.sig \
         https://composer.github.io/installer.sig \
 && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { echo 'Invalid installer' . PHP_EOL; exit(1); }" \
 && php /tmp/composer-setup.php --install-dir=/tmp --filename=composer \
    \
 && tar -xzf /tmp/kahlan.tar.gz -C /usr/src \
 && cd /usr/src/kahlan-*/ \
 && /tmp/composer install --no-dev --optimize-autoloader --no-progress \
 && ln -s /usr/src/kahlan-*/bin/kahlan /usr/local/bin/kahlan \
 && ln -s /usr/local/bin/kahlan /kahlan \
    \
 && rm -rf /root/.composer \
           /tmp/*


<? if ($isAlpine) { ?>
# Install Xdebug and make
RUN apk add --update --no-cache \
            make \
 && apk add --no-cache --virtual .tools-deps \
            autoconf g++ libtool \
 && (yes | pecl install xdebug<?= $isPHP5 ? '-2.5.5' : ''; ?>) \
 && apk del .tools-deps \
 && rm -rf /var/cache/apk/*
<? } else { ?>
# Install Xdebug
RUN yes | pecl install xdebug<?= $isPHP5 ? '-2.5.5' : ''; ?>
<? } ?>

# Create wrapper for running Kahlan under Xdebug
RUN ext=$(ls /usr/local/lib/php/extensions/no-debug-non-zts-*/xdebug.so \
                                                                 | tr -d '\n') \
 && echo '#!/bin/sh'                         >> /usr/local/bin/kahlan-xdebug \
 && echo -n 'exec /usr/local/bin/php'        >> /usr/local/bin/kahlan-xdebug \
 && echo -n ' -dzend_extension='$ext         >> /usr/local/bin/kahlan-xdebug \
 && echo ' -f /usr/local/bin/kahlan -- $@'   >> /usr/local/bin/kahlan-xdebug \
 && chmod +x /usr/local/bin/kahlan-xdebug \
 && ln -s /usr/local/bin/kahlan-xdebug /kahlan-xdebug


<? if (!$isPHP5) { ?>
# Create wrapper for running Kahlan under phpdbg
RUN echo '#!/bin/sh' >> /usr/local/bin/kahlan-phpdbg \
 && echo 'exec /usr/local/bin/phpdbg -qrr /usr/local/bin/kahlan "$@"' \
                     >> /usr/local/bin/kahlan-phpdbg \
 && chmod +x /usr/local/bin/kahlan-phpdbg \
 && ln -s /usr/local/bin/kahlan-phpdbg /kahlan-phpdbg


<? } ?>
VOLUME ["/app"]

WORKDIR /app

ENTRYPOINT ["/kahlan"]
