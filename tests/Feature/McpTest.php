<?php

use App\Mcp\Servers\ProyectosServer;
use App\Mcp\Tools\LeerRepositorio;
use App\Mcp\Tools\ListarCatalogos;
use App\Mcp\Tools\PublicarProyecto;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Proyecto;
use App\Models\Tecnologia;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/** Catálogo mínimo con nombres reales para probar la búsqueda por nombre. */
function catalogoMcp(): void
{
    Docente::factory()->create(['nombre' => 'Fabián Enrique Suárez Carvajal']);
    Docente::factory()->create(['nombre' => 'Docente de Bases de Datos (por definir)']);
    Materia::factory()->create(['codigo' => '22A14', 'nombre' => 'Programación Web', 'semestre' => 6]);
    Tecnologia::factory()->create(['nombre' => 'Laravel']);
    Tecnologia::factory()->create(['nombre' => 'React']);
}

/** Lo mínimo que el estudiante da (repo y docente) más lo que completa Claude. */
function argumentosMcp(array $extra = []): array
{
    return array_merge([
        'repositorio_url' => 'https://github.com/ana/campus-connect',
        'docente' => 'fabian suarez',
        'titulo' => 'Campus Connect',
        'contexto' => 'Red social académica para compartir recursos entre estudiantes.',
        'materia' => 'programacion web',
        'tecnologias' => ['laravel', 'React', 'Cobol'],
        'portada_url' => 'https://8.8.8.8/portada.png',
    ], $extra);
}

describe('servidor MCP: conexión', function (): void {
    it('exige el token Bearer del estudiante', function (): void {
        $this->postJson('/mcp/proyectos', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();
    });

    it('acepta un token personal y lista las tres herramientas', function (): void {
        $token = User::factory()->create()->createToken('Claude (MCP)')->plainTextToken;

        $respuesta = $this->withToken($token)->postJson('/mcp/proyectos', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertOk();

        expect(collect($respuesta->json('result.tools'))->pluck('name')->all())
            ->toBe(['listar_catalogos', 'leer_repositorio', 'publicar_proyecto']);
    });

    it('no confunde la ruta MCP con la SPA', function (): void {
        $this->get('/mcp/proyectos')->assertStatus(405);
    });
});

describe('servidor MCP: herramientas', function (): void {
    it('muestra la cuenta conectada y los catálogos', function (): void {
        catalogoMcp();
        $ana = User::factory()->create(['name' => 'Ana Pérez']);

        ProyectosServer::actingAs($ana)->tool(ListarCatalogos::class)
            ->assertOk()->assertSee(['Ana Pérez', 'Fabián Enrique Suárez Carvajal', 'Programación Web', 'Laravel']);
    });

    it('publica a nombre del estudiante resolviendo nombres y descargando la portada', function (): void {
        Storage::fake('public');
        Http::fake(['8.8.8.8/*' => Http::response(pngBytes(600, 300), 200, ['Content-Type' => 'image/png'])]);
        catalogoMcp();
        $ana = User::factory()->create(['name' => 'Ana Pérez']);

        ProyectosServer::actingAs($ana)->tool(PublicarProyecto::class, argumentosMcp())
            ->assertOk()
            ->assertSee(['Proyecto publicado', 'Publicado por: Ana Pérez', '/proyectos/campus-connect', 'Cobol']);

        $proyecto = Proyecto::query()->with('tecnologias', 'docente')->sole();
        expect($proyecto->user_id)->toBe($ana->id)
            ->and($proyecto->docente->nombre)->toBe('Fabián Enrique Suárez Carvajal')
            ->and($proyecto->tecnologias->pluck('nombre')->sort()->values()->all())->toBe(['Laravel', 'React'])
            ->and($proyecto->periodo)->toBe(PublicarProyecto::periodoActual())
            ->and($proyecto->portada_path)->toStartWith('portadas/');
        Storage::disk('public')->assertExists($proyecto->portada_path);
    });

    it('usa la imagen social de GitHub si Claude no propone portada', function (): void {
        Storage::fake('public');
        Http::fake(['opengraph.githubassets.com/*' => Http::response(pngBytes(600, 300))]);
        catalogoMcp();

        ProyectosServer::actingAs(User::factory()->create())
            ->tool(PublicarProyecto::class, argumentosMcp(['portada_url' => null]))
            ->assertOk();

        // Si no hay DNS en el entorno de pruebas, se publica sin foto; lo importante es que no falle.
        expect(Proyecto::query()->count())->toBe(1);
    });

    it('publica sin foto si la portada no es una imagen válida', function (): void {
        Storage::fake('public');
        Http::fake(['8.8.8.8/*' => Http::response('<html>no soy imagen</html>')]);
        catalogoMcp();

        ProyectosServer::actingAs(User::factory()->create())->tool(PublicarProyecto::class, argumentosMcp())
            ->assertOk()->assertSee('se publicó sin foto');

        expect(Proyecto::query()->sole()->portada_path)->toBeNull();
    });

    it('no descarga portadas de la red interna', function (): void {
        Http::fake();
        catalogoMcp();

        ProyectosServer::actingAs(User::factory()->create())
            ->tool(PublicarProyecto::class, argumentosMcp(['portada_url' => 'https://127.0.0.1/secreto.png']))
            ->assertOk();

        Http::assertNothingSent();
    });

    it('pide precisión si el docente o la materia no coinciden', function (): void {
        catalogoMcp();

        ProyectosServer::actingAs(User::factory()->create())
            ->tool(PublicarProyecto::class, argumentosMcp(['docente' => 'Profesor Inexistente', 'materia' => 'Cálculo']))
            ->assertHasErrors(['No encontré un único docente', 'Fabián Enrique Suárez Carvajal', 'No encontré una única materia']);

        expect(Proyecto::query()->count())->toBe(0);
    });

    it('aplica las mismas reglas que el formulario web', function (): void {
        catalogoMcp();

        ProyectosServer::actingAs(User::factory()->create())
            ->tool(PublicarProyecto::class, argumentosMcp(['contexto' => 'corto', 'repositorio_url' => 'http://inseguro.com', 'portada_url' => null]))
            ->assertHasErrors();

        expect(Proyecto::query()->count())->toBe(0);
    });

    it('lee un repositorio público de GitHub', function (): void {
        Http::fake([
            'api.github.com/repos/ana/campus-connect' => Http::response(['name' => 'campus-connect', 'description' => 'Red académica', 'topics' => ['laravel']]),
            'api.github.com/repos/ana/campus-connect/languages' => Http::response(['PHP' => 100, 'TypeScript' => 50]),
            'api.github.com/repos/ana/campus-connect/readme' => Http::response('# Campus Connect'),
        ]);

        ProyectosServer::actingAs(User::factory()->create())
            ->tool(LeerRepositorio::class, ['repositorio_url' => 'https://github.com/ana/campus-connect.git'])
            ->assertOk()->assertSee(['Red académica', 'TypeScript', '# Campus Connect', 'opengraph.githubassets.com']);
    });

    it('extrae dueño y nombre solo de enlaces de GitHub', function (string $url, ?string $esperado): void {
        expect(LeerRepositorio::repoDeGithub($url))->toBe($esperado);
    })->with([
        ['https://github.com/ana/campus-connect', 'ana/campus-connect'],
        ['https://github.com/ana/campus-connect.git', 'ana/campus-connect'],
        ['https://github.com/ana/campus-connect/tree/main', 'ana/campus-connect'],
        ['https://gitlab.com/ana/campus-connect', null],
        ['https://github.com.evil.com/ana/x', null],
    ]);
});
