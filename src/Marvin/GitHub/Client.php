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

    protected function filterPackages(array $array)
    {
        $hash = array();
        foreach ($array as $item) {
            if (strpos($item, '/') !== false) {
                $hash[$item] = $item;
            }
        }
        return $hash;
    }

    protected function getDependenciesFromJsonData($organization, $repositoryName)
    {
        $dependencies = array();
        try {
            $composerJsonData = $this->retrieveComposerJsonData($organization, $repositoryName);
            if (property_exists($composerJsonData, 'require')) {
                $dependencies = array_keys((array)$composerJsonData->require);
            }
            return $dependencies;
        } catch (GitHubRuntimeException $e) {
            return array();
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
        foreach ($repositories as $repository) {
            $repositoryName = $repository['name'];
            $dependencies = $this->getDependenciesFromJsonData($organization, $repositoryName);
            $dependencies = $this->filterPackages($dependencies);
            $projects[$organization . '/' . $repositoryName] = $dependencies;
        }
        return $projects;
    }

    protected function retrieveComposerJsonData($organization, $repositoryName)
    {
        $contents = $this->api('repository')->contents();
        $composerJsonContent = $contents->download($organization, $repositoryName, 'composer.json');
        $composerJsonData = json_decode($composerJsonContent);

        return $composerJsonData;
    }

}