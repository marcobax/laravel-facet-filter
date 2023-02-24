<?php

namespace Mgussekloo\FacetFilter\Facades;

use Illuminate\Support\Facades\Facade;

class FacetFilter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'facetfilter';
    }
}
