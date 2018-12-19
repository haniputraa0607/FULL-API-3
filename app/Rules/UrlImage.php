<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class UrlImage implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $isValid = false;

        $ch = curl_init($value);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($code == 200){
            $allowedContentTypes = [
                'image/jpeg', 'image/jpg', 'image/png'
            ];

            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

            if(in_array($contentType,$allowedContentTypes)){
                $isValid = true;
            }
        }

        curl_close($ch);

        return $isValid;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is not valid url.';
    }
}
