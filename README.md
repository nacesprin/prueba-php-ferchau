### Instalación
Se ha usado SQLite3 para la base de datos por simplificar la prueba

- `sudo apt-get install software-properties-common`
- `sudo add-apt-repository ppa:ondrej/php`
- `sudo apt update`
- `sudo apt install php8.1 php8.1-sqlite3`
- `composer install` (debe tener instalado composer)
- `php -S localhost:8080 public/index.php` para lanzar servidor de pruebas en <http://localhost:8080/>

### Usar postman para lanzar los endpoints configurados a la URL __<http://localhost:8080/>__

Descargar y usar esta [colección de postman](postman_collection.json)

### Estructura de tablas propuesta

```
Estudio: Id,nombre
Asignatura: id, nombre,idEstudio
Profesor:  id,nombre
Profesor-Asignatura: id, idprofesor,idasignatura
```

### Sentencias SQL para crear las tablas

```
CREATE TABLE estudio (
 id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
 nombre TEXT NOT NULL
);
CREATE UNIQUE INDEX estudio_nombre_IDX ON estudio (nombre);
```

```
CREATE TABLE asignatura (
 id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
 nombre TEXT NOT NULL,
 id_estudio INTEGER NOT NULL,
 FOREIGN KEY(id_estudio) REFERENCES estudio(id) on delete restrict on update restrict
);
CREATE INDEX asignatura_nombre_IDX ON asignatura (nombre);
```

```
CREATE TABLE profesor (
 id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
 nombre TEXT NOT NULL
);
CREATE INDEX profesor_nombre_IDX ON profesor (nombre);
```

```
CREATE TABLE profesor_asignatura (
 id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
 id_profesor INTEGER NOT NULL,
 id_asignatura INTEGER NOT NULL,
 FOREIGN KEY(id_profesor) REFERENCES profesor(id) on delete cascade on update cascade,
 FOREIGN KEY(id_asignatura) REFERENCES asignatura(id) on delete cascade on update cascade
);
```

### Seguridad

La API se podría completar incluyendo un mínimo de seguridad autenticando las solicitudes a través de basic-auth usando un token de seguridad compuesto por el usuario:contraseña codificado en base64 en la cabecera de la solicitud, o por medio de un token alfanumérico a modo de api-password creado de forma secreta para el usuario que está consumiendo la API. Podría devolver en cualquier caso un código de error 401 ó 403 para los casos en que la verificación de la autenticación se haya realizado sin éxito.

### Cómo organizaría los datos

Para tener mejor organizado el código, podría crearse ficheros de modelos para que cada uno estuviera relacionado con cada entidad, para así separar un poco el código de las rutas con respecto a lo que se hace con los datos dentro de cada solicitud a la API. Se podría organizar a través de las carpetas `src/Models/Estudio.php`, `src/Models/Asignatura.php`, `src/Models/Profesor.php`, dejando las rutas de la API sólo para recibir parámetros y devolver los resultados en `Json`, además de para hacer las llamadas a los modelos.

### Posibles mejoras / errores con la estructura propuesta

En la estructura propuesta debería de establecerse restricciones de claves foráneas, con idea de que cada tabla estuviera relacionada con un campo clave de otra tabla y no se pudiera establecer cualquier valor de forma aleatoria, pues si no se pierde la sincronía de los datos entre las tablas.

Habría que definir los campos índices de cada tabla para que las búsquedas por campos de texto sean efectivas. Igualmente, los campos índices que sean únicos deben ser definidos, como `estudio.nombre`, el cual no puede ser repetido en toda la tabla.

Por supuesto, habría que agregar más campos en cada una de las tablas, como por ejemplo, campos de fecha de creación o actualización del registro, siendo este último muy interesante con idea de poder controlar las cachés con idea de que las consultas a los datos se hagan en el menor tiempo posible. Además de los campos de datos adicionales necesario para un modelo completo de datos.

También habría que crear mínimamente las tablas:

- `alumno [ id | nombre | apellidos | fecha-nacimiento | ...]`
- `alumno_asignatura [id | id_alumno | id_asignatura]`

Yo mejoraría la tabla `asignatura`, añadiendo un campo `curso` para indicar si es de 1º, 2º, 3º año, etc. Si se aplica este cambio, por medio de la relación `alumno_asignatura` ya se sabría qué alumno está en qué estudio, asignatura y año de la carrera.

### Anotaciones

Como se observará, la API está incompleta. Sólo está realizada la parte de la tabla de estudio, pero el resto de modelos sigue la misma lógica.

La relación `profesor-asignatura` tiene la peculiaridad de tener que hacer un JOIN entre las tablas `profesor` y `asignatura` para poder obtener un listado de asignaturas del profesor que se solicite desde la supuesta ruta `[GET]/profesor-asignatura/1`, donde 1 sería el ID del profesor del cual se devolvería todas sus asignaturas.

Así mismo, la supuesta ruta `[GET]/asignatura-profesor/2` devolvería el profesor (o profesores) que impartan una misma asignatura con ID=2.
