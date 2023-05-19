FROM alpine:3.18

## INSTALL REQUIREMENTS
RUN apk add --no-cache bash procps openjdk11-jre
ENV JAVA_HOME "/usr/bin"

## INSTALL ZOOKEEPER
RUN wget https://dlcdn.apache.org/zookeeper/zookeeper-3.7.1/apache-zookeeper-3.7.1-bin.tar.gz
RUN tar -zxf apache-zookeeper-3.7.1-bin.tar.gz
RUN mv apache-zookeeper-3.7.1-bin /opt/zookeeper/

## CLEAN
RUN rm -rf apache-zookeeper-3.7.1-bin.tar.gz

## EXPOSE PORTS
EXPOSE 2181

## DEFINE THE WORKING DIRECTORY
WORKDIR /opt/zookeeper

## ADD ZOOKEEPER BINARY DIR TO PATH
ENV PATH=/opt/zookeeper/bin:$PATH

## COPY ZOOKEEPER CONF
RUN mkdir data
COPY zoo.cfg ./conf

## COPY BINARY FILE
RUN mv /opt/zookeeper/bin/zkServer.sh /opt/zookeeper/bin/zkServer
RUN chmod +x /opt/zookeeper/bin/zkServer

## DEFINE VOLUMES
VOLUME ["/opt/zookeeper/data", "/opt/zookeeper/conf"]

## START ZOOKEEPER SERVICE
ENTRYPOINT ["zkServer"]
CMD ["start-foreground"]
