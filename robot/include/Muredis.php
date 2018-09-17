<?php 
/**
 *  
 * Redis 是一个高性能的key-value数据库
 *
 */
class muredis{

    private $oRedis = null; //连接对象
    private $aServer = array(); //地址配置
    private $connect = false; //是否连接上
    private $connected = false; //是否已经连接过
    public $seria = false; //是否进行序列化
    
    public function __construct( $aServer){
        $this->aServer = $aServer;
    }
    /**
     * 连接.每个实例仅连接一次
     * @return Boolean
     */
    private function connect(){
        if ( ! $this->connected){
        	$this->connected = true; //标志已经连接过一次
            try {
                $this->oRedis = new Redis();
                $this->connect = $this->oRedis->connect($this->aServer[0], $this->aServer[1]); //TRUE on success, FALSE on error.
                $this->seria && $this->oRedis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
            }catch (Exception $e){ //连接失败,记录
            	$this->connected = false; 
                $this->errorlog('Connect', $e->getCode(), $e->getMessage());
            }
        }
        return $this->connect;
    }
    /**
     * 设置
     * @param String $key
     * @param Mixed $value
     * @return Boolean
     */
    public function set($key, $value, $Timeout=0){
        for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() && $this->oRedis->set( $key, $value, (int)$Timeout);
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('set', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
        return $res;
    }
    /**
     * 设置带过期时间的值(暂不支持)
     * @param String $key
     * @param Mixed $value
     * @param int $expire 过期时间.默认24小时
     * @return Boolean
     */
    public function setex($key, $value, $expire=86400, $zip=false){
        if( ! $this->connect()){
            return false;
        }
        
        $value = $zip ? @gzcompress( serialize( $value)) : serialize( $value);
        return $this->oRedis->setex( $key, $expire, $value); //Bool TRUE if the command is successful.
    }
    /**
     * 添加.存在该Key则返回false.
     * @param String $key
     * @param Mixed $value
     * @return Boolean
     */
    public function setnx($key, $value, $zip=false){
        if( ! $this->connect()){
            return false;
        }
        
        $value = $zip ? @gzcompress( serialize( $value)) : serialize( $value);
        return $this->oRedis->setnx( $key, $value);
    }
    /**
     * 添加.存在该Key则返回false.该方法可以看成是setnx的别名
     * @param String $key
     * @param Mixed $value
     * @return Boolean
     */
    public function add($key, $value, $zip=false, $timeout=0){
        if( ! $this->connect()){
            return false;
        }
        
        $value = $zip ? @gzcompress( serialize( $value)) : serialize( $value);
        $flag = $this->oRedis->add( $key, $value);
        if($flag && $timeout){
        	$this->oRedis->setTimeout($key,$timeout);
        }
        return $flag;
    }
    /**
     * 原子递加.不存在该key则基数为0,注意$value为 max(1, $value)
     * @param String $key
     * @param int $value
     * @return false/int 返回最新的值
     */
    public function incr($key, $value=1){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->incr( $key, $value);
    }
	
	/**
	 * 原子递加指定的整数.不存在该key则基数为0,注意$value可以为负数.返回的结果也可能是负数
	 * !!!如果超过42亿,请用incrByFloat
	 * @param String $key
	 * @param int $value 可以为0
	 * @return false/int 返回最新的值
	 */
	public function incrBy($key, $value){
		 if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->incrBy( $key, (int)$value);
	}
    /**
     * 原子递减.不存在该key则基数为0,注意$value为 max(1, $value).可以减成负数
     * @param String $key
     * @param int $value
     * @return false/int 返回最新的值
     */
    public function decr($key, $value=1){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->decr( $key, $value);
    }
    /**
     * 获取
     * @param String $key
     * @param Boolean $zip 存入时是否采取了压缩
     * @param Boolean $serial 存入时是否序列化了.如果存入时采取了压缩则一定序列化了.如incr和decr存入的就不需要serial
     * @return false/Mixed
     */
    public function get( $key, $zip=false, $serial=true){
      	 for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() ? $this->oRedis->get( $key) : false;
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('get', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
		return $res;
    }
    /**
     * 先获取该key的值,然后以新值替换掉该key
     * @param String $key
     * @param Mixed $value
     * @param Boolean $zip
     * @param Boolean $serial
     * @return Mixed/false
     */
    public function getSet($key, $value, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $value = $zip ? @gzcompress( serialize( $value)) : serialize( $value);
        $result = $this->oRedis->getSet($key, $value);
        return $result===false ? $result : ($zip ? unserialize( @gzuncompress( $result)) : ($serial ? unserialize( $result) : $result));
    }
    /**
     * 从存储器中随机获取一个key
     * @return String
     */
    public function randomKey(){
        if( ! $this->connect()){
            return '';
        }
        return $this->oRedis->randomKey();
    }
    /**
     * 选择数据库
     * @param int $dbindex
     * @return Boolean
     */
    public function select( $dbindex){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->select( $dbindex);
    }
    /**
     * 把某个key转移到另一个db中
     * @param String $key
     * @param int $dbindex
     * @return Boolean 当前db中没有该key或者...
     */
    public function move($key, $dbindex){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->move($key, $dbindex);
    }
    /**
     * 重命名某个Key.注意如果目的key存在将会被覆盖
     * @param String $srcKey
     * @param String $dstKey
     * @return Boolean 源key和目的key相同或者...
     */
    public function renameKey($srcKey, $dstKey){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->renameKey($srcKey, $dstKey);
    }
    /**
     * 重命名某个Key.和renameKey不同: 如果目的key存在将不执行
     * @param String $srcKey
     * @param String $dstKey
     * @return Boolean 源key和目的key相同或者...
     */
    public function renameNx($srcKey, $dstKey){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->renameNx($srcKey, $dstKey);
    }
    /**
     * 设置某个key过期时间.只能设置一次
     * @param String $key
     * @param int $expire 过期秒数
     * @return Boolean
     */
    public function setTimeout($key, $expire){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->setTimeout($key, $expire);
    }
    /**
     * 设置某个key在特定的时间过期
     * @param String $key
     * @param int $timestamp 时间戳
     * @return Boolean
     */
    public function expireAt($key, $timestamp){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->expireAt($key, $timestamp);
    }
    /**
     * 批量获取
     * @param Array $keys
     * @param Boolean $zip 存入时是否采取了压缩
     * @param Boolean $serial 存入时是否序列化了
     * @return Array
     */
    public function getMultiple($keys, $zip=false, $serial=true){
        if( (! is_array( $keys)) || (! count( $keys)) || (! $this->connect())){
            return array();
        }
        $keys = array_values( $keys); //确保数字索引
        $result = $this->oRedis->getMultiple( $keys);
        $result = is_array( $result) ? $result : array();
        foreach ($result as $k => $v){
            if( $v !== 'nil' ){ //服务器返回
                $aList[$keys[$k]] = $zip ? unserialize( @gzuncompress( $v)) : ($serial ? unserialize( $v) : $v);
            }
        }
        return (array)$aList;
    }
    /**
     * List章节 无索引序列 把元素加入到队列左边(头部).如果不存在则创建一个队列
     * @param String $key
     * @param Mixed $value
     * @return Boolean. 如果连接不上或者该key已经存在且不是一个队列
     */
    public function lPush($key, $value){
       for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() ? $this->oRedis->lPush($key, $value) : false;
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('lPush', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
        return $res;
    }
    /**
     * 把元素加入到队列右边(尾部)
     * @param String $key
     * @param Mixed $value
     * @return Boolean
     */
    public function rPush($key, $value, $zip=false, $serial=true, $timeout=0){
        if( ! $this->connect()){
            return false;
        }
        $value = $serial ? serialize( $value) : $value;
        $value = $zip ? @gzcompress( $value) : $value;
        $flag = $this->oRedis->rPush($key, $value);
        if($flag && $timeout){
        	$this->oRedis->setTimeout($key,$timeout);
        }
        return $flag;
    }
    
    /**
     * 弹出队列头部元素
     * @param String $key
     * @return Mixed/false
     */
    public function lPop($key, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $value = $this->oRedis->lPop( $key);
        $value = $zip ? @gzuncompress( $value) : $value;
        return $serial ? @unserialize( $value) : $value;
    }
    /**
     * 弹出队列尾部元素
     * @param String $key
     * @return Mixed/false
     */
    public function rPop($key, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $value = $this->oRedis->rPop( $key);
        $value = $zip ? @gzuncompress( $value) : $value;
        return $serial ? @unserialize( $value) : $value;
    }
    /**
     * 返回队列里的元素个数.不存在则为0.不是队列则为false
     * @param String $key
     * @return int/false
     */
    public function lSize( $key){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->lSize( $key);
    }
    
    /**
	 * 控制队列只保存某部分listTrim,即:删除队列的其余部分
	 * @param String $key
	 * @param int $start
	 * @param int $end
	 * @return Boolean 不是一个队列或者不存在...
	 */
	public function lTrim($key, $start, $end){
		for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() && $this->oRedis->lTrim($key, $start, $end);
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('lTrim', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
		return $res;
	}
    /**
     * 控制队列只保存某部分,即:删除队列的其余部分
     * @param String $key
     * @param int $start
     * @param int $end
     * @return Boolean
     */
    public function listTrim($key, $start, $end){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->listTrim($key, $start, $end);
    }
    /**
     * 获取队列的某个元素
     * @param String $key
     * @param int $index 0第一个1第二个...-1最后一个-2倒数第二个
     * @return Mixed/false 没有则为空字符串或者false
     */
    public function lGet($key, $index, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $value = $this->oRedis->lGet($key, $index);
        $value = $zip ? @gzuncompress( $value) : $value;
        return $serial ? @unserialize( $value) : $value;
    }
    /**
     * 修改队列中指定$index的元素
     * @param String $key
     * @param int $index
     * @param Mixed $value
     * @param Boolean $zip
     * @param Boolean $serial
     * @return Boolean 该$index不存在为false
     */
    public function lSet($key, $index, $value, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $value = $serial ? serialize( $value) : $value;
        $value = $zip ? @gzcompress( $value) : $value;
        return $this->oRedis->lSet($key, $index, $value);
    }
    /**
     * 取出队列的某一段
     * @param String $key
     * @param String $start 相当于$index:第一个为0...最后一个为-1
     * @param String $end
     * @return Array
     */
    public function lGetRange($key, $start, $end){
       for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() ? $this->oRedis->lGetRange($key, $start, $end) : array();
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('lGetRange', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
        return (array)$res;
    }
    /**
     * 删掉队列中的某些值
     * @param String $key
     * @param Mixed $value 要删除的值
     * @param int $count 去掉的个数,>0从左到右去除;0为去掉所有;<0从右到左去除
     * @return Boolean
     */
    public function lRemove($key, $value, $count=0, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $value = $serial ? serialize( $value) : $value;
        $value = $zip ? @gzcompress( $value) : $value;
        return $this->oRedis->lRemove($key, $value, $count);
    }
    /**
     * 给该key添加一个唯一值.相当于制作一个没有重复值的数组
     * @param String $key
     * @param Mixed $value
     * @return Boolean
     */
     public function sAdd($key, $value){
         for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() ? $this->oRedis->sAdd($key, $value) : false;
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('sAdd', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
		return $res;
     }
     
      /**
     * 获取某数组所有值sGetMembers
     * @param String $key
	 * @return Array 顺序是不固定的
	 */
	public function sMembers( $key){
		for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect('sMembers', $key) && is_array($result = $this->oRedis->sMembers( $key)) ? $result : array();
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('sMembers', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
		return $res;
	}
    /**
     * 获取某key对象个数
     * @param String $key 
     * @return int 不存在则为0
     */
    public function sSize( $key){
        if( ! $this->connect()){
            return 0;
        }
        return $this->oRedis->sSize( $key);
    }
    /**
     * 随机弹出一个值.
     * @param String $key
     * @param Boolean $zip
     * @param Boolean $serial
     * @return Mixed/false
     */
    public function sPop($key, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $result = $this->oRedis->sPop( $key);
        return $result===false ? $result : ($zip ? unserialize( @gzuncompress( $result)) : ($serial ? unserialize( $result) : $result));
    }
    /**
     * 随机取出一个值.与sPop不同,它不删除值(暂不支持)
     * @param String $key
     * @param Boolean $zip
     * @param Boolean $serial
     * @return Mixed/false
     */
    public function sRandMember($key, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $result = $this->oRedis->sRandMember( $key);
        return $result===false ? $result : ($zip ? unserialize( @gzuncompress( $result)) : ($serial ? unserialize( $result) : $result));
    }
    /**
     * 返回所给key列表都有的那些值,相当于求交集
     * $keys Array 
     * @return Array
     */
    public function sInter($keys, $zip=false, $serial=true){
        if( ! $this->connect()){
            return array();
        }
        $result = call_user_func_array(array($this->oRedis, 'sInter'), $keys);
        $result = is_array( $result) ? $result : array();
        foreach ($result as $k => $v){
            $aList[] = $zip ? unserialize( @gzuncompress( $v)) : ($serial ? unserialize( $v) : $v);
        }
        return (array)$aList;
    }
    /**
     * 把所给$keys列表都有的那些值存到$key指定的数组中.相当于执行sInter操作然后再存到另一个数组中
     * $key String 要存到的数组key 注意该数组如果存在会被覆盖
     * $keys Array 
     * @return int
     */
    public function sInterStore($key, $keys){
        if( ! $this->connect()){
            return 0;
        }
        return call_user_func_array(array($this->oRedis,'sInterStore'), array_merge(array($key), $keys));
    }
    /**
     * 返回所给key列表所有的值,相当于求并集
     * @param Array $keys
     * @param Boolean $zip
     * @param Boolean $serial
     * @return Array
     */
    public function sUnion($keys, $zip=false, $serial=true){
        if( ! $this->connect()){
            return array();
        }
        $result = call_user_func_array(array($this->oRedis,'sUnion'), $keys);
        $result = is_array( $result) ? $result : array();
        foreach ($result as $k => $v){
            $aList[] = $zip ? unserialize( @gzuncompress( $v)) : ($serial ? unserialize( $v) : $v);
        }
        return (array)$aList;
    }
    /**
     * 把所给key列表所有的值存储到另一个数组
     * @param String $key
     * @param Array $keys
     * @return int/false 并集的数量
     */
    public function sUnionStore($key, $keys){
        if( ! $this->connect()){
            return 0;
        }
        return call_user_func_array(array($this->oRedis,'sUnionStore'), array_merge(array($key), $keys));
    }
    /**
     * 返回所给key列表想减后的集合,相当于求差集
     * @param Array $keys 注意顺序,前面的减后面的
     * @param Boolean $zip
     * @param Boolean $serial
     * @return Array
     */
    public function sDiff($keys, $zip=false, $serial=true){
        if( ! $this->connect()){
            return array();
        }
        $result = call_user_func_array(array($this->oRedis,'sDiff'), $keys);
        $result = is_array( $result) ? $result : array();
        foreach ($result as $k => $v){
            $aList[] = $zip ? unserialize( @gzuncompress( $v)) : ($serial ? unserialize( $v) : $v);
        }
        return (array)$aList;
    }
    /**
     * 把所给key列表差集存储到另一个数组
     * @param String $key 要存储的目的数组
     * @param Array $keys
     * @return int/false 差集的数量
     */
    public function sDiffStore($key, $keys){
        if( ! $this->connect()){
            return 0;
        }
        return call_user_func_array(array($this->oRedis,'sDiffStore'), array_merge(array($key), $keys));
    }
    /**
     * 删除该数组中对应的值 
     * @param String $key
     * @param String $value
     * @return Boolean
     */
    public function sRemove($key, $value, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $value = $serial ? serialize( $value) : $value;
        $value = $zip ? @gzcompress( $value) : $value;
        return $this->oRedis->sRemove($key, $value);
    }
    /**
     * 把某个值从一个key转移到另一个key
     * @param String $srcKey
     * @param String $dstKey
     * @param Mixed $value
     * @return Boolean 源key不存在/目的key不存在/源值不存在/目的值存在->false
     */
    public function sMove($srcKey, $dstKey, $value, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $value = $serial ? serialize( $value) : $value;
        $value = $zip ? @gzcompress( $value) : $value;
        return $this->oRedis->sMove($srcKey, $dstKey, $value);
    }
    /**
     * 判断该数组中是否有对应的值
     * @param String $key
     * @param String $value
     * @return Boolean
     */
    public function sContains($key, $value, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $value = $serial ? serialize( $value) : $value;
        $value = $zip ? @gzcompress( $value) : $value;
        return $this->oRedis->sContains($key, $value);
    }
    /**
     * 获取某数组所有值
     * @param String $key
     * @return Array 顺序是不固定的
     */
    public function sGetMembers($key, $zip=false, $serial=true){
        if( ! $this->connect()){
            return array();
        }
        $result = $this->oRedis->sGetMembers($key);
        $result = is_array( $result) ? $result : array();
        foreach ($result as $k => $v){
            $aList[] = $zip ? unserialize( @gzuncompress( $v)) : ($serial ? unserialize( $v) : $v);
        }
        return (array)$aList;
    }
    /**
     * 添加一个指定了下标的数组单元(默认的数组下标从0开始)
     * @param String $key
     * @param int $score 数组下标
     * @param Mixed $value
     * @param Boolean $zip
     * @param Boolean $serial
     * @return false/int 成功加入的个数
     */
    public function zAdd($key, $score, $value, $zip=false, $serial=true){
        if( ! $this->connect()){
            return array();
        }
        $value = $serial ? serialize( $value) : $value;
        $value = $zip ? @gzcompress( $value) : $value;
        return $this->oRedis->zAdd($key, $score, $value);
    }
    /**
     * 获取指定单元的数据
     * @param String $key
     * @param int $start
     * @param int $end
     * @param Boolean $withscores 是否返回索引值.如果是则返回值=>索引的数组
     * @return Mixed
     */
    public function zRange($key, $start, $end, $withscores=false){
        if( ! $this->connect()){
            return array();
        }
        return $this->oRedis->zRange($key, $start, $end, $withscores);
    }
    /**
     * 获取指定单元的反序排列的数据
     * @param String $key
     * @param int $start
     * @param int $end
     * @param Boolean $withscores 是否返回索引值.如果是则返回值=>索引的数组
     * @return Mixed
     */
    public function zReverseRange($key, $start, $end, $withscores=false){
        if( ! $this->connect()){
            return array();
        }
        return $this->oRedis->zReverseRange($key, $start, $end, $withscores);
    }
    public function zRangeByScore(){
        
    }
    public function zCount(){
        
    }
    public function zDeleteRangeByScore(){
        
    }
    public function zSize(){
        
    }
    public function zScore($key, $member){
    	if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->zScore( $key, $member);
    }
    public function zRank($key, $member){
        for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() ? $this->oRedis->zRank( $key, $member) : 0;
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('zRank', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
		return $res;
    }
    public function zRevRank(){
        
    }
    public function zIncrBy($key, $value, $member){
    	if( ! $this->connect()){
            return false;
        }
      	return $this->oRedis->zIncrBy( $key, $value, $member);
    }
    public function zUnion(){
        
    }
    public function zInter(){
        
    }
    /**
     * 以下為HASH操作相關
     * @param $hashname
     * @param $key1....
     * @param $value1....
     * 向名稱為hashkey中添加key1->value1 将哈希表key中的域field的值设为value。如果key不存在，一个新的哈希表被创建并进行hset操作。如果域field已经存在于哈希表中，旧值将被覆盖。
     */
    public function hSet($hashname,$key,$value,$timeout=0){
        if( ! $this->connect()){
            return false;
        }
        $flag = $this->oRedis->hSet($hashname, $key, $value);
		if($flag && $timeout){
        	$this->oRedis->setTimeout($hashname,$timeout);
        }
		return $flag;
    }
	
	 /**
     * 添加式
     * @param String $key
     * @param String $hashKey
     * @param Mixed $value
     * @return Boolean
     */
    public function hSetNx($key, $hashKey, $value){
    	for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() && $this->oRedis->hSetNx($key, $hashKey, $value);
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('hSetNx', $e->getCode(), $e->getMessage());
			}
		}
		return $res;
	}
	
    /**
     * 從名稱為haskey中獲得key為$key的值
     * @param $hashname
     * @param $key
     */
    public function hGet($key, $hashKey){
       for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() ? $this->oRedis->hGet($key, $hashKey) : false;
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('hGet', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
		return $res;
    }
    /**
     * 返回名稱為hashname中的元素個數
     * @param $hashname
     */
    public function hLen($hashname){
         if( ! $this->connect()){
        	return false;
        }
        return $this->oRedis->hLen($hashname);
    }
    /**
     * 刪除hash中鍵名為key的域
     *
     * @param unknown_type $hashname
     * @param unknown_type $key
     */
    public function hDel($hashname,$key){
        if( ! $this->connect()){
        	return false;
        }
        return $this->oRedis->hDel($hashname, $key);
    }
    /**
     * 返回hash中所有的鍵名
     * @param $hashname
     */
    public function hKeys($hashname){
        if( ! $this->connect()){
        	return array();
        }
        return $this->oRedis->hKeys($hashname);
    }
    /**
     * 返回hash中所有對應的value
     * @param $hashname
     */
    public function hVals($hashname){
        if( ! $this->connect()){
        	return array();
        }
        return $this->oRedis->hVals($hashname);
    }
	/**
	 * 名稱hashname中是否存在鍵名為key的域
	 * @param $hashname
	 * @param $key
	 */
    public function hExists($hashname,$key){
         if( ! $this->connect()){
        	return false;
        }
        return $this->oRedis->hExists($hashname, $key);
    }
    /**
     * 更改hash中某個鍵名的值
     * @param $hashname
     * @param $key
     * @param $val 操作值
     */
    public function hIncrBy($hashname, $key, $val){
         if( ! $this->connect()){
        	return false;
        }
        return $this->oRedis->hincrby($hashname, $key, $val);
    }
    /**
     * 批量獲取hash中鍵名對應的值
     * @param $hashname
     * @param array('key1','key2')
     */
    public function hMGet($key, $memberKeys){
        for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() && ($result = $this->oRedis->hMget($key, $memberKeys)) ? $result : array();
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('hMget', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
		return $res;
    }
    /**
     * 向hash中批量添加元素
     * @param $hashname
     * @param array(key1=>value1,key2=>value2.........)
     */
    public function hMset($key, $members){
    	for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() && $this->oRedis->hMset($key, $members);
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('hMset', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
		return $res;
    }
	
    /**
	 * 返回名称为h的hash中所有键对应的value
	 * @param String $hashname
	 */
	public function hGetAll($hashname){
		for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() && ($result = $this->oRedis->hGetAll( $hashname)) ? $result : array();
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('hGetAll', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
		return $res;
	}
	
    /**
     * 删除对应的值
     * @param String $key
     * @param Mixed $value
     */
    public function zDelete($key, $value, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->zDelete($key, $value);
    }
    /**
     * 返回服务器信息
     * @return Array
     */
    public function info(){
        if( ! $this->connect()){
            return array();
        }
        return $this->oRedis->info();
    }
    /**
     * 返回某key剩余的时间.单位是秒
     * @param String $key
     * @return int/false -1为没有设置过期时间
     */
    public function ttl( $key){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->ttl( $key);
    }
    /**
     * 批量设置
     * @param Array $pairs 索引数组,索引为key,值为...
     * @param Boolean $zip
     * @param Boolean $serial
     * @return Boolean
     */
    public function mset($pairs, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        
        foreach ((array)$pairs as $k => $v){
            $pairs[$k] = $zip ? @gzcompress( serialize( $v)) : serialize( $v);
        }
        return $this->oRedis->mset( $pairs);
    }
    /**
     * 从源队列尾部弹出一项加到目的队列头部.并且返回该项
     * @param String $srcKey
     * @param String $dstKey
     * @param Boolean $zip
     * @param Boolean $serial
     * @return Mixed/false
     */
    public function rpoplpush($srcKey, $dstKey, $zip=false, $serial=true){
        if( ! $this->connect()){
            return false;
        }
        $result = $this->oRedis->rpoplpush($srcKey, $dstKey);
        return $result===false ? $result : ($zip ? unserialize( @gzuncompress( $result)) : ($serial ? unserialize( $result) : $result));
    }
    /**
     * 判断key是否存在
     * @param String $key
     * @return Boolean
     */
    public function exists( $key){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->exists( $key); //BOOL: If the key exists, return TRUE, otherwise return FALSE.
    }
	
	 /**
     * 设置某个key过期时间(Time To Live)expire. (redis2.1.3前的版本只能设置一次）
     * @param String $key
     * @param int $ttl 存活时长(秒)
     * @return Boolean $key不存在为false
     */
    public function expire($key, $ttl){
    	for ($retry=0; $retry<2; $retry++){ //重试两次
			try {
				$res = $this->connect() ? $this->oRedis->expire($key, $ttl) : false;
				break;
			}catch (RedisException $e){
				$this->close(); //显式关闭,强制重连
				$retry && $this->errorlog('expire', $e->getCode(), $e->getMessage(), false, 'redis_error.txt');
			}
		}
		return $res;
    }
    /**
     * 获取符合匹配的key.仅支持正则中的*通配符.如->getKeys('*')
     * @param String $pattern
     * @return Array/false
     */
    public function getKeys( $pattern){
        if( ! $this->connect()){
            return array();
        }
        return $this->oRedis->getKeys( $pattern);
    }
    /**
     * 删除某key/某些key
     * @param String/Array $keys
     * @return int 被删的个数
     */
    public function delete( $keys){
        if( ! $this->connect()){
            return 0;
        }
        if( empty($keys) ){
            return 0;
        }
        return call_user_func_array(array($this->oRedis, 'delete'), $keys);
    }
    /**
     * 返回当前key数量
     * @return int
     */
    public function dbSize(){
        if( ! $this->connect()){
            return 0;
        }
        return $this->oRedis->dbSize();
    }
    /**
   
    /**
     * 返回某key的数据类型
     * @param String $key
     * @return int 存在于: REDIS_* 中
     */
    public function type( $key){
        if( ! $this->connect()){
            return false;
        }
        return $this->oRedis->type( $key);
    }
    /**
     * 获取连接信息
     * @return String
     */
    public function ping(){
        $this->connect();
        
        return $this->oRedis->ping();
    }
    /**
     * 关闭连接
     */
    public function close(){
        $this->connect && $this->oRedis->close();
        $this->connected = false;
    }
    private function errorlog($keys, $code, $msg){
        $error = date('Y-m-d H:i:s').":\n".$code.";\nkeys:".var_export($keys, true).";\nmsg:{$msg}\n";
        fun::logs('redisErr', $error);
    }
}