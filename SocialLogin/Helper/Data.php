<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const LOGIN_REDIRECT_PATH = 'general/login_redirect_page';
    const REGISTER_REDIRECT_PATH = 'general/register_redirect_page';
    const FACEBOOK_APP_ID = 'fblogin/app_id';
    const FACEBOOK_APP_SECRET = 'fblogin/app_secret';
    const FACEBOOK_APP_VERSION = 'fblogin/app_version';
    const GOOGLE_CLIENT_KEY = 'gologin/consumer_key';
    const GOOGLE_CLIENT_SECRET = 'gologin/consumer_secret';
    const LINKED_IN_CLIENT_ID = 'linklogin/client_id';
    const LINKED_IN_CLIENT_SECRET = 'linklogin/client_secret';

    /**
     * Store manager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * ScopeConfig
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Url
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory
    )
    {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->_url = $context->getUrlBuilder();
        $this->_logger  =  $context->getLogger();
        parent::__construct($context);
    }

    /**
     * @description Retrieve facebook app id
     * @return string
     */
    public function getFbAppId()
    {
        return trim($this->getConfig(self::FACEBOOK_APP_ID));
    }

    /**
     * @description Retrieve facebook app secret
     * @return string
     */
    public function getFbAppSecret()
    {
        return trim($this->getConfig(self::FACEBOOK_APP_SECRET));
    }

    /**
     * @description Retrieve facebook app version
     * @return string
     */
    public function getFbAppVersion()
    {
        return trim($this->getConfig(self::FACEBOOK_APP_VERSION));
    }

    /**
     * @description Retrieve google client id
     * @return string
     */
    public function getGoogleClientId()
    {
        return trim($this->getConfig(self::GOOGLE_CLIENT_KEY));
    }

    /**
     * @description Retrieve google client secret
     * @return string
     */
    public function getGoogleClientSecret()
    {
        return trim($this->getConfig(self::GOOGLE_CLIENT_SECRET));
    }

    /**
     * @description Retrieve Linked-in client key
     * @return string
     */
    public function getLinkedInClientKey()
    {
        return trim($this->getConfig(self::LINKED_IN_CLIENT_ID));
    }

    /**
     * @description Retrieve Linked-in client secret
     * @return string
     */
    public function getLinkedInClientSecret()
    {
        return trim($this->getConfig(self::LINKED_IN_CLIENT_SECRET));
    }

    /**
     * @param $key
     * @param null $store
     * @return mixed
     */
    public function getConfig($key, $store = null)
    {
        return $this->_scopeConfig->getValue(
            'sociallogin/' . $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @description get facebook login redirect url
     * @return string
     */
    public function getFacebookLoginRedirectUrl() {
        return rtrim($this->_getUrl('sociallogin/sociallogin/facebooklogin'), '/');
    }

    /**
     * @description get facebook logout url
     * @return string
     */
    public function getFacebookLogoutUrl() {
        return $this->_getUrl('/',
            [
                '_secure' => true,
                '_use_rewrite' => true
            ]);
    }

    /**
     * @description get account url of user after login
     * @return string
     */
    public function getAccountEditUrl() {
        return $this->_getUrl('customer/account/edit',
            [
                '_secure' => true,
                '_use_rewrite' => true
            ]);
    }

    /**
     * @description get google response url after success-full login
     * @return string
     */
    public function getGoogleRedirectUrl() {
        return $this->_getUrl('sociallogin/googlelogin/user');
    }

    /**
     * @description get linked-in login redirect url
     * @return string
     */
    public function getLinkedInLoginRedirectUrl() {
        return $this->_getUrl('sociallogin/sociallogin/linkedinlogin');
    }
}