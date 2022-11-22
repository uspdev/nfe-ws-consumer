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
$xml = file_get_contents('nfe.xml');

$sefaz = new NfeWsConsumer($srv, 'usr', 'pwd');
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

* status()
* consultaXML()
* obterDanfe()
* obterSefaz()
* obterArquivo()

