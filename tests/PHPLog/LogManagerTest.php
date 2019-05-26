<?php
namespace Monolog\Handler;

use Sinute\PHPLog\LogManagerTest;

function date($format, $timestamp = null)
{
    if ($format === 'Y-m-d') {
        return \date($format, strtotime(LogManagerTest::$currentDate));
    } else {
        return \date($format, $timestamp);
    }
}

namespace Sinute\PHPLog;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LogManagerTest extends TestCase
{
    public static $currentDate;

    protected function setUp()
    {
        exec("rm -f " . dirname(__DIR__) . "/storage/log/LogTest*");
        static::$currentDate = date('Y-m-d H:i:s');
    }

    public function testSingleDriver()
    {
        $log = new LogManager('unittest', [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver' => 'single',
                    'path'   => dirname(__DIR__) . "/storage/log/LogTest.log",
                    'level'  => 'debug',
                    'days'   => 3,
                ],
            ],
        ]);

        $log->info(__FUNCTION__);
        $this->assertEquals(true, file_exists(dirname(__DIR__) . "/storage/log/LogTest.log"));
    }

    public function testRotatingFile()
    {
        $days = 3;
        $log  = new LogManager('unittest', [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver' => 'daily',
                    'path'   => dirname(__DIR__) . "/storage/log/LogTest.log",
                    'level'  => 'debug',
                    'days'   => $days,
                ],
            ],
        ]);

        $file    = dirname(__DIR__) . "/storage/log/LogTest-%date%.log";
        $date    = '2019-01-01';
        $endDate = '2019-01-31';
        while (strtotime($date) <= strtotime($endDate)) {
            file_put_contents(str_replace('%date%', $date, $file), '');
            $date = date('Y-m-d', strtotime("{$date} +1 day"));
        }
        // trigger rotate
        $log->info('test');
        // trigger destroy & handler->close()
        unset($log);
        $files = glob(str_replace('%date%', '[0-9]*', $file));
        $this->assertEquals($days, count($files));
    }

    public function testDailyDriver()
    {
        $log = new LogManager('unittest', [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver' => 'daily',
                    'path'   => dirname(__DIR__) . "/storage/log/LogTest.log",
                    'level'  => 'debug',
                    'days'   => 3,
                ],
            ],
        ]);

        $log->pushProcessor(function ($record) {
            $record['datetime'] = new \DateTime($record['message'], new \DateTimeZone(date_default_timezone_get() ?: 'UTC'));
            return $record;
        });

        $now     = time() + 24 * 3600 * 10;
        $endTime = $now + 24 * 3600 * 10;
        while ($now < $endTime) {
            $date     = date('Y-m-d 00:00:00', $now);
            $fileName = 'LogTest-' . date('Y-m-d', $now) . '.log';
            $now += 24 * 3600;
            static::$currentDate = $date;
            $log->info($date);
            $this->assertEquals(true, file_exists(dirname(__DIR__) . "/storage/log/{$fileName}"));
        }
    }

    public function testCustomDriver()
    {
        $log = new LogManager('unittest', [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver' => 'custom',
                    'path'   => [
                        'text' => dirname(__DIR__) . "/storage/log/LogTest.log",
                        'json' => dirname(__DIR__) . "/storage/log/LogTest.json.log",
                    ],
                    'level'  => 'debug',
                    'via'    => function ($env, $config) {
                        $steamHandler = new \Monolog\Handler\StreamHandler(
                            $config['path']['text'], $config['level'],
                            $config['bubble'] ?? true, $config['permission'] ?? null, $config['locking'] ?? false
                        );
                        $steamHandler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, true, true));
                        $jsonHandler = new \Monolog\Handler\StreamHandler(
                            $config['path']['json'], $config['level'],
                            $config['bubble'] ?? true, $config['permission'] ?? null, $config['locking'] ?? false
                        );
                        $steamHandler->setFormatter(new \Monolog\Formatter\JsonFormatter(1, true));
                        return new \Monolog\Logger($env, [
                            $steamHandler,
                            $jsonHandler,
                        ]);
                    },
                ],
            ],
        ]);

        $log->info('test');
        $this->assertEquals(true, file_exists(dirname(__DIR__) . "/storage/log/LogTest.log"));
        $this->assertEquals(true, file_exists(dirname(__DIR__) . "/storage/log/LogTest.json.log"));
    }

    public function testExtend()
    {
        $log = new LogManager('unittest', [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver' => 'extendTest',
                    'path'   => dirname(__DIR__) . "/storage/log/LogTest.log",
                    'level'  => 'debug',
                ],
            ],
        ]);
        $log->extend(
            'extendTest',
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
        $this->assertEquals(true, file_exists(dirname(__DIR__) . "/storage/log/LogTest.log"));
    }

    public function testLevels()
    {
        $log = new LogManager('unittest', [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver' => 'daily',
                    'path'   => dirname(__DIR__) . "/storage/log/LogTest.log",
                    'level'  => 'debug',
                    'days'   => 3,
                ],
            ],
        ]);

        $levels = Logger::getLevels();

        $date = date('Y-m-d');
        foreach ($levels as $level => $value) {
            $level = strtolower($level);
            $log->$level('test');
            $content = file_get_contents(dirname(__DIR__) . "/storage/log/LogTest-{$date}.log");
            $this->assertEquals(1, preg_match("~{$level}~i", $content), "test {$level}");
        }
    }

    public function testLog()
    {
        $log = new LogManager('unittest', [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver' => 'daily',
                    'path'   => dirname(__DIR__) . "/storage/log/LogTest.log",
                    'level'  => 'debug',
                    'days'   => 3,
                ],
            ],
        ]);

        $date = date('Y-m-d');
        $log->log('info', 'test');
        $content = file_get_contents(dirname(__DIR__) . "/storage/log/LogTest-{$date}.log");
        $this->assertEquals(1, preg_match("~info~i", $content), "test log");
    }

    /**
     * testInvalidLogLevel
     *
     * @author Sinute
     * @date   2019-05-24
     *
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Invalid log level.
     */
    public function testInvalidLogLevel()
    {
        $log = new LogManager('unittest', [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver' => 'daily',
                    'path'   => dirname(__DIR__) . "/storage/log/LogTest.log",
                    'level'  => 'unknown',
                    'days'   => 3,
                ],
            ],
        ]);
        $log->info('test');
    }

    /**
     * testInvalidLogConfig
     *
     * @author Sinute
     * @date   2019-05-24
     *
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Log [unknown] is not defined.
     */
    public function testInvalidLogConfig()
    {
        $log = new LogManager('unittest', [
            'default' => 'unknown',
        ]);
        $log->info('test');
    }

    /**
     * testInvalidDriverConfig
     *
     * @author Sinute
     * @date   2019-05-24
     *
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Driver [unknown] is not supported.
     */
    public function testInvalidDriverConfig()
    {
        $log = new LogManager('unittest', [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver' => 'unknown',
                ],
            ],
        ]);
        $log->info('test');
    }

    public function testLogLevelConfig()
    {
        $log = new LogManager('unittest', [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver' => 'daily',
                    'path'   => dirname(__DIR__) . "/storage/log/LogTest.log",
                    'level'  => 'error',
                    'days'   => 3,
                ],
            ],
        ]);

        $levels = Logger::getLevels();

        $date = date('Y-m-d');
        foreach ($levels as $level => $value) {
            $level = strtolower($level);
            $log->$level('test');
            $content = @file_get_contents(dirname(__DIR__) . "/storage/log/LogTest-{$date}.log");
            $this->assertEquals($value >= Logger::ERROR ? 1 : 0, preg_match("~{$level}~i", $content), "test {$level}");
        }
    }

    public function testCustomFormatter()
    {
        $config = [
            'default'  => 'test',
            'channels' => [
                'test' => [
                    'driver'         => 'daily',
                    'path'           => dirname(__DIR__) . "/storage/log/LogTest.log",
                    'level'          => 'debug',
                    'formatter'      => '\Monolog\Formatter\JsonFormatter',
                    'formatter_with' => [1, true],
                    'days'           => 3,
                ],
            ],
        ];
        $log = new LogManager('unittest', $config);

        $log->info('test');

        $date    = date('Y-m-d');
        $content = @file_get_contents(dirname(__DIR__) . "/storage/log/LogTest-{$date}.log");
        $this->assertEquals('test', (json_decode($content))->message);
    }
}
