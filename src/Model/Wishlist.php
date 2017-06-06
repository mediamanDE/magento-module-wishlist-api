<?php
/**
 * @copyright: Copyright © 2017 mediaman GmbH. All rights reserved.
 * @see LICENSE.txt
 */

namespace Mediaman\WishlistApi\Model;

use Mediaman\WishlistApi\Api\WishlistInterface;

/**
 * Class Wishlist
 * @package Mediaman\WishlistApi\Model
 */
class Wishlist extends \Magento\Wishlist\Model\Wishlist implements WishlistInterface
{

    /**
     * @inheritdoc
     */
    public function getItems()
    {
        return $this->getItemCollection()->getItems();
    }
}
