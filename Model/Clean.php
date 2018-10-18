<?php
namespace MageHost\SyncCache\Model;

use MageHost\SyncCache\Api\CleanInterface;

class Clean implements CleanInterface
{
    /** @var \Magento\Framework\App\Cache\Frontend\Pool */
    private $cacheFrontendPool;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Clean constructor.
     *
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->logger = $logger;
    }

    /**
     * Clean some cache records
     *
     * @param string $from - host sending request
     * @param string $mode - @see \Cm_Cache_Backend_Redis::clean()
     * @param string $tags_json - json encoded array of tags
     * @return boolean true if no problem
     *
     * mode: matchingTag  =  matching ALL tags, @see Cm_Cache_Backend_Redis::getIdsMatchingTags
     */
    public function clean($from, $mode, $tags_json)
    {
        $tags = json_decode($tags_json);
        $hostname = gethostname();
        $this->logger->info(
            sprintf(
                "%s:  Received clean request via API  from:%s  to:%s  mode:%s  tags:%s",
                __CLASS__,
                $from,
                $hostname,
                $mode,
                implode(",", $tags)
            )
        );
        if ($hostname == $from) {
            $this->logger->info(
                sprintf(
                    "%s:  Loop protection!",
                    __CLASS__
                )
            );
            return false;
        }
        $result = null;
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $frontendResult = $cacheFrontend->getBackend()->clean($mode, $tags);
            if (null === $result) {
                $result = $frontendResult;
            } else {
                $result = $result && $frontendResult;
            }
        }
        return $result;
    }
}
