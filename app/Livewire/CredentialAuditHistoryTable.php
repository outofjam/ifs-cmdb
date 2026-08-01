<?php

namespace App\Livewire;

use App\Models\Audit;
use App\Models\Credential;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * A real, searchable/sortable Filament table for one credential's audit
 * history -- embedded directly rather than as a nested RelationManager,
 * since Credential has no resource page of its own to nest a *second*
 * relation manager under (Filament doesn't support nesting a relation
 * manager inside another relation manager's row action). Mounted via
 * CredentialsRelationManager::viewAuditHistoryAction()'s slide-over.
 * Gives the same search/sort/pagination that AuditsRelationManager
 * already provides Customer/Environment, instead of the hand-rolled,
 * unsearchable card list this replaces.
 */
class CredentialAuditHistoryTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public Credential $credential;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Audit::query()
                ->where('auditable_type', Credential::class)
                ->where('auditable_id', $this->credential->id))
            ->columns([
                TextColumn::make('event')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('Unknown user')
                    ->searchable(),
                TextColumn::make('changes')
                    ->label('Changes')
                    ->state(fn (Audit $record): HtmlString => $this->formatChanges($record))
                    ->html()
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            // created_at alone isn't a reliable tiebreaker -- audits
            // created in a tight loop (bulk updates, or a fast test) can
            // share the same timestamp. defaultKeySort() appends Audit's
            // own primary key (an ordered UUID via HasUuids) as a
            // secondary sort, so ties still resolve to true insertion
            // order. This exact class of bug already bit an earlier,
            // hand-rolled version of this feature -- don't drop it.
            ->defaultKeySort()
            ->emptyStateHeading('No audit history yet')
            ->emptyStateDescription('Changes to this credential will appear here.')
            ->emptyStateIcon(Heroicon::OutlinedClock);
    }

    /**
     * Foreign keys (owner_id, etc.) resolve to the related record's name
     * via the same `filament-auditing.mapping` config
     * FormatsAuditFieldsForPresentation already uses elsewhere.
     */
    protected function formatChanges(Audit $audit): HtmlString
    {
        if ($audit->event !== 'updated' || blank($audit->new_values)) {
            return new HtmlString('<span class="text-gray-400 dark:text-gray-500">No field changes</span>');
        }

        $rows = collect($audit->new_values)
            ->map(function (mixed $newValue, string $field) use ($audit): string {
                [$label, $displayNew] = $this->credential->resolveAuditFieldForDisplay($field, $newValue);
                $oldValue = $audit->old_values[$field] ?? null;
                $displayOld = filled($oldValue)
                    ? $this->credential->resolveAuditFieldForDisplay($field, $oldValue)[1]
                    : 'Not set';

                return sprintf(
                    '<div><span class="font-medium">%s:</span> <span class="text-gray-500 line-through dark:text-gray-400">%s</span> &rarr; %s</div>',
                    e(Str::headline($label)),
                    e((string) $displayOld),
                    e((string) $displayNew),
                );
            })
            ->implode('');

        return new HtmlString($rows);
    }

    public function render(): View
    {
        return view('livewire.credential-audit-history-table');
    }
}
