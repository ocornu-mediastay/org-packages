<?php

namespace Symfony\Component\Console;

class OrganizationProjects extends Command\Command
{
    protected function configure()
    {
        $this
            ->setName('org:packages')
            ->setDescription('Retrieve packages and projects from organization')
            ->addArgument(
                'organization',
                Input\InputArgument::REQUIRED,
                'name of organization in GitHub to retrieve packages from'
            )
            ->addArgument(
                'token',
                Input\InputArgument::REQUIRED,
                'valid application token'
            );
    }

    protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
    {
        $authenticationToken = $input->getArgument('token');
        $organization = $input->getArgument('organization');
        $outputConfigFile = __DIR__ . '/../../../../compiled/projects.inc.php';

        $github = new \Github\Client();
        $github->authenticate($authenticationToken, \Github\Client::AUTH_HTTP_TOKEN);

        $packagist = new \Packagist\Api\Client();

        $repositories = $github->api('organization')->repositories($organization);

        $text = 'retrieving ' . count($repositories) . ' projects from ' . $organization . ' organization' . "\n";

        $projects = array();
        foreach ($repositories as $repository) {
            $project = $repository['name'];

            $text .= 'scanning ' . $project . '... ';
            try {
                $content = $github->api('repository')->contents()->download($organization, $project, 'composer.json');
                $json_data = json_decode($content);
                $projects[$project] = array();
                foreach ($json_data->require as $packageName => $version) {
                    if ('php' != $packageName) {
                        try {
                            $package=$packagist->get($packageName);
                            $version = array_pop($package->getVersions());
                            $packageData = array(
                                'description' => $package->getDescription(),
                                'homepage' => $version->getHomepage(),
                            );
                            $projects[$project][$packageName] = $packageData;
                        } catch(\Guzzle\Http\Exception\ClientErrorResponseException $e){
                            //nothing
                        }
                    }
                }
                $text .= 'adding projects';
            } catch (\Github\Exception\RuntimeException $e) {
                $text .= 'composer.json file not found';
            }
            $text .= "\n";
        }

        $fileContents = '<?php return ' . trim(var_export($projects, true)) . ';';

        file_put_contents($outputConfigFile, $fileContents);

        $output->writeln($text);
    }

}