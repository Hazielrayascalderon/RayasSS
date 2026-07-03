<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$hash_resultado = '';
$contrasena_ingresada = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['contrasena'])) {
    $contrasena_ingresada = $_POST['contrasena'];
    $hash_resultado = password_hash($contrasena_ingresada, PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Credenciales | Control Documental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        :root {
            --guinda-base: #9f2241;
            --guinda-oscuro: #6f1120; 
            --guinda-oro: #bc955c;
            --oro-claro: #ddc9a3;
            --surface-bg: #f4f6f8; 
            --text-main: #2d3748;
        }
        body { 
            background-color: var(--surface-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--text-main); 
        }
        .card-custom { 
            border-top: 4px solid var(--guinda-base); 
        }
        .btn-guinda { 
            background-color: var(--guinda-base); 
            color: white; 
            border: none;
        }
        .btn-guinda:hover { 
            background-color: var(--guinda-oscuro); 
            color: white; 
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 card-custom">
                <div class="card-body p-4">
                    <h4 class="text-center fw-bold mb-4 text-dark">🔐 Generador de Hashes</h4>
                    <p class="text-muted small text-center mb-4">Utiliza esta herramienta para crear las firmas de seguridad individuales de tus 4 JUDs.</p>
                    
                    <form method="POST" action="generar_hash.php">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Contraseña en Texto Plano</label>
                            <input type="text" name="contrasena" class="form-control" placeholder="Ej: Finanzas#2026" value="<?php echo htmlspecialchars($contrasena_ingresada); ?>" required autocomplete="off">
                        </div>
                        <button type="submit" class="btn btn-guinda w-100 fw-bold shadow-sm">Generar Hash Único</button>
                    </form>

                    <?php if (!empty($hash_resultado)) { ?>
                        <hr class="my-4">
                        <div class="alert alert-success border-0 shadow-sm p-3">
                            <span class="fw-bold text-success d-block mb-1">¡Clave Encriptada!</span>
                            <small class="text-muted d-block mb-2">Selecciona y copia todo el texto gris de abajo para colocarlo en tu proscesar_login.php:</small>
                            <div class="p-2 border rounded bg-white text-break font-monospace small mb-0" style="user-select: all; cursor: pointer;">
                                <?php echo $hash_resultado; ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="tablon.php" class="text-decoration-none text-muted small">Volver al Panel de Control</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>