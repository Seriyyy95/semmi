<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class GetUrlCalendarRequest
 * @package App\Http\Requests
 */
class GetUrlCalendarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() : array
    {
        return [
            'url' => ['required', 'url'],
            'ga_site_id' => ['required', 'integer', 'exists:google_analytics_sites,id'],
            'field' => ['required', 'in:pageviews,adsenseRevenue,organicSearches'],
            'agg_function' => ['required', 'in:sum,avg']
        ];
    }
}
