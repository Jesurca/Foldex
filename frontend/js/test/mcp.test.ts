// Pruebas de los textos de conexión MCP que se muestran en "Conéctate por MCP".
import { describe, expect, it } from 'vitest';
import { comandoClaudeCode, configClaudeDesktop, urlServidorMcp } from '@/components/ConectarMcp';

describe('conexión MCP', () => {
    const url = urlServidorMcp('http://localhost:8000');

    it('apunta a la ruta del backend', () => {
        expect(url).toBe('http://localhost:8000/mcp/proyectos');
    });

    it('arma el comando de Claude Code con el token Bearer', () => {
        expect(comandoClaudeCode(url, 'abc|123')).toBe(
            'claude mcp add --transport http proyectos-unab http://localhost:8000/mcp/proyectos --header "Authorization: Bearer abc|123"',
        );
    });

    it('arma la configuración de Claude Desktop con mcp-remote', () => {
        const config = JSON.parse(configClaudeDesktop(url, 'abc|123'));
        const servidor = config.mcpServers['proyectos-unab'];
        expect(servidor.command).toBe('npx');
        expect(servidor.args).toEqual(['-y', 'mcp-remote', url, '--header', 'Authorization:${AUTH_HEADER}']);
        expect(servidor.env.AUTH_HEADER).toBe('Bearer abc|123');
    });

    it('solo permite http fuera de localhost con --allow-http', () => {
        expect(JSON.parse(configClaudeDesktop('http://192.168.1.5:8000/mcp/proyectos', 't')).mcpServers['proyectos-unab'].args).toContain('--allow-http');
        expect(JSON.parse(configClaudeDesktop('https://proyectos.unab.edu.co/mcp/proyectos', 't')).mcpServers['proyectos-unab'].args).not.toContain('--allow-http');
    });
});
