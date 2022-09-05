<?php
declare(strict_types = 1);

namespace app\models;

use yii\base\Model;
use yii\web\IdentityInterface;

/**
 * Dummy identity stub (required by framework)
 * @property int $id
 * @property string $username Отображаемое имя пользователя
 * @property string $login Логин
 * @property string $password Хеш пароля либо сам пароль (если $salt пустой)
 * @property-read string $authKey @see [[yii\web\IdentityInterface::getAuthKey()]]
 */
class Users extends Model implements IdentityInterface {

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'username' => 'Имя пользователя',
			'login' => 'Логин',
			'password' => 'Пароль',
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function findIdentity($id) {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public static function findIdentityByAccessToken($token, $type = null):?IdentityInterface {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @inheritDoc
	 */
	public function getAuthKey():string {
		return md5($this->id.md5($this->login));
	}

	/**
	 * @inheritDoc
	 */
	public function validateAuthKey($authKey):bool {
		return $this->authKey === $authKey;
	}

}
