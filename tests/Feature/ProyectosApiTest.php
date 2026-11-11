<?php

use App\Models\Comentario;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Proyecto;
use App\Models\Tecnologia;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function datosProyecto(array $extra = []): array
{
    return array_merge([
        'titulo' => 'Campus Connect',
        'contexto' => 'Una comunidad digital para conectar estudiantes y compartir recursos.',
        'materia_id' => Materia::factory()->create()->id,
        'docente_id' => Docente::factory()->create()->id,
        'periodo' => '2026-2',
        'repositorio_url' => 'https://github.com/unab/campus-connect',
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'tecnologias' => Tecnologia::factory()->count(2)->create()->pluck('id')->all(),
    ], $extra);
}

describe('catálogos', function (): void {
    it('lista materias, docentes y tecnologías sin autenticación', function (string $ruta): void {
        Materia::factory()->count(2)->create();
        Docente::factory()->count(2)->create();
        Tecnologia::factory()->count(2)->create();

        $this->getJson("/api/v1/{$ruta}")->assertOk()->assertJsonCount(2, 'data');
    })->with(['materias', 'docentes', 'tecnologias']);

    it('no expone el correo de los docentes', function (): void {
        Docente::factory()->create();

        $this->getJson('/api/v1/docentes')->assertOk()->assertJsonMissingPath('data.0.email');
    });
});

describe('listado y detalle de proyectos', function (): void {
    it('pagina el catálogo con meta y contadores', function (): void {
        $proyecto = Proyecto::factory()->create();
        $proyecto->likes()->attach(User::factory()->count(2)->create());
        Comentario::factory()->for($proyecto)->create();

        $this->getJson('/api/v1/proyectos')
            ->assertOk()
            ->assertJsonStructure(['data' => [['slug', 'titulo', 'materia', 'docente', 'autor', 'tecnologias', 'can']], 'links', 'meta'])
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.likes_count', 2)
            ->assertJsonPath('data.0.comentarios_count', 1)
            ->assertJsonPath('data.0.liked', null)
            ->assertJsonPath('data.0.can.delete', false)
            ->assertJsonMissingPath('data.0.autor.email');
    });

    it('filtra por texto, materia, tecnología y semestre', function (): void {
        $materia = Materia::factory()->create(['semestre' => 4]);
        $tecnologia = Tecnologia::factory()->create(['nombre' => 'Laravel']);
        $buscado = Proyecto::factory()->for($materia)->create(['titulo' => 'RoboFinder']);
        $buscado->tecnologias()->attach($tecnologia);
        // Materia fija en otro semestre: si fuera aleatoria podría caer en el 4 y la prueba fallaría a veces.
        Proyecto::factory()->count(2)->for(Materia::factory()->create(['semestre' => 1]))->create();

        foreach (['q=robo', "materia={$materia->id}", "tecnologia={$tecnologia->id}", 'semestre=4', 'q=laravel'] as $filtro) {
            $this->getJson("/api/v1/proyectos?{$filtro}")
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.slug', $buscado->slug);
        }
    });

    it('ordena por populares', function (): void {
        Proyecto::factory()->create();
        $popular = Proyecto::factory()->create(['created_at' => now()->subYear()]);
        $popular->likes()->attach(User::factory()->create());

        $this->getJson('/api/v1/proyectos?orden=populares')->assertJsonPath('data.0.slug', $popular->slug);
    });

    it('ordena por likes recibidos en el rango elegido', function (): void {
        $antiguo = Proyecto::factory()->create(['created_at' => now()->subYears(3)]);
        $antiguo->likes()->attach(User::factory()->count(4)->create(), ['created_at' => now()->subYears(2)]);
        $historico = Proyecto::factory()->create();
        $historico->likes()->attach(User::factory()->count(3)->create(), ['created_at' => now()->subMonths(2)]);
        $semana = Proyecto::factory()->create(['created_at' => now()->subYear()]);
        $semana->likes()->attach(User::factory()->count(2)->create(), ['created_at' => now()->subDays(3)]);
        $hoy = Proyecto::factory()->create(['created_at' => now()->subYears(2)]);
        $hoy->likes()->attach(User::factory()->create(), ['created_at' => now()->subHour()]);

        $slugs = fn (string $rango) => collect($this->getJson("/api/v1/proyectos?rango={$rango}")->assertOk()->json('data'))->pluck('slug')->all();

        expect($slugs('diario'))->toBe([$hoy->slug, $antiguo->slug, $historico->slug, $semana->slug])
            ->and($slugs('semanal'))->toBe([$semana->slug, $hoy->slug, $antiguo->slug, $historico->slug])
            ->and($slugs('mensual'))->toBe([$semana->slug, $hoy->slug, $antiguo->slug, $historico->slug])
            ->and($slugs('anual'))->toBe([$historico->slug, $semana->slug, $hoy->slug, $antiguo->slug])
            ->and($slugs('historico'))->toBe([$antiguo->slug, $historico->slug, $semana->slug, $hoy->slug]);
    });

    it('rechaza filtros inválidos con 422', function (): void {
        $this->getJson('/api/v1/proyectos?orden=raro&semestre=20&rango=siglo')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['orden', 'semestre', 'rango']);
    });

    it('muestra el detalle por slug y marca el like del usuario', function (): void {
        $usuario = User::factory()->create();
        $proyecto = Proyecto::factory()->create();
        $proyecto->likes()->attach($usuario);
        Sanctum::actingAs($usuario);

        $this->getJson("/api/v1/proyectos/{$proyecto->slug}")
            ->assertOk()
            ->assertJsonPath('data.id', $proyecto->id)
            ->assertJsonPath('data.liked', true);
    });

    it('responde 404 si el proyecto no existe o fue eliminado', function (): void {
        $proyecto = Proyecto::factory()->create();
        $proyecto->delete();

        $this->getJson("/api/v1/proyectos/{$proyecto->slug}")->assertNotFound();
        $this->getJson('/api/v1/proyectos/no-existe')->assertNotFound();
    });
});

