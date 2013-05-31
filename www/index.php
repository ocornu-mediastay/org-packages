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

    $deduplicatePackages=array();
    $packages=array();
    $projects=$data['projects'];
    $organization=$data['organization'];
    foreach($projects as $project => $projectPackages) {
        $projectUrl='https://github.com/'.$organization.'/'.$project;
        foreach($projectPackages as $packageName => $packageData) {
            if(!isset($packages[$packageName])){
                $packages[$packageName]=array($project=>$projectUrl);
            } else {
                $packages[$packageName][$project]=$projectUrl;
            }
            if(!isset($deduplicatePackages[$packageName])){
                $deduplicatePackages[$packageName]=$packageData;
            }
        }
    }
    ksort($packages);
    ksort($deduplicatePackages);

    echo $twig->render('main.twig',array(
        'deduplicatePackages'=>$deduplicatePackages,
        'lastModifiedTime'=>$lastModifiedTime,
        'packages'=>$packages,
        'projects'=>$projects,
        'organization'=>ucfirst($organization)
    ));
} else {
    echo $twig->render('error.twig',array());
}
