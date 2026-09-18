<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    exit("Acceso denegado. Por favor, inicie sesión.");
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();

$rol = $_SESSION['rol'];
$id_area_usuario = $_SESSION['id_area'];
$id_doc = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener la información completa del documento y el área asignada
$sql = "SELECT d.*, a.nombre_area 
        FROM documentos d 
        JOIN areas a ON d.id_area_asignada = a.id_area 
        WHERE d.id_doc = :id_doc";
$stmt = $conn->prepare($sql);
$stmt->execute([':id_doc' => $id_doc]);
$doc = $stmt->fetch();

if (!$doc) {
    exit("El expediente solicitado no existe o fue eliminado.");
}

$puede_editar_acuse = ($rol === 'Coordinación');
$solo_observa = in_array($rol, ['Direccion General']);

// Si este folio se envió al mismo tiempo a varias/todas las JUD, buscamos las demás áreas
// hermanas (mismo num_ingreso + fecha_documento + titulo + capturista) para mostrarlas todas en el acuse.
$stmt_hermanos = $conn->prepare(
    "SELECT d.id_doc, d.id_area_asignada, a.nombre_area
     FROM documentos d
     JOIN areas a ON d.id_area_asignada = a.id_area
     WHERE d.num_ingreso = :num_ingreso
       AND d.fecha_documento = :fecha_documento
       AND d.titulo = :titulo
       AND d.capturista = :capturista
     ORDER BY a.nombre_area ASC"
);
$stmt_hermanos->execute([
    ':num_ingreso'     => $doc['num_ingreso'],
    ':fecha_documento' => $doc['fecha_documento'],
    ':titulo'          => $doc['titulo'],
    ':capturista'      => $doc['capturista'],
]);
$areas_hermanas = $stmt_hermanos->fetchAll();
$es_envio_multiple = count($areas_hermanas) > 1;

