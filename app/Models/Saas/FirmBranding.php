<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FirmBranding
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $brand_name
 * @property string|null $brand_slogan
 * @property string|null $website
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $facebook
 * @property string|null $linkedin
 * @property string|null $instagram
 * @property string|null $youtube
 * @property string|null $color_scheme
 * @property string|null $logo
 * @property string|null $logo_dark
 * @property string|null $favicon
 * @property string|null $legal_entity_type
 * @property string|null $legal_reg_certificate
 * @property string|null $legal_certificate_number
 * @property string|null $tax_reg_certificate
 * @property string|null $tax_certificate_no
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 *
 * @package App\Models\Saas
 */
class FirmBranding extends Model
{
	use SoftDeletes;
	protected $table = 'firm_brandings';

	protected $casts = [
		'firm_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'brand_name',
		'brand_slogan',
		'website',
		'email',
		'phone',
		'facebook',
		'linkedin',
		'instagram',
		'youtube',
		'color_scheme',
		'logo',
		'logo_dark',
		'favicon',
		'legal_entity_type',
		'legal_reg_certificate',
		'legal_certificate_number',
		'tax_reg_certificate',
		'tax_certificate_no',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
