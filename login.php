
<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

   $usuarios_sistema = [
    'admin@sistema.com' => [
        'id_usuario' => 1,
        'nombre'     => 'Administrador General',
        'password'   => '$2y$10$IuEux.TDM5kSiey8YLFmQ..LRl9o038rhKb15MVINBlHvDO3ERgMu',
        'rol'        => 'Administrador',
        'id_area'     => 1,
    ],
    'secretaria_dg@sistema.com' => [
        'id_usuario' => 2,
        'nombre'     => 'Secretaría de Dirección General',
        'password'   => '$2y$10$IuEux.TDM5kSiey8YLFmQ..LRl9o038rhKb15MVINBlHvDO3ERgMu',
        'rol'        => 'Secretaria',
        'id_area'     => 5,
    ],
    'direccion_general@sistema.com' => [
        'id_usuario' => 3,
        'nombre'     => 'Dirección General',
        'password'   => '$2y$10$IuEux.TDM5kSiey8YLFmQ..LRl9o038rhKb15MVINBlHvDO3ERgMu',
        'rol'        => 'Direccion General',
        'id_area'     => 6,
    ],
   
    'coordinacion@sistema.com' => [
        'id_usuario' => 4,
        'nombre'     => 'Coordinación de Administración de Capital Humano',
        'password'   => '$2y$10$IuEux.TDM5kSiey8YLFmQ..LRl9o038rhKb15MVINBlHvDO3ERgMu',
        'rol'        => 'Coordinación',
        'id_area'     => 7,
    ],
    'jud_prestacionesp@sistema.com' => [
        'id_usuario' => 10,
        'nombre'     => 'JUD de Prestaciones y Politicas Laborales',
        'password'   => '$2y$10$IuEux.TDM5kSiey8YLFmQ..LRl9o038rhKb15MVINBlHvDO3ERgMu',
        'rol'        => 'JUD',
        'id_area'     => 1,
    ],
    'jud_desaro@sistema.com' => [
        'id_usuario' => 11,
        'nombre'     => 'JUD de Desarrollo Organizacional',
        'password'   => '$2y$10$IuEux.TDM5kSiey8YLFmQ..LRl9o038rhKb15MVINBlHvDO3ERgMu',
        'rol'        => 'JUD',
        'id_area'     => 2,
    ],
    'jud_nominas@sistema.com' => [
        'id_usuario' => 12,
        'nombre'     => 'JUD de Nominas',
        'password'   => '$2y$10$IuEux.TDM5kSiey8YLFmQ..LRl9o038rhKb15MVINBlHvDO3ERgMu',
        'rol'        => 'JUD',
        'id_area'     => 3,
    ],
    'jud_controlpersonal@sistema.com' => [
        'id_usuario' => 13,
        'nombre'     => 'JUD de Control de Personal',
        'password'   => '$2y$10$IuEux.TDM5kSiey8YLFmQ..LRl9o038rhKb15MVINBlHvDO3ERgMu',
        'rol'        => 'JUD',
        'id_area'     => 4,
    ]
];


    if (array_key_exists($email, $usuarios_sistema)) {
        $usuario = $usuarios_sistema[$email];

        if (password_verify($password, $usuario['password'])) {
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['rol']        = $usuario['rol'];
            $_SESSION['id_area']    = $usuario['id_area'];
            header("Location: tablon.php");
            exit;
        }
    }

    header("Location: index.php?error=credenciales");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>



