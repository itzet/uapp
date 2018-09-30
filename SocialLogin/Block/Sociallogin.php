<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Block;

class Sociallogin extends \Magento\Framework\View\Element\Template
{
    /**
     * @description configuration constant.
     */
    const XML_PATH_SOCIAL_ENABLE = 'general/enable_socials';
    const XML_PATH_FBLOGIN_ACTIVE = 'fblogin/is_active';
    const XML_PATH_GOLOGIN_ACTIVE = 'gologin/is_active';
    const XML_PATH_LINKLOGIN_ACTIVE = 'linklogin/is_active';
    const XML_PATH_FBLOGIN_ORDER = 'fblogin/sort_order';
    const XML_PATH_GOLOGIN_ORDER = 'gologin/sort_order';
    const XML_PATH_LINKLOGIN_ORDER = 'linklogin/sort_order';

    /**
     * @var \Urjakart\SocialLogin\Helper\Data
     */
    protected $_dataHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlInterface;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Urjakart\SocialLogin\Helper\Data $dataHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Urjakart\SocialLogin\Helper\Data $dataHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_urlInterface = $context->getUrlBuilder();
        $this->_objectManager = $objectManager;
        $this->_storeManager = $context->getStoreManager();
        $this->_customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * @overriden Render block HTML
     * @return string
     */
    protected function _beforeToHtml()
    {
        if (!$this->getIsActive()) {
            $this->setTemplate(null);
        }

        if ($this->_getSession()->isLoggedIn()) {
            $this->setTemplate(null);
        }

        return parent::_beforeToHtml();
    }

    /**
     * @description Facebook model object
     * @return object
     * */
    protected function getFbModel()
    {
        return $this->_objectManager->get('Urjakart\SocialLogin\Model\FacebookLogin');
    }

    /**
     * @description Linked-in model object
     * @return object
     * */
    protected function getLinkedInModel()
    {
        return $this->_objectManager->get('Urjakart\SocialLogin\Model\LinkedInLogin');
    }

    /**
     * @description Customer Session Model
     * @return object
     * */
    public function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * @description Current url from url interface
     * @return string
     * */
    public function getCurrentUrl()
    {
        return $this->_urlInterface->getCurrentUrl();
    }

    /**
     * @description Current url from url interface
     * @return string
     * */
    public function getIsActive()
    {
        return (boolean)$this->_dataHelper->getConfig(self::XML_PATH_SOCIAL_ENABLE, $this->getStoreId());
    }

    /**
     * @description Facebook social login button show or not
     * @return boolean true/false
     * */
    public function isShowFaceBookButton()
    {
        return (boolean)$this->_dataHelper->getConfig(self::XML_PATH_FBLOGIN_ACTIVE, $this->getStoreId());
    }

    /**
     * @description Google social login button show or not
     * @return boolean true/false
     * */
    public function isShowGoogleButton()
    {
        return (boolean)$this->_dataHelper->getConfig(self::XML_PATH_GOLOGIN_ACTIVE, $this->getStoreId());
    }

    /**
     * @description Linked-in social login button show or not
     * @return boolean true/false
     * */
    public function isShowLinkedButton()
    {
        return (boolean)$this->_dataHelper->getConfig(self::XML_PATH_LINKLOGIN_ACTIVE, $this->getStoreId());
    }

    /**
     * @description Facebook social login button Html
     * @return string
     * */
    public function getFacebookButton()
    {
        return $this->getLayout()->createBlock('Urjakart\SocialLogin\Block\Fblogin')
            ->setTemplate('Urjakart_SocialLogin::bt_fblogin.phtml')->toHtml();

    }

    /**
     * @description Google social login button Html
     * @return string
     * */
    public function getGoogleButton()
    {
        return $this->getLayout()->createBlock('Urjakart\SocialLogin\Block\Gologin')
            ->setTemplate('Urjakart_SocialLogin::bt_gologin.phtml')->toHtml();

    }

    /**
     * @description Linked-in social login button Html
     * @return string
     * */
    public function getLinkedButton()
    {
        return $this->getLayout()->createBlock('Urjakart\SocialLogin\Block\Linklogin')
            ->setTemplate('Urjakart_SocialLogin::bt_linkedlogin.phtml')->toHtml();
    }

    /**
     * @description Facebook social login button position order
     * @return int
     * */
    public function sortOrderFaceBook()
    {
        return (int)$this->_dataHelper->getConfig(self::XML_PATH_FBLOGIN_ORDER);
    }

    /**
     * @description Google social login button position order
     * @return int
     * */
    public function sortOrderGoogle()
    {
        return (int)$this->_dataHelper->getConfig(self::XML_PATH_GOLOGIN_ORDER);
    }

    /**
     * @description Linked-in social login button position order
     * @return int
     * */
    public function sortOrderLinked()
    {
        return (int)$this->_dataHelper->getConfig(self::XML_PATH_LINKLOGIN_ORDER);
    }

    /**
     * @description Social login button with sorted order
     * @return array
     * */
    public function makeArrayButton()
    {
        $buttonArray = array();

        if ($this->isShowFaceBookButton()) {
            $buttonArray[] = [
                'button' => $this->getFacebookButton(),
                'check' => $this->isShowFaceBookButton(),
                'id' => 'bt-loginfb',
                'sort' => $this->sortOrderFaceBook(),
            ];
        }

        if ($this->isShowGoogleButton()) {
            $buttonArray[] = [
                'button' => $this->getGoogleButton(),
                'check' => $this->isShowGoogleButton(),
                'id' => 'bt-logingo',
                'sort' => $this->sortOrderGoogle(),
            ];
        }

        if ($this->isShowLinkedButton()) {
            $buttonArray[] = [
                'button' => $this->getLinkedButton(),
                'check' => $this->isShowLinkedButton(),
                'id' => 'bt-loginlinked',
                'sort' => $this->sortOrderLinked(),
            ];
        }
        usort($buttonArray, [$this, 'compareSortOrder']);

        return $buttonArray;
    }

    /**
     * @description sort the order
     * @param array $a
     * @param array $b
     * @return int 1/-1
     * */
    public function compareSortOrder($a, $b)
    {
        if ($a['sort'] == $b['sort']) {
            return 0;
        }

        return $a['sort'] < $b['sort'] ? -1 : 1;
    }

    /**
     * @description Get store id
     * @return int
     * */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @description Get store object
     * @return object \Magento\Store\Api\Data\StoreInterface
     * */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    /**
     * @description Get login url for social login cases only.
     * @return string
     * */
    public function getCheckLoginUrl()
    {
        return $this->getUrl('sociallogin/sociallogin/checklogin');
    }

    /**
     * @description check the social login buttons on checkout page or not.
     * @return string
     * */
    public function isCheckoutPage()
    {
        if (strpos($_SERVER['REQUEST_URI'], 'checkout') > 0)
            return 'style="display:none"';
        else
            return 'style="display:block"';
    }
}