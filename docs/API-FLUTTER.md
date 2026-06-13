# Margeen API — Referencia para Flutter

Base URL local (tu servidor actual):

```
http://192.168.101.85:8001/api
```

Emulador Android → `http://10.0.2.2:8001/api`  
Celular físico → IP de tu Mac en la misma red WiFi.

---

## Convenciones

| Item | Valor |
|------|-------|
| Formato | JSON (`Accept: application/json`) |
| Auth | `Authorization: Bearer {access_token}` |
| Moneda | Números decimales como string (`"240000.00"`) |
| Fechas | ISO 8601 (`2026-06-10T17:30:00+00:00`) |
| Paginación | Laravel default (`data`, `links`, `meta`) |
| Errores | `{ "message": "..." }` + HTTP status |

### Headers en todas las peticiones autenticadas

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {access_token}
```

### PDF (binario, no JSON)

```
GET /invoices/{id}/pdf
Accept: application/pdf
Authorization: Bearer {access_token}
```

En Dio: `responseType: ResponseType.bytes`

---

## Auth ✅ (ya montada en Flutter)

### POST `/auth/login` — público

**Body:**
```json
{
  "email": "admin@edwin.com",
  "password": "password"
}
```

**200:**
```json
{
  "message": "Inicio de sesión exitoso.",
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "abc...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": { "...": "ver User" }
  }
}
```

**Errores:** `401` credenciales · `403` usuario inactivo

---

### POST `/auth/refresh` — público

**Body:**
```json
{
  "refresh_token": "{refresh_token}"
}
```

**200:**
```json
{
  "message": "Token renovado.",
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "nuevo...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

Refresh token dura **30 días**. Access token **60 min**.

---

### GET `/auth/me` — auth

**200:**
```json
{
  "data": { "...": "ver User" }
}
```

Usar al abrir la app si hay token guardado.

---

### PATCH `/auth/profile` — auth

**Body** (todos opcionales):
```json
{
  "name": "Edwin Admin",
  "document": "1234567890",
  "phone": "3001112233",
  "address": "Sincelejo",
  "avatar_path": null
}
```

---

### POST `/auth/logout` — auth

**Body** (opcional):
```json
{
  "refresh_token": "{refresh_token}"
}
```

---

## Modelo User

```json
{
  "id": 1,
  "company_id": 1,
  "name": "Edwin Admin",
  "email": "admin@edwin.com",
  "document": "1234567890",
  "phone": "3001112233",
  "address": "Barrio El Centro",
  "avatar_path": null,
  "notes": "Dueño del negocio",
  "roles": ["admin"],
  "permissions": [
    "clients.view",
    "clients.create",
    "invoices.create",
    "users.manage"
  ],
  "is_active": true,
  "last_login_at": "2026-06-10T12:00:00+00:00",
  "company": {
    "id": 1,
    "name": "Distribuciones Edwin",
    "document": "900123456",
    "phone": "3001234567",
    "address": "Calle 10 #5-20",
    "invoice_prefix": "FAC",
    "next_invoice_number": 2,
    "default_margin_percent": "25.00"
  }
}
```

### Permisos por rol

| Permiso | Admin | Vendedor |
|---------|:-----:|:--------:|
| `clients.view/create` | ✅ | ✅ |
| `clients.update/delete` | ✅ | ❌ |
| `products.view` | ✅ | ✅ |
| `products.create/update/delete` | ✅ | ❌ |
| `invoices.view` | ✅ | ✅ (solo suyas) |
| `invoices.view-all` | ✅ | ❌ |
| `invoices.create` | ✅ | ✅ |
| `invoices.cancel` | ✅ | ❌ |
| `reports.view` | ✅ | ✅ |
| `reports.view-all` | ✅ | ❌ |
| `users.manage` | ✅ | ❌ |
| `company.manage` | ✅ | ❌ |

En Flutter: `user.can('invoices.create')` para mostrar/ocultar UI.

---

## Facturas ✅

### GET `/invoices` — auth

**Query params (opcionales):**

| Param | Ejemplo | Descripción |
|-------|---------|-------------|
| `client_id` | `1` | Filtrar por cliente |
| `status` | `issued` | `issued` \| `cancelled` |
| `from` | `2026-06-01` | Fecha desde |
| `to` | `2026-06-30` | Fecha hasta |
| `page` | `2` | Paginación |

**200** (paginado):
```json
{
  "data": [ { "...": "ver Invoice" } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 20, "total": 5 }
}
```

Vendedor: solo facturas donde `user_id` = él.  
Admin: todas las de la empresa.

---

### POST `/invoices` — auth · permiso `invoices.create`

**Body:**
```json
{
  "client_id": 1,
  "discount": 0,
  "notes": "Entrega mañana",
  "items": [
    {
      "product_id": 1,
      "quantity": 10,
      "unit_price": 24000,
      "unit_cost": 18000
    }
  ]
}
```

**Línea manual** (sin producto del catálogo):
```json
{
  "client_id": 1,
  "items": [
    {
      "description": "Arroz premium",
      "quantity": 10,
      "unit": "arroba",
      "unit_price": 24000,
      "unit_cost": 18000
    }
  ]
}
```

Si envías `product_id`, se precargan nombre, unidad y precios del catálogo (editables).

**201:**
```json
{
  "message": "Factura creada.",
  "data": { "...": "ver Invoice" }
}
```

---

### GET `/invoices/{id}` — auth

Detalle completo con `client`, `seller`, `items`.

---

### GET `/invoices/{id}/pdf` — auth

Retorna **bytes PDF**. Guardar en disco y compartir con `share_plus`.

```dart
final res = await dio.get(
  '/invoices/$id/pdf',
  options: Options(responseType: ResponseType.bytes),
);
```

---

### PATCH `/invoices/{id}/cancel` — auth · permiso `invoices.cancel` (admin)

Sin body. Marca `status: cancelled`.

---

## Modelo Invoice

```json
{
  "id": 1,
  "number": "FAC-0001",
  "status": "issued",
  "subtotal": "240000.00",
  "discount": "0.00",
  "total": "240000.00",
  "total_cost": "180000.00",
  "total_profit": "60000.00",
  "profit_margin_percent": 25,
  "notes": "Entrega mañana",
  "pdf_path": "invoices/1/FAC-0001.pdf",
  "pdf_url": "http://192.168.101.85:8001/api/invoices/1/pdf",
  "issued_at": "2026-06-10T15:00:00+00:00",
  "client": {
    "id": 1,
    "name": "Edwin Pérez",
    "phone": "3005556677"
  },
  "seller": {
    "id": 2,
    "name": "Carlos Vendedor"
  },
  "items": [
    {
      "id": 1,
      "product_id": 1,
      "description": "Arroz premium",
      "quantity": "10.00",
      "unit": "arroba",
      "unit_price": "24000.00",
      "unit_cost": "18000.00",
      "line_total": "240000.00",
      "line_profit": "60000.00"
    }
  ]
}
```

**Banner de ganancia en UI:** usar `total_profit` y `profit_margin_percent`.

---

## Usuarios (admin) ✅

Requiere permiso `users.manage`.

| Método | Ruta | Acción |
|--------|------|--------|
| GET | `/users` | Listar |
| POST | `/users` | Crear |
| GET | `/users/{id}` | Ver |
| PUT/PATCH | `/users/{id}` | Actualizar |
| DELETE | `/users/{id}` | Desactivar |

### POST `/users`

```json
{
  "name": "Ana Vendedora",
  "email": "ana@edwin.com",
  "password": "password123",
  "document": "1122334455",
  "phone": "3007776655",
  "address": "Sampués",
  "notes": "Ruta sur",
  "role": "vendedor",
  "is_active": true
}
```

`role`: `admin` | `vendedor`

---

## Clientes ✅

| Método | Ruta | Permiso |
|--------|------|---------|
| GET | `/clients` | `clients.view` |
| POST | `/clients` | `clients.create` |
| GET | `/clients/{id}` | `clients.view` |
| PATCH | `/clients/{id}` | `clients.update` (admin) |
| DELETE | `/clients/{id}` | `clients.delete` (admin) |

### GET `/clients` — buscar y listar

**Query params:**

| Param | Ejemplo | Descripción |
|-------|---------|-------------|
| `q` | `edwin` | Busca en nombre, documento y teléfono |
| `per_page` | `20` | Resultados por página |
| `page` | `2` | Paginación |

**Ejemplos:**
```
GET /clients              → todos
GET /clients?q=edwin      → filtrar "Edwin Pérez"
GET /clients?q=3005         → buscar por teléfono
```

**200:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Edwin Pérez",
      "document": "1088123456",
      "phone": "3005556677",
      "address": "Corozal, Sucre",
      "notes": "Cliente frecuente"
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 1 }
}
```

### POST `/clients`

```json
{
  "name": "María López",
  "document": "1098765432",
  "phone": "3008887766",
  "address": "Sincelejo",
  "notes": "Cliente nuevo"
}
```

**Flutter — selector de cliente:**
```dart
// Al escribir en el buscador
final res = await dio.get('/clients', queryParameters: {'q': searchText});
final clients = (res.data['data'] as List).map((e) => Client.fromJson(e));
// Usar client.id al crear factura, no hardcodear
```

---

## Productos ✅

| Método | Ruta | Permiso |
|--------|------|---------|
| GET | `/products` | `products.view` |
| POST | `/products` | `products.create` (admin) |
| GET | `/products/{id}` | `products.view` |
| PATCH | `/products/{id}` | `products.update` (admin) |
| DELETE | `/products/{id}` | `products.delete` (admin) |

### GET `/products` — buscar y listar

| Param | Ejemplo | Descripción |
|-------|---------|-------------|
| `q` | `arroz` | Busca por nombre |
| `active_only` | `1` | Solo productos activos (para factura) |
| `per_page` | `20` | Paginación |

```
GET /products?q=arroz&active_only=1
```

**200:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Arroz premium",
      "unit": "arroba",
      "cost_price": "18000.00",
      "sale_price": "24000.00",
      "is_active": true
    }
  ]
}
```

