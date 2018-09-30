<?php

/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Controller;

abstract class SocialLogin extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_customerHelperData;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Urjakart\SocialLogin\Helper\Data
     */
    protected $_helperData;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerSocialCollectionFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Customer\Model\Registration
     */
    protected $_registration;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $_accountManagement;
    
    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Urjakart\SocialLogin\Helper\Data $helperData
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Registration $registration
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Urjakart\SocialLogin\Helper\Data $helperData,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Registration $registration,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Action\Context $context
    )
    {
        $this->_customerSession = $customerSession;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_helperData = $helperData;
        $this->_objectManager = $context->getObjectManager();
        $this->_customerFactory = $customerFactory;
        $this->_accountManagement = $accountManagement;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_registration = $registration;
        parent::__construct($context);
    }

    /**
     * @description Retrieve customer session model object
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * @description Retrieve customer registration model object
     * @return \Magento\Customer\Model\Registration
     */
    protected function _getRegistration()
    {
        return $this->_registration;
    }

    /**
     * @description Retrieve Facebook model object
     * @return \Urjakart\SocialLogin\Model\FacebookLogin
     */
    protected function _getFacebook()
    {
        return $this->_objectManager->get('Urjakart\SocialLogin\Model\FacebookLogin');
    }

    /**
     * @description Retrieve Google model object
     * @return \Urjakart\SocialLogin\Model\GoogleLogin
     */
    protected function _getGoogle()
    {
        return $this->_objectManager->get('Urjakart\SocialLogin\Model\GoogleLogin');
    }

    /**
     * @description Retrieve Linked-in model object
     * @return \Urjakart\SocialLogin\Model\LinkedInLogin
     */
    protected function _getLinkedIn()
    {
        return $this->_objectManager->get('Urjakart\SocialLogin\Model\LinkedInLogin');
    }

    /**
     * @description validate token request
     * @param string $token.
     * @param string $data.
     * @param string $key.
     * @return boolean true/false.
     * */
    protected function tokenValidate($token, $data, $key)
    {
        $isValidated = false;
        if (!empty($token) && !empty($data) && !empty($key)) {
            $codeArr = json_decode(base64_decode($token), true);
            $x = trim($data);
            $y = trim($key);
            $j = 0;
            $e = str_split($x);
            $k = str_split($y);
            for ($i=0; $i < strlen($x); $i++) {
                if (!isset($k[$j])) {
                    $j = 0;
                    $p = ord($k[$j]);
                }
                $p = ord($k[$j]);
                $p = $p % 9;
                $v = ord($e[$i]);
                $v = $v << $p;
                $j++;
                if ($codeArr[$i] === $v) {
                    $isValidated = true;
                } else {
                    $isValidated = false;
                    break;
                }
            }
        }

        return $isValidated;
    }

    /**
     * @description Retrieve current database connection
     * @return \Magento\Framework\App\ResourceConnection
     */
    protected function _getDbConnection()
    {
        return $this->_objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
    }

    /**
     * @description Retrieve store id
     * @return int
     */
    protected function _getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @description show error in social login popup.
     * @param $error
     * */
    protected function _error($error) {
        if (isset($_SESSION['is_logged_in']))
            unset($_SESSION['is_logged_in']);
        echo '<div style="color:red;">' . $error . '</div>';
        exit;
    }

    /**
     * @description validate email if provide in popup of facebook/linked-in externally.
     * */
    protected function validateExtEmail()
    {
        $email = $this->getRequest()->getParam('getEmail');
        if (!\Zend_Validate::is($email, 'NotEmpty')) {
            $error = 'Email must not empty';
            echo $this->emailHtml($error);
            exit;
        } elseif (!\Zend_Validate::is($email, 'EmailAddress')) {
            $error = 'Please enter a valid email address.';
            echo $this->emailHtml($error);
            exit;
        }
    }
}