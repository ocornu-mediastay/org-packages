<?php

namespace Marvin\GitHub;

use Github\Exception\RuntimeException as GitHubRuntimeException;

class Client extends \Github\Client
{
    public function __construct($authenticationToken = false)
    {
        if ($authenticationToken) {
            $this->authenticate($authenticationToken, self::AUTH_HTTP_TOKEN);
        }
    }

    public function retrieveRepositoriesFromOrganization($organization)
    {
        $repositories = $this->api('organization')->repositories($organization);

        return $repositories;
    }

    public function retrieveProjects($organization, $repositories)
    {
        $projects = array();
        foreach ($repositories as $index => $repository) {
            $repositoryName = $repository['name'];
            $dependencies = $this->getDependenciesFromJsonData($index, $organization, $repositoryName);
            $projects[$organization . '/' . $repositoryName] = $dependencies;
        }
        return $projects;
    }

    protected function getDependenciesFromJsonData($index, $organization, $repositoryName)
    {
        $dependencies = array();
        //$output->write($index . ' - scanning ' . $repositoryName . '... ');
        try {
            $composerJsonData = $this->retrieveComposerJsonData($organization, $repositoryName);
            if (property_exists($composerJsonData, 'require')) {
                $dependencies = array_keys((array)$composerJsonData->require);
                foreach ($dependencies as $dependency) {
                    if ('php' != $dependency) {
                        $dependencies[] = $dependency;
                    }
                }
            }
            //$output->writeln('adding packages.');
            return $dependencies;
        } catch (GitHubRuntimeException $e) {
            //$output->writeln('no "composer.json" file found, skipping.');
            return array();
        }
    }

    protected function retrieveComposerJsonData($organization, $repositoryName)
    {
        $contents = $this->api('repository')->contents();
        $composerJsonContent = $contents->download($organization, $repositoryName, 'composer.json');
        $composerJsonData = json_decode($composerJsonContent);

        return $composerJsonData;
    }

}