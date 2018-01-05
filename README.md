# IP2Currency

A Wordpress plugin to map client IP address to local currency

## Installation

1. Unpack the zip archive and place the folder ip2currency containing plugin source files, into the wordpress plugins directory.
2. Login as site admin
3. Activate the plugin

IP2Currency should now be installed

## Administration and usage

Once IP2Currency is activated you should see an admin page under Settings -> IP2Currency

On actvation one default price set is created for each product. This set of prices is used as default for the corresponding product in case
* the country of client IP is not set, and/or
* the country of client IP cannot be determined

These default rows cannot be deleted. However, they can be edited.

Also, while adding records, duplicate product and country combination is not permitted.

The prices set in this admin screen can be used in pages/posts using the following shortcode
```php
[ip2currency_get product_id=$product_id idx=$price_idx]
```
or using the following function in php templates
```php
ip2currency_get($product_id, $price_idx)
```
For, both the above `$product_id` and `$price_idx` start from 1.
`$product_id` takes values 1 and 2, as it is setup for 2 products now.
`$price_idx` can take values 1,2,3,4 or 5 depending on which price record it is trying to get.

# Example
```php
[ip2currency_get_price product_id=1 idx=5]
```
gets Price5 for product '1 day course' in a post/page

and `<?php echo ip2currency_get_price(1,5) ?> ` gets the same price when used in a template.
