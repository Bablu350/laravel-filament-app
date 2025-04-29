<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure bank_details is only saved if valid
        if (isset($data['bank_details']) && isset($data['bank_details']['error'])) {
            unset($data['bank_details']);
        }
        return $data;
    }
}
