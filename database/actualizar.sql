USE ropa_live;

-- Ejecutar una sola vez sobre una instalación anterior, después de respaldar la base.
ALTER TABLE usuarios
    ADD COLUMN nombre VARCHAR(150) NOT NULL DEFAULT '' AFTER id,
    ADD COLUMN direccion VARCHAR(255) NULL,
    ADD COLUMN ciudad VARCHAR(100) NULL,
    ADD COLUMN foto VARCHAR(255) NULL,
    ADD COLUMN creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE usuarios MODIFY rol ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente';
UPDATE usuarios SET nombre = email WHERE nombre = '';

INSERT INTO usuarios (nombre, email, password, rol, creado_en)
SELECT nombre, email, password, 'cliente', creado_en
FROM clientes
ON DUPLICATE KEY UPDATE email = VALUES(email);

CREATE TABLE colecciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    imagen VARCHAR(255) NOT NULL,
    creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE productos
    ADD COLUMN coleccion_id INT UNSIGNED NULL AFTER id,
    ADD COLUMN marca VARCHAR(100) NOT NULL DEFAULT 'RopaLive',
    ADD COLUMN categoria VARCHAR(100) NOT NULL DEFAULT 'General',
    ADD COLUMN genero ENUM('mujer', 'hombre', 'unisex') NOT NULL DEFAULT 'unisex',
    ADD COLUMN creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD INDEX idx_productos_coleccion_genero (coleccion_id, genero),
    ADD CONSTRAINT fk_productos_coleccion FOREIGN KEY (coleccion_id)
        REFERENCES colecciones(id) ON DELETE SET NULL;

CREATE TABLE producto_imagenes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id INT UNSIGNED NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_producto_imagenes_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coleccion_imagenes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coleccion_id INT UNSIGNED NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_coleccion_imagenes_coleccion FOREIGN KEY (coleccion_id)
        REFERENCES colecciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE pedidos
    ADD COLUMN usuario_id INT UNSIGNED NULL AFTER id,
    ADD COLUMN nombre_cliente VARCHAR(150) NOT NULL DEFAULT '',
    ADD COLUMN direccion VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN ciudad VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN estado ENUM('pendiente', 'preparando', 'enviado', 'entregado', 'cancelado') NOT NULL DEFAULT 'pendiente';

UPDATE pedidos p
JOIN clientes c ON c.id = p.cliente_id
JOIN usuarios u ON u.email = c.email AND u.rol = 'cliente'
SET p.usuario_id = u.id, p.nombre_cliente = c.nombre;

ALTER TABLE pedidos
    DROP FOREIGN KEY fk_pedidos_cliente,
    DROP COLUMN cliente_id,
    ADD INDEX idx_pedidos_fecha (fecha),
    ADD CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL;

CREATE TABLE pedido_detalles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NULL,
    nombre_producto VARCHAR(150) NOT NULL,
    imagen_producto VARCHAR(255) NOT NULL DEFAULT '',
    cantidad INT UNSIGNED NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_pedido_detalles_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pedido_detalles_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE clientes;
