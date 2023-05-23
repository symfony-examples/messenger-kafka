# Symfony & Kafka
[![SF Messenger Kafka CI](https://github.com/symfony-examples/messenger-kafka/actions/workflows/ci.yaml/badge.svg?branch=main)](https://github.com/symfony-examples/messenger-kafka/actions/workflows/ci.yaml?query=branch%3Amain)
[![SF Messenger Kafka Security](https://github.com/symfony-examples/messenger-kafka/actions/workflows/security.yaml/badge.svg?branch=main)](https://github.com/symfony-examples/messenger-kafka/actions/workflows/security.yaml)
[![SF Messenger Kafka Packages retention policy](https://github.com/symfony-examples/messenger-kafka/actions/workflows/packages-retention-policy.yaml/badge.svg?branch=main)](https://github.com/symfony-examples/messenger-kafka/actions/workflows/packages-retention-policy.yaml)
## About
This is a simple implementation messenger with kafka transport.
In this example we have configured messenger component to :
* Publish message in a kafka topic
* Consume messages from a kafka topics

By default, kafka transport is not implemented by messenger that why we created custom transport in kafka directory.

## Requirements
* git
* docker
* docker-compose
* make

## How to
### Clone the project
```bash
git clone https://github.com/symfony-examples/doctrine-mongodb.git
```
### Installation
This command will create all services and create a kafka topic
```bash
make install-local
```
Enjoy ! 🥳

### Check if all is done
#### Producer
```shell
make console app:messenger:producer reference 2000
```
`reference` and `2000` are required argument, you can replace it by other values<br>
This command will send a message to kafka topic<br>
The topic is defined in `config/packages/messenger.yaml` `producer_topic`
```yaml
framework:
    messenger:
        transports:
            order_transport:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    ...
                    producer_topic: 'order_topic_test'
```

#### Consumer
```shell
make console messenger:consume order_transport
```
`order_transport` is the transport name defined in `config/packages/messenger.yaml`<br>
This command will consume message from the kafka topic define in `config/packages/messenger.yaml` `consumer_topics`<br>
```yaml
framework:
    messenger:
        transports:
            order_transport:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    ...
                    consumer_topics:
                        - 'order_topic_test'
```
Messages should be handled by `App\Messenger\Handler\OrderPaidHandler`<br>
In this example we send an InvoiceCreatedMessage for each order paid, you can update implementation and put your custom logic here.
```php
// App\Messenger\Handler\OrderPaidHandler
public function __invoke(OrderPaidMessage $message): void
{
    // implement logic here
}
```

## Setup in your symfony project
### PHP extension
`rdkafka` extension should be installed.<br>
```ini
; kafka.ini
extension=rdkafka.so
```
```dockerfile
## SETUP RDKAFKA EXTESIONS
RUN set -xe \
    && apk add --no-cache --update --virtual .phpize-deps $PHPIZE_DEPS \
    librdkafka-dev \
    && pecl install rdkafka
COPY ./.docker/php/kafka.ini $PHP_INI_DIR/conf.d/
```

Check if the extension is installed
```shell
php --ri rdkafka
```
### Config env
Add env variables in .env file:
```dotenv
# transport dsn must start with kafka://
MESSENGER_TRANSPORT_DSN=kafka://
# kafka broker list separate with comma (exp: kafka-1:9092,kafka-2:9092)
KAFKA_BROKERS=kafka:9092
```
### Config messenger
Configure your transport
```yaml
framework:
    messenger:
        transports:
            order_transport:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    metadata.broker.list: '%env(KAFKA_BROKERS)%'
                    group.id: 'my-group-id'
                    auto.offset.reset: 'earliest'
                    # you can add here any rdkafka option you need
                    # https://github.com/confluentinc/librdkafka/blob/master/CONFIGURATION.md
                    ...
                    consumer_topics:
                        - 'order_topic_test'
```
Setup transport
```shell
make console messenger:setup-transport
```
Enjoy ! 🥳

## References
https://github.com/arnaud-lb/php-rdkafka <br>
https://arnaud.le-blanc.net/php-rdkafka-doc/phpdoc/rdkafka.setup.html
https://github.com/confluentinc/librdkafka/blob/master/CONFIGURATION.md

