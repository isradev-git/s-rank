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

Antes de escribir Android se revisó lo desplegado. Lo que cambió en el código **ya está
subido y verificado en producción**:

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

Faltaba por comprobar la respuesta de un login **correcto**, que es donde vivía la
cookie: hace falta una cuenta real y una contraseña no se escribe en una conversación.
Eso y el resto de lo pendiente se cerró al día siguiente.

## Verificado el 11 de agosto, con una cuenta de verdad

Lo que quedaba abierto necesitaba poder entrar. En vez de reutilizar la contraseña
temporal de `hola@israelzamora.es`, que vivía en el historial de un chat que ni caduca ni
se puede retirar, la cuenta **se borró y se volvió a crear**. No tenía ni un dato: no está
en `srank-inicial.sql`, se había creado probando el despliegue. El borrado fue por
phpMyAdmin, en dos sentencias:

```sql
DELETE FROM personal_access_tokens
 WHERE tokenable_type = 'App\\Models\\User'
   AND tokenable_id = (SELECT id FROM users WHERE email = 'hola@israelzamora.es');

DELETE FROM users WHERE email = 'hola@israelzamora.es';
```

Las dos, en ese orden. Todo lo del usuario cae solo —las migraciones declaran
`cascadeOnDelete` y MySQL las respeta—, **menos los tokens**: `personal_access_tokens` usa
`morphs()`, que no lleva clave ajena. Sin la primera sentencia quedan filas huérfanas. No
autentican a nadie, porque Sanctum resuelve el usuario y le sale nulo, pero son basura.

La contraseña de la cuenta recreada no se escribió en ninguna conversación. Cuando haga
falta entrar, se pide con `forgot-password`.

| Comprobación | Resultado |
|---|---|
| El **mismo** token, por `Authorization: Bearer` | 200 |
| El **mismo** token, solo como cookie `fitloop_token` | 401 |
| Otros nombres de cookie (`token`, `auth_token`, `laravel_session`) | 401 |
| Login **correcto** | 200, y ni una `Set-Cookie` |
| Cuerpo del login | solo `access_token`, `token_type`, `user_name`, `is_admin` |
| El código de seis cifras llega al buzón | sí |
| `reset-password` con el código | 200, y el token de la sesión abierta pasa a 401 |
| Reutilizar el mismo código | 422, en español |

La cookie es la comprobación que importa, y hay que hacerla así: **el mismo token que da
200 por cabecera tiene que dar 401 por cookie**. Mandar una cookie inventada devuelve 401
igual que no mandar nada, así que no demuestra nada.

`app/Http/Middleware/EnsureAuthenticated.php` se borró a mano del servidor. El FTP
sobrescribe pero no borra, así que había sobrevivido al despliegue que lo quitó del
repositorio. Nada lo referenciaba, pero era la vía de la cookie esperando a que alguien la
reenganchara.

## La fuga que solo se ve con un cronómetro

`forgot-password` responde lo mismo exista o no el correo, y lo cumple palabra por
palabra. Cronometrado no lo cumplía en absoluto:

| | Antes | Después del arreglo |
|---|---|---|
| Correo registrado | 1,178 s | 0,492 s · 0,442 s |
| Correo desconocido | 0,186 s | 0,426 s |
| Diferencia | **0,992 s** | ~0,04 s |

Un segundo de diferencia distingue una cuenta registrada de una que no lo está, con una
sola petición y sin margen de duda. Es la misma fuga que se tapó en `login()` con
`HASH_SENUELO`, pero mucho mayor: allí eran milisegundos de bcrypt, aquí el saludo al SMTP
entero. La rama que no encuentra usuario no hacía nada y volvía enseguida.

Arreglado en `AuthController::forgotPassword()` por las dos causas a la vez: el envío del
correo sale de la petición con `app()->terminating()` —sin cola ni worker, que en Ginernet
no hay— y la rama del correo desconocido paga un bcrypt señuelo. Los 40 ms que quedan son
menores que la variación entre dos peticiones idénticas, que se llevan 50 ms entre sí.

Se subió por FTP el fichero suelto: un controlador no está cacheado, así que surte efecto
en la siguiente petición sin tocar nada más.

## Copias de seguridad de producción

**La base de datos `srank` de Ginernet no tiene copia automática de nada.** Es el único
punto del proyecto donde un fallo no se puede deshacer: hay 148 entrenos y 197 comidas
migrados que ya no están en ningún otro sitio en formato utilizable, y son datos de salud.
El hosting es FTP sin SSH, así que no hay `mysqldump` programado que valga.

