<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header("Location: tablon.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Sistema de Expedientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" crossorigin="anonymous">
          <link rel="icon" href="rayas.png" type="image/png">
    
 
    <style>
        :root {
            --guinda-base: #6f1120;
            --guinda-oscuro: #4a0812;
            --guinda-claro: #90162a;
        }
        body { background-color: #f4f6f9; }
        .text-guinda { color: var(--guinda-base) !important; }
        .btn-guinda { background-color: var(--guinda-base); color: white; border: none; }
        .btn-guinda:hover { background-color: var(--guinda-oscuro); color: white; }
    </style>
</head>
<body class="d-flex align-items-center" style="height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="text-center mb-4 fw-bold text-guinda">Control Documental</h4>
                    
                    <?php if(isset($_GET['error'])): ?>
                        <?php if($_GET['error'] == 'ip'): ?>
                            <div class="alert alert-warning text-center py-2 small fw-bold">
                                <i class="bi bi-exclamation-triangle-fill"></i> Acceso denegado.<br>
                                Su conexión no proviene de la IP autorizada para su JUD.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger text-center py-2">
                                Credenciales incorrectas.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label text-muted">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label text-muted">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-guinda w-100 fw-bold">Ingresar al Sistema</button>
                    </form>
                </div>
            </div>
            <div class="text-center mt-3 text-muted small">
                &copy; <?php echo date("Y"); ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>