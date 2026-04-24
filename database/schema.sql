-- ============================================================
-- TimeClock - Sistema de Control de Horas Laborales
-- Schema v1.0
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '-05:00'; -- Colombia (UTC-5)

CREATE DATABASE IF NOT EXISTS timeclock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE timeclock;

-- ------------------------------------------------------------
-- SEDES / LUGARES DE TRABAJO
-- ------------------------------------------------------------
CREATE TABLE sedes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    direccion VARCHAR(200),
    supervisor_id INT UNSIGNED NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- CARGOS
-- ------------------------------------------------------------
CREATE TABLE cargos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    -- Minutos de descanso (almuerzo/breaks) parametrizables por cargo
    minutos_descanso INT UNSIGNED DEFAULT 60,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- USUARIOS / EMPLEADOS
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    apellido VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE,
    cedula VARCHAR(30) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('empleado','supervisor','admin') DEFAULT 'empleado',
    cargo_id INT UNSIGNED,
    sede_id INT UNSIGNED,
    -- Si NULL => puede marcar desde cualquier equipo
    equipo_permitido VARCHAR(200) COMMENT 'Nombre de equipo o NULL para cualquiera',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cargo_id) REFERENCES cargos(id) ON DELETE SET NULL,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- EQUIPOS AUTORIZADOS POR CARGO (alternativa multi-equipo)
-- ------------------------------------------------------------
CREATE TABLE equipos_autorizados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cargo_id INT UNSIGNED NOT NULL,
    sede_id INT UNSIGNED,
    nombre_equipo VARCHAR(200) NOT NULL COMMENT 'Nombre de host del equipo',
    descripcion VARCHAR(200),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cargo_id) REFERENCES cargos(id) ON DELETE CASCADE,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TURNOS
-- ------------------------------------------------------------
CREATE TABLE turnos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    -- TRUE si el turno cruza la medianoche (ej: 22:00 - 06:00)
    nocturno TINYINT(1) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ASIGNACIÓN DE TURNOS POR SEMANA
