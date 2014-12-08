<?php
/**
 * Innomatic
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright  1999-2014 Innomatic Company
 * @license    http://www.innomatic.io/license/ New BSD License
 * @link       http://www.innomatic.io
 */
namespace Innomatic\Application;

/**
 * This class provides some helper methods for handling AppCentral operations
 * like retrieving list of all the available applications, updating
 * applications and so on.
 *
 * @since 6.5.0 introduced
 * @author Alex Pagnoni <alex.pagnoni@innomatic.io>
 */
class AppCentralHelper
{
    /* public updateApplications() {{{ */
    /**
     * Updates all the installed applications fetching new application versions
     * found in AppCentral repositories.
     *
     * @access public
     * @return array List of updated applications with their versions.
     */
    public function updateApplications()
    {
    }
    /* }}} */

    /* public getUpdatedApplications() {{{ */
    /**
     * Gets a list of the installed applications for which a new version is
     * available in AppCentral repositories.
     *
     * This method compares the installed applications with the ones found in
     * AppCentral repositories.
     *
     * @access public
     * @return array List of the available updated applications with their versions.
     */
    public function getUpdatedApplications()
    {
    }
    /* }}} */

    /* public getAvailableApplications() {{{ */
    /**
     * Gets a list of all the available applications in the registered
     * AppCentral repositories.
     *
     * @access public
     * @return array
     */
    public function getAvailableApplications($refresh = false)
    {
        $apps = array();

        // Fetch the list of the registered AppCentral servers.
        $dataAccess = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getDataAccess();

        $serverList = $dataAccess->execute(
            "SELECT id FROM applications_repositories"
        );

        while (!$serverList->eof) {
            $serverId = $serverList->getFields('id');
            $server = new AppCentralRemoteServer($serverId);

            // Fetch the list of the available repositories, refreshing the cache.
            $repositories = $server->listAvailableRepositories($refresh);

            foreach ($repositories as $repoId => $repoData) {
                // Fetch the list of the available repository applications.
                $repoApplications = $server->listAvailableApplications($repoId, $refresh);

                foreach ($repoApplications as $appId => $appData) {
                    // Fetch the list of the available application versions.
                    $versions = $server->listAvailableApplicationVersions(
                        $repoId,
                        $appId,
                        $refresh
                    );

                    // Add the application version to the applications list.
                    foreach ($versions as $version => $versionData) {
                        $apps[$appData['appid']][$version][] = [
                            'server' => $serverId,
                            'repository' => $repoId
                        ];
                    }
                }
            }
            $serverList->moveNext();
        }

        return $apps;
    }
    /* }}} */

    /* public findApplication($application) {{{ */
    /**
     * Checks if the given application is available in the registered
     * AppCentral servers.
     *
     * @param string $application Application name.
     * @access public
     * @return mixed false if the applications has not been found or an array of the servers
     * containing the application.
     */
    public function findApplication($application, $refresh = false)
    {
        $apps = $this->getAvailableApplications($refresh);
        if (!isset($apps[$application])) {
            return false;
        }

        return $apps[$application];
    }
    /* }}} */

    public function resolveDependencies($dependencies)
    {
    }

    public function updateApplicationsList(\Closure $item = null, \Closure $result = null)
    {
        // Fetch the list of the registered AppCentral servers.
        $dataAccess = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getDataAccess();

        $serverList = $dataAccess->execute(
            "SELECT id FROM applications_repositories"
        );

        while (!$serverList->eof) {
            $serverId = $serverList->getFields('id');
            $server = new AppCentralRemoteServer($serverId);

            // Fetch the list of the available repositories, refreshing the cache.
            $repositories = $server->listAvailableRepositories(true);
                        
            foreach ($repositories as $repoId => $repoData) {
                if (is_callable($item)) {
                    $item($serverId, $server->getAccount()->getName(), $repoId, $repoData);
                }
                
                // Fetch the list of the available repository applications.
                $repoApplications = $server->listAvailableApplications($repoId, true);

                foreach ($repoApplications as $appId => $appData) {
                    // Fetch the list of the available application versions.
                    $versions = $server->listAvailableApplicationVersions(
                        $repoId,
                        $appId,
                        true
                    );
                }

                if (is_callable($result)) {
                    $result(true);
                }
            }
            $serverList->moveNext();
        }
    }
}
