# IncotermController explicado línea por línea

Este documento explica el controlador completo `app/Http/Controllers/Api/Admin/IncotermController.php` línea por línea, función por función.

## Archivo completo

### Líneas 1 a 11

1. `<?php`
   - Indica que el archivo contiene código PHP.

2. Línea en blanco
   - Separa visualmente bloques de código.

3. `namespace App\Http\Controllers\Api\Admin;`
   - Define el espacio de nombres del controlador.
   - Sirve para organizar la clase dentro de la carpeta `Api/Admin`.

4. Línea en blanco
   - Separación visual.

5. `use App\Http\Controllers\Concerns\AuthorizesApiRequests;`
   - Importa el trait con la lógica de autorización y usuario actual.

6. `use App\Http\Controllers\Controller;`
   - Importa la clase base de todos los controladores Laravel.

7. `use App\Models\TipusIncoterm;`
   - Importa el modelo que representa la tabla `tipus_incoterms`.

8. `use Illuminate\Http\JsonResponse;`
   - Importa el tipo de respuesta JSON que devolverán los métodos.

9. `use Illuminate\Http\Request;`
   - Importa la clase que representa la petición HTTP entrante.

10. `use Illuminate\Support\Facades\DB;`
    - Importa la fachada de base de datos para usar transacciones.

11. `use Illuminate\Validation\Rule;`
    - Importa reglas de validación avanzadas, como `unique` con `ignore`.

### Líneas 13 a 15

13. `class IncotermController extends Controller`
    - Declara la clase del controlador.
    - Hereda del controlador base de Laravel.

14. `{`
    - Abre el bloque de la clase.

15. `use AuthorizesApiRequests;`
    - Añade el trait de autorización dentro del controlador.
    - Permite usar métodos como `requireRoles()` y `currentUser()`.

### Método index, líneas 17 a 31

17. `public function index(Request $request): JsonResponse`
    - Declara el método que lista todos los incoterms.
    - Recibe la petición HTTP.
    - Devuelve JSON.

18. `{`
    - Abre el bloque del método.

19. `$this->requireRoles($request, ['admin']);`
    - Verifica que el usuario tenga rol `admin`.
    - Si no lo tiene, Laravel devuelve 403.

20. Línea en blanco
    - Separa la validación del resto de la lógica.

21. `$incoterms = TipusIncoterm::query()`
    - Empieza una consulta sobre el modelo `TipusIncoterm`.

22. `    ->with(['trackingSteps'])`
    - Carga la relación `trackingSteps` para evitar consultas extra.

23. `    ->orderBy('codi')`
    - Ordena los resultados por el código del incoterm.

24. `    ->get()`
    - Ejecuta la consulta y obtiene la colección completa.

25. `    ->map(fn (TipusIncoterm $incoterm) => $this->formatIncoterm($incoterm))`
    - Recorre cada incoterm y lo transforma al formato de salida de la API.

26. `    ->values();`
    - Reindexa la colección desde 0.

27. Línea en blanco
    - Separación visual.

28. `return response()->json([`
    - Empieza la respuesta JSON.

29. `    'incoterms' => $incoterms,`
    - Envía la lista de incoterms en la clave `incoterms`.

30. `]);`
    - Cierra la respuesta JSON.

31. `}`
    - Cierra el método `index`.

### Método show, líneas 33 a 40

33. `public function show(Request $request, TipusIncoterm $incoterm): JsonResponse`
    - Declara el método que muestra un incoterm concreto.
    - Laravel inyecta el modelo `TipusIncoterm` por route model binding.

34. `{`
    - Abre el bloque del método.

35. `$this->requireRoles($request, ['admin']);`
    - Verifica que solo un admin pueda ver este recurso.

36. Línea en blanco
    - Separación visual.

37. `return response()->json([`
    - Prepara la respuesta JSON.

38. `    'incoterm' => $this->formatIncoterm($incoterm->load('trackingSteps')),`
    - Carga la relación `trackingSteps` y formatea el incoterm para devolverlo.

39. `]);`
    - Cierra la respuesta JSON.

40. `}`
    - Cierra el método `show`.

### Método store, líneas 42 a 63

42. `public function store(Request $request): JsonResponse`
    - Declara el método que crea un nuevo incoterm.

43. `{`
    - Abre el bloque del método.