El procedimiento, una vez al mes y a mano:

1. phpMyAdmin de Ginernet → base `srank` → **Exportar** → SQL, estructura y datos.
2. Guardar el `.sql` **fuera del proyecto** (no en el repositorio: `*.sql` está en el
   `.gitignore` precisamente porque lleva hashes de contraseña y datos de salud dentro).
3. Apuntar la fecha aquí abajo.

| Fecha | Quién |
|---|---|
| — | pendiente de la primera |

`ponytail:` manual y mensual. Con un usuario, perder como mucho un mes de registros es un
riesgo aceptable frente a montar un cron con retención. Cuando haya más usuarios, o cuando
un mes de datos empiece a doler, esto se convierte en una tarea programada del panel.

## Cabeceras, y cómo comprobarlas

`backend/public/.htaccess` manda `Content-Security-Policy`, `X-Content-Type-Options`,
`Referrer-Policy` y `Strict-Transport-Security`. **Ningún test las cubre**: en local no hay
Apache en medio, Vite y Laravel hablan directamente. Se comprueban contra el servidor:

```bash
curl -sI https://s-rank.israelzamora.es/ | grep -iE "content-security|nosniff|referrer|strict-transport"
```

Tienen que salir las cuatro. Si Ginernet vuelve a inyectar su propia CSP, aparecerá dos
veces la cabecera y el navegador aplica **la más restrictiva de las dos**: eso rompe la
aplicación en silencio, y esta orden es la forma de verlo.

## Verificado el 18 de agosto, contra el servidor

- **Las cuatro cabeceras salen, y cada una una sola vez.** Ginernet no vuelve a inyectar su
  CSP por detrás de la del `.htaccess`.
- **La sesión dura 30 días de verdad:** `s-rank-session` viene con `Max-Age=2592000`,
  `secure`, `httponly` y `samesite=lax`. Antes eran 7200 segundos, dos horas, y era el
  valor por defecto de Laravel: nadie lo había puesto en el `.env`.
- **`XSRF-TOKEN` sí es legible por JavaScript** —no lleva `httponly`— y tiene que seguir
  así: `api.ts` la lee para mandar `X-XSRF-TOKEN` en cada escritura. La que guarda la
  sesión es la otra, y esa no se lee desde el navegador.

Las tres solo se ven aquí. La suite corre con `SESSION_DRIVER=array` y sin Apache en medio,
así que ni la duración de la cookie ni las cabeceras pasan por ningún test.

### Y la cabecera que rompió el login el mismo día

`Referrer-Policy: no-referrer` **dejó a todo el mundo fuera de la aplicación**, con las
cuatro cabeceras saliendo perfectas en el `curl`. El síntoma: `POST /api/auth/login`
contestaba 200 y el `GET /api/user` siguiente, 401. El botón se quedaba en «ENTRANDO…».

Sanctum decide si una petición viene del frontend con `referer ?: origin`
(`EnsureFrontendRequestsAreStateful.php:75`). Un `fetch` de tipo GET **no manda `Origin`**
—solo lo llevan los que no son GET— así que el `Referer` era el único de los dos que
llegaba. Al quitarlo, la petición deja de ser stateful, la cookie de sesión se ignora y
Sanctum busca un Bearer que en el navegador no existe.

Reproducido contra el Laravel local, con la misma cookie y cambiando solo esa cabecera:

| `GET /api/user` | |
|---|---|
| con `Referer` | 200 |
| sin `Referer` ni `Origin` | 401 |

Arreglado con `Referrer-Policy: same-origin`, que manda el `Referer` completo dentro del
dominio y nada fuera: la intención de no filtrar URLs a terceros se mantiene entera.

**Lo que hay que aprender de esto:** que las cuatro cabeceras salgan en el `curl` demuestra
que están puestas, **no que la aplicación funcione**. La comprobación de verdad es entrar
con una cuenta y ver `hoy`. Cualquier cabecera nueva se prueba así antes de darla por buena.

## Recordatorio

`old/database/database.sqlite` y `old/database/database.2026-05-backup.sqlite` son la
**única copia** de los datos originales de FitLoop y no están en git. Hay que respaldarlos
fuera del proyecto antes de borrar `old/` al cerrar la fase 1.

Y `srank-inicial.sql` en la raíz del repositorio es el volcado de aquella migración, no un
respaldo vivo: no refleja nada de lo registrado desde el 10 de agosto de 2026.
