<?php

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Coupon;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Collection;

class AttendeeInsights extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.attendee-insights';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Attendee Insights';

    public ?array $data = [];

    public ?int $selectedEventId = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('event_id')
                    ->label('Filter by Event')
                    ->options(Event::pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('All Events')
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->selectedEventId = $state),
            ])
            ->statePath('data');
    }

    public function getTopCoupons(): Collection
    {
        $query = Registration::query()
            ->whereNotNull('coupon_code')
            ->where('coupon_code', '!=', '')
            ->whereIn('payment_status', ['paid', 'partial']);

        if ($this->selectedEventId) {
            $query->where('event_id', $this->selectedEventId);
        }

        return $query
            ->selectRaw('coupon_code, COUNT(*) as usage_count, SUM(discount_amount) as total_discount, SUM(paid_amount) as total_revenue')
            ->groupBy('coupon_code')
            ->orderByDesc('usage_count')
            ->limit(20)
            ->get();
    }

    public function getCountryBreakdown(): Collection
    {
        $query = Registration::query()
            ->whereIn('payment_status', ['paid', 'partial'])
            ->whereNotNull('metadata->country');

        if ($this->selectedEventId) {
            $query->where('event_id', $this->selectedEventId);
        }

        return $query
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.country")) as country, COUNT(*) as count')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(20)
            ->get();
    }

    public function getCompanyTypeBreakdown(): Collection
    {
        $query = Registration::query()
            ->whereIn('payment_status', ['paid', 'partial'])
            ->whereNotNull('metadata->attendee_type');

        if ($this->selectedEventId) {
            $query->where('event_id', $this->selectedEventId);
        }

        return $query
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.attendee_type")) as attendee_type, COUNT(*) as count')
            ->groupBy('attendee_type')
            ->orderByDesc('count')
            ->get();
    }

    public function getTopCompanies(): Collection
    {
        $query = Registration::query()
            ->whereNotNull('company')
            ->where('company', '!=', '')
            ->whereIn('payment_status', ['paid', 'partial']);

        if ($this->selectedEventId) {
            $query->where('event_id', $this->selectedEventId);
        }

        return $query
            ->selectRaw('company, COUNT(*) as attendee_count')
            ->groupBy('company')
            ->orderByDesc('attendee_count')
            ->limit(20)
            ->get();
    }

    public function getRevenueBySegment(): array
    {
        $query = Registration::query()
            ->whereIn('payment_status', ['paid', 'partial']);

        if ($this->selectedEventId) {
            $query->where('event_id', $this->selectedEventId);
        }

        $totalRevenue = $query->sum('paid_amount');
        $totalDiscount = $query->sum('discount_amount');
        $avgTicketPrice = $query->avg('paid_amount');

        return [
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'avg_ticket_price' => $avgTicketPrice,
            'total_registrations' => $query->count(),
        ];
    }
}
