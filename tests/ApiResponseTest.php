<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev\SmsDev;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Middleware;

/**
 * Test if the class can parse the API responses correctly.
 * 
 * @see https://www.smsdev.com.br/ SMSDev API specification.
 */
final class ApiResponseTest extends SmsDevMock
{
    public function testGetBalance()
    {
        $apiResponse = '{"situacao":"OK","saldo_sms":"1200","descricao":"SALDO ATUAL"}';

        $SmsDev = $this->getServiceMock($apiResponse);

        $this->assertSame(1200, $SmsDev->getBalance());
    }

    public function testGetBalance_EmptyResponse()
    {
        $apiResponse = '';

        $SmsDev = $this->getServiceMock($apiResponse);

        $this->assertSame(0, $SmsDev->getBalance());
    }

    /**
     * @dataProvider sendDataProvider
     */
    public function testSend($number, $message, $expectedResponse, $apiResponse)
    {
        $SmsDev = $this->getServiceMock($apiResponse);

        $this->assertSame($expectedResponse, $SmsDev->send($number, $message));
    }

    /**
     * @codeCoverageIgnore
     */
    public function sendDataProvider()
    {
        return [

            // number,         message,     expectedResponse,   apiResponse
            [ '1188881000',   'Message',    true,               '{"situacao": "OK", "codigo": "1", "id": "637849052", "descricao": "MENSAGEM NA FILA" }' ],
            [ '1188881000',   '',           false,              '{"situacao":"ERRO","codigo":"400","descricao":"MENSAGEM NAO DEFINIDA."}' ],
            [ '118888100',    'Message',    true,               '{"situacao":"OK","codigo":"1","id":"645106333","descricao":"MENSAGEM NA FILA"}' ],
            [ '11888810009',  'Message',    true,               '{"situacao":"OK","codigo":"1","id":"645106334","descricao":"MENSAGEM NA FILA"}' ],
            [ 'abc',          'Message',    false,              '{"situacao":"ERRO","codigo":"402","descricao":"SEM NUMERO DESTINATARIO."}' ],
            [ '',             'Message',    false,              '{"situacao":"ERRO","codigo":"402","descricao":"SEM NUMERO DESTINATARIO."}' ],

        ];
    }

    public function testFilterByUnread()
    {
        $apiResponse = '[{"situacao":"OK","data_read":"19\/01\/2018 11:35:14","telefone":"5511988887777","id":"","refer_id":"","nome":"","msg_sent":"","id_sms_read":"2515974","descricao":"Resposta"}]';

        $SmsDev = $this->getServiceMock($apiResponse);

        $SmsDev->setDateFormat('Y-m-d H:i:s')
            ->setFilter()
                ->isUnread()
            ->fetch();

        $parsedMessages = current($SmsDev->parsedMessages());
        
        $this->assertSame('2018-01-19 13:35:14', $parsedMessages['date']); // UTC conversion
        $this->assertSame('5511988887777',       $parsedMessages['number']);
        $this->assertSame('Resposta',            $parsedMessages['message']);
    }

    public function testFilterByUnread_EmptyResponse()
    {
        $SmsDev = $this->getServiceMock('');

        $SmsDev->setDateFormat('Y-m-d H:i:s')
            ->setFilter()
                ->isUnread()
            ->fetch();

        $this->assertEmpty($SmsDev->getResult());
        $this->assertEmpty($SmsDev->parsedMessages());
    }


    public function testFilterById()
    {
        $apiResponse = '[{"situacao":"OK","data_read":"19\/01\/2018 11:35:14","telefone":"5511988887777","id":"","refer_id":"","nome":"","msg_sent":"","id_sms_read":"2515974","descricao":"Resposta"}]';
        
        $SmsDev = $this->getServiceMock($apiResponse);

        $SmsDev->setFilter()
                    ->byId(2515974)
                ->fetch();

        $result = current($SmsDev->getResult());
        
        $this->assertSame(2515974, (int)$result['id_sms_read']);
    }

    public function testFilterById_EmptyResponse()
    {
        $apiResponse = '[{"situacao":"OK","descricao":"SEM MENSAGENS NA CAIXA DE ENTRADA."}]';

        $SmsDev = $this->getServiceMock($apiResponse);

        $SmsDev->setFilter()
                    ->byId(2515974)
                ->fetch();
        
        $this->assertEmpty($SmsDev->parsedMessages());
    }

    public function testFilterByDate()
    {
        $apiResponse = '[{"situacao":"OK","data_read":"19\/06\/2018 11:35:14","telefone":"5511988887777","id":"","refer_id":"","nome":"","msg_sent":"","id_sms_read":"2515974","descricao":"Resposta 1"}]';

        $SmsDev = $this->getServiceMock($apiResponse);

        $SmsDev->setDateFormat('U')
            ->setFilter()
                ->dateFrom(1516330800)
                ->dateTo(1559444399)
            ->fetch();

        $parsedMessages = current($SmsDev->parsedMessages());
        
        $this->assertSame('1529418914',    $parsedMessages['date']);
        $this->assertSame('5511988887777', $parsedMessages['number']);
        $this->assertSame('Resposta 1',    $parsedMessages['message']);
    }

    public function testWrongApiKey()
    {
        $apiResponse = '{"situacao":"ERRO","codigo":"403","descricao":"NAO AUTENTICADO."}';

        $SmsDev = $this->getServiceMock($apiResponse);

        $this->assertSame(false, $SmsDev->send('1188881000', 'Message'));

        $this->assertSame(false, $SmsDev->fetch());
    }
}