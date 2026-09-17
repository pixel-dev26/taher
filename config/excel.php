<?php

// Only the keys that differ from the package defaults (which are merged in
// underneath). See App\Exports\SafeStringValueBinder for why.
return [
    'value_binder' => [
        'default' => App\Exports\SafeStringValueBinder::class,
    ],
];
