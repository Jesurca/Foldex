<?php

use App\Mcp\Servers\ProyectosServer;
use Laravel\Mcp\Facades\Mcp;

/*
| Servidores MCP (Model Context Protocol). laravel/mcp carga este archivo solo.
|
| POST /mcp/proyectos → Claude publica proyectos a nombre del estudiante.
|   - auth:sanctum: el mismo token personal de /api/v1/me/tokens (Authorization: Bearer …).
|   - throttle: 60 mensajes MCP por minuto por usuario.
*/
Mcp::web('/mcp/proyectos', ProyectosServer::class)
    ->middleware(['auth:sanctum', 'throttle:60,1']);
