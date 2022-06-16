<?php
declare(strict_types = 1);

use cusodede\opentracing\OpenTracingComponent;
use yii\helpers\ArrayHelper;
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
		$I->assertContains(self::TEST_MESSAGE.'@'.$timestamp, ArrayHelper::getColumn(Yii::getLogger()->messages, '0'));
	}

	/**
	 * @param FunctionalTester $I
	 * @throws Throwable
	 * @throws Exception
	 */
	public function trace(FunctionalTester $I):void {
		$I->amOnRoute('site/index');
		$I->seeResponseCodeIs(200);
		//Принудительно сбрасываем лог в файл
		Yii::getLogger()->flush(true);
		$logFile = Yii::getAlias('@app/runtime/logs/ot-'.date('YmdH').'.log');
		$I->assertFileExists($logFile);
		/** @var array $logContents */
		$logContents = file($logFile);
		$I->assertIsArray($logContents);
		$requestLogString = $logContents[count($logContents) - 2];
		$responseLogString = $logContents[count($logContents) - 1];
		$requestLogArray = json_decode($requestLogString, true);
		$responseLogArray = json_decode($responseLogString, true);


		$I->assertArrayHasKey("TStamp", $requestLogArray);
		$I->assertArrayHasKey("trace_id", $requestLogArray);
		$I->assertArrayHasKey("parent_id", $requestLogArray);
		$I->assertArrayHasKey("span_id", $requestLogArray);
		$I->assertArrayHasKey("req.host", $requestLogArray);
		$I->assertArrayHasKey("req.url", $requestLogArray);
		$I->assertEquals("in", $requestLogArray["direction"]);

		$I->assertArrayHasKey("TStamp", $responseLogArray);
		$I->assertArrayHasKey("trace_id", $responseLogArray);
		$I->assertArrayHasKey("parent_id", $responseLogArray);
		$I->assertArrayHasKey("span_id", $responseLogArray);
		$I->assertArrayHasKey("rsp.http_code", $responseLogArray);
		$I->assertEquals(["content-type" => "text/html; charset=UTF-8"], $responseLogArray["rsp.headers"]);


	}

}
