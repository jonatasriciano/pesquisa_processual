<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;


/**
 * Componente de envio de SMS utilizando os serviços da: PG Mais Resultado
 */
class Sms extends Component
{

  const API_RECUPEREMAIS = 'http://api.recuperemais.com.br/';
  const TOKEN_RECUPEREMAIS = 'c64a9829fa4638ff5de86330dd227e35';

  /**
   * Envio de SMS
   */
  public function enviar($sms_to, $sms_msg, $sms_data = NULL){

    $sms_to  = implode(',', array_unique(explode(',', $sms_to)));
    $sms_msg = urlencode(strip_tags($sms_msg));

    $url = self::API_RECUPEREMAIS.'send?token='.self::TOKEN_RECUPEREMAIS.'&sms_to='.$sms_to.'&sms_msg="'.$sms_msg.'"';

    // AAAA-MM-DD HH:MM:SS
    if($sms_data){
      $sms_data = date('Y-m-d H:i:s', strtotime($sms_data));
      $url .= '&sms_data='.urlencode($sms_data);
    }

    // Get cURL resource
    $curl = curl_init();
    // Set some options - we are passing in a useragent too here
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url
    ));
    // Send the request & save response to $xml
    $xml = curl_exec($curl);
    // Close request to clear up some resources
    curl_close($curl);

    xml_parse_into_struct(xml_parser_create(), $xml, $nodes);

    $resumo = array();
    foreach($nodes as $node) {
      if($node['level'] === 2) {
          $fone = $node['value'];
          sleep(5);
          $resumo[$fone] = strtoupper($node['tag']) === 'MSG' ? $this->obterStatus($node['attributes']['ID']) : $this->obterErro($node['attributes']['ID']);
        }
    }
    return $resumo;
  }

  /**
   * Retorna o status do SMS enviado
   */
  public function obterStatus($id_msg){

    $msg_status = array(
      1 => 'Mensagem agendada', // Mensagens que estão na fila aguardando a data de envio.
      2 => 'Mensagem entregue', // Mensagens enviadas e recebidas pela Operadora.
      3 => 'Mensagem não entregue', // Mensagens não entregues ao destinatário.
      4 => 'Bloqueado para recebimento de SMS', // Telefone consultado na operadora e marcado como inválido para recebimento de SMS.
      5 => 'Mensagem cancelada', // Mensagem cancelada para envio.
      6 => 'Telefone inválido ou já enviado neste período' // Telefone Repetido, Blacklist ou formato inválido. É necessária a utilização da ferramenta web para cadastro dos números no Blacklist e configurar a data de verificação de envios repetidos no período.
    );

    $url = self::API_RECUPEREMAIS.'send?token='.self::TOKEN_RECUPEREMAIS.'&id_msg='.$id_msg;

    // Get cURL resource
    $curl = curl_init();
    // Set some options - we are passing in a useragent too here
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url
    ));
    // Send the request & save response to $xml
    $xml = curl_exec($curl);
    // Close request to clear up some resources
    curl_close($curl);

    xml_parse_into_struct(xml_parser_create(), $xml, $nodes);

    foreach($nodes as $node) {
      if($node['level'] === 2) {
        $id = (int)$node['attributes']['ID'];
          return $msg_status[$id];
      }
    }
  }

  /**
   * Retorna o erro do SMS enviado
   */
  public function obterErro($id_msg){

    $msg_status = array(
      1 => 'Erro de autenticação no serviço de SMS',
      2 => 'Erro nos dados informados',
      3 => 'Registros não encontrados',
      4 => 'Mensagem não encontrada no banco de dados',
      5 => 'Telefone Inválido'
    );

    $url = self::API_RECUPEREMAIS.'send?token='.self::TOKEN_RECUPEREMAIS.'&id_msg='.$id_msg;

    // Get cURL resource
    $curl = curl_init();
    // Set some options - we are passing in a useragent too here
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url
    ));
    // Send the request & save response to $xml
    $xml = curl_exec($curl);
    // Close request to clear up some resources
    curl_close($curl);

    xml_parse_into_struct(xml_parser_create(), $xml, $nodes);

    foreach($nodes as $node) {
      if($node['level'] === 2) {
        $id = (int)$node['attributes']['ID'];
          return $msg_status[$id];
      }
    }
  }


}

?>
