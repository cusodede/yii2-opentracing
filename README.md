# yii2-opentracing
OpenTracing support component

[![Build Status](https://github.com/cusodede/yii2-opentracing/actions/workflows/ci.yml/badge.svg)](https://github.com/cusodede/yii2-opentracing/actions)

# Установка

Добавляем

```
{
	"type": "vcs",
	"url": "https://github.com/cusodede/yii2-opentracing"
}
```

В секцию `repositories` файла `composer.json`, затем запускаем

```
php composer.phar require cusodede/yii2-opentracing "^1.0.0"
```

или добавляем

```
"cusodede/yii2-opentracing": "^1.0.0"
```

в секцию `require`.

# Подключение

```php
$config = [
	...
	'bootstrap' => ['log', 'opentracing'], //Обязательно добавляем в bootstrap
	...
	'components' => [
		...
		'opentracing' => [ //Подключаем сам компонент
			'class' => OpenTracingComponent::class,
			'excludedRequestsPaths' => [
				'assets/*'
			],
			'handlers' => [//Указываем хендлеры логирования
				HttpClientEventsHandler::class
			]
		],
		'log' => [
			...
			'targets' => [
				...
				[//добавляем target для логирования событий категории opentracing, OpenTracingFileTarget позволяет гибче конфигурировать имя файла
					'class' => OpenTracingFileTarget::class,
					'categories' => ['opentracing'],
					'logVars' => [],
					'logFile' => fn():string => '@app/runtime/logs/ot-'.date('YmdH').'.log'
				]
			],
		],
		...
];
```

# todo

Добавить Target для постинга Url - Лёша Галлямов предоставит отдельный сервис.