<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers;

use cusodede\opentracing\OpenTracingComponent;
use OpenTracing\GlobalTracer;
use OpenTracing\SpanContext;
use Throwable;
use Yii;
use yii\console\Application as ConsoleApplication;
use yii\helpers\ArrayHelper;
use yii\web\Application as WebApplication;
use yii\web\Response as WebResponse;
use const OpenTracing\Formats\HTTP_HEADERS;

/**
 * Class HttpRequestHandler
 */
class HttpRequestHandler implements RootEventHandlerInterface {

	/**
	 * @inheritDoc
	 */
	public function attach(OpenTracingComponent $tracingComponent):void {
		$requestLogCallback = function() use ($tracingComponent) {
			$spanContext = $this->extractContext();
			$span = $tracingComponent->tracer->startSpan('application.request', null === $spanContext
				?[]
				:['child_of' => $spanContext]);
			$span->log(
				$tracingComponent->dataFormattersFactory->getRequestDataFormatter()->format(Yii::$app->request)
			);
			$tracingComponent->setRootScope($tracingComponent->tracer->getScopeManager()->activate($span));
		};

		$responseLogCallback = function() use ($tracingComponent) {
			$tracingComponent->rootScope->getSpan()->log(
				$tracingComponent->dataFormattersFactory->getResponseDataFormatter()->format(Yii::$app->response)
			);
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
			Yii::$app->on(WebApplication::EVENT_BEFORE_REQUEST, $requestLogCallback);
			Yii::$app->response->on(WebResponse::EVENT_AFTER_SEND, $responseLogCallback);
			return;
		}

		Yii::$app->on(ConsoleApplication::EVENT_BEFORE_REQUEST, $requestLogCallback);
		Yii::$app->on(ConsoleApplication::EVENT_AFTER_REQUEST, $responseLogCallback);
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