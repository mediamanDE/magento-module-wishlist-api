<?php
/**
 * @copyright: Copyright © 2017 mediaman GmbH. All rights reserved.
 * @see LICENSE.txt
 */

namespace Mediaman\WishlistApi\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\ResourceModel\Item as ItemResource;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResource;
use Mediaman\WishlistApi\Api\WishlistInterface;

/**
 * Class WishlistRepositoryTest
 * @package Mediaman\WishlistApi
 */
class WishlistRepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpMock;

    /**
     * @var Token|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenMock;

    /**
     * @var TokenFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenFactoryMock;

    /**
     * @var WishlistResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wishlistResourceMock;

    /**
     * @var Wishlist|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wishlistMock;

    /**
     * @var WishlistInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wishlistFactoryMock;

    /**
     * @var ProductInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productMock;

    /**
     * @var ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepositoryMock;

    /**
     * @var Item|\PHPUnit_Framework_MockObject_MockObject
     */
    private $itemMock;

    /**
     * @var ItemResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $itemResourceMock;

    /**
     * @var CustomerSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSessionMock;

    /**
     * @var WishlistRepository
     */
    private $subject;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->httpMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'loadByToken',
                'getCustomerId',
            ])
            ->getMock();

        $this->tokenFactoryMock = $this->getMockBuilder(TokenFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->tokenFactoryMock->method('create')
            ->willReturn($this->tokenMock);

        $this->wishlistResourceMock = $this->getMockBuilder(WishlistResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->wishlistMock = $this->getMockBuilder(Wishlist::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wishlistMock->method('loadByCustomerId')
            ->willReturnSelf();
        $this->wishlistMock->method('getResource')
            ->willReturn($this->wishlistResourceMock);

        $this->wishlistFactoryMock = $this->getMockBuilder(WishlistFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->wishlistFactoryMock->method('create')
            ->willReturn($this->wishlistMock);

        $this->productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemResourceMock = $this->getMockBuilder(ItemResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = new WishlistRepository(
            $this->httpMock,
            $this->tokenFactoryMock,
            $this->wishlistFactoryMock,
            $this->productRepositoryMock,
            $this->itemResourceMock,
            $this->customerSessionMock
        );
    }

    /**
     * @test ::getCurrent
     */
    public function testGetCurrent()
    {
        $this->itShouldLoadAWishlist();

        $this->assertSame($this->wishlistMock, $this->subject->getCurrent());
    }

    /**
     * @test ::getCurrent with a customer without an existing wishlist
     */
    public function testGetCurrentNoPreExistingWishlist()
    {
        $customerIdMock = 42;
        $this->customerSessionMock->expects(static::once())
            ->method('getCustomerId')
            ->willReturn($customerIdMock);

        $this->wishlistMock->expects(static::once())
            ->method('loadByCustomerId')
            ->with($customerIdMock);

        $this->wishlistMock->expects(static::once())
            ->method('loadByCustomerId')
            ->with($customerIdMock);

        $this->wishlistMock->expects(static::once())
            ->method('getId')
            ->willReturn(null);
        $this->wishlistMock->expects(static::once())
            ->method('setCustomerId')
            ->with($customerIdMock);
        $this->wishlistResourceMock->expects(static::once())
            ->method('save')
            ->with($this->wishlistMock);

        $this->assertSame($this->wishlistMock, $this->subject->getCurrent());
    }

    /**
     * @test ::getCurrent without a customer session
     */
    public function testGetCurrentWithoutSession()
    {
        $tokenPayload = 'dnyuht5e0f6fgcwk7q90blkr88l4g3pa';
        $this->httpMock->expects(static::once())
            ->method('getHeader')
            ->with('Authorization')
            ->willReturn("Bearer ${tokenPayload}");

        $this->tokenMock->expects(static::once())
            ->method('loadByToken')
            ->with($tokenPayload);

        $customerIdMock = 42;
        $this->tokenMock->expects(static::once())
            ->method('getCustomerId')
            ->willReturn($customerIdMock);

        $this->assertSame($this->wishlistMock, $this->subject->getCurrent());
    }

    /**
     * @test ::addItem
     */
    public function testAddItem()
    {
        $sku = '24-MB01';
        $this->productRepositoryMock->expects(static::once())
            ->method('get')
            ->with($sku)
            ->willReturn($this->productMock);

        $this->itShouldLoadAWishlist();

        $this->wishlistMock->expects(static::once())
            ->method('addNewItem')
            ->with($this->productMock);

        $this->assertTrue($this->subject->addItem($sku));
    }

    /**
     * @test ::removeItem
     */
    public function testRemoveItem()
    {
        $this->itShouldLoadAWishlist();

        $itemId = 42;
        $this->wishlistMock->expects(static::once())
            ->method('getItem')
            ->with($itemId)
            ->willReturn($this->itemMock);

        $this->itemResourceMock->expects(static::once())
            ->method('delete')
            ->with($this->itemMock);

        $this->assertTrue($this->subject->removeItem($itemId));
    }

    /**
     * @test ::removeItem with invalid item id
     */
    public function testRemoveItemWithInvalidItemId()
    {
        $this->itShouldLoadAWishlist();

        $itemId = 42;
        $this->wishlistMock->expects(static::once())
            ->method('getItem')
            ->with($itemId)
            ->willReturn(false);

        $this->assertFalse($this->subject->removeItem($itemId));
    }

    /**
     * It should load a wishlist
     */
    private function itShouldLoadAWishlist()
    {
        $customerIdMock = 42;
        $this->customerSessionMock->expects(static::once())
            ->method('getCustomerId')
            ->willReturn($customerIdMock);

        $this->wishlistMock->expects(static::once())
            ->method('loadByCustomerId')
            ->with($customerIdMock);

        $this->wishlistMock->expects(static::once())
            ->method('loadByCustomerId')
            ->with($customerIdMock);

        $this->wishlistMock->expects(static::once())
            ->method('getId')
            ->willReturn(1);
    }
}
