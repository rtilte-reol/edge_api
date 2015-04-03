<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\CompositeAuth;
use yii\filters\ContentNegotiator;
use yii\filters\RateLimiter;
use yii\filters\VerbFilter;
use yii\web\Response;
use app\assets\Login;
use app\models\User;
use yii\base\Security;

/**
 * This is the class controller for login process
 *
 * @author Simith D'Oliveira
 */
class LoginController extends Controller
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
        return [
                'contentNegotiator' => [
                        'class' => ContentNegotiator::className(),
                        'formats' => [
                            'application/json' => Response::FORMAT_JSON,
                            //'application/xml' => Response::FORMAT_XML,
                        ],
                ],
                'verbFilter' => [
                        'class' => VerbFilter::className(),
                        'actions' => $this->verbs(),
                ],
                'authenticator' => [
                    'class' => CompositeAuth::className(),
                ],
                'rateLimiter' => [
                    'class' => RateLimiter::className(),
                ],
        ];
    }

    /**
     * Login action.
     *
     * @param string $username user name (email)
     * @param string $password user login password
     * @param string $accessToken user access token for auto login
     *
     * @return string access tokens
     * @throws UserException/LoginException
     */
	public function actionLogin($email=null,$password=null,$accessToken=null) {

        // Get the authentication data (email/password OR accessToken)
	    $login = new Login($email,$password,$accessToken);
        // Get the login Token
        $token = $login->login();

        $data = array('token' => $token);
        // Retrieve the user from its access-token and add the full name to the response
        $user = User::findIdentityByAccessToken($token);
        if ($user){
            $data["userfullname"] = $user->getname();
        }
        
        return $data;
	}

    /**
     * Create and send reset password action.
     *
     * @param string $username user name (email)
     * @throws UserException/LoginException
     */
	public function actionCreateAndSendNewPassword($email){
        $login = new Login($email);
        $login->createAndSendNewPassword();
	}

    /**
     * Forget password action.
     *
     * @param string $username user name (email)
     * @throws UserException/LoginException
     */
	public function actionForgetPassword($email)
	{
	    $login = new Login($email);
        $login->forgetPassword();
    }

    /**
    * Temporary function that return the hashed password
    * of a given used identified by an access-token
    *
    * @param string $accessToken access token
    */
    public function actionGetPasswordHash($accessToken)
    {
        $user = User::findIdentityByAccessToken($accessToken);
        $security = new Security();
        $hash = $security->generatePasswordHash($user->password);
        return $hash;
    }
}
