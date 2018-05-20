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

/**
 * JasperReports export data to JRPRINT.
 *
 * @package FacturaScripts\Plugins\Jasper\Lib\Export
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class JasperJRPRINTExport extends JasperExport
{
    /**
     * Return the format to generate the export file.
     *
     * @return string
     */
    protected function getExportFormat(): string
    {
        return 'jrprint';
    }
}
