<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Revokes all existing LoginAsCustomer tokens.
 */
class RevokeLoginAsCustomerTokens
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
     * Revoke all existing LoginAsCustomer tokens by customer id.
     *
     * @param int $customerId
     * @param int $integrationId
     * @param string $integrationTypeId
     */
    public function execute(int $customerId, int $integrationId, string $integrationTypeId): void
    {
        $tableName = $this->resourceConnection->getTableName('oauth_token');
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        $bind = [
            'revoked' => 1,
        ];

        $where = [
            'customer_id = ?' => $customerId,
            $integrationTypeId . ' = ?' => $integrationId,
            'revoked = 0',
        ];

        $connection->update(
            $tableName,
            $bind,
            $where
        );

        $connection->commit();
    }
}
