<?php

/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Model\System\Config;

class RegisterRedirectPage implements \Magento\Framework\Option\ArrayInterface
{
    const ACCOUNT_PAGE = 'customer/account';
    const CART_PAGE = 'checkout/cart';
    const HOME_PAGE = '/';
    const CURRENT_PAGE = 'current';

    /**
     * get redirect page value.
     *
     * @return []
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ACCOUNT_PAGE,
                'label' => __('Account Page'),
            ],
            [
                'value' => self::CART_PAGE,
                'label' => __('Cart Page'),
            ],
            [
                'value' => self::HOME_PAGE,
                'label' => __('Home Page'),
            ],
            [
                'value' => self::CURRENT_PAGE,
                'label' => __('Current Page'),
            ]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            0 => __('No'),
            1 => __('Yes'),
        ];
    }
}