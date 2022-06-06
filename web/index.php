<?php
// comment out the following two lines when deployed to production
require(__DIR__.'/../config/env.php');

require(__DIR__.'/../vendor/autoload.php');
require(__DIR__.'/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__.'/../config/web.php');

(new yii\web\Application($config))->run();

/**
 * Debug manual em tela
 * 
 * @param array $dados
 * @param boolean $exit
 * @param string $titulo
 */
function pred($dados, $exit = true, $titulo = 'Dados do debug')
{
    echo "<pre>";
    print_r($dados);
    echo "</pre>";

    if ($exit) {
        exit;
    }
}
