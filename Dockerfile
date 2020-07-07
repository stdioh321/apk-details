FROM ubuntu:20.04




ADD . /app

WORKDIR /app

EXPOSE 8000

USER root

RUN apt-get update \
    && echo "deb http://ppa.launchpad.net/openjdk-r/ppa/ubuntu focal main deb-src http://ppa.launchpad.net/openjdk-r/ppa/ubuntu focal main" >> /etc/apt/source.list \
    && apt-get update \
    && apt-get install -y openjdk-8-jdk \
    && apt-get install -y wget \
    && apt-get install -y adb \
    && apt-get install -y aapt \
    && apt-get install -y unzip \
    && apt-get install -y php \
    && cp /app/php.ini /etc/php/7.4/cli/ \
    && cp /app/php.ini /etc/php/7.4/apache2/ \
    && apt-get install -y php-xml \
    && mkdir /app/downloads

CMD php -S 0.0.0.0:$PORT

