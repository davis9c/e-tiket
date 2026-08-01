<?php

if (! function_exists('build_form_data')) {
    /**
     * Build a normalized form configuration array for controller-to-view form data.
     *
     * @param string $url
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    function build_form_data(string $url, array $fields = []): array
    {
        $form = ['url' => $url];

        foreach ($fields as $name => $field) {
            if (is_array($field)) {
                $form[$name] = array_merge([
                    'variable' => $name,
                    'value'    => null,
                ], $field);
                continue;
            }

            $form[$name] = [
                'variable' => $name,
                'value'    => $field,
            ];
        }

        return $form;
    }
}

if (! function_exists('form_field')) {
    /**
     * Build a single named form field item.
     *
     * @param string $name
     * @param mixed $value
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    function form_field(string $name, $value = null, array $options = []): array
    {
        return array_merge([
            'variable' => $name,
            'value'    => $value,
        ], $options);
    }
}

if (! function_exists('form_field_name')) {
    function form_field_name(array $form, string $field): string
    {
        return $form[$field]['variable'] ?? $field;
    }
}

if (! function_exists('form_field_value')) {
    function form_field_value(array $form, string $field, $default = null)
    {
        return $form[$field]['value'] ?? $default;
    }
}

if (! function_exists('form_field_option')) {
    function form_field_option(array $form, string $field): array
    {
        return $form[$field]['option'] ?? [];
    }
}
