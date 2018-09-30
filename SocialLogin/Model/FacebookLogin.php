<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Model;

class FacebookLogin extends SocialLogin
{
    /**
     * @description Retrieve facebook api object
     * @return object
     */
    public function newFacebook()
    {
        $facebook = null;
        try {
            $facebook = new \Facebook\Facebook(array(
                'app_id' => $this->_dataHelper->getFbAppId(),
                'app_secret' => $this->_dataHelper->getFbAppSecret(),
                'default_graph_version' => $this->_dataHelper->getFbAppVersion()
            ));
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $this->_messageManager->addErrorMessage($e->getMessage());
        }

        return $facebook;
    }

    /**
     * @description validate facebook login response.
     * @param string $code
     * @param string $state
     * @return array success or failure $response
     */
    public function validateLoginResponse($code, $state)
    {
        $response = [
            'token' => '',
            'error' => ''
        ];
        $fb = $this->newFacebook();
        $helper = $fb->getRedirectLoginHelper();
        $accessToken = null;
        try {
            $helper->getPersistentDataHandler()->set('state', $state);
            $helper->getPersistentDataHandler()->set('code', $code);
            $accessToken = $helper->getAccessToken();
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            $response['error'] .= 'Graph returned an error: ' . $e->getMessage();
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            $response['error'] .= 'Facebook SDK returned an error: ' . $e->getMessage();
        }
        if (!isset($accessToken)) {
            if ($helper->getError()) {
                $response['error'] .= "Error: " . $helper->getError();
                $response['error'] .= "Error Code: " . $helper->getErrorCode();
                $response['error'] .= "Error Reason: " . $helper->getErrorReason();
                $response['error'] .= "Error Description: " . $helper->getErrorDescription();
            } else {
                $response['error'] .= 'Bad request';
            }
        } else {
            $response['token'] = $accessToken->getValue();
        }

        if (!empty($response['error'])) {
            return $response;
        }

        $oAuth2Client = null;

        try {
            $oAuth2Client = $fb->getOAuth2Client();
            $tokenMetadata = $oAuth2Client->debugToken($accessToken);
            $tokenMetadata->validateAppId($this->_dataHelper->getFbAppId());
            $tokenMetadata->validateExpiration();
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $response['error'] .= $e->getMessage();
        }

        if (!$accessToken->isLongLived()) {
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                $response['error'] .= "Error getting long-lived access token: " . $e->getMessage();
            }
            $response['token'] = $accessToken->getValue();
        }

        return $response;
    }

    /**
     * @description Retrieve user data after success full login with facebook.
     * @param string $accessToken
     * @return array user data $user
     */
    public function getFbUser($accessToken)
    {
        $fb = $this->newFacebook();
        $user = [
            'user' => null,
            'error' => ''
        ];
        try {
            $response = $fb->get('/me?fields=first_name,last_name,middle_name,gender,email,location,hometown,work', $accessToken);
            $user['user'] = $response->getGraphUser();
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            $user['error'] .= 'Graph returned an error: ' . $e->getMessage();
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            $user['error'] .= 'Facebook SDK returned an error: ' . $e->getMessage();
        }

        return $user;
    }

    /**
     * @description Retrieve facebook login url.
     * @return string $loginUrl
     */
    public function getFbLoginUrl()
    {
        $facebook = $this->newFacebook();
        $helper = $facebook->getRedirectLoginHelper();
        $redirectUrl = $this->_dataHelper->getFacebookLoginRedirectUrl();
        $permissions = ['email']; // Optional permissions
        $loginUrl = $helper->getLoginUrl($redirectUrl, $permissions);

        return $loginUrl;
    }

    /**
     * @description Retrieve facebook logout url.
     * @return string $logoutUrl
     */
    public function getFbLogoutUrl()
    {
        $logoutUrl = [
            'url' => '',
            'error' => ''
        ];
        try {
            $facebook = $this->newFacebook();
            $helper = $facebook->getRedirectLoginHelper();
            $next = $this->_dataHelper->getFacebookLoginRedirectUrl();
            $logoutUrl['url'] = $helper->getLogoutUrl($_SESSION['token'], $next);
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $logoutUrl['error'] = $e->getMessage();
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $logoutUrl['error'] = $e->getMessage();
        }

        return $logoutUrl;
    }
}