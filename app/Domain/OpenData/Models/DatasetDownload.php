<?php

namespace App\Domain\OpenData\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Downloads of one distribution on one day (ADR-0025): a number only, with no address, cookie or browser detail.
 *
 * @property int $id
 * @property int $dataset_distribution_id
 * @property CarbonImmutable $day
 * @property int $count
 */
class DatasetDownload extends Model
{
    public $timestamps = false;

    protected $fillable = ['dataset_distribution_id', 'day', 'count'];

    protected function casts(): array
    {
        return ['day' => 'immutable_date', 'count' => 'integer'];
    }
}
