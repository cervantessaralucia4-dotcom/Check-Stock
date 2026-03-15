-- StockFlow · MySQL Schema + Datos Demo
-- Ejecutar una vez al iniciar el proyecto en Railway

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS venta_detalle, ventas, compra_detalle, compras,
                     producto_bodega, productos, categorias,
                     proveedores, bodegas, clientes, usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ── BODEGAS ──────────────────────────────────────────────────
CREATE TABLE bodegas (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(100) NOT NULL,
  direccion  VARCHAR(200),
  tipo       VARCHAR(30) DEFAULT 'bodega',
  pasillos   INT DEFAULT 1,
  activa     TINYINT(1) DEFAULT 1,
  creado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── CATEGORÍAS ───────────────────────────────────────────────
CREATE TABLE categorias (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(80) NOT NULL,
  color     VARCHAR(20) DEFAULT 'blue'
);

-- ── PROVEEDORES ──────────────────────────────────────────────
CREATE TABLE proveedores (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(150) NOT NULL,
  nit       VARCHAR(30),
  contacto  VARCHAR(100),
  telefono  VARCHAR(20),
  email     VARCHAR(100)
);

-- ── PRODUCTOS ────────────────────────────────────────────────
CREATE TABLE productos (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  codigo         VARCHAR(80) UNIQUE NOT NULL,
  nombre         VARCHAR(150) NOT NULL,
  descripcion    TEXT,
  marca          VARCHAR(60),
  modelo         VARCHAR(60),
  tipo_unidad    VARCHAR(20) DEFAULT 'unidad',
  precio_compra  DECIMAL(14,2) DEFAULT 0,
  precio_venta   DECIMAL(14,2) DEFAULT 0,
  foto           VARCHAR(500),
  activo         TINYINT(1) DEFAULT 1,
  categoria_id   INT,
  creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- ── STOCK POR BODEGA ─────────────────────────────────────────
CREATE TABLE producto_bodega (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  producto_id   INT NOT NULL,
  bodega_id     INT NOT NULL,
  pasillo       VARCHAR(10),
  estante       VARCHAR(10),
  stock         INT DEFAULT 0,
  stock_minimo  INT DEFAULT 0,
  UNIQUE KEY uq_prod_bod (producto_id, bodega_id),
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  FOREIGN KEY (bodega_id)   REFERENCES bodegas(id)   ON DELETE CASCADE
);

-- ── CLIENTES ─────────────────────────────────────────────────
CREATE TABLE clientes (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  tipo_documento      VARCHAR(20) DEFAULT 'CC',
  numero_documento    VARCHAR(30),
  nombre              VARCHAR(80) NOT NULL,
  apellido            VARCHAR(80),
  telefono            VARCHAR(20),
  email               VARCHAR(100),
  ciudad              VARCHAR(60),
  direccion           VARCHAR(150)
);

-- ── USUARIOS ─────────────────────────────────────────────────
CREATE TABLE usuarios (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(80) NOT NULL,
  apellido  VARCHAR(80),
  email     VARCHAR(100) UNIQUE NOT NULL,
  password  VARCHAR(255) NOT NULL,
  rol       VARCHAR(30) DEFAULT 'vendedor',
  activo    TINYINT(1) DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── VENTAS ───────────────────────────────────────────────────
CREATE TABLE ventas (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  codigo      VARCHAR(30) UNIQUE NOT NULL,
  fecha       DATE DEFAULT (CURRENT_DATE),
  hora        TIME DEFAULT (CURRENT_TIME),
  subtotal    DECIMAL(14,2) DEFAULT 0,
  iva         DECIMAL(14,2) DEFAULT 0,
  total       DECIMAL(14,2) DEFAULT 0,
  pagado      DECIMAL(14,2) DEFAULT 0,
  cambio      DECIMAL(14,2) DEFAULT 0,
  estado      VARCHAR(20) DEFAULT 'completada',
  cliente_id  INT,
  usuario_id  INT,
  bodega_id   INT,
  creado_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id)  REFERENCES clientes(id),
  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id),
  FOREIGN KEY (bodega_id)   REFERENCES bodegas(id)
);

-- ── DETALLE DE VENTA ─────────────────────────────────────────
CREATE TABLE venta_detalle (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  venta_id         INT NOT NULL,
  producto_id      INT NOT NULL,
  cantidad         INT NOT NULL,
  precio_unitario  DECIMAL(14,2) NOT NULL,
  total_linea      DECIMAL(14,2) NOT NULL,
  FOREIGN KEY (venta_id)    REFERENCES ventas(id)   ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- ── COMPRAS ──────────────────────────────────────────────────
CREATE TABLE compras (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  codigo            VARCHAR(30) UNIQUE NOT NULL,
  fecha             DATE DEFAULT (CURRENT_DATE),
  total             DECIMAL(14,2) DEFAULT 0,
  estado            VARCHAR(20) DEFAULT 'recibida',
  notas             TEXT,
  proveedor_id      INT,
  bodega_id         INT,
  usuario_id        INT,
  creado_en         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
  FOREIGN KEY (bodega_id)    REFERENCES bodegas(id),
  FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)
);

-- ── DETALLE DE COMPRA ────────────────────────────────────────
CREATE TABLE compra_detalle (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  compra_id        INT NOT NULL,
  producto_id      INT NOT NULL,
  cantidad         INT NOT NULL,
  precio_unitario  DECIMAL(14,2) NOT NULL,
  total_linea      DECIMAL(14,2) NOT NULL,
  FOREIGN KEY (compra_id)   REFERENCES compras(id)   ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- ── TRIGGER: descuenta stock al vender ───────────────────────
DELIMITER $$
CREATE TRIGGER tr_descuenta_stock
AFTER INSERT ON venta_detalle
FOR EACH ROW
BEGIN
  UPDATE producto_bodega pb
  JOIN ventas v ON v.id = NEW.venta_id
  SET pb.stock = pb.stock - NEW.cantidad
  WHERE pb.producto_id = NEW.producto_id
    AND pb.bodega_id   = v.bodega_id;
END$$

-- ── TRIGGER: suma stock al comprar ───────────────────────────
CREATE TRIGGER tr_suma_stock
AFTER INSERT ON compra_detalle
FOR EACH ROW
BEGIN
  INSERT INTO producto_bodega (producto_id, bodega_id, stock, stock_minimo)
  SELECT NEW.producto_id, c.bodega_id, NEW.cantidad, 0
  FROM compras c WHERE c.id = NEW.compra_id
  ON DUPLICATE KEY UPDATE stock = stock + NEW.cantidad;
END$$
DELIMITER ;

-- ────────────────────────────────────────────────────────────
--  DATOS DEMO
-- ────────────────────────────────────────────────────────────
INSERT INTO bodegas (nombre, direccion, tipo, pasillos) VALUES
('Bodega Central', 'Cra 15 #45-20, Bogotá', 'bodega',  4),
('Bodega Norte',   'Av. 68 #92-15, Bogotá', 'almacen', 3);

INSERT INTO categorias (nombre, color) VALUES
('Papelería',    'purple'),
('Tecnología',   'blue'),
('Insumos',      'amber'),
('Herramientas', 'green');

INSERT INTO proveedores (nombre, nit, contacto, telefono) VALUES
('Distrib. Office S.A.', '900.123.456-1', 'Carlos Ruiz',   '601-555-0101'),
('TechSupply Ltda.',     '800.987.654-2', 'Ana Martínez',  '601-555-0202'),
('Papelería Central',    '700.111.222-3', 'Luis González', '601-555-0303');

INSERT INTO clientes (nombre, apellido, tipo_documento) VALUES
('Público', 'General', 'N/A');

INSERT INTO usuarios (nombre, apellido, email, password, rol) VALUES
('Admin', 'Principal', 'admin@stockflow.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- password: password

INSERT INTO productos (codigo, nombre, marca, precio_compra, precio_venta, categoria_id) VALUES
('TON-HP85A',  'Tóner HP 85A',           'HP',      32000, 48000, 2),
('PAP-RES-A4', 'Papel Carta Resma',       'Rey',      8500, 12000, 1),
('CAB-USBC-1', 'Cable USB-C 1m',          'Genérico', 4500,  8500, 2),
('CIN-EPON-1', 'Cinta Epson LW',          'Epson',    6200,  9800, 3),
('LAP-BIC-AZ', 'Lápiz BIC Azul x12',     'BIC',      2100,  3500, 1),
('DEST-PH-M',  'Destornillador Phillips', 'Stanley',  3800,  6200, 4);

INSERT INTO producto_bodega (producto_id, bodega_id, pasillo, estante, stock, stock_minimo) VALUES
(1, 1, 'A', '2',  2, 10),
(2, 2, 'B', '1',  0, 20),
(3, 1, 'C', '4',  7, 15),
(4, 1, 'A', '5', 45, 10),
(5, 1, 'B', '3', 88, 30),
(6, 2, 'D', '2', 23,  5);
