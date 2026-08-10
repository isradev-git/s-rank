<?php

/**
 * Mensajes de validación en español.
 *
 * Laravel 12 no trae traducciones: sin este fichero, un registro con el correo repetido
 * responde «The email has already been taken» y la app tendría que traducir por su cuenta
 * textos que llegan del servidor. Aquí se traducen una vez y valen para toda la API.
 *
 * `fallback_locale` sigue en «en»: si algún día Laravel añade una regla nueva que aquí
 * falte, el mensaje saldrá en inglés en vez de imprimir la clave en crudo.
 *
 * ponytail: los mensajes que empiezan por :attribute salen en minúscula («el correo ya
 * está en uso»), porque Laravel no capitaliza el resultado. Como error debajo de un campo
 * se lee bien. Si en la fase 1.1 queda mal en pantalla, el arreglo es capitalizar en el
 * cliente al pintarlo, no aquí: poner «El correo» en `attributes` estropearía los
 * mensajes donde el campo va en mitad de la frase («Falta El correo»).
 */

return [

    'accepted'             => 'Tienes que aceptar :attribute.',
    'accepted_if'          => 'Tienes que aceptar :attribute cuando :other sea :value.',
    'active_url'           => ':attribute no es una dirección web válida.',
    'after'                => ':attribute tiene que ser posterior a :date.',
    'after_or_equal'       => ':attribute tiene que ser :date o posterior.',
    'alpha'                => ':attribute solo puede contener letras.',
    'alpha_dash'           => ':attribute solo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num'            => ':attribute solo puede contener letras y números.',
    'any_of'               => ':attribute no es válido.',
    'array'                => ':attribute tiene que ser una lista.',
    'ascii'                => ':attribute solo puede contener caracteres y símbolos de un byte.',
    'before'               => ':attribute tiene que ser anterior a :date.',
    'before_or_equal'      => ':attribute tiene que ser :date o anterior.',

    'between' => [
        'array'   => ':attribute tiene que tener entre :min y :max elementos.',
        'file'    => ':attribute tiene que ocupar entre :min y :max kilobytes.',
        'numeric' => ':attribute tiene que estar entre :min y :max.',
        'string'  => ':attribute tiene que tener entre :min y :max caracteres.',
    ],

    'boolean'              => ':attribute solo puede ser verdadero o falso.',
    'can'                  => ':attribute contiene un valor no permitido.',
    'confirmed'            => ':attribute no coincide con la confirmación.',
    'contains'             => 'A :attribute le falta un valor obligatorio.',
    'current_password'     => 'La contraseña no es correcta.',
    'date'                 => ':attribute no es una fecha válida.',
    'date_equals'          => ':attribute tiene que ser igual a :date.',
    'date_format'          => ':attribute no tiene el formato :format.',
    'decimal'              => ':attribute tiene que tener :decimal decimales.',
    'declined'             => 'Tienes que rechazar :attribute.',
    'declined_if'          => 'Tienes que rechazar :attribute cuando :other sea :value.',
    'different'            => ':attribute y :other tienen que ser distintos.',
    'digits'               => ':attribute tiene que tener :digits dígitos.',
    'digits_between'       => ':attribute tiene que tener entre :min y :max dígitos.',
    'dimensions'           => ':attribute tiene unas dimensiones de imagen no válidas.',
    'distinct'             => ':attribute está repetido.',
    'doesnt_end_with'      => ':attribute no puede terminar por ninguno de estos valores: :values.',
    'doesnt_start_with'    => ':attribute no puede empezar por ninguno de estos valores: :values.',
    'email'                => ':attribute no es un correo electrónico válido.',
    'ends_with'            => ':attribute tiene que terminar por alguno de estos valores: :values.',
    'enum'                 => ':attribute no es una opción válida.',
    'exists'               => ':attribute no existe.',
    'extensions'           => ':attribute tiene que tener una de estas extensiones: :values.',
    'file'                 => ':attribute tiene que ser un archivo.',
    'filled'               => ':attribute no puede estar vacío.',

    'gt' => [
        'array'   => ':attribute tiene que tener más de :value elementos.',
        'file'    => ':attribute tiene que ocupar más de :value kilobytes.',
        'numeric' => ':attribute tiene que ser mayor que :value.',
        'string'  => ':attribute tiene que tener más de :value caracteres.',
    ],

    'gte' => [
        'array'   => ':attribute tiene que tener :value elementos o más.',
        'file'    => ':attribute tiene que ocupar :value kilobytes o más.',
        'numeric' => ':attribute tiene que ser mayor o igual que :value.',
        'string'  => ':attribute tiene que tener :value caracteres o más.',
    ],

    'hex_color'            => ':attribute no es un color hexadecimal válido.',
    'image'                => ':attribute tiene que ser una imagen.',
    'in'                   => ':attribute no es una opción válida.',
    'in_array'             => ':attribute no está entre los valores de :other.',
    'in_array_keys'        => ':attribute tiene que contener al menos una de estas claves: :values.',
    'integer'              => ':attribute tiene que ser un número entero.',
    'ip'                   => ':attribute no es una dirección IP válida.',
    'ipv4'                 => ':attribute no es una dirección IPv4 válida.',
    'ipv6'                 => ':attribute no es una dirección IPv6 válida.',
    'json'                 => ':attribute tiene que ser una cadena JSON válida.',
    'list'                 => ':attribute tiene que ser una lista.',

    'lt' => [
        'array'   => ':attribute tiene que tener menos de :value elementos.',
        'file'    => ':attribute tiene que ocupar menos de :value kilobytes.',
        'numeric' => ':attribute tiene que ser menor que :value.',
        'string'  => ':attribute tiene que tener menos de :value caracteres.',
    ],

    'lte' => [
        'array'   => ':attribute no puede tener más de :value elementos.',
        'file'    => ':attribute tiene que ocupar :value kilobytes o menos.',
        'numeric' => ':attribute tiene que ser menor o igual que :value.',
        'string'  => ':attribute tiene que tener :value caracteres o menos.',
    ],

    'mac_address'          => ':attribute no es una dirección MAC válida.',

    'max' => [
        'array'   => ':attribute no puede tener más de :max elementos.',
        'file'    => ':attribute no puede ocupar más de :max kilobytes.',
        'numeric' => ':attribute no puede ser mayor que :max.',
        'string'  => ':attribute no puede tener más de :max caracteres.',
    ],

    'max_digits'           => ':attribute no puede tener más de :max dígitos.',
    'mimes'                => ':attribute tiene que ser un archivo de tipo: :values.',
    'mimetypes'            => ':attribute tiene que ser un archivo de tipo: :values.',

    'min' => [
        'array'   => ':attribute tiene que tener al menos :min elementos.',
        'file'    => ':attribute tiene que ocupar al menos :min kilobytes.',
        'numeric' => ':attribute tiene que ser al menos :min.',
        'string'  => ':attribute tiene que tener al menos :min caracteres.',
    ],

    'min_digits'           => ':attribute tiene que tener al menos :min dígitos.',
    'missing'              => ':attribute no puede estar presente.',
    'missing_if'           => ':attribute no puede estar presente cuando :other sea :value.',
    'missing_unless'       => ':attribute no puede estar presente salvo que :other sea :value.',
    'missing_with'         => ':attribute no puede estar presente si hay :values.',
    'missing_with_all'     => ':attribute no puede estar presente si están todos estos: :values.',
    'multiple_of'          => ':attribute tiene que ser múltiplo de :value.',
    'not_in'               => ':attribute no es una opción válida.',
    'not_regex'            => ':attribute tiene un formato no válido.',
    'numeric'              => ':attribute tiene que ser un número.',

    'password' => [
        'letters'       => 'La contraseña tiene que contener al menos una letra.',
        'mixed'         => 'La contraseña tiene que contener al menos una mayúscula y una minúscula.',
        'numbers'       => 'La contraseña tiene que contener al menos un número.',
        'symbols'       => 'La contraseña tiene que contener al menos un símbolo.',
        'uncompromised' => 'Esta contraseña ha aparecido en una filtración de datos. Elige otra.',
    ],

    'present'              => ':attribute tiene que estar presente.',
    'present_if'           => ':attribute tiene que estar presente cuando :other sea :value.',
    'present_unless'       => ':attribute tiene que estar presente salvo que :other sea :value.',
    'present_with'         => ':attribute tiene que estar presente si hay :values.',
    'present_with_all'     => ':attribute tiene que estar presente si están todos estos: :values.',
    'prohibited'           => ':attribute no está permitido.',
    'prohibited_if'        => ':attribute no está permitido cuando :other sea :value.',
    'prohibited_if_accepted' => ':attribute no está permitido cuando se acepta :other.',
    'prohibited_if_declined' => ':attribute no está permitido cuando se rechaza :other.',
    'prohibited_unless'    => ':attribute no está permitido salvo que :other esté entre :values.',
    'prohibits'            => ':attribute impide que :other esté presente.',
    'regex'                => ':attribute tiene un formato no válido.',
    'required'             => 'Falta :attribute.',
    'required_array_keys'  => ':attribute tiene que contener estas claves: :values.',
    'required_if'          => 'Falta :attribute cuando :other es :value.',
    'required_if_accepted' => 'Falta :attribute cuando se acepta :other.',
    'required_if_declined' => 'Falta :attribute cuando se rechaza :other.',
    'required_unless'      => 'Falta :attribute salvo que :other esté entre :values.',
    'required_with'        => 'Falta :attribute cuando hay :values.',
    'required_with_all'    => 'Falta :attribute cuando están todos estos: :values.',
    'required_without'     => 'Falta :attribute cuando no hay :values.',
    'required_without_all' => 'Falta :attribute cuando no hay ninguno de estos: :values.',
    'same'                 => ':attribute y :other tienen que coincidir.',

    'size' => [
        'array'   => ':attribute tiene que tener :size elementos.',
        'file'    => ':attribute tiene que ocupar :size kilobytes.',
        'numeric' => ':attribute tiene que ser :size.',
        'string'  => ':attribute tiene que tener :size caracteres.',
    ],

    'starts_with'          => ':attribute tiene que empezar por alguno de estos valores: :values.',
    'string'               => ':attribute tiene que ser texto.',
    'timezone'             => ':attribute tiene que ser una zona horaria válida.',
    'unique'               => ':attribute ya está en uso.',
    'uploaded'             => 'No se ha podido subir :attribute.',
    'uppercase'            => ':attribute tiene que ir en mayúsculas.',
    'url'                  => ':attribute no es una dirección web válida.',
    'ulid'                 => ':attribute no es un ULID válido.',
    'uuid'                 => ':attribute no es un UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Mensajes a medida
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'password' => [
            'min' => 'La contraseña tiene que tener al menos :min caracteres.',
        ],
        'code' => [
            'size' => 'El código son seis cifras.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nombres de los campos
    |--------------------------------------------------------------------------
    |
    | Lo que sustituye a :attribute. Sin esto saldría el nombre técnico del campo
    | («calories_per_100g») en un mensaje que va a leer el usuario final.
    |
    */

    'attributes' => [
        'name'              => 'el nombre',
        'email'             => 'el correo',
        'password'          => 'la contraseña',
        'current_password'  => 'la contraseña actual',
        'code'              => 'el código',
        'date'              => 'la fecha',
        'notes'             => 'las notas',
        'description'       => 'la descripción',
        'category'          => 'la categoría',
        'brand'             => 'la marca',
        'unit'              => 'la unidad',
        'image'             => 'la imagen',
        'mode'              => 'el tipo de entrenamiento',
        'duration_minutes'  => 'la duración',
        'calories_burned'   => 'las calorías quemadas',
        'exercises'         => 'los ejercicios',
        'exercises.*.name'  => 'el nombre del ejercicio',
        'exercises.*.sets'  => 'las series del ejercicio',
        'weight'            => 'el peso',
        'weight_kg'         => 'el peso',
        'steps'             => 'los pasos',
        'servings'          => 'las raciones',
        'weekly_goal'       => 'el objetivo semanal',
        'main_goal'         => 'el objetivo principal',
        'goal_type'         => 'el tipo de objetivo',
        'supplement_key'    => 'el suplemento',
        'calories_per_100g' => 'las calorías por 100 g',
        'protein_per_100g'  => 'las proteínas por 100 g',
        'carbs_per_100g'    => 'los hidratos por 100 g',
        'fat_per_100g'      => 'las grasas por 100 g',
        'fiber_per_100g'    => 'la fibra por 100 g',
        'sugar_per_100g'    => 'el azúcar por 100 g',
        'target_protein'    => 'el objetivo de proteínas',
        'target_carbs'      => 'el objetivo de hidratos',
        'target_fat'        => 'el objetivo de grasas',
        'target_fiber'      => 'el objetivo de fibra',
    ],

];
