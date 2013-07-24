<?php

namespace Symfony\Component\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Github\Exception\RuntimeException as GitHubRuntimeException;
use Guzzle\Http\Exception\ClientErrorResponseException as GuzzleClientException;
use Packagist\Api\Client as PackagistClient;

class OrganizationProjects extends Command
{

    protected $outputFilePath;

    public function __construct($outputFilePath, $name = null)
    {
        $this->outputFilePath = $outputFilePath;
        parent::__construct($name);
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

    protected function startsWith($haystack, $needle)
    {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    protected function buildDirectory(array $projects, $organization)
    {
        $directory = array();
        foreach ($projects as $main => $dependencies) {
            if (!isset($directory[$main])) {
                $info = $this->getInfoFromPackagist($main, $organization);
                $directory[$main] = $info;
            }
            foreach ($dependencies as $dependency) {
                if (!isset($directory[$dependency])) {
                    $info = $this->getInfoFromPackagist($dependency, $organization);
                    $directory[$dependency] = $info;
                }
            }
        }

        return $directory;
    }

    protected function getDependenciesFromJsonData($output, $index, $githubClient, $organization, $repositoryName)
    {
        $dependencies = array();
        $output->write($index . ' - scanning ' . $repositoryName . '... ');
        try {
            $composerJsonData = $this->retrieveComposerJsonData($githubClient, $organization, $repositoryName);
            if (property_exists($composerJsonData, 'require')) {
                $dependencies = array_keys((array)$composerJsonData->require);
                foreach ($dependencies as $dependency) {
                    if ('php' != $dependency) {
                        $dependencies[] = $dependency;
                    }
                }
            }
            $output->writeln('adding packages.');
            return $dependencies;
        } catch (GitHubRuntimeException $e) {
            $output->writeln('no "composer.json" file found, skipping.');
            return array();
        }
    }

    protected function retrieveProjects($githubClient, $organization, $repositories, OutputInterface $output)
    {
        $projects = array();
        foreach ($repositories as $index => $repository) {
            $repositoryName = $repository['name'];
            $dependencies = $this->getDependenciesFromJsonData($output, $index, $githubClient, $organization, $repositoryName);
            $projects[$organization . '/' . $repositoryName] = $dependencies;
        }
        return $projects;
    }

    protected function retrieveComposerJsonData($client, $organization, $repositoryName)
    {
        $composerJsonContent = $client->api('repository')->contents()->download(
            $organization,
            $repositoryName,
            'composer.json'
        );
        $composerJsonData = json_decode($composerJsonContent);

        return $composerJsonData;
    }

    protected function retrieveRepositoriesFromOrganization($client, $organization)
    {
        $repositories = $client->api('organization')->repositories($organization);

        return $repositories;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $authenticationToken = $input->getArgument('token');
        $organization = $input->getArgument('organization');

        $githubClient = new \Github\Client();
        if (!empty($authenticationToken)) {
            $githubClient->authenticate($authenticationToken, \Github\Client::AUTH_HTTP_TOKEN);
        }

        $repositories = $this->retrieveRepositoriesFromOrganization($githubClient, $organization);
        $output->writeln('retrieving ' . count($repositories) . ' projects from ' . $organization . ' organization' . PHP_EOL);

        $projects = $this->retrieveProjects($githubClient, $organization, $repositories, $output);

        $output->writeln('building directory');

        $data = array(
            'directory' => $this->buildDirectory($projects, $organization),
            'organization' => $organization,
            'projects' => $projects,
        );

        $fileContents = '<?php return ' . trim(var_export($data, true)) . ';';

        file_put_contents($this->outputFilePath, $fileContents);
    }

    protected function getInfoFromPackagist($packageName, $organization)
    {
        if ($this->startsWith($packageName, $organization)) {
            return array('githubUrl' => 'https://github.com/' . $packageName);
        }
        try {
            $packagist = new PackagistClient();
            $package = $packagist->get($packageName);
            $version = current($package->getVersions());
            $source = $version->getSource();
            $packageData = array(
                'description' => $package->getDescription(),
                'homepageUrl' => $version->getHomepage(),
                'packagistUrl' => 'https://packagist.org/packages/' . $packageName,
                'githubUrl' => $source->getUrl()
            );
            return $packageData;
        } catch (GuzzleClientException $e) {
            return array();
        }
    }

}