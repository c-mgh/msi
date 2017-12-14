<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Export;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection as AttributeCollection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Inventory\Model\ResourceModel\Source as SourceResourceModel;
use Magento\Inventory\Model\ResourceModel\SourceItem\Collection;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryImportExport\Model\Export\ColumnProviderInterface;
use Magento\InventoryImportExport\Model\Export\SourceItemCollectionFactoryInterface;
use Magento\ImportExport\Model\Export;

/**
 * @inheritdoc
 */
class SourceItemCollectionFactory implements SourceItemCollectionFactoryInterface
{
    /**
     * Source code field name
     */
    const SOURCE_CODE_FIELD = 'source_' . SourceInterface::CODE;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var FilterProcessorAggregator
     */
    private $filterProcessor;

    /**
     * @var ColumnProviderInterface
     */
    private $columnProvider;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param FilterProcessorAggregator $filterProcessor
     * @param ColumnProviderInterface $columnProvider
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        FilterProcessorAggregator $filterProcessor,
        ColumnProviderInterface $columnProvider,
        ResourceConnection $resourceConnection
    ) {
        $this->objectManager = $objectManager;
        $this->filterProcessor = $filterProcessor;
        $this->columnProvider = $columnProvider;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param AttributeCollection $attributeCollection
     * @param array $filters
     * @return Collection
     * @throws LocalizedException
     */
    public function create(AttributeCollection $attributeCollection, array $filters): Collection
    {
        /** @var Collection $collection */
        $collection = $this->objectManager->create(Collection::class);
        $columns = $this->columnProvider->getColumns($attributeCollection, $filters);
        if (($key = array_search(self::SOURCE_CODE_FIELD, $columns)) !== false) {
            unset($columns[$key]);
            $collection->join(
                ['s' => $this->resourceConnection->getTableName(SourceResourceModel::TABLE_NAME_SOURCE)],
                sprintf('s.%s = main_table.%s', SourceInterface::SOURCE_ID, SourceItemInterface::SOURCE_ID),
                ['source_code' => SourceInterface::CODE]
            );
            $collection->addFilterToMap('source_code', sprintf('s.%s', SourceInterface::CODE));
        }
        $collection->addFieldToSelect($columns);

        foreach ($this->retrieveFilterData($filters) as $columnName => $value) {
            $attributeDefinition = $attributeCollection->getItemById($columnName);
            if (!$attributeDefinition) {
                throw new LocalizedException(__(
                    'Given column name "%columnName" is not present in collection.',
                    ['columnName' => $columnName]
                ));
            }

            $type = $attributeDefinition->getData('backend_type');
            if (!$type) {
                throw new LocalizedException(__(
                    'There is no backend type specified for column "%columnName".',
                    ['columnName' => $columnName]
                ));
            }

            $this->filterProcessor->process($type, $collection, $columnName, $value);
        }
        return $collection;
    }

    /**
     * @param array $filters
     * @return array
     */
    private function retrieveFilterData(array $filters)
    {
        return array_filter(
            $filters[Export::FILTER_ELEMENT_GROUP] ?? [],
            function ($value) {
                return $value !== '';
            }
        );
    }
}