<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers;

use cusodede\opentracing\OpenTracingComponent;
use OpenTracing\GlobalTracer;
use OpenTracing\SpanContext;
use Throwable;
use Yii;
use yii\base\Application as BaseApplication;
use yii\helpers\ArrayHelper;
use yii\web\Application as WebApplication;
use yii\web\Response;
use const OpenTracing\Formats\HTTP_HEADERS;

/**
 * Class HttpRequestHandler
 */
class HttpRequestHandler implements RootEventHandlerInterface {

	/**
	 * @inheritDoc
	 */
	public function attach(OpenTracingComponent $tracingComponent):void {
		Yii::$app->on(BaseApplication::EVENT_BEFORE_REQUEST, function() use ($tracingComponent) {
			$spanContext = $this->extractContext();
			$span = $tracingComponent->tracer->startSpan('application.request', null === $spanContext
				?[]
				:['child_of' => $spanContext]);
			$span->log($tracingComponent->dataFormattersFactory->getRequestDataFormatter()->format(Yii::$app->request));
			$tracingComponent->setRootScope($tracingComponent->tracer->getScopeManager()->activate($span));
		});

		$responseLogCallback = function() use ($tracingComponent) {
			$tracingComponent->rootScope->getSpan()->log($tracingComponent->dataFormattersFactory->getResponseDataFormatter()->format(Yii::$app->response));

			if (null !== $exception = ArrayHelper::getValue(Yii::$app->controller->module, 'errorHandler.exception')) {
				try {
					$exceptionId = ArrayHelper::getValue(Yii::$app->controller->module, 'errorHandler.id');
				} catch (Throwable) {
					$exceptionId = null;
				}
				$tracingComponent->rootScope->getSpan()->log(
					$tracingComponent->dataFormattersFactory->getExceptionDataFormatter()->format($exception, $exceptionId)
				);
			}
		};

		if (Yii::$app instanceof WebApplication) {
			Yii::$app->response->on(Response::EVENT_AFTER_SEND, $responseLogCallback);
		} else {
			/**
			 * Не уверен до конца, пригодно ли для консольного приложения, но пусть будет.
			 * todo: test
			 */
			Yii::$app->on(BaseApplication::EVENT_AFTER_REQUEST, $responseLogCallback);
		}
	}

	/**
	 * @return SpanContext|null
	 */
	private function extractContext():?SpanContext {
		try {
			return GlobalTracer::get()->extract(HTTP_HEADERS, getallheaders());
		} catch (Throwable) {
			return null;
		}
	}
}