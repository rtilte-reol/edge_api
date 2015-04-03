<?php

namespace app\assets;

use Yii;
use app\models\User;
use yii\base\Security;
use yii\base\UserException;
use yii\base\Exception;
use yii\helpers\Url;

/**
 * This is the class that manage Login features
 *
 * @author Simith D'Oliveira
 */
class Login
{
    private $username;
    private $password;
    private $accessToken;

    const MAX_ATTEMPTS = 3;  //max number of login attemps
    const PASSWORD_SIZE = 16; //reset password string size
    const TOKEN_SIZE = 60;   //access token string size
    const LOCK_TIME = 300;   //user login lock time in seconds
    const FORGET_PASSWORD_EMAIL_SUBJECT = "EDGE password reset"; //
    const PASSWORD_FIELD = "password"; //error field password
    const EMAIL_FIELD = "email"; //error field email
    const ACCESS_TOKEN = "access_token"; // access token from auto-login (cookie) error

    /**
     * Class constructor
     *
     * @param string $username user name (email)
     * @param string $password user password
     */
    public function __construct($username,$password=null,$accessToken=null)
	{
       $this->username = $username;
       $this->password = $password;
       $this->accessToken = $accessToken;
	}

	/**
     * Login method.
     *
     * @return string access tokens
     * @throws UserException if '$user' object does not existe.
     */
    public function Login()
    {
        // Try to find the user using the username/password
        $user = User::findByUsername($this->username);
        if ($user){
            $userId = $user->id;
            if($this->validatePassword($user, $this->password)){
                $this->checkLock($userId);
                return $this->generateToken($user);
            } else {
                $this->manageFailedLoginAttempts($userId);
            }
        // Else, try to see if we can authenticate the user from the access-token
        } else if($this->accessToken != null) {
            if($user = User::findIdentityByAccessToken($this->accessToken))
                return $this->accessToken;
            else
                throw new UserException('Incorrect access token', self::ACCESS_TOKEN);
                
        } else {
            throw new UserException('Incorrect user name', self::EMAIL_FIELD);
        }
    }

    /**
     * Manage failed login attempts.
     *
	 * @param int $userId user id
     * @throws LoginException if user login attempts is bigger than max number of attempts
     */
    private function manageFailedLoginAttempts($userId)
    {
        $cache = Yii::$app->cache;
        $attemptsNumber = $cache->get('passwordAttempts_'.$userId);
        if ($attemptsNumber){
            if ($attemptsNumber >= self::MAX_ATTEMPTS){
                $cache->set('lock_'.$userId, time());
                throw new LoginException("You have exceeded the maximum number of attempts. Please wait ".self::LOCK_TIME." seconds and try again.");
            } else {
                $cache->set('passwordAttempts_'.$userId, $attemptsNumber+1);
            }
        } else {
            $cache->set('passwordAttempts_'.$userId, 1);
        }
        $this->createLoginAttemptsMessage($userId);
    }

    /**
     * Create messages for failed login attempts.
     *
     * @param int $userId user id
     * @throws UserException wrong user password
     */
    private function createLoginAttemptsMessage($userId)
    {
        $AttemptsLeft = self::MAX_ATTEMPTS - Yii::$app->cache->get('passwordAttempts_'.$userId);
        $message;
        if ($AttemptsLeft == 1){
            $message = "Incorrect password. You have ".$AttemptsLeft." Attempt";
        } elseif ($AttemptsLeft == 0 ){
            $message = "Incorrect password. Your last Attempt";
        }else {
            $message = "Incorrect password. You have ".$AttemptsLeft." Attempts";
        }
        throw new UserException($message, self::PASSWORD_FIELD);
    }

    /**
     * Verify user login lock.
     *
	 * @param int $userId user id
     * @throws LoginException if a blocked user tries to login before the system lock time
     */
    private function checkLock($userId)
    {
        $lockTime = Yii::$app->cache->get('lock_'.$userId);
        if ($lockTime)
        {
           if (time() < $lockTime + self::LOCK_TIME){
               throw new LoginException('User Locked for '.(self::LOCK_TIME-(time()-$lockTime)).' seconds');
            } else {
                Yii::$app->cache->delete('lock_'.$userId);
                Yii::$app->cache->delete('passwordAttempts_'.$userId);
            }
        } else {
          Yii::$app->cache->delete('passwordAttempts_'.$userId);
        }
    }

    /**
     * Generate and save access token.
     *
	 * @param object $user
     * @return string - access token
     */
    private function generateToken($user)
    {
        $security = new Security();
        // unique token
        do{
            $token = $security->generateRandomString(self::TOKEN_SIZE);
        } while ($user::find()->where(['access_token' => $token ])->count() != 0);

        // save new token
        $user->access_token = $token;
        $user->save();
        return $token;
    }
    
