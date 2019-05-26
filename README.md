<p align="center">
  <a href="https://travis-ci.org/sinute/phplog"><img src="https://travis-ci.org/sinute/phplog.svg" alt="Build Status"></a>
</p>

# PHPLog

PHPLog likes `illuminate/log`, but can use out of lumen / laravel.

# Driver

## Single

#### Usage

```php
<php
$log = new LogManager('dev', [
    'default'  => 'single',
    'channels' => [
        'single' => [
            'driver' => 'single',
            'path'   => dirname(__DIR__) . "/storage/log/app.log",
            'level'  => 'debug'
        ],
    ],
]);

$log->info('test');
```

#### Config

```php
[
    'driver'         => 'single', // required. Always log in one file.
    'path'           => 'app.log', // required. Log file path.
    'level'          => 'debug', // required. Logs below than this level will be not recorded.
    'name'           => '', // optional. Channel-name will overwrite env. (Default: '')
    'bubble'         => true, // optional. Indicates if messages should bubble up to other channels after being handled. (Default: true)
    'permission'     => null, // optional. The log file's permissions. (Default: 0644)
    'locking'        => false, // optional. Attempt to lock the log file before writing to it. (Default: false)
    'formatter'      => '\Monolog\Formatter\LineFormatter', // optional. Which formatter to use. (Default: \Monolog\Formatter\LineFormatter)
    'formatter_with' => [], // optional. Formatter parameters. (Default: [])
];
```

## Daily

#### Usage

```php
<?php
$log = new LogManager('dev', [
    'default'  => 'daily',
    'channels' => [
        'daily' => [
            'driver' => 'daily',
            'path'   => dirname(__DIR__) . "/storage/log/app.log",
            'level'  => 'debug',
            'days'   => 7,
        ],
    ],
]);

$log->info('test');
```

#### Config

```php
[
    'driver'         => 'daily', // required. Logs one file per day.
    'path'           => 'app.log', // required. Log file path.
    'level'          => 'debug', // required. Logs below than this level will be not recorded.
    'days'           => 7, // optional. How many files to keep. (Default: 7)
    'name'           => '', // optional. Channel-name will overwrite env. (Default: '')
    'bubble'         => true, // optional. Indicates if messages should bubble up to other channels after being handled. (Default: true)
    'permission'     => null, // optional. The log file's permissions. (Default: 0644)
    'locking'        => false, // optional. Attempt to lock the log file before writing to it. (Default: false)
    'formatter'      => '\Monolog\Formatter\LineFormatter', // optional. Which formatter to use. (Default: \Monolog\Formatter\LineFormatter)
    'formatter_with' => [], // optional. Formatter parameters. (Default: [])
];
```

## Custom

#### Usage

1. Use extend

```php
<php
$log = new LogManager('dev', [
    'default'  => 'custom',
    'channels' => [
        'custom' => [
            'driver' => 'myDriver',
            'path'   => dirname(__DIR__) . "/storage/log/app.log",
            'level'  => 'debug'
        ],
    ],
])->extend(
    'myDriver',
    function ($env, $config) {
        $steamHandler = new \Monolog\Handler\StreamHandler(
            $config['path'], $config['level'],
            $config['bubble'] ?? true, $config['permission'] ?? null, $config['locking'] ?? false
        );
        $steamHandler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, true, true));
        return new \Monolog\Logger($env, [$steamHandler]);
    }
);

$log->info('test');
```

2. Use `via` option

```php
<?php
$log = new LogManager('dev', [
    'default'  => 'custom',
    'channels' => [
        'custom' => [
            'driver' => 'myDriver',
            'path'   => dirname(__DIR__) . "/storage/log/app.log",
            'level'  => 'debug',
            'via'    => function ($env, $config) {
                $steamHandler = new \Monolog\Handler\StreamHandler(
                    $config['path'], $config['level'],
                    $config['bubble'] ?? true, $config['permission'] ?? null, $config['locking'] ?? false
                );
                $steamHandler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, true, true));
                return new \Monolog\Logger($env, [$steamHandler]);
            }
        ],
    ],
]);

$log->info('test');
```

#### Config

```php
[
    'driver'         => 'custom', // required. Custom driver.
    'path'           => 'app.log', // required. Log file path.
    'level'          => 'debug', // required. Logs below than this level will be not recorded.
    'via'            => null, // optional. If not extend, this option will be required to create a custom \Monolog\Logger. (Default: null)
];
```
