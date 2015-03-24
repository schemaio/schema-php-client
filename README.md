## Schema API Client for PHP

*Schema is the API-centric platform to build and scale ecommerce.*

Create an account at https://schema.io

## Usage example

	<?php require_once("/path/to/schema-php-client/lib/Schema.php");

	$client = new Schema\Client('your_client_id', 'your_secret_key');

	$products = $client->get('/products', ['categories' => ['id1', 'id2]]);

	print_r($products);

## Documentation

See <http://schema.io/docs/clients#php> for more API docs and usage examples

## Contributing

Pull requests are welcome

## License

MIT
