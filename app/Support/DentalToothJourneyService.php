<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DentalChartEntry;
use App\Models\Patient;
use App\Models\TreatmentPlanItem;
use Illuminate\Support\Collection;

final class DentalToothJourneyService
{
    private const TREATMENT_STATUS_LABELS = [
        'planned' => 'برنامه‌ریزی‌شده',
        'approved' => 'تأییدشده',
        'in_progress' => 'در حال انجام',
        'completed' => 'تکمیل‌شده',
        'cancelled' => 'لغوشده',
    ];

    public function build(Patient $patient, ?string $selectedTooth = null): array
    {
        $chartEntries = $patient->dentalChartEntries()
            ->with('recorder')
            ->latest('id')
            ->get()
            ->groupBy('tooth_code');

        $treatmentItems = $patient->treatmentPlans()
            ->with(['items.stage', 'items.treatment', 'items.statusHistory.changer'])
            ->latest('id')
            ->get()
            ->flatMap(static function ($plan): Collection {
                return $plan->items->map(static fn (TreatmentPlanItem $item): array => [
                    'item' => $item,
                    'plan_title' => $plan->title,
                ]);
            })
            ->filter(static fn (array $item): bool => filled($item['item']->tooth_code))
            ->groupBy(static fn (array $item): string => (string) $item['item']->tooth_code);

        $activeCodes = $chartEntries->keys()
            ->merge($treatmentItems->keys())
            ->unique()
            ->values();

        $teeth = collect(DentalToothPresenter::all())
            ->mapWithKeys(function (array $tooth) use ($chartEntries, $treatmentItems, $activeCodes): array {
                $entries = $chartEntries->get($tooth['code'], collect());
                $items = $treatmentItems->get($tooth['code'], collect());
                $currentEntry = $entries->firstWhere('surface_code', 'all') ?? $entries->first();
                $statusCode = $currentEntry?->status_code;

                return [$tooth['code'] => [
                    ...$tooth,
                    'is_active' => $activeCodes->contains($tooth['code']),
                    'chart_entries' => $entries,
                    'treatment_items' => $items,
                    'latest_entry' => $currentEntry,
                    'status_code' => $statusCode,
                    'status_label' => $statusCode
                        ? (DentalChartEntry::STATUSES[$statusCode] ?? $statusCode)
                        : ($items->isNotEmpty() ? 'دارای طرح درمان' : 'بدون سابقه'),
                    'surfaces' => $entries->pluck('surface_code')->unique()->values()->all(),
                    'treatment_count' => $items->count(),
                ]];
            });

        $selectedCode = $selectedTooth && $teeth->has($selectedTooth) && $activeCodes->contains($selectedTooth)
            ? $selectedTooth
            : $activeCodes->first();

        $selected = $selectedCode ? $teeth->get($selectedCode) : null;
        $journeyByTooth = $teeth
            ->filter(static fn (array $tooth): bool => $tooth['is_active'])
            ->mapWithKeys(fn (array $tooth): array => [$tooth['code'] => $this->journey($tooth)]);

        return [
            'teeth' => $teeth,
            'activeCodes' => $activeCodes,
            'activeCount' => $activeCodes->count(),
            'hiddenCount' => max(0, count(DentalChartEntry::allToothCodes()) - $activeCodes->count()),
            'selectedTooth' => $selected,
            'selectedCode' => $selectedCode,
            'journey' => $selected ? $journeyByTooth->get($selectedCode) : $this->emptyJourney(),
            'journeyByTooth' => $journeyByTooth,
            'treatmentStatusLabels' => self::TREATMENT_STATUS_LABELS,
        ];
    }

    private function journey(array $tooth): array
    {
        $chartEntries = $tooth['chart_entries'];
        $treatmentItems = $tooth['treatment_items'];
        $timeline = collect();

        foreach ($chartEntries as $entry) {
            $timeline->push([
                'type' => 'clinical',
                'type_label' => 'رویداد بالینی',
                'title' => DentalChartEntry::STATUSES[$entry->status_code] ?? $entry->status_code,
                'subtitle' => DentalToothPresenter::surfaceLabel($entry->surface_code),
                'note' => $entry->note,
                'actor' => $entry->recorder?->name ?: 'کاربر سامانه',
                'at' => $entry->created_at,
            ]);
        }

        $treatmentJourneys = $treatmentItems->map(function (array $itemData) use ($timeline): array {
            /** @var TreatmentPlanItem $item */
            $item = $itemData['item'];
            $planTitle = $itemData['plan_title'];
            $history = $item->statusHistory->sortByDesc('created_at')->map(static fn ($status): array => [
                'from_status' => $status->from_status,
                'to_status' => $status->to_status,
                'to_status_label' => self::TREATMENT_STATUS_LABELS[$status->to_status] ?? $status->to_status,
                'reason' => $status->reason,
                'actor' => $status->changer?->name ?: 'کاربر سامانه',
                'at' => $status->created_at,
            ])->values()->all();

            foreach ($history as $status) {
                $timeline->push([
                    'type' => 'treatment',
                    'type_label' => 'تغییر مسیر درمان',
                    'title' => $status['to_status_label'],
                    'subtitle' => $item->stage?->name ?: 'آیتم طرح درمان',
                    'note' => $status['reason'],
                    'actor' => $status['actor'],
                    'at' => $status['at'],
                ]);
            }

            return [
                'id' => $item->id,
                'plan_title' => $planTitle,
                'stage' => $item->stage?->name ?: 'بدون مرحله',
                'treatment' => $item->treatment?->name ?: 'خدمت درمانی',
                'status' => $item->status,
                'status_label' => self::TREATMENT_STATUS_LABELS[$item->status] ?? $item->status,
                'priority' => $item->priority,
                'surface_code' => $item->surface_code,
                'surface_label' => DentalToothPresenter::surfaceLabel($item->surface_code ?: 'all'),
                'estimated_cost' => $item->estimated_cost,
                'planned_on' => $item->planned_on,
                'notes' => $item->notes,
                'status_history' => $history,
            ];
        })->values()->all();

        return [
            'clinicalEntries' => $chartEntries->map(static fn (DentalChartEntry $entry): array => [
                'status' => DentalChartEntry::STATUSES[$entry->status_code] ?? $entry->status_code,
                'surface_code' => $entry->surface_code,
                'surface' => DentalToothPresenter::surfaceLabel($entry->surface_code),
                'note' => $entry->note,
                'actor' => $entry->recorder?->name ?: 'کاربر سامانه',
                'at' => $entry->created_at,
            ])->values()->all(),
            'treatments' => $treatmentJourneys,
            'timeline' => $timeline->sortByDesc('at')->values()->all(),
            'nextAction' => $this->nextAction($treatmentJourneys),
        ];
    }

    private function nextAction(array $treatments): ?array
    {
        foreach (['in_progress', 'approved', 'planned'] as $status) {
            foreach ($treatments as $treatment) {
                if ($treatment['status'] === $status) {
                    return $treatment;
                }
            }
        }

        return null;
    }

    private function emptyJourney(): array
    {
        return [
            'clinicalEntries' => [],
            'treatments' => [],
            'timeline' => [],
            'nextAction' => null,
        ];
    }
}