44. `$this->requireRoles($request, ['admin']);`
    - Comprueba que solo un admin pueda crear incoterms.

45. Línea en blanco
    - Separación visual.

46. `$validated = $request->validate($this->rules());`
    - Valida los datos de entrada con las reglas definidas en `rules()`.
    - Si hay errores, Laravel responde automáticamente con validación fallida.

47. Línea en blanco
    - Separación visual.

48. `$incoterm = DB::transaction(function () use ($validated): TipusIncoterm {`
    - Abre una transacción de base de datos.
    - Todo lo que ocurra dentro se confirma o se revierte junto.
    - `use ($validated)` pasa los datos validados al bloque.

49. `    $incoterm = TipusIncoterm::create([`
    - Crea un nuevo registro en `tipus_incoterms`.

50. `        'codi' => $validated['codi'],`
    - Asigna el código validado al nuevo registro.

51. `        'nom' => $validated['nom'],`
    - Asigna el nombre validado al nuevo registro.

52. `    ]);`
    - Cierra el array y ejecuta la inserción.

53. Línea en blanco
    - Separación visual.

54. `    $incoterm->trackingSteps()->sync($validated['tracking_step_ids']);`
    - Sincroniza los tracking steps asociados al incoterm.
    - Guarda las relaciones en la tabla pivote `incoterms`.
    - `sync()` deja exactamente los IDs enviados.

55. Línea en blanco
    - Separación visual.

56. `    return $incoterm->load('trackingSteps');`
    - Recarga el modelo con la relación `trackingSteps` ya cargada.

57. `});`
    - Cierra la transacción y devuelve el incoterm creado.

58. Línea en blanco
    - Separación visual.

59. `return response()->json([`
    - Empieza la respuesta JSON final.

60. `    'message' => 'Incoterm created successfully.',`
    - Devuelve un mensaje de confirmación.

61. `    'incoterm' => $this->formatIncoterm($incoterm),`
    - Devuelve el incoterm en el formato esperado por el frontend.

62. `], 201);`
    - Devuelve estado HTTP 201, que significa creado correctamente.

63. `}`
    - Cierra el método `store`.

### Método update, líneas 65 a 85

65. `public function update(Request $request, TipusIncoterm $incoterm): JsonResponse`
    - Declara el método que actualiza un incoterm existente.
    - El incoterm llega por route model binding.

66. `{`
    - Abre el bloque del método.

67. `$this->requireRoles($request, ['admin']);`
    - Verifica que solo admin pueda editar.

68. Línea en blanco
    - Separación visual.

69. `$validated = $request->validate($this->rules($incoterm));`
    - Valida la petición usando las reglas del método `rules()`.
    - Pasa el incoterm actual para que el `unique` de `codi` ignore su propio ID.

70. Línea en blanco
    - Separación visual.

71. `$incoterm = DB::transaction(function () use ($incoterm, $validated): TipusIncoterm {`
    - Abre una transacción para actualizar todo de forma atómica.
    - `use ($incoterm, $validated)` mete el modelo actual y los datos validados dentro del bloque.

72. `    $incoterm->update([`
    - Empieza la actualización del modelo.

73. `        'codi' => $validated['codi'],`
    - Actualiza el código.

74. `        'nom' => $validated['nom'],`
    - Actualiza el nombre.

75. `    ]);`
    - Ejecuta el update en la tabla principal.

76. Línea en blanco
    - Separación visual.

77. `    $incoterm->trackingSteps()->sync($validated['tracking_step_ids']);`
    - Reemplaza las relaciones con tracking steps por las nuevas.
    - Si antes tenía otros pasos, quedan sincronizados con la lista enviada.

78. Línea en blanco
    - Separación visual.

79. `    return $incoterm->load('trackingSteps');`
    - Devuelve el incoterm actualizado con sus relaciones cargadas.

80. `});`
    - Cierra la transacción.

81. Línea en blanco
    - Separación visual.

82. `return response()->json([`
    - Empieza la respuesta JSON.

83. `    'message' => 'Incoterm updated successfully.',`
    - Mensaje confirmando que la actualización fue correcta.

84. `    'incoterm' => $this->formatIncoterm($incoterm),`
    - Devuelve el incoterm actualizado y formateado.

85. `]);`
    - Cierra la respuesta JSON.

86. `}`
    - Cierra el método `update`.

### Método destroy, líneas 88 a 96

