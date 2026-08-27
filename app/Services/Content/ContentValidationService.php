<?php

namespace App\Services\Content;

use App\Models\Collection;
use App\Models\ContentMeta;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Dynamic field validation and value sanitisation for the EAV content
 * model. The admin UI and the public API share identical validation
 * logic — this service eliminates the duplication that previously
 * lived in three separate controller methods (API create, API update,
 * Admin store, Admin update).
 */
class ContentValidationService
{
    /**
     * Build Laravel validation rules + custom messages from a collection's
     * field definitions.
     *
     * The $prefix parameter adapts the rule keys for the different request
     * shapes used by the admin UI ("data.{field}") and the public API
     * (straight field name).
     *
     * @param array $fields  CollectionField models (options / validations
     *                       already JSON-decoded).
     * @param string $prefix Dot-path prefix for rule keys, e.g. "data".
     *                       Pass "" for the public API.
     * @return array{0: array, 1: array}  [$rules, $messages]
     */
    public function buildFieldValidationRules(array $fields, string $prefix = ''): array
    {
        $rules    = [];
        $messages = [];
        $key      = function (string $name, string $suffix = '') use ($prefix) {
            $base = $prefix === '' ? $name : $prefix . '.' . $name;
            return $suffix === '' ? $base : $base . '.' . $suffix;
        };

        foreach ($fields as $field) {
            $validations = $field->validations;
            $options     = $field->options;

            $repeatable = isset($options->repeatable) && $options->repeatable;

            // ---- required ----
            if ($validations->required->status) {
                if ($repeatable) {
                    $rules[$key($field->name, '*.value')][] = 'required';
                    $messages[$key($field->name, '*.value') . '.required'] =
                        $validations->required->message ?: 'The ' . $field->name . ' field is required.';
                } else {
                    $rules[$key($field->name)][] = 'required';
                    $messages[$key($field->name) . '.required'] =
                        $validations->required->message ?: 'The ' . $field->name . ' field is required.';
                }
            }

            // ---- email ----
            if ($field->type === 'email') {
                if ($repeatable) {
                    $rules[$key($field->name, '*.value')][] = 'nullable';
                    $rules[$key($field->name, '*.value')][] = 'email';
                    $messages[$key($field->name, '*.value') . '.email'] =
                        'The ' . $field->name . ' must be a valid email address.';
                } else {
                    $rules[$key($field->name)][] = 'email';
                    $messages[$key($field->name) . '.email'] =
                        'The ' . $field->name . ' must be a valid email address.';
                }
            }

            // ---- number ----
            if ($field->type === 'number') {
                if ($repeatable) {
                    $rules[$key($field->name, '*.value')][] = 'nullable';
                    $rules[$key($field->name, '*.value')][] = 'numeric';
                    $messages[$key($field->name, '*.value') . '.numeric'] =
                        'The ' . $field->name . ' must be numeric.';
                } else {
                    $rules[$key($field->name)][] = 'numeric';
                    $messages[$key($field->name) . '.numeric'] =
                        'The ' . $field->name . ' must be numeric.';
                }
            }

            // ---- color ----
            if ($field->type === 'color') {
                if ($repeatable) {
                    $rules[$key($field->name, '*.value')][] = 'nullable';
                    $rules[$key($field->name, '*.value')][] = 'color';
                    $messages[$key($field->name, '*.value') . '.color'] =
                        'The ' . $field->name . ' must be a color string.';
                } else {
                    $rules[$key($field->name)][] = 'color';
                    $messages[$key($field->name) . '.color'] =
                        'The ' . $field->name . ' must be a color string.';
                }
            }

            // ---- charcount ----
            if ($validations->charcount->status) {
                $type = $validations->charcount->type;
                $suffix = $field->type !== 'number' ? ' characters.' : '';

                if ($type === 'Between') {
                    $param = $validations->charcount->min . ',' . $validations->charcount->max;
                    $msg   = 'The ' . $field->name . ' must be between ' . $validations->charcount->min . ' and ' . $validations->charcount->max . $suffix;
                    $rule  = 'between:' . $param;
                } elseif ($type === 'Min') {
                    $param = $validations->charcount->min;
                    $msg   = 'The ' . $field->name . ' must be at least ' . $validations->charcount->min . $suffix;
                    $rule  = 'min:' . $param;
                } else { // Max
                    $param = $validations->charcount->max;
                    $msg   = 'The ' . $field->name . ' may not be greater than ' . $validations->charcount->max . $suffix;
                    $rule  = 'max:' . $param;
                }

                if ($repeatable) {
                    $rules[$key($field->name, '*.value')][]  = $rule;
                    $messages[$key($field->name, '*.value') . '.' . explode(':', $rule)[0]] = $msg;
                } else {
                    $rules[$key($field->name)][] = $rule;
                    $messages[$key($field->name) . '.' . explode(':', $rule)[0]] = $msg;
                }
            }
        }

        return [$rules, $messages];
    }

