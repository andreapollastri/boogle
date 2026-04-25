<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GroupIssuePrefix implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        if (strlen($value) > 8) {
            $fail('The :attribute must not be greater than 8 characters.');

            return;
        }

        if (! preg_match('/^[A-Za-z0-9]+$/', $value)) {
            $fail('The :attribute may only contain letters and numbers.');

            return;
        }

        if (strtoupper($value) === 'OUT') {
            $fail('The prefix OUT is reserved for uptime outages.');
        }
    }
}
