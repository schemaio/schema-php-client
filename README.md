## Schema API Client for PHP

Build and scale ecommerce with Schema. Create a free account at https://schema.io

## Example

```php
<?php require_once("/path/to/schema-php-client/lib/Schema.php");

$client = new Schema\Client('<client-id>', '<client-hey>');

$products = $client->get('/categories/shoes/products', array(
	'color' => 'blue'
));

print_r($products);
```

## Documentation

See <http://schema.io/docs/clients#php> for more API docs and usage examples

## Contributing

Pull requests are welcome

## License

MIT
