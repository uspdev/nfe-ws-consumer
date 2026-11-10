<?php

namespace Uspdev\Nfe;

class NfeWsConsumer
{

    protected $srv;
    protected $apiKey;
    protected $debug = 0; // 0 - desativado, 1 - somente erros, 2 - mensagens echo
    protected $sslVerify = true;

    protected $connectTimeout = 15;

    public function __construct($srv, $apiKey, $debug = 0, $sslVerify = true)
    {
        if ('/' != substr($srv, -1)) {
            $srv = $srv . '/';
        }
        $this->srv = $srv;
        $this->apiKey = $apiKey;
        $this->debug = $debug;
        $this->sslVerify = $sslVerify;
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }

    public function setSslVerify($sslVerify) {
        $this->sslVerify = (bool) $sslVerify;
    }

    /**
     * Comunica com o servidor e retorna o estado do certificado digital
     *
     * @return array|string|false Retorna o status em formato array (JSON decodificado) ou string/false em caso de erro
     */
    public function status()
    {
        if ($this->debug == 2) {
            echo 'debug: status: ', $this->srv . 'status', PHP_EOL;
        }
        $response = $this->obterArquivo($this->srv . 'status');
        if ($response === false) {
            return false;
        }
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }
        return $response;
    }

    /**
     * Consulta os dados e arquivos da NFe diretamente pela chave de 44 dígitos
     *
     * @param  string $chave Chave de 44 digitos da NFE
     * @return array|false Retorna o resultado da consulta no formato array
     */
    public function consultaChave($chave)
    {
        $endpoint = $this->srv . 'xml';

        if ($this->debug == 2) {
            echo 'debug: consultaChave: ', $endpoint, PHP_EOL;
        }

        $content = 'chave=' . rawurlencode($chave);
        $response = $this->chamar($endpoint, $content);
        return json_decode($response, true);
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
        $options = [
            'http' => [
                'header' => "X-API-Key: " . $this->apiKey,
                'ignore_errors' => true,
            ],
        ];

        if (!$this->sslVerify) {
            $options['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ];
        }

        $context = stream_context_create($options);
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
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-type: application/x-www-form-urlencoded; charset=UTF-8",
            "X-API-Key: " . $this->apiKey,
        ));

        if (!$this->sslVerify) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        }
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
