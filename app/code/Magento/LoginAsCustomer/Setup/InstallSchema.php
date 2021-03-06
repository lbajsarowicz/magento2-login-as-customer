<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomer\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Login as customer setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'login_as_customer_log'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('login_as_customer_log')
        )->addColumn(
            'login_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Admin Login ID'
        )->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Customer ID'
        )->addColumn(
            'admin_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Admin ID'
        )->addColumn(
            'secret',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64',
            ['nullable' => true],
            'Login Secret'
        )->addColumn(
            'used',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => '1'],
            'Is Login Used'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [],
            'Creation Time'
        )->addIndex(
            $installer->getIdxName('login_as_customer_log', ['customer_id']),
            ['customer_id']
        )
        ->addIndex(
            $installer->getIdxName('login_as_customer_log', ['admin_id']),
            ['admin_id']
        )->setComment(
            'Magento Login As Customer Table'
        );
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
