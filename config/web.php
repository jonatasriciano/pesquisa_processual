<?php

$params = require(__DIR__ . '/params.php');
$arr_db = require(__DIR__ . '/db.php');

$arr_config = [
    'id' => 'soa-admin',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'pt_BR',
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'Api2014',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'sms' => [
            'class' => 'app\components\Sms'
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
             'class' => 'yii\web\ErrorHandler',
        ],
        'mail' => [
	        'class' => 'yii\swiftmailer\Mailer',
	        'transport' => [
		        'class' => 'Swift_SmtpTransport',
		        'host' => 'relay.cotia.grupoapi.local',
                        'username' => 'api',
                        'password' => 'api2004',                    
		        'port' => '587', // Port 25 is a very common port too "587"
		        //'encryption' => 'tls', // It is often used, check your provider or mail server specs
	        ],
        ],
        'mail2' => [
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
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
    'params' => $params,
	'modules' => [
		'cp' => [
			'class' => 'app\modules\cp\Module',
		],
        'newsletter' => [
            'class' => 'app\modules\newsletter\Module',
        ],
        'movidesk' => [
            'class' => 'app\modules\movidesk\Module',
        ],
		'integra' => [
			'class' => 'app\modules\integra\Module',
		],
		'gridview' =>  [
			'class' => '\kartik\grid\Module'
			// enter optional module parameters below - only if you need to
			// use your own export download action or custom translation
			// message source
			// 'downloadAction' => 'gridview/export/download',
			// 'i18n' => []
		]
	],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $arr_config['bootstrap'][] = 'debug';
    $arr_config['modules']['debug'] = [
      'class' => 'yii\debug\Module',
      'allowedIPs' => ['127.0.0.1', '::1', '192.168.254.*'] // adjust this to your needs
    ];

    $arr_config['bootstrap'][] = 'gii';
    $arr_config['modules']['gii'] = [
	    'class' => 'yii\gii\Module',
	    'allowedIPs' => ['127.0.0.1', '::1', '192.168.254.*'] // adjust this to your needs
	];
}

$arr_config['components'] = array_merge( $arr_config['components'], $arr_db );

/*echo '<pre>';
print_r($arr_config);
die();*/
return $arr_config;