describe('publicar proyectos', function (): void {
    it('exige autenticación', function (): void {
        $this->postJson('/api/v1/proyectos', [])->assertUnauthorized();
    });

    it('publica a nombre del usuario del token aunque el body traiga otro autor', function (): void {
        $autor = User::factory()->create();
        $otro = User::factory()->create();
        Sanctum::actingAs($autor);

        $respuesta = $this->postJson('/api/v1/proyectos', datosProyecto(['user_id' => $otro->id, 'slug' => 'hackeado']))
            ->assertCreated()
            ->assertJsonPath('data.autor.id', $autor->id)
            ->assertJsonPath('data.slug', 'campus-connect')
            ->assertJsonPath('data.can.delete', true)
            ->assertJsonCount(2, 'data.tecnologias');

        expect($respuesta->json('data.publicado_en'))->not->toBeNull()
            ->and(Proyecto::sole()->user_id)->toBe($autor->id);
    });

    it('valida campos, enlaces https, periodo y tecnologías', function (): void {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/proyectos', [
            'titulo' => 'x',
            'repositorio_url' => 'http://github.com/x',
            'video_url' => 'no-es-url',
            'periodo' => '2026-3',
            'tecnologias' => [],
            'materia_id' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'titulo', 'contexto', 'materia_id', 'docente_id', 'periodo', 'repositorio_url', 'video_url', 'tecnologias',
        ])->assertJsonPath('errors.periodo.0', 'El periodo debe tener el formato AAAA-1 o AAAA-2 (por ejemplo 2026-2).');
    });

    it('permite publicar sin video', function (): void {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/proyectos', datosProyecto(['video_url' => '']))
            ->assertCreated()
            ->assertJsonPath('data.video_url', null);
    });
});

