<?php
namespace Urjakart\Onlinepayments\Block;

class EmailInvoiceBlock extends \Magento\Framework\View\Element\Template
{
    private $params;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context)
    {
        parent::__construct($context);
    }

    public function checkAuth()
    {
        if ($this->getName()) {
            $this->params = $this->getRequest()->getParams();
            return true;
        } else {
            return false;
        }
    }

    public function getCustomerName()
    {
        $fName = !empty($this->params['firstname']) ? $this->params['firstname'] : '';
        $lName = !empty($this->params['lastname']) ? $this->params['lastname'] : '';

        return $fName . ' ' . $lName;
    }

    public function getPaymentStatus()
    {
        return !empty($this->params['status']) ? $this->params['status'] : 'failure';
    }

    public function getOrderNumber()
    {
        return !empty($this->params['productinfo']) ? $this->params['productinfo'] : '';
    }

    public function getHomeUrl()
    {
        return $this->getBaseUrl();
    }
}