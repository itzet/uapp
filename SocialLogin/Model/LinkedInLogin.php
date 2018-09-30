<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\SocialLogin\Model;

class LinkedInLogin extends SocialLogin
{
    /**
     * @description Retrieve linked-in client api object
     * @return object
     */
    public function newLinkedIn()
    {
        $linkedIn = null;
        try {
            $linkedIn = new \Happyr\LinkedIn\LinkedIn(
                $this->_dataHelper->getLinkedInClientKey(),
                $this->_dataHelper->getLinkedInClientSecret()
            );
        } catch (\Exception $e) {
            $this->_messageManager->addErrorMessage($e->getMessage());
        }

        return $linkedIn;
    }

    /**
     * @description Retrieve linked-in login url.
     * @return string $loginUrl
     */
    public function getLinkedInLoginUrl()
    {
        $linkedIn = $this->newLinkedIn();
        $scope = 'r_basicprofile,r_emailaddress';
        $redirectUrl = $this->_dataHelper->getLinkedInLoginRedirectUrl();
        $params = ['redirect_uri' => $redirectUrl, 'scope' => $scope];
        $loginUrl = $linkedIn->getLoginUrl($params);

        return $loginUrl;
    }

    /**
     * @description Set request_uri and state of response data in client api.
     * @param $state
     * @return array $isSetData
     */
    public function setLinkedResponseData($state)
    {
        $isSetData = [
            'success' => false,
            'error' => ''
        ];
        try {
            $storage = new \Happyr\LinkedIn\Storage\SessionStorage();
            $storage->set('state', $state);
            $storage->set('redirect_uri', $this->_dataHelper->getLinkedInLoginRedirectUrl());
            $isSetData['success'] = true;
        } catch (\Exception $e) {
            $isSetData['error'] = $e->getMessage();
        }

        return $isSetData;
    }
}