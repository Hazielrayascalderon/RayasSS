<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    exit("Acceso denegado");
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();

$rol = $_SESSION['rol'];
$id_area_usuario = $_SESSION['id_area'];

$id_doc = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT d.*, a.nombre_area FROM documentos d JOIN areas a ON d.id_area_asignada = a.id_area WHERE d.id_doc = :id_doc";
$stmt = $conn->prepare($sql);
$stmt->execute([':id_doc' => $id_doc]);
$doc = $stmt->fetch();

if (!$doc) {
    exit("El expediente solicitado no existe.");
}

$puede_editar_acuse = ($rol === 'Administrador' || $rol === 'Coordinación' || ($rol === 'JUD' && $doc['id_area_asignada'] == $id_area_usuario));
$solo_observa = in_array($rol, ['Direccion General', 'Secretaria']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_acuse']) && $puede_editar_acuse && !$solo_observa) {
    $operativo = trim($_POST['operativo']);
    $fecha_entrega_operativo = !empty($_POST['fecha_entrega_operativo']) ? $_POST['fecha_entrega_operativo'] : null;
    $num_respuesta = trim($_POST['num_respuesta']);
    $fecha_respuesta = !empty($_POST['fecha_respuesta']) ? $_POST['fecha_respuesta'] : null;
    $enviado_por = trim($_POST['enviado_por']);
    $comentarios_abogado = trim($_POST['comentarios_abogado']);
    
    $estado = !empty($num_respuesta) ? 'Resuelto_Para_Validar' : 'Pendiente';

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
    <title>Acuse de Capital Humano - #<?php echo $doc['id_doc']; ?></title>
    <style>
        @page { size: letter portrait; margin: 8mm; }
        html, body { width: 100%; height: 100%; margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #fff; color: #000; font-size: 11px; box-sizing: border-box; }
        
        .panel-acciones-pantalla {
            text-align: center;
            background: #f1f5f9;
            padding: 10px;
            border-bottom: 1px solid #cbd5e1;
        }
        .btn-accion {
            color: #fff; border: none; padding: 8px 18px; font-weight: bold; border-radius: 4px; cursor: pointer; margin: 0 5px; font-size: 12px; text-decoration: none; display: inline-block;
        }
        .btn-print { background-color: #0f172a; }
        .btn-save { background-color: #16a34a; }
        .btn-back { background-color: #475569; }

        .contenedor-formato { width: 100%; height: calc(100vh - 16mm); display: flex; border: 2px solid #000; box-sizing: border-box; page-break-inside: avoid; }
        .bloque-izquierdo { width: 68%; border-right: 2px dashed #000; padding: 12px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; }
        .bloque-derecho { width: 32%; padding: 12px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; }
        .header-logo-zona { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 10px; }
        .logo-txt { font-size: 11.5px; font-weight: bold; line-height: 15px; margin-left: 12px; }
        .titulo-central { text-align: center; font-size: 16px; font-weight: bold; letter-spacing: 2px; margin: 5px 0 10px 0; }
        .tabla-datos { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .tabla-datos th, .tabla-datos td { border: 1px solid #000; padding: 5px 6px; text-align: left; vertical-align: top; }
        .lbl-seccion { font-size: 8.5px; font-weight: bold; display: block; text-transform: uppercase; color: #111; margin-bottom: 2px; }
        .val-seccion { font-size: 11px; color: #000; font-weight: 600; }
        .caja-texto-encuadrada { border: 1px solid #000; padding: 8px; flex-grow: 1; min-height: 80px; font-size: 11px; background: #fff; margin-top: 2px; margin-bottom: 10px; box-sizing: border-box; }
        .caja-instruccion { border: 1px solid #000; padding: 8px; min-height: 45px; font-family: monospace; font-size: 11px; background: #fdfdfd; margin-bottom: 10px; }
        .subtitulo-bar { font-weight: bold; text-align: center; background: #e6e6e6; border: 1px solid #000; padding: 5px; font-size: 10.5px; margin-bottom: 8px; text-transform: uppercase; }
        .firma-row { display: flex; justify-content: space-between; margin-top: 20px; font-size: 9.5px; }
        .firma-linea { border-top: 1px solid #000; width: 45%; text-align: center; padding-top: 5px; }
        .sello-recibido-box { border: 2.5px dashed #000; height: 140px; text-align: center; font-weight: bold; font-size: 11px; padding-top: 55px; box-sizing: border-box; margin-bottom: 20px; }
        .linea-control-derecha { margin-bottom: 15px; border-bottom: 1px dashed #000; padding-bottom: 6px; }
        .caja-blanca-observaciones { border: 1px solid #000; flex-grow: 1; min-height: 200px; margin-top: 8px; margin-bottom: 15px; padding: 0; box-sizing: border-box; background: #fff; }
        .estatus-check-zona { display: flex; justify-content: space-around; font-size: 10.5px; font-weight: bold; border: 1px solid #000; padding: 8px; background: #f9f9f9; }

        .input-editable-acuse {
            border: none !important; background: transparent !important; width: 100%; font-family: Arial, sans-serif; font-size: 11px; font-weight: 600; padding: 0; margin: 0; outline: none; color: #000;
        }
        .textarea-editable-acuse {
            border: none !important; background: transparent !important; width: 100%; height: 100%; font-family: Arial, sans-serif; font-size: 11px; padding: 6px; margin: 0; outline: none; box-sizing: border-box; resize: none; color: #000;
        }

        @media print {
            .panel-acciones-pantalla { display: none; }
            body, html { height: 100%; overflow: hidden; }
            .contenedor-formato { height: calc(100vh - 16mm); border: 2.5px solid #000; }
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
        <button type="button" class="btn-accion btn-print" onclick="window.print();">Imprimir Formato</button>
        <?php if ($puede_editar_acuse && !$solo_observa) { ?>
            <button type="submit" class="btn-accion btn-save">Guardar Cambios Locales</button>
        <?php } ?>
    </div>

    <div class="contenedor-formato" style="margin: 0 auto;">
        <div class="bloque-izquierdo">
            <div>
                <div class="header-logo-zona">
                    <img src="rayas.png" alt="Logo" style="height: 35px;">
                    <div class="logo-txt">GOBIERNO DE LA CIUDAD DE MÉXICO<br>COORDINACIÓN DE ADMINISTRACIÓN DE CAPITAL HUMANO</div>
                </div>
                
                <div class="titulo-central">ACUSE</div>

                <table class="tabla-datos">
                    <tr>
                        <td style="width: 8%;">
                            <span class="lbl-seccion">Año</span>
                            <span class="val-seccion">2026</span>
                        </td>
                        <td style="width: 18%;">
                            <span class="lbl-seccion">Fecha de Recepción</span>
                            <span class="val-seccion"><?php echo date('d/m/Y', strtotime($doc['fecha_ingreso'])); ?></span>
                        </td>
                        <td style="width: 10%;">
                            <span class="lbl-seccion">Hora</span>
                            <span class="val-seccion"><?php echo date('H:i', strtotime($doc['fecha_ingreso'])); ?></span>
                        </td>
                        <td style="width: 20%;">
                            <span class="lbl-seccion">No de Oficio</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['num_ingreso']); ?></span>
                        </td>
                        <td style="width: 24%;">
                            <span class="lbl-seccion">Fecha de Documento</span>
                            <span class="val-seccion"><?php echo !empty($doc['fecha_documento']) ? date('d/m/Y', strtotime($doc['fecha_documento'])) : 'S/F'; ?></span>
                        </td>
                        <td style="width: 20%;">
                            <span class="lbl-seccion">Tipo de Documento</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['tipo_documento']); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="lbl-seccion">Atención</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['prioridad']); ?></span>
                        </td>
                        <td colspan="1">
                            <span class="lbl-seccion">Folio CACH</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['trae_cach'] ?: 'S/N'); ?></span>
                        </td>
                        <td colspan="2">
                            <span class="lbl-seccion">Folio DGAF</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['es_dgaf'] ?: 'S/N'); ?></span>
                        </td>
                        <td colspan="1">
                            <span class="lbl-seccion">Folio CJSL</span>
                            <span class="val-seccion"><?php echo htmlspecialchars($doc['folio_cjsl'] ?: 'S/N'); ?></span>
                        </td>
                    </tr>
                </table>

                <span class="lbl-seccion">Enviado Por:</span>
                <div style="font-size: 11.5px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px;">
                    <?php echo htmlspecialchars($doc['enviado_por']); ?>
                </div>

                <span class="lbl-seccion">Asunto:</span>
                <div class="caja-texto-encuadrada">
                    <?php echo nl2br(htmlspecialchars($doc['titulo'])); ?>
                </div>

                <div class="subtitulo-bar">Turnar</div>

                <table class="tabla-datos">
                    <tr>
                        <td style="width: 50%;">
                            <span class="lbl-seccion">Departamento</span>
                            <span class="val-seccion" style="text-transform: uppercase;"><?php echo htmlspecialchars($doc['nombre_area']); ?></span>
                        </td>
                        <td style="width: 50%;">
                            <span class="lbl-seccion">Fecha de Entrega al Departamento</span>
                            <span class="val-seccion"><?php echo date('d/m/Y H:i', strtotime($doc['fecha_ingreso'])); ?></span>
                        </td>
                    </tr>
                </table>

                <?php if (!empty($doc['instruccion'])) { ?>
                    <span class="lbl-seccion">Instrucción:</span>
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
                            <span class="lbl-seccion">Fecha de Entrega a Operativo:</span>
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
                    <span class="lbl-seccion">Capturista Sistema:</span>
                    <strong><?php echo htmlspecialchars($doc['capturista'] ?: 'Alejandra'); ?></strong>
                </div>
                <div class="firma-linea">
                    <span class="lbl-seccion">Recibí Operativo:</span>
                    <br><span style="font-size: 8.5px; color: #444;">Nombre, Fecha y Firma</span>
                </div>
            </div>
        </div>

        <div class="bloque-derecho">
            <div>
                <div class="sello-recibido-box">ESPACIO PARA SELLO FÍSICO<br>DE RECIBIDO DEL DEPARTAMENTO</div>
                <div class="subtitulo-bar" style="background:#000; color:#fff;">Respuesta para Descarga</div>
                
                <div class="linea-control-derecha" style="margin-top: 8px;">
                    <span class="lbl-seccion">No Oficio de Respuesta:</span>
                    <?php if ($puede_editar_acuse && !$solo_observa) { ?>
                        <input type="text" name="num_respuesta" class="input-editable-acuse" value="<?php echo htmlspecialchars($doc['num_respuesta']); ?>" placeholder="Escribir número de oficio">
                    <?php } else { ?>
                        <span class="val-seccion"><?php echo htmlspecialchars($doc['num_respuesta'] ?: '____________________'); ?></span>
                    <?php } ?>
                </div>
                
                <div class="linea-control-derecha">
                    <span class="lbl-seccion">Fecha de Contestación:</span>
                    <?php if ($puede_editar_acuse && !$solo_observa) { ?>
                        <input type="date" name="fecha_respuesta" class="input-editable-acuse" value="<?php echo !empty($doc['fecha_respuesta']) ? date('Y-m-d', strtotime($doc['fecha_respuesta'])) : ''; ?>">
                    <?php } else { ?>
                        <span class="val-seccion"><?php echo !empty($doc['fecha_respuesta']) ? date('d/m/Y', strtotime($doc['fecha_respuesta'])) : '____ / ____ / 2026'; ?></span>
                    <?php } ?>
                </div>
                
                <div class="linea-control-derecha">
                    <span class="lbl-seccion">Dirigido a:</span>
                    <?php if ($puede_editar_acuse && !$solo_observa) { ?>
                        <input type="text" name="enviado_por" class="input-editable-acuse" value="<?php echo htmlspecialchars($doc['enviado_por']); ?>" required>
                    <?php } else { ?>
                        <span class="val-seccion" style="font-size: 10px;"><?php echo htmlspecialchars($doc['enviado_por']); ?></span>
                    <?php } ?>
                </div>
                
                <span class="lbl-seccion" style="margin-top: 8px;">Observaciones / Notas de Validación:</span>
            </div>
            
            <div class="caja-blanca-observaciones">
                <?php if ($puede_editar_acuse && !$solo_observa) { ?>
                    <textarea name="comentarios_abogado" class="textarea-editable-acuse" placeholder="Plasmado de observaciones o notas funcionales..."><?php echo htmlspecialchars($doc['comentarios_abogado'] ?? ''); ?></textarea>
                <?php } else { ?>
                    <div style="padding: 8px; font-size: 11px;"><?php echo nl2br(htmlspecialchars($doc['comentarios_abogado'] ?? '')); ?></div>
                <?php } ?>
            </div>

            <div class="estatus-check-zona">
                <div>[ <?php echo $doc['estado'] == 'Pendiente' ? 'X' : ' '; ?> ] PENDIENTE</div>
                <div>[ <?php echo $doc['estado'] == 'Resuelto_Para_Validar' ? 'X' : ' '; ?> ] CONCLUIDO</div>
            </div>
        </div>
    </div>
</form>

</body>
</html>