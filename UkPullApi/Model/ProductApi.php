<?php
/**
 *
 * Copyright © 2018 Urjakart, All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\UkPullApi\Model;

use \Urjakart\UkPullApi\Api\ProductApiInterface;
use \Magento\Framework\App\ObjectManager;
use \Urjakart\UkPullApi\Model\Format;
use \Urjakart\UkPullApi\Model\Header;

class ProductApi implements ProductApiInterface
{
    const FAILED = 'failed';
    const SUCCESS = 'success';
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $connection;

    /**
     * @var \Urjakart\UkPullApi\Model\Format
     */
    private $_format;

    /**
     * @var \Urjakart\UkPullApi\Model\Header
     */
    private $_header;

    /**
     * Product Api constructor.
     */
    public function __construct()
    {
        $objectManager = ObjectManager::getInstance();
        $this->_format = new Format;
        $this->_header = new Header;
        $this->connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        date_default_timezone_set("Asia/Kolkata");
    }

    /**
     * {@inheritdoc}
     */
    public function getProductData($fromDate, $toDate, $offset, $page) {

        try {
            $this->validateData($fromDate, $toDate, $offset, $page);
            $this->_header->code = 'E000';
            $this->_header->status = self::SUCCESS;
            $this->_header->message = 'Successfully data retrieved.';
            $this->_format->header = $this->_header;
            $this->_format->body = $this->skuData($fromDate, $toDate, $offset, $page);
            return $this->_format;
        } catch (\Exception $e) {
            $this->_header->code = 'E' . sprintf('%03d', $e->getCode());
            $this->_header->status = self::FAILED;
            $this->_header->message = $e->getMessage();
            $this->_format->header = $this->_header;
            $this->_format->body = [];
            return $this->_format;
        }
    }

    /**
     * get the products data on the basis of date range
     *
     * @param int $fromDate from date timestamp.
     * @param int $toDate till date timestamp.
     * @param int $offset product per page.
     * @param int $page page number.
     * @return array products data.
     * @throws
     */
    private function skuData($fromDate, $toDate, $offset, $page) {

        $offset = $offset*3;
        try {
            $cpe = $this->connection->getTableName('catalog_product_entity');
            $cce = $this->connection->getTableName('catalog_category_entity');
            $ccp = $this->connection->getTableName('catalog_category_product');
            $ccev = $this->connection->getTableName('catalog_category_entity_varchar');
            $cpei = $this->connection->getTableName('catalog_product_entity_int');
            $cpev = $this->connection->getTableName('catalog_product_entity_varchar');

            $query = 'select distinct cpe.entity_id,';
            $query .= ' case when cce.level=2 then ccev.value end as category,';
            $query .= ' case when cce.level=3 then ccev.value end as sub_category,';
            $query .= ' case when cce.level=4 then ccev.value end as item_group,';
            $query .= ' cpev.value item_name,';
            $query .= ' cpe.sku item_code,';
            $query .= ' cpe.created_at created_date,';
            $query .= ' cpe.updated_at updated_date,';
            $query .= ' IF(cpei.value=1, "Y", "N") as active';
            $query .= ' from ' . $cpe . ' cpe';
            $query .= ' inner join ' . $ccp . ' ccp on ccp.product_id=cpe.entity_id';
            $query .= ' inner join ' . $cce . ' cce on ccp.category_id=cce.entity_id';
            $query .= ' inner join ' . $ccev . ' ccev on (ccev.entity_id=ccp.category_id and ccev.attribute_id=45 and ccev.store_id=0)';
            $query .= ' inner join ' . $cpei . ' cpei on (cpei.entity_id=cpe.entity_id and cpei.attribute_id=97 and cpei.store_id=0)';
            $query .= ' inner join ' . $cpev . ' cpev on (cpev.entity_id=cpe.entity_id and cpev.attribute_id=73 and cpev.store_id=0)';
            $query .= ' where cpe.updated_at >= "' . date("Y-m-d H:i:s", $fromDate) . '"';
            $query .= ' and cpe.updated_at <= "' . date("Y-m-d H:i:s", $toDate) . '"';
            $query .= ' and cce.level != 1 and cce.level != 5 limit ' . $page . ',' . $offset;
            $statement = $this->connection->prepare($query);
            $statement->execute();
            $data = $statement->fetchAll();
            $data = array_chunk($data, 3);
            foreach ($data as $key => $product) {
                $formattedData = [];
                foreach ($product as $item) {
                    if (!empty($item['category']))
                        $category = $item['category'];
                    if (!empty($item['sub_category']))
                        $subCategory = $item['sub_category'];
                    if (!empty($item['item_group']))
                        $itemGroup = $item['item_group'];
                }
                $formattedData['entity_id'] = $product[0]['entity_id'];
                $formattedData['sales_unit_width'] = 0;
                $formattedData['sales_unit_length'] = 0;
                $formattedData['sales_unit_height'] = 0;
                $formattedData['item_group'] = $itemGroup;
                $formattedData['category'] = $category;
                $formattedData['item_name'] = $product[0]['item_name'];
                $formattedData['item_code'] = $product[0]['item_code'];
                $formattedData['sub_category'] = $subCategory;
                $formattedData['created_date'] = $product[0]['created_date'];
                $formattedData['updated_date'] = $product[0]['updated_date'];;
                $formattedData['active'] = $product[0]['active'];
                $data[$key] = $formattedData;
            }

            return $data;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * validate the request data.
     *
     * @param int $fromDate from date timestamp.
     * @param int $toDate till date timestamp.
     * @param int $offset product per page.
     * @param int $page page number.
     * @throws
     */
    private function validateData($fromDate, $toDate, $offset, $page) {

        $allowed = strtotime(date("Y-m-d H:i:s") .' -3 months');
        if (!$this->isValidData($fromDate)) {
            throw new \Exception('From date timestamp in invalid',1);
        } elseif (!$this->isValidData($toDate)) {
            throw new \Exception('To date timestamp in invalid',1);
        } elseif (!$this->isValidData($offset)) {
            throw new \Exception('Offset value is invalid',1);
        } elseif (!$this->isValidData($page)) {
            throw new \Exception('Page number is invalid',1);
        } elseif ($allowed > $fromDate || $allowed > $toDate) {
            throw new \Exception('Data not accessible older than one week',2);
        }
    }

    /**
     * validate timestamp, offset, page number
     *
     * @param int $data
     * @return boolean true/false
     */
    private function isValidData($data) {
        return is_int(filter_var($data, FILTER_VALIDATE_INT)) && $data <= PHP_INT_MAX;
    }
}
