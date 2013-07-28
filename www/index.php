<?php

require __DIR__ . '/../vendor/autoload.php';

function error($twig, $message,array $orgs=array())
{
    echo $twig->render('error.twig', array('message' => $message,'orgs'=>$orgs));
    exit;
}

date_default_timezone_set('Europe/Paris');

$selectedOrganization = isset($_GET['org']) ? $_GET['org'] : false;

$loader = new Twig_Loader_Filesystem(__DIR__ . '/../resources/views');
$twig = new Twig_Environment($loader, array(
    'auto_reload' => true,
    'cache' => __DIR__ . '/../compiled/views/cache',
));

$compiledFilePath = __DIR__ . '/data/projects.json';

if (!file_exists($compiledFilePath)) {
    error($twig, sprintf('file %s is missing.', $compiledFilePath));
}
$data = json_decode(file_get_contents($compiledFilePath),true);
if (!$selectedOrganization || !isset($data[$selectedOrganization])) {
    error($twig, 'missing valid organization to display, try one of them: ',array_keys($data));
}

$organizationData = $data[$selectedOrganization];
$packages = array();
$projects = $organizationData['projects'];
foreach ($projects as $project => $projectPackages) {
    foreach ($projectPackages as $packageName) {
        if (!isset($packages[$packageName])) {
            $packages[$packageName] = array($project);
        } else {
            $packages[$packageName][] = $project;
        }
    }
    if(empty($projectPackages)){
        unset($projects[$project]);
    }
}
ksort($packages);

echo $twig->render('main.twig', array(
    'directory' => $organizationData['directory'],
    'lastModifiedTime' => filemtime($compiledFilePath),
    'organization' => ucfirst($selectedOrganization),
    'packages' => $packages,
    'projects' => $projects,
));

