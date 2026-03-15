# 📦 StockFlow · PHP + MySQL
**Deploy en Railway en menos de 10 minutos — gratis**

---

## 🚀 Despliegue en Railway (paso a paso)

### Paso 1 — Subir a GitHub
1. Crea una cuenta en [github.com](https://github.com) si no tienes
2. Crea un repositorio nuevo (ej: `stockflow`)
3. Sube todos estos archivos al repositorio

### Paso 2 — Crear proyecto en Railway
1. Ve a [railway.app](https://railway.app) → **Start a New Project**
2. Selecciona **Deploy from GitHub repo** → conecta tu GitHub → elige `stockflow`
3. Railway detecta automáticamente el `nixpacks.toml` y configura PHP

### Paso 3 — Agregar MySQL
1. En tu proyecto Railway → **+ New** → **Database** → **MySQL**
2. Railway crea la base de datos y conecta las variables automáticamente
3. No necesitas configurar nada más — el `config/db.php` lee las variables de entorno

### Paso 4 — Obtener tu URL pública
1. En tu servicio → pestaña **Settings** → **Domains** → **Generate Domain**
2. Te da una URL tipo: `stockflow-production.up.railway.app`

### Paso 5 — Instalar la base de datos
Abre en el navegador:
```
https://tu-url.up.railway.app/api/install?token=stockflow2024
```
Esto crea todas las tablas y carga los datos demo. Solo se hace una vez.

### ✅ Listo
Tu sistema está en línea en `https://tu-url.up.railway.app`

---

## 📁 Estructura del proyecto
```
stockflow/
├── nixpacks.toml        ← Configuración de build para Railway (PHP 8.2)
├── config/
│   └── db.php           ← Conexión MySQL (lee variables de Railway automático)
├── api/
│   ├── dashboard.php    ← GET /api/dashboard
│   ├── productos.php    ← GET/POST/PUT/DELETE /api/productos
│   ├── ventas.php       ← GET/POST /api/ventas
│   ├── compras.php      ← GET/POST /api/compras
│   ├── bodegas.php      ← GET/POST /api/bodegas
│   ├── categorias.php   ← GET /api/categorias
│   ├── proveedores.php  ← GET/POST /api/proveedores
│   ├── alertas.php      ← GET /api/alertas
│   └── install.php      ← GET /api/install?token=... (instala BD)
├── sql/
│   └── schema.sql       ← Esquema completo con triggers y datos demo
└── public/
    ├── index.php        ← Router principal (API + frontend)
    └── index.html       ← App completa (HTML/JS/CSS)
```

---

## 💰 Costos Railway
- **Plan Starter (gratis)**: $5 USD de crédito al mes → suficiente para demo
- **Plan Pro ($5/mes)**: para producción real con clientes
- MySQL incluido en ambos planes

## 🔧 Para cada cliente nuevo
1. Crea un nuevo proyecto en Railway (~5 min)
2. Conecta el mismo repositorio GitHub
3. Agrega MySQL
4. Llama `/api/install` con el token
5. Dale la URL al cliente

## 🔐 Cambiar token de instalación
En Railway → Variables → agregar:
```
INSTALL_TOKEN=tu_token_secreto
```

---

## 📈 Cuando quieras escalar a SaaS
El siguiente paso es agregar un campo `empresa_id` a cada tabla y manejar
múltiples clientes en una sola instancia. El código base ya está listo para eso.
