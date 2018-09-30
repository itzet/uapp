<?php
namespace Urjakart\Mixpanel\Controller\Standard;

use Magento\Framework\App\ObjectManager;

class Catalog extends \Magento\Framework\App\Action\Action
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
  
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $objectManager = ObjectManager::getInstance();
        $this->_product = $objectManager->get('Magento\Catalog\Model\Product');
        $this->_category = $objectManager->create('Magento\Catalog\Model\Category');
        parent::__construct($context);
    }

    public function execute() {
        $params = [
            'brand' => [],
            'categoryl1' => [],
            'categoryl2' => [],
            'categoryl3' => [],
            'price' => 0
        ];
        $sku = $this->getRequest()->getParam('sku');
        $qty = $this->getRequest()->getParam('qty');
        if (!empty($sku) && !empty($qty)) {
            $skuArr = explode('|', $sku);
            $qtyArr = explode('|', $qty);
            $prodArr = array_combine($skuArr, $qtyArr);
            foreach ($prodArr as $sku => $qty) {
                $product_id = $this->_product->getIdBySku($sku);
                $product = $this->_product->load($product_id);
                $brand = $product->getAttributeText('manufacturer');
                array_push($params['brand'], $brand);
                $categoryIds = $product->getCategoryIds();
                
                $i = 1;
                foreach($categoryIds as $category){
                    $cat = $this->_category->load($category);
                    if(!empty($cat->getName()) && $i < 4) {
                        $params["categoryl".$i] = $cat->getName();
                    }
                    $i++;
                }
                $params['price'] += (int)$product->getPriceModel()->getFinalPrice($qty, $product) * $qty;
            }
        }

        return $this->resultJsonFactory->create()->setData($params);
    }
}
