<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // --- GYM (5) ---
            [
                "name" => "Adaptación Anatómica",
                "description" => "Rutina Full Body para principiantes. Máquinas y peso libre básico.",
                "level" => "Básico",
                "mode" => "gym",
                "duration_minutes" => 45,
                "image_url" => "/assets/img/gym-beginner.jpg",
                "exercises" => [
                    ["name" => "Prensa de Piernas", "sets" => 3, "reps" => 12, "rest_seconds" => 60],
                    ["name" => "Jalón al pecho", "sets" => 3, "reps" => 12, "rest_seconds" => 60],
                    ["name" => "Press de Pecho en Máquina", "sets" => 3, "reps" => 12, "rest_seconds" => 60],
                    ["name" => "Crunch Abdominal", "sets" => 3, "reps" => 15, "rest_seconds" => 45]
                ]
            ],
            [
                "name" => "Fuerza Base 5x5",
                "description" => "Clásico de fuerza. Pesado y ejercicios compuestos.",
                "level" => "Intermedio",
                "mode" => "gym",
                "duration_minutes" => 60,
                "image_url" => "/assets/img/gym-inter.jpg",
                "exercises" => [
                    ["name" => "Sentadilla Trasera", "sets" => 5, "reps" => 5, "rest_seconds" => 180],
                    ["name" => "Press Banca", "sets" => 5, "reps" => 5, "rest_seconds" => 180],
                    ["name" => "Remo con Barra", "sets" => 5, "reps" => 5, "rest_seconds" => 180]
                ]
            ],
            [
                "name" => "Push Pull Legs - Empuje",
                "description" => "Día 1 de PPL. Enfoque en Pecho, Hombros y Tríceps.",
                "level" => "Intermedio",
                "mode" => "gym",
                "duration_minutes" => 70,
                "image_url" => "/assets/img/gym-push.jpg",
                "exercises" => [
                    ["name" => "Press Banca Inclinado con Mancuernas", "sets" => 4, "reps" => 8, "rest_seconds" => 90],
                    ["name" => "Press Militar con Barra", "sets" => 4, "reps" => 8, "rest_seconds" => 90],
                    ["name" => "Elevaciones Laterales", "sets" => 3, "reps" => 15, "rest_seconds" => 60],
                    ["name" => "Tríceps en Polea", "sets" => 3, "reps" => 12, "rest_seconds" => 60]
                ]
            ],
            [
                "name" => "Fiel al Hierro - Pierna",
                "description" => "Día de pierna enfocado en hipertrofia.",
                "level" => "Avanzado",
                "mode" => "gym",
                "duration_minutes" => 90,
                "image_url" => "/assets/img/gym-legs.jpg",
                "exercises" => [
                    ["name" => "Sentadilla Hack", "sets" => 4, "reps" => 10, "rest_seconds" => 120],
                    ["name" => "Peso Muerto Rumano", "sets" => 4, "reps" => 10, "rest_seconds" => 120],
                    ["name" => "Zancadas Búlgaras", "sets" => 3, "reps" => 12, "rest_seconds" => 90],
                    ["name" => "Elevación de Gemelos", "sets" => 4, "reps" => 20, "rest_seconds" => 60]
                ]
            ],
            [
                "name" => "German Volume Training",
                "description" => "10 series de 10. Solo para valientes.",
                "level" => "Avanzado",
                "mode" => "gym",
                "duration_minutes" => 75,
                "image_url" => "/assets/img/gym-advanced.jpg",
                "exercises" => [
                    ["name" => "Press Banca Planto", "sets" => 10, "reps" => 10, "rest_seconds" => 90],
                    ["name" => "Dominadas Supinas", "sets" => 10, "reps" => 10, "rest_seconds" => 90],
                    ["name" => "Facepull", "sets" => 3, "reps" => 15, "rest_seconds" => 60]
                ]
            ],

            // --- CALISTENIA (5) ---
            [
                "name" => "Fundamentos Calisténicos",
                "description" => "Empieza a dominar tu peso corporal.",
                "level" => "Básico",
                "mode" => "calisthenics",
                "duration_minutes" => 35,
                "image_url" => "/assets/img/cali-beginner.jpg",
                "exercises" => [
                    ["name" => "Sentadillas al aire", "sets" => 3, "reps" => 20, "rest_seconds" => 45],
                    ["name" => "Flexiones con rodillas", "sets" => 3, "reps" => 10, "rest_seconds" => 60],
                    ["name" => "Remo invertido en mesa/barra baja", "sets" => 3, "reps" => 10, "rest_seconds" => 60],
                    ["name" => "Plancha abdominal", "time_seconds" => 30, "sets" => 3, "rest_seconds" => 45]
                ]
            ],
            [
                "name" => "Progresión de Dominadas",
                "description" => "Enfoque en espalda y bíceps.",
                "level" => "Intermedio",
                "mode" => "calisthenics",
                "duration_minutes" => 45,
                "image_url" => "/assets/img/cali-pull.jpg",
                "exercises" => [
                    ["name" => "Dominadas Negativas", "sets" => 4, "reps" => 5, "rest_seconds" => 90],
                    ["name" => "Australian Pull-ups", "sets" => 4, "reps" => 12, "rest_seconds" => 60],
                    ["name" => "Headbangers", "sets" => 3, "reps" => 8, "rest_seconds" => 90]
                ]
            ],
            [
                "name" => "Empuje Total (Push)",
                "description" => "Pecho, hombros y tríceps con peso corporal.",
                "level" => "Intermedio",
                "mode" => "calisthenics",
                "duration_minutes" => 50,
                "image_url" => "/assets/img/cali-push.jpg",
                "exercises" => [
                    ["name" => "Fondos en Paralelas", "sets" => 4, "reps" => 10, "rest_seconds" => 90],
                    ["name" => "Flexiones Diamante", "sets" => 3, "reps" => 12, "rest_seconds" => 60],
                    ["name" => "Flexiones declinadas", "sets" => 3, "reps" => 15, "rest_seconds" => 60]
                ]
            ],
            [
                "name" => "Skill Day: Muscle Up",
                "description" => "Entrenamiento técnico para el Muscle Up.",
                "level" => "Avanzado",
                "mode" => "calisthenics",
                "duration_minutes" => 60,
                "image_url" => "/assets/img/cali-skill.jpg",
                "exercises" => [
                    ["name" => "Explosive Pull-ups (High Pulls)", "sets" => 5, "reps" => 3, "rest_seconds" => 120],
                    ["name" => "Fondos en Barra Recta", "sets" => 4, "reps" => 10, "rest_seconds" => 90],
                    ["name" => "Muscle Up Negativas", "sets" => 4, "reps" => 3, "rest_seconds" => 120]
                ]
            ],
            [
                "name" => "Estilo Libre Front Lever",
                "description" => "Rutina de estáticos.",
                "level" => "Avanzado",
                "mode" => "calisthenics",
                "duration_minutes" => 75,
                "image_url" => "/assets/img/cali-lever.jpg",
                "exercises" => [
                    ["name" => "Tuck Front Lever Hold", "sets" => 5, "time_seconds" => 10, "rest_seconds" => 120],
                    ["name" => "Front Lever Raises", "sets" => 4, "reps" => 5, "rest_seconds" => 120],
                    ["name" => "Ice Cream Makers", "sets" => 3, "reps" => 8, "rest_seconds" => 90]
                ]
            ],

            // --- CASA (5) ---
            [
                "name" => "Full Body Mancuernas",
                "description" => "Rutina completa usando mancuernas ligeras.",
                "level" => "Básico",
                "mode" => "home",
                "duration_minutes" => 40,
                "image_url" => "/assets/img/home-dumbbell.jpg",
                "exercises" => [
                    ["name" => "Sentadilla Goblet (Mancuerna)", "sets" => 3, "reps" => 15, "weight_kg" => 15, "rest_seconds" => 60],
                    ["name" => "Press Militar de Pie (Mancuernas)", "sets" => 3, "reps" => 12, "weight_kg" => 10, "rest_seconds" => 60],
                    ["name" => "Remo a una mano", "sets" => 3, "reps" => 15, "weight_kg" => 15, "rest_seconds" => 60],
                    ["name" => "Fondos en Paralelas (Asistidos si necesario)", "sets" => 3, "reps" => 8, "rest_seconds" => 90]
                ]
            ],
            [
                "name" => "Torso con Gomas y Paralelas",
                "description" => "Hipertrofia de torso combinando gomas y calistenia.",
                "level" => "Intermedio",
                "mode" => "home",
                "duration_minutes" => 50,
                "image_url" => "/assets/img/home-bands.jpg",
                "exercises" => [
                    ["name" => "Fondos en Paralelas (Lastre opcional)", "sets" => 4, "reps" => 12, "rest_seconds" => 90],
                    ["name" => "Cruces de Pecho con Gomas", "sets" => 4, "reps" => 20, "rest_seconds" => 45],
                    ["name" => "Remo sentado con Gomas", "sets" => 4, "reps" => 20, "rest_seconds" => 45],
                    ["name" => "Curl de Bíceps con Gomas", "sets" => 3, "reps" => 15, "rest_seconds" => 45]
                ]
            ],
            [
                "name" => "Pierna Asesina en Casa",
                "description" => "Pierna intensa sin cargas pesadas: altas repes y unilaterales.",
                "level" => "Intermedio",
                "mode" => "home",
                "duration_minutes" => 55,
                "image_url" => "/assets/img/home-legs.jpg",
                "exercises" => [
                    ["name" => "Sentadilla Búlgara (Mancuernas)", "sets" => 4, "reps" => 12, "weight_kg" => 15, "rest_seconds" => 90],
                    ["name" => "Peso Muerto Rumano Unilateral (Mancuerna)", "sets" => 4, "reps" => 12, "weight_kg" => 15, "rest_seconds" => 90],
                    ["name" => "Hip Thrust Unilateral (en Banco)", "sets" => 3, "reps" => 15, "weight_kg" => 15, "rest_seconds" => 60],
                    ["name" => "Zancadas Caminando con Gomas", "sets" => 3, "reps" => 20, "rest_seconds" => 60]
                ]
            ],
            [
                "name" => "Metabólico Gomas Full Body",
                "description" => "Circuito de alta intensidad con gomas elásticas.",
                "level" => "Avanzado",
                "mode" => "home",
                "duration_minutes" => 30,
                "image_url" => "/assets/img/home-hiit.jpg",
                "exercises" => [
                    ["name" => "Thrusters con Gomas", "sets" => 4, "reps" => 20, "rest_seconds" => 30],
                    ["name" => "Flexiones con Goma en espalda", "sets" => 4, "reps" => 15, "rest_seconds" => 30],
                    ["name" => "Remo al mentón con Gomas", "sets" => 4, "reps" => 20, "rest_seconds" => 30],
                    ["name" => "Burpees", "sets" => 4, "reps" => 15, "rest_seconds" => 60]
                ]
            ],
            [
                "name" => "Fuerza Estática en Paralelas",
                "description" => "Dominando el soporte en paralelas (L-sit, Planche lean).",
                "level" => "Avanzado",
                "mode" => "home",
                "duration_minutes" => 60,
                "image_url" => "/assets/img/home-advanced.jpg",
                "exercises" => [
                    ["name" => "L-Sit Hold en Paralelas", "sets" => 5, "time_seconds" => 15, "rest_seconds" => 90],
                    ["name" => "Fondos en Paralelas Profundos", "sets" => 5, "reps" => 12, "rest_seconds" => 120],
                    ["name" => "Planque Lean en Paralelas", "sets" => 4, "time_seconds" => 20, "rest_seconds" => 90],
                    ["name" => "Flexiones Deficit en Paralelas", "sets" => 4, "reps" => 12, "rest_seconds" => 90]
                ]
            ],

            // --- CASA – PESO CORPORAL (5 nuevos) ---
            [
                "name" => "Inicio Sin Equipo",
                "description" => "Entrenamiento full body 100% peso corporal. Sin necesidad de ningún material. Ideal para empezar desde cero.",
                "level" => "Básico",
                "mode" => "home",
                "duration_minutes" => 30,
                "image_url" => "/assets/img/home-bw-beginner.jpg",
                "exercises" => [
                    ["name" => "Sentadilla con peso corporal", "sets" => 3, "reps" => 15, "rest_seconds" => 45],
                    ["name" => "Flexiones en rodillas", "sets" => 3, "reps" => 10, "rest_seconds" => 60],
                    ["name" => "Zancada estática", "sets" => 3, "reps" => 10, "rest_seconds" => 45],
                    ["name" => "Plancha abdominal", "sets" => 3, "time_seconds" => 20, "rest_seconds" => 45],
                    ["name" => "Puente de glúteos", "sets" => 3, "reps" => 15, "rest_seconds" => 45],
                ]
            ],
            [
                "name" => "Fuerza Corporal Intermedio",
                "description" => "Rutina de fuerza con peso corporal para nivel intermedio. Progresiones de flexiones y sentadillas unilaterales.",
                "level" => "Intermedio",
                "mode" => "home",
                "duration_minutes" => 45,
                "image_url" => "/assets/img/home-bw-inter.jpg",
                "exercises" => [
                    ["name" => "Flexiones arquero (archer push-ups)", "sets" => 4, "reps" => 8, "rest_seconds" => 75],
                    ["name" => "Sentadilla búlgara peso corporal", "sets" => 4, "reps" => 10, "rest_seconds" => 90],
                    ["name" => "Pike push-ups (hombros)", "sets" => 3, "reps" => 12, "rest_seconds" => 60],
                    ["name" => "Hip thrust con una pierna", "sets" => 3, "reps" => 15, "rest_seconds" => 60],
                    ["name" => "Plancha con elevación de brazo", "sets" => 3, "reps" => 10, "rest_seconds" => 45],
                ]
            ],
            [
                "name" => "Atlético Peso Corporal",
                "description" => "Entrenamiento avanzado sin equipamiento. Ejercicios explosivos y de alta demanda neuromuscular.",
                "level" => "Avanzado",
                "mode" => "home",
                "duration_minutes" => 55,
                "image_url" => "/assets/img/home-bw-advanced.jpg",
                "exercises" => [
                    ["name" => "Flexiones con palmada (clap push-ups)", "sets" => 5, "reps" => 6, "rest_seconds" => 90],
                    ["name" => "Sentadilla pistol asistida", "sets" => 4, "reps" => 5, "rest_seconds" => 120],
                    ["name" => "Handstand hold en pared", "sets" => 4, "time_seconds" => 20, "rest_seconds" => 90],
                    ["name" => "Nordic curl (con sofá)", "sets" => 4, "reps" => 6, "rest_seconds" => 120],
                    ["name" => "Burpee con flexión completa", "sets" => 4, "reps" => 8, "rest_seconds" => 60],
                ]
            ],
            [
                "name" => "Core Funcional",
                "description" => "Sesión enfocada al núcleo: abdomen, lumbares y estabilizadores. Sin equipamiento necesario.",
                "level" => "Intermedio",
                "mode" => "home",
                "duration_minutes" => 30,
                "image_url" => "/assets/img/home-core.jpg",
                "exercises" => [
                    ["name" => "Plancha frontal", "sets" => 4, "time_seconds" => 40, "rest_seconds" => 40],
                    ["name" => "Plancha lateral (cada lado)", "sets" => 3, "time_seconds" => 30, "rest_seconds" => 30],
                    ["name" => "Crunch bicicleta", "sets" => 3, "reps" => 20, "rest_seconds" => 45],
                    ["name" => "Elevación de piernas tumbado", "sets" => 3, "reps" => 15, "rest_seconds" => 45],
                    ["name" => "Superman hold", "sets" => 3, "time_seconds" => 30, "rest_seconds" => 45],
                    ["name" => "Dead bug", "sets" => 3, "reps" => 12, "rest_seconds" => 45],
                ]
            ],
            [
                "name" => "HIIT Quema Grasa Casa",
                "description" => "Circuito de alta intensidad para quemar calorías y mejorar el cardio. Sin descanso entre ejercicios del circuito.",
                "level" => "Avanzado",
                "mode" => "home",
                "duration_minutes" => 25,
                "image_url" => "/assets/img/home-hiit-adv.jpg",
                "exercises" => [
                    ["name" => "Jumping jacks", "sets" => 4, "reps" => 30, "rest_seconds" => 15],
                    ["name" => "Mountain climbers", "sets" => 4, "reps" => 20, "rest_seconds" => 15],
                    ["name" => "Squat jumps", "sets" => 4, "reps" => 15, "rest_seconds" => 15],
                    ["name" => "Flexiones explosivas", "sets" => 4, "reps" => 10, "rest_seconds" => 15],
                    ["name" => "Burpees", "sets" => 4, "reps" => 10, "rest_seconds" => 60],
                ]
            ],

            // --- CASA – MANCUERNAS (5 nuevos) ---
            [
                "name" => "Primeras Mancuernas",
                "description" => "Introducción al entrenamiento con mancuernas. Técnica correcta, cargas ligeras y movimientos fundamentales.",
                "level" => "Básico",
                "mode" => "home",
                "duration_minutes" => 35,
                "image_url" => "/assets/img/home-db-beginner.jpg",
                "exercises" => [
                    ["name" => "Curl de bíceps con mancuernas", "sets" => 3, "reps" => 12, "weight_kg" => 8, "rest_seconds" => 60],
                    ["name" => "Press de hombros sentado (mancuernas)", "sets" => 3, "reps" => 12, "weight_kg" => 8, "rest_seconds" => 60],
                    ["name" => "Extensión de tríceps sobre la cabeza", "sets" => 3, "reps" => 12, "weight_kg" => 8, "rest_seconds" => 60],
                    ["name" => "Remo inclinado a dos brazos", "sets" => 3, "reps" => 12, "weight_kg" => 10, "rest_seconds" => 60],
                    ["name" => "Sentadilla goblet", "sets" => 3, "reps" => 15, "weight_kg" => 12, "rest_seconds" => 60],
                ]
            ],
            [
                "name" => "Torso Completo Mancuernas",
                "description" => "Entrenamiento de torso completo con mancuernas. Pecho, espalda, hombros y brazos en una sola sesión.",
                "level" => "Intermedio",
                "mode" => "home",
                "duration_minutes" => 50,
                "image_url" => "/assets/img/home-db-torso.jpg",
                "exercises" => [
                    ["name" => "Press de pecho con mancuernas en suelo", "sets" => 4, "reps" => 10, "weight_kg" => 20, "rest_seconds" => 75],
                    ["name" => "Remo a una mano con apoyo", "sets" => 4, "reps" => 12, "weight_kg" => 20, "rest_seconds" => 60],
                    ["name" => "Elevaciones laterales de hombro", "sets" => 3, "reps" => 15, "weight_kg" => 10, "rest_seconds" => 60],
                    ["name" => "Press Arnold", "sets" => 3, "reps" => 10, "weight_kg" => 14, "rest_seconds" => 75],
                    ["name" => "Curl martillo", "sets" => 3, "reps" => 12, "weight_kg" => 14, "rest_seconds" => 60],
                    ["name" => "Tríceps francés (mancuerna)", "sets" => 3, "reps" => 12, "weight_kg" => 12, "rest_seconds" => 60],
                ]
            ],
            [
                "name" => "Lower Body Mancuernas",
                "description" => "Tren inferior completo con mancuernas. Glúteos, cuádriceps, isquiosurales y gemelos.",
                "level" => "Intermedio",
                "mode" => "home",
                "duration_minutes" => 45,
                "image_url" => "/assets/img/home-db-lower.jpg",
                "exercises" => [
                    ["name" => "Sentadilla sumo con mancuerna", "sets" => 4, "reps" => 15, "weight_kg" => 24, "rest_seconds" => 75],
                    ["name" => "Peso muerto con mancuernas", "sets" => 4, "reps" => 10, "weight_kg" => 24, "rest_seconds" => 90],
                    ["name" => "Zancadas con mancuernas", "sets" => 4, "reps" => 12, "weight_kg" => 16, "rest_seconds" => 75],
                    ["name" => "Hip thrust con mancuerna en cadera", "sets" => 3, "reps" => 15, "weight_kg" => 24, "rest_seconds" => 60],
                    ["name" => "Elevación de gemelos de pie", "sets" => 4, "reps" => 20, "weight_kg" => 20, "rest_seconds" => 45],
                ]
            ],
            [
                "name" => "Push Mancuernas Avanzado",
                "description" => "Sesión de empuje de alta intensidad con mancuernas pesadas. Hipertrofia máxima de pecho, hombros y tríceps.",
                "level" => "Avanzado",
                "mode" => "home",
                "duration_minutes" => 55,
                "image_url" => "/assets/img/home-db-push.jpg",
                "exercises" => [
                    ["name" => "Press de pecho con mancuernas (suelo)", "sets" => 5, "reps" => 8, "weight_kg" => 32, "rest_seconds" => 90],
                    ["name" => "Pullover con mancuerna", "sets" => 4, "reps" => 10, "weight_kg" => 24, "rest_seconds" => 75],
                    ["name" => "Press Arnold pesado", "sets" => 4, "reps" => 8, "weight_kg" => 22, "rest_seconds" => 90],
                    ["name" => "Elevaciones frontales + laterales (superserie)", "sets" => 4, "reps" => 10, "weight_kg" => 12, "rest_seconds" => 60],
                    ["name" => "Fondos en sillas con lastre", "sets" => 4, "reps" => 12, "rest_seconds" => 75],
                ]
            ],
            [
                "name" => "Full Body Mancuernas Express",
                "description" => "Rutina completa en 40 minutos con mancuernas. Perfecta para días con poco tiempo. Nivel avanzado.",
                "level" => "Avanzado",
                "mode" => "home",
                "duration_minutes" => 40,
                "image_url" => "/assets/img/home-db-express.jpg",
                "exercises" => [
                    ["name" => "Clean & Press con mancuernas", "sets" => 4, "reps" => 8, "weight_kg" => 20, "rest_seconds" => 75],
                    ["name" => "Remo con mancuerna + curl (superserie)", "sets" => 4, "reps" => 10, "weight_kg" => 20, "rest_seconds" => 60],
                    ["name" => "Sentadilla + press aéreo (thruster)", "sets" => 4, "reps" => 10, "weight_kg" => 16, "rest_seconds" => 75],
                    ["name" => "Peso muerto rumano unilateral", "sets" => 3, "reps" => 10, "weight_kg" => 20, "rest_seconds" => 60],
                    ["name" => "Swing con mancuerna (kettlebell style)", "sets" => 4, "reps" => 20, "weight_kg" => 20, "rest_seconds" => 45],
                ]
            ],

            // --- NATACIÓN (5) ---
            [
                "name" => "Iniciación al Agua",
                "description" => "Primer contacto y técnica básica.",
                "level" => "Básico",
                "mode" => "swimming",
                "duration_minutes" => 30,
                "image_url" => "/assets/img/swim-beginner.jpg",
                "exercises" => [
                    ["name" => "Crol Suave", "sets" => 4, "distance_m" => 50, "rest_seconds" => 60],
                    ["name" => "Patada de Crol con Tabla", "sets" => 4, "distance_m" => 50, "rest_seconds" => 60],
                    ["name" => "Braza Suave", "sets" => 2, "distance_m" => 50, "rest_seconds" => 60]
                ]
            ],
            [
                "name" => "Resistencia Aeróbica A1",
                "description" => "Nado continuo para ganar fondo.",
                "level" => "Intermedio",
                "mode" => "swimming",
                "duration_minutes" => 45,
                "image_url" => "/assets/img/swim-endurance.jpg",
                "exercises" => [
                    ["name" => "Crol Ritmo Medio", "sets" => 1, "distance_m" => 400, "rest_seconds" => 60],
                    ["name" => "Espalda Doble", "sets" => 4, "distance_m" => 100, "rest_seconds" => 45],
                    ["name" => "Estilos Variado", "sets" => 4, "distance_m" => 50, "rest_seconds" => 30]
                ]
            ],
            [
                "name" => "Series de Velocidad",
                "description" => "Sprints cortos y descansos largos.",
                "level" => "Intermedio",
                "mode" => "swimming",
                "duration_minutes" => 40,
                "image_url" => "/assets/img/swim-speed.jpg",
                "exercises" => [
                    ["name" => "Calentamiento Variado", "sets" => 1, "distance_m" => 200, "rest_seconds" => 60],
                    ["name" => "Sprint Crol", "sets" => 8, "distance_m" => 25, "rest_seconds" => 60],
                    ["name" => "Recuperación Suave", "sets" => 1, "distance_m" => 100, "rest_seconds" => 0]
                ]
            ],
            [
                "name" => "Técnica Avanzada de Mariposa",
                "description" => "El estilo más exigente.",
                "level" => "Avanzado",
                "mode" => "swimming",
                "duration_minutes" => 50,
                "image_url" => "/assets/img/swim-butterfly.jpg",
                "exercises" => [
                    ["name" => "Ondulación Delfín", "sets" => 4, "distance_m" => 50, "rest_seconds" => 45],
                    ["name" => "Mariposa a un brazo", "sets" => 4, "distance_m" => 50, "rest_seconds" => 45],
                    ["name" => "Mariposa Completa", "sets" => 6, "distance_m" => 25, "rest_seconds" => 60]
                ]
            ],
            [
                "name" => "El \"1500\" - Fondo Máximo",
                "description" => "Prueba de resistencia mental y física.",
                "level" => "Avanzado",
                "mode" => "swimming",
                "duration_minutes" => 60,
                "image_url" => "/assets/img/swim-long.jpg",
                "exercises" => [
                    ["name" => "Calentamiento", "sets" => 1, "distance_m" => 300, "rest_seconds" => 60],
                    ["name" => "Test 1500m Crol", "sets" => 1, "distance_m" => 1500, "rest_seconds" => 0],
                    ["name" => "Vuelta a la calma", "sets" => 1, "distance_m" => 200, "rest_seconds" => 0]
                ]
            ],

            // --- PASOS (2) ---
            [
                "name" => "Pasos Diarios",
                "description" => "Registra los pasos del día y las calorías que marca tu reloj o pulsera de actividad.",
                "level" => "Básico",
                "mode" => "pasos",
                "duration_minutes" => 0,
                "image_url" => "/assets/img/steps.jpg",
                "exercises" => [
                    ["name" => "Pasos del día", "sets" => 1, "reps" => 0, "rest_seconds" => 0]
                ]
            ],
            [
                "name" => "Actividad General",
                "description" => "Para registrar cualquier actividad ligera del día: paseo, bici casual, tareas del hogar, etc.",
                "level" => "Básico",
                "mode" => "pasos",
                "duration_minutes" => 30,
                "image_url" => "/assets/img/steps.jpg",
                "exercises" => [
                    ["name" => "Actividad del día", "sets" => 1, "reps" => 0, "rest_seconds" => 0]
                ]
            ],
        ];

        foreach ($templates as $t) {
            $templateId = Str::uuid()->toString();

            DB::table('templates')->insert([
                'id' => $templateId,
                'name' => $t['name'],
                'description' => $t['description'],
                'level' => $t['level'],
                'mode' => $t['mode'],
                'duration_minutes' => $t['duration_minutes'],
                'image_url' => $t['image_url'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (isset($t['exercises'])) {
                foreach ($t['exercises'] as $ex) {
                    DB::table('template_exercises')->insert([
                        'template_id' => $templateId,
                        'name' => $ex['name'],
                        'sets' => $ex['sets'] ?? null,
                        'reps' => $ex['reps'] ?? null,
                        'rest_seconds' => $ex['rest_seconds'] ?? null,
                        'time_seconds' => $ex['time_seconds'] ?? null,
                        'distance_m' => $ex['distance_m'] ?? null,
                        'weight_kg' => $ex['weight_kg'] ?? null,
                        // 'laps' => $ex['laps'] ?? null, // Migracion no lo tenía explícito, revisar si falta
                    ]);
                }
            }
        }
    }
}
