FROM ubuntu:20.04




ADD . /var/www/html/

WORKDIR /var/www/html/

EXPOSE 80

USER root

RUN apt-get update \
    && echo "deb http://ppa.launchpad.net/openjdk-r/ppa/ubuntu focal main deb-src http://ppa.launchpad.net/openjdk-r/ppa/ubuntu focal main" >> /etc/apt/source.list \
    && apt-get update \
    && apt-get install -y openjdk-8-jdk \
    && apt-get install -y wget \
    && apt-get install -y curl \
    && apt-get install -y adb \
    && apt-get install -y aapt \
    && apt-get install -y unzip \
    && apt-get install -y php \
    && apt-get install -y php-xml \
    && mkdir /var/www/html/downloads \
    && sed -i "s|Listen 80|Listen $PORT|g" /etc/apache2/ports.conf 

CMD apachectl start && tail -f /dev/null

