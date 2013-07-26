<?php

namespace Marvin\Packagist;

use Guzzle\Http\Exception\ClientErrorResponseException as GuzzleClientException;

class Client extends \Packagist\Api\Client
{
    public function getPackageInfo($packageName)
    {
        if (false === strpos($packageName, '/')) {
            return array();
        }

        try {
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
        } catch (GuzzleClientException $e) {
            return array();
        }
    }
}