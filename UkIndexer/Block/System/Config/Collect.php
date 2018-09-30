<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Urjakart\UkIndexer\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Urjakart\UkIndexer\Helper\Data;

class Collect extends Field
{
    /**
     * @var \Urjakart\UkIndexer\Helper\Data
     */
    protected $_helper;

    /**
     * @var string
     */
    protected $_template = 'Urjakart_UkIndexer::system/config/collect.phtml';

    /**
     * @param Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('ukindexer/system_config/collect');
    }

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        if ($this->_helper->getButtonStatus()) {
            $button = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Button'
            )->setData(
                [
                    'id' => 'collect_button',
                    'label' => __('Run')
                ]
            );
        } else {
            $button = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Button'
            )->setData(
                [
                    'id' => 'collect_button',
                    'label' => __('Run'),
                    'disabled' => 'disabled'
                ]
            );
        }

        return $button->toHtml();
    }
}