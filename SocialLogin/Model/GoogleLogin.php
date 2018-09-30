<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Model;

class GoogleLogin extends SocialLogin
{
    /**
     * @description Retrieve google client api object
     * @return object
     */
    public function newGoogle()
    {
        $google = null;
        try {
            $google = new \Google_Client;
            $google->setClientId($this->_dataHelper->getGoogleClientId());
            $google->setClientSecret($this->_dataHelper->getGoogleClientSecret());
            $google->setRedirectUri($this->_dataHelper->getGoogleRedirectUrl());
        } catch (\Exception $e) {
            $this->_messageManager->addErrorMessage($e->getMessage());
        }

        return $google;
    }
}
