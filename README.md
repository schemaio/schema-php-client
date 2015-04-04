## Schema API Client for PHP

*Schema is the platform to build and scale ecommerce.*

Create a free account at https://schema.io

## Usage example

	<?php require_once("/path/to/schema-php-client/lib/Schema.php");

	$client = new Schema\Client('your_client_id', 'your_secret_key');

	$products = $client->get('/categories/shoes/products', array('color' => 'blue'));

	print_r($products);

## Documentation

See <http://schema.io/docs/clients#php> for more API docs and usage examples

## Contributing

Pull requests are welcome

## License

MIT
