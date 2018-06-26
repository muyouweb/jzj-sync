<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/10/25
 * Time: 上午11:20
 */

namespace BaodSync\Lib;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Amqp extends Base
{
    /**
     * @var AMQPStreamConnection
     */
    public $_connection;
    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    public $_channel;

    /**
     * Amqp constructor
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $host = getenv('AMQP_HOST');
        $port = getenv('AMQP_PORT');
        $user = getenv('AMQP_USER');
        $password = getenv('AMQP_PASSWORD');
        $vhost = getenv('AMQP_VHOST');

        $this->_connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        if (!$this->_connection) {
            throw new \Exception('RabbitMQ连接失败，请检查配置');
        }
        $this->_channel = $this->_connection->channel();
        $this->_channel->confirm_select();
    }

    /**
     * 只能通过静态方法获得该类的对象(单例模式)
     * @return Amqp|null
     */
    public static function getInstance()
    {
        static $obj = null;
        if ($obj == null) {
            $obj = new Amqp();
        }

        return $obj;
    }

    /**
     * 发送消息
     *
     * @param string $eventKey 事件key
     * @param array $message 消息
     * @return array
     */
    public function send($eventKey, $message)
    {
        $routing_key = getenv('AMQP_BROADCAST');

        $_id = $this->generate();
        $messageBody = [
            '_id'      => $_id, // 事件号
            'eventKey' => $eventKey,
            'ip'       => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "0.0.0.0",
            'data'     => $message
        ];
        $message = new AMQPMessage(json_encode($messageBody), [
            'content_type' => 'text/plain',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);
        $exchange = getenv('AMQP_EXCHANGE');
        $this->_channel->basic_publish($message, $exchange, $routing_key);

        return $messageBody;
    }

    /**
     * 生成事件唯一号
     */
    public function generate()
    {
        static $number = null;
        if ($number == null) {
            $number = 0;
        }
        ++$number;
        $serverName = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "0.0.0.0";
        $serverName = str_replace(".", "_", $serverName); // 服务器IP
        $pid = getmypid(); // 进程ID
        return time() . '-' . $serverName . '-' . $pid . '-' . $number;
    }
}

