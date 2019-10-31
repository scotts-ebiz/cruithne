echo -e "\n\nThe following files are in the db, but do not exist in this file system:\n";
mysql -u magento -p$(grep -m1 password app/etc/env.php | cut -d "'" -f4) -h $(grep -m1 host app/etc/env.php | cut -d "'" -f4) magento -e "select value from catalog_product_entity_media_gallery;" 2>&1 | grep -v "Warning" | while read value; do
        if [ $value = "value" ]; then continue; fi
        if [ ! -f "/var/www/html/magento2/pub/media/catalog/product/${value}" ]; then
                echo "$value"
        fi
done

# Ask user if they'd like to delete these images
echo -e "\n Would you like to remove these images from the db? y/n"
read remove_images

if [ $remove_images = "y" ]; then
	mysql -u magento -p$(grep -m1 password app/etc/env.php | cut -d "'" -f4) -h $(grep -m1 host app/etc/env.php | cut -d "'" -f4) magento -e "select value from catalog_product_entity_media_gallery;" 2>&1 | grep -v "Warning" | while read value; do
		echo "Removing ${value}";
		mysql -u magento -p$(grep -m1 password app/etc/env.php | cut -d "'" -f4) -h $(grep -m1 host app/etc/env.php | cut -d "'" -f4) magento -e "delete from catalog_product_entity_media_gallery where value like '%${value}%';"
	done
elif [ $remove_images = "n" ]; then
	exit 0;
else
	echo "${remove_images} is not a valid answer";
	exit 1;
fi