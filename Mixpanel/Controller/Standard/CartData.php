<?php
namespace Urjakart\Mixpanel\Controller\Standard;

use Magento\Framework\App\ObjectManager;

class CartData extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $_category;
    protected $_cart;
    protected $_quote;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Cart2Quote\Quotation\Model\Quote $quote
    ) {
        $this->resultJsonFactory = $resultJsonFactory;

        $objectManager = ObjectManager::getInstance();
        $this->_cart = $cart;
        $this->_product = $objectManager->get('Magento\Catalog\Model\Product');
        $this->_category = $objectManager->create('Magento\Catalog\Model\Category');
        $this->_quote = $quote;
        parent::__construct($context);
    }

    public function execute() {

        $params = [
            'id' => [],
            'name' => [],
            'sku' => [],
            'qty' => 0,
            'qtys' => 0,
            'brand' => '',
            'category' => '',
            'quoteTotal' => 0
        ];
        $sku = $this->getRequest()->getParam('sku');
        $quote = $this->getRequest()->getParam('quote');
        if ($sku) {
            if ($quote) {
                $items = $this->_quote->getItemsCollection();
                foreach($items as $item) {
                    array_push($params['id'], $item->getProductId());
                    array_push($params['name'], $item->getName());
                    array_push($params['sku'], $item->getSku());
                    $params['qtys'] += (int)$item->getQty();
                    $params['quoteTotal'] += (int)$item->getPrice() * $item->getQty();
                    if ($sku == $item->getSku()) {
                        $params['qty'] = (int)$item->getQty();
                        $params['price'] = (int)$item->getPrice() * $item->getQty();
                    }
                }
                if (!in_array($sku, $params['sku'])) {
                    array_push($params['sku'], $sku);
                }
                $product_id = $this->_product->getIdBySku($sku);
                $product = $this->_product->load($product_id);
                $params['brand'] = $product->getAttributeText('manufacturer');
                $categoryIds = $product->getCategoryIds();
                $category = $this->_category->load(end($categoryIds));
                $params['category'] = $category->getName();
            } else {
                $items = $this->_cart->getItems();
                foreach($items as $item) {
                    array_push($params['id'], $item->getProductId());
                    array_push($params['name'], $item->getName());
                    array_push($params['sku'], $item->getSku());
                    $params['qtys'] += (int)$item->getQty();
                    if ($sku == $item->getSku()) {
                        $params['qty'] = (int)$item->getQty();
                    }
                }
                if (!in_array($sku, $params['sku'])) {
                    array_push($params['sku'], $sku);
                }
                //$params['qtys'] += 1;
                $product_id = $this->_product->getIdBySku($sku);
                $product = $this->_product->load($product_id);
                $params['brand'] = $product->getAttributeText('manufacturer');
                $categoryIds = $product->getCategoryIds();
                $category = $this->_category->load(end($categoryIds));
                $params['category'] = $category->getName();
                $params['cartTotal'] += (int)$this->_cart->getQuote()->getGrandTotal();// + round($product->getPriceModel()->getFinalPrice(1, $product));
            }
        }

        return $this->resultJsonFactory->create()->setData($params);
    }
}
