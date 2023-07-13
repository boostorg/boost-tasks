FROM ubuntu:18.04

RUN apt-get update
RUN DEBIAN_FRONTEND="noninteractive" apt-get -y install tzdata
RUN apt-get install -y php7.2 php-xml php7.2-mysql php7.2-sqlite php7.2-curl rsync net-tools htop libxml2-utils apache2 curl wget \
    iputils-ping jq vim tree ssh unzip git mlocate nload python3 build-essential

# COPY api.bintray.com.ssl.conf /etc/apache2/sites-available/
COPY api.github.com.ssl.conf /etc/apache2/sites-available/
# COPY dl.bintray.com.ssl.conf /etc/apache2/sites-available/
COPY boostorg.jfrog.io.ssl.conf /etc/apache2/sites-available/
COPY github.com.ssl.conf /etc/apache2/sites-available/

# From next section.
#    a2ensite dl.bintray.com.ssl.conf && \
#    a2ensite api.bintray.com.ssl.conf && \
RUN a2ensite github.com.ssl.conf && \
    a2ensite api.github.com.ssl.conf && \
    a2ensite boostorg.jfrog.io.ssl.conf && \
    a2dissite 000-default.conf && \
    a2dissite default-ssl.conf && \
    a2enmod headers env cgi alias rewrite ssl && \
    openssl rand -out /root/.rnd -hex 256 && \
    mkdir -p /root/certs
WORKDIR /root/certs
RUN openssl req -nodes -new -x509 -keyout myCA.key -out myCA.crt -subj "/C=US/ST=New York/L=Brooklyn/O=Example Company/CN=myCA.com" -days 3650 && \
    cp myCA.crt /usr/local/share/ca-certificates/ && \
    update-ca-certificates

# ARG DOMAIN=api.bintray.com
# RUN openssl req -newkey rsa:2048 -nodes -keyout $DOMAIN.key -out $DOMAIN.csr -subj "/C=US/ST=New York/L=Brooklyn/O=Example Company/CN=$DOMAIN" && \
#     openssl x509 -req -days 3650 -in $DOMAIN.csr -CA myCA.crt -CAkey myCA.key -CAcreateserial -out $DOMAIN.pem -sha256 && \
#     cp $DOMAIN.pem /etc/ssl/certs/ && \
#     cp $DOMAIN.key /etc/ssl/private/

ARG DOMAIN=api.github.com
RUN openssl req -newkey rsa:2048 -nodes -keyout $DOMAIN.key -out $DOMAIN.csr -subj "/C=US/ST=New York/L=Brooklyn/O=Example Company/CN=$DOMAIN" && \
    openssl x509 -req -days 3650 -in $DOMAIN.csr -CA myCA.crt -CAkey myCA.key -CAcreateserial -out $DOMAIN.pem -sha256 && \
    cp $DOMAIN.pem /etc/ssl/certs/ && \
    cp $DOMAIN.key /etc/ssl/private/

# ARG DOMAIN=dl.bintray.com
# RUN openssl req -newkey rsa:2048 -nodes -keyout $DOMAIN.key -out $DOMAIN.csr -subj "/C=US/ST=New York/L=Brooklyn/O=Example Company/CN=$DOMAIN" && \
#     openssl x509 -req -days 3650 -in $DOMAIN.csr -CA myCA.crt -CAkey myCA.key -CAcreateserial -out $DOMAIN.pem -sha256 && \
#     cp $DOMAIN.pem /etc/ssl/certs/ && \
#     cp $DOMAIN.key /etc/ssl/private/

ARG DOMAIN=boostorg.jfrog.io
RUN openssl req -newkey rsa:2048 -nodes -keyout $DOMAIN.key -out $DOMAIN.csr -subj "/C=US/ST=New York/L=Brooklyn/O=Example Company/CN=$DOMAIN" && \
    openssl x509 -req -days 3650 -in $DOMAIN.csr -CA myCA.crt -CAkey myCA.key -CAcreateserial -out $DOMAIN.pem -sha256 && \
    cp $DOMAIN.pem /etc/ssl/certs/ && \
    cp $DOMAIN.key /etc/ssl/private/

