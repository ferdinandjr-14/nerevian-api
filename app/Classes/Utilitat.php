<?php

namespace App\Classes;

use Throwable;

class Utilitat
{
    public static function errorMessage($error, ?string $fallbackMessage = null): string
    {
        $missatge = 'Error desconegut';

        if (is_object($error) && isset($error->errorInfo[1]) && !empty($error->errorInfo[1])) {
            $code = (int) $error->errorInfo[1];
            $detail = $error->errorInfo[2] ?? (method_exists($error, 'getMessage') ? $error->getMessage() : 'Error desconegut');

            switch ($code) {
                case 2601:
                case 1062:
                    return '1062 - Registre duplicat';
                case 2628:
                case 8152:
                    return '1495 - El valor introduit es massa llarg per al camp.';
                case 547:
                case 1451:
                    return $code . ' - Registre amb elements relacionats';
                default:
                    return $code . ' - ' . $detail;
            }
        }

        $code = null;

        if (is_object($error) && method_exists($error, 'getCode')) {
            $code = (int) $error->getCode();
            if ($fallbackMessage === null && method_exists($error, 'getMessage')) {
                $fallbackMessage = $error->getMessage();
            }
        }

        if (!is_object($error) && is_numeric($error)) {
            $code = (int) $error;
        }

        if ($code !== null) {
            switch ($code) {
                case 1044:
                    $missatge = '1044 - Usuari i/o password incorrectes';
                    break;
                case 1049:
                    $missatge = '1049 - Base de dades desconeguda';
                    break;
                case 1492:
                    $missatge = '1492 - ID es requerido';
                    break;
                case 1493:
                    $missatge = '1493 - No se puede insertar un ID manual en una columna identity';
                    break;
                case 1494:
                    $missatge = '1494 - No se puede eliminar este incoterm porque hay solicitudes que lo utilizan.';
                    break;
                case 1495:
                    $missatge = '1495 - El valor introduit es massa llarg per al camp.';
                    break;
                case 1496:
                    $missatge = '1496 - Ya existe un paso con ese orden para este incoterm.';
                    break;
                case 1497:
                    $missatge = '1497 - El incoterm debe conservar al menos un paso.';
                    break;
                case 1498:
                    $missatge = '1498 - Paso de tracking no encontrado.';
                    break;
                case 1499:
                    $missatge = '1499 - Debes indicar un tipo de incoterm existente.';
                    break;
                case 1500:
                    $missatge = '1500 - El tipo de incoterm indicado no existe en BBDD.';
                    break;
                case 1501:
                    $missatge = '1501 - El tipo de incoterm no tiene pasos configurados en tipus_tracking.';
                    break;
                case 1502:
                    $missatge = '1502 - No hay pasos por defecto en tracking_steps.';
                    break;
                case 1503:
                    $missatge = '1503 - No se encontró el tipo de incoterm para actualizar pasos.';
                    break;
                case 1504:
                    $missatge = '1504 - Incoterm no encontrado.';
                    break;
                case 2002:
                    $missatge = '2002 - No es troba el servidor';
                    break;
                default:
                    $missatge = $code . ' - ' . ($fallbackMessage ?? 'Error desconegut');
                    break;
            }

            return $missatge;
        }

        if ($error instanceof Throwable && $fallbackMessage !== null) {
            return $fallbackMessage;
        }

        if (is_string($error)) {
            return $error;
        }

        return (string) $error;
    }
}
