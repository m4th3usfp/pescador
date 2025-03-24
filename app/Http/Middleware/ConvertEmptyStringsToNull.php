<?php

namespace Illuminate\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull as Middleware;

class ConvertEmptyStringsToNull extends Middleware
{
    // Esse middleware converte strings vazias para NULL automaticamente
}
