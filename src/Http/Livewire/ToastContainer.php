<?php

declare(strict_types=1);

namespace Vsent\LaravelToastify\Http\Livewire; // Updated Namespace

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\On;
use Vsent\LaravelToastify\Contracts\ToastManagerContract; // Updated Namespace
use Vsent\LaravelToastify\DTOs\ToastMessageDTO;      // Updated Namespace

class ToastContainer extends Component
{
    protected ToastManagerContract $toastManager;
    protected ConfigRepository $configRepo;

    protected Collection $backendToasts;
    public array $toastsForJs = [];

    public string $positionClasses = '';
    public string $maxWidthClasses = '';
    public array $livewireConfig = [];

    public function boot(ToastManagerContract $toastManager, ConfigRepository $configRepo): void
    {
        $this->toastManager = $toastManager;
        $this->configRepo = $configRepo;
        $this->livewireConfig = $this->configRepo->get('toasts', []);
    }

    public function mount(): void
    {
        $this->resolveContainerClasses();
        $this->loadToasts();
    }

    protected function resolveContainerClasses(): void
    {
        $position = $this->livewireConfig['display']['position'] ?? 'bottom-right';
        $mobilePosition = $this->livewireConfig['display']['mobile_position'] ?? $position;

        $posClasses = match ($mobilePosition) {
            'top-right' => 'top-0 right-0',
            'top-left' => 'top-0 left-0',
            'bottom-right' => 'bottom-0 right-0',
            'bottom-left' => 'bottom-0 left-0',
            'top-center' => 'top-0 left-1/2 -translate-x-1/2',
            'bottom-center' => 'bottom-0 left-1/2 -translate-x-1/2',
            default => 'bottom-0 right-0',
        };
        $desktopOverrideClasses = match ($position) {
            'top-right' => 'sm:top-0 sm:right-0',
            'top-left' => 'sm:top-0 sm:left-0',
            'bottom-right' => 'sm:bottom-0 sm:right-0',
            'bottom-left' => 'sm:bottom-0 sm:left-0',
            'top-center' => 'sm:top-0 sm:left-1/2 sm:-translate-x-1/2',
            'bottom-center' => 'sm:bottom-0 sm:left-1/2 sm:-translate-x-1/2',
            default => 'sm:bottom-0 sm:right-0',
        };
        $this->positionClasses = $posClasses . ($position !== $mobilePosition ? ' ' . $desktopOverrideClasses : '');
        $this->positionClasses .= ' fixed z-[9999] p-4';

        $maxWidth = $this->livewireConfig['display']['max_width'] ?? 'max-w-md';
        $mobileMaxWidth = $this->livewireConfig['display']['mobile_max_width'] ?? $maxWidth;
        $this->maxWidthClasses = $mobileMaxWidth . ($maxWidth !== $mobileMaxWidth ? ' sm:' . $maxWidth : '');
    }

    protected function loadToasts(): void
    {
        $this->backendToasts = $this->toastManager->get();
        $this->toastsForJs = $this->backendToasts
            ->map(fn(ToastMessageDTO $toast) => $toast->jsonSerialize())
            ->values()
            ->toArray();
    }

    #[On('toast-created')]
    #[On('refresh-toasts')]
    public function refreshToastsList(): void
    {
        $this->loadToasts();
    }

    #[On('navigate')]
    public function handleNavigation(): void
    {
        if ($this->livewireConfig['behavior']['clear_all_on_navigate'] ?? true) {
            $this->toastManager->clear();
            $this->loadToasts();
        }
    }

    public function dismiss(string $toastId): void
    {
        $this->toastManager->dismiss($toastId);
        $this->loadToasts();
    }

    public function render(): View
    {
        // Use the package's view namespace
        return view('laravel-toastify::container', [
            'config' => $this->livewireConfig,
        ]);
    }
}
