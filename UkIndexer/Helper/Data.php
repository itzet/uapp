<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\UkIndexer\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
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
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @description check enable or disable module
     * @return string
     */
    public function getButtonStatus()
    {
        return (boolean)$this->getConfig('general/enable_ukindexer');
    }

    /**
     * @param $key
     * @param null $store
     * @return mixed
     */
    private function getConfig($key, $store = null)
    {
        return $this->_scopeConfig->getValue(
            'ukindexer/' . $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}