describe('eliminar proyectos', function (): void {
    it('el autor puede eliminar su proyecto', function (): void {
        $proyecto = Proyecto::factory()->create();
        Sanctum::actingAs($proyecto->user);

        $this->deleteJson("/api/v1/proyectos/{$proyecto->slug}")->assertNoContent();
        expect($proyecto->fresh()->trashed())->toBeTrue();
    });

    it('otro estudiante no puede eliminarlo', function (): void {
        $proyecto = Proyecto::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/v1/proyectos/{$proyecto->slug}")->assertForbidden();
        $this->getJson("/api/v1/proyectos/{$proyecto->slug}")->assertJsonPath('data.can.delete', false);
    });

    it('un admin puede eliminar cualquiera desde /admin', function (): void {
        $proyecto = Proyecto::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/admin/proyectos')->assertOk()->assertJsonPath('meta.total', 1);
        $this->deleteJson("/api/v1/admin/proyectos/{$proyecto->slug}")->assertNoContent();
        expect($proyecto->fresh()->trashed())->toBeTrue();
    });

    it('un estudiante no entra a /admin/proyectos', function (): void {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/proyectos')->assertForbidden();
    });
});

describe('likes', function (): void {
    it('da y quita like de forma idempotente', function (): void {
        $proyecto = Proyecto::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/proyectos/{$proyecto->slug}/like")->assertOk()->assertExactJson(['liked' => true, 'likes_count' => 1]);
        $this->postJson("/api/v1/proyectos/{$proyecto->slug}/like")->assertExactJson(['liked' => true, 'likes_count' => 1]);
        $this->deleteJson("/api/v1/proyectos/{$proyecto->slug}/like")->assertExactJson(['liked' => false, 'likes_count' => 0]);
        $this->deleteJson("/api/v1/proyectos/{$proyecto->slug}/like")->assertExactJson(['liked' => false, 'likes_count' => 0]);
    });

    it('exige autenticación', function (): void {
        $proyecto = Proyecto::factory()->create();

        $this->postJson("/api/v1/proyectos/{$proyecto->slug}/like")->assertUnauthorized();
    });
});

describe('comentarios', function (): void {
    it('lista comentarios paginados del más reciente al más antiguo', function (): void {
        $proyecto = Proyecto::factory()->create();
        Comentario::factory()->for($proyecto)->create(['created_at' => now()->subDay()]);
        $ultimo = Comentario::factory()->for($proyecto)->create();

        $this->getJson("/api/v1/proyectos/{$proyecto->slug}/comentarios")
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $ultimo->id);
    });

    it('crea un comentario a nombre del usuario autenticado', function (): void {
        $usuario = User::factory()->create();
        $proyecto = Proyecto::factory()->create();
        Sanctum::actingAs($usuario);

        $this->postJson("/api/v1/proyectos/{$proyecto->slug}/comentarios", ['contenido' => '  Muy buen trabajo  '])
            ->assertCreated()
            ->assertJsonPath('data.contenido', 'Muy buen trabajo')
            ->assertJsonPath('data.autor.id', $usuario->id)
            ->assertJsonPath('data.can.delete', true);

        $this->postJson("/api/v1/proyectos/{$proyecto->slug}/comentarios", ['contenido' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contenido');
    });

    it('solo el autor o un admin borran un comentario', function (): void {
        $comentario = Comentario::factory()->create();

        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson("/api/v1/comentarios/{$comentario->id}")->assertForbidden();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->deleteJson("/api/v1/comentarios/{$comentario->id}")->assertNoContent();
    });
});

describe('mi espacio', function (): void {
    it('lista mis publicaciones y mis me gusta', function (): void {
        $usuario = User::factory()->create();
        $mio = Proyecto::factory()->for($usuario)->create();
        $ajeno = Proyecto::factory()->create();
        $ajeno->likes()->attach($usuario);
        Proyecto::factory()->create();
        Sanctum::actingAs($usuario);

        $this->getJson('/api/v1/me/proyectos')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', $mio->slug);
        $this->getJson('/api/v1/me/likes')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $ajeno->slug)
            ->assertJsonPath('data.0.liked', true);
    });

    it('exige autenticación', function (): void {
        $this->getJson('/api/v1/me/proyectos')->assertUnauthorized();
    });
});
