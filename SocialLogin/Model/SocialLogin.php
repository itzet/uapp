<?php

/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Model;

use Magento\Framework\App\Filesystem\DirectoryList;

class SocialLogin extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Resource collection
     *
     * @var \Magento\Framework\Data\Collection\AbstractDb
     */
    protected $_resourceCollection;

    /**
     * Store Manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * SocialLogin Helper
     *
     * @var \Urjakart\SocialLogin\Helper\Data
     */
    protected $_dataHelper;

    /**
     * Customer Model Factory
     *
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Filesystem
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $_directory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * SocialLogin constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Urjakart\SocialLogin\Helper\Data $dataHelper
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Urjakart\SocialLogin\Helper\Data $dataHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->_resource = $resource;
        $this->_resourceCollection = $resourceCollection;
        $this->_dataHelper = $dataHelper;
        $this->_request = $request;
        $this->_messageManager = $messageManager;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @description get directory absolute path.
     * @return string path of directory
     */
    public function _getBaseDir()
    {
        return $this->_directory->getAbsolutePath();
    }
}

