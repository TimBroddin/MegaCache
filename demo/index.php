<!DOCTYPE html>
<html>
	<head>
		<title>MegaCache</title>
		<style>
			body {
				background-color: #EFEFEF;
				font-family: Verdana;
				font-size: 12px;
			}
			
			#wrapper {
				margin: 10px auto;
				padding: 10px;
				background-color:  white;
				width: 750px;
				-webkit-box-shadow: 2px 2px 5px black;
			}
			
			h1 {
				font-size: 70px;
				color: white;
				text-shadow: 3px 3px 13px black;
				text-align: center;
				cursor: pointer;
				-webkit-transition: all 0.25s ease-in-out;
				-moz-transition: all 0.25s ease-in-out;
				-o-transition: all 0.25s ease-in-out;
				
			}
			
			h1:hover {
				-webkit-transform: scale(3) rotate(360deg);
				-moz-transform: scale(3) rotate(360deg);
				-o-transform: scale(3) rotate(360deg);
				
			}
			
			.syntaxhighlighter {
				padding: 5px;
				font-size: 11px;
				
			}
			
			.section {				
				padding: 5px;
				border: 1px solid #EFEFEF;
				margin: 10px 0px;
			}
			
			.section:hover {
				background-color: #FEFEFE;
			}
			
			h2 {
				color: #AAA;
				border-bottom: 1px solid #EEE;
			}
			
			.rss a {
				color: black;
			}
			
			.rss {
				line-height: 2;
				padding-left: 20px;
			}
			
			.status {
				border: 1px solid #EFEFEF;
				padding: 10px;
			}
			
			.on {
				background-color: #11BB33;
				color: white;
				text-shadow: 1px 1px 1px #111;
			}
			
			.off {
				background-color: #BB1133;
				color: white;
				text-shadow: 1px 1px 1px #111;				
			}
			
			.on a {
				color: red;
				text-shadow: none;
				font-weight: bold;
			}
			
			.off a {
				color: green;
				text-shadow: none;
				font-weight: bold;
			}
			
			
			.time, .total_time {
				border: 1px solid #EFEFEF;
				background-color: white;
				margin: 5px;
				padding: 5px;
				font-weight: bold;
			
			}
		</style>
		<link href="http://alexgorbatchev.com/pub/sh/current/styles/shThemeDefault.css" rel="stylesheet" /> 
		<script src="http://alexgorbatchev.com/pub/sh/current/scripts/shCore.js"></script> 
		<script src="http://alexgorbatchev.com/pub/sh/current/scripts/shBrushPhp.js"></script> 
		<script src="http://alexgorbatchev.com/pub/sh/current/scripts/shAutoloader.js"></script> 		
	</head>
	<body>
		<?php
		require_once('../lib/CacheFactory.php');
		
		class FakeCache extends BaseCache {
			public function get($a) { return false; }
			public function set($a, $b, $c=0) { return false; }
			public function delete($a) { return false; }
		}
		
		class StopWatch { 
		    public $total; 
		    public $time; 
		    
		    public function __construct() { 
		        $this->total = $this->time = microtime(true); 
		    } 
		    
		    public function clock() { 
		        return round(-$this->time + ($this->time = microtime(true)),3); 
		    } 
		    
		    public function elapsed() { 
		        return microtime(true) - $this->total; 
		    } 
		    
		    public function reset() { 
		        $this->total=$this->time=microtime(true); 
		    } 
		} 

		$stopwatch = new StopWatch();
		
		if(!isset($_GET['disable_cache'])) {
			$cache = CacheFactory::factory('apc', array('cacheName' => 'demoke'));
			$status = 'on';
		} else {
			$cache = new FakeCache();
			$status = 'off';
		}
		
		?>
		<div id="wrapper">
			<h1 class="tk-blambot-fx-pro">MegaCache</h1>
			<div class="status <?php echo $status; ?>">
				Cache is <?php echo $status ?>. 
				<?php if($status == "on"): ?>
				Cache adapter is <abbr title="Alternative PHP Cache">APC</abbr>.<br /> Turn cache <a href="?disable_cache=1">off</a>.
				<?php else: ?>
				<br />Turn cache <a href="index.php">on</a>.
				<?php endif; ?>
	
			</div>
		
			<div class="section">
				<h2>Demo 1: cache external RSS-feed</h2>
				<h3>Code:</h3>
				<pre class="brush: php">
					$rss = $cache->fetch('http://rss.cnn.com/rss/edition.rss', 3600); // cache for 3600s
					$feed = new SimpleXmlElement($rss);
					foreach($feed->channel->item as $item) {
						echo '<a href="' . strip_tags($item->link) . '">' . strip_tags($item->title) . '</a><br />';
					}
				</pre>
				
				<h4>Result:</h4>
				<div class="rss">
				<?php
					$rss = $cache->fetch('http://rss.cnn.com/rss/edition.rss', 3600); // cache for 3600s
					$feed = new SimpleXmlElement($rss);
					foreach($feed->channel->item as $item) {
						echo '<a href="' . strip_tags($item->link) . '">' . strip_tags($item->title) . '</a><br />';
					}			
				?>
				</div>
				
				<div class="time">Time taken: <?php echo $stopwatch->clock(); ?>s</div>
			</div>
			<div class="section">
				<h2>Demo 2: cache a function</h2>
				<h3>Code:</h3>
				<pre class="brush: php">				
					function longCalulation($a, $b) {
						// do some useless number crunching
						for($i=0;$i<1000;$i++) {
							for($j=0;$j<1000;$j++) {
								$k = $i%$j;
							}
						}
						// do the math
						return $a + $b;
					}
					
					echo $cache->call('longCalculation', array(10, 20), 3600); // cache for 3600s
				</pre>
				
				<h3>Result:</h3>
				<?php
					function longCalculation($a, $b) {
						// do some number crunching
						for($i=1;$i<1000;$i++) {
							for($j=1;$j<1000;$j++) {
								$k = $i%$j;
							}
						}						
						// do the math
						return $a + $b;
					}
					
					echo $cache->call('longCalculation', array(10, 20), 3600); // cache for 3600s
				
				?>

				<div class="time">Time taken: <?php echo $stopwatch->clock(); ?>s</div>
			</div>
			<div class="section">
				<h2>Demo 3: cache a page fragment</h2>
				<h3>Code:</h3>
				<pre class="brush: php">
				if(!$cache->fragment('header')) {
					echo "The time will stand still for 60 seconds: " . date('H:i:s');
					sleep(5); // to simulate some heavy processing 
					$cache->saveFragment(60);
				}					
				</pre>
				
				<h3>Result:</h3>
				<p>Refresh to see the result</p>
				<?php
				if(!$cache->fragment('header')) {
					sleep(5);
					echo "The time will stand still for 60 seconds: " . date('H:i:s');
					$cache->saveFragment(60);
				}				
				?>
				<div class="time">Time taken: <?php echo $stopwatch->clock(); ?>s</div>
			</div>		
			
			<div class="total_time">Total time taken: <?php echo $stopwatch->elapsed(); ?>s </div>
		</div>
		
	
	<script> SyntaxHighlighter.all();</script>
	</body>
</html>