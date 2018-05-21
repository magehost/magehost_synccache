<?php

namespace MageHost\SyncCache\Observer;

use Magento\Framework\Event\ObserverInterface;

class CacheCleanObserver implements ObserverInterface
{
    const CONFIG_PATH = 'system/magehost_cachesync';

    /** @var \Psr\Log\LoggerInterface */
    protected $_logger;
    /** @var \MageHost\SyncCache\Model\RestClient\Local */
    protected $_restClient;
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $_scopeConfig;
    /** @var \Magento\Framework\Registry */
    protected $_registry;
    /** @var \Magento\Framework\App\RequestInterface */
    protected $_request;

    /**
     * CacheCleanObserver constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \MageHost\SyncCache\Model\RestClient\Local $restClient
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \MageHost\SyncCache\Model\RestClient\Local $restClient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request
    )
    {
        $this->_logger = $logger;
        $this->_restClient = $restClient;
        $this->_scopeConfig = $scopeConfig;
        $this->_registry = $registry;
        $this->_request = $request;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $registryVar = __CLASS__ . '::' . __FUNCTION__;
        if ($this->_registry->registry($registryVar) ) {
            // We are already in this function, prevent endless recursion.
            return;
        }
        if (false !== strpos($this->_request->getRequestUri(),'/magehost/synccache/')) {
            // We are already handling our API request, prevent endless sub requests.
            return;
        }
        $this->_registry->register($registryVar,true);
        if ($this->_scopeConfig->isSetFlag(self::CONFIG_PATH.'/sync_cache_cleaning')) {
            /** @var \Magento\Framework\DataObject $transportObject */
            $transportObject = $observer->getTransport();
            $integrationOK = $this->_restClient->setIntegrationId(
                $this->_scopeConfig->getValue(self::CONFIG_PATH.'/integration_id')
            );
            if ($integrationOK) {
                $nodes = explode("\n", $this->_scopeConfig->getValue(self::CONFIG_PATH . '/nodes'));
                $localHostname = gethostname();
                $localIPs = $this->getLocalIPs();
                foreach ($nodes as $node) {
                    $node = trim($node);
                    if (empty($node)) {
                        continue;
                    }
                    $nodeSplit = explode(':', $node);
                    $nodeHost = $nodeSplit[0];
                    if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $nodeHost)) {
                        $nodeIP = $nodeHost;
                    } else {
                        $nodeIP = gethostbyname($nodeHost);
                    }
                    if ($nodeHost == $localHostname || in_array($nodeIP, $localIPs)) {
                        // This is local, skip host
                        continue;
                    }
                    $sslOffloaded = false;
                    $urlProtocol = $this->_scopeConfig->getValue(self::CONFIG_PATH . '/protocol');
                    if ('http_ssloffloaded' == $urlProtocol) {
                        $urlProtocol = 'http';
                        $sslOffloaded = true;
                    }
                    $url = sprintf(
                        '%s://%s/rest/V1/magehost/synccache/clean/%s/%s/%s/',
                        $urlProtocol,
                        $node,
                        $localHostname,
                        urlencode($transportObject->getMode()),
                        urlencode(json_encode($transportObject->getTags()))
                    );
                    $this->_restClient->get(
                        $url,
                        $this->_scopeConfig->getValue(self::CONFIG_PATH . '/host_header'),
                        $sslOffloaded
                    );
                }
            }
        }
        $this->_registry->unregister($registryVar);
    }


    /**
     * Get the local IPs of a Linux, FreeBSD or Mac server
     *
     * @return array - Local IPs
     */
    protected function getLocalIPs() {
        $result = array();
        if ( function_exists('shell_exec') ) {
            $result = $this->readIPs('ip addr');
            if (empty($result)) {
                $result = $this->readIPs('ifconfig -a');
            }
        }
        if (!empty($_SERVER['SERVER_ADDR'])) {
            $result[] = $_SERVER['SERVER_ADDR'];
        }
        $result = array_unique($result);
        return $result;
    }

    /**
     * Execute shell command to receive IPs and parse the output
     *
     * @param $cmd   - can be 'ip addr' or 'ifconfig -a'
     * @return array - IP numbers
     */
    protected function readIPs( $cmd ) {
        $result = array();
        $lines = explode( "\n", trim(shell_exec($cmd.' 2>/dev/null')) );
        foreach( $lines as $line ) {
            $matches = array();
            if ( preg_match('|inet6?\s+(?:addr\:\s*)?([\:\.\w]+)|',$line,$matches) ) {
                $result[$matches[1]] = 1;
            }
        }
        unset( $result['127.0.0.1'] );
        unset( $result['::1'] );
        return array_keys($result);
    }
}
