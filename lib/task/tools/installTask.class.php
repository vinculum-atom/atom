<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

class installTask extends sfBaseTask
{
    protected $configFiles;

    /**
     * @see sfTask
     *
     * @param mixed $arguments
     * @param mixed $options
     */
    public function execute($arguments = [], $options = [])
    {
        /*
        $this->logSection('install', 'Checking requirements');

        $apcuLoaded = extension_loaded('apcu');
        $memcacheLoaded = extension_loaded('memcache');

        if (!$apcuLoaded && !$memcacheLoaded) {
            $this->logBlock(
                [
                    '',
                    'One of the following PHP extensions is required:',
                    '',
                    'php-apcu',
                    'php-memcache',
                    '',
                ],
                'ERROR'
            );

            exit(2);
        }
        */

        $this->logSection('install', 'Checking configuration files');

        $rootDir = sfConfig::get('sf_root_dir');
        $configFiles = [
            $rootDir.'/apps/qubit/config/settings.yml',
            $rootDir.'/config/config.php',
            $rootDir.'/config/databases.yml',
            $rootDir.'/config/propel.ini',
            $rootDir.'/config/search.yml',
        ];

        $existingConfigFiles = [];
        foreach ($configFiles as $file) {
            if (file_exists($file)) {
                $existingConfigFiles[] = $file;
            }
        }

        if (count($existingConfigFiles) > 0) {
            if (
                !$options['no-confirmation']
                && !$this->askConfirmation(array_merge(
                    [
                        'WARNING: The following configuration files already',
                        '         exist and will be overwritten!',
                        '',
                    ],
                    $existingConfigFiles,
                    ['', 'Would you like to continue? (y/N)']),
                    'QUESTION_LARGE',
                    false
                )
            ) {
                $this->logSection('install', 'Installation aborted');

                exit(1);
            }

            $this->logSection('install', 'Deleting configuration files');

            $deletionFailures = [];
            foreach ($configFiles as $file) {
                if (file_exists($file) && !unlink($file)) {
                    $deletionFailures[] = $file;
                }
            }

            if (count($deletionFailures) > 0) {
                $this->logBlock(array_merge(
                    [
                        '',
                        "The following configuration files can't be deleted:",
                        '',
                    ],
                    $deletionFailures,
                    ['']
                ), 'ERROR');

                exit(2);
            }
        }

        $this->logSection('install', 'Configure database');

        $databaseOptions = [
            'databaseHost' => $this->getOptionValue(
                'database-host',
                $options,
                'Database host',
                'localhost'
            ),
            'databasePort' => $this->getOptionValue(
                'database-port',
                $options,
                'Database port',
                '3306'
            ),
            'databaseName' => $this->getOptionValue(
                'database-name',
                $options,
                'Database name',
                'atom'
            ),
            'databaseUsername' => $this->getOptionValue(
                'database-user',
                $options,
                'Database user',
                'atom'
            ),
            'databasePassword' => $this->getOptionValue(
                'database-password',
                $options,
                'Database password'
            ),
        ];

        $this->logSection('install', 'Configure search');

        $searchOptions = [
            'searchHost' => $this->getOptionValue(
                'search-host',
                $options,
                'Search host',
                'localhost'
            ),
            'searchPort' => $this->getOptionValue(
                'search-port',
                $options,
                'Search port',
                '9200'
            ),
            'searchIndex' => $this->getOptionValue(
                'search-index',
                $options,
                'Search index',
                'atom'
            ),
        ];

        /*
        $cacheOptions = [];
        if ($apcuLoaded && $memcacheLoaded) {
            $this->logSection('install', 'Configure cache');

            $cacheOptions['engine'] = $this->getOptionValue(
                'cache-engine',
                $options,
                'Cache engine (apcu or memcache)',
                'apcu'
            );
        } elseif ($memcacheLoaded) {
            $this->logSection('install', 'Using memcache as cache engine');
            $cacheOptions['engine'] = 'memcache';
        } else {
            $this->logSection('install', 'Using apcu as cache engine');
            $cacheOptions['engine'] = 'apcu';
        }

        if ('memcache' === $cacheOptions['engine']) {
            $cacheOptions['host'] = $this->getOptionValue(
                'memcached-host',
                $options,
                'Memcached host',
                'localhost'
            );
            $cacheOptions['port'] = $this->getOptionValue(
                'memcached-port',
                $options,
                'Memcached port',
                '11211'
            );
        }
        */

        if ($options['demo']) {
            $this->logSection('install', 'Setting demo options');

            $siteOptions = [
                'siteTitle' => 'Demo site',
                'siteDescription' => 'Demo site',
                'siteBaseUrl' => 'http://127.0.0.1',
            ];
            $adminOptions = [
                'email' => 'demo@example.com',
                'username' => 'demo',
                'password' => 'demo',
            ];
        } else {
            $this->logSection('install', 'Configure site');

            $siteOptions = [
                'siteTitle' => $this->getOptionValue(
                    'site-title',
                    $options,
                    'Site title',
                    'AtoM'
                ),
                'siteDescription' => $this->getOptionValue(
                    'site-description',
                    $options,
                    'Site description',
                    'Access to Memory'
                ),
                'siteBaseUrl' => $this->getOptionValue(
                    'site-base-url',
                    $options,
                    'Site base URL',
                    'http://127.0.0.1'
                ),
            ];

            $this->logSection('install', 'Configure admin user');

            $adminOptions = [
                'email' => $this->getOptionValue(
                    'admin-email',
                    $options,
                    'Admin email'
                ),
                'username' => $this->getOptionValue(
                    'admin-username',
                    $options,
                    'Admin username',
                ),
                'password' => $this->getOptionValue(
                    'admin-password',
                    $options,
                    'Admin password',
                ),
            ];
        }

        // TODO:
        // - Configure cache?
        // - Configure Gearman server?
        // - Other config settings:
        //   - csrf_secret
        //   - default_culture (maybe after data load)
        //   - default_timezone
        //   - others from app.yml

        $this->logSection('install', 'Confirm configuration');

        echo "Database host       {$databaseOptions['databaseHost']}\n";
        echo "Database port       {$databaseOptions['databasePort']}\n";
        echo "Database name       {$databaseOptions['databaseName']}\n";
        echo "Database user       {$databaseOptions['databaseUsername']}\n";
        echo "Database password   {$databaseOptions['databasePassword']}\n";
        echo "Search host         {$searchOptions['searchHost']}\n";
        echo "Search port         {$searchOptions['searchPort']}\n";
        echo "Search index        {$searchOptions['searchIndex']}\n";
        echo "Site title          {$siteOptions['siteTitle']}\n";
        echo "Site description    {$siteOptions['siteDescription']}\n";
        echo "Site base URL       {$siteOptions['siteBaseUrl']}\n";
        echo "Admin email         {$adminOptions['email']}\n";
        echo "Admin username      {$adminOptions['username']}\n";
        echo "Admin password      {$adminOptions['password']}\n";

        if (
            !$options['no-confirmation']
            && !$this->askConfirmation(
                ['Confirm configuration and continue? (y/N)'],
                'QUESTION_LARGE',
                false
            )
        ) {
            $this->logSection('install', 'Installation aborted');

            exit(1);
        }

        $this->logSection('install', 'Setting configuration');

        try {
            arInstall::createDirectories();
            arInstall::checkWritablePaths();
            arInstall::createDatabasesYml();
            arInstall::createPropelIni();
            arInstall::createSettingsYml();
            arInstall::createSfSymlink();
            arInstall::configureDatabase($databaseOptions);
            arInstall::configureSearch($searchOptions);
        } catch (Exception $e) {
            $this->logBlock(
                [
                    '',
                    $e->getMessage(),
                    '',
                ],
                'ERROR'
            );

            exit(2);
        }

        /*
        // Configure cache (no change required for apcu)
        if ('memcache' === $cacheOptions['engine']) {
            $configFile = sfConfig::get('sf_app_config_dir').'/factories.yml';
            $config = [];
            if (!file_put_contents($configFile, sfYaml::dump($config, 9))) {
                $this->logBlock(
                    [
                        '',
                        "Can't write configuration file {$configFile}",
                        '',
                    ],
                    'ERROR'
                );

                exit(2);
            }
        }
        */

        // Reload configuration
        $cacheClear = new sfCacheClearTask(
            $this->dispatcher,
            $this->formatter
        );
        $cacheClear->run();
        Propel::configure($rootDir.'/config/config.php');
        Propel::setDefaultDB('propel');
        $this->configuration = ProjectConfiguration::getApplicationConfiguration(
            'qubit',
            'cli',
            false
        );
        $this->context = sfContext::createInstance($this->configuration);
        $this->context->databaseManager->loadConfiguration();
        arElasticSearchPluginConfiguration::reloadConfig(
            $this->context->getConfiguration()
        );

        try {
            $this->context->getDatabaseConnection('propel');
        } catch (Exception $e) {
            $this->logBlock(
                [
                    '',
                    'Database connection failure:',
                    '',
                    $e->getMessage(),
                    '',
                ],
                'ERROR'
            );

            exit(2);
        }

        try {
            arInstall::checkSearchConnection($searchOptions);
        } catch (Exception $e) {
            $this->logBlock(
                [
                    '',
                    'Elasticsearch connection failure:',
                    '',
                    $e->getMessage(),
                    '',
                ],
                'ERROR'
            );

            exit(2);
        }

        $this->logSection('install', 'Initializing database');

        $insertSql = new sfPropelInsertSqlTask(
            $this->dispatcher,
            $this->formatter
        );
        $ret = $insertSql->run(
            [],
            ['no-confirmation' => $options['no-confirmation']]
        );

        // Stop when the insert SQL task is aborted
        if ($ret) {
            $this->logSection('install', 'Installation aborted');

            exit(1);
        }

        arInstall::modifySql();

        arInstall::loadData($this->dispatcher, $this->formatter);

        $this->logSection('install', 'Creating search index');

        arInstall::populateSearchIndex();

        $this->logSection('install', 'Adding site configuration');

        foreach ($siteOptions as $name => $value) {
            $setting = new QubitSetting();
            $setting->name = $name;
            $setting->value = $value;
            $setting->save();
        }

        $this->logSection('install', 'Creating admin user');

        addSuperuserTask::addSuperUser(
            $adminOptions['username'],
            $adminOptions
        );

        /*
        $this->logSection('install', 'Making themes CSS');

        $output = [];
        exec(
            "make -C {$rootDir}/plugins/arDominionPlugin 2>&1",
            $output,
            $exitCode
        );

        if ($exitCode > 0) {
            $this->logBlock(array_merge(
                [
                    '',
                    'Error making arDominionPlugin theme CSS:',
                    '',
                ],
                $output,
                ['']
            ), 'ERROR');

            exit(2);
        }

        $output = [];
        exec(
            "make -C {$rootDir}/plugins/arArchivesCanadaPlugin 2>&1",
            $output,
            $exitCode
        );

        if ($exitCode > 0) {
            $this->logBlock(array_merge(
                [
                    '',
                    'Error making arArchivesCanadaPlugin theme CSS:',
                    '',
                ],
                $output,
                ['']
            ), 'ERROR');

            exit(2);
        }*/

        $this->logSection('install', 'Installation completed');
    }

