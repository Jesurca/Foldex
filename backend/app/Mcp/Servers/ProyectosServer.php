<?php

namespace App\Mcp\Servers;

// Imports: las tres herramientas MCP y los atributos del servidor (nombre, versión, instrucciones).
use App\Mcp\Tools\LeerRepositorio;
use App\Mcp\Tools\ListarCatalogos;
use App\Mcp\Tools\PublicarProyecto;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * Servidor MCP del repositorio. Claude (Claude Code o Claude Desktop) se conecta a
 * `POST /mcp/proyectos` (ver routes/ai.php) con el token personal del estudiante y, a
 * partir de un prompt, publica el proyecto a su nombre.
 *
 * Las instrucciones de abajo las lee Claude al conectarse: le dicen qué pedirle al
 * estudiante (solo repositorio y docente) y qué completar por su cuenta.
 */
#[Name('Repositorio de Proyectos UNAB')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
    Publica proyectos académicos de Ingeniería de Sistemas en el Repositorio de Proyectos UNAB.

    Al estudiante solo le pides: (1) el enlace del repositorio y (2) el nombre del docente.
    El publicante es la cuenta conectada (la ves en `listar_catalogos`); confírmale ese nombre antes de publicar.

    Flujo:
    1. `listar_catalogos`: cuenta conectada, materias, docentes y tecnologías válidas.
    2. `leer_repositorio`: README, lenguajes y temas del repositorio (si es de GitHub). Si no es de GitHub, léelo tú.
    3. Con eso redacta tú: título claro, contexto en español (qué problema resuelve, cómo funciona, cómo ejecutarlo;
       mínimo 20 caracteres), materia más cercana, tecnologías del catálogo y una foto de portada (`portada_url`).
    4. Muéstrale al estudiante un resumen y, cuando lo apruebe, llama a `publicar_proyecto`.
    Si un docente o materia no coincide, muestra las opciones del catálogo y pregunta; no inventes.
    TXT)]
class ProyectosServer extends Server
{
    /** Herramientas que Claude puede llamar, en el orden del flujo. */
    protected array $tools = [
        ListarCatalogos::class,
        LeerRepositorio::class,
        PublicarProyecto::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
