<?php

namespace app\assets;

use yii\base\Exception;

/**
 * LoginException is the class for exceptions concerning Login process that are meant to be shown to end users.
 *
 * @author Simith D'Oliveira
 */

class LoginException extends Exception
{
	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
	    return 'Login Exception';
	}
}
