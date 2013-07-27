<?php

namespace Marvin\Packagist;

class Client extends \Packagist\Api\Client
{
    public function getPackageInfo($packageName)
    {
        $package = $this->get($packageName);
        $version = current($package->getVersions());
        $source = $version->getSource();
        $packageData = array(
            'description' => $package->getDescription(),
            'homepageUrl' => $version->getHomepage(),
            'packagistUrl' => 'https://packagist.org/packages/' . $packageName,
            'githubUrl' => $source->getUrl()
        );
        return $packageData;
    }
}