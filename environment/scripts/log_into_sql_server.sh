mysql -u magento -p$(grep -m1 password app/etc/env.php | cut -d "'" -f4) -h $(grep -m1 host app/etc/env.php | cut -d "'" -f4) magento 