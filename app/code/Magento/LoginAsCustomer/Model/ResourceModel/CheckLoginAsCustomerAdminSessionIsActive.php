<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Check logged-as-customer session is still active.
 */
class CheckLoginAsCustomerAdminSessionIsActive
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
     * Check logged-as-customer session is still active.
     *
     * @param string $customerId
     * @param string $adminSessionId
     * @return bool
     */
    public function execute(string $customerId, string $adminSessionId): bool
    {
        $tableName = $this->resourceConnection->getTableName('login_as_customer_log');
        $connection = $this->resourceConnection->getConnection();

        $query = $connection->select()
            ->from($tableName)
            ->where('customer_id = ?', $customerId)
            ->where('admin_session_id = ?', $adminSessionId)
            ->where('is_active = 1');

        $result = $connection->fetchRow($query);

        return false !== $result;
    }
}
