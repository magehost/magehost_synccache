<?php

namespace MageHost\SyncCache\Observer;

use Magento\Framework\Event\ObserverInterface;

class CacheCleanObserver implements ObserverInterface
{
    /** @var \Psr\Log\LoggerInterface */
    protected $_logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $a = 1;
    }
}
