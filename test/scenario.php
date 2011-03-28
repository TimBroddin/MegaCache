<?php
header('Content-type: text/plain');
ob_start();
error_reporting(E_STRICT);
ini_set('display_errors', 'On');

$cache->flush();
for($x=1;$x<=3;$x++) {
	echo "RUN $x\n--------------\n\n";
	
	$watch = new StopWatch();
	
	echo "Test 1: simple variable caching\n\n";
	// simpe variable caching
	if($test1 = !$cache->get('somevariable')) {
		sleep(1);
		echo "- Not cached\n";
		$cache->set('somevariable', 'lorem', 0);
	} else {
		echo "- Cached\n";
	}
	echo "Duration: " . $watch->clock() . "\n";
	ob_flush();
	flush();
	$start = microtime();
	
	
	echo "\nTest 2: setting a huge amount of variables (1000)\n\n";
	
	for($i=0;$i<1000;$i++) {
		$cache->set('test' . $i, 1);
	}
	echo "Duration: " . $watch->clock() . "\n";
	ob_flush();
	flush();
	
	echo "\nTest 3: getting a huge amount of variables (1000)\n\n";
	for($i=0;$i<1000;$i++) {
		$result = $cache->get('test' . $i, 1);
		if(!$result) {
			echo "X";
		}
	}
	echo "Duration: " . $watch->clock() . "\n";
	ob_flush();
	flush();
	
	echo "\nTest 4: deleting a huge amount of variables (1000)\n\n";
	for($i=0;$i<1000;$i++) {
		$cache->delete('test' . $i, 1);
	}
	echo "Duration: " . $watch->clock() . "\n";
	ob_flush();
	flush();
	
	echo "\nTest 5: function caching (100)\n\n";
	// function caching
	for($a=0;$a<10;$a++) {
		for($b=0;$b<10;$b++) {
			$cache->call('do_sum', array($a, $b), 60);
		}
	}
	echo "Duration: " . $watch->clock() . "\n";
	ob_flush();
	flush();
	
	// resource caching
	echo "\nTest 6: resource caching\n\n";
	echo strlen($cache->fetch('http://twitter.com/statuses/user_timeline/61297416.rss', 20)) . "\n\n";
	echo "Duration: " . $watch->clock() . "\n";
	
	// pruned
	echo "\nTest 7: caching for next run (60 seconds)\n\n";
	if(!$cache->get('test-a-0')) {
		echo "Not yet cached\n\n";
	} else {
		echo "Cached\n\n";
	}
	
	for($i=0;$i<5;$i++) {
		if(!$cache->get('test-a-' . $i)) {
			sleep(1);
			$cache->set('test-a-' . $i, 1, 60);
		}
	}
	
	echo "Duration: " . $watch->clock() . "\n";
	
	echo "\nTest 8: ArrayAccess\n\n";
	$cache['arrayaccess'] = 5;
	if($cache['arrayaccess'] == 5) {
		echo "Array access works!\n\n";
	} else {
		echo "Array access failed!\n\n";
	}
	
	echo "\nTest 9: Fragment caching\n\n";
	
	if(!$cache->fragment('heading')) {
		for($k=0;$k<5;$k++) {
			sleep(1);
			echo date('H:i:s') . "\n";
		}
		$cache->saveFragment(60);	
	}
	
	echo "\n\nDuration: " . $watch->clock() . "\n";	
	echo "Global duration: " . $watch->elapsed() . "\n\n\n";
}

// helpers
function do_sum($a, $b) {
	usleep(200);
	return ($a + $b);
}

class StopWatch { 
    public $total; 
    public $time; 
    
    public function __construct() { 
        $this->total = $this->time = microtime(true); 
    } 
    
    public function clock() { 
        return round(-$this->time + ($this->time = microtime(true)),2); 
    } 
    
    public function elapsed() { 
        return microtime(true) - $this->total; 
    } 
    
    public function reset() { 
        $this->total=$this->time=microtime(true); 
    } 
} 
