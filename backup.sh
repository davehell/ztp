#!/bin/sh

DATUM=`date +%Y-%m-%d-%H%M`

cd /var/www/html/ztp/bkp

#vytvoření nové zálohy
/usr/bin/mysqldump --force --opt --user=ztp --password=Instar123 ztp-ostra > ztp-ostra-$DATUM.sql

#smazání starých záloh (zůstane jen x nejnovějších souborů)
(ls -t |head -n 15;ls)|sort|uniq -u|xargs rm

#smazání starého archivu
rm ztp-ostra.zip

#vytvoření nového archivu
zip ztp-ostra.zip *.sql
