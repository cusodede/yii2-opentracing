# yii2-opentracing
OpenTracing support component

![GitHub Workflow Status](https://img.shields.io/github/workflow/status/cusodede/yii2-opentracing/CI)

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