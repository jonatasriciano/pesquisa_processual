<?php

namespace app\components;

use yii\base\Component;

/**
 * Componente criado para prover métodos do antigo websevice SOA, para algumas actions do console
 *
 * @author Carlos Domingues <carlos.domingues@grupoapi.net.br>
 * @since 1.0
 */
class Soa extends Component {
    /**
     * Adiciona um alerta no Zabbix
     * @param type $item
     * @param type $mensagem
     * @return array
     */
    public static function alertaZabbix($item, $mensagem) {
        exec("zabbix_sender -z10.10.20.25 -p10051 -suruguai -k{$item} -o\"Mensagem: {$mensagem}\" 2>&1", $saida, $status);

        return array(
            'status' => $status,
            'saida' => $saida
        );
    }

    /**
     * Remove um alerta no zabbix
     * @param type $item
     * @return type
     */
    public function resolverZabbix($item = arg1) {
        exec("zabbix_sender -z10.10.20.25 -p10051 -suruguai -k{$item} -o\"OK\" 2>&1", $saida, $status);

        return array(
            'status' => $status,
            'saida' => $saida
        );
    }
}
