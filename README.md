MageHost CacheSync Extension for Magento 2
=====================
Sync cache cleaning among multiple nodes in a cluster setup.

# Install via Composer #

```
composer config repositories.magehost_synccache vcs git@github.com:magehost/magehost_synccache.git
composer require magehost/synccache
php bin/magento module:enable MageHost_SyncCache
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

# Uninstall #
```
php bin/magento module:disable MageHost_SyncCache
composer remove magehost/synccache
composer config --unset repositories.magehost_synccache
php bin/magento setup:upgrade
php bin/magento setup:di:compile

```
