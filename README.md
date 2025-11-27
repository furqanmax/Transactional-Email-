# Transactional Email API Client (Laravel + PHP)

A lightweight package to call a transactional email HTTP API. It supports:
- Login to obtain a bearer token
- Template-based sends (`/gettransactionalApi`)
- Direct sends (`/makeTransactionalApi`)

It works great in Laravel (auto-discovered provider + Facade) and can be used in any PHP project via Composer.

## Installation

```bash
composer require furqanmax/transactional-email
```

Laravel auto-discovers the service provider. Optionally publish the config:

```bash
php artisan vendor:publish --tag=config --provider="Furqanmax\\TransactionalEmail\\TransactionalEmailServiceProvider"
```

## Configuration

Config file: `config/transactional-email.php`

Environment variables you can set in Laravel:

```env
APP_ID=37d26fe1-cb38-4fc4-8f53-00e6a6d115f2   # REQUIRED: your application UUID used for template sends
TRANSACTIONAL_EMAIL_BASE_URL=http://127.0.0.1:8000/api
TRANSACTIONAL_EMAIL_LOGIN_ENDPOINT=/login
TRANSACTIONAL_EMAIL_TEMPLATE_ENDPOINT=/gettransactionalApi
TRANSACTIONAL_EMAIL_DIRECT_ENDPOINT=/makeTransactionalApi
TRANSACTIONAL_EMAIL_TIMEOUT=10
TRANSACTIONAL_EMAIL_VERIFY_SSL=false

# Optional (enables auto-login if token is not set)
TRANSACTIONAL_EMAIL_LOGIN_EMAIL=you@example.com
TRANSACTIONAL_EMAIL_LOGIN_PASSWORD=secret
```

## Usage in Laravel

You can use the Facade or inject the client.

- Facade: `Furqanmax\\TransactionalEmail\\Facades\\TransactionalEmail`
- Client: `Furqanmax\\TransactionalEmail\\TransactionalEmailClient`

### 1) Login (get token)

```php
use Furqanmax\\TransactionalEmail\\Facades\\TransactionalEmail;

// Explicit login
$token = TransactionalEmail::login('rohit@bizionictech.com', 'asdf');

// Or rely on configured credentials (env) for auto-login later
```

### 2) Send using a template

```php
use Furqanmax\\TransactionalEmail\\Facades\\TransactionalEmail;

$response = TransactionalEmail::sendTemplateEmail(
    from: 'pranita@bizionictech.com',
    to: 'pranita@bizionictech.com',
    templateKey: 'PRA_251125102142',
    templateVariables: [
        'name' => 'asdfas',
        'reset_link' => 'www.bizionictech.com',
        'otp' => '91991',
    ],
    subject: 'hello',
    preheaderText: 'hii everyone'
);
```

By default, the client uses `APP_ID` as the UUID for template sends. To override per call, pass the optional `uuid` argument:

```php
$response = TransactionalEmail::sendTemplateEmail(
    from: 'pranita@bizionictech.com',
    to: 'pranita@bizionictech.com',
    templateKey: 'WELCOME',
    templateVariables: ['name' => 'John'],
    uuid: 'override-uuid-here'
);
```

### 3) Send a direct email (no template)

```php
use Furqanmax\\TransactionalEmail\\Facades\\TransactionalEmail;

$response = TransactionalEmail::sendDirectEmail(
    from: 'pranita@bizionictech.com',
    to: 'pranita@bizionictech.com',
    subject: 'Hello World',
    preheader: 'Redeem your gift $ card now.',
    body: "Hello,\n\nGreat news! You've received a $50 gift card from App Name.\n\n" .
          "Gift Card Code: GC123456789\nRedeem it here: [Redeem Link]\n\n" .
          "Enjoy!\n\nRegards,\nApp Name Team",
    htmlBody: '<!DOCTYPE html>...YOUR FULL HTML HERE...'
);
```

Note: If you don't pass a `$token`, the client will automatically log in using configured credentials if present. Otherwise, call `login()` explicitly.

### Dependency Injection example

```php
use Furqanmax\\TransactionalEmail\\TransactionalEmailClient;

class MailController
{
    public function __construct(private TransactionalEmailClient $email) {}

    public function send()
    {
        // Auto-login based on env credentials if not logged in
        $resp = $this->email->sendDirectEmail(
            from: 'me@example.com',
            to: 'you@example.com',
            subject: 'Hi',
            body: 'Hello from DI!'
        );

        return response()->json($resp);
    }
}
```

## Usage in plain PHP

1) Require composer autoload and instantiate the client:

```php
require __DIR__ . '/vendor/autoload.php';

use Furqanmax\\TransactionalEmail\\TransactionalEmailClient;

$client = new TransactionalEmailClient(
    baseUrl: 'http://127.0.0.1:8000/api',
    endpoints: [
        'login' => '/login',
        'template' => '/gettransactionalApi',
        'direct' => '/makeTransactionalApi',
    ],
    httpConfig: [
        'timeout' => 10,
        'verify_ssl' => false,
    ],
    credentials: [
        'email' => 'rohit@bizionictech.com',
        'password' => 'asdf',
    ],
    appId: '37d26fe1-cb38-4fc4-8f53-00e6a6d115f2' // REQUIRED: application UUID (same as APP_ID in Laravel)
);

// Optional explicit login
$token = $client->login();

// Send using a template
$templateResp = $client->sendTemplateEmail(
    from: 'pranita@bizionictech.com',
    to: 'pranita@bizionictech.com',
    templateKey: 'PRA_251125102142',
    templateVariables: [
        'name' => 'asdfas',
        'reset_link' => 'www.bizionictech.com',
        'otp' => '91991',
    ],
    subject: 'hello',
    preheaderText: 'hii everyone'
);

// Send a direct email
$directResp = $client->sendDirectEmail(
    from: 'pranita@bizionictech.com',
    to: 'pranita@bizionictech.com',
    subject: 'Hello World',
    preheader: 'Redeem your gift $ card now.',
    body: "Hello,\n\nGreat news! You've received a $50 gift card from App Name.\n\n" .
          "Gift Card Code: GC123456789\nRedeem it here: [Redeem Link]\n\n" .
          "Enjoy!\n\nRegards,\nApp Name Team",
    htmlBody: '<!DOCTYPE html>...YOUR FULL HTML HERE...'
);

print_r([$templateResp, $directResp]);
```

## Error handling

- All API calls return decoded JSON arrays on success.
- The client throws `RuntimeException` on HTTP errors (>= 400) or invalid JSON.
- Wrap calls in `try/catch` as needed.

```php
try {
    $resp = TransactionalEmail::sendDirectEmail(...);
} catch (\\Throwable $e) {
    // log/report
}
```

## Notes

- Requires PHP 8.1+ and cURL extension (`ext-curl`).
- Endpoints, base URL, and HTTP behavior are fully configurable via config/env in Laravel or constructor args in plain PHP.
- The client will auto-login using configured credentials when no token is provided.
- Template sends require a UUID: either configured via `APP_ID` or provided per call as `uuid`.

## License

MIT
