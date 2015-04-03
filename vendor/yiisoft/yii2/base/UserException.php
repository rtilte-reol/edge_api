<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * UserException is the base class for exceptions that are meant to be shown to end users.
 * Such exceptions are often caused by mistakes of end users.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class UserException extends Exception
{
    private $field; //failed field name

    public function __construct($message, $field=null)
    {
        $this->field = $field;
        parent::__construct($message);
    }

	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
	    return 'UserException_'.$this->field;
	}

	public function getField() {
	    return $this->field;
	}
}
