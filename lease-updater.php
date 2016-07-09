#!/usr/bin/php
<?php
/**
 * Created by IntelliJ IDEA.
 * User: Andrey
 * Date: 07-Jul-16
 * Time: 09:22 PM
 */


use Andsol\DLink;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use JJG\Ping;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$commandLineOptions = new Getopt(array(
    new Option('h', 'host', Getopt::REQUIRED_ARGUMENT),
    new Option('p', 'password', Getopt::REQUIRED_ARGUMENT),
    new Option('l', 'login', Getopt::REQUIRED_ARGUMENT),
));

try {

    $commandLineOptions->parse();

    echo 'Ip check' . PHP_EOL;

    // Google server to ping
    $hostToPing = '8.8.8.8';

    $updateLease = true;


    $i = 0;
    while($i < 5) {
        $i++;
        $ping = new Ping($hostToPing);
        $latency = $ping->ping();
        if($latency){
            echo 'Host: ' . $hostToPing . ' latency: ' .   $latency . PHP_EOL;
            $updateLease = false;
            continue;
        }
        sleep(1);
    }

    if($updateLease){
        echo 'Can not ping: ' . $hostToPing . PHP_EOL;

        if(!$commandLineOptions->getOption('h') && !$commandLineOptions->getOption('l') && !$commandLineOptions->getOption('p')){
            throw new InvalidArgumentException('Missing arguments');
        }

        $client = new Client([RequestOptions::COOKIES => true]);
        $dlink = new DLink($commandLineOptions->getOption('h'), $commandLineOptions->getOption('l'), $commandLineOptions->getOption('p'), $client);
        $data = $dlink->status();

        if(!(array_key_exists('wanPara', $data) && array_key_exists(2, $data['wanPara']))){
            throw new RuntimeException('Can not get ip details');
        }

        echo 'Ip before: ' . $data['wanPara'][2] . PHP_EOL;
        sleep(1);

        $dlink->release();
        $data = $dlink->status();
        echo 'Release to: ' . $data['wanPara'][2] . PHP_EOL;
        sleep(3);

        $dlink->renew();
        echo 'Wait..' . PHP_EOL;
        sleep(20);
        $data = $dlink->status();
        echo 'Renew to: ' . $data['wanPara'][2] . PHP_EOL;
    }

} catch (\UnexpectedValueException $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo $commandLineOptions->getHelpText();
    exit(1);
} catch (\Exception $e){
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}