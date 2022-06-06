<?php

namespace app\modules\cp\controllers;

use Yii;
use app\modules\cp\controllers\UtilsController;

class TesteController extends UtilsController {

    public $objJSON;
    public $objResponse;
    private $ipCliente;
    private $log;

    public function beforeAction($action) {
        return parent::beforeAction($action);
    }

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);

        $this->ipCliente = Yii::$app->request->getUserIP();
        $this->log = __DIR__ . '/../../../runtime/logs/callbacks/' . date('Y-m-d') . '-TESTE.txt';
    }

    /**
     * Método apenas para receber os callbacks
     * Rota: web/index.php?r=cp/teste/
     */
    public function actionIndex() {

        //Captura os dados enviados
        $request = Yii::$app->request->get();
        $headers = apache_request_headers();
        $post = Yii::$app->request->post();

        try {

            //Log de dados recebidos
            $this->log(date('[d-m-Y] [H:i:s]') . "\n" . print_r($headers, true) . "\n" . print_r($request, true) . "\n " . print_r($post, true) . "\n", $this->log);

            $this->displayResultado(json_encode($post));
        } catch (Exception $ex) {
            $this->log("ERRO_" . date('[Y-m-d] [H:i:s]') . "\n" . $ex->getMessage() . "\n", $this->log);
        }
    }

}
