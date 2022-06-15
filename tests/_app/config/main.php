<?php /** @noinspection UsingInclusionReturnValueInspection */
declare(strict_types = 1);

use app\models\Users;
use cusodede\opentracing\OpenTracingComponent;
use cusodede\opentracing\targets\OpenTracingFileTarget;
use yii\caching\DummyCache;
use yii\log\FileTarget;
use yii\web\AssetManager;
use yii\web\ErrorHandler;

$db = require __DIR__.'/db.php';

$config = [
	'id' => 'basic',
	'basePath' => dirname(__DIR__),
	'bootstrap' => ['log', 'opentracing'],
	'aliases' => [
		'@vendor' => './vendor',
		'@bower' => '@vendor/bower-asset',
		'@npm' => '@vendor/npm-asset',
	],
	'components' => [
		'request' => [
			'cookieValidationKey' => 'sosijopu',
		],
		'cache' => [
			'class' => DummyCache::class,
		],
		'user' => [
			'identityClass' => Users::class,
			'enableAutoLogin' => true,
		],
		'errorHandler' => [
			'class' => ErrorHandler::class,
			'errorAction' => 'site/error',
		],
		'opentracing' => [
			'class' => OpenTracingComponent::class,
			'excludedRequestsPaths' => [
				'assets/*'
			]
		],
		'log' => [
			'traceLevel' => YII_DEBUG?3:0,
			'targets' => [
				[
					'class' => FileTarget::class,
					'levels' => [],
					'except' => ['opentracing'],
					'logVars' => []
				],
				[
					'class' => OpenTracingFileTarget::class,
					'categories' => ['opentracing'],
					'logVars' => [],
					'logFile' => fn():string => '@app/runtime/logs/ot-'.date('YmdH').'.log'
				]
			],
		],
		'urlManager' => [
			'enablePrettyUrl' => true,
			'showScriptName' => false,
			'rules' => [
			],
		],
		'assetManager' => [
			'class' => AssetManager::class,
			'basePath' => '@app/assets'
		],
	],
	'params' => [
		'bsVersion' => '4'
	],
];

return $config;