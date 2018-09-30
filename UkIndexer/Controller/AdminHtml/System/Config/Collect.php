<?php
/**
 * Copyright ©2017 Urjakart. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Urjakart\UkIndexer\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Collect extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Collect relations data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $isEnabled = $this->_objectManager->get('Urjakart\UkIndexer\Helper\Data')->getButtonStatus();
        $count = 0;
        $msg = '';
        if ($isEnabled && $this->getRequest()->getParam('isAjax')) {
            try {
                $count = $this->executeData();
            } catch (\Exception $e) {
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $msg = $e->getMessage();
            }
        } else {
            $msg = 'Please enable the module first or request not authorize!';
        }
        $result = $this->resultJsonFactory->create();

        return $result->setData(['success' => true, 'count' => $count, 'error' => $msg]);
    }

    /**
     * For controller authorization.
    */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Urjakart_UkIndexer::config');
    }

    /**
     * @description update attribute of product for filter on front
     * store
     * @return int record count
     * @throws $e
     */
    private function executeData() {
        $r = [];
        try {
            $connection = $this->_objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
            $t_cpev = $connection->getTableName('catalog_product_entity_varchar');
            $t_cpe = $connection->getTableName('catalog_product_entity');
            $t_eag = $connection->getTableName('eav_attribute_group');
            $t_eea = $connection->getTableName('eav_entity_attribute');
            $query = 'select entity_id, attribute_id from ' . $t_cpev . ' where value not like "%__deleted"';
            $data = $connection->fetchAll($query);
            $i = 0;
            foreach ($data as $val) {
                $q = 'select cpe.entity_id, eea.attribute_id, eag.attribute_group_id from ';
                $q .= $t_cpe .' cpe inner join ' . $t_eag . ' eag on cpe.attribute_set_id = eag.attribute_set_id ';
                $q .= 'inner join ' . $t_eea . ' eea on eag.attribute_set_id = eea.attribute_set_id where ';
                $q .= 'cpe.entity_id = ' . $val["entity_id"] . ' and eea.attribute_id=' . $val["attribute_id"];
                $d = $connection->fetchAll($q);
                if (!count($d)) {
                    $r[$i]['entity_id'] = $val['entity_id'];
                    $r[$i]['attribute_id'] = $val['attribute_id'];
                    $i++;
                }
            }
            // Log array of product and attribute in system.log
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical('Ashish : ' . print_r($r, 1));
            if (count($r)) {
                foreach($r as $val) {
                    $updateQuery = 'update ' . $t_cpev . ' set value=concat(value, "__deleted") ';
                    $updateQuery .= 'where entity_id=' . $val['entity_id'] . ' and attribute_id=';
                    $updateQuery .= $val['attribute_id'];
                    $connection->query($updateQuery);
                }
            }
            $indexerRegistry = $this->_objectManager->create('\Magento\Framework\Indexer\IndexerRegistry');
            $indexerCcp =  $indexerRegistry->get('catalog_category_product');
            $indexerCcp->invalidate();
            $indexerCcp->reindexAll();
            $indexerCpa = $indexerRegistry->get('catalog_product_attribute');
            $indexerCpa->invalidate();
            $indexerCpa->reindexAll();
          //  $this->_objectManager->get('Magento\Framework\App\State\CleanupFiles')->clearMaterializedViewFiles();
            $_cacheFrontendPool = $this->_objectManager->get('Magento\Framework\App\Cache\Frontend\Pool');
            foreach ($_cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return count($r);
    }
}
?>
