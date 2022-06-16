<?php
declare(strict_types = 1);

use yii\log\Logger;

/**
 * Class UsersCest
 */
class LoggerCest {

	private const TEST_MESSAGE = 'This is commander Sheppard and it is my favorite test!';

	/**
	 * @param FunctionalTester $I
	 * @throws Throwable
	 * @throws Exception
	 */
	public function log(FunctionalTester $I):void {
		$timestamp = time();
		Yii::getLogger()->log(self::TEST_MESSAGE.'@'.$timestamp, Logger::LEVEL_INFO, 'opentracing');
		$logFile = Yii::getAlias('@app/runtime/logs/ot-'.date('YmdH').'.log');

		$I->assertFileExists($logFile);
		$I->openFile($logFile);
		$I->seeInThisFile(self::TEST_MESSAGE.'@'.$timestamp);
		unlink($logFile);//if ok, we don't need this artifact anymore, but it will be recreated by logger itself
	}


	/**
	 * @param FunctionalTester $I
	 * @throws Throwable
	 * @throws Exception
	 */
	public function trace(FunctionalTester $I):void {
		$I->amOnRoute('site/index');
		$I->seeResponseCodeIs(200);
	}



}
