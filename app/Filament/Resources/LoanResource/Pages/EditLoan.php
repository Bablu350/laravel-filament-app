<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class EditLoan extends EditRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function afterSave(): void
    {
        $loan = $this->record;
        $data = $this->form->getState();

        if (
            isset($data['due_date'], $data['emi_type'], $data['loan_age'], $data['emi_amount']) &&
            (
                $loan->wasChanged('due_date') ||
                $loan->wasChanged('emi_type') ||
                $loan->wasChanged('loan_age') ||
                $loan->wasChanged('emi_amount')
            )
        ) {
            // Delete existing EMIs
            $loan->emis()->delete();
            try {
                $dueDate = Carbon::parse($data['due_date']);
                if (!$dueDate->isValid()) {
                    throw new \Exception('Invalid due_date format: ' . $data['due_date']);
                }

                $emiCount = (int) $data['loan_age'];
                $emis = [];

                for ($i = 0; $i < $emiCount; $i++) {
                    $emis[] = [
                        'loan_id' => $loan->id,
                        'due_date' => $dueDate->copy()->add(match ($data['emi_type']) {
                            'weekly' => new \DateInterval('P' . (7 * $i) . 'D'),
                            'bi-weekly' => new \DateInterval('P' . (14 * $i) . 'D'),
                            'monthly' => new \DateInterval('P' . $i . 'M'),
                        })->format('Y-m-d'),
                        'emi_amount' => $data['emi_amount'],
                        'emi_paid_amount' => 0,
                        'payment_date' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $loan->emis()->createMany($emis);
                Log::info('EMIs created', ['count' => count($emis)]);
            } catch (\Exception $e) {
                Log::error('EMI creation failed', ['error' => $e->getMessage()]);
                throw $e; // Re-throw to rollback transaction
            }
        }
    }
}
