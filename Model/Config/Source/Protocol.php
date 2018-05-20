<?php

namespace MageHost\SyncCache\Model\Config\Source;

class Protocol implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'auto', 'label' => __('Automatic')],
            ['value' => 'http', 'label' => __('HTTP')],
            ['value' => 'https', 'label' => __('HTTPS')]
        ];
    }
}
