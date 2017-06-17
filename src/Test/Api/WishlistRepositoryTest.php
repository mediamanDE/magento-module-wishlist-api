<?php
/**
 * @copyright: Copyright © 2017 mediaman GmbH. All rights reserved.
 * @see LICENSE.txt
 */

namespace Mediaman\WishlistApi\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Data\CustomerFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Class WishlistRepositoryTest
 * @package Mediaman\WishlistApi\Model
 */
class WishlistRepositoryTest extends WebapiAbstract
{

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private static $customerEmail = 'r.r4cc0n@example.com';

    /**
     * @var string
     */
    private static $customerPassword = 'Password123';

    /**
     * @var string
     */
    private static $productSku = 'laser-gun';

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @inheritdoc
     */
    public function tearDown()
    {
        /** @var Registry $registry */
        $registry = $this->objectManager->get(Registry::class);
        $registry->register('isSecureArea', true);

        // Rollback customer fixture

        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->get(static::$customerEmail);
        $customerRepository->delete($customer);

        // Rollback product fixture

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get(static::$productSku);
        $productRepository->delete($product);
    }

    /**
     * Load the test fixture
     */
    public static function loadFixture()
    {
        $objectManager = Bootstrap::getObjectManager();

        // Create customer fixture

        /** @var CustomerFactory $customerFactory */
        $customerFactory = $objectManager->get(CustomerFactory::class);

        /** @var AccountManagement $accountManagement */
        $accountManagement = $objectManager->get(AccountManagement::class);

        $customer = $customerFactory->create();
        $customer->setEmail(static::$customerEmail)
            ->setData('firstname', 'Rocket')
            ->setData('lastname', 'R4c00n');
        $accountManagement->createAccount($customer, static::$customerPassword);

        // Create product fixture

        /** @var ProductFactory $productFactory */
        $productFactory = $objectManager->get(ProductFactory::class);
        $product = $productFactory->create([
            'data' => [
                'name' => 'Laser gun',
                'sku' => static::$productSku,
                'price' => 19.99,
                'type_id' => Type::TYPE_SIMPLE,
                'attribute_set_id' => 4,
            ],
        ]);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $productRepository->save($product);
    }

    /**
     * Create a new customer account and return
     * the customer token
     *
     * @return string
     */
    private function getCustomerToken(): string
    {

        /** @var CustomerTokenServiceInterface $customerTokenService */
        $customerTokenService = $this->objectManager->get(CustomerTokenServiceInterface::class);
        $token = $customerTokenService->createCustomerAccessToken(static::$customerEmail, static::$customerPassword);

        return (string)$token;
    }

    /**
     * @magentoApiDataFixture loadFixture
     * @test GET /V1/wishlist
     */
    public function testGetCurrent()
    {
        $token = $this->getCustomerToken();

        $response = $this->_webApiCall([
            'rest' => [
                'resourcePath' => '/V1/wishlist',
                'httpMethod' => 'GET',
                'token' => $token,
            ],
        ]);

        $this->assertSame([
            'items_count' => 0,
            'items' => [],
        ], $response);
    }

    /**
     * @magentoApiDataFixture loadFixture
     * @test POST /V1/wishlist/:sku
     */
    public function testAddItem()
    {
        $token = $this->getCustomerToken();

        // Add a product to the customer wishlist

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get(static::$productSku);

        $response = $this->_webApiCall([
            'rest' => [
                'resourcePath' => '/V1/wishlist/' . static::$productSku,
                'httpMethod' => 'PUT',
                'token' => $token,
            ],
        ]);

        $this->assertTrue($response);

        // Check if the added product is in the customers wishlist
        $response = $this->_webApiCall([
            'rest' => [
                'resourcePath' => '/V1/wishlist',
                'httpMethod' => 'GET',
                'token' => $token,
            ],
        ]);

        $this->assertSame(1, $response['items_count']);
        $this->assertSame($product->getSku(), $response['items'][0]['product']['sku']);
    }

    /**
     * @magentoApiDataFixture loadFixture
     * @test DELETE /V1/wishlist/:itemId
     */
    public function testRemoveItem()
    {
        $token = $this->getCustomerToken();

        // Add a product to the customers wishlist
        $this->_webApiCall([
            'rest' => [
                'resourcePath' => '/V1/wishlist/' . static::$productSku,
                'httpMethod' => 'PUT',
                'token' => $token,
            ],
        ]);

        // Get the wishlist item id of the added product
        $response = $this->_webApiCall([
            'rest' => [
                'resourcePath' => '/V1/wishlist',
                'httpMethod' => 'GET',
                'token' => $token,
            ],
        ]);
        $itemId = $response['items'][0]['id'];

        // Remove the product from the customers wishlist
        $response = $this->_webApiCall([
            'rest' => [
                'resourcePath' => "/V1/wishlist/${itemId}",
                'httpMethod' => 'DELETE',
                'token' => $token,
            ],
        ]);

        $this->assertTrue($response);
    }

    /**
     * @magentoApiDataFixture loadFixture
     * @test DELETE /V1/wishlist/:itemId with invalid :itemId
     */
    public function testRemoveItemWithInvalidItemId()
    {
        $token = $this->getCustomerToken();

        $response = $this->_webApiCall([
            'rest' => [
                'resourcePath' => "/V1/wishlist/1",
                'httpMethod' => 'DELETE',
                'token' => $token,
            ],
        ]);

        $this->assertFalse($response);
    }
}
