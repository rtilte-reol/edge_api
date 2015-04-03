<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property integer $id
 * @property string $fname
 * @property string $sname
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fname', 'sname'], 'required'],
            [['sname'], 'string'],
            [['fname'], 'string', 'max' => 32]
        ];
    }
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if (isset($token) and strlen($token) == 0){
            $token = null;
        }
        return static::findOne(['access_token' => $token]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fname' => 'Fname',
            'sname' => 'Sname',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey(){}

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey){}

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        if (isset($username) and strlen($username) == 0){
            $username = null;
        }
        return static::findOne(['email' => $username]);
    }

    /**
     * get user full name
     *
     * @return string full name
     */
    public function getName() {
        return $this->fname." ".$this->sname;
    }
}
