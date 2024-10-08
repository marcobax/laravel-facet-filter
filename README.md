# Laravel Facet Filter

This package provides simple facet filtering (sometimes called Faceted Search or Faceted Navigation) in Laravel projects. It helps narrow down query results based on the attributes of your models.

- Free, no dependencies
- No complex queries to write
- Easy to extend

![Demo](https://raw.githubusercontent.com/mgussekloo/laravel-facet-filter/master/demo.gif)

### Contributing

Feel free to contribute to this package, either by creating a pull request or reporting an issue.

### Installation

This package can be installed through [Composer](https://packagist.org/packages/mgussekloo/laravel-facet-filter).

``` bash
composer require mgussekloo/laravel-facet-filter
```

## Prepare your project

### Publish and run the migrations

``` bash
php artisan vendor:publish --tag="facet-filter-migrations"
php artisan migrate
```

### Update your models

Add a Facettable trait and a facetDefinitions() method to models that should support facet filtering.

``` php
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Mgussekloo\FacetFilter\Traits\Facettable;

class Product extends Model
{
	use HasFactory;
	use Facettable;

	public static function facetDefinitions()
	{
		// Return an array of definitions
		return [
			[
				'title' => 'Main color', // The title will be used for the parameter.
				'fieldname' => 'color' // Model property from which to get the values.
			],
			[
				'title' => 'Sizes',
				'fieldname' => 'sizes.name' // Use dot notation to get the value from related models.
			]
		];
	}
}

```

### Build the index

Before you can start filtering you will have to build an index. There's an Indexer included.

``` php
use Mgussekloo\FacetFilter\Indexer;

$products = Product::with(['sizes'])->get(); // get some products

$indexer = new Indexer();
$indexer->resetIndex(); // clears the entire index
or
$indexer->resetRows($products); // clears the index for the provided models
$indexer->buildIndex($products); // process the models
```

## Get results

### Apply the facet filter to a query

``` php
$filter = request()->all(); // use the request parameters
$filter = ['main-color' => ['green']]; // (or provide your own array)

$products = Product::facetsMatchFilter($filter)->get();
```

## Build the frontend

``` php
$facets = Product::getFacets();

/* You can filter and sort like any regular Laravel collection. */
$singleFacet = $facets->firstWhere('fieldname', 'color');

/* Find out stuff about the facet. */
$paramName = $singleFacet->getParamName(); // "main-color"
$options = $singleFacet->getOptions();

/*
Options look like this:
(object)[
	'value' => 'Red',
	'selected' => false,
	'total' => 3,
	'slug' => 'color_red',
	'http_query' => 'main-color%5B1%5D=red&sizes%5B0%5D=small'
]
*/
```

### Basic frontend example

Here's a simple [demo project](https://github.com/mgussekloo/Facet-Demo) that demonstrates a basic frontend.

``` html
<div class="flex">
	<div class="w-1/4 flex-0">
		@foreach ($facets as $facet)
			<p>
				<h3>{{ $facet->title }}</h3>

				@foreach ($facet->getOptions() as $option)
					<a href="?{{ $option->http_query }}" class="{{ $option->selected ? 'underline' : '' }}">{{ $option->value }} ({{ $option->total }}) </a><br />
				@endforeach
			</p><br />
		@endforeach
	</div>
	<div class="w-3/4">
		@foreach ($products as $product)
			<p>
				<h1>{{ $product->name }} ({{ $product->sizes->pluck('name')->join(', ') }})</h1>
				{{ $product->color }}<br /><br />
			</p>
		@endforeach
	</div>
</div>
```

### Livewire example

This is how it could look like with Livewire.

``` html
<h2>Colors</h2>
@foreach ($facet->getOptions() as $option)
	<div class="facet-checkbox-pill">
		<input
			wire:model="filter.{{ $facet->getParamName() }}"
			type="checkbox"
			id="{{ $option->slug }}"
			value="{{ $option->value }}"
		/>
		<label for="{{ $option->slug }}" class="{{ $option->selected ? 'selected' : '' }}">
			{{ $option->value }} ({{ $option->total }})
		</label>
	</div>
@endforeach
```

## Customization

### Advanced indexing

Extend the [Indexer](src/Indexer.php) to customize behavior, e.g. to save a "range bracket" value instead of a "individual price" value to the index.

``` php
class CustomIndexer extends Mgussekloo\FacetFilter\Indexer
{
	public function buildRow($facet, $model, $value) {
		$row = parent::buildRow($facet, $model, $value);

		if ($facet->getSlug() == 'App\Models\Product.price') {
			if ($row['value'] > 0 && $row['value'] < 100) {
				$row['value'] = '0-100';
			}
		}

		return $row;
	}
}
```

Process models in chunks for very large datasets.

``` php
$perPage = 1000; $currentPage = Cache::get('facetIndexingPage', 1);

$products = Product::with(['sizes'])->paginate($perPage, ['*'], 'page', $currentPage);
$indexer = new Indexer($products);

if ($currentPage == 1) {
	$indexer->resetIndex();
}

$indexer->buildIndex();

if ($products->hasMorePages()) {}
	// next iteration, increase currentPage with one
}
```

### Custom facets

Provide custom attributes and an optional custom [Facet class](src/Models/Facet.php) in the facet definitions.

``` php
public static function facetDefinitions()
{
	return [
		[
			'title' => 'Main color',
			'description' => 'The main color.',
			'fieldname' => 'color',
			'facet_class' => CustomFacet::class
		]
	];
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

