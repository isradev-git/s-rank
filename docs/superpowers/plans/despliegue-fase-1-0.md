# Despliegue de la fase 1.0 a Ginernet

**Hecho el 10 de agosto de 2026.** La API está en producción y verificada de punta a punta.

    https://s-rank.israelzamora.es

## Lo que hay montado

| | |
|---|---|
| Subdominio | `s-rank.israelzamora.es` → `public_html/s-rank.israelzamora.es/`, con SSL |
| Base de datos | `israelza_srank`, usuario `israelza_glitchbane` (la contraseña solo en `.env`) |
| Correo saliente | `soc@israelzamora.es` por `mail.israelzamora.es:465` |
| Servidor | LiteSpeed, PHP con el Document Root en `public/` |

El volcado inicial se generó con:

```bash
cd backend
mysqldump -u srank -psrank_local --no-tablespaces srank > ../srank-inicial.sql
```

Ese fichero vive en la raíz del proyecto y **no** dentro de `backend/`: desde ahí lo
copiaría `build-deploy.sh` a `deploy/`, y `deploy/` se sube a una carpeta que sirve por
web. `*.sql` está en el `.gitignore` y el script aborta si alguno se cuela.

## Verificado contra el servidor

- Login y registro emiten token.
- `GET /api/system/today` genera las misiones del día al vuelo, en castellano.
- `POST /api/water` devuelve el bloque `system`: XP, misión completada, logro desbloqueado
  y progreso actualizado en la misma respuesta.
- `GET /api/system/profile` y `/achievements`: cuatro estadísticas, 40 logros, 10/12/10/8
  por rareza.
- Recuperación de contraseña: el código de seis cifras llega al buzón, cambia la
  contraseña, **cierra todas las sesiones abiertas** y no se puede reutilizar.
- Las 16 tablas de producción tienen exactamente las mismas filas que en local.

## Los cuatro fallos que solo aparecieron aquí

Ninguno lo cazaron los tests, y por el mismo motivo: la suite corre sobre SQLite y sin red.

**1 · Sanctum no admitía UUID.** `personal_access_tokens.tokenable_id` lo crea `morphs()`
como `bigint`, pero los usuarios tienen UUID. MySQL truncaba y `createToken()` reventaba:
**nadie podía iniciar sesión**. SQLite no tipa las columnas y se lo tragaba.
→ migración `2026_08_10_000005`, y `UuidSchemaTest` vigila las 14 columnas que apuntan a
un usuario.

**2 · Un 401 salía como 500.** Sin cabecera `Accept: application/json`, el middleware de
autenticación intentaba redirigir a la ruta `login`, que en una API no existe, y reventaba
antes de que el manejador de excepciones pudiera intervenir.
→ `redirectGuestsTo(null)` en `bootstrap/app.php`, más `ApiAlwaysJsonTest`.

**3 · `forgot-password` delataba qué correos tienen cuenta.** Prometía responder lo mismo
existiera o no la cuenta, pero si el envío fallaba la excepción salía como 500: correo
desconocido 200, correo registrado 500.
→ el fallo de envío se registra y la respuesta no cambia.

**4 · `MAIL_SCHEME=ssl` no existe.** Symfony Mailer solo acepta `smtp` y `smtps`. Ni `tls`
ni `ssl`. No falla al arrancar: falla la primera vez que se manda un correo.
→ 587 es `smtp`, 465 es `smtps`. `MailSchemeTest` valida el `.env` antes de empaquetar.

## Cosas a las que estar atento

**`[2002] No such file or directory` al conectar a MySQL.** Apareció una vez el 10 de
agosto a las 12:25 y no volvió. Es el socket Unix rechazando la conexión un instante. Si
se repite, cambiar `DB_HOST=localhost` por `DB_HOST=127.0.0.1`: fuerza TCP y se lo salta.

## Repaso previo a la fase 1.1

Antes de escribir Android se revisó lo desplegado. Las cuentas de prueba y la contraseña
temporal de `hola@israelzamora.es` quedaron resueltas. Lo demás cambió en el código y
**ya está subido y verificado en producción**:

| Qué | Por qué |
|---|---|
| Fuera `routes/web.php` y `EnsureAuthenticated` | `/informe-salud` devolvía 500 en producción por dos motivos a la vez: redirigía a una ruta `login` inexistente y su middleware validaba el token sin autenticar a nadie. Ningún test pedía esa ruta. La lógica sigue en `ReportController`, sin ruta, para la fase 1.5 |
| Fuera la cookie `fitloop_token` | Llevaba el token de Sanctum en claro (estaba excluida del cifrado en `bootstrap/app.php`) y sin `Secure`, siete días. Solo servía a la ruta de arriba |
| `login` compara siempre un hash | Sin usuario se saltaba bcrypt y la respuesta volvía mucho antes: el tiempo delataba qué correos existen, justo lo que `forgot-password` evita |
| `lang/es/validation.php` | Los errores de validación salían en inglés. `APP_LOCALE` por defecto es `es` en `config/app.php`, así que el `.env` del servidor no necesita tocarse |
| `s-rank`, no `rank-s` | `build-deploy.sh` y `.env.produccion.example` nombraban un subdominio que no existe. El script decía que subieras a `public_html/rank-s.israelzamora.es/` |
| `MAIL_PORT=465` + `MAIL_SCHEME=smtps` en el ejemplo | El ejemplo decía 587/`smtp` mientras producción usa el 465. Reconstruir el `.env` desde el ejemplo repetía el fallo del correo |

Comprobado contra el servidor después de subir:

| Comprobación | Resultado |
|---|---|
| `/informe-salud` y `/informe-salud/pdf` | 404 — antes 500 |
| `/api/user` sin token | 401 con cuerpo JSON |
| Login sin contraseña | 422 y «Falta la contraseña.» — confirma que `lang/es` subió, que el locale resuelve a `es` sin tocar el `.env`, y que no quedaba configuración cacheada vieja |
| `Set-Cookie` | ninguna, en ninguna respuesta |
| Límite de intentos | vivo: `x-ratelimit-limit: 5` |
| Cabeceras | HSTS un año, `X-Frame-Options`, `X-Content-Type-Options` |

Lo único que no se pudo comprobar desde fuera es la respuesta de un login **correcto**,
que es donde vivía la cookie: hace falta una cuenta real y una contraseña no se escribe en
una conversación. El código ya no la emite y ninguna otra respuesta trae `Set-Cookie`.

⚠️ El FTP sobrescribe pero no borra: `app/Http/Middleware/EnsureAuthenticated.php` sigue
en el servidor si no se ha borrado a mano. No lo referencia nada, pero es la vía de la
cookie esperando a que alguien la reenganche.

## Recordatorio

`old/database/database.sqlite` y `old/database/database.2026-05-backup.sqlite` son la
**única copia** de los datos originales de FitLoop y no están en git. Hay que respaldarlos
fuera del proyecto antes de borrar `old/` al cerrar la fase 1.
