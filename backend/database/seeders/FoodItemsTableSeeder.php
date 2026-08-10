<?php

namespace Database\Seeders;

use App\Models\FoodItem;
use Illuminate\Database\Seeder;

/**
 * Seeder de alimentos comunes de la dieta hispanohablante.
 * Todos los valores nutricionales son POR 100 GRAMOS.
 * Fuente: Tablas USDA + BEDCA (Base de Datos Española de Composición de Alimentos).
 *
 * Categorías incluidas:
 * - Carnes y aves
 * - Pescados y mariscos
 * - Lácteos y huevos
 * - Cereales y harinas
 * - Legumbres
 * - Verduras y hortalizas
 * - Frutas
 * - Frutos secos y semillas
 * - Aceites y grasas
 * - Bebidas
 * - Alimentos procesados comunes
 * - Snacks y dulces
 */
class FoodItemsTableSeeder extends Seeder
{
    public function run(): void
    {
        $foods = [
            // =====================================================================
            // CARNES Y AVES
            // =====================================================================
            ['name' => 'Pechuga de pollo cocida', 'category' => 'Carnes y aves', 'calories_per_100g' => 165, 'protein_per_100g' => 31, 'carbs_per_100g' => 0, 'fat_per_100g' => 3.6, 'fiber_per_100g' => 0],
            ['name' => 'Muslo de pollo sin piel', 'category' => 'Carnes y aves', 'calories_per_100g' => 177, 'protein_per_100g' => 25, 'carbs_per_100g' => 0, 'fat_per_100g' => 8.2, 'fiber_per_100g' => 0],
            ['name' => 'Pollo entero asado', 'category' => 'Carnes y aves', 'calories_per_100g' => 239, 'protein_per_100g' => 27, 'carbs_per_100g' => 0, 'fat_per_100g' => 14, 'fiber_per_100g' => 0],
            ['name' => 'Carne picada de ternera 80%', 'category' => 'Carnes y aves', 'calories_per_100g' => 254, 'protein_per_100g' => 17, 'carbs_per_100g' => 0, 'fat_per_100g' => 20, 'fiber_per_100g' => 0],
            ['name' => 'Filete de ternera', 'category' => 'Carnes y aves', 'calories_per_100g' => 213, 'protein_per_100g' => 26, 'carbs_per_100g' => 0, 'fat_per_100g' => 12, 'fiber_per_100g' => 0],
            ['name' => 'Lomo de cerdo', 'category' => 'Carnes y aves', 'calories_per_100g' => 242, 'protein_per_100g' => 27, 'carbs_per_100g' => 0, 'fat_per_100g' => 14, 'fiber_per_100g' => 0],
            ['name' => 'Chuleta de cerdo', 'category' => 'Carnes y aves', 'calories_per_100g' => 231, 'protein_per_100g' => 25, 'carbs_per_100g' => 0, 'fat_per_100g' => 14, 'fiber_per_100g' => 0],
            ['name' => 'Bacon / tocino', 'category' => 'Carnes y aves', 'calories_per_100g' => 541, 'protein_per_100g' => 37, 'carbs_per_100g' => 1.4, 'fat_per_100g' => 42, 'fiber_per_100g' => 0],
            ['name' => 'Jamón serrano', 'category' => 'Carnes y aves', 'calories_per_100g' => 241, 'protein_per_100g' => 34, 'carbs_per_100g' => 0.3, 'fat_per_100g' => 11, 'fiber_per_100g' => 0],
            ['name' => 'Jamón de York / cocido', 'category' => 'Carnes y aves', 'calories_per_100g' => 107, 'protein_per_100g' => 15, 'carbs_per_100g' => 2.5, 'fat_per_100g' => 4.2, 'fiber_per_100g' => 0],
            ['name' => 'Salchicha Frankfurt', 'category' => 'Carnes y aves', 'calories_per_100g' => 290, 'protein_per_100g' => 11, 'carbs_per_100g' => 3, 'fat_per_100g' => 26, 'fiber_per_100g' => 0],
            ['name' => 'Chorizo', 'category' => 'Carnes y aves', 'calories_per_100g' => 455, 'protein_per_100g' => 24, 'carbs_per_100g' => 1.9, 'fat_per_100g' => 38, 'fiber_per_100g' => 0],
            ['name' => 'Salchichón', 'category' => 'Carnes y aves', 'calories_per_100g' => 430, 'protein_per_100g' => 22, 'carbs_per_100g' => 2, 'fat_per_100g' => 37, 'fiber_per_100g' => 0],
            ['name' => 'Pavo (pechuga)', 'category' => 'Carnes y aves', 'calories_per_100g' => 135, 'protein_per_100g' => 30, 'carbs_per_100g' => 0, 'fat_per_100g' => 1, 'fiber_per_100g' => 0],
            ['name' => 'Carne de cordero', 'category' => 'Carnes y aves', 'calories_per_100g' => 294, 'protein_per_100g' => 25, 'carbs_per_100g' => 0, 'fat_per_100g' => 21, 'fiber_per_100g' => 0],
            ['name' => 'Hígado de ternera', 'category' => 'Carnes y aves', 'calories_per_100g' => 135, 'protein_per_100g' => 21, 'carbs_per_100g' => 3.9, 'fat_per_100g' => 3.6, 'fiber_per_100g' => 0],

            // =====================================================================
            // PESCADOS Y MARISCOS
            // =====================================================================
            ['name' => 'Salmón fresco', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 208, 'protein_per_100g' => 20, 'carbs_per_100g' => 0, 'fat_per_100g' => 13, 'fiber_per_100g' => 0],
            ['name' => 'Atún al natural (lata)', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 116, 'protein_per_100g' => 26, 'carbs_per_100g' => 0, 'fat_per_100g' => 1, 'fiber_per_100g' => 0],
            ['name' => 'Atún en aceite (lata)', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 198, 'protein_per_100g' => 25, 'carbs_per_100g' => 0, 'fat_per_100g' => 11, 'fiber_per_100g' => 0],
            ['name' => 'Merluza', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 86, 'protein_per_100g' => 17, 'carbs_per_100g' => 0, 'fat_per_100g' => 2, 'fiber_per_100g' => 0],
            ['name' => 'Bacalao seco salado', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 290, 'protein_per_100g' => 64, 'carbs_per_100g' => 0, 'fat_per_100g' => 2, 'fiber_per_100g' => 0],
            ['name' => 'Dorada al horno', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 96, 'protein_per_100g' => 18, 'carbs_per_100g' => 0, 'fat_per_100g' => 2.5, 'fiber_per_100g' => 0],
            ['name' => 'Sardina (fresca)', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 208, 'protein_per_100g' => 25, 'carbs_per_100g' => 0, 'fat_per_100g' => 12, 'fiber_per_100g' => 0],
            ['name' => 'Sardinas en lata', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 208, 'protein_per_100g' => 24, 'carbs_per_100g' => 0, 'fat_per_100g' => 12, 'fiber_per_100g' => 0],
            ['name' => 'Gamba cocida', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 99, 'protein_per_100g' => 21, 'carbs_per_100g' => 0.9, 'fat_per_100g' => 1.1, 'fiber_per_100g' => 0],
            ['name' => 'Pulpo cocido', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 82, 'protein_per_100g' => 15, 'carbs_per_100g' => 2.2, 'fat_per_100g' => 1, 'fiber_per_100g' => 0],
            ['name' => 'Calamar', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 92, 'protein_per_100g' => 16, 'carbs_per_100g' => 3.1, 'fat_per_100g' => 1.4, 'fiber_per_100g' => 0],
            ['name' => 'Mejillón cocido', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 172, 'protein_per_100g' => 24, 'carbs_per_100g' => 7.4, 'fat_per_100g' => 4.5, 'fiber_per_100g' => 0],
            ['name' => 'Lubina a la plancha', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 97, 'protein_per_100g' => 18, 'carbs_per_100g' => 0, 'fat_per_100g' => 2.5, 'fiber_per_100g' => 0],
            ['name' => 'Caballa', 'category' => 'Pescados y mariscos', 'calories_per_100g' => 205, 'protein_per_100g' => 19, 'carbs_per_100g' => 0, 'fat_per_100g' => 14, 'fiber_per_100g' => 0],

            // =====================================================================
            // LÁCTEOS Y HUEVOS
            // =====================================================================
            ['name' => 'Huevo entero', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 155, 'protein_per_100g' => 13, 'carbs_per_100g' => 1.1, 'fat_per_100g' => 11, 'fiber_per_100g' => 0],
            ['name' => 'Clara de huevo', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 52, 'protein_per_100g' => 11, 'carbs_per_100g' => 0.7, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 0],
            ['name' => 'Yema de huevo', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 322, 'protein_per_100g' => 16, 'carbs_per_100g' => 3.6, 'fat_per_100g' => 27, 'fiber_per_100g' => 0],
            ['name' => 'Leche entera', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 61, 'protein_per_100g' => 3.2, 'carbs_per_100g' => 4.8, 'fat_per_100g' => 3.3, 'fiber_per_100g' => 0],
            ['name' => 'Leche desnatada', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 35, 'protein_per_100g' => 3.4, 'carbs_per_100g' => 5, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 0],
            ['name' => 'Leche semidesnatada', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 46, 'protein_per_100g' => 3.3, 'carbs_per_100g' => 4.9, 'fat_per_100g' => 1.6, 'fiber_per_100g' => 0],
            ['name' => 'Yogur natural entero', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 61, 'protein_per_100g' => 3.5, 'carbs_per_100g' => 4.7, 'fat_per_100g' => 3.3, 'fiber_per_100g' => 0],
            ['name' => 'Yogur natural desnatado', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 40, 'protein_per_100g' => 4, 'carbs_per_100g' => 4.9, 'fat_per_100g' => 0.4, 'fiber_per_100g' => 0],
            ['name' => 'Queso fresco', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 98, 'protein_per_100g' => 11, 'carbs_per_100g' => 3.2, 'fat_per_100g' => 4.3, 'fiber_per_100g' => 0],
            ['name' => 'Queso manchego curado', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 399, 'protein_per_100g' => 26, 'carbs_per_100g' => 0.5, 'fat_per_100g' => 32, 'fiber_per_100g' => 0],
            ['name' => 'Queso mozzarella', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 280, 'protein_per_100g' => 17, 'carbs_per_100g' => 2.2, 'fat_per_100g' => 22, 'fiber_per_100g' => 0],
            ['name' => 'Queso cottage', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 98, 'protein_per_100g' => 11, 'carbs_per_100g' => 3.4, 'fat_per_100g' => 4.3, 'fiber_per_100g' => 0],
            ['name' => 'Requesón / ricotta', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 174, 'protein_per_100g' => 11, 'carbs_per_100g' => 3, 'fat_per_100g' => 13, 'fiber_per_100g' => 0],
            ['name' => 'Mantequilla', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 717, 'protein_per_100g' => 0.9, 'carbs_per_100g' => 0.1, 'fat_per_100g' => 81, 'fiber_per_100g' => 0],
            ['name' => 'Queso parmesano rallado', 'category' => 'Lácteos y huevos', 'calories_per_100g' => 431, 'protein_per_100g' => 38, 'carbs_per_100g' => 3.2, 'fat_per_100g' => 29, 'fiber_per_100g' => 0],

            // =====================================================================
            // CEREALES Y HARINAS
            // =====================================================================
            ['name' => 'Arroz blanco cocido', 'category' => 'Cereales y harinas', 'calories_per_100g' => 130, 'protein_per_100g' => 2.7, 'carbs_per_100g' => 28, 'fat_per_100g' => 0.3, 'fiber_per_100g' => 0.4],
            ['name' => 'Arroz integral cocido', 'category' => 'Cereales y harinas', 'calories_per_100g' => 123, 'protein_per_100g' => 2.6, 'carbs_per_100g' => 26, 'fat_per_100g' => 1, 'fiber_per_100g' => 1.8],
            ['name' => 'Arroz blanco crudo', 'category' => 'Cereales y harinas', 'calories_per_100g' => 360, 'protein_per_100g' => 7, 'carbs_per_100g' => 79, 'fat_per_100g' => 0.7, 'fiber_per_100g' => 1],
            ['name' => 'Pasta cocida (espagueti)', 'category' => 'Cereales y harinas', 'calories_per_100g' => 158, 'protein_per_100g' => 5.8, 'carbs_per_100g' => 31, 'fat_per_100g' => 0.9, 'fiber_per_100g' => 1.8],
            ['name' => 'Pasta integral cocida', 'category' => 'Cereales y harinas', 'calories_per_100g' => 149, 'protein_per_100g' => 5.3, 'carbs_per_100g' => 29, 'fat_per_100g' => 0.8, 'fiber_per_100g' => 3.9],
            ['name' => 'Pan blanco de trigo', 'category' => 'Cereales y harinas', 'calories_per_100g' => 265, 'protein_per_100g' => 9, 'carbs_per_100g' => 49, 'fat_per_100g' => 3.2, 'fiber_per_100g' => 2.7],
            ['name' => 'Pan integral', 'category' => 'Cereales y harinas', 'calories_per_100g' => 247, 'protein_per_100g' => 9, 'carbs_per_100g' => 43, 'fat_per_100g' => 4, 'fiber_per_100g' => 6],
            ['name' => 'Pan de centeno', 'category' => 'Cereales y harinas', 'calories_per_100g' => 259, 'protein_per_100g' => 8.5, 'carbs_per_100g' => 48, 'fat_per_100g' => 3.3, 'fiber_per_100g' => 5.8],
            ['name' => 'Avena cruda (copos)', 'category' => 'Cereales y harinas', 'calories_per_100g' => 389, 'protein_per_100g' => 17, 'carbs_per_100g' => 66, 'fat_per_100g' => 7, 'fiber_per_100g' => 10.6],
            ['name' => 'Avena cocida (porridge)', 'category' => 'Cereales y harinas', 'calories_per_100g' => 71, 'protein_per_100g' => 2.5, 'carbs_per_100g' => 12, 'fat_per_100g' => 1.5, 'fiber_per_100g' => 1.7],
            ['name' => 'Quinoa cocida', 'category' => 'Cereales y harinas', 'calories_per_100g' => 120, 'protein_per_100g' => 4.4, 'carbs_per_100g' => 21, 'fat_per_100g' => 1.9, 'fiber_per_100g' => 2.8],
            ['name' => 'Tortilla de maíz', 'category' => 'Cereales y harinas', 'calories_per_100g' => 218, 'protein_per_100g' => 5.7, 'carbs_per_100g' => 44, 'fat_per_100g' => 2.8, 'fiber_per_100g' => 5.2],
            ['name' => 'Maíz cocido', 'category' => 'Cereales y harinas', 'calories_per_100g' => 96, 'protein_per_100g' => 3.4, 'carbs_per_100g' => 21, 'fat_per_100g' => 1.5, 'fiber_per_100g' => 2.4],
            ['name' => 'Harina de trigo', 'category' => 'Cereales y harinas', 'calories_per_100g' => 364, 'protein_per_100g' => 10, 'carbs_per_100g' => 76, 'fat_per_100g' => 1, 'fiber_per_100g' => 2.7],
            ['name' => 'Cereales de desayuno (tipo corn flakes)', 'category' => 'Cereales y harinas', 'calories_per_100g' => 379, 'protein_per_100g' => 7, 'carbs_per_100g' => 84, 'fat_per_100g' => 1, 'fiber_per_100g' => 2],
            ['name' => 'Granola', 'category' => 'Cereales y harinas', 'calories_per_100g' => 471, 'protein_per_100g' => 10, 'carbs_per_100g' => 64, 'fat_per_100g' => 20, 'fiber_per_100g' => 6],
            ['name' => 'Cuscús cocido', 'category' => 'Cereales y harinas', 'calories_per_100g' => 112, 'protein_per_100g' => 3.8, 'carbs_per_100g' => 23, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 1.4],

            // =====================================================================
            // LEGUMBRES
            // =====================================================================
            ['name' => 'Lentejas cocidas', 'category' => 'Legumbres', 'calories_per_100g' => 116, 'protein_per_100g' => 9, 'carbs_per_100g' => 20, 'fat_per_100g' => 0.4, 'fiber_per_100g' => 7.9],
            ['name' => 'Garbanzos cocidos', 'category' => 'Legumbres', 'calories_per_100g' => 164, 'protein_per_100g' => 8.9, 'carbs_per_100g' => 27, 'fat_per_100g' => 2.6, 'fiber_per_100g' => 7.6],
            ['name' => 'Judías blancas cocidas', 'category' => 'Legumbres', 'calories_per_100g' => 127, 'protein_per_100g' => 8.7, 'carbs_per_100g' => 22, 'fat_per_100g' => 0.5, 'fiber_per_100g' => 6.3],
            ['name' => 'Alubias negras cocidas', 'category' => 'Legumbres', 'calories_per_100g' => 132, 'protein_per_100g' => 8.9, 'carbs_per_100g' => 24, 'fat_per_100g' => 0.5, 'fiber_per_100g' => 8.7],
            ['name' => 'Edamame (soja verde)', 'category' => 'Legumbres', 'calories_per_100g' => 121, 'protein_per_100g' => 11, 'carbs_per_100g' => 10, 'fat_per_100g' => 5.2, 'fiber_per_100g' => 5.2],
            ['name' => 'Tofu firme', 'category' => 'Legumbres', 'calories_per_100g' => 76, 'protein_per_100g' => 8, 'carbs_per_100g' => 1.9, 'fat_per_100g' => 4.8, 'fiber_per_100g' => 0.3],
            ['name' => 'Guisantes cocidos', 'category' => 'Legumbres', 'calories_per_100g' => 84, 'protein_per_100g' => 5.4, 'carbs_per_100g' => 15, 'fat_per_100g' => 0.4, 'fiber_per_100g' => 5.5],
            ['name' => 'Habas cocidas', 'category' => 'Legumbres', 'calories_per_100g' => 110, 'protein_per_100g' => 8, 'carbs_per_100g' => 20, 'fat_per_100g' => 0.6, 'fiber_per_100g' => 6.7],

            // =====================================================================
            // VERDURAS Y HORTALIZAS
            // =====================================================================
            ['name' => 'Brócoli cocido', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 34, 'protein_per_100g' => 2.4, 'carbs_per_100g' => 7, 'fat_per_100g' => 0.4, 'fiber_per_100g' => 3.3],
            ['name' => 'Espinacas crudas', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 23, 'protein_per_100g' => 2.9, 'carbs_per_100g' => 3.6, 'fat_per_100g' => 0.4, 'fiber_per_100g' => 2.2],
            ['name' => 'Espinacas cocidas', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 23, 'protein_per_100g' => 3, 'carbs_per_100g' => 3.8, 'fat_per_100g' => 0.3, 'fiber_per_100g' => 2.4],
            ['name' => 'Tomate fresco', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 18, 'protein_per_100g' => 0.9, 'carbs_per_100g' => 3.9, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 1.2],
            ['name' => 'Lechuga romana', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 17, 'protein_per_100g' => 1.2, 'carbs_per_100g' => 3.3, 'fat_per_100g' => 0.3, 'fiber_per_100g' => 2.1],
            ['name' => 'Zanahoria cruda', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 41, 'protein_per_100g' => 0.9, 'carbs_per_100g' => 10, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 2.8],
            ['name' => 'Patata cocida', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 87, 'protein_per_100g' => 1.9, 'carbs_per_100g' => 20, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 1.8],
            ['name' => 'Patata asada', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 93, 'protein_per_100g' => 2.5, 'carbs_per_100g' => 21, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 2.3],
            ['name' => 'Boniato / batata cocida', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 86, 'protein_per_100g' => 1.6, 'carbs_per_100g' => 20, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 3],
            ['name' => 'Cebolla cruda', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 40, 'protein_per_100g' => 1.1, 'carbs_per_100g' => 9.3, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 1.7],
            ['name' => 'Pimiento rojo', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 31, 'protein_per_100g' => 1, 'carbs_per_100g' => 7.2, 'fat_per_100g' => 0.3, 'fiber_per_100g' => 2.1],
            ['name' => 'Pimiento verde', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 20, 'protein_per_100g' => 0.9, 'carbs_per_100g' => 4.6, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 1.7],
            ['name' => 'Calabacín cocido', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 17, 'protein_per_100g' => 1.2, 'carbs_per_100g' => 3.6, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 1.1],
            ['name' => 'Berenjena asada', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 35, 'protein_per_100g' => 0.8, 'carbs_per_100g' => 8.7, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 2.5],
            ['name' => 'Pepino', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 16, 'protein_per_100g' => 0.7, 'carbs_per_100g' => 3.6, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 0.5],
            ['name' => 'Aguacate', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 160, 'protein_per_100g' => 2, 'carbs_per_100g' => 9, 'fat_per_100g' => 15, 'fiber_per_100g' => 6.7],
            ['name' => 'Coliflor cocida', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 23, 'protein_per_100g' => 1.9, 'carbs_per_100g' => 4.9, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 2],
            ['name' => 'Champiñón crudo', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 22, 'protein_per_100g' => 3.1, 'carbs_per_100g' => 3.3, 'fat_per_100g' => 0.3, 'fiber_per_100g' => 1],
            ['name' => 'Ajo', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 149, 'protein_per_100g' => 6.4, 'carbs_per_100g' => 33, 'fat_per_100g' => 0.5, 'fiber_per_100g' => 2.1],
            ['name' => 'Maíz dulce en lata', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 86, 'protein_per_100g' => 3.2, 'carbs_per_100g' => 19, 'fat_per_100g' => 1.2, 'fiber_per_100g' => 1.8],
            ['name' => 'Espárragos cocidos', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 22, 'protein_per_100g' => 2.4, 'carbs_per_100g' => 4.1, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 2.1],
            ['name' => 'Judías verdes cocidas', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 35, 'protein_per_100g' => 1.9, 'carbs_per_100g' => 8, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 3.4],
            ['name' => 'Pepinillos en vinagre', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 11, 'protein_per_100g' => 0.7, 'carbs_per_100g' => 2.3, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 1],
            ['name' => 'Rúcula', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 25, 'protein_per_100g' => 2.6, 'carbs_per_100g' => 3.7, 'fat_per_100g' => 0.7, 'fiber_per_100g' => 1.6],
            ['name' => 'Coles de Bruselas', 'category' => 'Verduras y hortalizas', 'calories_per_100g' => 43, 'protein_per_100g' => 3.4, 'carbs_per_100g' => 9, 'fat_per_100g' => 0.3, 'fiber_per_100g' => 3.8],

            // =====================================================================
            // FRUTAS
            // =====================================================================
            ['name' => 'Plátano / banana', 'category' => 'Frutas', 'calories_per_100g' => 89, 'protein_per_100g' => 1.1, 'carbs_per_100g' => 23, 'fat_per_100g' => 0.3, 'fiber_per_100g' => 2.6],
            ['name' => 'Manzana', 'category' => 'Frutas', 'calories_per_100g' => 52, 'protein_per_100g' => 0.3, 'carbs_per_100g' => 14, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 2.4],
            ['name' => 'Naranja', 'category' => 'Frutas', 'calories_per_100g' => 47, 'protein_per_100g' => 0.9, 'carbs_per_100g' => 12, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 2.4],
            ['name' => 'Pera', 'category' => 'Frutas', 'calories_per_100g' => 57, 'protein_per_100g' => 0.4, 'carbs_per_100g' => 15, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 3.1],
            ['name' => 'Fresa / frutilla', 'category' => 'Frutas', 'calories_per_100g' => 32, 'protein_per_100g' => 0.7, 'carbs_per_100g' => 7.7, 'fat_per_100g' => 0.3, 'fiber_per_100g' => 2],
            ['name' => 'Uva verde', 'category' => 'Frutas', 'calories_per_100g' => 67, 'protein_per_100g' => 0.6, 'carbs_per_100g' => 17, 'fat_per_100g' => 0.4, 'fiber_per_100g' => 0.9],
            ['name' => 'Sandía', 'category' => 'Frutas', 'calories_per_100g' => 30, 'protein_per_100g' => 0.6, 'carbs_per_100g' => 7.6, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 0.4],
            ['name' => 'Melón', 'category' => 'Frutas', 'calories_per_100g' => 34, 'protein_per_100g' => 0.8, 'carbs_per_100g' => 8.2, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 0.9],
            ['name' => 'Mango', 'category' => 'Frutas', 'calories_per_100g' => 60, 'protein_per_100g' => 0.8, 'carbs_per_100g' => 15, 'fat_per_100g' => 0.4, 'fiber_per_100g' => 1.6],
            ['name' => 'Piña natural', 'category' => 'Frutas', 'calories_per_100g' => 50, 'protein_per_100g' => 0.5, 'carbs_per_100g' => 13, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 1.4],
            ['name' => 'Kiwi', 'category' => 'Frutas', 'calories_per_100g' => 61, 'protein_per_100g' => 1.1, 'carbs_per_100g' => 15, 'fat_per_100g' => 0.5, 'fiber_per_100g' => 3],
            ['name' => 'Melocotón / durazno', 'category' => 'Frutas', 'calories_per_100g' => 39, 'protein_per_100g' => 0.9, 'carbs_per_100g' => 10, 'fat_per_100g' => 0.3, 'fiber_per_100g' => 1.5],
            ['name' => 'Cereza', 'category' => 'Frutas', 'calories_per_100g' => 63, 'protein_per_100g' => 1.1, 'carbs_per_100g' => 16, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 2.1],
            ['name' => 'Arándanos', 'category' => 'Frutas', 'calories_per_100g' => 57, 'protein_per_100g' => 0.7, 'carbs_per_100g' => 14, 'fat_per_100g' => 0.3, 'fiber_per_100g' => 2.4],
            ['name' => 'Limón (zumo)', 'category' => 'Frutas', 'calories_per_100g' => 22, 'protein_per_100g' => 0.4, 'carbs_per_100g' => 6.9, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 0.3],

            // =====================================================================
            // FRUTOS SECOS Y SEMILLAS
            // =====================================================================
            ['name' => 'Almendras crudas', 'category' => 'Frutos secos y semillas', 'calories_per_100g' => 579, 'protein_per_100g' => 21, 'carbs_per_100g' => 22, 'fat_per_100g' => 50, 'fiber_per_100g' => 12.5],
            ['name' => 'Nueces', 'category' => 'Frutos secos y semillas', 'calories_per_100g' => 654, 'protein_per_100g' => 15, 'carbs_per_100g' => 14, 'fat_per_100g' => 65, 'fiber_per_100g' => 6.7],
            ['name' => 'Cacahuetes (sin sal)', 'category' => 'Frutos secos y semillas', 'calories_per_100g' => 567, 'protein_per_100g' => 26, 'carbs_per_100g' => 16, 'fat_per_100g' => 49, 'fiber_per_100g' => 8.5],
            ['name' => 'Mantequilla de cacahuete', 'category' => 'Frutos secos y semillas', 'calories_per_100g' => 588, 'protein_per_100g' => 25, 'carbs_per_100g' => 20, 'fat_per_100g' => 50, 'fiber_per_100g' => 6],
            ['name' => 'Semillas de chía', 'category' => 'Frutos secos y semillas', 'calories_per_100g' => 486, 'protein_per_100g' => 17, 'carbs_per_100g' => 42, 'fat_per_100g' => 31, 'fiber_per_100g' => 34],
            ['name' => 'Semillas de lino', 'category' => 'Frutos secos y semillas', 'calories_per_100g' => 534, 'protein_per_100g' => 18, 'carbs_per_100g' => 29, 'fat_per_100g' => 42, 'fiber_per_100g' => 27],
            ['name' => 'Pipas de girasol', 'category' => 'Frutos secos y semillas', 'calories_per_100g' => 584, 'protein_per_100g' => 21, 'carbs_per_100g' => 20, 'fat_per_100g' => 51, 'fiber_per_100g' => 8.6],
            ['name' => 'Anacardos', 'category' => 'Frutos secos y semillas', 'calories_per_100g' => 553, 'protein_per_100g' => 18, 'carbs_per_100g' => 30, 'fat_per_100g' => 44, 'fiber_per_100g' => 3.3],
            ['name' => 'Pistachos', 'category' => 'Frutos secos y semillas', 'calories_per_100g' => 560, 'protein_per_100g' => 20, 'carbs_per_100g' => 28, 'fat_per_100g' => 45, 'fiber_per_100g' => 10.3],
            ['name' => 'Avellanas', 'category' => 'Frutos secos y semillas', 'calories_per_100g' => 628, 'protein_per_100g' => 15, 'carbs_per_100g' => 17, 'fat_per_100g' => 61, 'fiber_per_100g' => 9.7],

            // =====================================================================
            // ACEITES Y GRASAS
            // =====================================================================
            ['name' => 'Aceite de oliva virgen extra', 'category' => 'Aceites y grasas', 'calories_per_100g' => 884, 'protein_per_100g' => 0, 'carbs_per_100g' => 0, 'fat_per_100g' => 100, 'fiber_per_100g' => 0],
            ['name' => 'Aceite de girasol', 'category' => 'Aceites y grasas', 'calories_per_100g' => 884, 'protein_per_100g' => 0, 'carbs_per_100g' => 0, 'fat_per_100g' => 100, 'fiber_per_100g' => 0],
            ['name' => 'Aceite de coco', 'category' => 'Aceites y grasas', 'calories_per_100g' => 862, 'protein_per_100g' => 0, 'carbs_per_100g' => 0, 'fat_per_100g' => 100, 'fiber_per_100g' => 0],
            ['name' => 'Mayonesa', 'category' => 'Aceites y grasas', 'calories_per_100g' => 680, 'protein_per_100g' => 1, 'carbs_per_100g' => 0.6, 'fat_per_100g' => 75, 'fiber_per_100g' => 0],

            // =====================================================================
            // SALSAS Y CONDIMENTOS
            // =====================================================================
            ['name' => 'Ketchup', 'category' => 'Salsas y condimentos', 'calories_per_100g' => 112, 'protein_per_100g' => 1.4, 'carbs_per_100g' => 27, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 0.3],
            ['name' => 'Salsa de tomate (frito)', 'category' => 'Salsas y condimentos', 'calories_per_100g' => 80, 'protein_per_100g' => 1.4, 'carbs_per_100g' => 18, 'fat_per_100g' => 0.4, 'fiber_per_100g' => 1.2],
            ['name' => 'Hummus', 'category' => 'Salsas y condimentos', 'calories_per_100g' => 166, 'protein_per_100g' => 8, 'carbs_per_100g' => 14, 'fat_per_100g' => 9.6, 'fiber_per_100g' => 6],
            ['name' => 'Guacamole', 'category' => 'Salsas y condimentos', 'calories_per_100g' => 155, 'protein_per_100g' => 1.9, 'carbs_per_100g' => 8.5, 'fat_per_100g' => 13, 'fiber_per_100g' => 6.4],

            // =====================================================================
            // BEBIDAS
            // =====================================================================
            ['name' => 'Zumo de naranja natural', 'category' => 'Bebidas', 'calories_per_100g' => 45, 'protein_per_100g' => 0.7, 'carbs_per_100g' => 10, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 0.2],
            ['name' => 'Leche de avena', 'category' => 'Bebidas', 'calories_per_100g' => 47, 'protein_per_100g' => 1, 'carbs_per_100g' => 9, 'fat_per_100g' => 1.5, 'fiber_per_100g' => 0.5],
            ['name' => 'Leche de almendras (sin azúcar)', 'category' => 'Bebidas', 'calories_per_100g' => 15, 'protein_per_100g' => 0.6, 'carbs_per_100g' => 0.3, 'fat_per_100g' => 1.2, 'fiber_per_100g' => 0.4],
            ['name' => 'Leche de soja', 'category' => 'Bebidas', 'calories_per_100g' => 43, 'protein_per_100g' => 3.3, 'carbs_per_100g' => 3.7, 'fat_per_100g' => 2, 'fiber_per_100g' => 0.4],
            ['name' => 'Batido de proteínas (whey)', 'category' => 'Bebidas', 'calories_per_100g' => 400, 'protein_per_100g' => 75, 'carbs_per_100g' => 15, 'fat_per_100g' => 7, 'fiber_per_100g' => 2],
            ['name' => 'Cerveza (5%)', 'category' => 'Bebidas', 'calories_per_100g' => 43, 'protein_per_100g' => 0.5, 'carbs_per_100g' => 3.6, 'fat_per_100g' => 0, 'fiber_per_100g' => 0],
            ['name' => 'Vino tinto', 'category' => 'Bebidas', 'calories_per_100g' => 85, 'protein_per_100g' => 0.1, 'carbs_per_100g' => 2.6, 'fat_per_100g' => 0, 'fiber_per_100g' => 0],

            // =====================================================================
            // SNACKS Y DULCES
            // =====================================================================
            ['name' => 'Chocolate negro 70%', 'category' => 'Snacks y dulces', 'calories_per_100g' => 598, 'protein_per_100g' => 8, 'carbs_per_100g' => 46, 'fat_per_100g' => 43, 'fiber_per_100g' => 11],
            ['name' => 'Chocolate con leche', 'category' => 'Snacks y dulces', 'calories_per_100g' => 535, 'protein_per_100g' => 7.7, 'carbs_per_100g' => 57, 'fat_per_100g' => 30, 'fiber_per_100g' => 2],
            ['name' => 'Patatas fritas (bolsa)', 'category' => 'Snacks y dulces', 'calories_per_100g' => 536, 'protein_per_100g' => 7, 'carbs_per_100g' => 53, 'fat_per_100g' => 35, 'fiber_per_100g' => 4.8],
            ['name' => 'Galletas tipo María', 'category' => 'Snacks y dulces', 'calories_per_100g' => 429, 'protein_per_100g' => 7.4, 'carbs_per_100g' => 73, 'fat_per_100g' => 12, 'fiber_per_100g' => 2.4],
            ['name' => 'Miel', 'category' => 'Snacks y dulces', 'calories_per_100g' => 304, 'protein_per_100g' => 0.3, 'carbs_per_100g' => 82, 'fat_per_100g' => 0, 'fiber_per_100g' => 0.2],
            ['name' => 'Mermelada de fresa', 'category' => 'Snacks y dulces', 'calories_per_100g' => 250, 'protein_per_100g' => 0.4, 'carbs_per_100g' => 65, 'fat_per_100g' => 0.1, 'fiber_per_100g' => 1.2],
            ['name' => 'Barrita de cereal', 'category' => 'Snacks y dulces', 'calories_per_100g' => 374, 'protein_per_100g' => 7, 'carbs_per_100g' => 71, 'fat_per_100g' => 7, 'fiber_per_100g' => 3.5],
            ['name' => 'Croissant', 'category' => 'Snacks y dulces', 'calories_per_100g' => 406, 'protein_per_100g' => 8.2, 'carbs_per_100g' => 45, 'fat_per_100g' => 21, 'fiber_per_100g' => 2.3],

            // =====================================================================
            // PROTEÍNAS EN POLVO Y SUPLEMENTOS
            // =====================================================================
            ['name' => 'Proteína whey (polvo)', 'category' => 'Suplementos', 'calories_per_100g' => 380, 'protein_per_100g' => 74, 'carbs_per_100g' => 14, 'fat_per_100g' => 6, 'fiber_per_100g' => 1],
            ['name' => 'Proteína caseína (polvo)', 'category' => 'Suplementos', 'calories_per_100g' => 370, 'protein_per_100g' => 76, 'carbs_per_100g' => 10, 'fat_per_100g' => 5, 'fiber_per_100g' => 1],
            ['name' => 'Creatina monohidrato', 'category' => 'Suplementos', 'calories_per_100g' => 0, 'protein_per_100g' => 0, 'carbs_per_100g' => 0, 'fat_per_100g' => 0, 'fiber_per_100g' => 0],
            ['name' => 'BCAA en polvo', 'category' => 'Suplementos', 'calories_per_100g' => 280, 'protein_per_100g' => 70, 'carbs_per_100g' => 0, 'fat_per_100g' => 0, 'fiber_per_100g' => 0],

            // =====================================================================
            // ALIMENTOS PREPARADOS COMUNES
            // =====================================================================
            ['name' => 'Tortilla española (patata + huevo)', 'category' => 'Preparados', 'calories_per_100g' => 185, 'protein_per_100g' => 7.5, 'carbs_per_100g' => 14, 'fat_per_100g' => 11, 'fiber_per_100g' => 1.2],
            ['name' => 'Pizza margarita', 'category' => 'Preparados', 'calories_per_100g' => 266, 'protein_per_100g' => 11, 'carbs_per_100g' => 33, 'fat_per_100g' => 10, 'fiber_per_100g' => 2.3],
            ['name' => 'Hamburguesa (carne + pan)', 'category' => 'Preparados', 'calories_per_100g' => 295, 'protein_per_100g' => 17, 'carbs_per_100g' => 24, 'fat_per_100g' => 14, 'fiber_per_100g' => 1.5],
            ['name' => 'Paella de marisco', 'category' => 'Preparados', 'calories_per_100g' => 160, 'protein_per_100g' => 8, 'carbs_per_100g' => 28, 'fat_per_100g' => 2.5, 'fiber_per_100g' => 0.8],
            ['name' => 'Caldo de pollo', 'category' => 'Preparados', 'calories_per_100g' => 15, 'protein_per_100g' => 1.7, 'carbs_per_100g' => 1.7, 'fat_per_100g' => 0.2, 'fiber_per_100g' => 0],
        ];

        // Insertamos todos los alimentos como verificados (del sistema)
        foreach ($foods as $food) {
            FoodItem::create(array_merge($food, [
                'is_verified' => true,
                'sugar_per_100g' => 0,  // Valor por defecto para los que no tenemos dato
            ]));
        }

        $this->command->info('✅ Seeder de alimentos completado: ' . count($foods) . ' alimentos cargados.');
    }
}
