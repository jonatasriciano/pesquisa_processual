<?php

namespace app\modules\cp\models;

use Yii;

class Pesquisa {

    /**
     * Verifica se o usuario solicitante etem permissao
     * @param type $hash
     * @return type
     */
    public function login($hash) {

        $sql = "/*PE:1[{$hash}]*/
                SELECT *
                FROM pesquisa_processual.usuario_externo ue
                WHERE hash = '{$hash}'
                AND ativo = TRUE";
        return Yii::$app->db_cp->createCommand($sql)
                        ->queryOne();
    }

    /**
     * Insere a pesquisa efetuada no controle
     * @param type $codUsuario
     * @param type $cnj
     */
    public function controle($codUsuario, $cnj, $callback) {

        //Grava a pesquisa a ser feita
        $arrControle = [];
        $arrControle['cod_usuario_externo'] = $codUsuario;
        $arrControle['cnj'] = $cnj;
        $arrControle['callback'] = $callback;

        Yii::$app->db_cp->createCommand()
                ->insert('pesquisa_processual.pesquisa_controle', $arrControle)->execute();
    }

    /**
     * Verifica se o usuario já tem uma chave cadastrada
     * @param type $codUsuario
     * @return type
     */
    public function getChave($codUsuario) {

        $sql = "/*PE:2[{$codUsuario}]*/
                SELECT chave
                FROM pesquisa_processual.usuario_externo_chave uec
                WHERE cod_usuario_externo = {$codUsuario} AND ativa = true";

        return Yii::$app->db_cp->createCommand($sql)
                        ->queryScalar();
    }

    /**
     * Pega os callbacks nao processados
     * @param type $cnj
     * @return type
     */
    public function getCallback($cnj = null) {

        $sqlAux = ($cnj == null) ? "" : " AND cnj = '" . $cnj . "'  ";

        $sql = "/*PE:3[{$cnj}]*/
                SELECT 
                    codigo, 
                    retorno, 
                    cnj, 
                    hash, 
                    data_cadastro, 
                    data_processamento 
                FROM pesquisa_processual.callback_externo 
                WHERE processado IS NULL {$sqlAux} ORDER BY data_cadastro DESC";
        return Yii::$app->db_cp->createCommand($sql)
                        ->queryAll();
    }

    /**
     * Pega os callbacks processados (dentro de 1 mes)
     * @param type $cnj
     * @return type
     */
    public function getCallbackTrabalhado($cnj) {

        $sql = "/*PE:4[{$cnj}]*/
                SELECT 
                    dados
                FROM pesquisa_processual.callback_externo_trabalhado 
                WHERE cnj = '{$cnj}' AND inclusao >= CURRENT_TIMESTAMP - interval '1 month' 
                ORDER BY codigo DESC 
                LIMIT 1";
        return Yii::$app->db_cp->createCommand($sql)
                        ->queryScalar();
    }

    /**
     * Campos de controle da BipBop para serem removidos
     * @return type
     */
    public function camposRemover() {

        $sql = "/*PE:5*/
                SELECT nome
                FROM pesquisa_processual.callback_campo_remover 
                WHERE ativo = true";
        $arr = Yii::$app->db_cp->createCommand($sql)
                ->queryAll();

        $arrRet = [];
        foreach ($arr as $val) {
            $arrRet[] = $val['nome'];
        }
        return $arrRet;
    }

    /**
     * Cadastra a chave que será utilizada nas consultas junto a BipBop
     * @param type $arrDados
     * @return type
     */
    public function cadastrarChaveFilha($arrDados) {
        return Yii::$app->db_cp->createCommand()
                        ->insert('pesquisa_processual.usuario_externo_chave', $arrDados)->execute();
    }

    /**
     * Cadastra o callback para posterior processamento
     * @param type $arrRetorno
     * @return type
     */
    public function cadastrarCallback($arrRetorno) {
        return Yii::$app->db_cp->createCommand()
                        ->insert('pesquisa_processual.callback_externo', $arrRetorno)->execute();
    }

    /**
     * Pega o segquencial para o cadastro de callback
     * @return type
     */
    public function getCodCallbackTrabalhado() {

        $sql = "/*PE:6*/
                SELECT nextval('pesquisa_processual.callback_externo_trabalhado_codigo_seq'::regclass)";
        return Yii::$app->db_cp->createCommand($sql)
                        ->queryScalar();
    }

