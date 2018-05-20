# Jasper para FacturaScripts 2018
Añade soporte para utilizar plantillas de JasperReports desde FacturaScripts 2018 para así poder generar archivos *CSV, DOCX, JRPRINT, ODS, ODT, PDF, PPTX, RTF, XLS, XLSX y XML* a partir de archivos *.jrxml*.

Por ahora sólo funciona para la impresión de documentos concretos desde:
- Compras
  - Presupuestos: *EditPresupuestoCliente*
  - Pedidos: *EditPedidoCliente*
  - Albaranes: *EditAlbaranCliente*
  - Facturas: *EditFacturaCliente*
- Ventas
  - Presupuestos: *EditPresupuestoProveedor*
  - Pedidos: *EditPedidoProveedor*
  - Albaranes: *EditAlbaranProveedor*
  - Facturas: *EditFacturaProveedor*

## Atención
**Este plugin esta incompleto**. Cuando una plantilla no esta disponible, lanza una excepción que FacturaScripts captura en la página "Error de controlador" indicando los detalles en la barra de depuración.

- **GenericModel**: *Pendiente de implementar*. Para imprimir cualquier modelo de forma genérica, siguiendo el estilo de serie de FacturaScripts.
- **ListGeneric**: *Pendiente de implementar*. Para imprimir cualquier listado de un modelo de forma genérica, siguiendo el estilo de serie de FacturaScripts.

## Instalación
La clase ExportManager por ahora no soporta añadir nuevos tipos de forma dinámica y es necesario reemplazar este archivo.

Se encuentra en *Plugins/Jasper/Lib/ExportManager.php* y debe copiarse a *Core/Lib/ExportManager.php*


## Feedback
Si quieres ayudar con el desarrollo del plugin y/o plantillas, puedes mandar un Pull Request con los cambios.

Si no eres programador y quieres enviar alguna plantilla, puedes hacerlo en [*DefaultReports/Community*](https://github.com/shawe/Jasper/upload/master/DefaultReports/Community), sólo necesitas tener cuenta en github y podrás adjuntar archivos para que sean revisados.