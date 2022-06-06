<?php

namespace app\modules\cp\controllers;

use yii\web\Controller;
use yii\helpers\Json;

class UtilsController extends Controller {

    /**
     * Envia uma requisição e retorna os dados
     * @param type $url
     * @return type
     */
    public function getUrl($url) {

        $retValue = file_get_contents($url);
        $oXML = simplexml_load_string($retValue);
        $json = json_encode($oXML);

        return json_decode($json, true);
    }

    /**
     * Formata o callback que veio da BipBop para inserirmos no banco
     * @param type $request
     * @param type $headers
     * @param type $rawpost
     * @return type
     */
    public function _formatarCallback($request, $headers, $rawpost) {

        $objJSON = new Json();

        $arrCallback = [];
        $arrHeader = [];
        foreach ($headers as $header => $value) {
            $arrHeader[$header] = $value;
        }
        $arrCallback['header'] = $objJSON->encode($arrHeader);
        $arrCallback['retorno'] = $rawpost;
        foreach ($request as $k => $v) {
            switch ($k) {
                case "r":
                    $arrCallback['rota'] = $v;
                    break;
                case "hash":
                    $arrCallback['hash'] = $v;
                    break;
                case "cnj":
                    $arrCallback['cnj'] = $v;
                    break;
            }
        }
        return $arrCallback;
    }

    /**
     * Funcao completa para capturar TODOS os atributos que podem vir em um XML => ARRAY
     * @param type $xml
     * @param type $options
     * @return type
     */
    public function xmlToArray($xml, $options = array()) {

        $defaults = [
            'namespaceSeparator' => ':', //Separador
            'attributePrefix' => '@', //Apenas pra distinguir attr de nó
            'alwaysArray' => array(), //Array de nomes de tags xml que sempre devem se tornar arrays
            'autoArray' => true, //Apenas cria matrizes para tags que aparecem mais de uma vez
            'textContent' => '$', //Chave usada para o conteúdo de texto dos elementos
            'autoText' => true, //Pula a chave textContent se o nó não tiver atributos ou nós filhos
            'keySearch' => false, //Pesquisa opcional e substituição nos nomes de tags e atributos
            'keyReplace' => false           //Substitui valores pelos valores de pesquisa acima (conforme passado para str_replace ())
        ];

        $options = array_merge($defaults, $options);
        $namespaces = $xml->getDocNamespaces();
        $namespaces[''] = null;

        $attributesArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
                if ($options['keySearch']) {
                    $attributeName = str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
                }
                $attributeKey = $options['attributePrefix']
                        . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                        . $attributeName;
                $attributesArray[$attributeKey] = (string) $attribute;
            }
        }

        $tagsArray = [];
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->children($namespace) as $childXml) {
                $childArray = $this->xmlToArray($childXml, $options);
                list($childTagName, $childProperties) = each($childArray);

                if ($options['keySearch']) {
                    $childTagName = str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
                }

                if ($prefix) {
                    $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;
                }

                if (!isset($tagsArray[$childTagName])) {
                    $tagsArray[$childTagName] = in_array($childTagName, $options['alwaysArray']) || !$options['autoArray'] ? array($childProperties) : $childProperties;
                } elseif (
                        is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName]) === range(0, count($tagsArray[$childTagName]) - 1)
                ) {
                    $tagsArray[$childTagName][] = $childProperties;
                } else {
                    $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
                }
            }
        }

        $textContentArray = array();
        $plainText = trim((string) $xml);
        if ($plainText !== '') {
            $textContentArray[$options['textContent']] = $plainText;
        }


        $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '') ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

        return [$xml->getName() => $propertiesArray];
    }

    /**
     * Formata e envia o resultado para o navegador
     * @param type $resultado
     * @param type $httpCode
     * @param type $encode
     */
    public function displayResultado($resultado, $httpCode = null, $encode = true) {

        $objJSON = new Json();
        $objResponse = \Yii::$app->response;
        $objResponse->format = $objResponse::FORMAT_JSON;

        $httpCodeAux = !empty($httpCode) ? $httpCode : (isset($resultado['http_code']) ? $resultado['http_code'] : 200);
        $objResponse->setStatusCode($httpCodeAux);
        if ($encode) {
            $objResponse->content = $objJSON->encode($resultado);
        } else {
            $objResponse->content = $resultado;
        }
        $objResponse->send();
    }

    /**
     * LOG de Acoes e Erros
     * @param type $string
     * @param type $name
     * @param type $et
     * @param type $ql
     * @param type $d
     */
    public function log($string, $name, $et = false, $ql = false, $d = false) {
        $strData = ($d) ? "\r\n" . date('d/m/Y') . " " : "";

        // Exibe em tela cada mensagem
        $str = ($ql == true ? "\r\n" : "") . $strData . date('H:i:s') . " - {$string}\r\n";

        if ($et) {
            echo $str;
        }
        // Escreve cada linha no arquivo de log
        file_put_contents($name, $str, FILE_APPEND);
    }

}
