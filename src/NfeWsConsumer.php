<?php

namespace Uspdev\Nfe;

class NfeWsConsumer
{

    protected $srv;
    protected $usr;
    protected $pwd;
    protected $debug = 0; // 0 - desativado, 1 - somente erros, 2 - mensagens echo

    protected $connectTimeout = 15;

    public function __construct($srv, $usr, $pwd, $debug = 0)
    {
        if ('/' != substr($srv, -1)) {
            $srv = $srv . '/';
        }
        $this->srv = $srv;
        $this->usr = $usr;
        $this->pwd = $pwd;
        $this->debug = $debug;
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }

    /**
     * status Comunica com o servidor e retorna o estado
     *
     * @return string Retorna a resposta do servidor
     */
    public function status()
    {
        if ($this->debug == 2) {
            echo 'debug: consultaXML: ', $this->srv . 'status', PHP_EOL;
        }
        $status = $this->obterArquivo($this->srv . 'status');
        return $status;
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
     * Envia um XML de NFE para o servidor e retorna a resposta da consulta.
     *
     * O servidor valida o XML e retorna a resposta de consulta do XML junto à sefaz
     * incluindo links para protocolo, danfe, protocolo, etc
     * Em geral as respostas são válidas até 6 meses da emissão
     * Depois disso a sefaz pode remover a consulta retornando status ??
     *
     * nao iremos verificar a integridade do $xml
     *
     * @param  String $xml String contendo o xml da NFE
     * @return Array Retorna o resultado da consulta e dados da NFE no formato array
     */
    public function consultaXML($xml)
    {
        $endpoint = $this->srv . 'xml';

        if ($this->debug == 2) {
            echo 'debug: consultaXML: ', $endpoint, PHP_EOL;
        }

        $content = 'xml=' . rawurlencode($xml); // equivalente a curl_escape()
        $response = $this->chamar($endpoint, $content);
        return json_decode($response, true);
    }

    /**
     * Retorna o DANFE do xml fornecido
     */
    public function obterDanfe($xml)
    {
        $consulta = $this->consultaXML($xml);

        if ($consulta['status'] == 'ok') {
            return SELF::obterArquivo($consulta['url']['danfe']);
        } else {
            return false;
        }
    }

    /**
     * Retorna o relatório de consulta À SEFAZ do xml fornecido
     */
    public function obterSefaz($xml)
    {
        $consulta = $this->consultaXML($xml);

        if ($consulta['status'] == 'ok') {
            return SELF::obterArquivo($consulta['url']['sefaz']);
        } else {
            return false;
        }
    }

    /**
     * Executa a chamada de obter arquivo no servidor remoto
     *
     * @param $url
     * @return Filestream
     */
    public function obterArquivo($url)
    {
        $context = stream_context_create([
            'http' => ['header' => "Authorization: Basic " . base64_encode("$this->usr:$this->pwd")],
        ]);
        return file_get_contents($url, false, $context);
    }

    /**
     * Realiza a chamada CURL no servidor
     *
     * @param String $endpoint
     * @param String $content
     * @return Resposta do CURL
     */
    protected function chamar($endpoint, $content = null)
    {
        $curl = curl_init($endpoint);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->connectTimeout); //timeout in seconds

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->usr . ":" . $this->pwd);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/x-www-form-urlencoded; charset=UTF-8"));
        if ($content) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        } else {
            curl_setopt($curl, CURLOPT_POST, false);
        }
        if ($this->debug > 0) {
            curl_setopt($curl, CURLOPT_VERBOSE, true); // somente para debug
        }

        $response = curl_exec($curl);

        if ($res = $this->curlErrors($curl)) {
            return $res;
        }
        curl_close($curl);

        return $response;
    }

    // em caso de erros
    private function curlErrors($ch)
    {
        if (curl_errno($ch)) {
            return '(' . curl_errno($ch) . ') ' . curl_error($ch) . PHP_EOL;
        }
        $response = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ('200' != $response) {
            return 'Erro: CURLINFO_RESPONSE_CODE=' . $response . PHP_EOL;
        }
        return false;
    }
}
