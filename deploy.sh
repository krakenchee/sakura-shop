#!/bin/bash
cd /srv/public
git pull origin main
if [ ! -f config.php ]; then
    echo "⚠️ Внимание: config.php отсутствует!"
else
    echo "config.php найден"
fi
chmod -R 755 /srv/public
chmod 644 /srv/public/config.php
chmod -R 755 /srv/public/css
chmod -R 755 /srv/public/assets
chmod -R 755 /srv/public/assets/uploads
chmod 777 /srv/public/assets/uploads/banners
chmod 777 /srv/public/assets/uploads/products
chmod 777 /srv/public/assets/uploads/categories
echo "✅ Deploy completed: $(date)"
