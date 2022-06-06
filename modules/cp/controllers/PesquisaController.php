<?php

namespace app\modules\cp\controllers;

use Yii;
use app\modules\cp\controllers\UtilsController;
use app\modules\cp\models\Pesquisa;
use yii\helpers\Json;

class PesquisaController extends UtilsController {

    public $objJSON;
    private $objModel;
    private $arrGET;
    private $codUsuario;
    private $hash;
    private $nomeUsuario;
    private $emailUsuario;
    public $objResponse;
    private $_keyChild;
    private $pushId;
    private $ipCliente;
    private $log;
    private $logErro;
    private $logVazio;
    private $logException;

    public function beforeAction($action) {
        return parent::beforeAction($action);
    }

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);

        //Pega os dados de post
        $this->arrGET = Yii::$app->request->get();
        $this->objResponse = Yii::$app->response;
        $this->objModel = new Pesquisa();
        $this->objJSON = new Json();
        $this->ipCliente = Yii::$app->request->getUserIP();
        $this->log = __DIR__ . '/../../../runtime/logs/callbacks/' . date('Y-m-d') . '.txt';
        $this->logErro = __DIR__ . '/../../../runtime/logs/callbacks/' . date('Y-m-d') . '-ERRO.txt';
        $this->logVazio = __DIR__ . '/../../../runtime/logs/callbacks/' . date('Y-m-d') . '-VAZIO.txt';
        $this->logException = __DIR__ . '/../../../runtime/logs/callbacks/' . date('Y-m-d') . '-EXCEPTION.txt';



        //Verifica se foi passada alguma hash
        if (isset($this->arrGET['hash'])) {

            //Hash de autenticacao para efetuar a pesquisa
            $dadosAuth = $this->objModel->login($this->arrGET['hash']);

            if (isset($dadosAuth['codigo'])) {
                $this->codUsuario = $dadosAuth['codigo'];
                $this->nomeUsuario = $dadosAuth['nome'];
                $this->emailUsuario = $dadosAuth['email'];
                $this->hash = $dadosAuth['hash'];
            } else {
                // Se falhou a autenticação
                $dadosAuth['resposta'] = "HASH inválido";
                $this->displayResultado(['dados' => $dadosAuth], 401);
            }
        } else {
            //Sem hash, sem resposta
            exit();
        }
    }

    /**
     * Método apenas para receber os callbacks
     * Rota: web/index.php?r=cp/pesquisa/
     */
    public function actionIndex() {

        //Captura os dados enviados
        $request = Yii::$app->request->get();
        $headers = apache_request_headers();
        $rawpost = file_get_contents('php://input');

        try {

            //Log de dados recebidos
            $this->log(date('[d-m-Y] [H:i:s]') . "\n" . print_r($headers, true) . "\n" . print_r($request, true) . "\n " . $rawpost . "\n", $this->log);

            //Formata os dados
            $arrCallback = $this->_formatarCallback($request, $headers, $rawpost);

            //Insere no controle
            $this->objModel->cadastrarCallback($arrCallback);

            //Apos apos receber o resultado, já processa os dados para disponibilzar para o cliente
            $this->actionProcessar();
        } catch (Exception $ex) {
            $this->log("ERRO_" . date('[Y-m-d] [H:i:s]') . "\n" . $ex->getMessage() . "\n", $this->logErro);
        }
    }

    /**
     * Efetua a pesquisa de dados com base no CNJ enviado
     * Rota: web/index.php?r=cp/pesquisa/efetuar
     */
    public function actionEfetuar() {

        $arrDados = null;
        $arrRetorno = [];
        $this->pushId = null;
        $this->log = __DIR__ . '/../../../runtime/logs/callbacks/' . date('Y-m-d') . '.txt';
        $intervalo = $this->objModel->getConfig('pesquisa-intervalo-espera');
        $maximo = $this->objModel->getConfig('pesquisa-maximo-espera');

        try {
            if (isset($this->arrGET['cnj'])) {

                try {
                    $codUsuario = $this->codUsuario;
                    $cnj = $this->arrGET['cnj'];

                    $callback = null;
                    if (isset($this->arrGET['callback'])) {
                        $callback = $this->arrGET['callback'];
                    }

                    //Verifica e formata o CNJ
                    $arr = $this->_verifCnj($cnj);


                    if (isset($arr['numero'])) {

                        //Pega o numero formatado
                        $cnj = $arr['numero'];

                        //Efetua todo tratamento necessário para obter uma chave de consulta válida 
                        $this->_chaveFilha();

                        //Insere no controle 
                        $this->objModel->controle($codUsuario, $cnj, $callback);

                        //Verifica se já temos na base os dados do processo em questao (Para não gastarmos R$ com a BipBop)
                        $arrDados = $this->objModel->getCallbackTrabalhado($cnj);

                        if ($arrDados !== false) {

                            //Caso tenhamos esse dado formatado na base - Envia o ultimo json processado para esse CNJ
                            $this->displayResultado($arrDados, 200, false);
                            exit;
                        } else {

                            //Efetua a pesquisa
                            $arrRetorno = $this->_efetuar($codUsuario, $cnj);

                            //Após efetuar a pesquisa com sucesso aguarda o processamento dos possiveis callbacks 
                            if ($arrRetorno['sucesso'] == true) {
                                //Incrementa o tempo de espera até o dado ser processado
                                for ($i = $intervalo; $i <= $maximo; $i++) {
                                    sleep($i);
                                    $arrDados = $this->objModel->getCallbackTrabalhado($cnj);
                                    if ($arrDados !== false) {
                                        $this->displayResultado($arrDados, 200, false);
                                        exit;
                                    }
                                }

                                //Se, ao fim do limite, não tivermos o resultado, enviamos como timeout 
                                $arrRetorno['timeout'] = true;
                                $this->displayResultado($arrRetorno);
                                exit;
                            } else {
                                $this->displayResultado($arrRetorno);
                                exit;
                            }
                        }
                    } else {

                        $arrRetorno['sucesso'] = false;
                        $arrRetorno['mensagem'] = 'Número CNJ enviado é inválido.';
                        $this->displayResultado($arrRetorno, 401);
                    }
                } catch (\Exception $ex) {
                    $arrRetorno['sucesso'] = false;
                    $arrRetorno['mensagem'] = $ex->getMessage();
                    $this->displayResultado($arrRetorno, 401);
                }
            } else {
                $arrRetorno['sucesso'] = false;
                $arrRetorno['mensagem'] = 'Para efetuar a pesquisa é necessário enviar um número CNJ.';
                $this->displayResultado($arrRetorno, 401);
            }
        } catch (Exception $ex) {
            $this->log("ERRO_" . date('[Y-m-d] [H:i:s]') . "\n" . $ex->getMessage() . "\n", $this->logErro);
        }
    }

    /**
     * Processa os callbacks recebidos com o CNJ em questao
     * Rota: web/index.php?r=cp/pesquisa/processar
     */
    public function actionProcessar() {

        try {
            //Campos que precisam ser removidos do array
            $camposRemover = $this->objModel->camposRemover();
            $cnj = null;
            if (isset($this->arrGET['cnj'])) {
                $cnj = $this->arrGET['cnj'];
            }
            //Logo apos inserir no controle, verifica se esse dado ja cosnta na base
            $arr = $this->objModel->getCallback($cnj);

            //Processa os callbacks conforme necessidade
            $this->_processarCallback($arr, $camposRemover);
        } catch (Exception $ex) {
            $this->log("ERRO_" . date('[Y-m-d] [H:i:s]') . "\n" . $ex->getMessage() . "\n", $this->logErro);
        }
    }

    /**
     * Resumo de consultas efetuadas
     * Rota: web/index.php?r=cp/pesquisa/resumo
     */
    public function actionResumo() {

        try {
            //Campos que precisam ser removidos do array
            $dia = null;
            if (isset($this->arrGET['dia'])) {
                $dia = $this->arrGET['dia'];
            }
            $mes = date("m");
            if (isset($this->arrGET['mes'])) {
                $mes = $this->arrGET['mes'];
            }
            $ano = date("Y");
            if (isset($this->arrGET['ano'])) {
                $ano = $this->arrGET['ano'];
            }

            //Traz todas as consultas
            $arr = $this->objModel->getResumo($this->codUsuario, $dia, $mes, $ano);

            //Inclui os json de resposta
            if (isset($this->arrGET['detalhado'])) {
                foreach ($arr as $k => $val) {
                    $arr[$k]['detalhado'] = json_decode($this->objModel->getCallbackTrabalhado($val['cnj']));
                }
            }
            $this->displayResultado($arr);
        } catch (Exception $ex) {
            $this->log("ERRO_" . date('[Y-m-d] [H:i:s]') . "\n" . $ex->getMessage() . "\n", $this->logErro);
        }
    }

    /**
     * Lista as exessoes retornadas da busca por CNJ
     * Rota: web/index.php?r=cp/pesquisa/exceptions
     */
    public function actionExceptions() {

        try {
            //Verifica se foi passado algum CNJ
            $cnj = null;
            if (isset($this->arrGET['cnj'])) {
                $cnj = $this->arrGET['cnj'];
            }

            //Logo apos inserir no controle, verifica se esse dado ja cosnta na base
            $arr = $this->objModel->getListaExcessoes($this->codUsuario, $cnj);

            foreach ($arr as $k => $val) {

                $arr[$k]['cnj'] = $val['cnj'];
                $arr[$k]['data_processamento'] = $val['data_processamento'];
                $arr[$k]['exception'] = json_decode($val['retorno']);
                unset($arr[$k]['retorno']);
            }
            $this->displayResultado($arr);
        } catch (Exception $ex) {
            $this->log("ERRO_" . date('[Y-m-d] [H:i:s]') . "\n" . $ex->getMessage() . "\n", $this->logErro);
        }
    }

    //Percorre todos os array de array 
    private function _foreachRecursivo($arr, $campoRetorno = null, $camposRemover = []) {

        foreach ($arr as $key => $value) {
            //Recursivo
            if (is_array($value)) {

                $arr[$key] = $this->_foreachRecursivo($value, $campoRetorno, $camposRemover);
            } else {
                //Verifica se e uma data que precisaser formatada
                if (strpos($value, "/") > 0) {
                    $value = $this->_formatDD($value);
                    $arr[$key] = $value;
                }
            }
            //Valor de retorno
            if ($key === $campoRetorno) {
                switch ($campoRetorno) {
                    case "apiKey":
                        $this->_keyChild = $value;
                        break;
                    case "id":
                        $this->pushId = $value;
                        break;
                }
            }
            if (in_array($key, $camposRemover)) {
                unset($arr[$key]);
            }
        }
        return $arr;
    }

    private function _processarCallback($arr, $camposRemover) {

        $codCallTrab = null;
        $arrLimpo = null;

        foreach ($arr as $val) {

            if ($val['retorno'] !== "") {
                $arrAux = $this->xmlToArray(simplexml_load_string($val['retorno']));

                if (isset($arrAux['BPQL']['header']['exception'])) {
                    $arrAux = $arrAux['BPQL']['header']['exception'];
                    $arrAux['data_cadastro'] = $val['data_cadastro'];

                    $arrProcessado = [];
                    $arrProcessado['processado'] = 5;
                    $arrProcessado['data_processamento'] = date('Y-m-d H:i:s');
                    $this->objModel->setProcessado($arrProcessado, $val['codigo']);

                    $arrException = [];
                    $arrException['cnj'] = $val['cnj'];
                    $arrException['retorno'] = json_encode($arrAux);
                    $arrException['cod_callback_exceptions'] = $arrProcessado['processado'];
                    $this->objModel->cadastrarExcecaoCallbackTrabalhado($arrException);

                    $this->log(date('[Y-m-d] [H:i:s]') . "\nCALLBACK PROBLEMATICO:" . $val['codigo'] . "\n", $this->logException);
                    //pred($val,false);
                    continue;
                }

                if (isset($arrAux['BPQL']['body'])) {

                    $arrAux = $arrAux['BPQL']['body'];

                    if (count($arrAux) > 0) {

                        $ret = $this->_verifCnj($val['cnj']);
                        $cnj = $ret['numero'];

                        //Limpa o array com os dados irrelevantes
                        $arrLimpo = $this->_foreachRecursivo($arrAux, null, $camposRemover);


                        $codCallTrab = $this->objModel->getCodCallbackTrabalhado();

                        $arrCallTrab = [];
                        $arrCallTrab['codigo'] = $codCallTrab;
                        $arrCallTrab['cnj'] = $cnj;
                        $arrCallTrab['dados'] = json_encode($arrLimpo);

                        //Cadastra o calback para devolver o dado
                        if ($this->objModel->cadastrarCallbackTrabalhado($arrCallTrab)) {

                            $arrProcessado = [];
                            $arrProcessado['processado'] = 1;
                            $arrProcessado['data_processamento'] = date('Y-m-d H:i:s');
                            $this->objModel->setProcessado($arrProcessado, $val['codigo']);

                            //Apos processar verifica no controle se houve solicitacao de callback
                            $arrUrlCallback = $this->objModel->getUrlCallback($cnj, $val['hash']);
                            $this->_enviarCallback($arrUrlCallback, $arrLimpo);
                        }
                    } else {
                        $arrProcessado = [];
                        $arrProcessado['processado'] = 2;
                        $arrProcessado['data_processamento'] = date('Y-m-d H:i:s');
                        $this->objModel->setProcessado($arrProcessado, $val['codigo']);

                        $arrException = [];
                        $arrException['cnj'] = $val['cnj'];
                        $arrException['retorno'] = json_encode($arrAux);
                        $arrException['cod_callback_exceptions'] = $arrProcessado['processado'];
                        $this->objModel->cadastrarExcecaoCallbackTrabalhado($arrException);
                        $this->log(date('[Y-m-d] [H:i:s]') . "\nCALLBACK VAZIO:" . $val['codigo'] . "\n", $this->logVazio);
                    }
                }
            }
        }

        //PASSO 2: Se o dado já estiver pronto para envio de devolve na solicitação
        if ($codCallTrab !== null) {
            //Envia o json que acabou de ser processado
            $this->displayResultado($arrLimpo);
        }
    }

    private function _chaveFilha() {

        //Verifica a existência de uma chave filha para a empresa
        $this->_keyChild = $this->objModel->getChave($this->codUsuario);

        //Se a empresa não tiver uma chave gerada, de fazer a requisição e cadastrar uma nova
        if ($this->_keyChild == "") {

            //Para gerar a chave deve se usar - cod_usuario_externo e o email
            $chave = $this->codUsuario . $this->emailUsuario;

            //Efetua a requisição para criar a nova chave para a empresa
            $this->_gerarChaveFilha($chave);

            $arrDados = [];
            $arrDados['cod_usuario_externo'] = $this->codUsuario;
            $arrDados['chave'] = $this->_keyChild;
            $arrDados['ativa'] = true;

            //Insere a chave gerada
            $this->objModel->cadastrarChaveFilha($arrDados);
        }
    }

    private function _gerarChaveFilha($chave) {

        //Pega os dados de configuração
        $urlConsulta = $this->objModel->getConfig('url-geracao-chave');
        $keyMaster = $this->objModel->getConfig('master-key-pass-bip-bop');

        //Formata a URL com os dados de pesquisa        
        $urlConsulta = str_replace("{MASTER_KEY}", $keyMaster, $urlConsulta);
        $urlConsulta = str_replace("{EMPRESA}", $chave, $urlConsulta);

        $arrRetorno = $this->getUrl($urlConsulta);
        $this->_foreachRecursivo($arrRetorno, "apiKey");
    }

    private function _efetuar($codUsusario, $cnj) {

        //Efetua consulta de dados de processo com base no cnj
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->_pushHelper($cnj, $this->hash, $this->_keyChild),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $arrReturn = curl_exec($curl);
        $oXML = simplexml_load_string($arrReturn);
        $json = json_encode($oXML);
        $arrReturn = json_decode($json, true);
        $this->_foreachRecursivo($arrReturn, "id");

        //Adiciona o push id a tabela de controle
        $ret = [];
        if ($this->pushId !== null) {
            $arrDados = [];
            $arrDados['push_id'] = $this->pushId;
            $this->objModel->setPushId($arrDados, $codUsusario, $cnj);

            $ret['sucesso'] = true;
            $ret['mensagem'] = "Pesquisa cadastrada com sucesso.";
            $ret['hash_controle'] = $this->pushId;
            return $ret;
        } else {
            $ret['sucesso'] = false;
            $ret['mensagem'] = "Tribunal indisponível no momento. Tentaremos efetuar a consulta novamente em instantes.";
            return $ret;
        }
    }

    private function _callbackHelper($cnj) {
        return sprintf($this->objModel->getConfig('url-callback_externo'), http_build_query([
            'r' => 'cp/pesquisa/',
            'hash' => $this->hash,
            'cnj' => $cnj,
        ]));
    }

    private function _pushHelper($cnj, $hash, $apiKey) {
        $cnj = preg_replace('/[^\d]/', '', $cnj);
        return sprintf('http://irql.bipbop.com.br/?%s', http_build_query([
            'q' => 'INSERT INTO \'PUSH\'.\'JOB\'',
            'pushQuery' => 'SELECT FROM \'JURISTEK\'.\'INFO\'',
            'data' => sprintf('SELECT FROM \'CNJ\'.\'PROCESSO\' WHERE \'cacheIfPossible\' = \'TRUE\' AND \'numero_processo\'=\'%s\'', $cnj),
            'apiKey' => $apiKey,
            'pushCallback' => $this->_callbackHelper($cnj),
            'pushMaxVersion' => '1',
        ]));
    }

    /**
     * Verifica se veio no formato correto
     * @param type $numero
     */
    private function _verifCnj($numero) {

        $arrRet = [];
        //EX: 0280692-29.2016.4.01.9198
        if ((strpos($numero, '-') == 7) && (substr_count($numero, '.') == 4) && (strlen($numero) == 25)) {
            $arrRet['numero'] = $numero;
        } else {
            $numAux = preg_replace('/[^0-9]/', '', $numero);
            if (strlen($numAux) == 20) {
                $arr = array_reverse(str_split(substr($numero, 0, 20)));
                $arr['6'] = $arr['6'] . ".";
                $arr['4'] = $arr['4'] . ".";
                $arr['7'] = $arr['7'] . ".";
                $arr['11'] = $arr['11'] . ".";
                $arr['13'] = $arr['13'] . "-";
                $numero = str_pad(implode("", array_reverse($arr)), 25, '0', STR_PAD_LEFT);
                $arrRet['numero'] = $numero;
            }
        }
        return $arrRet;
    }

    /**
     * Envia os callbacks conforme solicitado pelo usuario pelo parametro de callback
     */
    private function _enviarCallback($arrUrl, $arrEnviar) {

        foreach ($arrUrl as $url) {

            $data = json_encode($arrEnviar);
            $urlEnvio = $url['url'];

            $ch = curl_init($urlEnvio);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
            );

            $info = curl_getinfo($ch);
            $httpCode = $info['http_code'];

            $ret = curl_exec($ch);
            curl_close($ch);

            //Para cada execucao de CURL grava o resultado
            $arrCurl = [];
            $arrCurl['cod_pesquisa_controle'] = $url['codigo'];
            $arrCurl['url'] = $urlEnvio;
            $arrCurl['http_code'] = $httpCode;
            $arrCurl['retorno'] = $ret;

            $this->objModel->cadastrarRetornoCallback($arrCurl);
        }
    }

    /**
     * Funcao apenas para o pessoal da 
     * @param string $data
     * @return string
     */
    private function _formatDD($data) {

        $d = \DateTime::createFromFormat('d/m/Y', $data);
        if ($d) {

            //2019-11-06T02:00:00.000Z
            $arr = explode("/", $data);
            $data = $arr[2] . "-" . $arr[1] . "-" . $arr[0] . "T00:00:00.000Z";
            return $data;
        }
        return $data;
    }

}
