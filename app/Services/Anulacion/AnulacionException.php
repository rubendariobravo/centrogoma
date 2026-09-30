<?php

namespace App\Services\Anulacion;

/**
 * Regla de negocio que impide anular un comprobante. El mensaje se muestra tal cual al operador.
 */
class AnulacionException extends \RuntimeException
{
}
