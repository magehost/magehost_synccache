<?php
/**
 * MageHost_Hosting
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category     MageHost
 * @package      MageHost_Hosting
 * @copyright    Copyright (c) 2015 MageHost BVBA (http://www.magentohosting.pro)
 */

/**
 * Class MageHost_Cm_Cache_Backend_File
 * This class adds some functionality to Cm_Cache_Backend_File, mainly events.
 *
 * {@inheritdoc}
 */
class MageHost_Cm_Cache_Backend_File extends \Cm_Cache_Backend_File
{
    /** @var string|null */
    protected $frontendPrefix = null;
    /** @var \Magento\Framework\Event\ManagerInterface */
    protected $eventManager;

    function __construct(array $options = array())
    {
        parent::__construct($options);
        // There is no other way since the Cache Backend is a classic Zend class.
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->eventManager = $objectManager->create('\Magento\Framework\Event\Manager');
    }

    /**
     * This method will dispatch the events 'magehost_clean_backend_cache_before'
     *                                  and 'magehost_clean_backend_cache_after'.
     * Event listeners can change the mode or tags.
     *
     * {@inheritdoc}
     */
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {
        $transportObject = new \Magento\Framework\DataObject();
        /** @noinspection PhpUndefinedMethodInspection */
        $transportObject->setMode( $mode );
        /** @noinspection PhpUndefinedMethodInspection */
        $transportObject->setTags( $tags );
        $this->eventManager->dispatch( 'magehost_clean_backend_cache_before', array( 'transport' => $transportObject ) );
        /** @noinspection PhpUndefinedMethodInspection */
        $mode = $transportObject->getMode();
        /** @noinspection PhpUndefinedMethodInspection */
        $tags = $transportObject->getTags();
        $result = parent::clean($mode, $tags);
        $transportObject->setResult( $result );
        $this->eventManager->dispatch( 'magehost_clean_backend_cache_after', array( 'transport' => $transportObject ) );
        $result = $transportObject->getResult();
        return $result;
    }
}
