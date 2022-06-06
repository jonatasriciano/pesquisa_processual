<?php
Yii::setAlias('@tests', dirname(__DIR__) . '/tests');

$params = require(__DIR__ . '/params.php');
$arr_db = require(__DIR__ . '/db.php');

$arr_config = [
    'id' => 'agendador-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'sms' => [
            'class' => 'app\components\Sms'
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'flushInterval' => 1,
            'targets' => [
                [
                  'class' => 'yii\log\FileTarget',
                  'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'mail' => [
	        'class' => 'yii\swiftmailer\Mailer',
	        'transport' => [
                    'class' => 'Swift_SmtpTransport',
                    'host' => 'relay.cotia.grupoapi.local',
                    'username' => 'boletos',
                    'password' => 'BoletosAPI',
                    'port' => '587', // Port 25 is a very common port too "587"
                    //'encryption' => 'tls', // It is often used, check your provider or mail server specs
	        ],
        ],
        'formatter' => [
            'locale' => 'pt_BR',
            'decimalSeparator' => ',',
            'thousandSeparator' => '.',
            //'dateFormat' => 'php:d/m/Y',
            //'datetimeFormat' => 'php:d/m/Y H:i:s',
            //'timeFormat' => 'php:H:i:s',
        ],
    ],
    'params' => $params,
    'enableCoreCommands' => false, // disable all core commands,
    /*'controllerMap' => [
        'migrate' => 'yii\console\controllers\MigrateController', // enable the migrate core command
    ],*/
];


if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $arr_config['bootstrap'][] = 'debug';
    $arr_config['modules']['debug'] = [
      'class' => 'yii\debug\Module',
      'allowedIPs' => ['127.0.0.1', '::1', '192.168.254.*'] // adjust this to your needs
    ];
    $arr_config['components']['log']['targets'] = [
                [
                  'class' => 'yii\log\FileTarget',
                  'levels' => ['info'],
                  'categories' => ['yii\db\*'], //Command::query
                  'logFile' => '@app/runtime/logs/db-info.log',
                  'maxFileSize' => 1024 * 5,
                  'maxLogFiles' => 50,
                  'exportInterval' => 1,
                  'logVars' => [],
                  'prefix' => function ($message) { return null; }
                ],
                [
                  'class' => 'yii\log\FileTarget',
                  'levels' => ['trace'],
                  'categories' => ['yii\db\*'],
                  'logFile' => '@app/runtime/logs/db-trace.log',
                  'maxFileSize' => 1024 * 2,
                  'maxLogFiles' => 50,
                  'exportInterval' => 1,
                  'logVars' => [],
                  'prefix' => function ($message) { return null; }
                ],
                [
                  'class' => 'yii\log\FileTarget',
                  'levels' => ['error'],
                  'categories' => ['yii\db\*'],
                  'logFile' => '@app/runtime/logs/db-error.log',
                  'maxFileSize' => 1024 * 2,
                  'maxLogFiles' => 50,
                  'exportInterval' => 1,
                  'logVars' => [],
                  'prefix' => function ($message) { return null; }
                ],
                [
                  'class' => 'yii\log\FileTarget',
                  'levels' => ['warning'],
                  'categories' => ['yii\db\*'],
                  'logFile' => '@app/runtime/logs/db-warning.log',
                  'maxFileSize' => 1024 * 2,
                  'maxLogFiles' => 50,
                  'exportInterval' => 1,
                  'logVars' => [],
                  'prefix' => function ($message) { return null; }
                ],

    ];
}

$arr_config['components'] = array_merge( $arr_config['components'], $arr_db );

/*print_r($arr_config);
die();*/

return $arr_config;
