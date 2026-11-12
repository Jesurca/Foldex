<?php

// Imports: el servidor MCP y la fachada Mcp de laravel/mcp.
use App\Mcp\Servers\ProyectosServer;
use Laravel\Mcp\Facades\Mcp;

/*
| Servidores MCP (Model Context Protocol). Lo carga bootstrap/app.php (withRouting → then).
|
| POST /mcp/proyectos → Claude publica proyectos a nombre del estudiante.
|   - auth:sanctum: el mismo token personal de /api/v1/me/tokens (Authorization: Bearer …).
|   - throttle: 60 mensajes MCP por minuto por usuario.
*/

// Ruta del servidor MCP del repositorio (ver backend/app/Mcp).
Mcp::web('/mcp/proyectos', ProyectosServer::class)
    ->middleware(['auth:sanctum', 'throttle:60,1']);
