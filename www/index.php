<?php

require __DIR__ .'/../vendor/autoload.php';

date_default_timezone_set('Europe/Paris');

$loader = new Twig_Loader_Filesystem(__DIR__ . '/../resources/views');
$twig = new Twig_Environment($loader, array(
    'auto_reload' => true,
    'cache' => __DIR__ . '/../compiled/views/cache',
));

$compiledFilePath = __DIR__. '/../compiled/projects.inc.php';

if(file_exists($compiledFilePath)) {
    $lastModifiedTime=filemtime($compiledFilePath);
    $data = include $compiledFilePath;

    $packages=array();
    $projects=$data['projects'];
    foreach($projects as $project => $projectPackages) {
        foreach($projectPackages as $packageName) {
            if(!isset($packages[$packageName])){
                $packages[$packageName]=array($project);
            } else {
                $packages[$packageName][]=$project;
            }
        }
    }
    ksort($packages);

    echo $twig->render('main.twig',array(
        'directory'=>$data['directory'],
        'lastModifiedTime'=>$lastModifiedTime,
        'organization'=>ucfirst($data['organization']),
        'packages'=>$packages,
        'projects'=>$projects,
    ));
} else {
    echo $twig->render('error.twig',array());
}
