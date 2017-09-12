<?php declare(strict_types=1);
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Framework\Write;

use Doctrine\DBAL\Connection;

class SqlGateway
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $uuid
     *
     * @return bool
     */
    public function exists(string $tableName, array $pkData): bool
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($tableName);

        foreach ($pkData as $pkDatum => $pkValue) {
            $qb->andWhere($pkDatum . '= :' . $pkDatum);
            $qb->setParameter($pkDatum, $pkValue);
        }

        $ret = (bool) $qb
            ->execute()
            ->fetchColumn();

        return $ret;
    }

    /**
     * @param array $data
     */
    public function insert(string $tableName, array $data): void
    {
        //        $this->connection->transactional(function() use ($data, $tableName) {
        $affectedRows = $this->connection->insert(
                $tableName,
                $data
            );

        if (!$affectedRows) {
            throw new ExceptionNoInsertedRecord('Unable to insert data');
        }
        //        });
    }

    /**
     * @param string $uuid
     * @param array  $data
     */
    public function update(string $tableName, array $uuid, array $data): void
    {
        //        $this->connection->transactional(function() use ($uuid, $data, $tableName) {
        $affectedRows = $this->connection->update(
                $tableName,
                $data,
                $uuid
            );

        if (0 === $affectedRows) {
            throw new ExceptionNoUpdatedRecord(sprintf('Unable to update "%s"::"%s" - no rows updated with %s', $tableName, print_r($uuid, true), print_r($data, true)));
            //                throw new ExceptionNoUpdatedRecord(sprintf('Unable to update "%s" - no rows updated', $uuid));
        }

        if ($affectedRows > 1) {
            throw new ExceptionMultipleUpdatedRecord(sprintf('Unable to update "%s" - multiple rows updated', $uuid));
        }
        //        });
    }
}