# nfe-ws Consumer

API PHP para consumir dados do webservice [nfe-ws](https://github.com/uspdev/nfe-ws).

A verificação automática de uma nota fiscal (XML) depende de validar a estutura do documento, sua assinatura e consultar a Secretaria da Fazenda (SEFAZ) correspondente quanto a sua autorização de utilização bem como os eventos subsequentes relacionados à nota fiscal.

Devido à dificuldade inerente ao processo, foi criado um servidor que responde à requisições rest [nfe-ws](https://github.com/uspdev/nfe-ws). A finalidade desse servidor é tratar as informações específicas da nota fiscal e retornar ao programa dados já processados bem como dados referentes à consulta da validade junto à SEFAZ.

Dessa forma a consulta da validade de um XML bem como a geração de sua DANFE é feita numa simples requisição por meio dessa classe que implementa um consumer para esse servidor.


## Dependências

* php-curl

## Instalação

`composer require uspdev/nfe-ws-consumer`

## Exemplo

```php
<?php
include '../vendor/autoload.php';
use Uspdev\Nfe\NfeWsConsumer;

$srv = 'http://servidor.eesc.usp.br/nfe-ws/api/';
$apiKey = 'sua-api-key-aqui';
$xml = file_get_contents('nfe.xml');

$sefaz = new NfeWsConsumer($srv, $apiKey);
$ret = $sefaz->consultaXML($xml);

print_r($ret);
```

Resposta
```
Array
(
    [status] => ok
    [url] => Array
        (
            [xml] => http://servidor/nfe-ws/xml/chave-da-nfe-nfe.xml
            [proto] => http://servidor/nfe-ws/prot/chave-da-nfe-prot.xml
            [sefaz] => http://servidor/nfe-ws/sefaz/chave-da-nfe-prot.pdf
            [danfe] => http://servidor/nfe-ws/danfe/chave-da-nfe-danfe.pdf
        )

    [xml] => Array
        (
            [status] => ok
            [estrutura] => Estrutura do XML está OK
            [assinatura] => Assinatura ok
            [digest] => Digest ok
            [modelo] => 55
            [import] => Importado com sucesso
            [versao] => 4.00
        )

    [chave] => chave-da-nfe
    [prot] => Array
        (
            [age] => 20
            [cStat] => 100
            [xMotivo] => Autorizado o uso da NF-e
            [tpAmb] => 1
            [dhConsulta] => 21/11/2022 - 15:05:46
            [status] => ok
            [eventos] => Array
                (
                    [0] => Array
                        (
                            [tpEvento] => 100
                            [descEvento] => Autorizado o uso da NF-e
                            [nProt] => 135221386812053
                            [dhEvento] => 10/10/2022 - 16:14:16
                            [digVal] => vmQ/8gcUAxVUTKl7UzulmDess+Q=
                        )

                )

            [raw] => <?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><soap:Body>
            ...
            </soap:Body></soap:Envelope>
        )

    [sefaz] => Array
        (
            [age] => 20
            [cStat] => 100
            [xMotivo] => Autorizado o uso da NF-e
            [dhConsulta] => 21/11/2022 - 15:05:46
            [tpAmb] => 1
            [versao] => uspdev/NFE-WS v2.0.7
        )

    [nfe] => Array
        (
            [ide] => Array
                (
                    [nro] => 999
                    [serie] => 111
                    [dataemi] => 10/10/2022 - 16:13:25
                    [total] => 180,00
                )

            [emit] => Array
                (
                    [cnpj] => CNPJ EMIT
                    [nome] => RAZÃO SOCIAL
                    [mun] => MUNICÍPIO
                    [uf] => SP
                )

            [dest] => Array
                (
                    [cnpj] => CNPJ/CPF DEST
                    [nome] => NOME DEST
                )

            [infadic] => DADOS ADICIONAIS DA NFE
        )
)
```

## Métodos

### Sumário dos Métodos

| Método | Retorno | Descrição |
|---|---|---|
| [`__construct($srv, $apiKey, $debug = 0, $sslVerify = true)`](#__construct) | `void` | Inicializa o cliente consumidor com a URL da API e a API key. |
| [`consultaXML($xml)`](#consultaxml) | `array` | Valida o XML, consulta a SEFAZ e gera os links para download dos documentos. |
| [`consultaChave($chave)`](#consultachave) | `array` | Consulta os dados e arquivos da NF-e diretamente pela chave de 44 dígitos. |
| [`status()`](#status) | `array\|string\|false` | Retorna o status de conexão e os dados do certificado digital ICP-Brasil em JSON/array. |
| [`obterDanfe($xml)`](#obterdanfe) | `string\|false` | Atalho que processa o XML e já retorna o conteúdo binário do PDF do DANFE. |
| [`obterSefaz($xml)`](#obtersefaz) | `string\|false` | Atalho que processa o XML e já retorna o conteúdo binário do PDF do protocolo SEFAZ. |
| [`obterArquivo($url)`](#obterarquivo) | `string` | Faz o download de um arquivo do webservice a partir de sua URL. |
| [`setDebug($debug)`](#setdebug) | `void` | Altera o nível de depuração da instância em tempo de execução. |
| [`setSslVerify($sslVerify)`](#setsslverify) | `void` | Ativa ou desativa a verificação de certificados SSL (útil para desenvolvimento). |

---

### `__construct()`

```php
public function __construct(string $srv, string $apiKey, int $debug = 0)
```

Inicializa o cliente consumidor do webservice.

* **Parâmetros:**
  * `$srv` *(string)*: URL base da API do webservice `nfe-ws` (ex: `'https://servidor.eesc.usp.br/nfe-ws/api/'`). Caso a barra final seja omitida, ela será incluída automaticamente.
  * `$apiKey` *(string)*: Chave de API de acesso gerada no servidor via `php users.php add <nome>`.
  * `$debug` *(int, opcional)*: Nível de depuração (padrão: `0`):
    * `0`: Desativado.
    * `1`: Exibe erros detalhados e saída do cURL em modo verbose.
    * `2`: Exibe mensagens de endpoint acessado (`echo`).

---

### `consultaXML()`

```php
public function consultaXML(string $xml): array
```

Envia a string XML da NF-e para validação de estrutura, verificação de assinatura e consulta em tempo real junto à SEFAZ. O webservice gera os PDFs do DANFE e do protocolo SEFAZ e retorna um array associativo completo com os dados da nota.

* **Parâmetros:**
  * `$xml` *(string)*: Conteúdo completo do XML da NF-e.
* **Retorno:**
  * `array`: Estrutura com situação da validação (`xml`), dados do protocolo (`prot`), dados cadastrais da nota (`nfe`) e URLs dos arquivos gerados (`url`).

```php
$sefaz = new NfeWsConsumer($srv, $apiKey);
$xml = file_get_contents('nfe.xml');

$resultado = $sefaz->consultaXML($xml);

if ($resultado['status'] === 'ok') {
    echo "Chave da NF-e: " . $resultado['chave'] . PHP_EOL;
    echo "Status SEFAZ: " . $resultado['prot']['xMotivo'] . PHP_EOL;
    echo "Link do DANFE: " . $resultado['url']['danfe'] . PHP_EOL;
}
```

---

### `consultaChave()`

```php
public function consultaChave(string $chave): array|false
```

Consulta uma nota fiscal diretamente pela sua chave de 44 dígitos junto à SEFAZ.

* **Parâmetros:**
  * `$chave` (`string`): Chave de 44 dígitos da NF-e.
* **Retorno:**
  * `array|false`: Resposta estruturada com dados do protocolo e URLs dos documentos.

---

### `status()`

```php
public function status(): array|string|false
```

Verifica a conectividade com o webservice e a validade do certificado digital ICP-Brasil instalado no servidor em formato JSON/array.

* **Retorno:**
  * `array`: Dados estruturados contendo `status`, `cnpj`, `empresa`, `validade`, `valid_to`, `expirado` e `mensagem`.
  * `false`: Falha de comunicação.

```php
$sefaz = new NfeWsConsumer($srv, $apiKey);
$status = $sefaz->status();

print_r($status);
/* Exemplo de saída:
Array
(
    [status] => ok
    [cnpj] => 63025530002824
    [empresa] => UNIVERSIDADE DE SAO PAULO:63025530002824
    [validade] => 11/02/2027
    [valid_to] => 2027-02-11T12:00:00+00:00
    [expirado] => false
    [mensagem] => Certificado VÁLIDO!
)
*/
```

---

### `obterDanfe()`

```php
public function obterDanfe(string $xml): string|false
```

Atalho que processa o XML fornecido e já realiza o download do binário do DANFE gerado em formato PDF.

* **Parâmetros:**
  * `$xml` *(string)*: Conteúdo completo do XML da NF-e.
* **Retorno:**
  * `string`: Conteúdo binário do arquivo PDF do DANFE.
  * `false`: Caso ocorra erro na validação ou consulta.

```php
$sefaz = new NfeWsConsumer($srv, $apiKey);
$xml = file_get_contents('nfe.xml');

$pdfDanfe = $sefaz->obterDanfe($xml);

if ($pdfDanfe !== false) {
    file_put_contents('danfe.pdf', $pdfDanfe);
    echo "DANFE salvo em danfe.pdf" . PHP_EOL;
}
```

---

### `obterSefaz()`

```php
public function obterSefaz(string $xml): string|false
```

Atalho que processa o XML fornecido e já realiza o download do binário do relatório em PDF do protocolo e eventos retornados pela SEFAZ.

* **Parâmetros:**
  * `$xml` *(string)*: Conteúdo completo do XML da NF-e.
* **Retorno:**
  * `string`: Conteúdo binário do arquivo PDF do relatório SEFAZ.
  * `false`: Caso ocorra erro na validação ou consulta.

```php
$sefaz = new NfeWsConsumer($srv, $apiKey);
$xml = file_get_contents('nfe.xml');

$pdfSefaz = $sefaz->obterSefaz($xml);

if ($pdfSefaz !== false) {
    file_put_contents('protocolo-sefaz.pdf', $pdfSefaz);
    echo "Relatório SEFAZ salvo em protocolo-sefaz.pdf" . PHP_EOL;
}
```

---

### `obterArquivo()`

```php
public function obterArquivo(string $url): string
```

Faz o download direto de qualquer arquivo gerado no webservice a partir de sua URL (retornada no array de `$resultado['url']`). A chamada inclui o cabeçalho de autenticação `X-API-Key` automaticamente.

* **Parâmetros:**
  * `$url` *(string)*: URL completa do recurso no webservice (ex: `$resultado['url']['xml']` ou `$resultado['url']['proto']`).
* **Retorno:**
  * `string`: Conteúdo em bytes (binário ou texto) do arquivo remoto baixado.

```php
$sefaz = new NfeWsConsumer($srv, $apiKey);
$res = $sefaz->consultaXML($xml);

if ($res['status'] === 'ok') {
    // Baixa o XML da NF-e armazenado no servidor
    $xmlNfe = $sefaz->obterArquivo($res['url']['xml']);
    file_put_contents('nfe-salva.xml', $xmlNfe);

    // Baixa o XML do protocolo retornado pela SEFAZ
    $xmlProt = $sefaz->obterArquivo($res['url']['proto']);
    file_put_contents('protocolo-sefaz.xml', $xmlProt);
}

// Exemplo de proxy web: entrega o PDF para o navegador sem expor a API Key
$pdf = $sefaz->obterArquivo($res['url']['danfe']);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="danfe.pdf"');
echo $pdf;
exit;
```

---

### `setDebug()`

```php
public function setDebug(int $debug): void
```

Altera o modo de depuração da instância em tempo de execução.

* **Parâmetros:**
  * `$debug` *(int)*: Nível de debug (`0` = desativado, `1` = cURL verbose, `2` = mensagens via echo).


