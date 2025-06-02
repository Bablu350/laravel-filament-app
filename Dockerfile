FROM debian:bookworm-slim
ARG APP_UID=1000
ENV APP_UID=${APP_UID:-1000}
EXPOSE $APP_UID
ARG APP_GID=1000
ENV APP_GID=${APP_GID:-1000}
EXPOSE $APP_GID
RUN apt update -y
RUN apt upgrade -y
RUN apt dist-upgrade -y
RUN apt install -y gnupg2 lsb-release ca-certificates apt-transport-https wget curl git default-mysql-client pv screen tzdata wget
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
CMD ["/bin/sh", "-c", "if [ ! -f vendor/autoload.php ]; then composer update; fi && cd public && php -S 0.0.0.0:8000"]