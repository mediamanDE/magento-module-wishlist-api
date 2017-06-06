<?php
/**
 * @copyright: Copyright © 2017 mediaman GmbH. All rights reserved.
 * @see LICENSE.txt
 */

namespace Mediaman\WishlistApi\Api;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface WishlistRepositoryInterface
 * @package Mediaman\WishlistApi\Api
 * @api
 */
interface WishlistRepositoryInterface
{

    /**
     * Get the current customers wishlist
     *
     * @return \Mediaman\WishlistApi\Api\WishlistInterface
     * @throws NoSuchEntityException
     */
    public function getCurrent(): WishlistInterface;

    /**
     * Add an item from the customers wishlist
     *
     * @param string $sku
     * @return bool
     */
    public function addItem(string $sku): bool;

    /**
     * Remove an item from the customers wishlist
     *
     * @param int $itemId
     * @return boolean
     * @throws NoSuchEntityException
     */
    public function removeItem(int $itemId): bool;
}
