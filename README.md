# Wishlist API

Adds an API to interact with the Magento2 wishlist.

![Demo GIF](https://raw.githubusercontent.com/mediamanDE/magento-module-wishlist-api/master/demo.gif)

## Getting Started

Install the module via composer

```
$ composer install "mediaman/module-wishlist-api: 1.*"
```

Enable the module

```
$ ./bin/magento module:enable Mediaman_WishlistApi
```

Upgrade your Magento database schemas

```
$ ./bin/magento setup:upgrade
```

### Usage

The module adds three new API endpoints that allow you to interact with the Magento 2 wishlist.

If there's no customer session available, the current customer is received through the customer token.

**GET** `/rest/V1/wishlist`

Get the wishlist for the user.

**Example:** 

```
$ curl -X GET http://magento.example.com/rest/V1/wishlist --header "Authorization: Bearer pbhercbtk6dd3eatf1pyx8jj45avjluu"
```

**PUT** `/rest/V1/wishlist/:sku`

Add the product to the users wishlist.

**Example:**

```
$ curl -X PUT http://magento.example.com/rest/V1/wishlist/24-MB01 --header "Authorization: Bearer pbhercbtk6dd3eatf1pyx8jj45avjluu"
```

**DELETE** `/rest/V1/wishlist/:itemId`

Remove an item from the users wishlist.

**Example:**

```
$ curl -X DELETE http://magento.example.com/rest/V1/wishlist/1 --header "Authorization: Bearer pbhercbtk6dd3eatf1pyx8jj45avjluu"
```

## License

MIT © [mediaman GmbH](mailto:hallo@mediaman.de)
