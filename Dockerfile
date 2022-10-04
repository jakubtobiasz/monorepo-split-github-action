# THIS IS BASE IMAGE
FROM php:8.0-cli

RUN echo 'deb http://deb.debian.org/debian bullseye-backports main' >> /etc/apt/sources.list

RUN apt-get update -y
RUN apt-get install openssh-client python3 git git-filter-repo -y

RUN mkdir -p /root/.ssh && ssh-keyscan github.com >> /root/.ssh/known_hosts

# directory inside docker
WORKDIR /splitter

# make local content available inside docker - copies to /splitter
COPY . .

# see https://nickjanetakis.com/blog/docker-tip-86-always-make-your-entrypoint-scripts-executable
ENTRYPOINT ["php", "/splitter/entrypoint.php"]
