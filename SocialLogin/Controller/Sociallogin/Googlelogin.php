<?php

/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Controller\Sociallogin;

class Googlelogin extends \Urjakart\SocialLogin\Controller\SocialLogin
{
    /**
     * @var \Google_Client
     */
    private $_google = null;

    /**
     * @description default controller method.
     * */
    public function execute()
    {
        try {
            $this->getAuthorization();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }

    /**
     * @description get google client object.
     * @return object $_google
     * */
    private function getGoogle()
    {
        if (!$this->_google) {
            $this->_google = $this->_getGoogle()->newGoogle();
        }

        return $this->_google;
    }

    /**
     * @description send request to google for login.
     * */
    public function getAuthorization()
    {
        try {
            $scope = [
                'https://www.googleapis.com/auth/userinfo.profile',
                'https://www.googleapis.com/auth/userinfo.email',
            ];
            $google = $this->getGoogle();
            $google->setScopes($scope);
            $authUrl = $google->createAuthUrl();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        die;
    }
}