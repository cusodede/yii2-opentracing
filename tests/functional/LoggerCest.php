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
	 * @throws Throwable
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
	 * @param FunctionalTester $I
	 * @throws Throwable
	 * @throws Exception
	 */
	public function trace(FunctionalTester $I):void {
		$I->amOnRoute('site/index');
		$I->seeResponseCodeIs(200);
		//Сбрасываем лог в файл
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
		 * {"TStamp":"2022-06-17T08:10:24.131+00:00","trace_id":"011c30f465e8d12e21f253986fb2ccd8","parent_id":"0000000000000000","span_id":"b679ebe32f454ce2","duration":1666,"operationName":"application.request","req.host":"http://localhost","req.method":"GET","req.remote_ip":null,"req.remote_host":null,"req.remote_port":null,"req.url":"http://localhost/site/index","req.path":"site/index","req.referer":null,"req.size":0,"req.headers":{"user-agent":"Symfony BrowserKit","host":"localhost"},"req.body":[],"direction":"in","env":"test","rsp.http_code":200,"rsp.size":5,"rsp.headers":{"content-type":"text/html; charset=UTF-8"},"rsp.body":null,"user_id":null,"level":"info"}
		 */

		$I->assertArrayHasKey("TStamp", $requestLogArray);
		$I->assertArrayHasKey("trace_id", $requestLogArray);
		$I->assertArrayHasKey("parent_id", $requestLogArray);
		$I->assertArrayHasKey("span_id", $requestLogArray);
		$I->assertArrayHasKey("req.host", $requestLogArray);
		$I->assertArrayHasKey("req.url", $requestLogArray);
		$I->assertEquals("0000000000000000", $requestLogArray["parent_id"]);
		$I->assertEquals("in", $requestLogArray["direction"]);
		/*Проверить всё и вся не получится, урл зависит от окружения, и остальные параметры тоже плавающие*/
	}

	/**
	 * Отправляем запрос с уже существующим trace_id, он должен включиться в лог
	 * @param FunctionalTester $I
	 * @return void
	 */
	public function incomingTrace(FunctionalTester $I):void {
		$incomingHeaderContent = Functional::gen_trace_header();
		$traceParts = explode('-', $incomingHeaderContent);
		$I->haveHttpHeader('traceparent', $incomingHeaderContent);
		$response = $I->sendGet('site/api');

		$I->assertEquals(['status' => 'ok'], json_decode($response,true));
		$I->seeResponseCodeIs(200);
		//Сбрасываем лог в файл
		Yii::getLogger()->flush();
		$logFile = Yii::getAlias('@app/runtime/logs/ot-'.date('YmdH').'.log');
		/** @var array $logContents */
		$logContents = file($logFile);
		$I->assertIsArray($logContents);
		$requestLogString = $logContents[count($logContents) - 1];
		$requestLogArray = json_decode($requestLogString, true);

		$I->assertEquals($traceParts[1], $requestLogArray["trace_id"]);
		$I->assertEquals($traceParts[2], $requestLogArray["parent_id"]);
	}

}
