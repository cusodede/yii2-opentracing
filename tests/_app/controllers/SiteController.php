<?php
declare(strict_types = 1);

namespace app\controllers;

use Yii;
use yii\filters\ContentNegotiator;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\Response;

/**
 * class SiteController
 */
class SiteController extends Controller {
	/**
	 * {@inheritdoc}
	 */
	public function behaviors():array {
		return [
			'contentNegotiator' => [
				'class' => ContentNegotiator::class,
				'formats' => [
					'application/json' => Response::FORMAT_JSON
				],
				'only' => [
					'api'
				]
			],
		];
	}
	/**
	 * @return string
	 */
	public function actionIndex():string {
		return "hello";
	}

	/**
	 * @return string[]
	 */
	public function actionApi():array {
		return ['status' => 'ok'];
	}

	/**
	 * @return string
	 */
	public function actionError():string {
		$exception = Yii::$app->errorHandler->exception;

		if (null !== $exception) {
			return Html::encode($exception->getMessage());
		}
		return "Status: {$exception->statusCode}";
	}
}