-- ------------------------------------------------------------
CREATE TABLE asignacion_turnos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    turno_id INT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    -- Días de la semana activos (0=Dom, 1=Lun ... 6=Sab) separados por coma
    dias_semana VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5' COMMENT '0=Dom,1=Lun,...,6=Sab',
    aprobado TINYINT(1) DEFAULT 0,
    supervisor_id INT UNSIGNED,
    observacion VARCHAR(300),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turno_id) REFERENCES turnos(id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MARCACIONES (entradas / salidas)
-- ------------------------------------------------------------
CREATE TABLE marcaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo ENUM('entrada','salida') NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    fecha_hora DATETIME NOT NULL,
    sede_id INT UNSIGNED,
    nombre_equipo VARCHAR(200),
    observacion TEXT,
    -- Estado calculado respecto al turno
    estado ENUM(
        'puntual',
        'llegada_tarde',
        'salida_temprana',
        'salida_tarde',
        'compensacion',
        'cierre_automatico',
        'fuera_turno',
        'sin_turno'
    ) DEFAULT 'puntual',
    minutos_diferencia INT DEFAULT 0 COMMENT 'Diferencia vs turno en minutos (+tarde, -temprano)',
    -- Aprobación supervisor
    aprobado ENUM('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
    supervisor_id INT UNSIGNED,
    supervisor_observacion VARCHAR(300),
    aprobado_at TIMESTAMP NULL,
    -- Marcación automática por cierre de turno
    auto_cerrado TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL,
    FOREIGN KEY (supervisor_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- RESUMEN DIARIO (calculado al cerrar el día)
-- ------------------------------------------------------------
CREATE TABLE resumen_diario (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    entrada_id INT UNSIGNED,
    salida_id INT UNSIGNED,
    hora_entrada TIME,
    hora_salida TIME,
    minutos_brutos INT DEFAULT 0,
    minutos_descanso INT DEFAULT 0,
    minutos_netos INT DEFAULT 0,
    horas_netas DECIMAL(5,2) DEFAULT 0.00,
    dia_semana TINYINT COMMENT '0=Dom,1=Lun,...,6=Sab',
    es_festivo TINYINT(1) DEFAULT 0,
    estado_dia ENUM('completo','pendiente_salida','auto_cerrado','sin_marcacion') DEFAULT 'pendiente_salida',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resumen (usuario_id, fecha),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (entrada_id) REFERENCES marcaciones(id) ON DELETE SET NULL,
    FOREIGN KEY (salida_id) REFERENCES marcaciones(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PARÁMETROS GLOBALES / POR CARGO
-- ------------------------------------------------------------
CREATE TABLE parametros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- NULL = parámetro global; valor = aplica solo al cargo
    cargo_id INT UNSIGNED,
    clave VARCHAR(80) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    descripcion VARCHAR(300),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_param (cargo_id, clave),
    FOREIGN KEY (cargo_id) REFERENCES cargos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- FESTIVOS DE COLOMBIA
-- ------------------------------------------------------------
CREATE TABLE festivos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    tipo ENUM('fijo','trasladable','especial') DEFAULT 'fijo'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- LOGS DE AUDITORÍA
-- ------------------------------------------------------------
CREATE TABLE auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED,
    accion VARCHAR(80) NOT NULL,
    tabla VARCHAR(60),
    registro_id INT UNSIGNED,
    datos_anteriores JSON,
    datos_nuevos JSON,
    ip VARCHAR(45),
    user_agent VARCHAR(300),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- FK diferida: sedes.supervisor_id → usuarios (sedes se crea antes que usuarios)
ALTER TABLE sedes
  ADD CONSTRAINT fk_sedes_supervisor
      FOREIGN KEY (supervisor_id) REFERENCES usuarios(id) ON DELETE SET NULL;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Parámetros globales por defecto
INSERT INTO parametros (cargo_id, clave, valor, descripcion) VALUES
(NULL, 'tolerancia_entrada_antes', '10', 'Minutos antes del turno que se permite marcar entrada'),
(NULL, 'tolerancia_entrada_despues', '15', 'Minutos después de inicio de turno considerados puntuales'),
(NULL, 'tolerancia_salida_antes', '10', 'Minutos antes de fin de turno para marcar salida'),
(NULL, 'tolerancia_salida_despues', '30', 'Minutos después de fin de turno para marcar salida normal'),
(NULL, 'minutos_descanso_global', '60', 'Minutos de descanso por defecto si cargo no tiene configurado'),
(NULL, 'cerrar_turno_auto', '1', '1=activar cierre automático de turno pendiente'),
(NULL, 'requiere_aprobacion', '1', '1=marcaciones requieren aprobación de supervisor');

-- Turnos de ejemplo
INSERT INTO turnos (nombre, hora_inicio, hora_fin, nocturno) VALUES
('Mañana', '07:00:00', '17:00:00', 0),
('Tarde', '14:00:00', '22:00:00', 0),
('Noche', '22:00:00', '06:00:00', 1),
('Oficina', '08:00:00', '18:00:00', 0);

-- Admin por defecto (contraseña: Admin2025!)
INSERT INTO cargos (nombre, minutos_descanso) VALUES ('Administrador', 60);
INSERT INTO sedes (nombre) VALUES ('Sede Principal');
INSERT INTO usuarios (nombre, apellido, email, cedula, password_hash, rol, cargo_id, sede_id) VALUES
('Admin', 'Sistema', 'admin@timeclock.com', '000000001',
 '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiuVP4kU3F5g8d9e0f1a2b3c4d5e6',
 'admin', 1, 1);

-- Festivos Colombia 2025 (ejemplo de fijos)
INSERT INTO festivos (fecha, nombre, tipo) VALUES
('2025-01-01','Año Nuevo','fijo'),
('2025-01-06','Reyes Magos','trasladable'),
('2025-03-24','San José','trasladable'),
('2025-04-17','Jueves Santo','especial'),
('2025-04-18','Viernes Santo','especial'),
('2025-05-01','Día del Trabajo','fijo'),
('2025-06-02','Ascensión del Señor','trasladable'),
('2025-06-23','Corpus Christi','trasladable'),
('2025-06-30','Sagrado Corazón','trasladable'),
('2025-07-07','San Pedro y San Pablo','trasladable'),
('2025-07-20','Independencia de Colombia','fijo'),
('2025-08-07','Batalla de Boyacá','fijo'),
('2025-08-18','Asunción de la Virgen','trasladable'),
('2025-10-13','Día de la Raza','trasladable'),
('2025-11-03','Todos los Santos','trasladable'),
('2025-11-17','Independencia de Cartagena','trasladable'),
('2025-12-08','Inmaculada Concepción','fijo'),
('2025-12-25','Navidad','fijo');
