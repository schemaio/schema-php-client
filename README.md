## Schema API Client for PHP

Build and scale ecommerce with Schema. Create a free account at https://schema.io

## Example

```php
require_once("/path/to/schema-php-client/lib/Schema.php");

$client = new Schema\Client('<client-id>', '<client-key>');

$products = $client->get('/categories/shoes/products', array(
	'color' => 'blue'
));

print_r($products);
```

or with [Composer](https://getcomposer.org/doc/05-repositories.md#vcs)

__composer.json__
```json
"require": {
	"schemaio/schema-php-client" : "dev-master"
},
 "repositories": [
	{
		"type" : "vcs",
		"url"  : "git@github.com:schemaio/schema-php-client.git"
	}
]
```

Then run `composer update` to download and install the library

```php
require __DIR__ . '/vendor/autoload.php';

$client = new Schema\Client('<client-id>', '<client-key>');

$products = $client->get('/categories/shoes/products', array(
	'color' => 'blue'
));

print_r($products);

```




## Documentation

See <http://schema.io/docs/clients#php> for more API docs and usage examples

## CA certificates

TLS certificate verification uses the bundled Mozilla CA certificate store at
`data/ca-certificates.crt` via `lib/Connection.php`. Keep this file current
before publishing releases that may be used with `verify_cert` enabled.

To update it, download the current PEM bundle from
https://curl.se/docs/caextract.html and replace `data/ca-certificates.crt`,
preserving the filename because it is referenced directly by the client.

## Contributing

Pull requests are welcome

## License

MIT
