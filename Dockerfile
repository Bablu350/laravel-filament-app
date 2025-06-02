FROM debian:bookworm-slim
ARG APP_UID=1000
ENV APP_UID=${APP_UID:-1000}
EXPOSE $APP_UID
ARG APP_GID=1000
ENV APP_GID=${APP_GID:-1000}
EXPOSE $APP_GID
RUN apt update -y && apt upgrade -y && apt dist-upgrade -y
RUN apt install -y gnupg2 lsb-release ca-certificates apt-transport-https wget curl default-mysql-client pv screen tzdata wget unzip
RUN apt-get install -y software-properties-common

# Install Basic Requirements
RUN buildDeps='gcc make autoconf libc-dev zlib1g-dev pkg-config' \
  && set -x \
  && apt update -y \
  && apt install -y gnupg2 dirmngr wget curl apt-transport-https lsb-release ca-certificates \
  && wget -qO /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
  && echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

RUN apt update -y
RUN apt install -y php8.2-cli php8.2-bz2 php-common php8.2-curl php8.2-gd php8.2-mbstring php8.2-mysql php8.2-pgsql php8.2-sqlite3 php8.2-xml php8.2-curl php8.2-soap php8.2-bcmath php8.2-zip php8.2-gd php8.2-imagick php8.2-intl
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
WORKDIR /web
COPY . /web
CMD ["/bin/sh", "-c", "if [ ! -f vendor/autoload.php ]; then composer update; fi && php artisan migrate && php artisan vendor:publish --tag=filament-assets --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]