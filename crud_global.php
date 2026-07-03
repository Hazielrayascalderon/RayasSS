<?php
session_start();
if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['rol'], ['Administrador', 'Coordinación', 'Coordinaci贸n'])) {
    header("Location: tablon.php");
    exit;
}

require_once 'conexion.php';
$db = new Conexion();
$conn = $db->getConexion();
$rol = $_SESSION['rol'];

$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$filtro_area = isset($_GET['filtro_area']) ? $_GET['filtro_area'] : '';
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$orden_fecha = isset($_GET['orden_fecha']) && $_GET['orden_fecha'] === 'ASC' ? 'ASC' : 'DESC';
$areas = $conn->query("SELECT * FROM areas ORDER BY nombre_area ASC")->fetchAll();

$conditions = [];
$params = [];
if (!empty($buscar)) {
    $conditions[] = "(d.id_doc = :b_id OR d.titulo LIKE :b_like OR d.num_ingreso LIKE :b_like OR d.enviado_por LIKE :b_like OR d.capturista LIKE :b_like OR d.num_respuesta LIKE :b_like)";
    $params[':b_id'] = intval($buscar);
    $params[':b_like'] = '%' . $buscar . '%';
}
if (!empty($filtro_area)) {
    $conditions[] = "d.id_area_asignada = :area";
    $params[':area'] = intval($filtro_area);
}
if (!empty($filtro_estado)) {
    $conditions[] = "d.estado = :estado";
    $params[':estado'] = $filtro_estado;
}

