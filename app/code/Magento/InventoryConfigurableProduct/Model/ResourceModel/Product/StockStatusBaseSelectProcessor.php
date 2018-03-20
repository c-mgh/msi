<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\BaseSelectProcessorInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\DB\Select;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogInventory\Model\ResourceModel\Product\StockStatusBaseSelectProcessor as LegacyProcessor;

/**
 * Add stock item filter to selects.
 */
class StockStatusBaseSelectProcessor implements BaseSelectProcessorInterface
{
    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfig;

    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var LegacyProcessor
     */
    private $legacyProcessor;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param StockConfigurationInterface $stockConfig
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param LegacyProcessor $legacyProcessor
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        StockConfigurationInterface $stockConfig,
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        DefaultStockProviderInterface $defaultStockProvider,
        LegacyProcessor $legacyProcessor
    ) {
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->stockConfig = $stockConfig;
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->legacyProcessor = $legacyProcessor;
    }

    /**
     * @param Select $select
     * @return Select
     */
    public function process(Select $select)
    {
        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $stock = $this->stockResolver->get(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = (int)$stock->getStockId();

        if ($this->defaultStockProvider->getId() === $stockId) {
            $this->legacyProcessor->process($select);
        } elseif (!$this->stockConfig->isShowOutOfStock()) {
            $stockTable = $this->stockIndexTableNameResolver->execute($stockId);
            /** @var Select $select */
            $select->join(
                ['stock' => $stockTable],
                sprintf('stock.sku = %s.sku', BaseSelectProcessorInterface::PRODUCT_TABLE_ALIAS),
                []
            )->where('stock.is_salable = ?', 1);
        }

        return $select;
    }
}
