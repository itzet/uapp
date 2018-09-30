<?php

/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\SocialLogin\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        /*
         * Create table uk_facebook_customer
         */
        if (!$installer->tableExists('uk_facebook_customer')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('uk_facebook_customer')
                )->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'facebook users entity id'
                )->addColumn(
                    'facebook_id',
                    Table::TYPE_BIGINT,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Facebook User Id'
                )->addColumn(
                    'customer_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Urjakart Customer Id'
                )->addColumn(
                    'email',
                    Table::TYPE_TEXT,
                    55,
                    ['nullable' => false, 'primary' => true],
                    'Customer email'
                )->addColumn(
                    'access_token',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => null],
                    'Facebook access token'
                )->addColumn(
                    'first_name',
                    Table::TYPE_TEXT,
                    30,
                    ['nullable' => true, 'default' => null],
                    'Customer First Name'
                )->addColumn(
                    'last_name',
                    Table::TYPE_TEXT,
                    30,
                    ['nullable' => true, 'default' => null],
                    'Customer Last Name'
                )->addColumn(
                    'register_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Customer Register Date'
                )->addIndex(
                    $installer->getIdxName('uk_facebook_customer', ['entity_id']),
                    ['entity_id']
                )->addForeignKey(
                    $installer->getFkName(
                        'uk_facebook_customer',
                        'customer_id',
                        'customer_entity',
                        'entity_id'
                    ),
                    'customer_id',
                    $installer->getTable('customer_entity'),
                    'entity_id',
                    Table::ACTION_CASCADE
                )->setComment(
                'Facebook Social Login Customer Table'
            );
            $installer->getConnection()->createTable($table);
        }

        /*
         * Create table uk_google_customer
         */
        if (!$installer->tableExists('uk_google_customer')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('uk_google_customer')
            )->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'google users entity id'
            )->addColumn(
                'google_id',
                Table::TYPE_BIGINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Google User Id'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Urjakart Customer Id'
            )->addColumn(
                'email',
                Table::TYPE_TEXT,
                55,
                ['nullable' => false, 'primary' => true],
                'Customer email'
            )->addColumn(
                'access_token',
                Table::TYPE_TEXT,
                2255,
                ['nullable' => true, 'default' => null],
                'Google access token'
            )->addColumn(
                'first_name',
                Table::TYPE_TEXT,
                30,
                ['nullable' => true, 'default' => null],
                'Customer First Name'
            )->addColumn(
                'last_name',
                Table::TYPE_TEXT,
                30,
                ['nullable' => true, 'default' => null],
                'Customer Last Name'
            )->addColumn(
                'register_date',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Customer Register Date'
            )->addIndex(
                $installer->getIdxName('uk_google_customer', ['entity_id']),
                ['entity_id']
            )->addForeignKey(
                $installer->getFkName(
                    'uk_google_customer',
                    'customer_id',
                    'customer_entity',
                    'entity_id'
                ),
                'customer_id',
                $installer->getTable('customer_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            )->setComment(
                'Google Social Login Customer Table'
            );
            $installer->getConnection()->createTable($table);
        }

        /*
         * Create table uk_linked_in_customer
         */
        if (!$installer->tableExists('uk_linked_in_customer')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('uk_linked_in_customer')
            )->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'linked-in users entity id'
            )->addColumn(
                'linked_in_id',
                Table::TYPE_TEXT,
                22,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Linked-in User Id'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Urjakart Customer Id'
            )->addColumn(
                'email',
                Table::TYPE_TEXT,
                55,
                ['nullable' => false, 'primary' => true],
                'Customer email'
            )->addColumn(
                'access_token',
                Table::TYPE_TEXT,
                2255,
                ['nullable' => true, 'default' => null],
                'Linked-in access token'
            )->addColumn(
                'first_name',
                Table::TYPE_TEXT,
                30,
                ['nullable' => true, 'default' => null],
                'Customer First Name'
            )->addColumn(
                'last_name',
                Table::TYPE_TEXT,
                30,
                ['nullable' => true, 'default' => null],
                'Customer Last Name'
            )->addColumn(
                'register_date',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Customer Register Date'
            )->addIndex(
                $installer->getIdxName('uk_linked_in_customer', ['entity_id']),
                ['entity_id']
            )->addForeignKey(
                $installer->getFkName(
                    'uk_linked_in_customer',
                    'customer_id',
                    'customer_entity',
                    'entity_id'
                ),
                'customer_id',
                $installer->getTable('customer_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            )->setComment(
                'Linked-in Social Login Customer Table'
            );
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}