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
                'organization (name of organization in GitHub)',
                Input\InputArgument::REQUIRED,
                'missing valid organization'
            )
            ->addArgument(
                'token (valid application token)',
                Input\InputArgument::REQUIRED,
                'missing valid application token'
            );
    }

    protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
    {
        $authenticationToken = $input->getArgument('token');
        $organization = $input->getArgument('organization');
        $outputConfigFile = __DIR__ . '/../../../compiled/projects.inc.php';

        $client = new \Github\Client();
        $client->authenticate($authenticationToken, \Github\Client::AUTH_HTTP_TOKEN);

        $repositories = $client->api('organization')->repositories($organization);

        $text = 'retrieving ' . count($repositories) . ' projects from ' . $organization . ' organization' . "\n";

        $projects = array();
        foreach ($repositories as $repository) {
            $project = $repository['name'];

            $text .= 'scanning ' . $project . '... ';
            try {
                $content = $client->api('repository')->contents()->download($organization, $project, 'composer.json');
                $json_data = json_decode($content);
                $projects[$project] = array();
                foreach ($json_data->require as $component => $version) {
                    if ('php' != $component) {
                        $projects[$project][] = $component;
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