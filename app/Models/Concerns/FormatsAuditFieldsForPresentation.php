<?php

namespace App\Models\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Audit;

/**
 * tapp/filament-auditing's own `mapping` config (resolving a foreign key
 * like customer_id to a related record's name) never actually reaches the
 * rendered table -- AuditValuesColumn's Blade view reads $getState()
 * directly and ignores whatever formatStateUsing()/mapRelatedColumns()
 * computed, so old_values/new_values always show raw UUIDs regardless of
 * that config. HasFormattedData::formatData() checks for this method on
 * the owner record FIRST, before falling through to the broken config
 * path, and its return value *is* honored -- so this is the real
 * integration point, reusing the same `filament-auditing.mapping` config
 * as the single source of truth for which fields resolve to which model.
 */
trait FormatsAuditFieldsForPresentation
{
    public function formatAuditFieldsForPresentation(string $field, Audit $record): HtmlString
    {
        $values = Arr::wrap($record->{$field});

        $html = '<ul>';

        foreach ($values as $key => $value) {
            [$label, $display] = $this->resolveAuditFieldForDisplay($key, $value);

            $html .= '<li>';
            $html .= '<span class="inline-block rounded-md whitespace-normal text-gray-700 dark:text-gray-200 bg-gray-500/10">';
            $html .= e(Str::title(str_replace('_', ' ', $label))).':';
            $html .= '</span> ';
            $html .= '<span class="font-semibold">';
            $html .= is_bool($value) ? ($value ? 'true' : 'false') : e((string) $display);
            $html .= '</span></li>';
        }

        $html .= '</ul>';

        return new HtmlString($html);
    }

    /**
     * Resolves a single audited field to a [label, displayValue] pair,
     * using the same `filament-auditing.mapping` config as the single
     * source of truth for which fields point at which related model.
     * Exposed separately from formatAuditFieldsForPresentation() so
     * callers that build their own layout (e.g. an old-vs-new diff view)
     * can resolve one field at a time instead of getting back a whole
     * pre-rendered list.
     *
     * @return array{0: string, 1: mixed}
     */
    public function resolveAuditFieldForDisplay(string $key, mixed $value): array
    {
        $mapping = config('filament-auditing.mapping', []);

        if (isset($mapping[$key]) && filled($value)) {
            return [
                $mapping[$key]['label'],
                $mapping[$key]['model']::find($value)?->{$mapping[$key]['field']} ?? $value,
            ];
        }

        return [$key, $value];
    }
}