### POST `/products` (admin)

```json
{
  "name": "Frijol rojo",
  "unit": "bulto",
  "cost_price": 80000,
  "sale_price": 95000,
  "is_active": true
}
```

---

## Reportes ✅

### GET `/reports/dashboard`

| Param | Default | Descripción |
|-------|---------|-------------|
| `from` | Inicio del mes | `YYYY-MM-DD` |
| `to` | Hoy | `YYYY-MM-DD` |

Vendedor: solo sus facturas. Admin: todas (`reports.view-all`).

**200:**
```json
{
  "data": {
    "period": { "from": "2026-06-01", "to": "2026-06-10" },
    "summary": {
      "invoice_count": 5,
      "total_sales": "1200000.00",
      "total_profit": "300000.00",
      "profit_margin_percent": 25
    },
    "top_clients": [
      { "client_id": 1, "client_name": "Edwin Pérez", "total_sales": "480000.00", "invoice_count": 2 }
    ],
    "top_products": [
      { "product_id": 1, "description": "Arroz premium", "total_quantity": "20.00", "total_sales": "480000.00", "total_profit": "120000.00" }
    ],
    "recent_invoices": [
      { "id": 1, "number": "FAC-0001", "client_name": "Edwin Pérez", "total": "240000.00", "total_profit": "60000.00" }
    ]
  }
}
```

