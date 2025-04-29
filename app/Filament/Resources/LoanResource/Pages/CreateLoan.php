<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;
    protected function afterCreate(): void
    {
        DB::transaction(function () {
            $loan = $this->record;
            $data = $this->form->getState();
    
            Log::info('CreateLoan afterCreate', ['data' => $data]);
    
            if (isset($data['due_date'], $data['emi_type'], $data['loan_age'], $data['emi_amount'])) {
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
            } else {
                Log::error('Missing required fields for EMI generation', ['data' => $data]);
            }
        });
    }
}
