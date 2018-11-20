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