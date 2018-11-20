# nfe-ws-consumer

API PHP para consumir dados do webservice nfe-ws

Essa biblioteca poderá ser incorporada à aplicação a fim de tornar transparente a consulta ao webservice nfe-ws.


Dados de acesso ao webservice de nfe\
https://github.com/uspdev/nfe-ws

## Instalação

composer

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