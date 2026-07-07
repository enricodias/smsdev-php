<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev\Exceptions\ApiException;
use enricodias\SmsDev\Filter\Filter;

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

        $balance = $SmsDev->getBalance();

        $this->assertSame(1200, $balance->getSaldoSms());
        $this->assertSame('R$ 12,00', $balance->getFormattedBalance());

        $this->assertTrue($this->getLogger()->hasRecordWithContext('info', 'Balance fetched.', [
            'balance' => 1200,
        ]));
    }

    public function testGetBalance_EmptyResponse()
    {
        $apiResponse = '{}';

        $SmsDev = $this->getServiceMock($apiResponse);

        try {
            $SmsDev->getBalance();

            $this->fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            $this->assertTrue($this->getLogger()->hasRecord('error', 'Failed to fetch balance.'));
        }
    }

    /**
     * @dataProvider sendDataProvider
     */
    public function testSend($number, $message, $refer, $expectedSuccess, $apiResponse)
    {
        $SmsDev = $this->getServiceMock($apiResponse);

        $SmsDev->setNumberValidation(false);

        $results = $SmsDev->send($number, $message, $refer);

        $this->assertCount(1, $results);
        $this->assertSame($expectedSuccess, $results[0]->isSuccess());

        if ($expectedSuccess) {
            $this->assertTrue($this->getLogger()->hasRecord('info', 'SMS message sent.'));
            return;
        }

        $this->assertTrue($this->getLogger()->hasRecord('error', 'Failed to send SMS message.'));
    }

    /**
     * @codeCoverageIgnore
     */
    public function sendDataProvider()
    {
        return [
            // number,         message,     refer       expectedSuccess,   apiResponse
            [ '1188881000',   'Message',    null,       true,               '{"situacao": "OK", "codigo": "1", "id": "637849052", "descricao": "MENSAGEM NA FILA" }' ],
            [ '1188881000',   '',           null,       false,              '{"situacao":"ERRO","codigo":"400","descricao":"MENSAGEM NAO DEFINIDA."}' ],
            [ '118888100',    'Message',    null,       true,               '{"situacao":"OK","codigo":"1","id":"645106333","descricao":"MENSAGEM NA FILA"}' ],
            [ '11888810009',  'Message',    null,       true,               '{"situacao":"OK","codigo":"1","id":"645106334","descricao":"MENSAGEM NA FILA"}' ],
            [ 'abc',          'Message',    null,       false,              '{"situacao":"ERRO","codigo":"402","descricao":"SEM NUMERO DESTINATARIO."}' ],
            [ '',             'Message',    null,       false,              '{"situacao":"ERRO","codigo":"402","descricao":"SEM NUMERO DESTINATARIO."}' ],
            [ '1188881000',   'Message',    'Refer',    true,               '{"situacao": "OK", "codigo": "1", "id": "637849052", "refer": "Refer", "descricao": "MENSAGEM NA FILA"}' ],
            [ '1188881000',   '',           'Refer',    false,              '{"situacao":"ERRO","codigo":"400","refer": "Refer","descricao":"MENSAGEM NAO DEFINIDA."}' ],
            [ '118888100',    'Message',    'Refer',    true,               '{"situacao":"OK","codigo":"1","id":"645106333","refer": "Refer","descricao":"MENSAGEM NA FILA"}' ],
            [ '11888810009',  'Message',    'Refer',    true,               '{"situacao":"OK","codigo":"1","id":"645106334","refer": "Refer","descricao":"MENSAGEM NA FILA"}' ],
            [ 'abc',          'Message',    'Refer',    false,              '{"situacao":"ERRO","codigo":"402","refer": "Refer","descricao":"SEM NUMERO DESTINATARIO."}' ],
            [ '',             'Message',    'Refer',    false,              '{"situacao":"ERRO","codigo":"402","refer": "Refer","descricao":"SEM NUMERO DESTINATARIO."}'],
        ];
    }

    public function testFilterByUnread()
    {
        $apiResponse = '[{"situacao":"OK","data_read":"19\/01\/2018 11:35:14","telefone":"5511988887777","id":"","refer_id":"","nome":"","msg_sent":"","id_sms_read":"2515974","descricao":"Resposta"}]';

        $SmsDev = $this->getServiceMock($apiResponse);

        $messages = $SmsDev->setFilter(
            Filter::create()
                ->isUnread()
        )->fetch();

        $message = $messages[0];

        $this->assertSame('2018-01-19 13:35:14', $message->getDataRead()->format('Y-m-d H:i:s')); // UTC conversion
        $this->assertSame('5511988887777', $message->getTelefone());
        $this->assertSame('Resposta', $message->getDescricao());

        $this->assertTrue($this->getLogger()->hasRecordWithContext('info', 'Messages fetched.', [
            'count' => 1,
        ]));
    }

    public function testFilterByUnread_EmptyResponse()
    {
        $SmsDev = $this->getServiceMock('{}');

        $messages = $SmsDev->setFilter(
            Filter::create()
                ->isUnread()
        )->fetch();

        $this->assertEmpty($SmsDev->getResult());
        $this->assertEmpty($messages);
    }

    public function testFilterById()
    {
        $apiResponse = '[{"situacao":"OK","data_read":"19\/01\/2018 11:35:14","telefone":"5511988887777","id":"","refer_id":"","nome":"","msg_sent":"","id_sms_read":"2515974","descricao":"Resposta"}]';

        $SmsDev = $this->getServiceMock($apiResponse);

        $messages = $SmsDev->setFilter(
            Filter::create()
                ->byId(2515974)
        )->fetch();

        $this->assertSame(2515974, (int) $messages[0]->getIdSmsRead());
    }

    public function testFilterById_EmptyResponse()
    {
        $apiResponse = '[{"situacao":"OK","descricao":"SEM MENSAGENS NA CAIXA DE ENTRADA."}]';

        $SmsDev = $this->getServiceMock($apiResponse);

        $messages = $SmsDev->setFilter(
            Filter::create()
                ->byId(2515974)
        )->fetch();

        $this->assertEmpty($messages);
    }

    public function testFilterByDate()
    {
        $apiResponse = '[{"situacao":"OK","data_read":"19\/06\/2018 11:35:14","telefone":"5511988887777","id":"","refer_id":"","nome":"","msg_sent":"","id_sms_read":"2515974","descricao":"Resposta 1"}]';

        $SmsDev = $this->getServiceMock($apiResponse);

        $messages = $SmsDev->setFilter(
            Filter::create()
                ->setDateFormat('U')
                ->dateFrom(1516330800)
                ->dateTo(1559444399)
        )->fetch();

        $message = $messages[0];

        $this->assertSame(1529418914, $message->getDataRead()->getTimestamp());
        $this->assertSame('5511988887777', $message->getTelefone());
        $this->assertSame('Resposta 1', $message->getDescricao());
    }

    public function testEmptyInbox()
    {
        $apiResponse = '[{"situacao":"OK","descricao":"SEM MENSAGENS NA CAIXA DE ENTRADA."}]';

        $SmsDev = $this->getServiceMock($apiResponse);

        $messages = $SmsDev->fetch();

        $this->assertEmpty($messages);
    }

    public function testWrongApiKey()
    {
        $apiResponse = '{"situacao":"ERRO","codigo":"403","descricao":"NAO AUTENTICADO."}';

        $SmsDev = $this->getServiceMock($apiResponse);

        $SmsDev->setNumberValidation(false);

        $results = $SmsDev->send('1188881000', 'Message');

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]->isSuccess());

        $this->assertTrue($this->getLogger()->hasRecord('error', 'Failed to send SMS message.'));
    }
}