    /**
     * Verifica se na solicitacao deve ser devolvida via callback
     * @param type $cnj
     * @param type $hash
     * @return type
     */
    public function getUrlCallback($cnj, $hash) {

        $sql = "/*PE:6*/
                SELECT 
                    codigo, 
                    callback as url 
                FROM pesquisa_processual.pesquisa_controle
                WHERE cnj = '{$cnj}'
                    AND cod_usuario_externo IN
                        (SELECT codigo
                         FROM pesquisa_processual.usuario_externo ue
                         WHERE hash = '$hash')
                    AND callback IS NOT NULL";

        return Yii::$app->db_cp->createCommand($sql)
                        ->queryAll();
    }

    /**
     * Cadastra o callback após todas as informaçoes terem sido trabalhadas
     * @param type $arrDados
     * @return type
     */
    public function cadastrarCallbackTrabalhado($arrDados) {
        return Yii::$app->db_cp->createCommand()
                        ->insert('pesquisa_processual.callback_externo_trabalhado', $arrDados)->execute();
    }

    /**
     * Cadastra o execao de callback processado
     * @param type $arrDados
     * @return type
     */
    public function cadastrarExcecaoCallbackTrabalhado($arrDados) {
        return Yii::$app->db_cp->createCommand()
                        ->insert('pesquisa_processual.callback_externo_exceptions', $arrDados)->execute();
    }

    /**
     * Masrca o callbakc que veio da BipBop como já processado
     * @param type $arrDados
     * @param type $codigo
     * @return type
     */
    public function setProcessado($arrDados, $codigo) {
        return Yii::$app->db_cp->createCommand()
                        ->update('pesquisa_processual.callback_externo', $arrDados, " codigo = {$codigo}")->execute();
    }

    /**
     * Grava o resultado de cada execucao de callback enviado
     * @param type $arr
     * @return type
     */
    public function cadastrarRetornoCallback($arr) {
        return Yii::$app->db_cp->createCommand()
                        ->insert('pesquisa_processual.pesquisa_controle_callback', $arr)->execute();
    }

    /**
     * Grava o PUSH ID (Numero de controle da BiBop para fazermos um force callback
     * @param type $arrDados
     * @param type $codUsuario
     * @param type $cnj
     * @return type
     */
    public function setPushId($arrDados, $codUsuario, $cnj) {
        return Yii::$app->db_cp->createCommand()
                        ->update('pesquisa_processual.pesquisa_controle', $arrDados, " cod_usuario_externo = {$codUsuario} AND cnj = '{$cnj}' ")->execute();
    }

    /**
     * Resumo de pesquisas efetuadas num peridodo especifico
     * @param type $codUsuario
     * @param type $mes
     * @param type $ano
     * @return type
     */
    public function getResumo($codUsuario, $dia, $mes, $ano) {


        $sqlAux = ($dia == null ) ? " AND data BETWEEN '{$ano}-{$mes}-01'::date AND ('{$ano}-{$mes}-' ||EXTRACT(DAY FROM (('{$ano}-' || ({$mes}+1) ||'-01'):: DATE - 1)))::date " : "AND data::date = '{$ano}-{$mes}-{$dia}'::date";

        $sql = "/*PE:7[{$codUsuario}]*/
                SELECT 
                    cnj,
                    data
                FROM pesquisa_processual.pesquisa_controle pc
                WHERE cod_usuario_externo = {$codUsuario} {$sqlAux}";

        return Yii::$app->db_cp->createCommand($sql)
                        ->queryAll();
    }

    public function getListaExcessoes($codUsuario, $cnj) {

        $sqlAux = ($cnj == null) ? "" : " WHERE cnj = '" . $cnj . "'";

        $sql = "/*PE:8[{$codUsuario}]*/
                SELECT
                    cnj,
                    data_processamento,
                    retorno
                FROM pesquisa_processual.callback_externo_exceptions {$sqlAux} 
                ORDER BY  data_processamento DESC";

        return Yii::$app->db_cp->createCommand($sql)
                        ->queryAll();
    }

    /**
     * Pega os dados padrao do sistema
     * @param type $campo
     * @return type
     */
    public function getConfig($campo) {
        $sql = "/*PE:9[{$campo}]*/
                SELECT valor
                FROM integra_conf.config_default_values
                WHERE (campo = '{$campo}' or md5(campo) ='{$campo}') AND ATIVO = 1 ORDER BY codigo DESC LIMIT 1";

        return Yii::$app->db_cp->createCommand($sql)
                        ->queryScalar();
    }

}
