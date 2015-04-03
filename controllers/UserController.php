<?php

namespace app\controllers;

use yii\rest\ActiveController;

/**
 * This is the class controller for listing, view, create, update, delete user model
 *
 * @author Simith D'Oliveira
 */
class UserController extends ActiveController
{
    public $modelClass = 'app\models\User';

}