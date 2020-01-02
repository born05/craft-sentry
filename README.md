# Sentry plugin for Craft CMS 3

Pushes Craft CMS errors to [Sentry](https://sentry.io/).
Does not log exceptions in devMode.

## Installation

### Plugin Store
1. Search for 'Sentry SDK'.
2. Hit install
3. Create a config file as explained below.

### Composer
1. Run: `composer require born05/craft-sentry`
2. Hit install in Admin > Settings > Plugins
3. Create a config file as explained below.

## Requirements
- Craft 3.1 or later
- PHP 7.1 at least

## Configuring Sentry
Create a `config/sentry-sdk.php` config file with the following contents:

```php
<?php

return [
    'enabled'       => true,
    'anonymous'     => false, // Determines to log user info or not
    'clientDsn'     => getenv('SENTRY_DSN') ?: 'https://example@sentry.io/123456789', // Set as string or use environment variable.
    'excludedCodes' => ['400', '404', '429'],
    'release'       => getenv('SENTRY_RELEASE') ?: null, // Release number/name used by sentry.
];
```

## Credits
Based upon the sentry plugin by [Luke Youell](https://github.com/lukeyouell).

## License

Copyright © [Born05](https://www.born05.com/)

See [license](https://github.com/born05/craft-sentry/blob/master/LICENSE.md)
