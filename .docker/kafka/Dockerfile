FROM alpine:3.18

## INSTALL REQUIREMENTS
RUN apk add --no-cache bash openjdk11-jre
ENV JAVA_HOME "/usr"

## INSTALL KAFKA
RUN wget https://dlcdn.apache.org/kafka/3.4.0/kafka_2.13-3.4.0.tgz
RUN tar -zxf kafka_2.13-3.4.0.tgz
RUN cp -R kafka_2.13-3.4.0 /opt/kafka/

## CLEAN
RUN rm -rf kafka_2.13-3.4.0.tgz

## DEFINE THE WORKING DIRECTORY
WORKDIR /opt/kafka

## ADD KAFKA BINARY DIR TO PATH
ENV PATH=/opt/kafka/bin:$PATH

## DELETE WINDOWS CONF
RUN rm -rf /opt/kafka/bin/windows

## COPY KAFKA CONF
RUN mkdir kafka-logs
COPY zookeeper.properties ./config/

## DEFINE VOLUMES
VOLUME ["/opt/kafka/config", "/opt/kafka/kafka-logs"]

## EXPOSE PORTS
EXPOSE 9092

## START KAFKA SERVER
ENTRYPOINT ["kafka-server-start.sh", "/opt/kafka/config/server.properties", \
    "--override", "log.dirs=/opt/kafka/kafka-logs" \
]
