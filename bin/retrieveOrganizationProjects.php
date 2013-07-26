<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Marvin\Console\OrganizationProjects;

$outputConfigFile = __DIR__ . '/../compiled/projects.inc.php';

$application = new Application();
$application->add(new OrganizationProjects($outputConfigFile));
$projectsData = $application->run();