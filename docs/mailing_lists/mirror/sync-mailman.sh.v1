#!/bin/bash

# sync from wowbagger. Run as mlman user.

set -x
date

echo "--- rsyncing /opt/mailman"
rsync -Pav -e "ssh -i $HOME/.ssh/id_rsa -o HostKeyAlgorithms=ssh-rsa,ssh-dss -o 'PubkeyAcceptedKeyTypes +ssh-rsa'" cppal@boost.org:/opt/mailman/ /opt/mailman/ || true
echo "--- rsyncing /home/hyper-archives/"
rsync -Pav -e "ssh -i $HOME/.ssh/id_rsa -o HostKeyAlgorithms=ssh-rsa,ssh-dss -o 'PubkeyAcceptedKeyTypes +ssh-rsa'" cppal@boost.org:/home/hyper-archives/ /home/hyper-archives/ || true
echo "--- rsyncing /home/www/lists.boost.org/"
rsync -Pav -e "ssh -i $HOME/.ssh/id_rsa -o HostKeyAlgorithms=ssh-rsa,ssh-dss -o 'PubkeyAcceptedKeyTypes +ssh-rsa'" cppal@boost.org:/home/www/lists.boost.org/ /home/www/lists.boost.org/ || true

echo "--- text replace in boost-old/index.php"
findtext='header("Location: http://lists.boost.org/Archives/boost");'
replacetext='header("Location: http://lists.boost.org.cpp.al/Archives/boost");'
file='/home/hyper-archives/boost-old/index.php'
sed -i "s~${findtext}~${replacetext}~" $file

# Fix a swish error. However, ultimately this is not working anyway because swish-e on wowbagger is 2.5.8 and the latest available version for download is 2.4.7.
findtext="'.swishcgi.conf'"
replacetext="'./.swishcgi.conf'"
files="""
/home/hyper-archives/boost-gil/cgi-bin/swish.cgi
/home/hyper-archives/boost-users/cgi-bin/swish.cgi
/home/hyper-archives/boost/cgi-bin/swish.cgi
"""
for file in $files; do
    sed -i "s~${findtext}~${replacetext}~" $file
done

# Remove search forms, because search is not currently supported on the mirror.
files="""
/home/hyper-archives/boost-gil/include/index-header.inc
/home/hyper-archives/boost-users/include/index-header.inc
/home/hyper-archives/boost/include/index-header.inc
"""

for file in $files; do
    perl -i -p0e 's~<p>\s*<form.*?</form>\s*</p>~~s' $file
done

# Use wget to download rendered html files for certain index pages. This is necessary, because the pages are usually generated server-side on the mailing list server,
# but those scripts are failing to run on the mirror.

mkdir -p /home/www/lists.boost.org/mailman/listinfo.cgi
cd /home/www/lists.boost.org/mailman/
wget lists.boost.org -O index.html

mailinglists="""
boost
boost-announce	
boost-bugs	
boost-build	
boost-cmake
boost-commit	
boost-docs	
boost-gil	
boost-interest
boost-maint	
boost-mpi	
boost-testing	
boost-users	
boost-www
geometry	
glas	
osl-test2	
proto
test	
test-ciere	
threads-devel	
ublas
"""

cd /home/www/lists.boost.org/mailman/listinfo.cgi
for list in $mailinglists; do
    wget https://lists.boost.org/mailman/listinfo.cgi/$list -O $list
    findtext="lists.boost.org/"
    replacetext="lists.boost.org.cpp.al/"
    sed -i "s~${findtext}~${replacetext}~" $list

    # and another files
    sed -i "s~${findtext}~${replacetext}~" /home/hyper-archives/$list/include/msg-footer.inc || echo "\nmsg-footer.inc not found in $list\n"
    sed -i "s~${findtext}~${replacetext}~" /home/hyper-archives/$list/include/index-footer.inc || echo "\nindex-footer.inc not found in $list\n"
    sed -i "s~${findtext}~${replacetext}~" /home/hyper-archives/$list/include/index-header.inc || echo "\nindex-header.inc not found in $list\n"
done

echo "Completed sync-mailman.sh"
