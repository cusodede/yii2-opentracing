<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers;

use cusodede\opentracing\OpenTracingComponent;
use cusodede\opentracing\OTTracer;
use yii\base\Event;
use yii\httpclient\Client;
use yii\httpclient\RequestEvent;
use const OpenTracing\Formats\HTTP_HEADERS;

/**
 * Class HttpClientEventsHandler
 */
class HttpClientEventsHandler implements RootEventHandlerInterface {

	/**
	 * @inheritDoc
	 */
	public function attach(OpenTracingComponent $tracingComponent):void {
		Event::on(Client::class, Client::EVENT_BEFORE_SEND, static function(RequestEvent $e) use ($tracingComponent) {
			$activeScope = $tracingComponent->tracer->startActiveSpan('client.request');
			$activeScope->getSpan()->log(
				$tracingComponent->dataFormattersFactory->getRequestDataFormatter()->format($e->request)
			);

			$tracer->inject($activeScope->getSpan()->getContext(), HTTP_HEADERS, $e->request->headers);
		});

		Event::on(Client::class, Client::EVENT_AFTER_SEND, static function(RequestEvent $e) use ($tracingComponent) {
			if (null !== $activeScope = $tracingComponent->tracer->getScopeManager()->getActive()) {
				$activeScope->getSpan()->log(
					$tracingComponent->dataFormattersFactory->getResponseDataFormatter()->format($e->request)
				);
				$activeScope->close();
			}
		});
	}
}