ARG DOMAIN=github.com
RUN openssl req -newkey rsa:2048 -nodes -keyout $DOMAIN.key -out $DOMAIN.csr -subj "/C=US/ST=New York/L=Brooklyn/O=Example Company/CN=$DOMAIN" && \
    openssl x509 -req -days 3650 -in $DOMAIN.csr -CA myCA.crt -CAkey myCA.key -CAcreateserial -out $DOMAIN.pem -sha256 && \
    cp $DOMAIN.pem /etc/ssl/certs/ && \
    cp $DOMAIN.key /etc/ssl/private/ && \
    useradd -m git && \
    useradd -m dnljms

RUN mkdir /root/.ssh
WORKDIR /root/.ssh
# From below
# mkdir -p /var/www/html/api.bintray.com && \
# mkdir -p /var/www/html/dl.bintray.com && \
# cp /var/www/html/index.html /var/www/html/api.bintray.com/ && \
# cp /var/www/html/index.html /var/www/html/dl.bintray.com/
RUN ssh-keygen -t rsa -N "" -f id_rsa && \
    mkdir /home/git/.ssh && \
    cp id_rsa /home/git/.ssh/ && \
    cp id_rsa.pub /home/git/.ssh/ && \
    cp id_rsa.pub /home/git/.ssh/authorized_keys && \
    chown -R git:git /home/git/.ssh && \
    mkdir /home/dnljms/.ssh && \
    cp id_rsa /home/dnljms/.ssh/ && \
    cp id_rsa.pub /home/dnljms/.ssh/ && \
    cp id_rsa.pub /home/dnljms/.ssh/authorized_keys && \
    chown -R dnljms:dnljms /home/dnljms/.ssh && \
    mkdir -p /home/www/shared/data/ && \
    mkdir -p /var/www/html/api.github.com && \
    mkdir -p /var/www/html/boostorg.jfrog.io && \
    mkdir -p /var/www/git && \
    cp /var/www/html/index.html /var/www/html/api.github.com/ && \
    cp /var/www/html/index.html /var/www/html/boostorg.jfrog.io/

#Download repo list
ARG GITHUBTOKEN
RUN mkdir -p /var/www/html/api.github.com/orgs/boostorg && \
    cd /var/www/html/api.github.com/orgs/boostorg && \
    curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/orgs/boostorg/events > events && \
    sleep 2 && \
    curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/orgs/boostorg/repos > repos && \
    sleep 2 && \
    mkdir -p /var/www/html/api.github.com/organizations/3170529 && \
    cd /var/www/html/api.github.com/organizations/3170529 && \
    curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/organizations/3170529/repos?page=2 > repos_page=2 && \
    sleep 2 && \
    curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/organizations/3170529/repos?page=3 > repos_page=3 && \
    sleep 2 && \
    curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/organizations/3170529/repos?page=4 > repos_page=4 && \
    sleep 2 && \
    curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/organizations/3170529/repos?page=5 > repos_page=5 && \
    sleep 2 && \
    curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/organizations/3170529/repos?page=6 > repos_page=6 && \
    sleep 2 && \
    curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/organizations/3170529/repos?page=7 > repos_page=7 && \
    sleep 2 && \
    curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/organizations/3170529/repos?page=8 > repos_page=8 && \
    sleep 2 && \
    curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/organizations/3170529/repos?page=9 > repos_page=9 

