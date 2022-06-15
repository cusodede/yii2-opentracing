<?php
declare(strict_types = 1);

use yii\base\InvalidConfigException;
use yii\log\Logger;

/**
 * Class UsersCest
 */
class LoggerCest {

	/**
	 * @param FunctionalTester $I
	 * @throws Throwable
	 * @throws InvalidConfigException
	 * @throws Exception
	 */
	public function log(FunctionalTester $I):void {
		Yii::getLogger()->log('sosi jopu', Logger::LEVEL_INFO, 'opentracing');

		$I->amOnRoute('site/index');
		$I->seeResponseCodeIs(200);
	}

}
