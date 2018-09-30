<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Block;

class Linklogin extends Sociallogin
{
    /**
     * @description get linked-in login url.
     * @return string linked-in login url.
     */
    public function getLinkedInLoginUrl()
    {
        return $this->getLinkedInModel()->getLinkedInLoginUrl();
    }
}