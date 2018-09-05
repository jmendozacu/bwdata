#!/bin/bash
/usr/bin/php /var/www/html/dbsync/dbdump.php
rm /home/manish/Documents/Bakeway/dbsync/proddb.sql
mysqldump -u root -p'zaq1!1qaz' -h bakeway-rds.cscupwfn77vh.ap-south-1.rds.amazonaws.com bakeway_11_12_2017 > /home/manish/Documents/Bakeway/dbsync/proddb.sql
sed -i 's/bakeway_11_12_2017/bakeway_prod_sync/g' /home/manish/Documents/Bakeway/dbsync/proddb.sql
mysql -u root -p'root' bakeway_prod_sync < /home/manish/Documents/Bakeway/dbsync/proddb.sql
mysql -u root -p'root' bakeway_prod_sync < /home/manish/Documents/Bakeway/dbsync/cleanupquery.sql
/usr/bin/php /var/www/html/bakeway/bin/magento setup:upgrade
chmod -R 777 /var/www/html/bakeway/var/ /var/www/html/bakeway/pub/static/
/usr/bin/php /var/www/html/bakeway/bin/magento setup:di:compile
chmod -R 777 /var/www/html/bakeway/var/ /var/www/html/bakeway/pub/static/
/usr/bin/php /var/www/html/bakeway/bin/magento setup:static-content:deploy
chmod -R 777 /var/www/html/bakeway/var/ /var/www/html/bakeway/pub/static/
/usr/bin/php /var/www/html/bakeway/bin/magento indexer:reindex
chmod -R 777 /var/www/html/bakeway/var/ /var/www/html/bakeway/pub/static/
chmod -R 777 /var/www/html/bakeway/var/ /var/www/html/bakeway/pub/static/