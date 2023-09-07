<?php
declare(strict_types = 1);

use cusodede\opentracing\OpenTracingComponent;
use Helper\Functional;
use yii\log\Logger;

/**
 * Class UsersCest
 */
class LoggerCest {

	private const TEST_MESSAGE = 'This is commander Sheppard and it is my favorite test!';

	/**
	 * Проверяет произвольную запись без обработчика, но с указанной категорией
	 * @param FunctionalTester $I
	 * @throws Exception
	 */
	public function log(FunctionalTester $I):void {
		$timestamp = time();
		Yii::getLogger()->log(self::TEST_MESSAGE.'@'.$timestamp, Logger::LEVEL_INFO, OpenTracingComponent::CATEGORY);
		Yii::getLogger()->flush();
		$logFile = Yii::getAlias('@app/runtime/logs/ot-'.date('YmdH').'.log');
		$I->assertFileExists($logFile);
		/** @var array $logContents */
		$logContents = file($logFile);
		$I->assertIsArray($logContents);
		$I->assertStringContainsString(self::TEST_MESSAGE.'@'.$timestamp, $logContents[count($logContents) - 1]);
	}

	/**
	 * Проверяет необходимые поля в логе
	 * @param FunctionalTester $I
	 * @throws Exception
	 */
	public function trace(FunctionalTester $I):void {
		$I->amOnRoute('site/index');
		$I->seeResponseCodeIs(200);
		Yii::getLogger()->flush();
		$logFile = Yii::getAlias('@app/runtime/logs/ot-'.date('YmdH').'.log');
		$I->assertFileExists($logFile);
		/** @var array $logContents */
		$logContents = file($logFile);
		$I->assertIsArray($logContents);
		$requestLogString = $logContents[count($logContents) - 1];
		$requestLogArray = json_decode($requestLogString, true);

		/*
		 * Последняя строка в логе должна иметь вид
		 * {"TStamp":"2023-09-07T17:26:36.526+00:00","trace_id":"3f7b030f23632dc6cf17bf6e3f3d7ebd","trace_span_id":"1a56e8c035c17124","trace_parent_id":"0000000000000000","log_level":"Info","log_level_id":2,"message":"","app_name":"My Application","log_context":"cusodede\\opentracing\\OpenTracingComponent::extractSpanData","duration":4,"log_event_name":"application.request","app_version":"1.0","env_name":"test","url":"http://localhost/site/index","direction":"in"}
		 */

		// обязательные поля
		$I->assertArrayHasKey("TStamp", $requestLogArray);
		$I->assertArrayHasKey("trace_id", $requestLogArray);
		$I->assertArrayHasKey("trace_span_id", $requestLogArray);
		$I->assertArrayHasKey("trace_parent_id", $requestLogArray);
		$I->assertEquals("0000000000000000", $requestLogArray["trace_parent_id"]);
		$I->assertArrayHasKey("log_level", $requestLogArray);
		$I->assertArrayHasKey("log_level_id", $requestLogArray);
		$I->assertArrayHasKey("message", $requestLogArray);

		// рекомендуемые поля
		$I->assertArrayHasKey("log_context", $requestLogArray);
		$I->assertArrayHasKey("app_name", $requestLogArray);
		$I->assertArrayHasKey("duration", $requestLogArray);

		// стандартные поля
		$I->assertArrayHasKey("log_event_name", $requestLogArray);
		$I->assertArrayHasKey("app_version", $requestLogArray);
		$I->assertArrayHasKey("env_name", $requestLogArray);
	}

	/**
	 * Отправляем запрос с уже существующим trace_id, он должен включиться в лог
	 * @param FunctionalTester $I
	 * @return void
	 * @throws Exception
	 */
	public function incomingTrace(FunctionalTester $I):void {
		$incomingHeaderContent = Functional::genTraceHeader();
		$traceParts = explode('-', $incomingHeaderContent);
		$I->haveHttpHeader('traceparent', $incomingHeaderContent);
		$response = $I->sendGet('site/api');

		$I->assertEquals(['status' => 'ok'], json_decode($response, true));
		$I->seeResponseCodeIs(200);
		//Сбрасываем лог в файл
		Yii::getLogger()->flush();
		$logFile = Yii::getAlias('@app/runtime/logs/ot-'.date('YmdH').'.log');
		/** @var array $logContents */
		$logContents = file($logFile);
		$I->assertIsArray($logContents);
		$requestLogString = $logContents[count($logContents) - 1];
		$requestLogArray = json_decode($requestLogString, true);

		/*
		 * Последняя строка в логе должна иметь вид
		 * {"TStamp":"2023-09-07T17:34:12.382+00:00","trace_id":"020438bf1afbda90","trace_span_id":"f0c440e8c6dcdc9d","trace_parent_id":"36e677cd","log_level":"Info","log_level_id":2,"message":"","app_name":"My Application","log_context":"cusodede\\opentracing\\OpenTracingComponent::extractSpanData","duration":1,"log_event_name":"application.request","app_version":"1.0","env_name":"test","url":"http://localhost/site/api","direction":"in"}
		 */

		$I->assertEquals($traceParts[1], $requestLogArray["trace_id"]);
		$I->assertEquals($traceParts[2], $requestLogArray["trace_parent_id"]);
	}

}
