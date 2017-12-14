<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Inventory\Model\Source\Command\GetInterface;
use Magento\Inventory\Model\Source\Command\GetByCodeInterface;
use Magento\Inventory\Model\Source\Command\GetListInterface;
use Magento\Inventory\Model\Source\Command\SaveInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * @inheritdoc
 */
class SourceRepository implements SourceRepositoryInterface
{
    /**
     * @var SaveInterface
     */
    private $commandSave;

    /**
     * @var GetInterface
     */
    private $commandGet;

    /**
     * @var GetByCodeInterface
     */
    private $commandGetByCode;

    /**
     * @var GetListInterface
     */
    private $commandGetList;

    /**
     * @param SaveInterface $commandSave
     * @param GetInterface $commandGet
     * @param GetListInterface $commandGetList
     */
    public function __construct(
        SaveInterface $commandSave,
        GetInterface $commandGet,
        GetByCodeInterface $commandGetByCode,
        GetListInterface $commandGetList
    ) {
        $this->commandSave = $commandSave;
        $this->commandGet = $commandGet;
        $this->commandGetByCode = $commandGetByCode;
        $this->commandGetList = $commandGetList;
    }

    /**
     * @inheritdoc
     */
    public function save(SourceInterface $source): int
    {
        return $this->commandSave->execute($source);
    }

    /**
     * @inheritdoc
     */
    public function get(int $sourceId): SourceInterface
    {
        return $this->commandGet->execute($sourceId);
    }

    /**
     * @inheritdoc
     */
    public function getByCode(string $code): SourceInterface
    {
        return $this->commandGetByCode->execute($code);
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null): SourceSearchResultsInterface
    {
        return $this->commandGetList->execute($searchCriteria);
    }
}