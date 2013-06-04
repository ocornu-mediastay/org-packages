<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$outputConfigFile = __DIR__ . '/../compiled/projects.inc.php';

$application = new Application();
$application->add(new \Symfony\Component\Console\OrganizationProjects($outputConfigFile));
$projectsData = $application->run();