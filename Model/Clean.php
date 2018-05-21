<?php
namespace MageHost\SyncCache\Model;

use MageHost\SyncCache\Api\CleanInterface;

class Clean implements CleanInterface
{
    /** @var \Magento\Framework\Webapi\Rest\Request */
    protected $_request;
    /** @var \Magento\Framework\App\Cache\Frontend\Pool */
    protected $_cacheFrontendPool;
    /** @var \Psr\Log\LoggerInterface */
    protected $_logger;

    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Psr\Log\LoggerInterface $logger
    ) {
    
        $this->_request = $request;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->_logger = $logger;
    }

    /**
     * Clean some cache records
     *
     * @param string $from - host sending request
     * @param string $mode - @see \Cm_Cache_Backend_Redis::clean()
     * @param string $tags_json - json encoded array of tags
     * @return boolean true if no problem
     */
    public function clean($from, $mode, $tags_json)
    {
        $tags = json_decode($tags_json);
        $hostname = gethostname();
        $this->_logger->info(
            sprintf(
                "%s:  from:%s  to:%s  mode:%s  tags:%s",
                __CLASS__,
                $from,
                $hostname,
                $mode,
                implode(",", $tags)
            )
        );
        if ($hostname == $from) {
            $this->_logger->info(
                sprintf(
                    "%s:  Loop protection!",
                    __CLASS__
                )
            );
            return false;
        }
        $result = null;
        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $frontendResult = $cacheFrontend->getBackend()->clean($mode, $tags);
            if (is_null($result)) {
                $result = $frontendResult;
            } else {
                $result = $result && $frontendResult;
            }
        }
        return $result;
    }
}
