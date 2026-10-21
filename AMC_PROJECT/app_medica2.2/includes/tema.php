<?php
/**
 * Paletas cromáticas y tipografías disponibles.
 * Se envían al navegador como JSON para que assets/js/tema.js las aplique
 * (la preferencia de cada usuario se guarda en su navegador con localStorage).
 */

const PALETAS = [
    'teal' => [
        'nombre' => 'Turquesa Clínico', 'hex' => '#0D9488',
        'vars' => ['--primary-50' => '#f0fdfa', '--primary-100' => '#ccfbf1', '--primary-500' => '#14b8a6',
                   '--primary-600' => '#0d9488', '--primary-700' => '#0f766e', '--primary-ring' => 'rgba(13, 148, 136, 0.25)'],
    ],
    'blue' => [
        'nombre' => 'Azul Hospitalario', 'hex' => '#2563EB',
        'vars' => ['--primary-50' => '#eff6ff', '--primary-100' => '#dbeafe', '--primary-500' => '#3b82f6',
                   '--primary-600' => '#2563eb', '--primary-700' => '#1d4ed8', '--primary-ring' => 'rgba(37, 99, 235, 0.25)'],
    ],
    'emerald' => [
        'nombre' => 'Esmeralda Vital', 'hex' => '#059669',
        'vars' => ['--primary-50' => '#ecfdf5', '--primary-100' => '#d1fae5', '--primary-500' => '#10b981',
                   '--primary-600' => '#059669', '--primary-700' => '#047857', '--primary-ring' => 'rgba(5, 150, 105, 0.25)'],
    ],
    'violet' => [
        'nombre' => 'Violeta Especializado', 'hex' => '#7C3AED',
        'vars' => ['--primary-50' => '#faf5ff', '--primary-100' => '#f3e8ff', '--primary-500' => '#8b5cf6',
                   '--primary-600' => '#7c3aed', '--primary-700' => '#6d28d9', '--primary-ring' => 'rgba(124, 58, 237, 0.25)'],
    ],
    'amber' => [
        'nombre' => 'Ámbar Asistencial', 'hex' => '#D97706',
        'vars' => ['--primary-50' => '#fffbeb', '--primary-100' => '#fef3c7', '--primary-500' => '#f59e0b',
                   '--primary-600' => '#d97706', '--primary-700' => '#b45309', '--primary-ring' => 'rgba(217, 119, 6, 0.25)'],
    ],
    'slate' => [
        'nombre' => 'Grafito Clínico Pro', 'hex' => '#334155',
        'vars' => ['--primary-50' => '#f8fafc', '--primary-100' => '#f1f5f9', '--primary-500' => '#64748b',
                   '--primary-600' => '#475569', '--primary-700' => '#334155', '--primary-ring' => 'rgba(71, 85, 105, 0.25)'],
    ],
];

const TIPOGRAFIAS = [
    'jakarta' => ['nombre' => 'Plus Jakarta Sans', 'descripcion' => 'Moderna, equilibrada y ultra nítida en pantallas médicas.',
                  'css' => "'Plus Jakarta Sans', system-ui, -apple-system, sans-serif", 'etiqueta' => 'Recomendada'],
    'outfit'  => ['nombre' => 'Outfit', 'descripcion' => 'Geométrica con curvas suaves y enfoque amigable al paciente.',
                  'css' => "'Outfit', system-ui, -apple-system, sans-serif", 'etiqueta' => ''],
    'lexend'  => ['nombre' => 'Lexend', 'descripcion' => 'Diseñada científicamente para reducir la fatiga visual en expedientes.',
                  'css' => "'Lexend', system-ui, -apple-system, sans-serif", 'etiqueta' => 'Anti-fatiga'],
    'manrope' => ['nombre' => 'Manrope', 'descripcion' => 'Humanista contemporánea con proporciones claras para datos clínicos.',
                  'css' => "'Manrope', system-ui, -apple-system, sans-serif", 'etiqueta' => ''],
    'dmsans'  => ['nombre' => 'DM Sans', 'descripcion' => 'Alta legibilidad en textos pequeños, etiquetas y tablas numéricas.',
                  'css' => "'DM Sans', system-ui, -apple-system, sans-serif", 'etiqueta' => ''],
];

/** Datos mínimos que necesita el motor de temas en el navegador. */
function tema_json(): string
{
    $paletas = [];
    foreach (PALETAS as $k => $p) {
        $paletas[$k] = $p['vars'];
    }
    $fuentes = [];
    foreach (TIPOGRAFIAS as $k => $f) {
        $fuentes[$k] = $f['css'];
    }
    return json_encode(['paletas' => $paletas, 'fuentes' => $fuentes], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
}