RUN mkdir -p /root/scripts && \
    cd /root/scripts && \
    cat /var/www/html/api.github.com/orgs/boostorg/repos | jq '.[]|.name' >> list_of_all_libraries.txt && \
    cat /var/www/html/api.github.com/organizations/3170529/repos_page=2 | jq '.[]|.name' >> list_of_all_libraries.txt && \
    cat /var/www/html/api.github.com/organizations/3170529/repos_page=3 | jq '.[]|.name' >> list_of_all_libraries.txt && \
    cat /var/www/html/api.github.com/organizations/3170529/repos_page=4 | jq '.[]|.name' >> list_of_all_libraries.txt && \
    cat /var/www/html/api.github.com/organizations/3170529/repos_page=5 | jq '.[]|.name' >> list_of_all_libraries.txt && \
    cat /var/www/html/api.github.com/organizations/3170529/repos_page=6 | jq '.[]|.name' >> list_of_all_libraries.txt && \
    cat /var/www/html/api.github.com/organizations/3170529/repos_page=7 | jq '.[]|.name' >> list_of_all_libraries.txt && \
    cat /var/www/html/api.github.com/organizations/3170529/repos_page=8 | jq '.[]|.name' >> list_of_all_libraries.txt && \
    cat /var/www/html/api.github.com/organizations/3170529/repos_page=9 | jq '.[]|.name' >> list_of_all_libraries.txt && \
    sed -i -e 's/^"//' -e 's/"$//' list_of_all_libraries.txt && \
    sort -o list_of_all_libraries.txt list_of_all_libraries.txt 

# # Download info from api.bintray.com
# RUN mkdir -p /var/www/html/api.bintray.com/packages/boostorg/master/snapshot
# WORKDIR /var/www/html/api.bintray.com/packages/boostorg/master/snapshot
# RUN wget https://api.bintray.com/packages/boostorg/master/snapshot/files
# 
# RUN mkdir -p /var/www/html/api.bintray.com/packages/boostorg/develop/snapshot
# WORKDIR /var/www/html/api.bintray.com/packages/boostorg/develop/snapshot
# RUN wget https://api.bintray.com/packages/boostorg/develop/snapshot/files
# 
# RUN mkdir -p /var/www/html/api.bintray.com/packages/boostorg/release/boost
# WORKDIR /var/www/html/api.bintray.com/packages/boostorg/release/boost
# RUN wget https://api.bintray.com/packages/boostorg/release/boost/files

# # Download bintray releases
# RUN mkdir -p /var/www/html/dl.bintray.com/boostorg/release/1.64.0/source
# WORKDIR /var/www/html/dl.bintray.com/boostorg/release/1.64.0/source
# RUN wget https://dl.bintray.com/boostorg/release/1.64.0/source/boost_1_64_0.tar.bz2
# 
# RUN mkdir -p /var/www/html/dl.bintray.com/boostorg/release/1.73.0/source
# WORKDIR /var/www/html/dl.bintray.com/boostorg/release/1.73.0/source
# RUN wget https://dl.bintray.com/boostorg/release/1.73.0/source/boost_1_73_0.tar.bz2
# 
# RUN mkdir -p /var/www/html/dl.bintray.com/boostorg/develop
# WORKDIR  /var/www/html/dl.bintray.com/boostorg/develop
# RUN wget https://dl.bintray.com/boostorg/develop/boost_1_74_0-snapshot.tar.bz2
# 
# RUN mkdir -p /var/www/html/dl.bintray.com/boostorg/master
# WORKDIR  /var/www/html/dl.bintray.com/boostorg/master
# RUN wget https://dl.bintray.com/boostorg/master/boost_1_74_0-snapshot.tar.bz2

# Download bintray releases
ARG DOWNLOADVERSION1=1.64.0
ARG DOWNLOADVERSION1_UNDERSCORE=boost_1_64_0
RUN mkdir -p /var/www/html/boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION1}/source
WORKDIR /var/www/html/boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION1}/source
RUN wget https://boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION1}/source/${DOWNLOADVERSION1_UNDERSCORE}.tar.bz2
RUN wget https://boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION1}/source/${DOWNLOADVERSION1_UNDERSCORE}.7z.json
RUN wget https://boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION1}/source/${DOWNLOADVERSION1_UNDERSCORE}.tar.bz2.json
RUN wget https://boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION1}/source/${DOWNLOADVERSION1_UNDERSCORE}.tar.gz.json
RUN wget https://boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION1}/source/${DOWNLOADVERSION1_UNDERSCORE}.zip.json

