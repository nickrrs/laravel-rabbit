<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQController extends Controller
{
    public function sendMessage() {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');

        $message = new AMQPMessage('Hello World Queue');

        $channel = $connection->channel();
        $channel->basic_publish($message, 'pdf_events', 'create_pdf');
        $channel->basic_publish($message, 'pdf_events', 'pdf_log');

        $channel->close();
        $connection->close();

        Log::info('Messages sent');
        echo "Message published to RabbitMQ \n";
   }

    public function consumeMessage() {
        try {
            $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');

            $channel = $connection->channel();

            $channel->basic_consume('create_pdf_queue', 'create_pdf_queue', false, true, false, false, function ($msg) {
                Log::info('[x] Received: ' . $msg->body);
            });

            while ($channel->is_consuming()) {
                $channel->wait();
            }

            $channel->close();
            $connection->close();

            echo "Check your logs";
        } catch (Exception $exception) {
            echo $exception->getMessage();
            Log::info($exception->getMessage());
        }
    }
}