// Procesar actualización rápida desde el acuse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_acuse']) && $puede_editar_acuse && !$solo_observa) {
    $operativo = trim($_POST['operativo']);
    $fecha_entrega_operativo = !empty($_POST['fecha_entrega_operativo']) ? $_POST['fecha_entrega_operativo'] : null;
    $num_respuesta = trim($_POST['num_respuesta']);
    $fecha_respuesta = !empty($_POST['fecha_respuesta']) ? $_POST['fecha_respuesta'] : null;
    $enviado_por = trim($_POST['enviado_por']);
    $comentarios_abogado = trim($_POST['comentarios_abogado']);
    
    $estado = !empty($num_respuesta) ? 'Resuelto_Para_Validar' : $doc['estado'];

    $sql_u = "UPDATE documentos SET 
                operativo = :operativo, 
                fecha_entrega_operativo = :fecha_entrega_operativo, 
                num_respuesta = :num_respuesta, 
                fecha_respuesta = :fecha_respuesta, 
                enviado_por = :enviado_por, 
                comentarios_abogado = :comentarios_abogado,
                estado = :estado 
              WHERE id_doc = :id_doc";
    $stmt_u = $conn->prepare($sql_u);
    $stmt_u->execute([
        ':operativo' => $operativo,
        ':fecha_entrega_operativo' => $fecha_entrega_operativo,
        ':num_respuesta' => $num_respuesta,
        ':fecha_respuesta' => $fecha_respuesta,
        ':enviado_por' => $enviado_por,
        ':comentarios_abogado' => $comentarios_abogado,
        ':estado' => $estado,
        ':id_doc' => $id_doc
    ]);

    header("Location: imprimir_acuse.php?id=" . $id_doc);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acuse de Recibo - Folio #<?php echo $doc['id_doc']; ?></title>
    <style>
        @page { 
            size: letter portrait; 
            margin: 8mm; 
        }
        
        * {
            box-sizing: border-box;
        }

        html, body { 
            width: 100%; 
            height: 100%; 
            margin: 0; 
            padding: 0; 
            font-family: Arial, Helvetica, sans-serif; 
            background-color: #fff; 
            color: #000; 
            font-size: 11px; 
        }

        /* Panel superior de acciones en pantalla */
        .panel-acciones-pantalla {
            text-align: center;
            background: #f1f5f9;
            padding: 10px;
            border-bottom: 1px solid #cbd5e1;
        }

        .btn-accion {
            color: #fff; 
            border: none; 
            padding: 8px 18px; 
            font-weight: bold; 
            border-radius: 4px; 
            cursor: pointer; 
            margin: 0 5px; 
            font-size: 12px; 
            text-decoration: none; 
            display: inline-block;
        }

        .btn-print { background-color: #0f172a; }
        .btn-save { background-color: #16a34a; }
        .btn-back { background-color: #475569; }

        /* Estructura dividida en dos bloques */
        .contenedor-formato { 
            width: 100%; 
            max-width: 1000px;
            margin: 0 auto;
            min-height: calc(100vh - 60px); 
            display: flex; 
            border: 2px solid #000; 
            background: #fff;
        }

        .bloque-izquierdo { 
            width: 68%; 
            border-right: 2px dashed #000; 
            padding: 12px; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
        }

        .bloque-derecho { 
            width: 32%; 
            padding: 12px; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
        }

        /* Encabezados y Secciones */
        .header-logo-zona { 
            display: flex; 
            align-items: center; 
            border-bottom: 2px solid #000; 
            padding-bottom: 8px; 
            margin-bottom: 10px; 
        }

        .logo-txt { 
            font-size: 11px; 
            font-weight: bold; 
            line-height: 14px; 
            margin-left: 10px; 
        }

        .titulo-central { 
            text-align: center; 
            font-size: 15px; 
            font-weight: bold; 
            letter-spacing: 1px; 
            margin: 4px 0 8px 0; 
        }

        .tabla-datos { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 8px; 
        }

        .tabla-datos th, .tabla-datos td { 
            border: 1px solid #000; 
            padding: 4px 6px; 
            text-align: left; 
            vertical-align: top; 
        }

        .lbl-seccion { 
            font-size: 8.5px; 
            font-weight: bold; 
            display: block; 
            text-transform: uppercase; 
            color: #222; 
            margin-bottom: 2px; 
        }

        .val-seccion { 
            font-size: 11px; 
            color: #000; 
            font-weight: 600; 
        }

        .caja-texto-encuadrada { 
            border: 1px solid #000; 
            padding: 6px; 
            min-height: 60px; 
            font-size: 10.5px; 
            background: #fff; 
            margin-top: 2px; 
            margin-bottom: 8px; 
            word-wrap: break-word;
        }

        .caja-instruccion { 
            border: 1px solid #000; 
            padding: 6px; 
            min-height: 40px; 
            font-family: monospace; 
            font-size: 10.5px; 
            background: #fdfdfd; 
            margin-bottom: 8px; 
        }

        .subtitulo-bar { 
            font-weight: bold; 
            text-align: center; 
            background: #e6e6e6; 
            border: 1px solid #000; 
            padding: 4px; 
            font-size: 10px; 
            margin-bottom: 6px; 
            text-transform: uppercase; 
        }

        .firma-row { 
            display: flex; 
            justify-content: space-between; 
            margin-top: 15px; 
            font-size: 9px; 
        }

        .firma-linea { 
            border-top: 1px solid #000; 
            width: 45%; 
            text-align: center; 
            padding-top: 4px; 
        }

        .sello-recibido-box { 
            border: 2px dashed #000; 
            height: 120px; 
            text-align: center; 
            font-weight: bold; 
            font-size: 10px; 
            padding-top: 45px; 
            margin-bottom: 15px; 
        }

        .linea-control-derecha { 
            margin-bottom: 10px; 
            border-bottom: 1px dashed #000; 
            padding-bottom: 4px; 
        }

        .caja-blanca-observaciones { 
            border: 1px solid #000; 
            flex-grow: 1; 
            min-height: 120px; 
            margin-top: 6px; 
            margin-bottom: 10px; 
            padding: 4px; 
            background: #fff; 
        }

        .estatus-check-zona { 
            display: flex; 
            justify-content: space-around; 
            font-size: 10px; 
            font-weight: bold; 
            border: 1px solid #000; 
            padding: 6px; 
            background: #f9f9f9; 
        }

        .input-editable-acuse {
            border: none !important; 
            background: transparent !important; 
            width: 100%; 
            font-family: Arial, sans-serif; 
            font-size: 11px; 
            font-weight: 600; 
            padding: 0; 
            margin: 0; 
            outline: none; 
            color: #000;
        }

        .textarea-editable-acuse {
            border: none !important; 
            background: transparent !important; 
            width: 100%; 
            height: 100%; 
            font-family: Arial, sans-serif; 
            font-size: 10.5px; 
            padding: 4px; 
            margin: 0; 
            outline: none; 
            resize: none; 
            color: #000;
        }

        /* Ajustes exclusivos de Impresión */
        @media print {
            .panel-acciones-pantalla { display: none !important; }
            body, html { height: 100%; overflow: hidden; }
            .contenedor-formato { 
                height: 98vh; 
                max-width: 100%;
                border: 2px solid #000; 
            }
            .input-editable-acuse::-webkit-calendar-picker-indicator { display: none !important; -webkit-appearance: none; }
            .input-editable-acuse { -moz-appearance: none; -webkit-appearance: none; appearance: none; }
        }
    </style>
</head>
<body>

<form action="" method="POST" style="margin:0; padding:0; height:100%;">
    <input type="hidden" name="actualizar_acuse" value="1">

    <div class="panel-acciones-pantalla">
        <a href="tablon.php" class="btn-accion btn-back">Volver al Tablón</a>
        <button type="button" class="btn-accion btn-print" onclick="window.print();">Imprimir Acuse</button>
        <?php if ($puede_editar_acuse && !$solo_observa) { ?>
            <button type="submit" class="btn-accion btn-save">Guardar Cambios</button>
        <?php } ?>
    </div>

    <div class="contenedor-formato">
        <!-- SECCIÓN IZQUIERDA: DATOS PRINCIPALES DEL REGISTRO -->
        <div class="bloque-izquierdo">
            <div>
                <div class="header-logo-zona">
                    <img src="rayas.png" alt="Logo" style="height: 32px;">
                    <div class="logo-txt">
                        GOBIERNO DE LA CIUDAD DE MÉXICO<br>
                        DIRECCIÓN GENERAL DE ADMINISTRACIÓN Y FINANZAS (DGAF)
                    </div>
                </div>
                
                <div class="titulo-central">ACUSE DE CONTROL DOCUMENTAL #<?php echo $doc['id_doc']; ?></div>

                <!-- Folios y fechas principales -->
                <table class="tabla-datos">
                    <tr>
                        <td style="width: 10%;">
                            <span class="lbl-seccion">Año</span>
                            <span class="val-seccion"><?php echo !empty($doc['fecha_ingreso']) ? date('Y', strtotime($doc['fecha_ingreso'])) : date('Y'); ?></span>
                        </td>
                        <td style="width: 22%;">
                            <span class="lbl-seccion">Fecha Recepción</span>
                            <span class="val-seccion"><?php echo !empty($doc['fecha_ingreso']) ? date('d/m/Y H:i', strtotime($doc['fecha_ingreso'])) : 'N/A'; ?></span>
                        </td>
                        <td style="width: 22%;">
                            <span class="lbl-seccion">No. Oficio / Ingreso</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['num_ingreso']); ?></span>
                        </td>
                        <td style="width: 23%;">
                            <span class="lbl-seccion">Fecha Documento</span>
                            <span class="val-seccion"><?php echo !empty($doc['fecha_documento']) ? date('d/m/Y', strtotime($doc['fecha_documento'])) : 'S/F'; ?></span>
                        </td>
                        <td style="width: 23%;">
                            <span class="lbl-seccion">Tipo Documento</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['tipo_documento']); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="lbl-seccion">Atención / Prioridad</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['prioridad']); ?></span>
                        </td>
                        <td>
                            <span class="lbl-seccion">Folio CACH</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['trae_cach'] ?: 'S/N'); ?></span>
                        </td>
                        <td>
                            <span class="lbl-seccion">Folio DGAF</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['es_dgaf'] ?: 'S/N'); ?></span>
                        </td>
                        <td>
                            <span class="lbl-seccion">Folio CJSL</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['folio_cjsl'] ?: 'S/N'); ?></span>
                        </td>
                    </tr>
                </table>

                <span class="lbl-seccion">Enviado Por (Autoridad Remitente):</span>
                <div style="font-size: 11px; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 3px;">
                    <?php if ($puede_editar_acuse && !$solo_observa) { ?>
                        <input type="text" name="enviado_por" class="input-editable-acuse" value="<?php echo htmlspecialchars($doc['enviado_por']); ?>" required>
                    <?php } else { ?>
                        <?php echo htmlspecialchars($doc['enviado_por']); ?>
                    <?php } ?>
                </div>

                <span class="lbl-seccion">Asunto / Título Oficial:</span>
                <div class="caja-texto-encuadrada">
                    <?php echo nl2br(htmlspecialchars($doc['titulo'])); ?>
                </div>

                <div class="subtitulo-bar">Información del Turno / Destino</div>

                <table class="tabla-datos">
                    <tr>
                        <td style="width: 50%;">
                            <span class="lbl-seccion">Área / JUD Asignada</span>
                            <span class="val-seccion" style="text-transform: uppercase;"><?php echo htmlspecialchars($doc['nombre_area']); ?></span>
                        </td>
                        <td style="width: 50%;">
                            <span class="lbl-seccion">Fecha Asignación Área</span>
                            <span class="val-seccion"><?php echo !empty($doc['fecha_ingreso']) ? date('d/m/Y H:i', strtotime($doc['fecha_ingreso'])) : 'S/N'; ?></span>
                        </td>
                    </tr>
                    <?php if ($es_envio_multiple) { ?>
                    <tr>
                        <td colspan="2">
                            <span class="lbl-seccion">Con copia a</span>
                            <span class="val-seccion" style="text-transform: uppercase; display:block;">
                                <?php
                                    $nombres_copia = [];
                                    foreach ($areas_hermanas as $ah) {
                                        if ($ah['id_doc'] != $doc['id_doc']) { $nombres_copia[] = htmlspecialchars($ah['nombre_area']); }
                                    }
                                    echo implode(', ', $nombres_copia);
                                ?>
                            </span>
                        </td>
                    </tr>
                    <?php } ?>
                </table>

                <?php if (!empty($doc['instruccion'])) { ?>
                    <span class="lbl-seccion">Instrucción / Observación de Coordinación:</span>
                    <div class="caja-instruccion"><?php echo nl2br(htmlspecialchars($doc['instruccion'])); ?></div>
                <?php } ?>

                <table class="tabla-datos">
                    <tr>
                        <td style="width: 50%;">
                            <span class="lbl-seccion" style="color: #6f1120;">Operativo Asignado:</span>
                            <?php if ($puede_editar_acuse && !$solo_observa) { ?>
                                <select name="operativo" class="input-editable-acuse">
                                    <option value="Jefatura de Unidad Departamental">Jefatura de Unidad Departamental</option>
                                    <?php
                                    $stmt_p = $conn->prepare("SELECT * FROM personal WHERE id_area = :area ORDER BY nombre ASC");
                                    $stmt_p->execute([':area' => $doc['id_area_asignada']]);
                                    foreach ($stmt_p->fetchAll() as $p) {
                                        $selected = ($doc['operativo'] === $p['nombre']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($p['nombre']) . "' $selected>" . htmlspecialchars($p['nombre']) . "</option>";
                                    }
                                    ?>
                                </select>
                            <?php } else { ?>
                                <span class="val-seccion"><?php echo htmlspecialchars($doc['operativo'] ?: 'Por asignar en Jefatura Local'); ?></span>
                            <?php } ?>
                        </td>
                        <td style="width: 50%;">
                            <span class="lbl-seccion">Entrega a Operativo:</span>
                            <?php if ($puede_editar_acuse && !$solo_observa) { ?>
                                <input type="date" name="fecha_entrega_operativo" class="input-editable-acuse" value="<?php echo !empty($doc['fecha_entrega_operativo']) ? date('Y-m-d', strtotime($doc['fecha_entrega_operativo'])) : ''; ?>">
                            <?php } else { ?>
                                <span class="val-seccion"><?php echo !empty($doc['fecha_entrega_operativo']) ? date('d/m/Y', strtotime($doc['fecha_entrega_operativo'])) : 'Pendiente'; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="firma-row">
                <div class="firma-linea">
                    <span class="lbl-seccion">Capturó en Sistema:</span>
                    <strong><?php echo htmlspecialchars($doc['capturista'] ?: 'Sistema'); ?></strong>
                </div>
                <div class="firma-linea">
                    <span class="lbl-seccion">Firma de Recibido (Operativo):</span>
                    <br><span style="font-size: 8px; color: #555;">Nombre, Fecha y Firma</span>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DERECHA: SELLO FÍSICO Y RESOLUCIÓN -->
        <div class="bloque-derecho">
            <div>
                <div class="sello-recibido-box">
                    ESPACIO RESERVADO<br>PARA SELLO FÍSICO<br>DE LA JUD
                </div>

                <div class="subtitulo-bar" style="background:#000; color:#fff;">Datos de Respuesta</div>
                
                <div class="linea-control-derecha" style="margin-top: 6px;">
                    <span class="lbl-seccion">Oficio Resp. / Salida:</span>
                    <?php if ($puede_editar_acuse && !$solo_observa) { ?>
                        <input type="text" name="num_respuesta" class="input-editable-acuse" value="<?php echo htmlspecialchars($doc['num_respuesta']); ?>" placeholder="Escriba folio de respuesta">
                    <?php } else { ?>
                        <span class="val-seccion"><?php echo htmlspecialchars($doc['num_respuesta'] ?: '____________________'); ?></span>
                    <?php } ?>
                </div>
                
                <div class="linea-control-derecha">
                    <span class="lbl-seccion">Fecha de Respuesta:</span>
                    <?php if ($puede_editar_acuse && !$solo_observa) { ?>
                        <input type="date" name="fecha_respuesta" class="input-editable-acuse" value="<?php echo !empty($doc['fecha_respuesta']) ? date('Y-m-d', strtotime($doc['fecha_respuesta'])) : ''; ?>">
                    <?php } else { ?>
                        <span class="val-seccion"><?php echo !empty($doc['fecha_respuesta']) ? date('d/m/Y', strtotime($doc['fecha_respuesta'])) : '____ / ____ / 2026'; ?></span>
                    <?php } ?>
                </div>

                <span class="lbl-seccion" style="margin-top: 6px;">Observaciones Finales / Notas:</span>
            </div>
            
            <div class="caja-blanca-observaciones">
                <?php if ($puede_editar_acuse && !$solo_observa) { ?>
                    <textarea name="comentarios_abogado" class="textarea-editable-acuse" placeholder="Observaciones o síntesis del trámite..."><?php echo htmlspecialchars($doc['comentarios_abogado'] ?? ''); ?></textarea>
                <?php } else { ?>
                    <div style="padding: 4px; font-size: 10.5px;"><?php echo nl2br(htmlspecialchars($doc['comentarios_abogado'] ?? 'Sin observaciones.')); ?></div>
                <?php } ?>
            </div>

            <div class="estatus-check-zona">
                <div>[ <?php echo ($doc['estado'] === 'Pendiente' || $doc['estado'] === 'Rechazado') ? 'X' : ' '; ?> ] EN TRÁMITE</div>
                <div>[ <?php echo ($doc['estado'] === 'Concluido' || $doc['estado'] === 'Resuelto_Para_Validar') ? 'X' : ' '; ?> ] ATENDIDO</div>
            </div>
        </div>
    </div>
</form>

</body>
</html>