## TALLER 3 - ARQUITECTURA DE DESPLIEGUE Y CLOUD DATABASES

## y Despliegue con PostgreSQL en Render Contenerizacién con Docker

Docker Engine - Docker Compose - PostgreSQL Cloud - Variables de Entorno - Migraciones

Automadticas

Docente:

FABIAN ENRIQUE SUAREZ CARVAJAL

Institucién:

UNIVERSIDAD AUTONOMA DE BUCARAMANGA

Curso:

DESARROLLO BACKEND

## 1. Introduccion y Objetivos Detallados

En las fases previas de este curso disefiaste, estructuraste y probaste localmente una API RESTful de publicaciones inspirada en redes sociales utilizando Laravel. Aprendiste a manejar migraciones, relaciones Eloquent complejas, subida de mdltiples archivos y validaciones avanzadas. Sin embargo, en la industria del desarrollo de software profesional, un sistema no se considera completo hasta que esta ejecutandose en un entorno de produccién accesible a través de internet.

Uno de los retos mas grandes al desplegar aplicaciones es garantizar que el entorno de desarrollo local (tu computadora) sea idéntico al entorno de produccion (el servidor en la nube). Para solucionar esto,

utilizaremos Docker, una tecnologia que empaqueta nuestra aplicacion junto con su propio servidor web


(Nginx) y motor de lenguaje (PHP) en un contenedor aislado. Adicionalmente, desacoplaremos la persistencia de datos utilizando una base de datos relacional robusta y escalable en la nube: PostgreSQL gestionada en Render.

## Objetivo General del Taller

Preparar el proyecto Laravel para operar con bases de datos relacionales robustas, contenerizar la aplicacion utilizando un Dockerfile optimizado, configurar pruebas locales con Docker Compose, y ejecutar un despliegue completo en la nube conectando la API a una

base de datos PostgreSQL gratuita alojada en Render.

## Competencias Clave a Desarrollar

- Comprender los fundamentos de la contenerizacion y el aislamiento de procesos mediante imagenes y contenedores Docker.

- Instalar configurar el motor de Docker y Docker Compose en sistemas operativos Windows, macOS o y Linux.

- Adaptar los archivos de configuracion de Laravel para interactuar nativamente con controladores de bases de datos relacionales avanzados (PostgreSQL).

- Construirun Dockerfile de produccién personalizado que instale extensiones criticas del sistema

- como pdo_pgsql . Disefar scripts de automatizacion de arranque (entrypoints) para ejecutar migraciones en la nube de manera desatendida.

- variables de entorno seguras. configurar y conectar una base de datos PostgreSQL administrada en la nube mediante

- utilizando Postman. y certificar el correcto funcionamiento de los endpoints expuestos en produccion

## 2. Arquitectura de Despliegue con PostgreSQL

Para entender se comunicaran los componentes en produccion, es vital analizar el siguiente diagrama

de arquitectura:

T

|

NUBE DE PRODUCCION (RENDER)

|

|

I

| Web Service (Contenedor)

PostgresqL Database

(Servicio Gestionado) |

|

1

I

1

|

|

|

|

|

|


A diferencia de usar SQLite (que almacena los datos en un archivo local dentro del contenedor y se perderia ante cada reinicio en la nube), el uso de PostgreSQL en Render garantiza que la informacion de usuarios, publicaciones, comentarios y likes persista de forma segura y profesional en un servidor de bases de datos dedicado.

## 3. Adaptacion del Proyecto Laravel para PostgreSQL

Antes de empaquetar la aplicacion en Docker, debemos verificar que Laravel esté preparado para comunicarse con PostgreSQL en lugar de SQLite o MySQL.

## Paso 1 - Revisar la configuracion en “config/database.php’

Abre el archivo config/database.php en tu proyecto y aseglrate de que la conexion por defecto esté configurada para leerse desde las variables de entorno:

```
‘default’ env( 'DB_CONNECTION', 'pgsql'),
```

Verifica también el bloque de configuracién para pgsql contenga los parametros estandar que utilizara que

para las credenciales provenientes del servidor: mapear

Laravel

```
'pgsql' => [
‘driver’ 'pgsql’,
‘url! env ( 'DATABASE_URL'),
=> env('DB_HOST', '127.0.0.1'),
env('DB_PORT', '5432'),
'forge'),
'forge'),
env('DB_PASSWORD', ''),
=>
‘prefix’
‘pref ndexes' =>
'search_path' =>
```


## 4. Instalacion de Docker y Docker Compose

ya Docker instalado verificado, puedes avanzar a la Seccion 5. Si necesitas instalarlo, sigue las posees y

directrices tu sistema operativo:

Si

## Guia de Instalacion Rapida