    /**
     * Forget password method.
     *
     * @return bool
     * @throws UserException if '$user' object does not existe.
     */
    public function forgetPassword()
    {
        $user = User::findByUsername($this->username);
        if ($user){
            // delete token
            $user->access_token = null;
            $user->save();

            $this->sendResetUrl($user);
        } else {
            throw new UserException('Email not found',self::EMAIL_FIELD);
        }
    }

    /**
     * Create, save (reset) and send new password to the user.
     *
     * @return bool
     * @throws UserException if '$user' object does not existe.
     */
    public function createAndSendNewPassword(){
        //decode user
        $security = new Security();
        $email = $security->decryptByKey(base64_decode($this->username),Yii::$app->params['encryptKey']);
	
        $user = User::findByUsername($email);
        if ($user){
            $newPassword = $this->resetPassword($user);
            // save reset password
            $user->password = $security->generatePasswordHash($newPassword);
            $user->save();

            $this->sendNewPassword($user,$newPassword);
        } else {
            throw new UserException('Email not found',self::EMAIL_FIELD);
        }
    }

    /**
     * Send a reset url to the user.
     *
     * @param object $user
     */
    private function sendResetUrl($user){
        $to = $user->email;
        $subject = self::FORGET_PASSWORD_EMAIL_SUBJECT;
        $headers = "From: ".Yii::$app->params['adminEmail'];
        $from = Yii::$app->params['noreplyEmail'];
        $url = $this->createResetPasswordUrl($user);
        //TODO use yii2 framework to pass params to the email view template
        //$params = array('first_name' => $user->fname, 'last_name' => $user->sname, 'reset_password_url' => $this->createResetPasswordUrl($user));

        //TODO remove as soon as yii2 framework start to work
        $txt ="Dear $user->fname $user->sname,

Forgot your password? Please click on the link below to reset it and gain access to your EDGE account: $url

If you did not submit the request to change your password, please ignore this e-mail.

Thank you.
The EDGE Team";
        
        $this->sendEmail($to,$from,$subject,$txt, null);
    }

    /**
     * Send a reset password to the user.
     *
     * @param object $user
     * @param string reset password
     */
    private function sendNewPassword($user,$newPassword){
        $to = $user->email;
        $from = Yii::$app->params['noreplyEmail'];
        $subject = self::FORGET_PASSWORD_EMAIL_SUBJECT;
        $headers = "From: ".Yii::$app->params['adminEmail'];
        //$txt = "new password: ".$newPassword;
        
        //TODO remove as soon as yii2 framework start to work
        $txt = "Dear $user->fname $user->sname,

Your new password was successfully created. You will be able to login using the following password:

Password: $newPassword

You can change this password yourself via the profile page.

Thank you.
The EDGE Team";
    
        $this->sendEmail($to,$from,$subject,$txt,null);
    }
    
    /**
     * Basic method to send email to the user.
     *
     * @param string $to      user email address
     * @param string $from    intern email address
     * @param string $subject email subject
     * @param string $params  params that will be changed in the view template
     * @throws LoginException if the mail failed to be accepted for delivery
     */
    private function sendEmail($to,$from,$subject,$txt,$params){
        //yii2 default mailing
        
        //TODO use yii2 framework to pass params to the email view template
        //$mail =  Yii::$app->mailer->compose('forgetPassword', $params)
        $mail =  Yii::$app->mailer->compose()
        ->setFrom($from)
        ->setTo($to)
        ->setSubject($subject)
        ->setTextBody($txt)
        ->send();

        if (!$mail){
            throw new LoginException("mail failed to be accepted for delivery");
        }
    }

    /**
     * Create a Basic method to send email to the user.
     *
     * @param object user
     */
    private function createResetPasswordUrl($user) {
        $security = new Security();
        $encryptedEmail = urlencode(base64_encode($security->encryptByKey($user->email, Yii::$app->params['encryptKey'])));

        $domain = Yii::$app->request->referrer;
        return $domain."#/reset?email=".$encryptedEmail;
    }

    /**
     * Generate and save password.
     *
     * @param object $user
     * @return string - reset password
     */
    private function resetPassword($user)
    {
        $security = new Security();
        $password = $security->generateRandomString(self::PASSWORD_SIZE);

        // save new password
        $user->password = $security->generatePasswordHash($password);
        $user->save();
        return $password;
    }

    /**
     * Validates password
     *
     * @param  object  $user user object
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($user,$password)
    {
        $security = new Security();
        return $user->password === $password || $security->validatePassword($password, $user->password);
    }
}
