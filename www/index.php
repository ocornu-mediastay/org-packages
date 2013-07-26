<?php

require __DIR__ . '/../vendor/autoload.php';

function error($twig, $message)
{
    echo $twig->render('error.twig', array('message' => $message));
    exit;
}

date_default_timezone_set('Europe/Paris');

$selectedOrganization = isset($_GET['org']) ? $_GET['org'] : false;

$loader = new Twig_Loader_Filesystem(__DIR__ . '/../resources/views');
$twig = new Twig_Environment($loader, array(
    'auto_reload' => true,
    'cache' => __DIR__ . '/../compiled/views/cache',
));

$compiledFilePath = __DIR__ . '/../compiled/projects.inc.php';

if (!file_exists($compiledFilePath)) {
    error($twig, sprintf('file %s is missing.', $compiledFilePath));
}
if (!$selectedOrganization) {
    error($twig, 'organization is required. Try "org" param in URI');
}
$lastModifiedTime = filemtime($compiledFilePath);
$data = include $compiledFilePath;

if (!isset($data[$selectedOrganization])) {
    error($twig, sprintf('unable to find data about %s organization.', $selectedOrganization));
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
}
ksort($packages);

echo $twig->render('main.twig', array(
    'directory' => $organizationData['directory'],
    'lastModifiedTime' => $lastModifiedTime,
    'organization' => ucfirst($selectedOrganization),
    'packages' => $packages,
    'projects' => $projects,
));

