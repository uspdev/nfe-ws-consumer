<?php

namespace Uspdev\Nfe;

class NfeWsConsumer
{

    protected $srv;
    protected $usr;
    protected $pwd;

    public function __construct($srv, $usr, $pwd)
    {
        if ('/' != substr($srv, -1)) {
            $srv = $srv . '/';
        }
        $this->srv = $srv;
        $this->usr = $usr;
        $this->pwd = $pwd;
    }

    /**
     * status Comunica com o servidor e retorna o estado
     *
     * @return string Retorna a resposta do servidor
     */
    public function status()
    {
        $endpoint = 'status';

        $curl = curl_init($this->srv . $endpoint);

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->usr . ":" . $this->pwd);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/x-www-form-urlencoded; charset=UTF-8"));
        curl_setopt($curl, CURLOPT_POST, true);
        #curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        //curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        #curl_setopt($curl, CURLOPT_VERBOSE, true); // somente para debug

        $json_response = curl_exec($curl);

        // verifica error
        if ($res = $this->curlErrors($curl)) {
            return $res;
        }

        curl_close($curl);

        $response = json_decode($json_response, true);
        return $response;
    }

    /**
     * consultaChave
     *
     * @param  string $chave Chave de 44 digitos da NFE
     *
     * @return array Retorna o resultado da consulta no formato array
     */
    public function consultaChave($chave)
    {

    }

    /**
     * consultaXML
     *
     * Envia um XML de NFE para o servidor e retorna a resposta da consulta.
     * O servidor valida o XML e retorna a resposta de consulta do XML junto à sefaz
     * Em geral as respostas são válidas até 6 meses da emissão
     * Depois disso a sefaz pode remover a consulta retornando status ??
     * documento antigo ??? (ou algo assim)
     *
     * @param  String $xml String contendo o xml da NFE
     *
     * @return Array Retorna o resultado da consulta e dados da NFE no formato array
     */
    public function consultaXML($xml)
    {
        // nao iremos verificar a integridade do $xml

        $endpoint = 'xml';

        $curl = curl_init($this->srv . $endpoint);

        // precisamos escapar pois alguns xmls tem caracteres especiais
        $content = 'xml=' . curl_escape($curl, $xml);

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->usr . ":" . $this->pwd);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/x-www-form-urlencoded; charset=UTF-8"));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        #curl_setopt($curl, CURLOPT_VERBOSE, true); // somente para debug

        $json_response = curl_exec($curl);

        if ($res = $this->curlErrors($curl)) {
            return $res;
        }

        curl_close($curl);

        $response = json_decode($json_response, true);
        return $response;
    }

    private function curlErrors($ch) {
        // em caso de erros
        if (curl_errno($ch)) {
            return '('.curl_errno($ch) . ') ' . curl_error($ch) . PHP_EOL;
        }

        $response = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ('200' != $response) {
            return 'Erro: CURLINFO_RESPONSE_CODE=' . $response . PHP_EOL;
        }

        return false;
    }
}
