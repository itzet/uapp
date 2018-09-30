<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Block;

class Fblogin extends Sociallogin
{
    /**
     * @description get facebook login url.
     * @return string facebook login url.
     */
    public function getFbLoginUrl()
    {
        return $this->getFbModel()->getFbLoginUrl();
    }

    /**
     * @description get facebook logout url.
     * @return string facebook logout url.
     */
    public function getFbLogoutUrl()
    {
        return $this->getFbModel()->getFbLogoutUrl();
    }
}