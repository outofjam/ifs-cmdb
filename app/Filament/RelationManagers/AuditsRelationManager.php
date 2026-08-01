<?php

namespace App\Filament\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Tapp\FilamentAuditing\Filament\Actions\RestoreAuditAction;
use Tapp\FilamentAuditing\Filament\Resources\Audits\Schemas\AuditFilters;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager as BaseAuditsRelationManager;

/**
 * tapp/filament-auditing's own AuditsRelationManager renders old_values/
 * new_values with a custom AuditValuesColumn whose Blade view reads
 * $getState() directly rather than the column's formatted state -- so
 * formatStateUsing() (and by extension the `filament-auditing.mapping`
 * config, and App\Models\Concerns\FormatsAuditFieldsForPresentation) is
 * silently ignored and every foreign key renders as a raw UUID. Confirmed
 * empirically: replacing that closure's return value entirely had zero
 * effect on the rendered HTML.
 *
 * This subclass keeps everything from the parent except table(), which is
 * copied verbatim but swaps AuditValuesColumn for a plain TextColumn --
 * TextColumn's own Blade view *does* apply formatStateUsing() correctly,
 * so App\Models\Concerns\FormatsAuditFieldsForPresentation actually
 * reaches the page this way.
 */
class AuditsRelationManager extends BaseAuditsRelationManager
{
    public function table(Table $table): Table
    {
        $tableActions = [
            ViewAction::make(),
            RestoreAuditAction::make('restore'),
        ];

        if (config('filament-auditing.grouped_table_actions')) {
            $tableActions = ActionGroup::make($tableActions);
        }

        return $table
            ->recordTitle(fn (Model $record): string => 'Audit')
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['user', 'auditable'])
                    ->orderBy(config('filament-auditing.audits_sort.column'), config('filament-auditing.audits_sort.direction'));
            })
            ->content(fn (): ?View => config('filament-auditing.custom_audits_view') ? view('filament-auditing::tables.custom-audit-content', Arr::add(static::customViewParameters(), 'owner', $this->getOwnerRecord())) : null)
            ->emptyStateHeading(trans('filament-auditing::filament-auditing.table.empty_state_heading'))
            ->columns(Arr::flatten([
                TextColumn::make('user.name')
                    ->label(trans('filament-auditing::filament-auditing.column.user_name')),
                TextColumn::make('event')
                    ->label(trans('filament-auditing::filament-auditing.column.event')),
                TextColumn::make('created_at')
                    ->since()
                    ->label(trans('filament-auditing::filament-auditing.column.created_at')),
                TextColumn::make('old_values')
                    ->label(trans('filament-auditing::filament-auditing.column.old_values'))
                    ->html()
                    ->formatStateUsing(fn (Model $record): Htmlable => $this->formatData($record, name: 'old_values', state: $record->old_values ?? [])),
                TextColumn::make('new_values')
                    ->label(trans('filament-auditing::filament-auditing.column.new_values'))
                    ->html()
                    ->formatStateUsing(fn (Model $record): Htmlable => $this->formatData($record, name: 'new_values', state: $record->new_values ?? [])),
                self::extraColumns(),
            ]))
            ->filters(AuditFilters::configure())
            ->headerActions([
                //
            ])
            ->recordActions($tableActions)
            ->toolbarActions([
                //
            ]);
    }
}
