<?php

namespace MageHost\SyncCache\Observer;

use Magento\Framework\Event\ObserverInterface;

class CacheCleanObserver implements ObserverInterface
{
    const CONFIG_PATH = 'system/magehost_cachesync';

    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /** @var \MageHost\SyncCache\Model\RestClient\Local */
    private $restClient;
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;
    /** @var \Magento\Framework\Registry */
    private $registry;
    /** @var \Magento\Framework\UrlInterface */
    private $url;

    /**
     * CacheCleanObserver constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \MageHost\SyncCache\Model\RestClient\Local $restClient
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \MageHost\SyncCache\Model\RestClient\Local $restClient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->logger = $logger;
        $this->restClient = $restClient;
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
        $this->url = $url;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $registryVar = __CLASS__ . '::' . __FUNCTION__;
        if ($this->registry->registry($registryVar)) {
            // We are already in this function, prevent endless recursion.
            return;
        }
        $this->registry->register($registryVar, true);
        if (false !== strpos($this->url->getCurrentUrl(), '/magehost/synccache/')) {
            // We are already handling our API request, prevent endless sub requests.
            $this->registry->unregister($registryVar);
            return;
        }
        if (! $this->scopeConfig->isSetFlag(self::CONFIG_PATH.'/sync_cache_cleaning')) {
            // Cache cleaning is not enabled in configuration
            $this->registry->unregister($registryVar);
            return;
        }
        /** @var \Magento\Framework\DataObject $transportObject */
        $integrationOK = $this->restClient->setIntegrationId(
            $this->scopeConfig->getValue(self::CONFIG_PATH.'/integration_id')
        );
        if (! $integrationOK) {
            // Configured integration id is not working
            $this->registry->unregister($registryVar);
            return;
        }

        $transportObject = $observer->getTransport();
        $nodes = explode("\n", $this->scopeConfig->getValue(self::CONFIG_PATH . '/nodes'));
        $localHostname = gethostname();
        $sslOffloaded = false;
        $urlProtocol = $this->scopeConfig->getValue(self::CONFIG_PATH . '/protocol');
        if ('http_ssl_offloaded' == $urlProtocol) {
            $urlProtocol = 'http';
            $sslOffloaded = true;
        }
        foreach ($nodes as $node) {
            $node = trim($node);
            if (!$this->checkUseNode($node)) {
                continue;
            }
            $url = sprintf(
                '%s://%s/rest/V1/magehost/synccache/clean/%s/%s/%s/',
                $urlProtocol,
                $node,
                $localHostname,
                urlencode($transportObject->getMode()),
                urlencode(json_encode($transportObject->getTags()))
            );
            $this->restClient->get(
                $url,
                $this->scopeConfig->getValue(self::CONFIG_PATH . '/host_header'),
                $sslOffloaded
            );
        }
        $this->registry->unregister($registryVar);
    }

    /**
     * Check if a cluster node should be called or skipped
     *
     * @param string $node
     * @return bool
     */
    private function checkUseNode($node)
    {
        if (empty($node)) {
            return false;
        }
        $nodeSplit = explode(':', $node);
        $nodeHost = $nodeSplit[0];
        if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $nodeHost)) {
            $nodeIP = $nodeHost;
        } else {
            $nodeIP = gethostbyname($nodeHost);
        }
        if ($nodeHost == gethostname()) {
            // Node is the local hostname, skip host
            return false;
        }
        if (in_array($nodeIP, $this->getLocalIPs())) {
            // Node IP is a local IP, skip host
            return false;
        }
        return true;
    }

    /**
     * Get the local IPs of a Linux, FreeBSD or Mac server
     *
     * @return array - Local IPs
     */
    private function getLocalIPs()
    {
        static $result = []; // for caching
        if (empty($result)) {
            if (function_exists('shell_exec')) {
                $result = $this->readIPs('ip addr');
                if (empty($result)) {
                    $result = $this->readIPs('ifconfig -a');
                }
            }
            if (!empty($_SERVER['SERVER_ADDR'])) {
                $result[] = $_SERVER['SERVER_ADDR'];
            }
            $result = array_unique($result);
        }
        return $result;
    }

    /**
     * Execute shell command to receive IPs and parse the output
     *
     * @param $cmd   - can be 'ip addr' or 'ifconfig -a'
     * @return array - IP numbers
     */
    private function readIPs($cmd)
    {
        $result = [];
        $lines = explode("\n", trim(shell_exec($cmd.' 2>/dev/null')));
        foreach ($lines as $line) {
            $matches = [];
            if (preg_match('|inet6?\s+(?:addr\:\s*)?([\:\.\w]+)|', $line, $matches)) {
                $result[$matches[1]] = 1;
            }
        }
        unset($result['127.0.0.1']);
        unset($result['::1']);
        return array_keys($result);
    }
}
