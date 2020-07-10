FROM ubuntu:20.04




ADD . /var/www/html/

WORKDIR /var/www/html/

EXPOSE 80

USER root

RUN apt-get update \
    && echo "deb http://ppa.launchpad.net/openjdk-r/ppa/ubuntu focal main deb-src http://ppa.launchpad.net/openjdk-r/ppa/ubuntu focal main" >> /etc/apt/source.list \
    && apt-get update \
    && apt-get install -y openjdk-8-jdk \
    && apt-get install -y git \
    && apt-get install -y wget \
    && apt-get install -y curl \
    && apt-get install -y adb \
    && apt-get install -y aapt \
    && apt-get install -y unzip \
    && apt-get install -y zip \
    && wget https://services.gradle.org/distributions/gradle-6.4.1-bin.zip -P /tmp && unzip -d /opt/gradle /tmp/gradle-6.4.1-bin.zip && ln -s /opt/gradle/gradle-6.4.1/bin/gradle /usr/local/bin/gradle \
    && git clone https://github.com/skylot/jadx.git /opt/jadx/ && cd /opt/jadx/  && gradle dist && ln -s /opt/jadx/build/jadx/bin/jadx /usr/local/bin/jadx \
    && apt-get install -y php \
    && apt-get install -y php-xml \
    && mkdir /var/www/html/downloads && mkdir /var/www/html/downloads/decompiled \
    && cd /var/www/html/ \
    && sed -i "s|post_max_size = \([[:alnum:]]\+\)|post_max_size = 2048M|" /etc/php/7.4/apache2/php.ini \
    && sed -i "s|memory_limit = \([[:alnum:]]\+\)|memory_limit = 2048M|" /etc/php/7.4/apache2/php.ini \
    && sed -i "s|upload_max_filesize = \([[:alnum:]]\+\)|upload_max_filesize = 2048M|" /etc/php/7.4/apache2/php.ini 

CMD sed -i "s|Listen 80|Listen $PORT|g" /etc/apache2/ports.conf && apachectl start && tail -f /dev/null

# PHP CONFIGURATIONS
# /etc/php/7.4/apache2/php.ini
#   max_file_uploads
#   post_max_size
#   memory_limit
#   upload_max_filesize
