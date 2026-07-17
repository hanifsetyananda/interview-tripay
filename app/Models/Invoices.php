<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @property int $id
 * @property int $product_id
 * @property string $tripay_reference
 * @property string $buyer_email
 * @property string $buyer_phone
 * @property string $raw_response
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices whereBuyerEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices whereBuyerPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices whereRawResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices whereTripayReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoices whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[TypeScript]
class Invoices extends Model
{
    //
}
