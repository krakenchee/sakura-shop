HOST=127.0.0.1
USER=root
PASSWORD=root
DATABASE=sakura_shop

mysql -u $USER -h $HOST -p$PASSWORD -e "DROP DATABASE IF EXISTS $DATABASE;"
mysql -u $USER -h $HOST -p$PASSWORD -e "CREATE DATABASE IF NOT EXISTS $DATABASE;"
mysql -u $USER -h $HOST -p$PASSWORD $DATABASE < /srv/public/sakura_shop.sql