$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "SELECT d.*, a.nombre_area 
        FROM documentos d 
        JOIN areas a ON d.id_area_asignada = a.id_area 
        $where_clause 
        ORDER BY d.fecha_ingreso $orden_fecha";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$documentos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Maestro Global | Coordinación y Administración</title>
    <link rel="icon" href="rayas.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        :root {
            --guinda-base: #9f2241;
            --guinda-oscuro: #6f1120; 
            --guinda-oro: #bc955c;
            --oro-claro: #ddc9a3;
            --naranja-base: #f97316;
            --surface-bg: #f4f6f8; 
            --text-main: #2d3748;
        }
        body { background-color: var(--surface-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); -webkit-font-smoothing: antialiased; font-size: 13px; }
        
        .navbar-custom { 
            background: linear-gradient(135deg, var(--guinda-oscuro) 0%, var(--guinda-base) 100%);
            border-bottom: 4px solid var(--guinda-oro);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .logo-esquina { height: 45px; object-fit: contain; border-radius: 6px; background: rgba(255,255,255,0.1); padding: 2px; }
        .nav-link { color: rgba(255, 255, 255, 0.8) !important; font-weight: 500; font-size: 0.9rem; border-radius: 6px; transition: all 0.2s ease; padding: 0.5rem 0.8rem !important; }
        .nav-link:hover { color: var(--guinda-oro) !important; background-color: rgba(255, 255, 255, 0.05); }
        .nav-link.active { color: #ffffff !important; font-weight: 700; background-color: rgba(255, 255, 255, 0.15); border-bottom: 2px solid var(--guinda-oro); border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
        .btn-salir { background-color: rgba(255, 255, 255, 0.1); color: white; border: 1px solid rgba(255,255,255,0.2); transition: all 0.2s; }
        .btn-salir:hover { background-color: #dc2626; border-color: #dc2626; color: white !important; }

        .table th { font-size: 0.72rem; letter-spacing: 0.8px; text-transform: uppercase; font-weight: 700; color: #475569; background-color: #f8fafc; }
        .modal-header { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom mb-4 sticky-top py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 me-lg-4" href="tablon.php">
            <img src="rayas.png" alt="Logo" class="logo-esquina">
            <div class="d-flex flex-column lh-1">
                <span class="fw-bold fs-6 text-white" style="letter-spacing: 0.5px;">Control Documental</span>
                <span class="fw-medium" style="color: var(--oro-claro); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px;">Gestión Interna</span>
            </div>
        </a>

        <button class="navbar-toggler border-0 text-white p-1" type="button" data-bs-toggle="collapse" data-bs-target="#menuNavegacion">
            <i class="bi bi-list fs-2 text-white"></i>
        </button>

        <div class="collapse navbar-collapse" id="menuNavegacion">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1 mt-3 mt-lg-0">
                <li class="nav-item"><a class="nav-link px-3" href="tablon.php"><i class="bi bi-folder2-open me-2"></i>Expedientes</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="finalizados.php"><i class="bi bi-check2-circle me-2"></i>Histórico</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="empleados_tareas.php"><i class="bi bi-people me-2"></i>Tareas</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="tablon_monitoreo.php"><i class="bi bi-graph-up me-2"></i>Monitoreo</a></li>
                <li class="nav-item"><a class="nav-link active px-3" href="crud_global.php"><i class="bi bi-sliders me-2"></i>Ajustes</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0 pb-2 pb-lg-0 border-top border-lg-0 pt-3 pt-lg-0 border-secondary-subtle">
                <div id="reloj-banner" class="small fw-medium font-monospace d-none d-md-flex align-items-center px-3 py-1.5 rounded" style="background: rgba(0,0,0,0.2); color: var(--oro-claro);"></div>
                <div class="vr d-none d-lg-block opacity-25" style="height: 25px; background-color: white;"></div>
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center border border-light border-opacity-25" style="width: 36px; height: 36px; background: rgba(255,255,255,0.1);">
                        <i class="bi bi-person-fill text-white"></i>
                    </div>
                    <div class="d-flex flex-column lh-1">
                        <span class="small fw-bold text-white"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                        <span style="font-size: 0.65rem; color: var(--guinda-oro); font-weight: 700; text-transform: uppercase;"><?php echo $rol; ?></span>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-salir rounded p-2 ms-1 d-flex align-items-center justify-content-center" title="Cerrar Sesión" style="width: 36px; height: 36px;">
                    <i class="bi bi-power fs-5"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    
    <div class="card border-0 shadow-sm p-4 mb-4">
        <form method="GET" action="crud_global.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary">Buscador Inteligente</label>
                <input type="text" name="buscar" class="form-control" placeholder="Buscar..." value="<?php echo htmlspecialchars($buscar); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary">Filtrar por Área Turnada</label>
                <select name="filtro_area" class="form-select">
                    <option value="">-- Ver Todas --</option>
                    <?php foreach ($areas as $a) { ?>
                        <option value="<?php echo $a['id_area']; ?>" <?php echo ($filtro_area == $a['id_area']) ? 'selected':''; ?>><?php echo htmlspecialchars($a['nombre_area']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary">Estatus Técnico</label>
                <select name="filtro_estado" class="form-select">
                    <option value="">-- Todos --</option>
                    <option value="Pendiente" <?php echo ($filtro_estado === 'Pendiente') ? 'selected':''; ?>>Pendientes</option>
                    <option value="Resuelto_Para_Validar" <?php echo ($filtro_estado === 'Resuelto_Para_Validar') ? 'selected':''; ?>>Concluidos</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary">Organizar por Fecha</label>
                <select name="orden_fecha" class="form-select">
                    <option value="DESC" <?php echo ($orden_fecha === 'DESC') ? 'selected':''; ?>>Más recientes primero</option>
                    <option value="ASC" <?php echo ($orden_fecha === 'ASC') ? 'selected':''; ?>>Más antiguos primero</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn w-100 fw-bold text-white shadow-sm" style="background-color: var(--guinda-oro); border:none;"><i class="bi bi-filter"></i> Filtrar</button>
            </div>
        </form>
    </div>

    <?php if(isset($_GET['msg'])) { ?>
        <div class="alert alert-success border-0 shadow-sm py-2">
            <i class="bi bi-check-circle-fill me-1"></i> Transacción ejecutada con éxito en la base de datos de Capital Humano.
        </div>
    <?php } ?>

    <div class="card border-0 shadow-sm overflow-hidden mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 5%">ID</th>
                        <th style="width: 10%">Estatus</th>
                        <th style="width: 15%">Área Destino</th>
                        <th style="width: 45%">Asunto e Información Técnico-Documental</th>
                        <th style="width: 10%">Archivos</th>
                        <th class="text-end pe-4" style="width: 15%">Acciones de Auditoría</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($documentos) === 0) { ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">No se encontraron expedientes con los parámetros seleccionados.</td></tr>
                    <?php } else { 
                        foreach($documentos as $doc) { ?>
                        <tr>
                            <td class="ps-4 fw-bold text-secondary">#<?php echo $doc['id_doc']; ?></td>
                            <td>
                                <span class="badge <?php echo $doc['estado'] === 'Pendiente' ? 'bg-warning text-dark' : 'bg-success'; ?> px-2 py-1">
                                    <?php echo $doc['estado'] === 'Pendiente' ? 'Pendiente' : 'Concluido'; ?>
                                </span>
                            </td>
                            <td><span class="fw-semibold text-dark small uppercase"><?php echo htmlspecialchars($doc['nombre_area']); ?></span></td>
                            <td>
                                <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($doc['titulo']); ?></div>
                                <div class="small text-muted mb-1">
                                    Oficio Entrada: <strong><?php echo htmlspecialchars($doc['num_ingreso']); ?></strong> (<?php echo date('d/m/Y', strtotime($doc['fecha_documento'])); ?>) |
                                    Remitente: <strong><?php echo htmlspecialchars($doc['enviado_por']); ?></strong> |
                                    Capturó: <strong><?php echo htmlspecialchars($doc['capturista']); ?></strong>
                                </div>
                                <?php if (!empty($doc['operativo'])) { ?>
                                    <div class="small text-primary"><i class="bi bi-person-fill"></i> Operativo Asignado: <strong><?php echo htmlspecialchars($doc['operativo']); ?></strong></div>
                                <?php } ?>
                                <?php if (!empty($doc['num_respuesta'])) { ?>
                                    <div class="small text-success"><i class="bi bi-reply-fill"></i> Oficio Respuesta: <strong><?php echo htmlspecialchars($doc['num_respuesta']); ?></strong> (<?php echo date('d/m/Y', strtotime($doc['fecha_respuesta'])); ?>)</div>
                                <?php } ?>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <a href="descargar_doc.php?id=<?php echo $doc['id_doc']; ?>&tipo=ingreso" target="_blank" class="badge bg-secondary text-decoration-none text-start p-1.5"><i class="bi bi-file-pdf"></i> Entrada</a>
                                    <?php if(!empty($doc['archivo_respuesta'])) { ?>
                                        <a href="descargar_doc.php?id=<?php echo $doc['id_doc']; ?>&tipo=respuesta" target="_blank" class="badge bg-success text-decoration-none text-start p-1.5"><i class="bi bi-file-check"></i> Respuesta</a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-1">
                                    <button class="btn btn-sm btn-warning text-white font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalCrudEditar<?php echo $doc['id_doc']; ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <a href="eliminar_doc.php?id=<?php echo $doc['id_doc']; ?>" class="btn btn-sm btn-danger fw-bold" onclick="return confirm('¿Está completamente seguro de eliminar este folio?');"><i class="bi bi-trash-fill"></i></a>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalCrudEditar<?php echo $doc['id_doc']; ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content text-start">
                              <div class="modal-header">
                                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-warning me-2"></i>Modificación de Folio Maestro #<?php echo $doc['id_doc']; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <form action="editar_doc.php" method="POST" enctype="multipart/form-data">
                                  <div class="modal-body">
                                      <input type="hidden" name="id_doc" value="<?php echo $doc['id_doc']; ?>">
                                      
                                      <div class="row mb-3">
                                          <div class="col-md-6">
                                              <label class="form-label fw-bold text-secondary">No. de Oficio Entrada</label>
                                              <input type="text" name="num_ingreso" class="form-control" value="<?php echo htmlspecialchars($doc['num_ingreso']); ?>" required>
                                          </div>
                                          <div class="col-md-6">
                                              <label class="form-label fw-bold text-secondary">Fecha del Oficio</label>
                                              <input type="date" name="fecha_documento" class="form-control" value="<?php echo htmlspecialchars($doc['fecha_documento']); ?>" required>
                                          </div>
                                      </div>

                                      <div class="mb-3">
                                          <label class="form-label fw-bold text-secondary">Asunto / Título Oficial</label>
                                          <textarea name="titulo" class="form-control" rows="2" required><?php echo htmlspecialchars($doc['titulo']); ?></textarea>
                                      </div>

                                      <div class="row mb-3">
                                          <div class="col-md-4">
                                              <label class="form-label fw-bold text-secondary">Folio CACH</label>
                                              <input type="text" name="trae_cach" class="form-control" value="<?php echo htmlspecialchars($doc['trae_cach']); ?>">
                                          </div>
                                          <div class="col-md-4">
                                              <label class="form-label fw-bold text-secondary">Folio DGAF</label>
                                              <input type="text" name="es_dgaf" class="form-control" value="<?php echo htmlspecialchars($doc['es_dgaf']); ?>">
                                          </div>
                                          <div class="col-md-4">
                                              <label class="form-label fw-bold text-secondary">Folio CJSL</label>
                                              <input type="text" name="folio_cjsl" class="form-control" value="<?php echo htmlspecialchars($doc['folio_cjsl']); ?>">
                                          </div>
                                      </div>

                                      <div class="row mb-3">
                                          <div class="col-md-4">
                                              <label class="form-label fw-bold text-secondary">Dirigido A / Enviado Por</label>
                                              <input type="text" name="enviado_por" class="form-control" value="<?php echo htmlspecialchars($doc['enviado_por']); ?>" required>
                                          </div>
                                          <div class="col-md-4">
                                              <label class="form-label fw-bold text-secondary">Capturista</label>
                                              <input type="text" name="capturista" class="form-control" value="<?php echo htmlspecialchars($doc['capturista']); ?>" required>
                                          </div>
                                          <div class="col-md-4">
                                              <label class="form-label fw-bold text-secondary">Estatus General</label>
                                              <select name="estado" class="form-select">
                                                  <option value="Pendiente" <?php echo ($doc['estado'] === 'Pendiente') ? 'selected':''; ?>>Pendiente</option>
                                                  <option value="Resuelto_Para_Validar" <?php echo ($doc['estado'] === 'Resuelto_Para_Validar') ? 'selected':''; ?>>Concluido</option>
                                              </select>
                                          </div>
                                      </div>

                                      <div class="row mb-3">
                                          <div class="col-md-6">
                                              <label class="form-label fw-bold text-secondary">Área Asignada (Re-turnar)</label>
                                              <select name="id_area_asignada" class="form-select">
                                                  <?php foreach ($areas as $ar) { ?>
                                                      <option value="<?php echo $ar['id_area']; ?>" <?php echo ($doc['id_area_asignada'] == $ar['id_area']) ? 'selected':''; ?>><?php echo htmlspecialchars($ar['nombre_area']); ?></option>
                                                  <?php } ?>
                                              </select>
                                          </div>
                                          <div class="col-md-3">
                                              <label class="form-label fw-bold text-secondary">Tipo Documento</label>
                                              <select name="tipo_documento" class="form-select">
                                                  <option value="Volante" <?php echo ($doc['tipo_documento'] === 'Volante') ? 'selected':''; ?>>Volante</option>
                                                  <option value="Nota Informativa" <?php echo ($doc['tipo_documento'] === 'Nota Informativa') ? 'selected':''; ?>>Nota Informativa</option>
                                                  <option value="Escrito" <?php echo ($doc['tipo_documento'] === 'Escrito') ? 'selected':''; ?>>Escrito</option>
                                              </select>
                                          </div>
                                          <div class="col-md-3">
                                              <label class="form-label fw-bold text-secondary">Atención</label>
                                              <select name="prioridad" class="form-select">
                                                  <option value="Sin término" <?php echo ($doc['prioridad'] === 'Sin término') ? 'selected':''; ?>>Sin término</option>
                                                  <option value="Urgente" <?php echo ($doc['prioridad'] === 'Urgente') ? 'selected':''; ?>>Urgente</option>
                                                  <option value="Extraurgente" <?php echo ($doc['prioridad'] === 'Extraurgente') ? 'selected':''; ?>>Extraurgente</option>
                                              </select>
                                          </div>
                                      </div>

                                      <div class="p-3 bg-light rounded border mb-3">
                                          <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> SUSTITUCIÓN DE ARCHIVOS DIGITALES</h6>
                                          <div class="row">
                                              <div class="col-md-6">
                                                  <label class="form-label small text-muted mb-1 fw-bold">Quitar y cambiar PDF de Entrada original</label>
                                                  <input type="file" name="replace_entrada" class="form-control" accept=".pdf">
                                              </div>
                                              <div class="col-md-6">
                                                  <label class="form-label small text-muted mb-1 fw-bold">Quitar y cambiar PDF de Respuesta</label>
                                                  <input type="file" name="replace_respuesta" class="form-control" accept=".pdf">
                                              </div>
                                          </div>
                                      </div>

                                      <div class="row mb-3">
                                          <div class="col-md-6">
                                              <label class="form-label fw-bold text-secondary">Operativo Asignado</label>
                                              <select name="operativo" class="form-select">
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
                                          </div>
                                          <div class="col-md-6">
                                              <label class="form-label fw-bold text-secondary">Fecha Entrega Operativo</label>
                                              <input type="date" name="fecha_entrega_operativo" class="form-control" value="<?php echo !empty($doc['fecha_entrega_operativo']) ? date('Y-m-d', strtotime($doc['fecha_entrega_operativo'])) : ''; ?>">
                                          </div>
                                      </div>

                                      <div class="row mb-3">
                                          <div class="col-md-6">
                                              <label class="form-label fw-bold text-secondary">No. Oficio de Respuesta</label>
                                              <input type="text" name="num_respuesta" class="form-control" value="<?php echo htmlspecialchars($doc['num_respuesta']); ?>">
                                          </div>
                                          <div class="col-md-6">
                                              <label class="form-label fw-bold text-secondary">Fecha de Contestación</label>
                                              <input type="date" name="fecha_respuesta" class="form-control" value="<?php echo htmlspecialchars($doc['fecha_respuesta']); ?>">
                                          </div>
                                      </div>

                                      <div class="mb-2">
                                          <label class="form-label fw-bold text-secondary">Observaciones / Notas de Validación</label>
                                          <textarea name="comentarios_abogado" class="form-control" rows="2"><?php echo htmlspecialchars($doc['comentarios_abogado'] ?? ''); ?></textarea>
                                      </div>
                                  </div>
                                  <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar ventana</button>
                                      <button type="submit" class="btn text-white fw-bold shadow-sm" style="background-color: var(--guinda-base);">Guardar Cambios Maestros</button>
                                  </div>
                              </form>
                            </div>
                        </div>

                    <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function inicializarReloj() {
    const ahora = new Date();
    const opciones = { weekday: 'short', day: 'numeric', month: 'short' };
    const fecha = ahora.toLocaleDateString('es-MX', opciones).replace('.', '');
    const hora = String(ahora.getHours()).padStart(2, '0') + ':' + String(ahora.getMinutes()).padStart(2, '0');
    
    const banner = document.getElementById('reloj-banner');
    if(banner) {
        banner.innerHTML = `<i class="bi bi-calendar-event me-2"></i> <span class="text-capitalize me-2">${fecha}</span> <span class="mx-1 opacity-50">|</span> <i class="bi bi-clock ms-2 me-1"></i> ${hora}`;
    }
}
setInterval(inicializarReloj, 1000); 
inicializarReloj();
</script>
</body>
</html>