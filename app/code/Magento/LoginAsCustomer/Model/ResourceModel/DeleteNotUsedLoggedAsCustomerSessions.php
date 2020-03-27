<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\LoginAsCustomer\Model\Login;

/**
 * Delete not used logged-as-customer sessions.
 */
class DeleteNotUsedLoggedAsCustomerSessions
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param ResourceConnection $resourceConnection
     * @param DateTime $dateTime
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DateTime $dateTime
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dateTime = $dateTime;
    }

    /**
     * Delete not used logged-as-customer sessions.
     */
    public function execute(): void
    {
        $tableName = $this->resourceConnection->getTableName('login_as_customer_log');
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        $where = [
            'created_at < ?' => $this->getDateTimePoint(),
            'used' => 0,
        ];

        $connection->update(
            $tableName,
            $where
        );

        $connection->commit();
    }

    /**
     * Retrieve login datetime point.
     *
     * @return string
     */
    private function getDateTimePoint(): string
    {
        return date('Y-m-d H:i:s', $this->dateTime->gmtTimestamp() - Login::TIME_FRAME);
    }
}
