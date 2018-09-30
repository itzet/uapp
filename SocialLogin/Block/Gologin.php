<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Block;

class Gologin extends Sociallogin
{
    /**
     * @description get google login url.
     * @return string google login url.
     */
    public function getGoogleLoginUrl()
    {
        return $this->getUrl('sociallogin/sociallogin/googlelogin');
    }
}