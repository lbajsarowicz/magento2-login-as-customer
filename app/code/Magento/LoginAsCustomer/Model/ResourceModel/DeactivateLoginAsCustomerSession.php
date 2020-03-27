<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Deactivate all login as customer sessions for current admin user.
 */
class DeactivateLoginAsCustomerSession
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Deactivate all login as customer sessions for current admin user.
     *
     * @param int $adminId
     */
    public function execute(int $adminId): void
    {
        $tableName = $this->resourceConnection->getTableName('login_as_customer_log');
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        $bind = [
            'is_active' => 0,
        ];

        $where = [
            'admin_id = ? ' => $adminId,
            'is_active' => 1,
        ];

        $connection->update(
            $tableName,
            $bind,
            $where
        );

        $connection->commit();
    }
}
