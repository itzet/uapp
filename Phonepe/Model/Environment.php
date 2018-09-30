<?php
namespace Urjakart\Phonepe\Model;


class Environment implements \Magento\Framework\Option\ArrayInterface
{
    const ENVIRONMENT_PRODUCTION = 'production';
    const ENVIRONMENT_UAT    = 'uat';

    /**
     * Possible environment types
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ENVIRONMENT_UAT,
                'label' => 'Uat',
            ],
            [
                'value' => self::ENVIRONMENT_PRODUCTION,
                'label' => 'Production'
            ]
        ];
    }
}
