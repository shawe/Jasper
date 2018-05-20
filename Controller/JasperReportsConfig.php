<?php
/**
 * This file is part of Jasper
 * Copyright (C) 2018 Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Jasper\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\FileManager;
use FacturaScripts\Core\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JasperReportsConfig
 *
 * @package FacturaScripts\Plugins\Jasper\Controller
 *
 * @author  Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class JasperReportsConfig extends Controller
{
    const BASE = \FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . 'Jasper' . DIRECTORY_SEPARATOR;
    public $reports;

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'jasperreports';
        $pageData['icon'] = 'fa-file';
        $pageData['menu'] = 'admin';
        $pageData['submenu'] = 'control-panel';

        return $pageData;
    }


    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param Model\User            $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    /**
     * Initialize basic options.
     */
    public function init()
    {
        if (!\is_dir(self::BASE)) {
            $path = str_replace('/', \DIRECTORY_SEPARATOR, \FS_FOLDER . '/Plugins/Jasper/DefaultReports');
            $this->miniLog->info($this->i18n->trans('default-reports-added'));
            FileManager::recurseCopy($path, self::BASE);
        }

        $this->reports = FileManager::scanFolder(self::BASE);
    }

    /**
     * Return the FA icon OS that was executing PHP.
     *
     * @return string
     */
    public function getIconOS()
    {
        switch (true) {
            case stristr(PHP_OS, 'DAR'):
                return 'apple';
            case stristr(PHP_OS, 'WIN'):
                return 'windows';
            case stristr(PHP_OS, 'LINUX'):
                return 'linux';
            default:
                return 'question';
        }
    }

    /**
     * Return binary path for platform.
     *
     * @return string
     */
    public function getBinaryPath(): string
    {
        switch (true) {
            case stristr(PHP_OS, 'WIN'):
                $filename = '/Plugins/Jasper/vendor/cossou/jasperphp/src/JasperStarter/bin/jasperstarter.exe';
                break;
            case stristr(PHP_OS, 'DAR'):
            case stristr(PHP_OS, 'LINUX'):
                $filename = '/Plugins/Jasper/vendor/cossou/jasperphp/src/JasperStarter/bin/jasperstarter';
                break;
            default:
                $filename = '';
        }
        return \FS_FOLDER . $filename;
    }

    /**
     * Return if binary file is executable.
     *
     * @return bool
     */
    public function isExecutable(): bool
    {
        return \is_executable(\str_replace('/', DIRECTORY_SEPARATOR, $this->getBinaryPath()));
    }

    /**
     * Return if JAVA is installed.
     *
     * @return int
     */
    public function isJavaInstalled(): int
    {
        if (!\function_exists('exec')) {
            return -1;
        }

        $result = exec('java -version > NUL && echo yes || echo no');
        return $result === 'yes' ? 1 : 0;
    }

    /**
     * Return the max file size that can be uploaded.
     *
     * @return int
     */
    public function getMaxFileUpload(): int
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }

    /**
     * Execute main actions.
     *
     * @param string $action
     */
    private function execAction($action)
    {
        switch ($action) {
            case 'upload':
                $this->uploadedFiles();
                break;
            case 'delete':
                $file = $this->request->query->get('file');
                if ($file !== null && \file_exists(self::BASE . $file)) {
                    if (\unlink(self::BASE . $file)) {
                        $this->miniLog->info(
                            $this->i18n->trans(
                                'file-deleted',
                                ['%fileName%' => $file]
                            )
                        );
                    } else {
                        $this->miniLog->error(
                            $this->i18n->trans(
                                'can-not-deleted-file',
                                ['%fileName%' => $file]
                            )
                        );
                    }
                }
                break;
        }
        $this->init();
    }

    /**
     * Upload and save a file.
     */
    private function uploadedFiles()
    {
        foreach ($this->request->files->all() as $key => $uploadFile) {
            if ($uploadFile instanceof UploadedFile) {
                if (!$uploadFile->isValid()) {
                    $this->miniLog->error($uploadFile->getErrorMessage());
                    continue;
                }

                /// exclude php files
                if (\in_array($uploadFile->getClientMimeType(), ['application/x-php', 'text/x-php'])) {
                    $this->miniLog->error($this->i18n->trans('php-files-blocked'));
                    continue;
                }

                if ($uploadFile->move(self::BASE, $uploadFile->getClientOriginalName())) {
                    $this->miniLog->info('file-stored', ['%fileName%' => $uploadFile->getClientOriginalName()]);
                }
            }
        }
    }
}
