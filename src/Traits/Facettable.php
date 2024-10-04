<?php

namespace Mgussekloo\FacetFilter\Traits;

use Mgussekloo\FacetFilter\Facades\FacetFilter;

use Mgussekloo\FacetFilter\Builders\FacetQueryBuilder;

use Mgussekloo\FacetFilter\Collections\FacettableCollection;
use Illuminate\Support\Collection;


trait Facettable
{
    abstract public static function facetDefinitions();

	public function newCollection(array $models = [])
    {
        return new FacettableCollection($models);
    }

    /**
     * Make the facets
     */
    public static function makeFacets($subjectType=null,$definitions=null): Collection
    {
    	if (is_null($subjectType)) {
    		$subjectType = self::class;
    	}

    	if (is_null($definitions)) {
    		$definitions = self::facetDefinitions();
    	}

        if (is_array($definitions)) {
            $definitions = collect($definitions);
        }

        $facetClass = config('facet-filter.classes.facet');

        $definitions = $definitions->map(function ($definition) use ($subjectType, $facetClass) {
            return array_merge([
                'subject_type' => $subjectType,
                'facet_class' => $facetClass,
            ], $definition);
        })->filter();

        // Instantiate models
        $facets = [];
        foreach ($definitions as $definition) {
            $facets[] = new $definition['facet_class']($definition);
        }

        return collect($facets);
    }

    // get the facet models
    public static function getFacets($filter = null, $load = true): Collection
    {
        return FacetFilter::getFacets(self::class, $filter, $load);
    }

    public function facetrows()
    {
        $facetRowClass = config('facet-filter.classes.facetrow');
        return $this->hasMany($facetRowClass, 'subject_id');
    }

    public function newEloquentBuilder($query): FacetQueryBuilder
    {
        return new FacetQueryBuilder($query);
    }

    public static function getFilterFromArr($arr = [])
    {
        return FacetFilter::getFilterFromArr(self::class, $arr);
    }


	public static function filterCollection($models, $filter, $indexer=null)
    {
    	return FacetFilter::filterCollection($models, $filter, $indexer);
    }


}
