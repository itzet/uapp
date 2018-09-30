<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\SocialLogin\Block;

use Urjakart\SocialLogin\Helper\Data;

class Toplinks extends Sociallogin
{
    /**
     * @description Get login redirect url for popup login case only.
     * @return string
     * */
    public function getLoginRedirectUrl()
    {
        $url = $this->_dataHelper->getConfig(Data::LOGIN_REDIRECT_PATH, $this->getStoreId());
        if ($url === 'current') {
            return $url;
        }
        return $this->getStore()->getUrl($url, ['_secure' => true]);
    }

    /**
     * @description Get register redirect url for popup register case only.
     * @return string
     * */
    public function getRegisterRedirectUrl()
    {
        $url = $this->_dataHelper->getConfig(Data::REGISTER_REDIRECT_PATH, $this->getStoreId());
        if ($url === 'current') {
            return $url;
        }
        return $this->getStore()->getUrl($url, ['_secure' => true]);
    }

    /**
     * @description Get login url for popup login case only.
     * @return string
     * */
    public function getPopupLoginUrl()
    {
        return $this->_storeManager->getStore()->getUrl('sociallogin/popup/login', ['_secure' => true]);
    }

    /**
     * @description Get forgot password url for popup forgot password case only.
     * @return string
     * */
    public function getPopupSendPass()
    {
        return $this->_storeManager->getStore()->getUrl('sociallogin/popup/sendpass', ['_secure' => true]);
    }

    /**
     * @description Get register url for popup register case only.
     * @return string
     * */
    public function getPopupCreateAcc()
    {
        return $this->_storeManager->getStore()->getUrl('sociallogin/popup/createacc', ['_secure' => true]);
    }

    /**
     * @description Get customer account page url.
     * @return string
     * */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getUrl('customer/account');
    }
}