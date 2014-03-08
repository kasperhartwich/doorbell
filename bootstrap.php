<?php
require_once(dirname(__FILE__) . '/vendor/autoload.php');
foreach (glob(dirname(__FILE__) . '/app/*.php') as $filename) {
    include($filename);
}
$GLOBALS['config'] = parse_ini_file(dirname(__FILE__) . '/config.ini', true);

$app = new \Klein\App();
$app->register('db', function() {
    $temp = new pdoext_DummyConnection(); //TODO: Temp. hack to include pdoext.inc.php
    return isset($GLOBALS['db']) ? $GLOBALS['db'] : $GLOBALS['db'] = new pdoext_Connection(
    	$GLOBALS['config']['database']['dsn'], 
    	$GLOBALS['config']['database']['username'], 
    	$GLOBALS['config']['database']['password']
    );
});
