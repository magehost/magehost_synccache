<?php

namespace MageHost\SyncCache\Model\Config\Source;

class Integration implements \Magento\Framework\Option\ArrayInterface
{
    /** @var \Magento\Integration\Model\ResourceModel\Integration\Collection */
    protected $_collection;

    public function __construct(
        \Magento\Integration\Model\ResourceModel\Integration\Collection $collection
    ) {
        $this->_collection = $collection;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->_collection->getData() as $data) {
            $result[] = ['value' =>  $data['consumer_id'], 'label' => $data['name']];
        }
        return $result;
    }
}