- \* Windows / macOS: Descarga e instala Docker Desktop desde el sitio oficial docker.com. En Windows, asegUrate de tener activada la caracteristica WSL 2 (Windows Subsystem for Linux).

- Linux (Ubuntu / Debian): Ejecuta el siguiente bloque de comandos en tu terminal para configurar los repositorios oficiales e instalar Docker Engine junto con Docker Compose:

sudo apt-get update sudo apt-get install -y ca-certificates curl gnupg lsb-release sudo mkdir /etc/apt/keyrings -p curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /et echo "deb [arch=\$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg sudo apt-get update

sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

Verificacién de la instalacién

Ejecuta estos comandos en tu consola para confirmar que todo funciona correctamente:

docker --version

docker version compose

## 5. Creacion del Dockerfile y Script de Arranque

Para nuestra imagen de PHP en Docker pueda comunicarse sin problemas con PostgreSQL, es que

indispensable instalar la extension nativa pdo_pgsql .


*Paso 2 - Crear el script de entrada*


Este archivo es fundamental para entornos en la nube, ya que permite limpiar cachés, generar enlaces simbolicos de almacenamiento ejecutar automaticamente las migraciones en la base de datos cada vez que y el contenedor arranca.

Crea el archivo docker-entrypoint.sh en la raiz del proyecto:

```
#!/bin/sh
set -e
Limpiando y optimizando cachés de Laravel ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache
Ejecutando migraciones de base de datos en PostgreSQL
php artisan migrate --force
echo " Creando enlace simb6lico del storage publico ==="
php artisan storage:link || true
Iniciando servidor PHP integrado en puerto ${PORT:-10000} =
exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
```

## Paso 3 - Configurar para pruebas locales

Si deseas probar el funcionamiento completo conectando tu aplicacion Laravel con una base de datos

PostgreSQL de forma local antes de subirla a la nube, crea el archivo docker-compose.yml enla


Para levantar este entorno local de prueba, ejecuta en tu terminal:

docker

compose -d --build up

## 6. Aprovisionamiento de PostgreSQL en Render

Ahora configuraremos la base de datos oficial en la plataforma gratuita de Render para que reciba las

conexiones de produccion.

- 1. Ingresa a tu cuenta en

- 2. Haz clic en el superior New +.

- 3. Selecciona la opcién PostgreSQL.

- 4. Completa los datos del servicio de base de datos:

- o Name: facebook-db-prod o Database: facebook_api (puedes dejar el valor por defecto). Hears Fh nicar [nnadac daiar al valar nar dafacta)


PUT

- © Region: Selecciona la region geografica que prefieras (ej. Oregon (US West) o Ohio (US East)).

- o Instance Type: Selecciona el plan Free.

- 5. Haz clic en el botén inferior Create Database.

## Datos de Vitales

Una vez creada la base de datos, Render te redirigira a los detalles del servicio. Debes copiar y guardar temporalmente los siguientes datos de acceso, ya que los necesitaras en la siguiente seccion:

- « Internal Database URL (para conexiones internas seguras dentro de Render).

- Host (servidor de base de datos).

- Port (generalmente 5432 ).

- Database Name (nombre de la BD).

- User (nombre de usuario).

- Password segura generada por Render).

## 7. Despliegue del Web Service y Vinculacién de Variables

Con la base de datos lista, procederemos a desplegar el codigo fuente de nuestra API vinculandolo con PostgreSQL.

- 1. Asegurate de versionar y subir todos tus cambios mas recientes a tu repositorio en GitHub:

git add . git commit -m "config: configurar Dockerfile, entrypoint y soporte PostgreSQL" git push origin main

- 2. En el Dashboard de Render, haz clic en el botén New + y selecciona Web Service.

- 3. Selecciona la de conectar un repositorio Git y elige tu repositorio del proyecto.

- 4. Configura los parametros del Web Service:

- o Name: laravel-facebook-api-prod (debe ser un nombre tnico globalmente).

- o Environment: Docker (Render automaticamente el archivo Dockerfile).

- © Region: Selecciona exactamente la misma donde creaste tu base de datos PostgreSQL en el paso anterior.

- o Branch: main principal de tu repositorio).


- o Instance Type: Selecciona el plan Free.

- 5. Baja hasta la seccién Environment Variables y haz clic en Add Environment Variable para registrar cada una de las siguientes claves de configuracion:

