<?php
/**
 * Mongo操作类
 */
class MongoHelper {

	private $manager = null; //数据库连接对象
	private $writeConcern;
	private $replicaSet;

	public function __construct($aSer){
		$this->replicaSet = $aSer['replicaSet'];
		$aV = array();
		foreach((array)$aSer['servers'] as $row){
			$aV[] = $row[0] . ':' . $row[1];
		}

		$this->servers = implode(',', $aV);
		$this->doconn();
		$this->writeConcern = new MongoDB\Driver\WriteConcern(1, 3000);
	}
	
	public function doconn(){
		$opt = array(
			'connect' => true,
			'connectTimeoutMS' => 1000,
			'socketTimeoutMS' => 5000,
			'replicaSet' => $this->replicaSet,
		);
		$this->manager = new MongoDB\Driver\Manager("mongodb://". $this->servers, $opt);
	}

	/**
	 * 安全插入数据
	 * @param type $tbl
	 * @param type $data
	 * @return int 插入的行数
	 */
	public function insert($tbl, $data){
		try {
			$bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
			$bulk->insert($data);
			$result = $this->manager->executeBulkWrite($tbl, $bulk, $this->writeConcern);
			return $result->getInsertedCount();
		}catch (Exception $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}catch (Error $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}
		$this->logs(array('insert',  $tbl, $errExcep));
		return false;
	}

	/**
	 * 查询并返回一条记录
	 * @param type $tbl
	 * @param type $criteria
	 * @param type $fields
	 * @return object|boolean false时表示未取到结果，否则为object
	 */
	public function findOne($tbl, $criteria, $fields = array()){
		try {
			$needs = array();
			if (!empty($fields)){
				foreach ($fields as $v) {
					$needs[$v] =  1;
				}
			}
			$options['projection'] = $needs;
			$options['limit'] = 1;
			$query = new MongoDB\Driver\Query($criteria, $options);
			$rp = new MongoDB\Driver\ReadPreference(MongoDB\Driver\ReadPreference::RP_PRIMARY);
			$result = $this->manager->executeQuery($tbl, $query, $rp);
			$result->setTypeMap(['document' => 'array', 'root' => 'array']);
			return current($result->toArray());
		}catch (Exception $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}catch (Error $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}
		$this->logs(array('findOne',  $tbl, $errExcep));
		return false;
	}

	/**
	 * 查询记录
	 * @param type $tbl
	 * @param type $criteria
	 * @param type $fields
	 * @return object|boolean 空数组时表示未取到结果，否则为object
	 */
	public function find($tbl, $criteria, $fields = array(), $limit = 0){
		try {
			$needs = array();
			if (!empty($fields)) {
				foreach ($fields as $v) {
					$needs[$v] =1;
				}
			}
			$options['projection'] = $needs;
			if(is_array($limit)){
				list($options['skip'] , $options['limit']) = $limit;
			}elseif($limit){
				$options['limit'] = $limit;
			}
			$query = new MongoDB\Driver\Query($criteria, $options);
			$result = $this->manager->executeQuery($tbl, $query);
			$result->setTypeMap(['document' => 'array', 'root' => 'array']);
			$documents = array();
			foreach ($result as $ret) {
				$documents[] = $ret;
			}
			return $documents;
		}catch (Exception $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}catch (Error $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}
		$this->logs(array('find',  $tbl, $errExcep));
		return false;
	}

	public function count($tbl, $filter) {
		try{
			list($dbName, $coll) = explode('.', $tbl);
			$options = ['count' => $coll];
			$options['query'] = $filter;
			$cmd = new MongoDB\Driver\Command($options);
			$cursor = $this->manager->executeCommand($dbName, $cmd);
			$ret = current($cursor->toArray());
			if (is_object($ret) && $ret->ok == 1) {
				return $ret->n;
			}
		}catch (Exception $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}catch (Error $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}
		$this->logs(array('count',  $tbl, $errExcep));
		return false;
	}

	/**
	 * 原子性更新
	 * @param type $tbl
	 * @param type $criteria
	 * @param type $update
	 * @param type $fields
	 * @param array $options
	 * @return array 返回的新值，为null时表示不存在记录
	 */
	public function findAndModify($tbl, $criteria, $update, $fields = array(), $options = array()) {
		try{
			if (!empty($fields)) {
				$fields = array_flip($fields);
			}
			list($dbName, $coll) = explode('.', $tbl);
			$cmd['findAndModify'] = $coll;
			$cmd['query'] = $criteria;
			$cmd['update'] = $update;
			$cmd['fields'] = $fields;
			$cmd['writeConcern'] = $this->writeConcern;
			$cmd['new'] = true;
			if (!empty($options)) {
				foreach (['writeConcern', 'new', 'sort'] as $key) {
					if (isset($options[$key])) {
						$cmd[$key] = $options[$key];
					}
				}
			}
			$commnd = new MongoDB\Driver\Command($cmd);
			$cursor = $this->manager->executeCommand($dbName, $commnd);
			$cursor->setTypeMap(['document' => 'array']);
			return current($cursor->toArray())->value;
		}catch (Exception $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}catch (Error $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}
		$this->logs(array('findAndModify',  $tbl, $errExcep));
		return false;
	}

	/**
	 * 删除
	 * @param type $tbl
	 * @param type $id
	 * @return int 返回删除的行数
	 */
	public function delete($tbl, $filter, $limit = 1) {
		$bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
		$bulk->delete($filter, ['limit' => $limit]);
		$result = $this->manager->executeBulkWrite($tbl, $bulk, $this->writeConcern);
		return $result->getDeletedCount();
	}

	/**
	 * 更新数据
	 * @param type $tbl
	 * @param type $filter
	 * @param type $update
	 * @param type $limit
	 * @return type
	 */
	public function update($tbl, $filter, $update, $limit = 1) {
		try{
			$bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
			$bulk->update($filter, $update, ['limit' => $limit, 'upsert' => true]);//默认不存在就插入
			$result = $this->manager->executeBulkWrite($tbl, $bulk, $this->writeConcern);
			return $result->getDeletedCount();
		}catch (Exception $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}catch (Error $e){
			$this->doconn();
			$errExcep = $e->getMessage();
		}
		$this->logs(array('update',  $tbl, $errExcep));
		return false;
	}
	
	private function logs($msg){
		if(is_array($msg)){
			$msg = json_encode($msg);
		}
		fun::logs('mongoErr', $msg);
	}

}
