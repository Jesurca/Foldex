<?php

namespace Database\Seeders;

use App\Models\Comentario;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Proyecto;
use App\Models\Tecnologia;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración (solo entorno local): 3 estudiantes y 6 proyectos
 * para que el catálogo no arranque vacío. Contraseña de los estudiantes: Estudiante123
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Proyecto::withTrashed()->exists()) {
            return;
        }

        $estudiantes = collect([
            ['Santiago Gómez', 'sgomez@unab.edu.co', 7],
            ['Valentina Torres', 'vtorres@unab.edu.co', 8],
            ['Daniel Redondo', 'dredondo@unab.edu.co', 6],
        ])->map(fn (array $e) => User::firstOrCreate(['email' => $e[1]], [
            'name' => $e[0],
            'password' => 'Estudiante123',
            'programa' => 'Ingeniería de Sistemas',
            'semestre' => $e[2],
        ]));

        $docente = Docente::orderBy('id')->firstOrFail();

        $proyectos = [
            ['Campus Connect', 'Una comunidad digital para conectar estudiantes, compartir recursos y descubrir lo que pasa en el campus.', 'ISI-402', ['React', 'Laravel', 'PostgreSQL'], 0, '2026-2'],
            ['MediTrack', 'Seguimiento inteligente de hábitos y bienestar. La tecnología al servicio de una vida más saludable.', 'ISI-701', ['Flutter', 'Dart'], 1, '2026-2'],
            ['RoboFinder', 'Robot autónomo que explora su entorno, identifica obstáculos y encuentra nuevas rutas en tiempo real.', 'ISI-702', ['Python'], 2, '2026-1'],
            ['UNAB Analytics', 'Datos que cuentan historias: visualización del rendimiento académico para apoyar decisiones educativas.', 'ISI-302', ['Python', 'React'], 1, '2026-1'],
            ['EcoCampus', 'Una plataforma colaborativa para medir y reducir la huella ambiental de nuestra universidad.', 'ISI-601', ['Vue', 'Laravel', 'PostgreSQL'], 0, '2025-2'],
            ['VisionBot', 'Visión artificial para reconocer objetos y acercar la robótica a nuevos espacios de aprendizaje.', 'ISI-702', ['Python', 'Docker'], 2, '2025-2'],
        ];

        foreach ($proyectos as $i => [$titulo, $contexto, $codigo, $tecnologias, $autor, $periodo]) {
            $proyecto = new Proyecto([
                'titulo' => $titulo,
                'contexto' => $contexto,
                'materia_id' => Materia::where('codigo', $codigo)->value('id'),
                'docente_id' => $docente->id,
                'periodo' => $periodo,
                'repositorio_url' => 'https://github.com/unab/'.str($titulo)->slug(),
                'video_url' => null,
            ]);
            // DatabaseSeeder usa WithoutModelEvents: el evento `creating` no genera el slug.
            $proyecto->slug = Proyecto::slugUnico($titulo);
            $proyecto->user()->associate($estudiantes[$autor]);
            $proyecto->created_at = now()->subDays($i * 3);
            $proyecto->save();
            $proyecto->tecnologias()->sync(Tecnologia::whereIn('nombre', $tecnologias)->pluck('id'));
            $proyecto->likes()->attach($estudiantes->reject(fn (User $u) => $u->is($estudiantes[$autor]))->take(2 - $i % 3)->pluck('id'));
        }

        $primero = Proyecto::where('titulo', 'Campus Connect')->firstOrFail();
        $comentario = new Comentario(['contenido' => 'Una propuesta muy interesante. ¡Excelente trabajo integrando la comunidad universitaria!']);
        $comentario->user()->associate($estudiantes[1]);
        $comentario->proyecto()->associate($primero);
        $comentario->save();
    }
}