| Key (Variable de |   |
| --- | --- |
| Value (Valor a Configurar) | Descripcion / Detalle |
| Entorno) |   |
| FacebookAPI APP_NAME | Nombre identificativo del sistema. |
| production APP_ENV | Indica a Laravel que opera en modo |
|   | produccién. |
| APP_DEBUG false | Desactiva la traza de errores detallada por |
|   | seguridad. |
| base64:... APP_KEY | Obligatorio: Ejecuta localmente php |
|   | artisan key:generate --show y |
|   | pega aqui la llave generada. |
| APP_URL | La URL publica que te asignara Render. |
| servicio.onrender.com |   |
| pgsql DB_CONNECTION | Define que el motor de base de datos es |
|   | PostgreSQL. |
| DB_HOST Pega el valor del Internal Host provisto | Direccion del servidor PostgreSQL en la |
| por Render en tu DB. | nube de Render. |
| DB_PORT 5432 | Puerto estandar de PostgreSQL. |
| DB_DATABASE Nombre de la BD en Render (ej. | Nombre del esquema de base de datos |
| facebook_api ). | asignado. |
| DB_USERNAME Usuario de la BD en Render (ej. | Credencial de usuario autorizado. |
| fb_user ). |   |
| DB_PASSWORD Contrasena provista por Render. | Clave de acceso secreta a la base de datos. |

6. Haz clic en el inferior Create Web Service.

## Monitoreo del Despliegue

Render iniciara el proceso de compilacion descargando la imagen de PHP, instalando las extensiones de PostgreSQL y levantando el servidor. Puedes revisar los registros (logs) en tiempo real desde la consola del navegador. Una vez finalice con éxito, el estado cambiara a un indicador verde de Live.


## 8. Pruebas y Validacion Funcional en Postman

Para comprobar que tu API esta respondiendo correctamente desde la nube y almacenando los datos en PostgreSQL:

- 1. Copia la URL publica HTTPS de tu Web Service en Render (por ejemplo: api-prod.onrendex.com ).

- 2. Abre Postman actualiza la variable de entorno {{base_url}} con esta nueva direccién URL. y

- 3. Realiza una peticion de prueba utilizando el método POST a {{base_url}}/api/posts enviando texto e imagenes en el cuerpo (form-data).

- 4. Verifica la respuesta devuelva un codigo HTTP 201 Created y que al consultar mediante GET a que {{base_url}}/api/posts obtengas el listado completo persistido en tu base de datos PostgreSQL en la nube.

## ¢ Comportamiento del Plan

Recuerda que en el plan gratuito de Render, los servicios entran en modo suspension tras 15 minutos sin recibir peticiones. La primera consulta que realices tras un periodo de inactividad puede tardar unos 30 a 50 segundos mientras el contenedor vuelve a encenderse; las peticiones posteriores responderan de inmediato.

## 9. de Errores Frecuentes (Troubleshooting)

## X Error: PDOException: could not find driver (pgsql)

Causa: El contenedor Docker se construy6 sin las extensiones necesarias para conectar con PostgreSQL. Solucién: Asegurate de que tu Dockerfile incluyalainstruccion RUN docker-php-ext-install pdo_pgsql pgsql vy realiza un nuevo deploy en Render.

## XX Error de conexién a la Base de Datos (Connection Refused / Timed Out)

Causa: Las credenciales de DB_HOST , usuario o contrasefia en las Environment Variables de Render no coinciden con los datos de tu PostgreSQL.

Solucién: Copia los datos exactos del panel de tu base de datos en Render y asegtirate de usar el Internal Host si la app y la base de datos estan en la misma region de Render.


Causa: Falta configurar la variable APP_KEY en el panel de Render. Solucién: Genera una clave con php artisan key:generate --show en tu entorno local y agrégala alas variables de entorno de Render.

## 10. Checklist Final - Entrega del Taller 3

| Cri | io de Evalua | Evidencia | Requ |
| --- | --- | --- | --- |
| Configuracion del proyecto Laravel adaptada a O |   | Revision del archivo |   |
| PostgreSQL |   | config/database.php |   |
| Dockerfile configurado correctamente con la 0 |   | Cédigo del archivo Dockerfile en el repositorio |   |
| extension pdo_pgsql |   |   |   |
| Script docker-entrypoint.sh creado para O |   | Script ejecutable en la raiz del proyecto |   |
| automatizar migraciones |   |   |   |
| Base de datos PostgreSQL creada y activa en Render 0 |   | Captura del Dashboard de Render |   |
| (Plan Free) |   |   |   |
| Variables de entorno correctamente vinculadas en el 0 |   | Captura de la | Environment en Render |
| Web Service de Render |   |   |   |
| Despliegue exitoso (Estado Live) y migraciones O |   | URL publica de Render activa |   |
| ejecutadas automaticamente |   |   |   |
| Pruebas funcionales de la API en produccién usando O |   | Captura de pantalla de Postman con respuestas |   |
| Postman |   | 200/201 OK |   |

@ iFelicitaciones! Has completado con éxito el ciclo de desarrollo backend profesional y despliegue cloud con

contenedores y bases de datos relacionales.
