USE ropa_live;

SET @agregar_marca = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE productos ADD COLUMN marca VARCHAR(100) NOT NULL DEFAULT ''RopaLive'' AFTER descripcion',
        'SELECT ''La columna marca ya existe'' AS resultado'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'productos'
      AND COLUMN_NAME = 'marca'
);
PREPARE sentencia_marca FROM @agregar_marca;
EXECUTE sentencia_marca;
DEALLOCATE PREPARE sentencia_marca;

SET @agregar_categoria = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE productos ADD COLUMN categoria VARCHAR(100) NOT NULL DEFAULT ''General'' AFTER marca',
        'SELECT ''La columna categoria ya existe'' AS resultado'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'productos'
      AND COLUMN_NAME = 'categoria'
);
PREPARE sentencia_categoria FROM @agregar_categoria;
EXECUTE sentencia_categoria;
DEALLOCATE PREPARE sentencia_categoria;
