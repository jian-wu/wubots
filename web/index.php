<?php
require(__DIR__ . '/../vendor/autoload.php');

use Monolog\Logger;
//use Monolog\Handler\LogglyHandler;
use Monolog\Handler\SyslogHandler;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Yaml\Yaml;

$app = new Application();

//$logConfig  = Yaml::parse(__DIR__ . '/../configs/loggly.yml');
// @todo activate Loggly logger before going in production
//$logHandler = new LogglyHandler($logConfig['token']);
// Syslog handler
$logHandler = new SyslogHandler('hooks');
//$logHandler->setTag('hooks');


$app['monolog'] = $app->share(
    function ($app) {
        return new Logger('Hooks');
    }
);

$app['monolog']->pushHandler($logHandler);

$app->error(
    function (\Exception $e, $code) use ($app) {
        $app['monolog']->addError($e->getMessage());

        if ($app['debug']) {
            return null;
        }

        $message = $e->getMessage();

//        if ($code != 200) {
//            $message = 'Bad request.';
//        }

        return new Response($message);
    }
);

$app->get(
    '/',
    function () {
        return new Response("my token is" . getenv('github_token'));
    }
);

$app->post('/payload', 'Orchard\\CiUtils\\Controller\\Index::payload');

/*
$app->get('/tracks/{upc}', 'Orchard\\Vector\\Controller\\TrackMeta::getTrackInfo')
    ->assert('upc', '\d+');

$app->get('/tracks/upc/{upc}', 'Orchard\\Vector\\Controller\\TrackMeta::getTrackInfo')
    ->assert('upc', '\d+');

$app->get('/clips/{upc}/{cd}/{track_id}/{clip_number}', 'Orchard\\Vector\\Controller\\TrackMeta::getTrackInfo')
    ->assert('upc', '\d+')
    ->assert('cd', '\d+')
    ->assert('track_id', '\d+')
    ->assert('clip_number', '\d+');

$app->get(
    '/clips/upc/{upc}/cd/{cd}/track_id/{track_id}/clip_number/{clip_number}',
    'Orchard\\Vector\\Controller\\TrackMeta::getTrackInfo'
)
    ->assert('upc', '\d+')
    ->assert('cd', '\d+')
    ->assert('track_id', '\d+')
    ->assert('clip_number', '\d+');

$app->get('/ringtones/{ringtone_id}', 'Orchard\\Vector\\Controller\\TrackMeta::getRingtoneInfo')
    ->assert('ringtone_id', '\d+');

$app->get('/ringtones/ringtone_id/{ringtone_id}', 'Orchard\\Vector\\Controller\\TrackMeta::getRingtoneInfo')
    ->assert('ringtone_id', '\d+');


$app->post('/assetpath', 'Orchard\\Vector\\Controller\\AssetProperty::getAssetPathInfo');

$app->get('/streams/{upc}/{physical_location_id}', 'Orchard\\Vector\\Controller\\Streams::getStreams')
    ->assert('upc', '\d+')
    ->assert('physical_location_id', '\d+');

$app->get(
    '/stream/{upc}/{physical_location_id}/{unique_track_id}/{token}',
    'Orchard\\Vector\\Controller\\Streams::playMp3'
)
    ->assert('upc', '\d+')
    ->assert('physical_location_id', '\d+')
    ->assert('unique_track_id', '\d+');
*/
$app->run();
