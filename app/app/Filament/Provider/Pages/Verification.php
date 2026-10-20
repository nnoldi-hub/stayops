<?php

namespace App\Filament\Provider\Pages;

use App\Models\Provider;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Verification extends Page
{
    protected static ?string $navigationLabel = 'Verificare KYC';

    protected static ?string $title = 'Verificare KYC';

    protected static string|\UnitEnum|null $navigationGroup = 'Cont';

    protected static ?int $navigationSort = 40;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected string $view = 'filament.provider.pages.verification';

    public function provider(): ?Provider
    {
        $user = auth()->user();

        return $user instanceof User ? $user->provider : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitKyc')
                ->label('Trimite documente')
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn (): bool => $this->provider()?->kyc_status !== 'approved')
                ->form([
                    FileUpload::make('documents')
                        ->label('Documente (CUI / act de identitate)')
                        ->multiple()
                        ->required()
                        ->disk('public')
                        ->directory('kyc/providers')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(5120),
                ])
                ->modalSubmitActionLabel('Trimite spre verificare')
                ->action(function (array $data): void {
                    $provider = $this->provider();

                    abort_if($provider === null, 403);

                    $provider->update([
                        'kyc_documents' => array_values(array_merge(
                            $provider->kyc_documents ?? [],
                            $data['documents'] ?? [],
                        )),
                        'kyc_status' => 'submitted',
                    ]);

                    Notification::make()
                        ->title('Documentele au fost trimise spre verificare')
                        ->success()
                        ->send();
                }),
        ];
    }
}
