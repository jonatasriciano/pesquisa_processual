<?php
/**
 * PHPExcel_Autoloader
 *
 * @category    PHPExcel
 * @package     PHPExcel
 * @copyright   Copyright (c) 2006 - 2012 PHPExcel (http://www.codeplex.com/PHPExcel)
 */

class PHPExcel_Autoloader
{
    /**
     * Register the Autoloader with SPL
     *
     */
    public static function Register() {
        if (function_exists('__autoload')) {
            //  Register any existing autoloader function with SPL, so we don't get any clashes
            spl_autoload_register('__autoload');
        }
        $registered = false;
        if(self::beforeAutoloadRegister()) {
            //  Register ourselves with SPL
            $registered = spl_autoload_register(array('PHPExcel_Autoloader', 'Load'));
            self::afterAutoloadRegister();
        }
        
        return $registered;
    }   //  function Register()

    private static function beforeAutoloadRegister()
    {
        //fix for usage in Yii framework v1.1.x (unregister Yii's autoloader)
        if(class_exists('YiiBase') && method_exists('YiiBase','autoload'))
            spl_autoload_unregister(array('YiiBase', 'autoload'));
        
        return true;
    }
    private static function afterAutoloadRegister()
    {
        //fix for usage in Yii framework v1.1.x (re-register Yii's autoloader)
        if(class_exists('YiiBase') && method_exists('YiiBase','autoload'))
            spl_autoload_register(array('YiiBase', 'autoload'));
    }
}
?>