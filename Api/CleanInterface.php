<?php
namespace MageHost\SyncCache\Api;

interface CleanInterface
{
    /**
     * Clean some cache records
     *
     * @param string $from - host sending request
     * @param string $mode - @see \Cm_Cache_Backend_Redis::clean()
     * @param string $tags_json - json encoded array of tags
     * @return boolean true if no problem
     */
    public function clean($from, $mode, $tags_json);
}