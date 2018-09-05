#!/bin/bash
/usr/bin/php  /var/www/html/php/scriptbash.php
mysql -u root -p'root' bakeway_prod_copy < /var/www/html/php/cleanupquery.sql
/usr/bin/php /var/www/html/bakeway/bin/magento setup:upgrade
sudo chmod -R 777 /var/www/html/bakeway/var/ /var/www/html/bakeway/pub/static/
/usr/bin/php /var/www/html/bakeway/bin/magento setup:di:compile
sudo chmod -R 777 /var/www/html/bakeway/var/ /var/www/html/bakeway/pub/static/
/usr/bin/php /var/www/html/bakeway/bin/magento setup:static-content:deploy
sudo chmod -R 777 /var/www/html/bakeway/var/ /var/www/html/bakeway/pub/static/
/usr/bin/php /var/www/html/bakeway/bin/magento indexer:reindex
sudo chmod -R 777 /var/www/html/bakeway/var/ /var/www/html/bakeway/pub/static/
sudo chmod -R 777 /var/www/html/bakeway/var/ /var/www/html/bakeway/pub/static/