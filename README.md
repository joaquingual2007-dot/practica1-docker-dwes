# Práctica 1 (UD1) - Arranque del entorno de desarrollo con Docker

## Descripción

Este proyecto crea un entorno web con tres contenedores conectados mediante Docker Compose:

- `web`: Nginx, sirve la aplicación en el puerto `8080`
- `php`: PHP-FPM, ejecuta la lógica de la aplicación
- `db`: MySQL, base de datos del proyecto

La página principal comprueba que PHP y MySQL funcionan correctamente y muestra la información del entorno.

## Requisitos

- Docker instalado
- Docker Compose disponible
- Navegador web

## Estructura del proyecto

```text
.
├── docker-compose.yml
├── nginx/
│   └── default.conf
├── php/
│   └── Dockerfile
├── src/
│   └── index.php
├── README.md
└── .gitignore
```

## Cómo levantar el entorno

Desde la raíz del proyecto, ejecuta:

```bash
docker compose up -d --build
```

Esto construye la imagen de PHP y levanta los contenedores en segundo plano.

## Acceso a la aplicación

Abre en el navegador:

```text
http://localhost:8080
```

## Qué muestra la web

La página principal muestra:

- estado del servidor web
- versión de PHP
- estado de la base de datos
- verificación de la conexión PDO con MySQL

## Cómo detener el entorno

```bash
docker compose down
```

Si quieres eliminar también los volúmenes de datos:

```bash
docker compose down -v
```

## Resultado esperado

La práctica permite comprobar que:

- Nginx está sirviendo la página
- PHP está ejecutándose correctamente
- MySQL responde y la aplicación puede conectarse a la base de datos

## Observación

La primera vez que se inicie MySQL puede tardar unos segundos en prepararse. Si aparece un aviso temporal, recarga la página.

## Capturas del resultado

### Página principal de la aplicación

<img width="1343" height="613" alt="Captura de pantalla 2026-09-28 132155" src="https://github.com/user-attachments/assets/4477b8df-e99e-4667-98ab-3d273f58242a" />

### Entorno Docker en ejecución

Los contenedores `web`, `php` y `db` quedan levantados y funcionando en el puerto `8080` con la base de datos disponible.

<img width="1377" height="180" alt="Captura de pantalla 2026-09-28 132551" src="https://github.com/user-attachments/assets/c691b142-35a6-4221-a0a6-610b1b7ce0ae" />

