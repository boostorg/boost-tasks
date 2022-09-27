
## Boost Mailing Lists Mirror Website

A copy of the archives from https://lists.boost.org are now hosted at https://lists.boost.org.cpp.al   

The initial goal was to attempt to reinstall and copy the entire mailing list system from Centos 6 to Ubuntu 22. However the mailman package available for Ubuntu 22 is mailman3. Alternative installation methods lead to incompatibilities with libraries and dependencies. Looking forward to the future, when upgrading to Ubuntu 24.04 or 26.04, python2 will cease to be available. Maintaining an instance of mailman2 will not be possible.  

What about copying the webpage archives, and not the full set of mailman programs?  

Mailman 2 is designed in such as way that the html webpages are somewhat separated from the functionality of sending and receiving emails. The html pages are generated and stored in their own directory. Let's only copy those pages and host them in read-only mode on Ubuntu 22.  

## Setup Instructions  

Steps to configure the web server running https://lists.boost.org.cpp.al :    

Create a mlman user account, install apache, configure Let's Encrypt, install the sync script. A number of those steps are documented in the setup.sh script in this directory. The setup.sh script is partially done, and includes comments about tasks that should be done manually.

Read ./setup.sh (from this directory) see what it's doing and run it.  

```
./setup.sh  
```

Check that an account on boost.org has the necessary ssh credentials, keys, and read access to all mailing list folders.  

## Ongoing Operation

The mirror works by running a sync script once per day, to copy the mailing lists from from list.boost.org to lists.boost.org.cpp.al  

```
./sync-mailman.sh  
```

cron:

```
0 5 * * * /home/mlman/scripts/sync-mailman.sh >> /tmp/sync-output.txt 2>&1  
```

The sync is replacing hyperlink text: lists.boost.org.cpp.al. If the archive goes into production and replaces lists.boost.org then it will be better to continue to use the URL "lists.boost.org" to avoid breaking search engine results.  

