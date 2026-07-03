<?php

/**
 * Servicio de Autorización
 * 
 * Gestiona los permisos y autorización de usuarios
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */
class AuthorizationService
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        require_once __DIR__ . '/../config/conexion.php';
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    /**
     * Verifica si un usuario tiene un permiso específico
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso a verificar
     * @return bool True si tiene permiso, False en caso contrario
     */
    public function tienePermiso($idusuario, $idpermiso)
    {
        try {
            // Verificar si el usuario es administrador
            if ($this->esAdministrador($idusuario)) {
                return true;
            }

            // Verificar permiso específico asignado al usuario
            $query = "SELECT COUNT(*) FROM permiso_usuario 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso AND estado = 1";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            // Si no tiene permiso específico, verificar permisos por cargo
            $query = "SELECT cargo FROM usuarios WHERE idusuario = :idusuario AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            $cargo = $stmt->fetchColumn();

            // Verificar si el cargo tiene el permiso
            return $this->cargoTienePermiso($cargo, $idpermiso);
        } catch (PDOException $e) {
            // Registrar error
            error_log('Error al verificar permiso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un usuario tiene un permiso por su nombre
     * 
     * @param int $idusuario ID del usuario
     * @param string $nombre_permiso Nombre del permiso a verificar
     * @return bool True si tiene permiso, False en caso contrario
     */
    public function tienePermisoNombre($idusuario, $nombre_permiso)
    {
        try {
            // Si el usuario es administrador, tiene todos los permisos
            if ($this->esAdministrador($idusuario)) {
                return true;
            }

            // Obtener el ID del permiso por su nombre
            $query = "SELECT idpermiso FROM permiso WHERE nombre = :nombre AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $nombre_permiso, PDO::PARAM_STR);
            $stmt->execute();

            $idpermiso = $stmt->fetchColumn();

            if (!$idpermiso) {
                return false; // El permiso no existe
            }

            return $this->tienePermiso($idusuario, $idpermiso);
        } catch (PDOException $e) {
            error_log('Error al verificar permiso por nombre: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un cargo tiene un permiso específico
     * 
     * @param string $cargo Cargo del usuario
     * @param int $idpermiso ID del permiso
     * @return bool True si tiene permiso, False en caso contrario
     */
    private function cargoTienePermiso($cargo, $idpermiso)
    {
        // Definir permisos por cargo
        $permisos_por_cargo = [
            'admin' => ['*'], // El admin tiene todos los permisos
            'recepcionista' => [
                1, // ID de permiso 'usuarios'
                2  // ID de permiso 'perfil'
            ],
            'vendedor' => [
                2  // ID de permiso 'perfil'
            ]
            // Añadir más cargos según sea necesario
        ];

        // Si el cargo no está definido, no tiene permisos
        if (!isset($permisos_por_cargo[$cargo])) {
            return false;
        }

        // Si el cargo tiene todos los permisos
        if (in_array('*', $permisos_por_cargo[$cargo])) {
            return true;
        }

        // Verificar si el permiso específico está en la lista
        return in_array($idpermiso, $permisos_por_cargo[$cargo]);
    }

    /**
     * Verifica si un usuario puede acceder a un módulo
     * 
     * @param int $idusuario ID del usuario
     * @param string $modulo Nombre del módulo (usuarios o perfil)
     * @return bool True si puede acceder, False en caso contrario
     */
    public function puedeAccederModulo($idusuario, $modulo)
    {
        return $this->tienePermisoNombre($idusuario, $modulo);
    }

    /**
     * Verifica si un usuario tiene acceso a un módulo (permiso específico O es administrador)
     * Ideal para módulos estándar donde el administrador siempre debe tener acceso
     * 
     * @param int $idusuario ID del usuario
     * @param string $modulo Nombre del módulo
     * @return bool True si puede acceder, False en caso contrario
     */
    public function tieneAccesoModulo($idusuario, $modulo)
    {
        return $this->puedeAccederModulo($idusuario, $modulo) || $this->esAdministrador($idusuario);
    }

    /**
     * Verifica si un usuario tiene acceso crítico a un módulo 
     * (debe tener el permiso específico Y ser administrador)
     * Ideal para módulos críticos como configuración del sistema, gestión de usuarios, etc.
     * 
     * @param int $idusuario ID del usuario
     * @param string $modulo Nombre del módulo
     * @return bool True si puede acceder, False en caso contrario
     */
    public function tieneAccesoCritico($idusuario, $modulo)
    {
        return $this->puedeAccederModulo($idusuario, $modulo) && $this->esAdministrador($idusuario);
    }

    /**
     * Verifica si un usuario tiene acceso exclusivo a un módulo 
     * (debe tener SOLO el permiso específico, NO es suficiente ser administrador)
     * Ideal para módulos donde se requiere un permiso explícito
     * 
     * @param int $idusuario ID del usuario
     * @param string $modulo Nombre del módulo
     * @return bool True si puede acceder, False en caso contrario
     */
    public function tieneAccesoExclusivo($idusuario, $modulo)
    {
        return $this->puedeAccederModulo($idusuario, $modulo);
    }

    /**
     * Verifica si un usuario es administrador
     * 
     * @param int $idusuario ID del usuario
     * @return bool True si es administrador, False en caso contrario
     */
    public function esAdministrador($idusuario)
    {
        try {
            $query = "SELECT cargo FROM usuarios WHERE idusuario = :idusuario AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            $cargo = $stmt->fetchColumn();

            return $cargo === 'Administrador';
        } catch (PDOException $e) {
            error_log('Error al verificar si es administrador: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los permisos de un usuario
     * 
     * @param int $idusuario ID del usuario
     * @return array Lista de permisos
     */
    public function obtenerPermisosUsuario($idusuario)
    {
        try {
            // Si es administrador, devolver todos los permisos
            if ($this->esAdministrador($idusuario)) {
                $query = "SELECT idpermiso, nombre FROM permiso WHERE estado = 1";
                $stmt = $this->conexion->prepare($query);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Obtener permisos específicos del usuario
            $query = "SELECT p.idpermiso, p.nombre 
                     FROM permiso_usuario pu 
                     JOIN permiso p ON pu.idpermiso = p.idpermiso 
                     WHERE pu.idusuario = :idusuario AND pu.estado = 1 AND p.estado = 1";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            $permisos_especificos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener cargo del usuario
            $query = "SELECT cargo FROM usuarios WHERE idusuario = :idusuario AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();

            $cargo = $stmt->fetchColumn();

            // Obtener permisos por cargo
            $permisos_cargo = $this->obtenerPermisosPorCargo($cargo);

            // Combinar ambos conjuntos de permisos (evitando duplicados por ID)
            $todos_permisos = array_merge($permisos_especificos, $permisos_cargo);
            $permisos_unicos = [];
            $ids_procesados = [];

            foreach ($todos_permisos as $permiso) {
                if (!in_array($permiso['idpermiso'], $ids_procesados)) {
                    $permisos_unicos[] = $permiso;
                    $ids_procesados[] = $permiso['idpermiso'];
                }
            }

            return $permisos_unicos;
        } catch (PDOException $e) {
            // Registrar error
            error_log('Error al obtener permisos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los permisos asociados a un cargo
     * 
     * @param string $cargo Cargo del usuario
     * @return array Lista de permisos
     */
    private function obtenerPermisosPorCargo($cargo)
    {
        // Similar a cargoTienePermiso, pero devuelve la lista completa de permisos
        $permisos_por_cargo = [
            'admin' => ['*'], // Todos los permisos
            'recepcionista' => [
                1, // ID de permiso 'usuarios'
                2  // ID de permiso 'perfil'
            ],
            'vendedor' => [
                2  // ID de permiso 'perfil'
            ]
        ];

        if (!isset($permisos_por_cargo[$cargo])) {
            return [];
        }

        if (in_array('*', $permisos_por_cargo[$cargo])) {
            // Si tiene todos los permisos, obtener la lista completa de la base de datos
            $query = "SELECT idpermiso, nombre FROM permiso WHERE estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Obtener los detalles de los permisos por sus IDs
        $ids = implode(',', $permisos_por_cargo[$cargo]);
        $query = "SELECT idpermiso, nombre FROM permiso WHERE idpermiso IN ($ids) AND estado = 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Asigna un permiso a un usuario
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso
     * @return bool True si se asignó correctamente, False en caso contrario
     */
    public function asignarPermiso($idusuario, $idpermiso)
    {
        try {
            // Verificar si ya existe la asignación
            $query = "SELECT COUNT(*) FROM permiso_usuario 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                // Ya existe, actualizar estado a activo
                $query = "UPDATE permiso_usuario SET estado = 1 
                         WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
                $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
                return $stmt->execute();
            } else {
                // No existe, crear nueva asignación
                $query = "INSERT INTO permiso_usuario (idpermiso, idusuario) 
                         VALUES (:idpermiso, :idusuario)";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
                $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
                return $stmt->execute();
            }
        } catch (PDOException $e) {
            error_log('Error al asignar permiso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoca un permiso a un usuario
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso
     * @return bool True si se revocó correctamente, False en caso contrario
     */
    public function revocarPermiso($idusuario, $idpermiso)
    {
        try {
            $query = "UPDATE permiso_usuario SET estado = 0 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error al revocar permiso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los permisos disponibles en el sistema
     * 
     * @return array Lista de todos los permisos
     */
    public function obtenerTodosLosPermisos()
    {
        try {
            $query = "SELECT idpermiso, nombre FROM permiso WHERE estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error al obtener todos los permisos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un usuario tiene asignado un permiso específico
     * 
     * @param int $idusuario ID del usuario
     * @param int $idpermiso ID del permiso
     * @return bool True si tiene el permiso asignado, False en caso contrario
     */
    public function tienePermisoAsignado($idusuario, $idpermiso)
    {
        try {
            $query = "SELECT COUNT(*) FROM permiso_usuario 
                     WHERE idusuario = :idusuario AND idpermiso = :idpermiso AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->bindParam(':idpermiso', $idpermiso, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error al verificar permiso asignado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los permisos agrupados por cargo
     * 
     * @return array Permisos agrupados por cargo
     */
    public function obtenerPermisosAgrupados()
    {
        try {
            // Definir los permisos por cargo
            $permisos_por_cargo = [
                'Administrador' => [
                    'descripcion' => 'Acceso total al sistema',
                    'permisos' => '*' // Todos los permisos
                ],
                'Limpieza' => [
                    'descripcion' => 'Acceso a asignación de limpieza y perfil',
                    'permisos' => ['limpieza', 'perfil']
                ],
                'Recepcionista' => [
                    'descripcion' => 'Acceso a módulos de recepción y servicios',
                    'permisos' => [
                        'productos',
                        'categorias',
                        'recepcion',
                        'habitaciones',
                        'nueva_venta',
                        'ventas',
                        'nueva_compra',
                        'compras',
                        'personas',
                        'clientes',
                        'banos',
                        'servicios_bano',
                        'limpieza',
                        'equipajes',
                        'precios_equipaje',
                        'perfil'
                    ]
                ]
            ];

            // Obtener todos los permisos disponibles
            $todos_permisos = $this->obtenerTodosLosPermisos();

            // Agrupar permisos por ID para búsqueda rápida
            $permisos_por_id = [];
            foreach ($todos_permisos as $permiso) {
                $permisos_por_id[$permiso['nombre']] = $permiso;
            }

            // Construir resultado final con IDs de permisos
            $resultado = [];
            foreach ($permisos_por_cargo as $cargo => $info) {
                $permisos_cargo = [];

                // Si tiene todos los permisos
                if ($info['permisos'] === '*') {
                    $permisos_cargo = $todos_permisos;
                } else {
                    // Filtrar permisos específicos
                    foreach ($info['permisos'] as $nombre_permiso) {
                        if (isset($permisos_por_id[$nombre_permiso])) {
                            $permisos_cargo[] = $permisos_por_id[$nombre_permiso];
                        }
                    }
                }

                $resultado[$cargo] = [
                    'descripcion' => $info['descripcion'],
                    'permisos' => $permisos_cargo
                ];
            }

            return $resultado;
        } catch (PDOException $e) {
            error_log('Error al obtener permisos agrupados: ' . $e->getMessage());
            return [];
        }
    }
}
