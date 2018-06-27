FROM tutum/apache-php

RUN ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/

#Install php5-dev
RUN apt-get update && apt-get install -yq php5-dev

RUN apt-get update && apt-get install -yq git php5-mcrypt && rm -rf /var/lib/apt/lists/*


# remove app from image
RUN rm -fr /app

# add local repo to /app image - always run from root of project (. = current working dir)
ADD . /app

# remove and remake to get fresh start
RUN rm -fr /var/www

#symlink /app to location that apache will use
RUN ln -s /app /var/www

#env variables (local) are actually set in aptible, but it's necessary to be maintained.
RUN touch /app/.env

#PHP Composer installation
#RUN composer install
RUN composer update --lock

#add DNS server to Container
RUN echo "nameserver 8.8.8.8" >> /etc/resolv.conf
RUN echo "nameserver 8.8.4.4" >> /etc/resolv.conf