88. `public function destroy(Request $request, TipusIncoterm $incoterm): JsonResponse`
    - Declara el método que elimina un incoterm.

89. `{`
    - Abre el bloque del método.

90. `$this->requireRoles($request, ['admin']);`
    - Verifica que solo admin pueda borrar.

91. Línea en blanco
    - Separación visual.

92. `$incoterm->delete();`
    - Elimina el registro de `tipus_incoterms`.
    - La base de datos elimina también las relaciones de la tabla pivote por cascada.

93. Línea en blanco
    - Separación visual.

94. `return response()->json([`
    - Prepara la respuesta JSON final.

95. `    'message' => 'Incoterm deleted successfully.',`
    - Mensaje de confirmación del borrado.

96. `]);`
    - Cierra la respuesta JSON.

97. `}`
    - Cierra el método `destroy`.

### Método rules, líneas 99 a 111

99. `private function rules(?TipusIncoterm $incoterm = null): array`
    - Declara el método privado que devuelve las reglas de validación.
    - El parámetro es opcional para poder reutilizarlo en `store` y `update`.

100. `{`
     - Abre el bloque del método.

101. `return [`
     - Devuelve un array con reglas de validación.

102. `    'codi' => [`
     - Empieza las reglas para el campo `codi`.

103. `        'required',`
     - Obliga a que el campo exista.

104. `        'string',`
     - Exige que sea una cadena de texto.

105. `        'max:50',`
     - Limita la longitud máxima a 50 caracteres.

106. `        Rule::unique('tipus_incoterms', 'codi')->ignore($incoterm?->id),`
     - Obliga a que el código sea único en la tabla `tipus_incoterms`.
     - Si se está editando, ignora el ID del incoterm actual.

107. `    ],`
     - Cierra las reglas de `codi`.

108. `    'nom' => ['required', 'string', 'max:255'],`
     - Reglas para el nombre.
     - Es obligatorio, texto y con máximo 255 caracteres.

109. `    'tracking_step_ids' => ['required', 'array', 'min:1'],`
     - Obliga a enviar un array de IDs.
     - Debe tener al menos un elemento.

110. `    'tracking_step_ids.*' => ['required', 'integer', 'distinct', 'exists:tracking_steps,id'],`
     - Valida cada elemento del array.
     - Debe existir, ser entero y no repetirse.

111. `];`
     - Cierra el array de reglas y devuelve la configuración completa.

112. `}`
     - Cierra el método `rules`.

### Método formatIncoterm, líneas 114 a 128

114. `private function formatIncoterm(TipusIncoterm $incoterm): array`
     - Declara un método privado para transformar el modelo en un array de salida.

115. `{`
     - Abre el bloque del método.

116. `return [`
     - Devuelve un array estructurado.

117. `    'id' => $incoterm->id,`
     - Incluye el ID del incoterm.

118. `    'codi' => $incoterm->codi,`
     - Incluye el código.

119. `    'nom' => $incoterm->nom,`
     - Incluye el nombre.

120. `    'tracking_steps' => $incoterm->trackingSteps`
     - Empieza a construir la lista de pasos relacionados.

121. `        ->map(fn ($trackingStep) => [`
     - Recorre cada tracking step y lo transforma.

122. `            'id' => $trackingStep->id,`
     - Devuelve el ID del paso.

123. `            'ordre' => $trackingStep->ordre,`
     - Devuelve el orden del paso.

124. `            'nom' => $trackingStep->nom,`
     - Devuelve el nombre del paso.

125. `        ])`
     - Cierra la transformación de cada paso.

126. `        ->values(),`
     - Reindexa la colección para que quede limpia.

127. `];`
     - Cierra el array final del incoterm.

128. `}`
     - Cierra el método `formatIncoterm`.

### Cierre de la clase

129. `}`
   - Cierra la clase `IncotermController`.

## Resumen funcional

Este controlador hace tres cosas principales:
- `store`: crea un incoterm nuevo.
- `update`: modifica un incoterm existente.
- `destroy`: elimina un incoterm.

Además:
- Solo deja operar a usuarios con rol `admin`.
- Valida toda la entrada antes de tocar la base de datos.
- Usa transacciones para evitar estados incompletos.
- Sincroniza la relación con `trackingSteps` a través de la tabla pivote `incoterms`.
- Devuelve respuestas JSON listas para consumir desde frontend.
