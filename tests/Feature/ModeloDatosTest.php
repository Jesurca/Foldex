<?php

use App\Enums\Role;
use App\Models\Comentario;
use App\Models\Materia;
use App\Models\Proyecto;
use App\Models\Tecnologia;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;

it('siembra catálogos y un admin institucional', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Materia::count())->toBeGreaterThan(10)
        ->and(Tecnologia::where('nombre', 'Laravel')->exists())->toBeTrue();

    $admin = User::where('role', Role::Admin)->sole();
    expect($admin->email)->toEndWith('@unab.edu.co');
});

it('genera usuarios de factory con correo @unab.edu.co y rol estudiante', function (): void {
    $user = User::factory()->create()->refresh();

    expect($user->email)->toEndWith('@unab.edu.co')
        ->and($user->role)->toBe(Role::Estudiante);
});

it('no permite asignar role ni user_id por asignación masiva', function (): void {
    $user = new User(['name' => 'X', 'email' => 'x@unab.edu.co', 'password' => 'secreto123', 'role' => 'admin']);
    $proyecto = new Proyecto(['titulo' => 'Demo', 'user_id' => 99]);

    expect($user->role)->toBe(Role::Estudiante)
        ->and($proyecto->user_id)->toBeNull();
});

it('relaciona proyecto con autor, materia, docente, tecnologías, comentarios y likes', function (): void {
    $proyecto = Proyecto::factory()->create();
    $tecnologias = Tecnologia::factory()->count(2)->create();
    $proyecto->tecnologias()->attach($tecnologias);
    Comentario::factory()->for($proyecto)->create();
    $fan = User::factory()->create();
    $fan->likes()->attach($proyecto);

    $proyecto = Proyecto::withCount(['likes', 'comentarios'])
        ->with(['user', 'materia', 'docente', 'tecnologias'])
        ->find($proyecto->id);

    expect($proyecto->user)->toBeInstanceOf(User::class)
        ->and($proyecto->materia->proyectos)->toHaveCount(1)
        ->and($proyecto->docente)->not->toBeNull()
        ->and($proyecto->tecnologias)->toHaveCount(2)
        ->and($proyecto->likes_count)->toBe(1)
        ->and($proyecto->comentarios_count)->toBe(1)
        ->and($fan->likes()->first()->id)->toBe($proyecto->id);
});

it('genera slugs únicos a partir del título', function (): void {
    $a = Proyecto::factory()->create(['titulo' => 'Mi Proyecto Laravel']);
    $b = Proyecto::factory()->create(['titulo' => 'Mi Proyecto Laravel']);

    expect($a->slug)->toBe('mi-proyecto-laravel')
        ->and($b->slug)->toBe('mi-proyecto-laravel-2');
});

it('impide dos likes del mismo usuario al mismo proyecto (PK compuesta)', function (): void {
    $proyecto = Proyecto::factory()->create();
    $user = User::factory()->create();
    $user->likes()->attach($proyecto);

    expect(fn () => $user->likes()->attach($proyecto))->toThrow(QueryException::class);
});

it('impide borrar una materia en uso (restrictOnDelete)', function (): void {
    $proyecto = Proyecto::factory()->create();

    expect(fn () => $proyecto->materia->delete())->toThrow(QueryException::class);
});

it('borra en cascada los proyectos al eliminar el usuario', function (): void {
    $proyecto = Proyecto::factory()->create();
    $proyecto->user->delete();

    expect(Proyecto::withTrashed()->find($proyecto->id))->toBeNull();
});

it('aplica softDeletes a proyectos', function (): void {
    $proyecto = Proyecto::factory()->create();
    $proyecto->delete();

    expect(Proyecto::find($proyecto->id))->toBeNull()
        ->and(Proyecto::withTrashed()->find($proyecto->id))->not->toBeNull();
});
