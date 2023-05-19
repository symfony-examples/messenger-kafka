<?php

namespace App\Kafka\Transport;

use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class KafkaTransportFactory implements TransportFactoryInterface
{

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        unset($options['transport_name']);
        return new KafkaTransport(Connection::builder($dsn, $options), $serializer);
    }

    public function supports(string $dsn, array $options): bool
    {
        // todo: create ENUM
        return str_starts_with($dsn, 'kafka://') ||
            str_starts_with($dsn, 'kafka+PLAINTEXT://') ||
            str_starts_with($dsn, 'kafka+SSL://') ||
            str_starts_with($dsn, 'kafka+SASL_PLAINTEXT://') ||
            str_starts_with($dsn, 'kafka+SASL_SSL://')
        ;
    }
}
