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
echo "✅ Deploy completed: $(date)"