    /**
     * Register the custom "color" validator extension.
     * Call once before Validator::make() — the extension is safe to
     * register multiple times (Laravel skips duplicates).
     */
    public static function registerCustomValidators(): void
    {
        Validator::extend('color', function ($attribute, $value) {
            $color_regex = "/(#(?:[0-9a-f]{2}){2,4}$|(#[0-9a-f]{3}$)|(rgb|hsl)a?\((-?\d*\.?\d*+%?[,\s]+){2,3}\s*[\d\.]+%?\)$|black$|silver$|gray$|whitesmoke$|maroon$|red$|purple$|fuchsia$|green$|lime$|olivedrab$|yellow$|navy$|blue$|teal$|aquamarine$|orange$|aliceblue$|antiquewhite$|aqua$|azure$|beige$|bisque$|blanchedalmond$|blueviolet$|brown$|burlywood$|cadetblue$|chartreuse$|chocolate$|coral$|cornflowerblue$|cornsilk$|crimson$|currentcolor$|darkblue$|darkcyan$|darkgoldenrod$|darkgray$|darkgreen$|darkgrey$|darkkhaki$|darkmagenta$|darkolivegreen$|darkorange$|darkorchid$|darkred$|darksalmon$|darkseagreen$|darkslateblue$|darkslategray$|darkslategrey$|darkturquoise$|darkviolet$|deeppink$|deepskyblue$|dimgray$|dimgrey$|dodgerblue$|firebrick$|floralwhite$|forestgreen$|gainsboro$|ghostwhite$|goldenrod$|gold$|greenyellow$|grey$|honeydew$|hotpink$|indianred$|indigo$|ivory$|khaki$|lavenderblush$|lavender$|lawngreen$|lemonchiffon$|lightblue$|lightcoral$|lightcyan$|lightgoldenrodyellow$|lightgray$|lightgreen$|lightgrey$|lightpink$|lightsalmon$|lightseagreen$|lightskyblue$|lightslategray$|lightslategrey$|lightsteelblue$|lightyellow$|limegreen$|linen$|mediumaquamarine$|mediumblue$|mediumorchid$|mediumpurple$|mediumseagreen$|mediumslateblue$|mediumspringgreen$|mediumturquoise$|mediumvioletred$|midnightblue$|mintcream$|mistyrose$|moccasin$|navajowhite$|oldlace$|olive$|orangered$|orchid$|palegoldenrod$|palegreen$|paleturquoise$|palevioletred$|papayawhip$|peachpuff$|peru$|pink$|plum$|powderblue$|rosybrown$|royalblue$|saddlebrown$|salmon$|sandybrown$|seagreen$|seashell$|sienna$|skyblue$|slateblue$|slategray$|slategrey$|snow$|springgreen$|steelblue$|tan$|thistle$|tomato$|transparent$|turquoise$|violet$|wheat$|white$|yellowgreen$|rebeccapurple$)/i";
            return preg_match($color_regex, $value);
        });
    }

    /**
     * Validate unique constraints on a collection's fields.
     *
     * Returns a response array on failure (keyed 'errors') or an empty
     * array on success. The caller sends the HTTP 422 response itself.
     *
     * @param array       $fields           Collection fields (validations decoded).
     * @param array       $input            The raw field-name → value payload.
     * @param int         $collectionId
     * @param int|null    $excludeContentId Exclude this content id (on update).
     * @return array  Empty when valid; otherwise ['errors' => [...]].
     */
    public function validateUniqueFields(
        array $fields,
        array $input,
        int $collectionId,
        ?int $excludeContentId = null
    ): array {
        $errors = [];

        foreach ($fields as $field) {
            $validations = $field->validations;
            if (! $validations->unique->status) {
                continue;
            }
            if (! isset($input[$field->name])) {
                continue;
            }

            $query = ContentMeta::where('collection_id', $collectionId)
                ->where('field_name', $field->name)
                ->where('value', $input[$field->name]);

            if ($excludeContentId !== null) {
                $query->where('content_id', '!=', $excludeContentId);
            }

            if ($query->count() !== 0) {
                $errors[$field->name] = [
                    $validations->unique->message ?: 'The ' . $field->name . ' has already been taken.',
                ];
            }
        }

        return $errors ? ['errors' => $errors] : [];
    }

    /**
     * Transform a raw field value for storage according to its type.
     *
     * @param string $fieldType    e.g. "password", "enumeration"…
     * @param mixed  $rawValue     The incoming value.
     * @param object $fieldOptions Decoded JSON options object (nullable).
     * @param bool   $isUpdate     Whether this is an update (password
     *                             fields keep old hash on empty input).
     * @param mixed  $existingPasswordValue The current hashed value (password only, update only).
     * @return mixed  Sanitised value ready for ContentMeta::value.
     */
    public function sanitizeFieldValue(
        string $fieldType,
        mixed $rawValue,
        ?object $fieldOptions = null,
        bool $isUpdate = false,
        mixed $existingPasswordValue = null
    ): mixed {
        // Password: hash on create; on update keep old hash if empty.
        if ($fieldType === 'password') {
            if ($isUpdate) {
                if ($existingPasswordValue !== null) {
                    return empty($rawValue) ? $existingPasswordValue : Hash::make($rawValue);
                }
                return Hash::make($rawValue);
            }
            return Hash::make($rawValue);
        }

        // Enumeration (multiple): join array → comma-separated string.
        if ($fieldType === 'enumeration') {
            if (isset($fieldOptions->multiple) && $fieldOptions->multiple && is_array($rawValue)) {
                return implode(',', $rawValue);
            }
            return $rawValue;
        }

        // Media / Relation: join array → comma-separated string.
        if ($fieldType === 'media' || $fieldType === 'relation') {
            if (is_array($rawValue)) {
                return implode(',', $rawValue);
            }
            // Already a comma-separated string (API with explicit explode/implode).
            return $rawValue;
        }

        // JSON: encode to string.
        if ($fieldType === 'json') {
            return json_encode($rawValue);
        }

        return $rawValue;
    }
}