---

## Errores comunes

| HTTP | Significado |
|------|-------------|
| 401 | Token inválido/expirado → refresh o re-login |
| 403 | Sin permiso Spatie |
| 404 | Recurso no existe o otra empresa |
| 422 | Validación → `{ "message": "...", "errors": { "campo": ["..."] } }` |

---

## Flutter — mapa de servicios sugerido

```
lib/
├── core/api/
│   ├── dio_client.dart          ✅
│   └── auth_interceptor.dart    ✅
├── data/
│   ├── auth_api.dart            ✅ (auth_repository)
│   ├── invoice_api.dart         → Fase 2
│   ├── client_api.dart          → cuando exista API
│   └── product_api.dart         → cuando exista API
└── features/
    ├── auth/                    ✅
    ├── invoices/                → Fase 2
    ├── clients/                 → Fase 3
    └── products/                → Fase 4
```

### Formato montos en UI

```dart
final total = double.parse(invoice.total);
NumberFormat.currency(locale: 'es_CO', symbol: '\$').format(total);
// $240.000
```

---

## Usuarios demo

```
Admin:    admin@edwin.com    / password
Vendedor: vendedor@edwin.com / password

client_id  = 1  → Edwin Pérez
product_id = 1  → Arroz premium (arroba, costo 18000, venta 24000)
```
