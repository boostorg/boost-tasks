
## Boost Mailing List Administration

The boost mailing lists are hosted on the main web server at https://lists.boost.org. The software is running mailman version 2 and python2.  

In view of the new boost website upgrade project there are multiple paths that might be followed. Not definitely decided yet.  

1. Copy the old mailing lists onto a new server and host them as a read-only archive.  

2. Upgrade the mailing lists to mailman 3 and python3. Mailman 3 is a major upgrade using Django and Postgres.  

3. Import the mailing list content into the new boost website. If this is done, the easiest path would probably be to complete step #2 first. The upgrade would migrate all list content into database format. Then it can be exported/imported more directly.  

A difficulty with importing the mailing lists is that the old lists were threaded. The new forums have a linear format. Also, there is a problem of linking the author of the earlier email message to the new account (if it exists) in the new forum. The URLs will change. Is an import required? Perhaps a read-only archive is enough.

Each of the above topics can be discussed further. See [mirror/README.md](mirror/) for details about a mirror archive of the old lists on a new server.  
