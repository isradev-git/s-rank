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

**Los mensajes de validación salen en inglés.** Laravel 12 no trae traducciones al
español. Se resuelve en la fase 1.1 publicando `lang/es`.

## Pendiente de limpiar

Dos cuentas creadas para probar:

```sql
DELETE FROM users WHERE email IN ('prueba-despliegue@israelzamora.es');
```

Y `hola@israelzamora.es` tiene una contraseña temporal que se escribió en una
conversación: **cámbiala**.

## Recordatorio

`old/database/database.sqlite` y `old/database/database.2026-05-backup.sqlite` son la
**única copia** de los datos originales de FitLoop y no están en git. Hay que respaldarlos
fuera del proyecto antes de borrar `old/` al cerrar la fase 1.
