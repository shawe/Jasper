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

namespace FacturaScripts\Plugins\Jasper\Lib\Export;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Lib\Export\ExportInterface;
use FacturaScripts\Core\Model;
use FacturaScripts\Core\Model\Base\ModelClass;
use JasperPHP\JasperPHP;
use Symfony\Component\HttpFoundation\Response;

/**
 * JasperReports export data.
 *
 * @package FacturaScripts\Plugins\Jasper\Lib\Export
 *
 * @author  Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
abstract class JasperExport implements ExportInterface
{
    /**
     * Base path where are saved al jrxml and additional files.
     */
    const BASE = \FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . 'Jasper' . DIRECTORY_SEPARATOR;

    /**
     * JasperPHP object.
     *
     * @var JasperPHP
     */
    protected $jasper;

    /**
     * Path where output is saved.
     *
     * @var string
     */
    protected $outputPath;

    /**
     * Contains the translator
     *
     * @var Translator
     */
    private $i18n;

    /**
     * Output file name.
     *
     * @var string
     */
    private $outputFile;

    /**
     * Full path to file.
     *
     * @var string
     */
    private $fullPath;

    /**
     * JasperExport constructor.
     */
    public function __construct()
    {
        require_once \FS_FOLDER . '/Plugins/Jasper/vendor/autoload.php';
        $this->jasper = new JasperPHP(self::BASE);
        $this->outputPath = str_replace('/', \DIRECTORY_SEPARATOR, \FS_FOLDER . '/MyFiles/Cache/Jasper/');
        if (!\is_dir($this->outputPath) && !mkdir($this->outputPath) && !is_dir($this->outputPath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->outputPath));
        }
        $this->i18n = new Translator();
    }

    /**
     * Return the full document.
     *
     * @return string
     */
    public function getDoc(): string
    {
        $file = $this->outputPath . $this->outputFile . '.' . $this->getExportFormat();
        if (\file_exists($file)) {
            $txt = $this->readFileChunked($file);
            \unlink($file);
            return $txt;
        }

        throw new \RuntimeException(
            $this->i18n->trans('file-not-exists', ['%fileName%' => $file])
        );
    }

    /**
     * Blank document.
     */
    public function newDoc()
    {
        // Jasper don't need this, but is required from ExportInterface.
    }

    /**
     * Set headers and output document content to response.
     *
     * @param Response $response
     */
    public function show(Response &$response)
    {
        $response->headers->set('Content-Type', 'application/' . $this->getExportFormat());
        $response->headers->set('Content-Disposition', 'inline; filename="' . $this->outputFile . '.' . $this->getExportFormat() . '"');
        $response->setContent($this->getDoc());
    }

    /**
     * Adds a new page with the model data.
     *
     * @param mixed  $model
     * @param array  $columns
     * @param string $title
     */
    public function generateModelPage($model, $columns, $title = '')
    {
        $fileName = str_replace('FacturaScripts\Dinamic\Model\\', '', \get_class($model));

        if (!\file_exists(self::BASE . $fileName . '.jrxml')) {
            $fileName2 = 'GenericModel';
            if (!\file_exists(self::BASE . $fileName2 . '.jrxml')) {
                throw new \RuntimeException(
                    $this->i18n->trans('generic-model-not-exists', ['%genericModel%' => 'GenericModel.jrxml', '%especificModel%' => $fileName . '.jrxml'])
                );
            }
            $this->compile($model);
            $this->generateGeneric($model, $fileName2, [], [], 0, $columns, $title);
        }

        $this->compile($model);
        $this->generateModel($model, $fileName, $columns, $title);
    }

    /**
     * Adds a new page with a table listing the models data.
     *
     * @param mixed           $model
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param array           $columns
     * @param string          $title
     */
    public function generateListModelPage($model, $where, $order, $offset, $columns, $title = '')
    {
        $fileName = 'List' . str_replace('FacturaScripts\Dinamic\Model\\', '', \get_class($model));

        if (!\file_exists(self::BASE . $fileName . '.jrxml')) {
            $fileName2 = 'ListGeneric';
            if (!\file_exists(self::BASE . $fileName2 . '.jrxml')) {
                throw new \RuntimeException(
                    $this->i18n->trans('generic-model-not-exists', ['%genericModel%' => 'ListGeneric.jrxml', '%especificModel%' => $fileName . '.jrxml'])
                );
            }
            $this->compile($model);
            $this->generateGeneric($model, $fileName2, $where, $order, $offset, $columns, $title);
            return;
        }

        $this->compile($model);
        $this->generateListModel($model, $fileName, $where, $order, $offset, $columns, $title);
    }

    /**
     * Adds a new page with the document data.
     *
     * @param mixed $model
     */
    public function generateDocumentPage($model)
    {
        $fileName = str_replace('FacturaScripts\Dinamic\Model\\', '', \get_class($model));

        switch ($fileName) {
            case 'PresupuestoCliente':
            case 'PedidoCliente':
            case 'AlbaranCliente':
            case 'FacturaCliente':
                $fileName2 = 'SalesDocument';
                break;
            case 'PresupuestoProveedor':
            case 'PedidoProveedor':
            case 'AlbaranProveedor':
            case 'FacturaProveedor':
                $fileName2 = 'PurchaseDocument';
                break;
            default:
                $fileName2 = 'Document';
                break;
        }

        if (!\file_exists(self::BASE . $fileName . '.jrxml')) {
            $this->compile($fileName2);
            $this->generateGeneric($model, $fileName);
            return;
        }
        $this->compile($fileName);
        $this->generate($model, $fileName);
    }

    /**
     * Adds a new page with the table.
     *
     * @param array $headers
     * @param array $rows
     */
    public function generateTablePage($headers, $rows)
    {
        // Jasper don't need this, but is required from ExportInterface.
    }

    /**
     * Read a file chunked.
     * Trying directly readfile, can cause a broken file.
     *
     * @param string    $fileName
     * @param float|int $chunkSize
     *
     * @return bool|string
     */
    protected function readFileChunked($fileName, $chunkSize = 1 * (1024 * 1024))
    {
        $handle = fopen($fileName, 'rb');
        if ($handle === false) {
            return false;
        }

        $txt = '';
        while (!feof($handle)) {
            $buffer = fread($handle, $chunkSize);
            $txt .= $buffer;
        }
        return $txt;
    }

    /**
     * Return the format to generate the export file.
     *
     * @return string
     */
    abstract protected function getExportFormat(): string;

    /**
     * Compilate JRXML file.
     *
     * @param string $fileName
     */
    private function compile($fileName)
    {
        $this->fullPath = self::BASE . $fileName . '.jrxml';

        try {
            $this->jasper->compile($this->fullPath)->execute();
        } catch (\Exception $e) {
            throw new \RuntimeException('<pre>' . \print_r($e->getMessage(), true) . '</pre>');
        }
    }

    /**
     * Generate final file.
     *
     * @param mixed           $model
     * @param string          $fileName
     * @param DataBaseWhere[] $where
     * @param int             $offset
     * @param array           $columns
     * @param string          $title
     */
    private function generate($model, $fileName, array $where = [], $offset = null, array $columns = [], $title = '')
    {
        $params = $this->getParams($fileName, $model, $columns, $where, [], $offset, $title);
        try {
            $result = $this->jasper->process(
                $this->fullPath,
                $this->outputPath . $this->outputFile,
                [[$this->getExportFormat()],],
                $params,
                $this->getDbConnection(),
                true,
                true
            )->execute();

            if (!empty($result)) {
                throw new \RuntimeException(
                    '<pre>' . print_r($params, true) . '</pre>'
                    . '<pre>' . print_r($result, true) . '</pre>'
                );
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(
                '<pre>' . \print_r($e->getMessage(), true) . '</pre>'
            );
        }
    }

    /**
     * Generate final model file.
     *
     * @param mixed  $model
     * @param string $fileName
     * @param array  $columns
     * @param string $title
     */
    private function generateModel($model, $fileName, array $columns = [], $title = '')
    {
        // TODO: Not implemented yet.
    }

    /**
     * Generate final list model file.
     *
     * @param mixed           $model
     * @param string          $fileName
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param array           $columns
     * @param string          $title
     */
    private function generateListModel($model, $fileName, array $where = [], array $order = [], $offset = 0, array $columns = [], $title = '')
    {
        // TODO: Not implemented yet.
    }

    /**
     * Generate final generic file.
     *
     * @param mixed           $model
     * @param string          $fileName
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param array           $columns
     * @param string          $title
     */
    private function generateGeneric($model, $fileName, array $where = [], array $order = [], $offset = null, array $columns = [], $title = '')
    {
        $params = $this->getParams($fileName, $model, $columns, $where, $order, $offset, $title);
        try {
            $result = $this->jasper->process(
                $this->fullPath,
                $this->outputPath . $this->outputFile,
                [$this->getExportFormat()],
                $params,
                $this->getDbConnection(),
                true,
                true
            )->execute();

            if (!empty($result)) {
                throw new \RuntimeException(
                    '<pre>' . print_r($params, true) . '</pre>'
                    . '<pre>' . print_r($result, true) . '</pre>'
                );
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(
                '<pre>' . print_r($e->getMessage(), true) . '</pre>'
            );
        }
    }

    /**
     * Return params needed by jasper reports.
     *
     * @param string          $fileName
     * @param ModelClass      $model
     * @param array           $columns
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param string          $title
     *
     * @return array
     */
    private function getParams($fileName, $model, array $columns = [], array $where = [], array $order = [], $offset = null, $title = ''): array
    {
        $fileName2 = null;
        $params = [];

        $tableName = $model::tableName();
        $primaryKey = $model::primaryColumn();
        $identifier = $model->{$primaryKey};

        $params['primaryName'] = '"' . $primaryKey . '"';
        $params['primaryKey'] = $identifier;

        if (empty($columns)) {
            foreach ((array) $model->getModelFields() as $field) {
                $columns[] = $field['name'];
            }
        }

        if (!empty($where)) {
            $params['where'] = '';
            foreach ($where as $itemWhere) {
                $params['where'] .= $itemWhere->getSQLWhereItem();
            }
        }
        if (!empty($order)) {
            $params['order'] = '';
            $sep = '';
            foreach ($order as $key => $value) {
                $params['order'] .= $sep . $key . ' ' . $value;
                $sep = ' ';
            }
        }
        if ($offset !== null) {
            $params['offset'] = $offset;
        }

        $params['docSQL'] = $columns;

        switch ($fileName) {
            case 'PresupuestoCliente':
                $params['docName'] = $params['docName'] ?? '"Presupuesto"';
                $lines = new Model\LineaPresupuestoCliente();
            // no break
            case 'PedidoCliente':
                /** @noinspection SuspiciousAssignmentsInspection */
                $params['docName'] = $params['docName'] ?? '"Pedido"';
                $lines = $lines ?? new Model\LineaPedidoCliente();
            // no break
            case 'AlbaranCliente':
                /** @noinspection SuspiciousAssignmentsInspection */
                $params['docName'] = $params['docName'] ?? '"Albarán"';
                $lines = $lines ?? new Model\LineaAlbaranCliente();
            // no break
            case 'FacturaCliente':
                /** @noinspection SuspiciousAssignmentsInspection */
                $params['docName'] = $params['docName'] ?? '"Factura"';
                $lines = $lines ?? new Model\LineaFacturaCliente();

                $linesColumns = [];
                foreach ((array) $lines->getModelFields() as $field) {
                    $linesColumns[] = $field['name'];
                }
                $fileName2 = 'SalesDocument';
                $params['tableName'] = '"' . $tableName . '"';
                $params['linesTableName'] = '"lineas' . $tableName . '"';
                $params['linesDocSQL'] = $linesColumns;
                $this->fixCustomerCols($params, $fileName);
                break;
            case 'PresupuestoProveedor':
                $params['docName'] = $params['docName'] ?? '"Presupuesto de compra"';
                $lines = $lines ?? new Model\LineaPresupuestoProveedor();
            // no break
            case 'PedidoProveedor':
                /** @noinspection SuspiciousAssignmentsInspection */
                $params['docName'] = $params['docName'] ?? '"Pedido de compra"';
                $lines = $lines ?? new Model\LineaPedidoProveedor();
            // no break
            case 'AlbaranProveedor':
                /** @noinspection SuspiciousAssignmentsInspection */
                $params['docName'] = $params['docName'] ?? '"Albarán de compra"';
                $lines = $lines ?? new Model\LineaAlbaranProveedor();
            // no break
            case 'FacturaProveedor':
                /** @noinspection SuspiciousAssignmentsInspection */
                $params['docName'] = $params['docName'] ?? '"Factura de compra"';
                $lines = $lines ?? new Model\LineaFacturaProveedor();
                $fileName2 = 'PurchaseDocument';
                $linesColumns = [];
                foreach ((array) $lines->getModelFields() as $field) {
                    $linesColumns[] = $field['name'];
                }
                $params['tableName'] = '"' . $tableName . '"';
                $params['linesTableName'] = '"lineas' . $tableName . '"';
                $params['linesDocSQL'] = $linesColumns;
                $this->fixProviderCols($params, $fileName);
                break;
        }

        $this->fullPath = self::BASE . $fileName2 ?? $fileName . '.jasper';
        $this->outputFile = $fileName . '_' . $identifier;

        return $params;
    }

    /**
     * Unset not common customer cols.
     *
     * @param array  $params
     * @param string $fileName
     */
    private function fixCustomerCols(array &$params, $fileName)
    {
        $unsetItems = [
            'codigorect', 'vencimiento', 'fechasalida', 'finoferta', 'idasiento', 'idasientop', 'pagada', 'anulada', 'actualizastock', 'porcomision'
        ];
        foreach ($unsetItems as $item) {
            if (($key = array_search($item, $params['docSQL'], false)) !== false) {
                unset($params['docSQL'][$key]);
            }
            if (($key = array_search($item, $params['linesDocSQL'], false)) !== false) {
                unset($params['linesDocSQL'][$key]);
            }
        }
        $this->fixCommonCols($params, $fileName);
    }

    /**
     * Unset not common provider cols.
     *
     * @param array  $params
     * @param string $fileName
     */
    private function fixProviderCols(array &$params, $fileName)
    {
        $unsetItems = ['codigorect', 'idasiento', 'idasientop', 'pagada', 'anulada'];
        foreach ($unsetItems as $item) {
            if (($key = array_search($item, $params, false)) !== false) {
                unset($params[$key]);
            }
        }
        $this->fixCommonCols($params, $fileName);
    }

    /**
     * Unset common provider cols.
     *
     * @param array  $params
     * @param string $fileName
     */
    private function fixCommonCols(array &$params, $fileName)
    {
        $fixItems = [];
        switch ($fileName) {
            case 'PresupuestoCliente':
            case 'PresupuestoProveedor':
                $fixItems['idpresupuesto'] = 'idpresupuesto AS iddoc';
                $fixItems['idpedido'] = 'idpedido AS iddocnext';
                $params['linesDocSQL'][] = '"NULL AS iddocprev"';
                $params['linesDocSQL'][] = '"NULL AS idlineadocprev"';
                break;
            case 'PedidoCliente':
            case 'PedidoProveedor':
                $fixItems['idpedido'] = 'idpedido AS iddoc';
                $fixItems['idalbaran'] = 'idalbaran AS iddocnext';
                $fixItems['idpresupuesto'] = 'idpresupuesto AS iddocprev';
                $fixItems['idlineapresupuesto'] = 'idlineapresupuesto AS idlineadocprev';
                $params['linesDocSQL'][] = '"NULL AS iddocprev"';
                $params['linesDocSQL'][] = '"NULL AS idlineadocprev"';
                break;
            case 'AlbaranCliente':
            case 'AlbaranProveedor':
                $fixItems['idalbaran'] = 'idalbaran AS iddoc';
                $fixItems['idfactura'] = 'idfactura AS iddocnext';
                $fixItems['idpedido'] = 'idpedido AS iddocprev';
                $fixItems['idlineapedido'] = 'idlineapedido AS idlineadocprev';
                break;
            case 'FacturaCliente':
            case 'FacturaProveedor':
                $fixItems['idfactura'] = 'idfactura AS iddoc';
                $fixItems['idfacturarect'] = 'idfacturarect AS iddocnext';
                $fixItems['idlineaalbaran'] = 'idlineaalbaran AS idlineadocprev';
                break;
        }

        // Rename the field to unify it in the jasper reports
        foreach ($fixItems as $oldItem => $newItem) {
            if (($key = array_search($oldItem, $params['docSQL'], false)) !== false) {
                unset($params['docSQL'][$key]);
                $params['docSQL'][] = $newItem;
            }
            if (($key = array_search($oldItem, $params['linesDocSQL'], false)) !== false) {
                unset($params['linesDocSQL'][$key]);
                $params['linesDocSQL'][] = $newItem;
            }
        }

        $params['docSQL'] = '"' . \implode(', ', $params['docSQL']) . '"';
        $params['linesDocSQL'] = '"' . \implode(', ', $params['linesDocSQL']) . '"';
    }

    /**
     * Return database connection values.
     *
     * @return array
     */
    private function getDbConnection(): array
    {
        return [
            'driver' => \FS_DB_TYPE === 'postgresql' ? 'postgres' : 'mysql',
            'host' => \FS_DB_HOST,
            'port' => \FS_DB_PORT,
            'database' => \FS_DB_NAME,
            'username' => \FS_DB_USER,
            'password' => \FS_DB_PASS,
        ];
    }
}
