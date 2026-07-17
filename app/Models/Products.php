<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property int $price
 * @property string $reference
 * @property string|null $image
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Products whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[TypeScript]
class Products extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'price',
        'reference',
        'image',
    ];
}
