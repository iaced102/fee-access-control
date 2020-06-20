<?php
namespace Library;
use RdKafka;
class MessageBus{
    const BOOTSTRAP_BROKER = 'k1.symper.vn:9092';
    /**
     * Dev create: Dinhnv
     * CreateTime: 18/06/2020 
     * publish event vào Message Bus hàng loạt
     * @param $topic : topic cần push vào message bus 
     * @param $event : event của resource
     * @param $resources : List các resource
     * @return void
     */
    public static function publishBulk($topic,$event,$resources){
        $conf = self::getKafkaConfig();
        $producer = new RdKafka\Producer($conf);
        $topic = $producer->newTopic($topic);
        foreach($resources as $resource){
            $payload = [
                'event' => $event,
                'data' => $resource,
                'time' => microtime(true)
            ];
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($payload));
        }
        $producer->flush(10000);
    }
    /**
     * Dev create: Dinhnv
     * CreateTime: 18/06/2020 
     * Push 1 event riêng lẻ vào Message bus
    * @param $topic : topic cần push vào message bus 
     * @param $event : event của resource
     * @param $resources :  resource cần pulish
     * @return void
     */
    public static function publish($topic,$event,$reource){
        self::publishBulk($topic,$event,[$reource]);
    }
    /**
     * Dev create: Dinhnv
     * CreateTime: 18/06/2020 
     * Hàm subscribe topic và call về hàm callback
     * @param $topic : topic cần subscribe từ message bus 
     * @param $callback : hàm callback để xử lý data lấy được
     * @param $consumerId : consumerId: String | false -nếu là không xác định và sẽ lấy từ đầu đến hiện tại (không thể resume), ngược lại, nếu set consumerid thì có thể resume khi bắt đầu lại
     * @return void
     */
    public static function subscribe($topic,$consumerId,$callback){
        $conf = self::getKafkaConfig();
        $offsetType = RD_KAFKA_OFFSET_BEGINNING;
        $topicConf = new RdKafka\TopicConf();
        if($consumerId!=false){
            $conf->set('group.id', $consumerId);
            $offsetType = RD_KAFKA_OFFSET_STORED;
            
            $topicConf->set('auto.commit.interval.ms', 100);
            $topicConf->set('offset.store.method', 'broker');
            $topicConf->set('auto.offset.reset', 'smallest');;
        }

        $rk = new RdKafka\Consumer($conf);
        $topicObject = $rk->newTopic($topic,$topicConf);
        
        $topicObject->consumeStart(0, $offsetType);
        while (true) {
            $msg = $topicObject->consume(0,120000);
            if (null === $msg || $msg->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) {
                continue;
            } elseif ($msg->err) {
                break;
            } else {
                $payload = json_decode($msg->payload,true);
                if(is_callable($callback)){
                    $callback($msg->topic_name,$payload);
                }
            }
            exit;
        }
    }
    /**
     * Dev create: Dinhnv
     * CreateTime: 20/06/2020 
     * Hàm subscribe đồng thời nhiều topic và call về hàm callback
     * @param $topics : List các topic cần subscribe từ message bus. Kiểu Array [String]
     * @param $callback : hàm callback để xử lý data lấy được
     * @param $consumerId : consumerId: String | false -nếu là không xác định và sẽ lấy từ đầu đến hiện tại (không thể resume), ngược lại, nếu set consumerid thì có thể resume khi bắt đầu lại
     * @return void
     */
    public static function subscribeMultiTopic($topics,$consumerId,$callback){
        $conf = self::getKafkaConfig();
        $offsetType = RD_KAFKA_OFFSET_BEGINNING;
        $topicConf = new RdKafka\TopicConf();
        if($consumerId!=false){
            $conf->set('group.id', $consumerId);
            $offsetType = RD_KAFKA_OFFSET_STORED;
            
            $topicConf->set('auto.commit.interval.ms', 100);
            $topicConf->set('offset.store.method', 'broker');
            $topicConf->set('auto.offset.reset', 'smallest');;
        }

        $rk = new RdKafka\Consumer($conf);
        //
        $queue = $rk->newQueue();
        foreach($topics as $topic){
            $topicObject = $rk->newTopic($topic,$topicConf);
            $topicObject->consumeQueueStart(0, $offsetType,$queue);
        }
        while (true) {
            $msg = $queue->consume(120000);
            if (null === $msg || $msg->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) {
                continue;
            } elseif ($msg->err) {
                break;
            } else {
                $payload = json_decode($msg->payload,true);
                
                if(is_callable($callback)){
                    $callback($msg->topic_name,$payload);
                }
            }
        }
    }
    
    /**
     * Dev create: Dinhnv
     * CreateTime: 18/06/2020 
     * Hàm get config Kafka
     * @return RdKafka\Conf
     */
    private static function getKafkaConfig(){
        $conf = new RdKafka\Conf();
        $conf->set('metadata.broker.list', self::BOOTSTRAP_BROKER);
        return $conf;
    }
}