<?php
/**
 * Created by SxdStorm.
 * User: yuemingzeng
 * Date: 2018/8/3
 * Time: 15:55
 */

class BaseProcess
{
	private $process_list = [];         // 进程池对象数组
	private $process_use = [];          // 进程占用标记数组
	private $min_worker_num = 3;        // 进程池最小值
	private $max_worker_num = 6;        // 进程池最大值

	private $current_num;               // 当前进程数

	public function __construct()
	{
		$this->run();
	}

	public function run()
	{
		$this->current_num = $this->min_worker_num;

		// 初始化进程池
		for($i=0;$i<$this->current_num ; $i++){
			$process = new swoole_process(array($this, 'task_run') , false , 2);
			$pid = $process->start();
			$this->process_list[$pid] = $process;
			$this->process_use[$pid] = 0;
		}
		// 绑定子进程管道的读事件，接收子进程任务结束的通知
		foreach ($this->process_list as $process) {
			swoole_event_add($process->pipe, function ($pipe) use($process) {
				$data = $process->read();
				var_dump($data);
				$this->process_use[$data] = 0;
			});
		}
		// 使用定时器，每500ms派发一个任务，共发送10个
		swoole_timer_tick(500, function($timer_id) {
			static $index = 0;
			$index = $index + 1;
			$flag = true;
			// 查找是否有可用的进程派发任务
			foreach ($this->process_use as $pid => $used) {
				// 找到了闲置的进程
				if($used == 0)
				{
					$flag = false;
					$this->process_use[$pid] = 1;
					// 派发任务
					$this->process_list[$pid]->write($index . "Hello");
					break;
				}
			}
			// 没有找到进程，并且进程池并没有满
			if( $flag && $this->current_num < $this->max_worker_num )
			{
				// 创建新的进程
				$process = new swoole_process(array($this, 'task_run') , false , 2);
				$pid = $process->start();
				$this->process_list[$pid] = $process;
				$this->process_use[$pid] = 1;
				// 派发任务
				$this->process_list[$pid]->write($index . "Hello");
				$this->current_num ++;
				// 绑定子进程管道的读事件
				swoole_event_add($process->pipe, function ($pipe) use($process) {
					$data = $process->read();
					var_dump($data);
					$this->process_use[$data] = 0;
				});
			}
			var_dump($index);
			if( $index == 10 )
			{
				// 任务结束，退出所有子进程
				foreach ($this->process_list as $process) {
					$process->write("exit");
				}
				swoole_timer_clear($timer_id);
			}
		});
	}

	public function task_run($worker)
	{
		// 注册监听管道的事件，接收任务
		swoole_event_add($worker->pipe, function ($pipe) use ($worker){
			$data = $worker->read();
			var_dump($worker->pid . ": " . $data);
			if($data == 'exit')
			{
				// 收到退出指令，关闭子进程
				$worker->exit();
				exit;
			}
			// 模拟任务执行过程
			sleep(mt_rand(1, 2));
			// 执行完成，通知父进程
			$worker->write("" . $worker->pid);
		});
	}
}

new BaseProcess();
// 注册信号，回收退出的子进程
swoole_process::signal(SIGCHLD, function($sig) {
	while($ret =  swoole_process::wait(false)) {
		echo "PID={$ret['pid']}\n";
	}
});