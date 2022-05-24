<?php
namespace Library;
use RdKafka;
use Library\Request;
class MessageBus{
    const PING_MEM_KEYWORD = 'lastTimePingLuffy';
    const LUFFY_URL        = 'https://luffy.symper.vn/consumers';
    const BOOTSTRAP_BROKER = 'k1.symper.vn:9092';
    const TIMEOUT          = 60; //s
    /**
     * Dev create: Dinhnv
     * CreateTime: 18/06/2020 
     * publish event vào Message Bus hàng loạt
     * @param $topic : topic cần push vào message bus 
     * @param $event : event của resource
     * @param $resources : List các resource
     * @return void
     */
    public static function publishBulk($topicName,$event,$resources){
        $conf = self::getKafkaConfig();
        $conf->set('topic.metadata.refresh.interval.ms', 1);
        $topicName = Environment::getPrefixEnvironment()."$topicName";
        $producer = new RdKafka\Producer($conf);
        $topic = $producer->newTopic($topicName);
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
            $consumerId = Environment::getPrefixEnvironment().$consumerId;
            $conf->set('group.id', $consumerId);
            $offsetType = RD_KAFKA_OFFSET_STORED;
            
            $topicConf->set('auto.commit.interval.ms', 100);
            $topicConf->set('offset.store.method', 'broker');
            $topicConf->set('auto.offset.reset', 'smallest');;
        }

        $rk = new RdKafka\Consumer($conf);
        $topicObject = $rk->newTopic(Environment::getPrefixEnvironment().$topic,$topicConf);
        
        $topicObject->consumeStart(0, $offsetType);
        while (true) {
            $msg = $topicObject->consume(0,self::TIMEOUT*1000);
            if (null === $msg || $msg->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) {
                continue;
            } elseif ($msg->err) {
                break;
            } else {
                $payload = json_decode($msg->payload,true);
                if(is_callable($callback)){
                    $topicName = $msg->topic_name;
                    if(strpos($topicName,Environment::getPrefixEnvironment())===0){
                        $topicName = substr($topicName,strlen(Environment::getPrefixEnvironment()));
                    }
                    $callback($topicName,$payload);
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
    public static function subscribeMultiTopic($topics,$consumerId,$callback,$triggerUrl='',$stopUrl=''){
        $conf = self::getKafkaConfig();
        $offsetType = RD_KAFKA_OFFSET_BEGINNING;
        $topicConf = new RdKafka\TopicConf();
        if($consumerId!=false){
            $consumerId = Environment::getPrefixEnvironment().$consumerId;
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
            $topic = Environment::getPrefixEnvironment().$topic;
            $topicObject = $rk->newTopic($topic,$topicConf);
            $topicObject->consumeQueueStart(0, $offsetType,$queue);
        }
        while (true) {
            self::pingToLuffy($consumerId,$triggerUrl,$stopUrl,$topics);
            $msg = $queue->consume(self::TIMEOUT*1000);
            if (null === $msg || $msg->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) {
                continue;
            } elseif ($msg->err) {
                break;
            } else {
                $payload = json_decode($msg->payload,true);
                
                if(is_callable($callback)){
                    $topicName = $msg->topic_name;
                    if(strpos($topicName,Environment::getPrefixEnvironment())===0){
                        $topicName = substr($topicName,strlen(Environment::getPrefixEnvironment()));
                    }
                    $callback($topicName,$payload);
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
    private static function pingToLuffy($serviceId,$triggerUrl,$stopUrl,$topics){
        if(self::checkTimeoutToPing()){
            if($triggerUrl == false){
                $triggerUrl = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            }
            if(stripos($triggerUrl,'http')!==0){
                $triggerUrl = "https://$_SERVER[HTTP_HOST]$triggerUrl";
            }
            if(stripos($stopUrl,'http')!==0){
                $stopUrl = "https://$_SERVER[HTTP_HOST]$stopUrl";
            }
            $dataPost = [
                'serviceId'=>$serviceId,
                'triggerUrl'=>$triggerUrl,
                'topics'=>json_encode($topics),
                'processId'=>getmypid(),
                'stopUrl'=>$stopUrl
            ];
            Request::request(self::LUFFY_URL,$dataPost,'POST',false);
        }
    }
    private static function checkTimeoutToPing(){
        if(!isset($GLOBALS[self::PING_MEM_KEYWORD])){
            $GLOBALS[self::PING_MEM_KEYWORD] = time();
            return true;
        }
        else{
            if((time()-$GLOBALS[self::PING_MEM_KEYWORD])>=self::TIMEOUT){
                $GLOBALS[self::PING_MEM_KEYWORD] = time();
                return true;
            }
        }
        return false;
    }
    
}