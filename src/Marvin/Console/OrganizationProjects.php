<?php

namespace Marvin\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Marvin\Packagist\Client as PackagistClient;
use Marvin\GitHub\Client as GitHubClient;
use Guzzle\Http\Exception\ClientErrorResponseException as GuzzleClientException;

class OrganizationProjects extends Command
{

    protected $outputFilePath;
    protected $directory;
    protected $output;

    public function __construct($outputFilePath, $name = null)
    {
        $this->outputFilePath = $outputFilePath;
        parent::__construct($name);
    }

    protected function addPackageInfo($githubClient, $packageName, $packagistClient)
    {
        if (isset($this->directory[$packageName])) {
            return;
        }
        try {
            $info = $packagistClient->getPackageInfo($packageName);
        } catch (GuzzleClientException $e) {
            $info = $githubClient->getPackageInfo($packageName);
        }

        $this->directory[$packageName] = $info;
        $this->out('adding packagist info on package ' . $packageName);
    }

    protected function buildDirectory($githubClient, array $projects)
    {
        $this->directory = array();
        $packagistClient = new PackagistClient();
        foreach ($projects as $main => $dependencies) {
            $this->addPackageInfo($githubClient, $main, $packagistClient);
            foreach ($dependencies as $dependency) {
                $this->addPackageInfo($githubClient, $dependency, $packagistClient);
            }
        }
    }

    protected function configure()
    {
        $this
            ->setName('org:packages')
            ->setDescription('Retrieve packages and projects from organization')
            ->addArgument(
                'organization',
                InputArgument::REQUIRED,
                'name of organization in GitHub to retrieve packages from'
            )
            ->addArgument(
                'token',
                InputArgument::OPTIONAL,
                'valid application token'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $authenticationToken = $input->getArgument('token');
        $organization = $input->getArgument('organization');

        $githubClient = new GitHubClient($authenticationToken);

        $repositories = $githubClient->retrieveRepositoriesFromOrganization($organization);
        $message = sprintf(PHP_EOL . 'retrieving %d projects from %s' . PHP_EOL, count($repositories), $organization);
        $this->out($message);

        $projects = $githubClient->retrieveProjects($organization, $repositories);

        $this->out('building directory');

        $this->buildDirectory($githubClient, $projects, $output);
        $data = array($organization => array('directory' => $this->directory, 'projects' => $projects));

        $compiledFilePath = __DIR__ . '/../../../compiled/projects.inc.php';
        if (file_exists($compiledFilePath)) {
            $existingData = include $compiledFilePath;
            if (is_array($existingData)) {
                $data = array_merge($existingData, $data);
            }
        }

        $fileContents = '<?php return ' . trim(var_export($data, true)) . ';';

        file_put_contents($this->outputFilePath, $fileContents);
    }

    protected function out($message)
    {
        $this->output->writeln($message);
    }

}