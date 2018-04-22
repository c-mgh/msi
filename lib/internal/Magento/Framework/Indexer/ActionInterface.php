<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Indexer;

/**
 * @api Implement custom Action Interface
 */
interface ActionInterface
{
    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull(): void;

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     * @throws \Magento\Framework\Exception\StateException
     */
    public function executeList(array $ids): void;

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @return void
     * @throws \Magento\Framework\Exception\StateException
     */
    public function executeRow($id): void;
}
