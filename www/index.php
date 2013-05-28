<?php

require __DIR__ .'/../vendor/autoload.php';

date_default_timezone_set('Europe/Paris');

$loader = new Twig_Loader_Filesystem(__DIR__ . '/../resources/views');
$twig = new Twig_Environment($loader, array(
    'auto_reload' => true,
    'cache' => __DIR__ . '/../../compiled/views/cache',
));

$compiledFilePath = __DIR__. '/../compiled/projects.inc.php';

if(file_exists($compiledFilePath)) {
    $lastModifiedTime=filemtime($compiledFilePath);
    $projects = include $compiledFilePath;
    ksort($projects);

    $deduplicatePackages=array();
    $packages=array();
    foreach($projects as $project => $projectPackages) {
        foreach($projectPackages as $package) {
            if(!isset($packages[$package])){
                $packages[$package]=array($project);
            } else {
                $packages[$package][]=$project;
            }
            if(!in_array($package,$deduplicatePackages)){
                $deduplicatePackages[]=$package;
            }
        }
    }
    ksort($packages);
    sort($deduplicatePackages);

    echo $twig->render('main.twig',array(
        'deduplicatePackages'=>$deduplicatePackages,
        'lastModifiedTime'=>$lastModifiedTime,
        'packages'=>$packages,
        'projects'=>$projects
    ));
} else {
    echo $twig->render('error.twig',array());
}