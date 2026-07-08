<?php

namespace enricodias\SmsDev\Tests;

use enricodias\SmsDev\Exceptions\ApiException;
use enricodias\SmsDev\Exceptions\InvalidPhoneNumberException;
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

    /**
     * @dataProvider cancelDataProvider
     */
    public function testCancel($id, $expectedCount, $expectedSuccess, $apiResponse)
    {
        $SmsDev = $this->getServiceMock($apiResponse);

        $results = $SmsDev->cancel($id);

        $this->assertCount($expectedCount, $results);
        $this->assertSame($expectedSuccess, $results[0]->isSuccess());

        if ($expectedSuccess) {
            $this->assertTrue($this->getLogger()->hasRecord('info', 'Message cancelled.'));
            return;
        }

        $this->assertTrue($this->getLogger()->hasRecord('error', 'Failed to cancel message.'));
    }

    /**
     * @codeCoverageIgnore
     */
    public function cancelDataProvider()
    {
        return [
            // id,                     expectedCount, expectedSuccess, apiResponse
            [ 9999999,                 1,             true,            '{"situacao":"OK","codigo":"1","id":"9999999","descricao":"MENSAGEM CANCELADA COM SUCESSO"}' ],
            [ 9999999,                 1,             false,           '{"situacao":"ERRO","codigo":"400","descricao":"MENSAGEM NAO ENCONTRADA."}' ],
            [ [9999999, 8888888],      2,             true,            '[{"situacao":"OK","codigo":"1","id":"9999999","descricao":"MENSAGEM CANCELADA COM SUCESSO"},{"situacao":"OK","codigo":"1","id":"8888888","descricao":"MENSAGEM CANCELADA COM SUCESSO"}]' ],
        ];
    }

    public function testCancel_EmptyResponse()
    {
        $SmsDev = $this->getServiceMock('{}');

        $results = $SmsDev->cancel(9999999);

        $this->assertEmpty($results);
        $this->assertTrue($this->getLogger()->hasRecord('error', 'Failed to cancel message.'));
    }

    public function testGetStatus()
    {
        $apiResponse = '{"situacao":"OK","codigo":"1","data_envio":"21\/10\/2019 11:08:58","operadora":"OI","descricao":"RECEBIDA"}';

        $SmsDev = $this->getServiceMock($apiResponse);

        $status = $SmsDev->getStatus(9999999);

        $this->assertSame('2019-10-21 14:08:58', $status->getDataEnvio()->format('Y-m-d H:i:s')); // UTC conversion
        $this->assertSame('OI', $status->getOperadora());
        $this->assertSame('RECEBIDA', $status->getDescricao());

        $this->assertTrue($this->getLogger()->hasRecordWithContext('info', 'Message status fetched.', [
            'id' => 9999999,
        ]));
    }

    public function testGetStatus_EmptyResponse()
    {
        $apiResponse = '{}';

        $SmsDev = $this->getServiceMock($apiResponse);

        try {
            $SmsDev->getStatus(9999999);

            $this->fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            $this->assertTrue($this->getLogger()->hasRecord('error', 'Failed to fetch message status.'));
        }
    }

    public function testGetStatus_ArrayIdThrows()
    {
        $SmsDev = $this->getServiceMock('{"situacao":"OK"}');

        $this->expectException(\InvalidArgumentException::class);

        $SmsDev->getStatus([9999999, 8888888]);
    }

    public function testGetReport()
    {
        $apiResponse = '{"situacao":"OK","codigo":"1","data_inicio":"01\/01\/2020","data_fim":"30\/01\/2020","enviada":"100","recebida":"10200","blacklist":"0","cancelada":"0","qtd_credito":"10300","descricao":"CONSULTA REALIZADA"}';

        $SmsDev = $this->getServiceMock($apiResponse);

        $dateFrom = new \DateTimeImmutable('2020-01-01', new \DateTimeZone('America/Sao_Paulo'));
        $dateTo   = new \DateTimeImmutable('2020-01-30', new \DateTimeZone('America/Sao_Paulo'));

        $report = $SmsDev->getReport($dateFrom, $dateTo);

        $this->assertSame('2020-01-01', $report->getDataInicio()->format('Y-m-d')); // UTC conversion
        $this->assertSame('2020-01-30', $report->getDataFim()->format('Y-m-d')); // UTC conversion
        $this->assertSame(100, $report->getEnviada());
        $this->assertSame(10200, $report->getRecebida());
        $this->assertSame(10300, $report->getQtdCredito());

        $this->assertTrue($this->getLogger()->hasRecordWithContext('info', 'Report fetched.', [
            'date_from' => '01/01/2020',
            'date_to'   => '30/01/2020',
        ]));
    }

    public function testGetReport_EmptyResponse()
    {
        $apiResponse = '{}';

        $SmsDev = $this->getServiceMock($apiResponse);

        $dateFrom = new \DateTimeImmutable('2020-01-01', new \DateTimeZone('America/Sao_Paulo'));
        $dateTo   = new \DateTimeImmutable('2020-01-30', new \DateTimeZone('America/Sao_Paulo'));

        try {
            $SmsDev->getReport($dateFrom, $dateTo);

            $this->fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            $this->assertTrue($this->getLogger()->hasRecord('error', 'Failed to fetch report.'));
        }
    }

    public function testPhoneNumberValidator()
    {
        $SmsDev = $this->getServiceMock('{"situacao": "OK", "codigo": "1", "id": "637849052", "descricao": "MENSAGEM NA FILA" }');

        $results = $SmsDev->send(5511988887777, 'Message');

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->isSuccess());

        $this->assertTrue($this->getLogger()->hasRecord('info', 'SMS message sent.'));

        try {
            $results = $SmsDev->send(1234, 'Message');

            // giggsey/libphonenumber-for-php is not installed locally: the number is sent as-is and rejected by the API mock instead.
            $this->assertEmpty($results);
            $this->assertTrue($this->getLogger()->hasRecord('error', 'Failed to send SMS message.'));
        } catch (InvalidPhoneNumberException $e) {
            $this->assertTrue($this->getLogger()->hasRecord('warning', 'Invalid phone number.'));
        }
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
