MageHost CacheSync Extension for Magento 2
=====================
Sync cache cleaning among multiple nodes in a cluster setup.
Every node has its own cache storage, so we need to sync cache flushes.

# Install via Composer #

```
composer config repositories.magehost_synccache vcs git@github.com:magehost/magehost_synccache.git
composer config repositories.magehost_cm_cache_backend-m2 vcs git@github.com:magehost/magehost_cm_cache_backend-m2.git
composer require magehost/synccache
php bin/magento module:enable MageHost_SyncCache
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

# Usage #
* You need to configure `MageHost_Cm_Cache_Backend_Redis` or `MageHost_Cm_Cache_Backend_File` as cache backend in `app/etc/env.php`.
These are extended from [Collin Mollenhour's cache classes](https://github.com/colinmollenhour/Cm_Cache_Backend_Redis)
* After installation go *System > Integrations* in the Admin to create an integration with the API *System > MageHost CacheSync* enabled.
* Go to *Stores > Configuration > ADVANCED > System > MageHost CacheSync* to select the integration and enter the nodes.

# Uninstall #
```
php bin/magento module:disable MageHost_SyncCache
composer remove magehost/synccache
composer config --unset repositories.magehost_synccache
composer config --unset repositories.magehost_cm_cache_backend-m2
php bin/magento setup:upgrade
php bin/magento setup:di:compile

```

# Licence #
**This extension is free, licence: [BSD-3-Clause](https://github.com/magehost/magehost_synccache/blob/master/LICENSE).**
