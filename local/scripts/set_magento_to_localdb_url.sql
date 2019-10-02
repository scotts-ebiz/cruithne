# This set of scripts will update the correct core_config_data rows with the test urls
# I'll usually run these manually (copy these lines and paste them in the mysql prompt, but you might be able
# to run it as a script

update core_config_data set value='http://magento.scottsprogram.local/' where scope='default' and path='web/unsecure/base_url';
update core_config_data set value='http://magento.scottsprogram.local/' where scope='default' and path='web/secure/base_url';
update core_config_data set value='http://magento.scottsprogram.local/' where scope='websites' and scope_id=1 and path='web/unsecure/base_url';
update core_config_data set value='http://magento.scottsprogram.local/' where scope='websites' and scope_id=1 and path='web/unsecure/base_link_url';
update core_config_data set value='http://magento.scottsprogram.local/' where scope='stores' and scope_id=1 and path='web/unsecure/base_url';
update core_config_data set value='http://magento.scottsprogram.local/' where scope='stores' and scope_id=1 and path='web/unsecure/base_link_url';
update core_config_data set value='http://magento.scottsprogram.local/' where scope='stores' and scope_id=2 and path='web/unsecure/base_url';
update core_config_data set value='http://magento.scottsprogram.local/' where scope='stores' and scope_id=2 and path='web/unsecure/base_link_url';

update core_config_data set value='magento.scottsprogram.local' where scope='default' and scope_id=0 and path='web/cookie/cookie_domain';
update core_config_data set value='magento.scottsprogram.local' where scope='websites' and scope_id=2 and path='web/cookie/cookie_domain';
update core_config_data set value='magento.scottsprogram.local' where scope='stores' and scope_id=2 and path='web/cookie/cookie_domain';

update core_config_data set value='http://magento.scottsprogram.local/' where scope='websites' and scope_id=2 and path='web/unsecure/base_url';
update core_config_data set value='http://magento.scottsprogram.local/' where scope='websites' and scope_id=2 and path='web/unsecure/base_link_url';
update core_config_data set value='http://magento.scottsprogram.local/' where scope='websites' and scope_id=2 and path='web/secure/base_url';
update core_config_data set value='http://magento.scottsprogram.local/' where scope='websites' and scope_id=2 and path='web/secure/base_link_url';

update core_config_data set value='0' where scope='default' and path='web/secure/use_in_adminhtml';
update core_config_data set value='0' where scope='default' and path='web/secure/use_in_frontend';

# http://magento.scottsprogram.local/admin_1j9rrk/