ARG DOWNLOADVERSION2=1.73.0
ARG DOWNLOADVERSION2_UNDERSCORE=boost_1_73_0
RUN mkdir -p /var/www/html/boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION2}/source
WORKDIR /var/www/html/boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION2}/source
RUN wget https://boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION2}/source/${DOWNLOADVERSION2_UNDERSCORE}.tar.bz2
RUN wget https://boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION2}/source/${DOWNLOADVERSION2_UNDERSCORE}.7z.json
RUN wget https://boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION2}/source/${DOWNLOADVERSION2_UNDERSCORE}.tar.bz2.json
RUN wget https://boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION2}/source/${DOWNLOADVERSION2_UNDERSCORE}.tar.gz.json
RUN wget https://boostorg.jfrog.io/artifactory/main/release/${DOWNLOADVERSION2}/source/${DOWNLOADVERSION2_UNDERSCORE}.zip.json

ARG DOWNLOADVERSION3=1.83.0
ARG DOWNLOADVERSION3_UNDERSCORE=boost_1_83_0-snapshot
RUN mkdir -p /var/www/html/boostorg.jfrog.io/artifactory/main/develop
WORKDIR /var/www/html/boostorg.jfrog.io/artifactory/main/develop
RUN wget https://boostorg.jfrog.io/artifactory/main/develop/${DOWNLOADVERSION3_UNDERSCORE}.tar.bz2
RUN wget https://boostorg.jfrog.io/artifactory/main/develop/${DOWNLOADVERSION3_UNDERSCORE}.7z.json
RUN wget https://boostorg.jfrog.io/artifactory/main/develop/${DOWNLOADVERSION3_UNDERSCORE}.tar.bz2.json
RUN wget https://boostorg.jfrog.io/artifactory/main/develop/${DOWNLOADVERSION3_UNDERSCORE}.tar.gz.json
RUN wget https://boostorg.jfrog.io/artifactory/main/develop/${DOWNLOADVERSION3_UNDERSCORE}.zip.json

RUN mkdir -p /var/www/html/boostorg.jfrog.io/artifactory/main/master
WORKDIR /var/www/html/boostorg.jfrog.io/artifactory/main/master
RUN wget https://boostorg.jfrog.io/artifactory/main/master/${DOWNLOADVERSION3_UNDERSCORE}.tar.bz2
RUN wget https://boostorg.jfrog.io/artifactory/main/master/${DOWNLOADVERSION3_UNDERSCORE}.7z.json
RUN wget https://boostorg.jfrog.io/artifactory/main/master/${DOWNLOADVERSION3_UNDERSCORE}.tar.bz2.json
RUN wget https://boostorg.jfrog.io/artifactory/main/master/${DOWNLOADVERSION3_UNDERSCORE}.tar.gz.json

RUN mkdir -p /var/www/html/boostorg.jfrog.io/artifactory/api/storage/main
WORKDIR /var/www/html/boostorg.jfrog.io/artifactory/api/storage/main
RUN wget https://boostorg.jfrog.io/artifactory/api/storage/main/develop
RUN wget https://boostorg.jfrog.io/artifactory/api/storage/main/master
 
ARG DOWNLOADVERSION4=1.83.0.beta1
ARG DOWNLOADVERSION4_UNDERSCORE=boost_1_83_0_b1
RUN mkdir -p /var/www/html/boostorg.jfrog.io/artifactory/main/beta/${DOWNLOADVERSION4}/source/
WORKDIR  /var/www/html/boostorg.jfrog.io/artifactory/main/beta/${DOWNLOADVERSION4}/source/
RUN wget https://boostorg.jfrog.io/artifactory/main/beta/1.77.0.beta1/source/${DOWNLOADVERSION4_UNDERSCORE}.tar.bz2
RUN wget https://boostorg.jfrog.io/artifactory/main/beta/1.77.0.beta1/source/${DOWNLOADVERSION4_UNDERSCORE}.7z.json
RUN wget https://boostorg.jfrog.io/artifactory/main/beta/1.77.0.beta1/source/${DOWNLOADVERSION4_UNDERSCORE}.tar.bz2.json
RUN wget https://boostorg.jfrog.io/artifactory/main/beta/1.77.0.beta1/source/${DOWNLOADVERSION4_UNDERSCORE}.tar.gz.json
RUN wget https://boostorg.jfrog.io/artifactory/main/beta/1.77.0.beta1/source/${DOWNLOADVERSION4_UNDERSCORE}.zip.json

