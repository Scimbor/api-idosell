# Idosell-API

1. Make ```composer require api/idosell```
2. After install add ```\Api\Idosell\IdosellApiServiceProvider::class``` to ```bootstrap/providers/php```
3. Add ```api_key``` and ```domain_url``` in ```idosell.php```
4. Run ```php artisan optimize```
5. Run command ```php artisan api:test-api-idosell-package``` for tests