    /**
     * @see sfTask
     */
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption(
                'application',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'The application name',
                'qubit'
            ),
            new sfCommandOption(
                'env',
                null,
                sfCommandOption::PARAMETER_REQUIRED,
                'The environment',
                'cli'
            ),
            new sfCommandOption(
                'connection',
                null,
                sfCommandOption::PARAMETER_REQUIRED,
                'The connection name',
                'propel'
            ),
            new sfCommandOption(
                'database-host',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Database host'
            ),
            new sfCommandOption(
                'database-port',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Database port'
            ),
            new sfCommandOption(
                'database-name',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Database name'
            ),
            new sfCommandOption(
                'database-user',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Database user'
            ),
            new sfCommandOption(
                'database-password',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Database password'
            ),
            new sfCommandOption(
                'search-host',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Search host'
            ),
            new sfCommandOption(
                'search-port',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Search port'
            ),
            new sfCommandOption(
                'search-index',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Search index'
            ),
            /*
            new sfCommandOption(
                'cache-engine',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Cache engine (apcu or memcache)'
            ),
            new sfCommandOption(
                'memcached-host',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Memcached host'
            ),
            new sfCommandOption(
                'memcached-port',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Memcached port'
            ),
            */
            new sfCommandOption(
                'site-title',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Site title'
            ),
            new sfCommandOption(
                'site-description',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Site description'
            ),
            new sfCommandOption(
                'site-base-url',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Site base URL'
            ),
            new sfCommandOption(
                'admin-email',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Admin email'
            ),
            new sfCommandOption(
                'admin-username',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Admin username'
            ),
            new sfCommandOption(
                'admin-password',
                null,
                sfCommandOption::PARAMETER_OPTIONAL,
                'Admin password'
            ),
            new sfCommandOption(
                'demo',
                null,
                sfCommandOption::PARAMETER_NONE,
                'Use default demo values'
            ),
            new sfCommandOption(
                'no-confirmation',
                null,
                sfCommandOption::PARAMETER_NONE,
                'Do not ask for confirmation'
            ),
        ]);

        $this->namespace = 'tools';
        $this->name = 'install';
        $this->briefDescription = 'Install AtoM.';
        $this->detailedDescription = 'TODO';
    }

    private function getOptionValue($name, $options, $prompt, $default = null)
    {
        if ($options[$name]) {
            return $options[$name];
        }

        if ($default) {
            $prompt .= " [{$default}]";
        }

        $value = readline($prompt.': ');
        $value = $value ? trim($value) : $default;

        if (!$value) {
            throw new Exception("{$prompt} is required.");
        }

        return $value;
    }
}
