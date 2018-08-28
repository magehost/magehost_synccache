<?php

namespace MageHost\SyncCache\Model\Config\Source;

class Integration implements \Magento\Framework\Option\ArrayInterface
{
    /** @var \Magento\Integration\Model\ResourceModel\Integration\Collection */
    private $collection;

    /**
     * Integration constructor.
     * @param \Magento\Integration\Model\ResourceModel\Integration\Collection $collection
     */
    public function __construct(
        \Magento\Integration\Model\ResourceModel\Integration\Collection $collection
    ) {
        $this->collection = $collection;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->collection->getData() as $data) {
            $result[] = ['value' =>  $data['integration_id'], 'label' => $data['name']];
        }
        return $result;
    }
}