# Clone all boostorg repos
RUN mkdir -p /var/www/git/boostorg/
WORKDIR /var/www/git/boostorg
RUN cat /root/scripts/list_of_all_libraries.txt | xargs -I {} git clone https://github.com/boostorg/{} /var/www/git/boostorg/{}.git
RUN cat /root/scripts/list_of_all_libraries.txt | xargs -I {} sh -c '{ cd {}.git ; git checkout master ; cd .. ; }'
RUN cat /root/scripts/list_of_all_libraries.txt | xargs -I {} sh -c '{ cd {}.git ; git checkout develop ; cd .. ; }'

# Clone boost-tasks repo, over https
RUN mkdir -p /var/www/git/boostorg/
WORKDIR /var/www/git/boostorg
RUN git clone https://github.com/boostorg/boost-tasks
WORKDIR /var/www/git/boostorg/boost-tasks
RUN git checkout php7
RUN git checkout master
RUN git config --file .git/config http.receivepack true
RUN chmod -R 777 /var/www/git/boostorg/boost-tasks/

# Clone repos for ssh access
RUN mkdir -p /home/git/boostorg
WORKDIR /home/git/boostorg
RUN git clone --bare https://github.com/boostorg/website website.git
RUN git clone --bare https://github.com/boostorg/boost boost.git
RUN chown -R git:git /home/git/boostorg

# Retrieve commit info from api.github.com
RUN mkdir -p /var/www/html/api.github.com/repos/boostorg
WORKDIR /var/www/html/api.github.com/repos/boostorg
RUN cat /root/scripts/list_of_all_libraries.txt | xargs -I {} sh -c '{ mkdir -p {}/git/refs/heads ; curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/repos/boostorg/{}/git/refs/heads/master > {}/git/refs/heads/master; sleep 2 ; }'
RUN cat /root/scripts/list_of_all_libraries.txt | xargs -I {} sh -c '{ mkdir -p {}/git/refs/heads ; curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/repos/boostorg/{}/git/refs/heads/develop > {}/git/refs/heads/develop; sleep 2 ; }'

# Retrieve pull info from api.github.com
RUN mkdir -p /var/www/html/api.github.com/repos/boostorg
WORKDIR /var/www/html/api.github.com/repos/boostorg
RUN cat /root/scripts/list_of_all_libraries.txt | xargs -I {} sh -c '{ mkdir -p {} ; curl -H "Authorization: token $GITHUBTOKEN" https://api.github.com/repos/boostorg/{}/pulls > {}/pulls; sleep 2 ; }'

# Get boost-tasks
RUN mkdir -p /opt/github/boostorg
WORKDIR /opt/github/boostorg
RUN git clone https://github.com/boostorg/boost-tasks

# Install composer
RUN cd /tmp && \
    curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer 
WORKDIR /opt/github/boostorg/boost-tasks
RUN composer install

COPY config.neon var/

RUN mkdir -p /home/www/shared/archives/test/tmp && \
    echo "git config --global user.name testuser" >> /root/.bashrc && \
    echo "git config --global user.email testuser@gmail.com" >> /root/.bashrc

#Install quickbook
RUN cd /opt/github && \
    git clone -b master https://github.com/boostorg/boost.git && \
    cd boost && \
    git submodule update --init libs/context && \
    git submodule update --init tools/boostbook && \
    git submodule update --init tools/boostdep && \
    git submodule update --init tools/docca && \
    git submodule update --init tools/quickbook && \
    python3 tools/boostdep/depinst/depinst.py ../tools/quickbook && \
    ./bootstrap.sh && \
    ./b2 tools/quickbook/ && \
    cp dist/bin/quickbook /usr/local/bin/quickbook

# LAST SECTION --------------------
WORKDIR /opt/github/boostorg/boost-tasks
CMD sleep 1000000000
